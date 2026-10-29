<?php

namespace frontend\modules\hr\models;

use common\modules\employee\models\Department;
use common\modules\employee\models\Employee;
use common\modules\employee\models\EmployeeSchoolSubject;
use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolSubject;
use Yii;
use yii\base\Model;

class OnboardingEmployeeForm extends Model
{
    public ?int $id = null;
    public string $full_name = '';
    public string $portal_email = '';
    public string $portal_password = '';
    public string $personal_email = '';
    public ?string $phone = null;
    public ?string $marital_status = null;
    public string $job_title = '';
    public ?string $position_level = null;
    public string $joining_date = '';
    public ?string $employment_type = null;
    public int $teacher = 0;
    /** @var int|string|null */
    public int|string|null $manager_id = null;
    /** @var int|string|null */
    public int|string|null $department_id = null;
    /** @var int[] */
    public array $subject_ids = [];
    public ?string $location = null;

    public function init(): void
    {
        parent::init();

        if ($this->id === null && Employee::hasColumn('joining_date') && trim($this->joining_date) === '') {
            $this->joining_date = date('Y-m-d');
        }
    }

    public function rules()
    {
        $required = ['full_name', 'job_title'];
        if (Employee::hasColumn('joining_date')) {
            $required[] = 'joining_date';
        }

        $rules = [
            [$required, 'required'],
            [['personal_email'], 'trim'],
            [['personal_email'], 'email', 'skipOnEmpty' => true],
            [['full_name', 'job_title'], 'match', 'pattern' => '/^[a-zA-Z\s]+$/', 'message' => '{attribute} must contain letters and spaces only.'],
            [['department_id', 'manager_id'], 'filter', 'filter' => static function ($value) {
                if ($value === '' || $value === null) {
                    return null;
                }
                return (int)$value;
            }],
            [['department_id', 'manager_id'], 'integer'],
            [['phone'], 'trim'],
            [
                ['phone'],
                'unique',
                'targetClass' => Employee::class,
                'targetAttribute' => 'phone',
                'filter' => function ($query) {
                    if ($this->id !== null) {
                        $query->andWhere(['!=', 'id', (int)$this->id]);
                    }
                },
                'skipOnEmpty' => true,
                'message' => 'This phone number is already in use by another employee.',
            ],
            [
                ['marital_status', 'position_level', 'employment_type'],
                'filter',
                'filter' => static fn($value) => $value === '' || $value === null ? null : $value,
            ],
            [['phone', 'marital_status', 'position_level', 'employment_type', 'location'], 'string', 'max' => 255],
            ['location', 'filter', 'filter' => [self::class, 'normalizeWorkLocation']],
            ['location', 'in', 'range' => ['remote', 'hybrid', 'onsite'], 'skipOnEmpty' => true],
            [['portal_password'], 'string', 'min' => 8, 'skipOnEmpty' => true],
            ['manager_id', 'validateManager'],
            ['department_id', 'validateDepartment'],
            [['subject_ids'], 'filter', 'filter' => static function ($value) {
                if (!is_array($value)) {
                    return [];
                }

                return array_values(array_unique(array_filter(array_map('intval', $value))));
            }],
            [['subject_ids'], 'each', 'rule' => ['integer']],
            [['subject_ids'], 'validateSubjectIds'],
        ];

        if (Employee::hasColumn('joining_date')) {
            $rules[] = [['joining_date'], 'date', 'format' => 'php:Y-m-d'];
            $rules[] = [
                ['joining_date'],
                'compare',
                'compareValue' => date('Y-m-d'),
                'operator' => '<=',
                'skipOnEmpty' => true,
                'message' => 'Joining date cannot be in the future.',
            ];
        }
        if (Employee::hasColumn('position_level')) {
            $rules[] = [['position_level'], 'in', 'range' => ['l1', 'l2', 'l3', 'l4', 'l5'], 'skipOnEmpty' => true];
        }
        if (Employee::hasColumn('marital_status')) {
            $rules[] = [['marital_status'], 'in', 'range' => ['single', 'married', 'divorced', 'widowed'], 'skipOnEmpty' => true];
        }
        if (Employee::hasColumn('employment_type')) {
            $rules[] = [['employment_type'], 'in', 'range' => ['full-time', 'part-time', 'contract', 'intern'], 'skipOnEmpty' => true];
        }
        if (Employee::hasColumn('teacher')) {
            $rules[] = [['teacher'], 'filter', 'filter' => static fn($value) => (int)$value === 1 ? 1 : 0];
            $rules[] = [['teacher'], 'integer'];
            $rules[] = [['teacher'], 'in', 'range' => [0, 1]];
            $rules[] = [['teacher'], 'default', 'value' => 0];
        }

        return $rules;
    }

