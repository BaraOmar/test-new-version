<?php

namespace common\models;

use common\components\HrPermissionCatalog;
use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User identity backed by the default Yii2 user table.
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property int $status
 * @property string $role
 * @property string|null $permissions
 * @property int $is_super_admin
 * @property string|null $phone
 * @property string|null $system_password_fingerprint
 * @property int|bool $password_changed_by_user
 * @property string $password Virtual attribute for HR user creation forms
 */
class User extends ActiveRecord implements IdentityInterface
{
    /** @var string|null Used when creating users from manage-HR flows */
    public $password;

    public const STATUS_DELETED = 0;
    public const STATUS_INACTIVE = 9;
    public const STATUS_ACTIVE = 10;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            [['email'], 'email'],
            [['username', 'email', 'password_hash', 'password_reset_token'], 'string', 'max' => 255],
            [['role'], 'string', 'max' => 50],
            [['role'], 'default', 'value' => 'employee'],
            [['status', 'created_at', 'updated_at'], 'integer'],
            [['auth_key'], 'string', 'max' => 32],
        ];

        if ($this->hasAttribute('phone')) {
            $rules[] = [['phone'], 'trim'];
            $rules[] = [['phone'], 'string', 'max' => 32];
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
                'targetAttribute' => 'phone',
                'skipOnEmpty' => true,
                'message' => 'This phone number is already in use.',
            ];
        }

        if ($this->hasAttribute('status')) {
            $rules[] = ['status', 'default', 'value' => self::STATUS_ACTIVE];
            $rules[] = ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED, 'Active', 'Inactive']];
        }

        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findByEmail($username);
    }

    public static function findByEmail($email)
    {
        $email = strtolower(trim((string)$email));
        if ($email === '') {
            return null;
        }

        return static::find()
            ->where(['status' => self::STATUS_ACTIVE])
            ->andWhere('LOWER([[email]]) = :email', [':email' => $email])
            ->one();
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::hasColumn('password_reset_token')) {
            return null;
        }

        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds user by verification email token
     *
     * @param string $token verify email token
     * @return static|null
     */
    public static function findByVerificationToken($token)
    {
        if (!static::hasColumn('verification_token')) {
            return null;
        }

        $query = static::find()->where(['verification_token' => $token]);
        if (static::hasColumn('status')) {
            $query->andWhere(['status' => self::STATUS_INACTIVE]);
        }

        return $query->one();
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        if ($this->hasAttribute('auth_key') && $this->auth_key) {
            return $this->auth_key;
        }

        return hash('sha256', (string)$this->id . '|' . (string)$this->getPasswordHash());
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return hash_equals((string)$this->getAuthKey(), (string)$authKey);
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        if ($this->hasAttribute('auth_key')) {
            $this->auth_key = Yii::$app->security->generateRandomString();
        }
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        if ($this->hasAttribute('password_reset_token')) {
            $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
        }
    }

    /**
     * Generates new token for email verification
     */
    public function generateEmailVerificationToken()
    {
        if ($this->hasAttribute('verification_token')) {
            $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
        }
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        if ($this->hasAttribute('password_reset_token')) {
            $this->password_reset_token = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Unique index ux_user_phone treats '' as a value; multiple portal users
        // without a phone must store NULL instead of an empty string.
        if ($this->hasAttribute('phone') && trim((string)$this->phone) === '') {
            $this->phone = null;
        }

        return true;
    }

    public function getDisplayName()
    {
        return $this->username ?: $this->email;
    }

    public function getIsHrAdmin()
    {
        return $this->isHr();
    }

    public function getRole()
    {
        return $this->hasAttribute('role') && $this->getAttribute('role') ? $this->getAttribute('role') : 'employee';
    }

    public function isHr(): bool
    {
        $role = $this->getRole();

        return $role === 'hr' || $role === 'admin';
    }

    public function isEmployee(): bool
    {
        return $this->getRole() === 'employee';
    }

    public function isStudent(): bool
    {
        return $this->getRole() === 'student';
    }

    public function isParent(): bool
    {
        return $this->getRole() === 'parent';
    }

    public static function passwordFingerprint(string $password): string
    {
        return hash('sha256', strtolower(trim($password)));
    }

    public function markPasswordChangedByUser(): void
    {
        if ($this->hasAttribute('password_changed_by_user')) {
            $this->password_changed_by_user = 1;
        }
        if ($this->hasAttribute('system_password_fingerprint')) {
            $this->system_password_fingerprint = null;
        }
    }

    public function assignSystemPassword(string $password): void
    {
        $this->setPassword($password);
        if ($this->hasAttribute('password_changed_by_user')) {
            $this->password_changed_by_user = 0;
        }
        if ($this->hasAttribute('system_password_fingerprint')) {
            $this->system_password_fingerprint = self::passwordFingerprint($password);
        }
    }

    /**
     * @return string[]
     */
    public static function assignableHrModuleKeys(): array
    {
        return HrPermissionCatalog::allKeys();
    }

    public function hasPermission($permission): bool
    {
        if (!$this->isHr()) {
            return false;
        }

        if ($this->isSuperHrAdmin()) {
            return true;
        }

        return HrPermissionCatalog::isGranted((string)$permission, $this->getPermissionList());
    }

    /**
     * Adds newly introduced HR modules (e.g. recruitment) for accounts that already have broad HR access.
     */
    public function syncMissingHrModules(): bool
    {
        if (!$this->isHr() || !$this->hasAttribute('permissions') || $this->isSuperHrAdmin()) {
            return false;
        }

        $current = $this->getPermissionList();
        if ($current === []) {
            return false;
        }

        $full = HrPermissionCatalog::allKeys();
        $legacyCore = array_values(array_diff($full, [
            'recruitment',
            'recruitment_jobs',
            'recruitment_reports',
            'recruitment_applications',
            'recruitment_application_status',
        ]));
        $hasBroadAccess = in_array('manage_hr', $current, true)
            || count(array_intersect($current, $legacyCore)) >= count($legacyCore) - 1;

        if (!$hasBroadAccess) {
            return false;
        }

        $merged = array_values(array_unique(array_merge($current, $full)));
        sort($merged);
        $sortedCurrent = $current;
        sort($sortedCurrent);

        if ($merged === $sortedCurrent) {
            return false;
        }

        $this->permissions = json_encode($merged, JSON_UNESCAPED_SLASHES);

        return $this->save(false, ['permissions']);
    }

    /**
     * @return string[]
     */
    public function getPermissionList(): array
    {
        if (!$this->hasAttribute('permissions') || !$this->permissions) {
            return [];
        }

        $decoded = json_decode((string)$this->permissions, true);

        return is_array($decoded)
            ? HrPermissionCatalog::normalizeList(array_values(array_map('strval', $decoded)))
            : [];
    }

    /**
     * @return array<int, string>
     */
    public function getHrHomeRoute(): array
    {
        if ($this->isSuperHrAdmin()) {
            return ['/hr/hr/dashboard'];
        }

        foreach (HrPermissionCatalog::portalHomeRoutes() as $permission => $route) {
            if ($this->hasPermission($permission)) {
                return $route;
            }
        }

        throw new \yii\web\ForbiddenHttpException('No HR permissions assigned.');
    }

    public function getIsAdmin()
    {
        return $this->isSuperHrAdmin();
    }

    /**
     * The one designated superior HR account. Not granted by permissions or "Select All".
     */
    public function isSuperHrAdmin(): bool
    {
        if (!$this->isHr()) {
            return false;
        }

        if ($this->hasAttribute('is_super_admin')) {
            return (int)$this->is_super_admin === 1;
        }

        return false;
    }

    /**
     * Email safe to show in HR admin UI (never exposed for super HR).
     */
    public function getAccountEmailForDisplay(): string
    {
        if ($this->isSuperHrAdmin()) {
            return '';
        }

        return trim((string)($this->email ?? ''));
    }

    /**
     * @return static|null
     */
    public static function findSuperHrAdmin(): ?self
    {
        if (!static::hasColumn('is_super_admin')) {
            return null;
        }

        return static::find()
            ->where(['is_super_admin' => 1, 'role' => ['hr', 'admin']])
            ->one();
    }

    /**
     * @return self[]
     */
    public static function findHrAccounts(): array
    {
        return static::find()
            ->where(['role' => ['hr', 'admin']])
            ->orderBy(['username' => SORT_ASC, 'email' => SORT_ASC])
            ->all();
    }

    public function getPermissionSummary(): string
    {
        $list = $this->getPermissionList();
        if ($list === []) {
            return '—';
        }

        return implode(', ', $list);
    }

    public function getInitials(): string
    {
        $source = trim((string)($this->username ?: $this->email ?: 'U'));
        $parts = preg_split('/[\s._-]+/', $source) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            if ($part !== '') {
                $initials .= strtoupper($part[0]);
            }
            if (strlen($initials) >= 2) {
                break;
            }
        }

        return $initials !== '' ? $initials : 'HR';
    }

    /**
     * @return array{label:string,class:string}
     */
    public function getHrRoleBadge(): array
    {
        if ($this->isSuperHrAdmin()) {
            return ['label' => 'SUPER ADMIN', 'class' => 'bg-rose-100 text-rose-700'];
        }

        $limited = ['dashboard', 'employees'];
        $perms = $this->getPermissionList();
        sort($perms);
        $limitedSorted = $limited;
        sort($limitedSorted);
        if ($perms === $limitedSorted) {
            return ['label' => 'LIMITED HR', 'class' => 'bg-slate-100 text-slate-600'];
        }

        return ['label' => 'FULL HR', 'class' => 'bg-blue-100 text-blue-700'];
    }

    public function isActiveAccount(): bool
    {
        return (int)($this->status ?? self::STATUS_ACTIVE) === self::STATUS_ACTIVE;
    }

    /**
     * @return array{visible:string[],overflow:int}
     */
    public function getPermissionPills(int $maxVisible = 2): array
    {
        $list = $this->getPermissionList();
        if ($list === []) {
            return ['visible' => [], 'overflow' => 0];
        }

        if ($this->isSuperHrAdmin() && count($list) >= 6) {
            return ['visible' => ['all_modules'], 'overflow' => max(0, count($list) - 1)];
        }

        $visible = array_slice($list, 0, $maxVisible);
        $overflow = max(0, count($list) - count($visible));

        return ['visible' => $visible, 'overflow' => $overflow];
    }

    public function hasModulePermission(string $module): bool
    {
        return $this->hasPermission($module);
    }

    protected function getPasswordHash()
    {
        return $this->password_hash;
    }

    public static function hasColumn($column)
    {
        $schema = Yii::$app->db->schema->getTableSchema(static::tableName());

        return $schema !== null && $schema->getColumn($column) !== null;
    }
}
