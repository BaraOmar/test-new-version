<?php

namespace frontend\modules\hr\models;

use common\components\HrPermissionCatalog;
use common\models\User;
use common\modules\employee\models\Employee;
use Yii;
use yii\base\Model;

class GrantEmployeePermissionsForm extends Model
{
    public const ACCOUNT_EMPLOYEE = 'employee';
    public const ACCOUNT_EXTERNAL = 'external';

    public string $account_type = self::ACCOUNT_EMPLOYEE;
    public ?int $employee_id = null;
    public string $full_name = '';
    public ?string $phone = null;
    public string $dashboard_email = '';
    public string $password = '';
    public bool $has_dashboard_account = false;
    /** @var string[] */
    public array $permissions = [];

    /** Display-only fields loaded from the employee record (not saved from this form). */
    public string $display_full_name = '';
    public string $display_email = '';
    public ?string $display_phone = null;
    public string $display_job_title = '';
    public ?string $display_position = null;
    public string $display_joining_date = '';
    public ?string $display_employment_type = null;

    public function rules(): array
    {
        return [
            [['account_type'], 'in', 'range' => [self::ACCOUNT_EMPLOYEE, self::ACCOUNT_EXTERNAL]],
            [['employee_id'], 'integer'],
            [['employee_id'], 'validateEmployeeSelection'],
            [['full_name'], 'trim'],
            [['full_name'], 'match', 'pattern' => '/^[a-zA-Z\s]+$/', 'message' => '{attribute} must contain letters and spaces only.'],
            [['full_name'], 'string', 'max' => 255],
            [['full_name'], 'validateExternalFullName'],
            [['phone'], 'trim'],
            [['phone'], 'string', 'max' => 32],
            [['phone'], 'validateExternalPhone'],
            [['dashboard_email'], 'trim'],
            ['dashboard_email', 'required'],
            ['dashboard_email', 'email'],
            [
                'dashboard_email',
                'unique',
                'targetClass' => User::class,
                'targetAttribute' => 'email',
                'filter' => function ($query) {
                    $linkedUserId = $this->resolveLinkedUserIdForValidation();
                    if ($linkedUserId > 0) {
                        $query->andWhere(['<>', 'id', $linkedUserId]);
                    }
                },
                'message' => 'This dashboard email is already in use.',
            ],
            [['dashboard_email'], 'string', 'max' => 255],
            ['password', 'validatePassword'],
            ['permissions', 'validatePermissions'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'account_type' => 'Account Type',
            'employee_id' => 'Employee',
            'full_name' => 'Full Name',
            'phone' => 'Phone Number',
            'dashboard_email' => 'Dashboard Email',
            'password' => 'Dashboard Password',
            'permissions' => 'Permissions',
        ];
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (is_array($values)) {
            if (array_key_exists('employee_id', $values)) {
                $rawEmployeeId = $values['employee_id'];
                $values['employee_id'] = $rawEmployeeId === '' || $rawEmployeeId === null
                    ? null
                    : (int)$rawEmployeeId;
            }

            if (array_key_exists('has_dashboard_account', $values)) {
                $values['has_dashboard_account'] = filter_var(
                    $values['has_dashboard_account'],
                    FILTER_VALIDATE_BOOLEAN
                );
            }
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function isExternalAccount(): bool
    {
        return $this->account_type === self::ACCOUNT_EXTERNAL;
    }

    public function validateEmployeeSelection(string $attribute): void
    {
        if ($this->hasErrors() || $this->isExternalAccount()) {
            return;
        }

        $this->validateEmployee($attribute);
    }

    public function validateExternalFullName(string $attribute): void
    {
        if ($this->hasErrors() || !$this->isExternalAccount()) {
            return;
        }

        if (trim($this->full_name) === '') {
            $this->addError($attribute, 'Enter the external user\'s full name.');
        }
    }

    public function validateExternalPhone(string $attribute): void
    {
        if ($this->hasErrors() || !$this->isExternalAccount()) {
            return;
        }

        if (!User::hasColumn('phone')) {
            return;
        }

        $phone = trim((string)$this->phone);
        if ($phone === '') {
            $this->addError($attribute, 'Enter a phone number.');

            return;
        }

        $duplicate = User::find()->where(['phone' => $phone])->exists();
        if ($duplicate) {
            $this->addError($attribute, 'This phone number is already in use.');
        }
    }

    public function validateEmployee(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $employeeId = (int)$this->employee_id;
        if ($employeeId <= 0) {
            $this->addError($attribute, 'Select an employee.');

            return;
        }

        $employee = Employee::findOne(['id' => $employeeId]);
        if ($employee === null) {
            $this->addError($attribute, 'Employee not found.');

            return;
        }

        if (Employee::hasColumn('user_id') && (int)$employee->user_id > 0) {
            $linkedUser = User::findOne(['id' => (int)$employee->user_id]);
            if ($linkedUser !== null && $linkedUser->isSuperHrAdmin()) {
                $this->addError($attribute, 'Super admin accounts cannot be modified from this page.');
            }
        }
    }

    public function validatePassword(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $password = trim($this->password);
        if ($this->isExternalAccount() || !$this->hasExistingDashboardAccount()) {
            if ($password === '') {
                $this->addError($attribute, 'Set a dashboard password for new accounts.');

                return;
            }
        }

        if ($password !== '' && strlen($password) < 8) {
            $this->addError($attribute, 'Password must be at least 8 characters.');
        }
    }

    public function validatePermissions(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }

        if (HrPermissionCatalog::normalizeList($this->permissions) === []) {
            $this->addError($attribute, 'Select at least one module permission.');
        }
    }

    public function loadFromEmployee(Employee $employee): void
    {
        $this->employee_id = (int)$employee->id;
        $this->loadDisplayFieldsFromEmployee($employee);
        $this->dashboard_email = '';
        $this->password = '';
        $this->has_dashboard_account = false;
        $this->permissions = [];

        $linkedUser = $this->resolveLinkedUser($employee);
        if ($linkedUser !== null && !$linkedUser->isSuperHrAdmin()) {
            $storedPermissions = $linkedUser->getPermissionList();
            if ($storedPermissions !== []) {
                $this->permissions = HrPermissionCatalog::expandForDisplay($storedPermissions);
            }

            $this->has_dashboard_account = $linkedUser->isHr() || $storedPermissions !== [];
            if (trim((string)($linkedUser->email ?? '')) !== '') {
                $this->dashboard_email = trim((string)$linkedUser->email);
            }
        }
    }

    public function refreshDisplayFromEmployee(): void
    {
        $employeeId = (int)$this->employee_id;
        if ($employeeId <= 0) {
            return;
        }

        $employee = Employee::findOne(['id' => $employeeId]);
        if ($employee !== null) {
            $this->loadDisplayFieldsFromEmployee($employee);
        }
    }

    public function save(User $actor): bool
    {
        if (!$actor->hasPermission('manage_hr') || !$this->validate()) {
            return false;
        }

        if ($this->isExternalAccount()) {
            return $this->saveExternalUser($actor);
        }

        $employee = Employee::findOne(['id' => (int)$this->employee_id]);
        if ($employee === null) {
            $this->addError('employee_id', 'Employee not found.');

            return false;
        }

        $permissions = ManageHrUserForm::filterPermissionsForActorStatic(
            $actor,
            HrPermissionCatalog::normalizeList($this->permissions)
        );
        if ($permissions === []) {
            $this->addError('permissions', 'Select at least one valid permission.');

            return false;
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            $user = $this->resolveUserForEmployee($employee);
            if ($user === null) {
                $tx->rollBack();

                return false;
            }

            if ($user->isSuperHrAdmin()) {
                $this->addError('employee_id', 'Super admin accounts cannot be modified.');
                $tx->rollBack();

                return false;
            }

            $displayName = trim((string)$employee->full_name);
            if ($user->hasAttribute('username')) {
                $user->username = $displayName !== '' ? $displayName : $this->dashboard_email;
            }
            if ($user->hasAttribute('email')) {
                $user->email = $this->dashboard_email;
            }
            if ($user->hasAttribute('role')) {
                $user->role = 'hr';
            }
            if ($user->hasAttribute('status')) {
                $user->status = User::STATUS_ACTIVE;
            }
            if ($user->hasAttribute('permissions')) {
                $user->permissions = json_encode(array_values($permissions), JSON_UNESCAPED_SLASHES);
            }
            if ($user->hasAttribute('updated_at')) {
                $user->updated_at = time();
            }

            $password = trim($this->password);
            if ($password !== '' && method_exists($user, 'setPassword')) {
                $user->setPassword($password);
                if (method_exists($user, 'generateAuthKey')) {
                    $user->generateAuthKey();
                }
            }

            if (!$user->save()) {
                $this->addModelErrors($user, 'dashboard_email');
                $tx->rollBack();

                return false;
            }

            if (!$this->linkEmployeeToDashboardUser($employee, (int)$user->id)) {
                $tx->rollBack();

                return false;
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            $this->addError('employee_id', YII_DEBUG ? $e->getMessage() : 'Could not save permissions. Please try again.');

            return false;
        }

        return true;
    }

    private function saveExternalUser(User $actor): bool
    {
        $permissions = ManageHrUserForm::filterPermissionsForActorStatic(
            $actor,
            HrPermissionCatalog::normalizeList($this->permissions)
        );
        if ($permissions === []) {
            $this->addError('permissions', 'Select at least one valid permission.');

            return false;
        }

        $password = trim($this->password);
        if ($password === '') {
            $this->addError('password', 'Set a dashboard password for new accounts.');

            return false;
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            $user = new User();
            $displayName = trim($this->full_name);
            if ($user->hasAttribute('username')) {
                $user->username = $displayName !== '' ? $displayName : $this->dashboard_email;
            }
            if ($user->hasAttribute('email')) {
                $user->email = $this->dashboard_email;
            }
            if ($user->hasAttribute('phone')) {
                $user->phone = trim((string)$this->phone);
            }
            if ($user->hasAttribute('role')) {
                $user->role = 'hr';
            }
            if ($user->hasAttribute('status')) {
                $user->status = User::STATUS_ACTIVE;
            }
            if ($user->hasAttribute('permissions')) {
                $user->permissions = json_encode(array_values($permissions), JSON_UNESCAPED_SLASHES);
            }
            if ($user->hasAttribute('is_super_admin')) {
                $user->is_super_admin = 0;
            }

            $now = time();
            if ($user->hasAttribute('created_at')) {
                $user->created_at = $now;
            }
            if ($user->hasAttribute('updated_at')) {
                $user->updated_at = $now;
            }
            if (method_exists($user, 'setPassword')) {
                $user->setPassword($password);
            }
            if (method_exists($user, 'generateAuthKey')) {
                $user->generateAuthKey();
            }

            if (!$user->save()) {
                $this->addModelErrors($user, 'dashboard_email');
                $tx->rollBack();

                return false;
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            $this->addError('full_name', YII_DEBUG ? $e->getMessage() : 'Could not create external user. Please try again.');

            return false;
        }

        return true;
    }

    private function linkEmployeeToDashboardUser(Employee $employee, int $userId): bool
    {
        $attributes = [];

        if ($employee->hasAttribute('user_id') && (int)$employee->user_id !== $userId) {
            $employee->user_id = $userId;
            $attributes[] = 'user_id';
        }

        if ($employee->hasAttribute('is_hr_admin') && (int)$employee->is_hr_admin !== 1) {
            $employee->is_hr_admin = 1;
            $attributes[] = 'is_hr_admin';
        }

        if ($attributes === []) {
            return true;
        }

        if ($employee->save(false, $attributes)) {
            return true;
        }

        $this->addModelErrors($employee, 'employee_id');

        return false;
    }

    private function loadDisplayFieldsFromEmployee(Employee $employee): void
    {
        $this->display_full_name = (string)$employee->full_name;
        $this->display_email = trim((string)($employee->email ?? ''));
        $this->display_phone = $employee->hasAttribute('phone') && $employee->phone !== null
            ? (string)$employee->phone
            : null;
        $this->display_job_title = $employee->hasAttribute('job_title')
            ? (string)$employee->job_title
            : '';
        $this->display_position = $employee->hasAttribute('position_level') && $employee->position_level !== null
            ? strtoupper((string)$employee->position_level)
            : null;
        $this->display_joining_date = $employee->hasAttribute('joining_date')
            ? (string)($employee->joining_date ?? '')
            : '';
        $this->display_employment_type = $employee->hasAttribute('employment_type') && $employee->employment_type !== null
            ? (string)$employee->employment_type
            : null;
    }

    private function resolveUserForEmployee(Employee $employee): ?User
    {
        $existingUser = $this->resolveLinkedUser($employee);
        if ($existingUser !== null) {
            $existingUser = User::findOne(['id' => (int)$existingUser->id]);
            if ($existingUser === null) {
                $this->addError('employee_id', 'Linked user account not found.');

                return null;
            }

            $duplicate = User::find()
                ->where(['email' => $this->dashboard_email])
                ->andWhere(['<>', 'id', (int)$existingUser->id])
                ->exists();
            if ($duplicate) {
                $this->addError('dashboard_email', 'This dashboard email is already in use.');

                return null;
            }

            return $existingUser;
        }

        $duplicate = User::find()->where(['email' => $this->dashboard_email])->exists();
        if ($duplicate) {
            $this->addError('dashboard_email', 'This dashboard email is already in use by another account.');

            return null;
        }

        $user = new User();
        $displayName = trim((string)$employee->full_name);
        if ($user->hasAttribute('username')) {
            $user->username = $displayName !== '' ? $displayName : $this->dashboard_email;
        }
        if ($user->hasAttribute('email')) {
            $user->email = $this->dashboard_email;
        }
        if ($user->hasAttribute('role')) {
            $user->role = 'hr';
        }
        if ($user->hasAttribute('status')) {
            $user->status = User::STATUS_ACTIVE;
        }
        if ($user->hasAttribute('is_super_admin')) {
            $user->is_super_admin = 0;
        }

        $now = time();
        if ($user->hasAttribute('created_at')) {
            $user->created_at = $now;
        }
        if ($user->hasAttribute('updated_at')) {
            $user->updated_at = $now;
        }
        if (method_exists($user, 'generateAuthKey')) {
            $user->generateAuthKey();
        }

        $password = trim($this->password);
        if ($password === '' || !method_exists($user, 'setPassword')) {
            $this->addError('password', 'Set a dashboard password for new accounts.');

            return null;
        }

        $user->setPassword($password);

        if (!$user->save()) {
            $this->addModelErrors($user, 'dashboard_email');

            return null;
        }

        return $user;
    }

    private function addModelErrors(Model $model, string $fallbackAttribute): void
    {
        foreach ($model->getErrors() as $attribute => $messages) {
            $target = $this->hasProperty((string)$attribute) ? (string)$attribute : $fallbackAttribute;
            foreach ($messages as $message) {
                $this->addError($target, $message);
            }
        }
    }

    private function resolveLinkedUserIdForValidation(): int
    {
        $employeeId = (int)$this->employee_id;
        if ($employeeId <= 0) {
            return 0;
        }

        $employee = Employee::findOne(['id' => $employeeId]);
        if ($employee === null) {
            return 0;
        }

        $linkedUser = $this->resolveLinkedUser($employee);

        return $linkedUser instanceof User ? (int)$linkedUser->id : 0;
    }

    private function hasExistingDashboardAccount(): bool
    {
        $employeeId = (int)$this->employee_id;
        if ($employeeId <= 0) {
            return false;
        }

        $employee = Employee::findOne(['id' => $employeeId]);
        if ($employee === null) {
            return false;
        }

        return $this->resolveLinkedUser($employee) instanceof User;
    }

    private function resolveLinkedUser(Employee $employee): ?User
    {
        $user = $this->findLinkedUser($employee);
        if ($user instanceof User) {
            return $user;
        }

        $dashboardEmail = trim($this->dashboard_email);
        if ($dashboardEmail !== '') {
            $user = User::find()->where(['email' => $dashboardEmail])->one();
            if ($user instanceof User) {
                return $user;
            }
        }

        return null;
    }

    private function findLinkedUser(Employee $employee): ?User
    {
        if (!Employee::hasColumn('user_id') || (int)$employee->user_id <= 0) {
            return null;
        }

        $user = User::findOne(['id' => (int)$employee->user_id]);

        return $user instanceof User ? $user : null;
    }
}
