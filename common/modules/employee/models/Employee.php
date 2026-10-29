<?php

namespace common\modules\employee\models;

use common\models\User;
use common\modules\employee\services\SchoolDepartmentLeadershipService;
use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $full_name
 * @property string $email
 * @property string|null $personal_email
 * @property string|null $login_password_plain
 * @property string|null $password
 * @property string|null $phone
 * @property string|null $marital_status
 * @property string $job_title
 * @property string|null $position_level
 * @property string $joining_date
 * @property string|null $employment_type
 * @property int $teacher
 * @property int|null $manager_id
 * @property int|null $department_id
 * @property int|null $school_department_id
 * @property string|null $location
 * @property string|null $status
 * @property int|null $is_hr_admin
 * @property int|null $is_manager
 * @property int|null $user_id
 */
class Employee extends ActiveRecord
{
    public $plainPassword;

    public static function tableName()
    {
        return '{{%employees}}';
    }

    public function rules()
    {
        $required = [];
        foreach (['full_name', 'email', 'job_title', 'joining_date'] as $attr) {
            if (self::hasColumn($attr)) {
                $required[] = $attr;
            }
        }

        $rules = [];
        if ($required) {
            $rules[] = [$required, 'required'];
        }

        foreach (['department_id', 'manager_id', 'school_department_id', 'is_hr_admin', 'is_manager', 'user_id'] as $attr) {
            if (self::hasColumn($attr)) {
                $rules[] = [[$attr], 'integer'];
            }
        }

        if (self::hasColumn('joining_date')) {
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

        if (self::hasColumn('email')) {
            $rules[] = [['email'], 'email'];
            $rules[] = [['email'], 'unique'];
        }

        if (self::hasColumn('personal_email')) {
            $rules[] = [['personal_email'], 'trim'];
            $rules[] = [['personal_email'], 'email', 'skipOnEmpty' => true];
        }

        if (self::hasColumn('login_password_plain')) {
            $rules[] = [['login_password_plain'], 'string', 'max' => 128];
        }

        if (self::hasColumn('phone')) {
            $rules[] = [['phone'], 'trim'];
            $rules[] = [
                ['phone'],
                'match',
                'pattern' => '/^\d+$/',
                'skipOnEmpty' => true,
                'message' => 'Phone Number must contain numbers only.',
            ];
            $rules[] = [
                ['phone'],
                'unique',
                'skipOnEmpty' => true,
                'message' => 'This phone number is already in use by another employee.',
            ];
        }

        if (self::hasColumn('full_name') || self::hasColumn('job_title')) {
            $rules[] = [['full_name', 'job_title'], 'match', 'pattern' => '/^[a-zA-Z\s]+$/', 'message' => '{attribute} must contain letters and spaces only.'];
        }

        $stringAttrs = array_values(array_filter(['full_name', 'email', 'personal_email', 'job_title', 'phone', 'marital_status', 'employment_type', 'location'], [self::class, 'hasColumn']));
        if ($stringAttrs) {
            $rules[] = [$stringAttrs, 'string', 'max' => 255];
        }

        if (self::hasColumn('position_level')) {
            $rules[] = [['position_level'], 'in', 'range' => ['l1', 'l2', 'l3', 'l4', 'l5'], 'skipOnEmpty' => true];
        }

        if (self::hasColumn('marital_status')) {
            $rules[] = [['marital_status'], 'in', 'range' => ['single', 'married', 'divorced', 'widowed'], 'skipOnEmpty' => true];
        }

        if (self::hasColumn('employment_type')) {
            $rules[] = [['employment_type'], 'in', 'range' => ['full-time', 'part-time', 'contract', 'intern'], 'skipOnEmpty' => true];
        }

        if (self::hasColumn('teacher')) {
            $rules[] = [['teacher'], 'integer'];
            $rules[] = [['teacher'], 'in', 'range' => [0, 1]];
            $rules[] = [['teacher'], 'default', 'value' => 0];
        }

        $rules[] = [['plainPassword'], 'string', 'min' => 8];
        if (self::hasColumn('manager_id')) {
            $rules[] = [['manager_id'], 'validateManager'];
            $rules[] = [['manager_id'], 'exist', 'targetClass' => self::class, 'targetAttribute' => ['manager_id' => 'id'], 'skipOnEmpty' => true];
        }

        if (self::hasColumn('department_id')) {
            $rules[] = [['department_id'], 'exist', 'targetClass' => Department::class, 'targetAttribute' => ['department_id' => 'id'], 'skipOnEmpty' => true];
        }

        if (self::hasColumn('school_department_id')) {
            $rules[] = [['school_department_id'], 'exist', 'targetClass' => SchoolDepartment::class, 'targetAttribute' => ['school_department_id' => 'id'], 'skipOnEmpty' => true];
        }

        $defaults = array_values(array_filter(['phone', 'marital_status', 'position_level', 'employment_type', 'manager_id', 'location', 'plainPassword'], [self::class, 'hasColumn']));
        if ($defaults) {
            $rules[] = [$defaults, 'default', 'value' => null];
        }

        if (self::hasColumn('is_hr_admin')) {
            $rules[] = [['is_hr_admin'], 'default', 'value' => 0];
        }

        if (self::hasColumn('status')) {
            $rules[] = [['status'], 'string', 'max' => 255];
            $rules[] = [['status'], 'default', 'value' => 'Active'];
        }

        if (self::hasColumn('is_manager')) {
            $rules[] = [['is_manager'], 'default', 'value' => 0];
        }

        return $rules;
    }

    public function attributeLabels()
    {
        return [
            'full_name' => 'Full Name',
            'job_title' => 'Job Title',
            'joining_date' => 'Joining Date',
            'department_id' => 'Department',
            'school_department_id' => 'School Department',
            'manager_id' => 'Manager',
            'position_level' => 'Position Level',
            'employment_type' => 'Employment Type',
            'teacher' => 'Teacher',
            'is_hr_admin' => 'HR Administrator',
            'is_manager' => 'Manager Flag',
            'plainPassword' => 'Password',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->plainPassword && self::hasColumn('password')) {
            $this->password = password_hash($this->plainPassword, PASSWORD_DEFAULT);
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (!SchoolDepartment::isEnabled()) {
            return;
        }

        $shouldSyncLeadership = $insert
            || array_key_exists('school_department_id', $changedAttributes)
            || array_key_exists('teacher', $changedAttributes)
            || array_key_exists('status', $changedAttributes);

        if ($shouldSyncLeadership) {
            SchoolDepartmentLeadershipService::syncLeadershipForEmployee($this);
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();
        SchoolDepartmentLeadershipService::clearLeadershipForEmployeeId((int)$this->id);
    }

    public function validateManager($attribute)
    {
        if ($this->$attribute && (int)$this->$attribute === (int)$this->id) {
            $this->addError($attribute, 'Employee cannot be their own manager.');
        }
    }

    /**
     * Name shown in survey/evaluation targeting and pending-respondent lists.
     */
    public function getDisplayName(): string
    {
        if (self::hasColumn('full_name')) {
            $name = trim((string)$this->getAttribute('full_name'));
            if ($name !== '') {
                return $name;
            }
        }

        if (self::hasColumn('email')) {
            $email = trim((string)$this->getAttribute('email'));
            if ($email !== '') {
                return $email;
            }
        }

        return 'Employee #' . (int)$this->id;
    }

    public function getContactEmail(): string
    {
        if (self::hasColumn('personal_email')) {
            $personalEmail = trim((string)($this->personal_email ?? ''));
            if ($personalEmail !== '') {
                return $personalEmail;
            }
        }

        return '';
    }

    public function getDepartment()
    {
        return $this->hasOne(Department::class, ['id' => 'department_id']);
    }

    public function getSchoolDepartment()
    {
        return $this->hasOne(SchoolDepartment::class, ['id' => 'school_department_id']);
    }

    public function getSchoolSubjects()
    {
        return $this->hasMany(SchoolSubject::class, ['id' => 'school_subject_id'])
            ->viaTable('{{%employee_school_subject}}', ['employee_id' => 'id']);
    }

    public function getEmployeeSchoolSubjects()
    {
        return $this->hasMany(EmployeeSchoolSubject::class, ['employee_id' => 'id']);
    }

    /**
     * Departments where this employee is assigned as head (departments.manager_id).
     */
    public function getManagedDepartments()
    {
        return $this->hasMany(Department::class, ['manager_id' => 'id']);
    }

    /**
     * @return string[]
     */
    public function getManagedDepartmentNames(): array
    {
        if (!Department::hasColumn('manager_id')) {
            return [];
        }

        $names = [];
        foreach ($this->managedDepartments as $department) {
            $name = trim((string)$department->name);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    public function getManagedSchoolDepartments()
    {
        return $this->hasMany(SchoolDepartment::class, ['head_id' => 'id'])
            ->orderBy(['name' => SORT_ASC]);
    }

    public function getDeputySchoolDepartmentRecord()
    {
        return $this->hasOne(SchoolDepartment::class, ['deputy_head_id' => 'id']);
    }

    /**
     * @return string[]
     */
    public function getManagedSchoolDepartmentNames(): array
    {
        if (!SchoolDepartment::isEnabled()) {
            return [];
        }

        $names = [];
        foreach ($this->managedSchoolDepartments as $department) {
            $name = trim((string)$department->name);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    public function isSchoolDepartmentHead(): bool
    {
        return $this->getManagedSchoolDepartmentNames() !== [];
    }

    public function isSchoolDepartmentDeputy(): bool
    {
        if (!SchoolDepartment::isEnabled()) {
            return false;
        }

        return $this->deputySchoolDepartmentRecord !== null;
    }

    public function getSchoolDepartmentForReporting(): ?SchoolDepartment
    {
        if (!SchoolDepartment::isEnabled()) {
            return null;
        }

        $managed = $this->managedSchoolDepartments;
        if ($managed !== []) {
            return $managed[0];
        }

        if ($this->deputySchoolDepartmentRecord !== null) {
            return $this->deputySchoolDepartmentRecord;
        }

        return $this->schoolDepartment;
    }

    public function getReportingDepartmentName(): string
    {
        if ($this->isTeacher() && SchoolDepartment::isEnabled()) {
            $department = $this->getSchoolDepartmentForReporting();
            if ($department !== null) {
                $name = trim((string)$department->name);
                if ($name !== '') {
                    return $name;
                }
            }

            return '—';
        }

        if ($this->department !== null) {
            $name = trim((string)$this->department->name);
            if ($name !== '') {
                return $name;
            }
        }

        return '—';
    }

    public function getSchoolDepartmentReportingManagerName(): string
    {
        if (!$this->isTeacher() || !SchoolDepartment::isEnabled()) {
            return $this->manager->full_name ?? 'No Manager Assigned';
        }

        if ($this->isSchoolDepartmentHead()) {
            return 'No Manager Assigned';
        }

        $department = $this->getSchoolDepartmentForReporting();
        if ($department === null || !$department->head_id || (int)$department->head_id === (int)$this->id) {
            return 'No Manager Assigned';
        }

        $head = $department->head;
        if ($head !== null) {
            $name = trim((string)$head->full_name);
            if ($name !== '') {
                return $name;
            }
        }

        return 'No Manager Assigned';
    }

    public function isDepartmentHead(): bool
    {
        return $this->getManagedDepartmentNames() !== [];
    }

    public function getManager()
    {
        return $this->hasOne(self::class, ['id' => 'manager_id']);
    }

    public function getLinkedUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Employee record tied to an HR portal account (created via Manage HR).
     */
    public function isManagedHrEmployee(): bool
    {
        if (self::hasColumn('is_hr_admin') && (int)$this->is_hr_admin === 1) {
            return true;
        }

        if (!self::hasColumn('user_id') || (int)$this->user_id <= 0) {
            return false;
        }

        $user = $this->linkedUser;
        return $user !== null && $user->isHr();
    }

    public function getDirectReports()
    {
        return $this->hasMany(self::class, ['manager_id' => 'id']);
    }

    public static function hasColumn($column)
    {
        return Yii::$app->db->schema->getTableSchema(static::tableName()) && Yii::$app->db->schema->getTableSchema(static::tableName())->getColumn($column) !== null;
    }

    /**
     * Keeps only attributes that exist on the employees table (plus plainPassword).
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public static function filterAssignableAttributes(array $attributes): array
    {
        $filtered = [];
        foreach ($attributes as $key => $value) {
            if ($key === 'plainPassword' || self::hasColumn($key)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    public function getDisplayStatus()
    {
        return $this->hasAttribute('status') && $this->status ? $this->status : 'Active';
    }

    public function isTerminated(): bool
    {
        if (!$this->hasAttribute('status')) {
            return false;
        }

        return strtolower(trim((string)$this->status)) === 'terminated';
    }

    /**
     * @return string[]
     */
    public static function lmsBlockedStatuses(): array
    {
        return ['onboarding', 'leave', 'terminated'];
    }

    public function isLmsBlockedByEmployeeStatus(): bool
    {
        if (!$this->hasAttribute('status')) {
            return false;
        }

        return in_array(
            strtolower(trim((string)$this->status)),
            self::lmsBlockedStatuses(),
            true
        );
    }

    public function canAccessTeacherLms(): bool
    {
        return $this->isTeacher() && !$this->isLmsBlockedByEmployeeStatus();
    }

    /**
     * Terminated employees may view their profile only; no surveys, LMS, or other portal data.
     */
    public function canReceiveEmployeePortalData(): bool
    {
        return !$this->isTerminated();
    }

    public function getDisplayMaritalStatus(): string
    {
        return self::formatProfileOption($this->hasAttribute('marital_status') ? $this->marital_status : null);
    }

    public function getDisplayPositionLevel(): string
    {
        if (!$this->hasAttribute('position_level') || !$this->position_level) {
            return '—';
        }

        $level = strtolower(trim((string)$this->position_level));
        $labels = [
            'l1' => 'Entry (L1)',
            'l2' => 'Intermediate (L2)',
            'l3' => 'Senior (L3)',
            'l4' => 'Staff (L4)',
            'l5' => 'Director (L5)',
        ];

        return $labels[$level] ?? strtoupper($level);
    }

    public function getDisplayEmploymentType(): string
    {
        return self::formatProfileOption($this->hasAttribute('employment_type') ? $this->employment_type : null);
    }

    public function getDisplayEmployeeType(): string
    {
        if (!$this->hasAttribute('teacher')) {
            return '—';
        }

        return (int)$this->teacher === 1 ? 'Teacher' : 'Employee';
    }

    public function isTeacher(): bool
    {
        return $this->hasAttribute('teacher') && (int)$this->teacher === 1;
    }

    public function getDisplayDepartmentName(): string
    {
        if ($this->isTeacher() && self::hasColumn('school_department_id') && $this->schoolDepartment !== null) {
            $name = trim((string)$this->schoolDepartment->name);
            if ($name !== '') {
                return $name;
            }
        }

        if ($this->department !== null) {
            $name = trim((string)$this->department->name);
            if ($name !== '') {
                return $name;
            }
        }

        return '—';
    }

    private static function formatProfileOption(?string $value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '—';
        }

        return ucwords(str_replace('-', ' ', $value));
    }
}
