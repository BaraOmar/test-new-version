<?php

namespace common\modules\survey\services;

use common\helpers\EmployeeEngagementFilter;
use common\modules\employee\models\Department;
use common\modules\employee\models\Employee;
use common\modules\survey\models\Survey;
use common\modules\survey\models\SurveyEmployeeTarget;
use common\modules\survey\models\SurveyResponse;
use common\modules\survey\models\SurveyTarget;
use Yii;
use yii\db\Expression;
use yii\db\Query;

class SurveyAccessService
{
    public static function tableExists(string $table): bool
    {
        return Yii::$app->db->schema->getTableSchema($table, true) !== null;
    }

    public static function findEmployeeByUserId(int $userId): ?Employee
    {
        if (!Employee::hasColumn('user_id')) {
            return null;
        }

        return Employee::find()->where(['user_id' => $userId])->one();
    }

    /**
     * Visibility: explicit employee list, and/or department match, else organization-wide.
     */
    public static function isAssignedToEmployee(Survey $survey, Employee $employee): bool
    {
        if (!$employee->canReceiveEmployeePortalData()) {
            return false;
        }

        if (!self::tableExists('{{%surveys}}')) {
            return false;
        }

        $employeeId = (int)$employee->id;
        $departmentId = $employee->hasAttribute('department_id') ? (int)$employee->department_id : 0;

        if (self::tableExists('{{%survey_employee_targets}}')
            && SurveyEmployeeTarget::find()
                ->where(['survey_id' => $survey->id, 'employee_id' => $employeeId])
                ->exists()) {
            return true;
        }

        if (self::tableExists('{{%survey_targets}}')
            && SurveyTarget::find()->where(['survey_id' => $survey->id])->exists()) {
            return self::matchesDepartmentTarget($survey, $employee);
        }

        if (self::tableExists('{{%survey_employee_targets}}')
            && SurveyEmployeeTarget::find()->where(['survey_id' => $survey->id])->exists()) {
            return false;
        }

        $audience = $survey->hasAttribute('audience_type')
            ? strtolower(trim((string)$survey->audience_type))
            : '';

        if ($audience === 'employee' || $audience === 'department') {
            return false;
        }

        return true;
    }

    public static function findResponse(Survey $survey, int $employeeId): ?SurveyResponse
    {
        if (!self::tableExists('{{%survey_responses}}')) {
            return null;
        }

        return SurveyResponse::find()
            ->where(['survey_id' => $survey->id, 'employee_id' => $employeeId])
            ->orderBy(['id' => SORT_DESC])
            ->one();
    }

    public static function hasSubmitted(Survey $survey, int $employeeId): bool
    {
        return self::findResponse($survey, $employeeId) !== null;
    }

    public static function canRespond(Survey $survey, Employee $employee): array
    {
        if (!$employee->canReceiveEmployeePortalData()) {
            return [false, 'Your employment has ended. You can no longer submit responses.'];
        }

        if (!self::isAssignedToEmployee($survey, $employee)) {
            return [false, 'You are not assigned to this survey.'];
        }

        if (!EmployeeEngagementFilter::isSentAfterEmployeeActive(
            $survey,
            $employee,
            '{{%survey_employee_targets}}',
            'survey_id'
        )) {
            return [false, 'This survey was sent before you joined.'];
        }

        if (self::hasSubmitted($survey, (int)$employee->id)) {
            return [false, 'You have already submitted this survey.'];
        }

        $status = strtolower(trim((string)($survey->status ?? 'active')));
        if ($status === 'closed' || $status === 'draft') {
            return [false, 'This survey is not open for responses.'];
        }

        if ($survey->hasAttribute('start_at') && $survey->start_at) {
            $startTs = strtotime((string)$survey->start_at);
            if ($startTs && time() < $startTs) {
                return [false, 'This survey has not started yet.'];
            }
        }

        if ($survey->hasAttribute('end_at') && $survey->end_at) {
            $endTs = strtotime((string)$survey->end_at);
            if ($endTs && time() > $endTs) {
                return [false, 'This survey is closed.'];
            }
        }

        return [true, ''];
    }

    /**
     * @return Survey[]
     */
    public static function findAssignedSurveys(Employee $employee, ?string $search = null): array
    {
        if (!$employee->canReceiveEmployeePortalData() || !self::tableExists('{{%surveys}}')) {
            return [];
        }

        $ids = self::assignedSurveyIdsSubquery($employee);
        $query = Survey::find()->where(['id' => $ids])->orderBy(
            Survey::hasColumn('created_at') ? ['created_at' => SORT_DESC, 'id' => SORT_DESC] : ['id' => SORT_DESC]
        );

        if ($search !== null && trim($search) !== '') {
            $query->andWhere(['like', 'title', trim($search)]);
        }

        return $query->all();
    }

