<?php

namespace frontend\modules\hr\models;

use common\modules\employee\models\Employee;
use yii\base\Model;
use yii\db\ActiveRecord;

/**
 * Shared employee profile fields and validation for HR user create/update forms.
 */
trait HrEmployeeProfileTrait
{
    public string $full_name = '';
    public ?string $phone = null;
    public string $job_title = '';
    public ?string $position = null;
    public string $joining_date = '';
    public ?string $employment_type = null;

    protected function applyDefaultJoiningDateForNewRecord(): void
    {
        if (!Employee::hasColumn('joining_date') || trim($this->joining_date) !== '') {
            return;
        }

        $this->joining_date = date('Y-m-d');
    }

    /** @return array<int, mixed> */
    protected function employeeProfileRules(?int $excludeEmployeeId = null): array
    {
        $rules = [
            [['full_name'], 'trim'],
            [['full_name'], 'match', 'pattern' => '/^[a-zA-Z\s]+$/', 'message' => '{attribute} must contain letters and spaces only.'],
            [['full_name'], 'string', 'max' => 255],
        ];

        if (Employee::hasColumn('job_title')) {
            $rules[] = [['job_title'], 'match', 'pattern' => '/^[a-zA-Z\s]+$/', 'message' => '{attribute} must contain letters and spaces only.'];
            $rules[] = [['job_title'], 'string', 'max' => 255];
        }

        if (Employee::hasColumn('phone')) {
            $rules[] = [['phone'], 'trim'];
            $rules[] = [
                ['phone'],
                'unique',
                'targetClass' => Employee::class,
                'targetAttribute' => 'phone',
                'filter' => static function ($query) use ($excludeEmployeeId) {
                    if ($excludeEmployeeId) {
                        $query->andWhere(['<>', 'id', $excludeEmployeeId]);
                    }
                },
                'skipOnEmpty' => true,
                'message' => 'This phone number is already in use by another employee.',
            ];
            $rules[] = [['phone'], 'string', 'max' => 255];
        }

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

        if (Employee::hasColumn('employment_type')) {
            $rules[] = [['employment_type'], 'string', 'max' => 255];
            $rules[] = [
                ['employment_type'],
                'in',
                'range' => ['full-time', 'part-time', 'contract', 'intern'],
                'skipOnEmpty' => true,
            ];
        }

        if (Employee::hasColumn('position_level')) {
            $rules[] = [['position'], 'in', 'range' => ['l1', 'l2', 'l3', 'l4', 'l5'], 'skipOnEmpty' => true];
        }

        return $rules;
    }

    /** @return string[] */
    protected function employeeProfileRequiredAttributes(): array
    {
        $required = ['full_name'];
        if (Employee::hasColumn('job_title')) {
            $required[] = 'job_title';
        }
        if (Employee::hasColumn('joining_date')) {
            $required[] = 'joining_date';
        }

        return $required;
    }

    /** @return array<string, string> */
    protected function employeeProfileAttributeLabels(): array
    {
        return [
            'full_name' => 'Full Name',
            'phone' => 'Phone Number',
            'job_title' => 'Job Title',
            'position' => 'Position',
            'joining_date' => 'Joining Date',
            'employment_type' => 'Employment Type',
        ];
    }

    protected function loadEmployeeProfileFromEmployee(Employee $employee): void
    {
        $this->full_name = (string)$employee->full_name;
        if ($employee->hasAttribute('phone')) {
            $this->phone = $employee->phone !== null ? (string)$employee->phone : null;
        }
        if ($employee->hasAttribute('job_title')) {
            $this->job_title = (string)$employee->job_title;
        }
        if ($employee->hasAttribute('position_level')) {
            $this->position = $employee->position_level !== null ? (string)$employee->position_level : null;
        }
        if ($employee->hasAttribute('joining_date')) {
            $this->joining_date = (string)($employee->joining_date ?? '');
        }
        if ($employee->hasAttribute('employment_type')) {
            $this->employment_type = $employee->employment_type !== null ? (string)$employee->employment_type : null;
        }
    }

    protected function applyEmployeeProfile(Employee $employee, int $userId): void
    {
        if ($employee->hasAttribute('full_name')) {
            $employee->full_name = $this->full_name;
        }
        if ($employee->hasAttribute('email')) {
            $employee->email = $this->email;
        }
        if ($employee->hasAttribute('job_title')) {
            $employee->job_title = $this->job_title;
        }
        if ($employee->hasAttribute('position_level')) {
            $employee->position_level = $this->position ?: null;
        }
        if ($employee->hasAttribute('joining_date')) {
            $employee->joining_date = $this->joining_date;
        }
        if ($employee->hasAttribute('phone')) {
            $employee->phone = $this->phone !== null && trim($this->phone) !== '' ? trim($this->phone) : null;
        }
        if ($employee->hasAttribute('employment_type')) {
            $employee->employment_type = $this->employment_type ?: null;
        }
        if ($employee->hasAttribute('user_id')) {
            $employee->user_id = $userId;
        }
        if ($employee->isNewRecord && $employee->hasAttribute('status')) {
            $employee->status = 'Active';
        }
        if ($employee->isNewRecord && $employee->hasAttribute('is_hr_admin')) {
            $employee->is_hr_admin = 1;
        }
    }

    protected function addEmployeeProfileErrors(ActiveRecord $model, ?string $fallbackAttribute = null): void
    {
        /** @var Model $this */
        foreach ($model->getErrors() as $attribute => $messages) {
            $target = $this->hasProperty((string)$attribute)
                || in_array($attribute, ['full_name', 'email', 'phone', 'job_title', 'position', 'joining_date', 'employment_type', 'dashboard_email', 'password', 'permissions'], true)
                ? (string)$attribute
                : ($fallbackAttribute ?? 'full_name');
            foreach ($messages as $message) {
                $this->addError($target, $message);
            }
        }
    }

    protected function findEmployeeForUser(int $userId): ?Employee
    {
        if (!Employee::hasColumn('user_id')) {
            return null;
        }

        return Employee::find()->where(['user_id' => $userId])->one();
    }
}
