<?php

namespace common\modules\survey\models;

use common\modules\employee\models\Employee;
use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $status
 * @property string|null $audience_type
 * @property int|null $is_anonymous
 * @property string|null $start_at
 * @property string|null $end_at
 * @property int|null $duration_days
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int $content_version
 */
class Survey extends ActiveRecord
{
    public $questions_json = '[]';
    public $target_type = 'organization';
    public $departments = [];
    public $employees = [];
    public $start_date;
    public $start_time;
    public $end_date;
    public $end_time;
    public $anonymous = 0;

    public static function tableName()
    {
        return '{{%surveys}}';
    }

    public function rules()
    {
        $rules = [
            [['title'], 'required', 'except' => ['schedule']],
            [['title'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['questions_json'], 'validateQuestions', 'except' => ['schedule']],
            [['start_date'], 'validateScheduleRange', 'on' => ['default', 'schedule']],
            [['target_type'], 'in', 'range' => ['organization', 'department', 'employee']],
            [['departments', 'employees'], 'each', 'rule' => ['integer']],
            [['start_date', 'end_date'], 'date', 'format' => 'php:Y-m-d'],
            [['start_time', 'end_time'], 'match', 'pattern' => '/^\d{2}:\d{2}(:\d{2})?$/'],
            [['anonymous'], 'boolean'],
            [['target_type'], 'default', 'value' => 'organization'],
            [['questions_json'], 'default', 'value' => '[]'],
            [['departments', 'employees'], 'default', 'value' => []],
            [['description', 'start_date', 'start_time', 'end_date', 'end_time'], 'default', 'value' => null],
            [['departments'], 'validateDepartments'],
            [['employees'], 'validateEmployees'],
        ];

        foreach (['status', 'audience_type'] as $column) {
            if (self::hasColumn($column)) {
                $rules[] = [[$column], 'string', 'max' => 255];
            }
        }

        foreach (['is_anonymous', 'anonymous', 'duration_days', 'content_version'] as $column) {
            if (self::hasColumn($column)) {
                $rules[] = [[$column], 'integer'];
            }
        }

        foreach (['start_at', 'end_at', 'created_at', 'updated_at'] as $column) {
            if (self::hasColumn($column)) {
                $rules[] = [[$column], 'safe'];
            }
        }

        return $rules;
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['schedule'] = ['start_date', 'start_time', 'end_date', 'end_time'];

        return $scenarios;
    }

    public function validateScheduleRange($attribute): void
    {
        if (!$this->start_date || !$this->end_date) {
            return;
        }

        $start = strtotime((string)$this->start_date);
        $end = strtotime((string)$this->end_date);
        if ($start && $end && $end < $start) {
            $this->addError('end_date', 'End date must be on or after the start date.');
        }
    }

    public function populateScheduleFromDb(): void
    {
        $this->start_date = null;
        $this->start_time = null;
        $this->end_date = null;
        $this->end_time = null;

        if ($this->hasAttribute('start_at') && $this->start_at) {
            $ts = strtotime((string)$this->start_at);
            if ($ts) {
                $this->start_date = date('Y-m-d', $ts);
                $this->start_time = date('H:i', $ts);
            }
        }

        if ($this->hasAttribute('end_at') && $this->end_at) {
            $ts = strtotime((string)$this->end_at);
            if ($ts) {
                $this->end_date = date('Y-m-d', $ts);
                $this->end_time = date('H:i', $ts);
            }
        }
    }

    /**
     * Set status from open/close times (does not change draft surveys).
     */
    public function syncScheduleToAttributes(): void
    {
        if ($this->hasAttribute('start_at')) {
            $this->start_at = $this->composeDateTime($this->start_date, $this->start_time, '00:00:00');
        }

        if ($this->hasAttribute('end_at')) {
            $this->end_at = $this->composeDateTime($this->end_date, $this->end_time, '23:59:59');
        }

        if ($this->hasAttribute('duration_days')) {
            if ($this->start_date && $this->end_date) {
                $start = new \DateTime($this->start_date);
                $end = new \DateTime($this->end_date);
                $this->duration_days = $start->diff($end)->days + 1;
            } else {
                $this->duration_days = null;
            }
        }
    }

    public function applyScheduleStatus(): void
    {
        if (!$this->hasAttribute('status')) {
            return;
        }

        if (strtolower((string)($this->status ?? '')) === 'draft') {
            return;
        }

        $now = time();

        if ($this->hasAttribute('end_at') && $this->end_at) {
            $endTs = strtotime((string)$this->end_at);
            if ($endTs && $now > $endTs) {
                $this->status = 'closed';

                return;
            }
        }

        if ($this->hasAttribute('start_at') && $this->start_at) {
            $startTs = strtotime((string)$this->start_at);
            if ($startTs && $now < $startTs) {
                $this->status = 'scheduled';

                return;
            }
        }

        $this->status = 'active';
    }

    public function validateDepartments($attribute): void
    {
        if ($this->target_type !== 'department') {
            return;
        }

        $ids = array_values(array_filter(array_map('intval', (array)$this->departments)));
        if ($ids === []) {
            $this->addError($attribute, 'Select at least one department.');
        }
    }

    public function validateEmployees($attribute): void
    {
        if ($this->target_type !== 'employee') {
            return;
        }

        $ids = array_values(array_filter(array_map('intval', (array)$this->employees)));
        if ($ids === []) {
            $this->addError($attribute, 'Select at least one employee.');
        }
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $this->target_type = $this->target_type ?: 'organization';
        if ($this->target_type === 'organization') {
            $this->departments = [];
            $this->employees = [];
        } elseif ($this->target_type === 'department') {
            $this->employees = [];
        } elseif ($this->target_type === 'employee') {
            $this->departments = [];
        }

        if ($this->hasAttribute('description')) {
            $this->description = self::normalizeDescription($this->description);
        }

        return true;
    }

    /**
     * Plain text only — strips pasted HTML/fonts/styles from descriptions.
     */
    public static function normalizeDescription(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $value = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/<br\s*\/?>/i', "\n", $value);
        $value = preg_replace('/<\/p>/i', "\n", $value);
        $value = strip_tags($value);
        $value = str_replace(["\r\n", "\r"], "\n", (string)$value);

        $lines = explode("\n", $value);
        $lines = array_map(
            static fn(string $line): string => trim(preg_replace('/[ \t]+/u', ' ', $line)),
            $lines
        );

        return trim(implode("\n", $lines));
    }

    public function getDescriptionPlain(): string
    {
        return self::normalizeDescription($this->description ?? '');
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->hasAttribute('audience_type')) {
            $this->audience_type = $this->target_type;
        }

        if ($this->hasAttribute('is_anonymous')) {
            $this->is_anonymous = (int)$this->anonymous;
        } elseif ($this->hasAttribute('anonymous')) {
            $this->setAttribute('anonymous', (int)$this->anonymous);
        }

        $this->syncScheduleToAttributes();

        if ($this->hasAttribute('status') && !$this->status) {
            $this->status = $this->start_date && strtotime($this->start_date) > time() ? 'scheduled' : 'active';
        }

        if ($insert && $this->hasAttribute('created_at') && !$this->created_at) {
            $this->created_at = date('Y-m-d H:i:s');
        }

        return true;
    }

