<?php

namespace common\modules\recruitment\services;

use common\models\User;
use common\modules\employee\models\Department;
use common\modules\employee\models\Employee;
use common\modules\recruitment\models\Applicant;
use common\modules\recruitment\models\Job;
use Yii;

class ApplicantConversionService
{
    /**
     * @return array{employee: Employee, tempPassword: string|null, userCreated: bool}
     * @throws \RuntimeException
     */
    public function convert(Applicant $applicant, Job $job, Department $department): array
    {
        if (Employee::findOne(['email' => $applicant->email])) {
            throw new \RuntimeException('An employee with this email already exists.');
        }

        $tempPassword = null;
        $userCreated = false;
        $user = User::findByEmail($applicant->email);

        if ($user === null) {
            $tempPassword = Yii::$app->security->generateRandomString(10);
            $user = $this->createEmployeeUser($applicant, $tempPassword);
            $userCreated = true;
        }

        $attributes = [
            'full_name' => $applicant->full_name,
            'email' => $applicant->email,
            'phone' => $applicant->phone,
            'job_title' => $job->job_title,
            'department_id' => $department->id,
            'joining_date' => date('Y-m-d'),
            'employment_type' => 'full-time',
            'location' => $job->location,
            'is_hr_admin' => 0,
            'status' => 'Active',
        ];

        if (Employee::hasColumn('user_id') && $user !== null) {
            $attributes['user_id'] = (int)$user->id;
        }

        if (Employee::hasColumn('login_password_plain') && $tempPassword !== null) {
            $attributes['login_password_plain'] = $tempPassword;
        }

        if (Employee::hasColumn('password') && $tempPassword !== null) {
            $attributes['plainPassword'] = $tempPassword;
        }

        $employee = new Employee(Employee::filterAssignableAttributes($attributes));

        if (!$employee->save()) {
            throw new \RuntimeException(implode(' ', $employee->getFirstErrors()));
        }

        return [
            'employee' => $employee,
            'tempPassword' => $tempPassword,
            'userCreated' => $userCreated,
        ];
    }

    private function createEmployeeUser(Applicant $applicant, string $password): User
    {
        $user = new User();
        $now = time();

        if ($user->hasAttribute('username')) {
            $user->username = trim((string)$applicant->full_name) !== '' ? $applicant->full_name : $applicant->email;
        }
        if ($user->hasAttribute('email')) {
            $user->email = $applicant->email;
        }
        if ($user->hasAttribute('role')) {
            $user->role = 'employee';
        }
        if ($user->hasAttribute('status')) {
            $user->status = User::STATUS_ACTIVE;
        }
        if ($user->hasAttribute('created_at')) {
            $user->created_at = $now;
        }
        if ($user->hasAttribute('updated_at')) {
            $user->updated_at = $now;
        }
        if (method_exists($user, 'generateAuthKey')) {
            $user->generateAuthKey();
        }
        if (method_exists($user, 'setPassword')) {
            $user->setPassword($password);
        }

        if (!$user->save()) {
            throw new \RuntimeException(implode(' ', $user->getFirstErrors()));
        }

        return $user;
    }
}
