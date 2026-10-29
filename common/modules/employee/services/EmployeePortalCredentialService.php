<?php

namespace common\modules\employee\services;

use common\models\User;
use common\modules\employee\models\Employee;
use common\modules\finance\services\FinanceParentProfileService;
use Yii;

class EmployeePortalCredentialService
{
    public function generatePreviewPassword(): string
    {
        return (new FinanceParentProfileService())->generatePreviewPassword();
    }

    public function getDomain(): string
    {
        $domain = trim((string)(Yii::$app->params['employeePortalEmailDomain'] ?? 'employees.culturehr.local'));

        return $domain !== '' ? $domain : 'employees.culturehr.local';
    }

    public function generateUniquePortalEmail(Employee $employee, ?int $excludeUserId = null): string
    {
        $segments = [];
        $name = trim((string)$employee->full_name);
        foreach (preg_split('/\s+/', $name) ?: [] as $part) {
            $segment = $this->slugEmailSegment((string)$part);
            if ($segment !== '') {
                $segments[] = $segment;
            }
        }
        if ($segments === []) {
            $segments[] = 'employee';
        }

        $employeeId = (int)($employee->id ?? 0);
        if ($employeeId > 0) {
            $segments[] = (string)$employeeId;
        }

        $baseLocal = implode('.', $segments);
        if (strlen($baseLocal) > 180) {
            $baseLocal = substr($baseLocal, 0, 180);
            $baseLocal = rtrim($baseLocal, '.');
        }

        $domain = $this->getDomain();
        $candidate = strtolower($baseLocal . '@' . $domain);
        if ($this->loginEmailConflictMessage($candidate, $excludeUserId) === null) {
            return $candidate;
        }

        for ($attempt = 0; $attempt < 40; $attempt++) {
            $number = (string)random_int(1000, 999999);
            $candidate = strtolower($baseLocal . '.' . $number . '@' . $domain);
            if ($this->loginEmailConflictMessage($candidate, $excludeUserId) === null) {
                return $candidate;
            }
        }

        return strtolower($baseLocal . '.' . time() . random_int(10, 99) . '@' . $domain);
    }

    /**
     * @return array{success:bool,message:string,plain_password?:string,login_email?:string}
     */
    public function syncPortalAccount(Employee $employee, string $requestedPassword = ''): array
    {
        if (!Employee::hasColumn('user_id')) {
            return ['success' => true, 'message' => ''];
        }

        $user = null;
        $linkedUserId = (int)($employee->user_id ?? 0);
        if ($linkedUserId > 0) {
            $user = User::findOne($linkedUserId);
        }
        if ($user === null) {
            $existingEmail = strtolower(trim((string)($employee->email ?? '')));
            if ($existingEmail !== '') {
                $user = User::find()->where(['email' => $existingEmail])->one();
            }
        }

        $createdUser = false;
        if ($user === null) {
            $user = new User();
            $createdUser = true;
        }

        $excludeUserId = $user->isNewRecord ? null : (int)$user->id;
        $loginEmail = $createdUser
            ? $this->generateUniquePortalEmail($employee, $excludeUserId)
            : strtolower(trim((string)$user->email));

        if ($loginEmail === '' || !filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Unable to generate a valid employee login email.'];
        }

        if (Employee::hasColumn('email')) {
            $employee->email = $loginEmail;
        }

        $now = time();
        if ($user->isNewRecord && $user->hasAttribute('created_at')) {
            $user->created_at = $now;
        }
        if ($user->hasAttribute('updated_at')) {
            $user->updated_at = $now;
        }

        $user->username = $loginEmail;
        $user->email = $loginEmail;
        if ($user->hasAttribute('role')) {
            $user->role = 'employee';
        }
        if ($user->hasAttribute('status')) {
            $user->status = defined(User::class . '::STATUS_ACTIVE') ? User::STATUS_ACTIVE : 10;
        }
        if ($user->hasAttribute('is_super_admin')) {
            $user->is_super_admin = 0;
        }
        if (method_exists($user, 'generateAuthKey') && ($user->isNewRecord || !$user->auth_key)) {
            $user->generateAuthKey();
        }

        $requestedPassword = trim($requestedPassword);
        $currentPlain = Employee::hasColumn('login_password_plain')
            ? trim((string)($employee->login_password_plain ?? ''))
            : '';

        $plainPassword = null;
        $passwordChanged = false;

        if ($createdUser) {
            if ($requestedPassword !== '') {
                if (strlen($requestedPassword) < 8) {
                    return ['success' => false, 'message' => 'Portal password must be at least 8 characters.'];
                }
                $plainPassword = $requestedPassword;
            } else {
                $plainPassword = $this->generatePreviewPassword();
            }
            $user->assignSystemPassword($plainPassword);
            $passwordChanged = true;
        } elseif ($requestedPassword !== '' && $requestedPassword !== $currentPlain) {
            if (strlen($requestedPassword) < 8) {
                return ['success' => false, 'message' => 'Portal password must be at least 8 characters.'];
            }
            $plainPassword = $requestedPassword;
            $user->assignSystemPassword($plainPassword);
            $passwordChanged = true;
        }

        if (!$user->save(false)) {
            $errors = $user->getFirstErrors();

            return [
                'success' => false,
                'message' => $errors !== [] ? (string)reset($errors) : 'Unable to save employee login account.',
            ];
        }

        $employee->user_id = (int)$user->id;
        if ($passwordChanged && $plainPassword !== null && Employee::hasColumn('login_password_plain')) {
            $employee->login_password_plain = $plainPassword;
        }

        $result = [
            'success' => true,
            'message' => '',
            'login_email' => $loginEmail,
        ];
        if ($passwordChanged && $plainPassword !== null) {
            $result['plain_password'] = $plainPassword;
        }

        return $result;
    }