    public function validateQuestions($attribute)
    {
        $answerable = ['multiple_choice', 'checkbox', 'dropdown', 'short_text', 'long_text', 'rating', 'linear_scale'];
        $count = 0;
        foreach ($this->getQuestionPayload() as $question) {
            $type = (string)($question['type'] ?? '');
            if (!in_array($type, $answerable, true)) {
                continue;
            }
            if (trim((string)($question['text'] ?? '')) !== '') {
                $count++;
            }
        }
        if ($count === 0) {
            $this->addError($attribute, 'Please add at least one survey question.');
        }
    }

    public function getQuestionPayload()
    {
        $questions = json_decode((string)$this->questions_json, true);
        if (!is_array($questions)) {
            return [];
        }

        return array_values(array_filter($questions, static function ($question) {
            $type = (string)($question['type'] ?? 'short_text');
            if (in_array($type, ['section_break', 'image', 'video'], true)) {
                return true;
            }
            if ($type === 'section_title') {
                return trim((string)($question['text'] ?? '')) !== '' || trim((string)($question['sectionTitle'] ?? '')) !== '';
            }

            return trim((string)($question['text'] ?? '')) !== '';
        }));
    }

    public function getContentVersion(): int
    {
        if ($this->hasAttribute('content_version') && (int)$this->content_version > 0) {
            return (int)$this->content_version;
        }

        if (SurveyQuestion::hasColumn('content_version')) {
            $max = (int)SurveyQuestion::find()
                ->where(['survey_id' => $this->id])
                ->max('content_version');
            if ($max > 0) {
                return $max;
            }
        }

        return 1;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions()
    {
        $order = SurveyQuestion::hasColumn('position') ? ['position' => SORT_ASC, 'id' => SORT_ASC] : ['id' => SORT_ASC];
        $query = $this->hasMany(SurveyQuestion::class, ['survey_id' => 'id']);
        if (SurveyQuestion::hasColumn('content_version')) {
            $query->andWhere(['content_version' => $this->getContentVersion()]);
        }

        return $query->orderBy($order);
    }

    public function getResponses()
    {
        return $this->hasMany(SurveyResponse::class, ['survey_id' => 'id']);
    }

    public function getDepartmentTargets()
    {
        return $this->hasMany(SurveyTarget::class, ['survey_id' => 'id']);
    }

    public function getEmployeeTargets()
    {
        return $this->hasMany(SurveyEmployeeTarget::class, ['survey_id' => 'id']);
    }

    public function getResponseCount()
    {
        return (int)$this->getResponses()->count();
    }

    public function getAudienceType()
    {
        if (SurveyTarget::find()->where(['survey_id' => $this->id])->exists()) {
            return 'department';
        }

        if (SurveyEmployeeTarget::find()->where(['survey_id' => $this->id])->exists()) {
            return 'employee';
        }

        if ($this->hasAttribute('audience_type') && $this->audience_type) {
            $stored = strtolower(trim((string)$this->audience_type));
            if (in_array($stored, ['organization', 'department', 'employee'], true)) {
                return $stored;
            }
        }

        return 'organization';
    }

    public function getStoredAudienceType(): string
    {
        if ($this->hasAttribute('audience_type') && $this->audience_type) {
            $stored = strtolower(trim((string)$this->audience_type));
            if (in_array($stored, ['organization', 'department', 'employee'], true)) {
                return $stored;
            }
        }

        return 'organization';
    }

    /**
     * @return int[]
     */
    public function getIndividualTargetEmployeeIds(): array
    {
        if ($this->hasDepartmentTargets()) {
            return [];
        }

        $ids = array_map(
            'intval',
            SurveyEmployeeTarget::find()
                ->select('employee_id')
                ->where(['survey_id' => $this->id])
                ->column()
        );
        $ids = array_values(array_unique(array_filter($ids, static fn(int $id): bool => $id > 0)));

        if ($ids !== []) {
            return $ids;
        }

        if ($this->getStoredAudienceType() === 'employee') {
            $ids = array_map(
                'intval',
                SurveyResponse::find()
                    ->select('employee_id')
                    ->where(['survey_id' => $this->id])
                    ->column()
            );

            return array_values(array_unique(array_filter($ids, static fn(int $id): bool => $id > 0)));
        }

        return [];
    }

    public function ensureEmployeeTargetRecord(int $employeeId): void
    {
        if ($employeeId <= 0 || $this->hasDepartmentTargets()) {
            return;
        }

        if (SurveyEmployeeTarget::find()
            ->where(['survey_id' => $this->id, 'employee_id' => $employeeId])
            ->exists()) {
            return;
        }

        (new SurveyEmployeeTarget([
            'survey_id' => $this->id,
            'employee_id' => $employeeId,
        ]))->save(false);

        if ($this->hasAttribute('audience_type') && $this->getStoredAudienceType() !== 'employee') {
            $this->audience_type = 'employee';
            $this->save(false, ['audience_type']);
        }
    }

    public function repairEmployeeTargetsFromResponses(): void
    {
        if ($this->hasDepartmentTargets() || $this->getStoredAudienceType() !== 'employee') {
            return;
        }

        if (SurveyEmployeeTarget::find()->where(['survey_id' => $this->id])->exists()) {
            return;
        }

        foreach ($this->getIndividualTargetEmployeeIds() as $employeeId) {
            $this->ensureEmployeeTargetRecord($employeeId);
        }
    }

    public function getTargetCount()
    {
        if ($this->hasDepartmentTargets()) {
            $departmentIds = SurveyTarget::find()->select('department_id')->where(['survey_id' => $this->id])->column();
            if ($departmentIds === []) {
                return 0;
            }

            $conditions = ['or'];
            if (Employee::hasColumn('department_id')) {
                $conditions[] = ['department_id' => $departmentIds];
            }
            if (\common\modules\employee\models\Department::hasColumn('manager_id')) {
                $conditions[] = [
                    'id' => \common\modules\employee\models\Department::find()
                        ->select('manager_id')
                        ->where(['id' => $departmentIds])
                        ->andWhere(['not', ['manager_id' => null]]),
                ];
            }

            return count($conditions) > 1
                ? (int)Employee::find()->where($conditions)->count()
                : 0;
        }

        $ids = $this->getIndividualTargetEmployeeIds();
        if ($ids !== []) {
            return count($ids);
        }

        if ($this->getAudienceType() === 'organization') {
            return (int)Employee::find()->count();
        }

        return 0;
    }

    /**
     * @return Employee[]
     */
    public function getTargetEmployees(): array
    {
        if ($this->hasDepartmentTargets()) {
            $departmentIds = SurveyTarget::find()
                ->select('department_id')
                ->where(['survey_id' => $this->id])
                ->column();
            if ($departmentIds === []) {
                return [];
            }

            $conditions = ['or'];
            if (Employee::hasColumn('department_id')) {
                $conditions[] = ['department_id' => $departmentIds];
            }
            if (\common\modules\employee\models\Department::hasColumn('manager_id')) {
                $conditions[] = [
                    'id' => \common\modules\employee\models\Department::find()
                        ->select('manager_id')
                        ->where(['id' => $departmentIds])
                        ->andWhere(['not', ['manager_id' => null]]),
                ];
            }

            if (count($conditions) <= 1) {
                return [];
            }

            return Employee::find()
                ->where($conditions)
                ->orderBy(['full_name' => SORT_ASC])
                ->all();
        }

        $ids = $this->getIndividualTargetEmployeeIds();
        if ($ids !== []) {
            $order = Employee::hasColumn('full_name') ? ['full_name' => SORT_ASC] : ['id' => SORT_ASC];

            return Employee::find()->where(['id' => $ids])->orderBy($order)->all();
        }

        if ($this->getAudienceType() === 'organization') {
            return Employee::find()->orderBy(['full_name' => SORT_ASC])->all();
        }

        return [];
    }

    /**
     * @return array{count: int, employees: array<int, array{id: int, full_name: string, email: string}>}
     */
    public function getPendingRespondents(): array
    {
        $targets = $this->getTargetEmployees();
        $respondedIds = array_map(
            'intval',
            SurveyResponse::find()
                ->select('employee_id')
                ->where(['survey_id' => $this->id])
                ->column()
        );
        $respondedLookup = array_fill_keys($respondedIds, true);

        $employees = [];
        foreach ($targets as $employee) {
            $id = (int)$employee->id;
            if (isset($respondedLookup[$id])) {
                continue;
            }
            $employees[] = [
                'id' => $id,
                'full_name' => $employee->getDisplayName(),
                'email' => trim((string)($employee->email ?? '')),
            ];
        }

        return [
            'count' => count($employees),
            'employees' => $employees,
        ];
    }

    public function getIsAnonymous()
    {
        if ($this->hasAttribute('is_anonymous')) {
            return (bool)$this->is_anonymous;
        }
        if ($this->hasAttribute('anonymous')) {
            return (bool)$this->getAttribute('anonymous');
        }

        return true;
    }

    /** @return string[] */
    public function getTargetDepartmentNames(): array
    {
        if ($this->isRelationPopulated('departmentTargets') && $this->departmentTargets !== []) {
            $names = [];
            foreach ($this->departmentTargets as $target) {
                $name = trim((string)($target->department->name ?? ''));
                if ($name !== '') {
                    $names[] = $name;
                }
            }
            if ($names !== []) {
                sort($names, SORT_NATURAL | SORT_FLAG_CASE);

                return $names;
            }
        }

        $ids = SurveyTarget::find()
            ->select('department_id')
            ->where(['survey_id' => $this->id])
            ->column();

        if ($ids === []) {
            return [];
        }

        return \common\modules\employee\models\Department::find()
            ->select('name')
            ->where(['id' => $ids])
            ->orderBy(['name' => SORT_ASC])
            ->column();
    }

    public function hasDepartmentTargets(): bool
    {
        return SurveyTarget::find()->where(['survey_id' => $this->id])->exists();
    }

    /** @return string[] */
    public function getTargetEmployeeNames(): array
    {
        if ($this->hasDepartmentTargets()) {
            return [];
        }

        if ($this->isRelationPopulated('employeeTargets') && $this->employeeTargets !== []) {
            $names = [];
            foreach ($this->employeeTargets as $target) {
                $employee = $target->employee ?? null;
                if ($employee !== null) {
                    $names[] = $employee->getDisplayName();
                }
            }
            if ($names !== []) {
                sort($names, SORT_NATURAL | SORT_FLAG_CASE);

                return $names;
            }
        }

        $ids = $this->getIndividualTargetEmployeeIds();
        if ($ids === []) {
            return [];
        }

        $order = Employee::hasColumn('full_name') ? ['full_name' => SORT_ASC] : ['id' => SORT_ASC];
        $employees = Employee::find()->where(['id' => $ids])->orderBy($order)->all();
        $names = array_map(static fn(Employee $employee): string => $employee->getDisplayName(), $employees);
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    /**
     * Audience summary for results header (department names or employee names).
     */
    public function getTargetDisplayLabel(): string
    {
        if ($this->hasDepartmentTargets()) {
            $names = $this->getTargetDepartmentNames();

            return $names !== [] ? implode(', ', $names) : 'No departments selected';
        }

        $employeeNames = $this->getTargetEmployeeNames();
        if ($employeeNames !== []) {
            return implode(', ', $employeeNames);
        }

        if ($this->getAudienceType() === 'organization') {
            return 'Organization-wide (all employees)';
        }

        return 'Individual employees';
    }

    /**
     * Human-readable audience line for survey list cards.
     */
    public function getAudienceDisplayLine(): string
    {
        return $this->getTargetDisplayLabel();
    }

    /**
     * Formatted survey due date (close date/time), or null if open-ended.
     */
    public function getDueDateLabel(): ?string
    {
        if (!$this->hasAttribute('end_at') || !$this->end_at) {
            return null;
        }

        try {
            return (new \DateTime((string)$this->end_at))->format('M j, Y g:i A');
        } catch (\Exception) {
            return (string)$this->end_at;
        }
    }

    public static function hasColumn($column)
    {
        $schema = Yii::$app->db->schema->getTableSchema(static::tableName());
        return $schema !== null && $schema->getColumn($column) !== null;
    }

    protected function composeDateTime($date, $time, $defaultTime)
    {
        if (!$date) {
            return null;
        }

        return $date . ' ' . ($time ?: $defaultTime);
    }
}