    public function load($data, $formName = null): bool
    {
        $loaded = parent::load($data, $formName);
        $scope = $formName === null ? $this->formName() : $formName;
        if (
            (int)$this->teacher === 1
            && is_array($data[$scope] ?? null)
            && !array_key_exists('subject_ids', $data[$scope])
        ) {
            $this->subject_ids = [];
        }

        return $loaded;
    }

    public function attributeLabels()
    {
        return [
            'full_name' => 'Full Name',
            'portal_email' => 'Portal Email',
            'portal_password' => 'Portal Password',
            'personal_email' => 'Personal Email',
            'job_title' => 'Job Title',
            'joining_date' => 'Joining Date',
            'department_id' => 'Department',
            'manager_id' => 'Manager',
            'subject_ids' => 'Subjects',
            'phone' => 'Phone Number',
            'teacher' => 'Employee Type',
        ];
    }

    public function beforeValidate()
    {
        if ((int)($this->department_id ?? 0) <= 0) {
            $this->manager_id = null;
        }

        if ((int)$this->teacher !== 1) {
            $this->subject_ids = [];
        }

        return parent::beforeValidate();
    }

    public function validateManager($attribute)
    {
        if ((int)($this->department_id ?? 0) <= 0) {
            if ($this->$attribute) {
                $this->addError($attribute, 'Select a department before choosing a manager.');
            }

            return;
        }

        if ($this->$attribute && $this->id !== null && (int)$this->$attribute === (int)$this->id) {
            $this->addError($attribute, 'Employee cannot be their own manager.');
            return;
        }

        $deptId = (int)$this->department_id;
        $selectedManagerId = $this->$attribute ? (int)$this->$attribute : null;
        $allowedManagerIds = $this->allowedManagerIdsForDepartment($deptId);

        if ($selectedManagerId !== null && !in_array($selectedManagerId, $allowedManagerIds, true)) {
            $this->addError($attribute, 'Manager must be the head of the selected department.');
        }
    }

    /**
     * @return int[]
     */
    private function allowedManagerIdsForDepartment(int $departmentId): array
    {
        if ((int)$this->teacher === 1 && SchoolDepartment::isEnabled()) {
            $dept = SchoolDepartment::find()->where(['id' => $departmentId])->one();
            if ($dept === null || !$dept->head_id) {
                return [];
            }

            $headId = (int)$dept->head_id;
            if ($this->id !== null && $headId === (int)$this->id) {
                return [];
            }

            return [$headId];
        }

        if (!Department::hasColumn('manager_id')) {
            return [];
        }

        $dept = Department::find()->where(['id' => $departmentId])->one();
        if ($dept === null || !$dept->manager_id) {
            return [];
        }

        $managerId = (int)$dept->manager_id;
        if ($this->id !== null && $managerId === (int)$this->id) {
            return [];
        }

        return [$managerId];
    }

    public function validateDepartment($attribute)
    {
        if ($this->$attribute === null || $this->$attribute === '') {
            return;
        }

        $deptId = (int)$this->$attribute;
        if ($deptId <= 0) {
            $this->addError($attribute, 'Department is invalid.');
            return;
        }

        if ((int)$this->teacher === 1) {
            if (!SchoolDepartment::isEnabled()) {
                $this->addError($attribute, 'School departments are not available.');
                return;
            }

            $dept = SchoolDepartment::find()->where(['id' => $deptId])->one();
            if ($dept === null) {
                $this->addError($attribute, 'Department is invalid.');
                return;
            }

            $status = strtolower(trim((string)($dept->status ?? '')));
            if ($status !== 'active') {
                $this->addError($attribute, 'Department is inactive.');
            }

            return;
        }

        $dept = Department::find()->where(['id' => $deptId])->one();
        if ($dept === null) {
            $this->addError($attribute, 'Department is invalid.');
            return;
        }

        if (Department::hasColumn('status')) {
            $status = strtolower(trim((string)($dept->status ?? '')));
            if ($status !== 'active') {
                $this->addError($attribute, 'Department is inactive.');
            }
        }
    }

