<?php

namespace common\modules\employee\services;

use common\modules\employee\models\Employee;
use common\modules\employee\models\SchoolDepartment;

class SchoolDepartmentLeadershipService
{
    public static function employeeBelongsToSchoolDepartment(?Employee $employee, int $departmentId): bool
    {
        if ($employee === null || $departmentId <= 0 || !SchoolDepartment::isEnabled()) {
            return false;
        }

        if (!Employee::hasColumn('school_department_id') || !Employee::hasColumn('teacher')) {
            return false;
        }

        if (!(int)$employee->teacher === 1) {
            return false;
        }

        if (Employee::hasColumn('status')) {
            $status = strtolower(trim((string)$employee->status));
            if ($status !== 'active') {
                return false;
            }
        }

        return (int)($employee->school_department_id ?? 0) === $departmentId;
    }

    public static function syncLeadershipForEmployee(Employee $employee): void
    {
        if (!SchoolDepartment::isEnabled()) {
            return;
        }

        $employeeId = (int)$employee->id;
        if ($employeeId <= 0) {
            return;
        }

        $departments = SchoolDepartment::find()
            ->where([
                'or',
                ['head_id' => $employeeId],
                ['deputy_head_id' => $employeeId],
            ])
            ->all();

        foreach ($departments as $department) {
            if (self::employeeBelongsToSchoolDepartment($employee, (int)$department->id)) {
                continue;
            }

            $changed = false;
            if ((int)($department->head_id ?? 0) === $employeeId) {
                $department->head_id = null;
                $changed = true;
            }
            if ((int)($department->deputy_head_id ?? 0) === $employeeId) {
                $department->deputy_head_id = null;
                $changed = true;
            }

            if ($changed) {
                $department->save(false);
            }
        }
    }

    public static function clearLeadershipForEmployeeId(int $employeeId): void
    {
        if (!SchoolDepartment::isEnabled() || $employeeId <= 0) {
            return;
        }

        foreach (SchoolDepartment::find()->all() as $department) {
            $changed = false;
            if ((int)($department->head_id ?? 0) === $employeeId) {
                $department->head_id = null;
                $changed = true;
            }
            if ((int)($department->deputy_head_id ?? 0) === $employeeId) {
                $department->deputy_head_id = null;
                $changed = true;
            }

            if ($changed) {
                $department->save(false);
            }
        }
    }

    /**
     * Clears head/deputy assignments where the employee is no longer an active teacher in that department.
     */
    public static function reconcileStaleLeadership(): void
    {
        if (!SchoolDepartment::isEnabled()) {
            return;
        }

        foreach (SchoolDepartment::find()->all() as $department) {
            $departmentId = (int)$department->id;
            $changed = false;

            $headId = (int)($department->head_id ?? 0);
            if ($headId > 0) {
                $head = Employee::findOne($headId);
                if (!self::employeeBelongsToSchoolDepartment($head, $departmentId)) {
                    $department->head_id = null;
                    $changed = true;
                }
            }

            $deputyId = (int)($department->deputy_head_id ?? 0);
            if ($deputyId > 0) {
                $deputy = Employee::findOne($deputyId);
                if (!self::employeeBelongsToSchoolDepartment($deputy, $departmentId)) {
                    $department->deputy_head_id = null;
                    $changed = true;
                }
            }

            if ($changed) {
                $department->save(false);
            }
        }
    }
}