    /**
     * Ensure HR can view a stored portal password for employees with login accounts.
     * If none is stored yet, generates a new password and saves it.
     */
    public function ensureVisiblePassword(Employee $employee): ?string
    {
        if (!Employee::hasColumn('login_password_plain')) {
            return null;
        }

        $stored = trim((string)($employee->login_password_plain ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $userId = (int)($employee->user_id ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $user = User::findOne($userId);
        if (!$user instanceof User) {
            return null;
        }

        $password = $this->generatePreviewPassword();
        $user->assignSystemPassword($password);
        if ($user->hasAttribute('updated_at')) {
            $user->updated_at = time();
        }
        if (!$user->save(false)) {
            return null;
        }

        $employee->login_password_plain = $password;
        $employee->save(false, ['login_password_plain', 'updated_at']);

        return $password;
    }

    private function slugEmailSegment(string $value): string
    {
        $value = mb_strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';

        return $value;
    }

    private function loginEmailConflictMessage(string $loginEmail, ?int $excludeUserId): ?string
    {
        $normalized = strtolower(trim($loginEmail));
        $signature = $this->emailSignature($normalized);

        $users = User::find()
            ->select(['id', 'email'])
            ->andFilterWhere(['<>', 'id', $excludeUserId])
            ->all();

        foreach ($users as $user) {
            $existing = strtolower(trim((string)$user->email));
            if ($existing === $normalized) {
                return 'This login email is already used by another account.';
            }
            if ($this->emailSignature($existing) === $signature) {
                return 'This login email is too similar to an existing account email.';
            }
        }

        if (Employee::hasColumn('email')) {
            $employeeQuery = Employee::find()
                ->select(['id', 'email'])
                ->where(['email' => $normalized]);
            if ($excludeUserId !== null && $excludeUserId > 0 && Employee::hasColumn('user_id')) {
                $employeeQuery->andWhere(['<>', 'user_id', $excludeUserId]);
            }
            if ($employeeQuery->exists()) {
                return 'This login email is already used by another employee.';
            }
        }

        return null;
    }

    private function emailSignature(string $email): string
    {
        $email = strtolower(trim($email));
        $parts = explode('@', $email, 2);
        $local = $parts[0] ?? '';
        $domain = $parts[1] ?? '';
        $plusPosition = strpos($local, '+');
        if ($plusPosition !== false) {
            $local = substr($local, 0, $plusPosition);
        }
        $local = str_replace('.', '', $local);

        return $local . '@' . $domain;
    }
}