    public function validateSubjectIds(string $attribute): void
    {
        if ((int)$this->teacher !== 1 || !EmployeeSchoolSubject::isEnabled()) {
            return;
        }

        $departmentId = (int)($this->department_id ?? 0);
        if ($departmentId <= 0) {
            if ($this->$attribute !== []) {
                $this->addError($attribute, 'Select a department before choosing subjects.');
            }

            return;
        }

        if (!SchoolSubject::isEnabled()) {
            return;
        }

        $subjectIds = array_values(array_unique(array_filter(array_map('intval', $this->$attribute))));
        if ($subjectIds === []) {
            return;
        }

        $validIds = SchoolSubject::find()
            ->select('id')
            ->where([
                'school_department_id' => $departmentId,
                'id' => $subjectIds,
            ])
            ->column();

        $validIds = array_map('intval', $validIds);
        foreach ($subjectIds as $subjectId) {
            if (!in_array($subjectId, $validIds, true)) {
                $this->addError($attribute, 'One or more selected subjects are invalid for this department.');
                return;
            }
        }
    }

    public static function fromEmployee(Employee $employee): self
    {
        $m = new self();
        $m->id = (int)$employee->id;
        $m->full_name = (string)$employee->full_name;
        $m->portal_email = (string)$employee->email;
        if (Employee::hasColumn('personal_email')) {
            $m->personal_email = (string)($employee->personal_email ?? '');
        }
        if (Employee::hasColumn('login_password_plain')) {
            $m->portal_password = (string)($employee->login_password_plain ?? '');
        }
        $m->job_title = (string)$employee->job_title;
        $m->joining_date = $employee->hasAttribute('joining_date') ? (string)($employee->joining_date ?? '') : '';
        if ($employee->isTeacher()
            && $employee->hasAttribute('school_department_id')
            && (int)$employee->school_department_id > 0) {
            $m->department_id = (int)$employee->school_department_id;
        } elseif ($employee->hasAttribute('department_id')) {
            $m->department_id = $employee->department_id ? (int)$employee->department_id : null;
        }
        $m->manager_id = $employee->hasAttribute('manager_id') ? ($employee->manager_id ? (int)$employee->manager_id : null) : null;

        foreach (['phone', 'marital_status', 'position_level', 'employment_type'] as $attr) {
            if ($employee->hasAttribute($attr)) {
                $m->$attr = $employee->$attr;
            }
        }
        if ($employee->hasAttribute('teacher')) {
            $m->teacher = (int)$employee->teacher === 1 ? 1 : 0;
        }
        if ($employee->hasAttribute('location')) {
            $m->location = self::normalizeWorkLocation($employee->location !== null ? (string)$employee->location : null);
        }
        if ($employee->isTeacher() && EmployeeSchoolSubject::isEnabled()) {
            $m->subject_ids = array_map(
                'intval',
                EmployeeSchoolSubject::find()
                    ->select('school_subject_id')
                    ->where(['employee_id' => (int)$employee->id])
                    ->column()
            );
        }

        return $m;
    }

    public function workLocationValue(): string
    {
        return self::normalizeWorkLocation($this->location) ?? '';
    }

    public static function normalizeWorkLocation(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = strtolower(trim(preg_replace('/[\s\-_]+/', '', $value)));
        if ($normalized === 'remote') {
            return 'remote';
        }
        if ($normalized === 'hybrid') {
            return 'hybrid';
        }
        if ($normalized === 'onsite' || str_contains($normalized, 'onsite')) {
            return 'onsite';
        }

        return null;
    }
}