    /**
     * @return int[]
     */
    public static function assignedSurveyIdsSubquery(Employee $employee): Query
    {
        $employeeId = (int)$employee->id;
        $departmentId = $employee->hasAttribute('department_id') ? (int)$employee->department_id : 0;

        $employeeTargetExists = (new Query())
            ->select(new Expression('1'))
            ->from('{{%survey_employee_targets}} se')
            ->where('se.survey_id = s.id');

        $departmentTargetExists = (new Query())
            ->select(new Expression('1'))
            ->from('{{%survey_targets}} st')
            ->where('st.survey_id = s.id');

        $conditions = [
            ['exists', (clone $employeeTargetExists)->andWhere(['se.employee_id' => $employeeId])],
            [
                'and',
                ['not exists', $departmentTargetExists],
                ['not exists', $employeeTargetExists],
            ],
        ];

        if ($departmentId > 0) {
            $conditions[] = [
                'exists',
                (clone $departmentTargetExists)->andWhere(['st.department_id' => $departmentId]),
            ];
        }

        if (Department::hasColumn('manager_id')) {
            $managedDeptMatch = (new Query())
                ->select(new Expression('1'))
                ->from(['st' => '{{%survey_targets}}'])
                ->innerJoin(['d' => '{{%departments}}'], 'd.id = st.department_id')
                ->where('st.survey_id = s.id')
                ->andWhere(['d.manager_id' => $employeeId]);
            $conditions[] = ['exists', $managedDeptMatch];
        }

        $query = (new Query())
            ->select('s.id')
            ->from(['s' => '{{%surveys}}'])
            ->where(array_merge(['or'], $conditions));

        EmployeeEngagementFilter::applySentAfterActiveToQuery(
            $query,
            $employee,
            '{{%surveys}}',
            '{{%survey_employee_targets}}',
            'survey_id',
            's'
        );

        return $query;
    }

    /**
     * Member of a targeted department, or head of that department (departments.manager_id).
     */
    public static function matchesDepartmentTarget(Survey $survey, Employee $employee): bool
    {
        if (!self::tableExists('{{%survey_targets}}')) {
            return false;
        }

        $departmentId = $employee->hasAttribute('department_id') ? (int)$employee->department_id : 0;
        if ($departmentId > 0) {
            $memberMatch = SurveyTarget::find()
                ->where(['survey_id' => $survey->id, 'department_id' => $departmentId])
                ->exists();
            if ($memberMatch) {
                return true;
            }
        }

        if (!Department::hasColumn('manager_id')) {
            return false;
        }

        return SurveyTarget::find()
            ->alias('st')
            ->innerJoin(['d' => Department::tableName()], 'd.id = st.department_id')
            ->where(['st.survey_id' => $survey->id, 'd.manager_id' => (int)$employee->id])
            ->exists();
    }

    public static function countPendingForEmployee(Employee $employee): int
    {
        if (!self::tableExists('{{%surveys}}') || !self::tableExists('{{%survey_responses}}')) {
            return 0;
        }

        $assignedIds = self::assignedSurveyIdsSubquery($employee);
        $query = Survey::find()->alias('s')->where(['s.id' => $assignedIds]);

        if (Survey::hasColumn('status')) {
            $query->andWhere(['not in', 's.status', ['closed', 'draft']]);
        }

        $surveys = $query->all();
        $pending = 0;
        foreach ($surveys as $survey) {
            if (!self::hasSubmitted($survey, (int)$employee->id) && self::canRespond($survey, $employee)[0]) {
                $pending++;
            }
        }

        return $pending;
    }

    /**
     * @return array{text:string,color:string}
     */
    public static function timeLeftLabel(Survey $survey): array
    {
        $now = time();
        $status = strtolower(trim((string)($survey->status ?? 'active')));

        if ($survey->hasAttribute('start_at') && $survey->start_at) {
            $startTs = strtotime((string)$survey->start_at);
            if ($startTs && $now < $startTs) {
                return [
                    'text' => 'Starts in ' . self::humanTimeDiff($startTs - $now),
                    'color' => 'text-amber-600 dark:text-amber-400',
                ];
            }
        }

        if ($survey->hasAttribute('end_at') && $survey->end_at) {
            $endTs = strtotime((string)$survey->end_at);
            if ($endTs) {
                if ($now > $endTs) {
                    return ['text' => 'Closed', 'color' => 'text-rose-600 dark:text-rose-400'];
                }

                return [
                    'text' => 'Ends in ' . self::humanTimeDiff($endTs - $now),
                    'color' => 'text-rose-600 dark:text-rose-400',
                ];
            }
        }

        if ($status === 'draft') {
            return ['text' => 'Draft', 'color' => 'text-slate-500 dark:text-slate-400'];
        }
        if ($status === 'scheduled') {
            return ['text' => 'Scheduled', 'color' => 'text-amber-600 dark:text-amber-400'];
        }

        return ['text' => 'No deadline', 'color' => 'text-slate-500 dark:text-slate-400'];
    }

    public static function statusBadgeClass(string $status): string
    {
        return match (strtolower($status)) {
            'active' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
            'scheduled' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
            'closed' => 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400',
            default => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300',
        };
    }

    private static function humanTimeDiff(int $seconds): string
    {
        $days = (int)floor($seconds / 86400);
        $hours = (int)floor(($seconds % 86400) / 3600);
        $minutes = max(1, (int)floor(($seconds % 3600) / 60));

        if ($days > 0) {
            return $days . ' day' . ($days > 1 ? 's' : '');
        }
        if ($hours > 0) {
            return $hours . ' hour' . ($hours > 1 ? 's' : '');
        }

        return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    }
}
