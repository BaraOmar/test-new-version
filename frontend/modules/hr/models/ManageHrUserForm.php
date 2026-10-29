<?php

namespace frontend\modules\hr\models;

use common\components\HrPermissionCatalog;
use common\models\User;
use common\modules\employee\models\Employee;
use Yii;
use yii\base\Model;

class ManageHrUserForm extends Model
{
    use HrEmployeeProfileTrait;

    public string $email = '';
    public string $password = '';
    /** @var string[] */
    public array $permissions = [];

    public function init(): void
    {
        parent::init();
        $this->applyDefaultJoiningDateForNewRecord();
    }

    public function rules(): array
    {
        $required = array_merge($this->employeeProfileRequiredAttributes(), ['email', 'password']);

        $rules = array_merge([
            [$required, 'required'],
            [['email'], 'trim'],
            ['email', 'email'],
            [
                'email',
                'unique',
                'targetClass' => User::class,
                'targetAttribute' => 'email',
                'message' => 'This email is already in use.',
            ],
            [['email'], 'string', 'max' => 255],
            ['password', 'string', 'min' => 8],
            ['permissions', 'validatePermissions'],
        ], $this->employeeProfileRules());

        if (User::hasColumn('phone')) {
            $rules[] = [['phone'], 'trim'];
            $rules[] = [['phone'], 'string', 'max' => 32];
            $rules[] = [
                ['phone'],
                'unique',
                'targetClass' => User::class,
                'targetAttribute' => 'phone',
                'skipOnEmpty' => true,
                'message' => 'This phone number is already in use.',
            ];
        }

        return $rules;
    }

    public function attributeLabels(): array
    {
        return array_merge($this->employeeProfileAttributeLabels(), [
            'email' => 'Work Email',
            'password' => 'Password',
            'permissions' => 'Permissions',
        ]);
    }

    public function validatePermissions(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $normalized = HrPermissionCatalog::normalizeList($this->permissions);
        if ($normalized === []) {
            $this->addError($attribute, 'Select at least one module permission.');
        }
    }

    public function save(User $actor): bool
    {
        if (!$actor->hasPermission('manage_hr') || !$this->validate()) {
            return false;
        }

        $permissions = $this->filterPermissionsForActor($actor, HrPermissionCatalog::normalizeList($this->permissions));
        if ($permissions === []) {
            $this->addError('permissions', 'Select at least one valid permission.');
            return false;
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            $user = new User();
            if ($user->hasAttribute('username')) {
                $user->username = $this->full_name;
            }
            if ($user->hasAttribute('email')) {
                $user->email = $this->email;
            }
            if ($user->hasAttribute('phone')) {
                $phone = trim((string)$this->phone);
                $user->phone = $phone !== '' ? $phone : null;
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
                $user->setPassword($this->password);
            }
            if (method_exists($user, 'generateAuthKey')) {
                $user->generateAuthKey();
            }

            if (!$user->save()) {
                $this->addEmployeeProfileErrors($user, 'email');
                $tx->rollBack();
                return false;
            }

            $employee = new Employee();
            $this->applyEmployeeProfile($employee, (int)$user->id);

            if (!$employee->save()) {
                $this->addEmployeeProfileErrors($employee);
                $tx->rollBack();
                return false;
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            $this->addError('full_name', YII_DEBUG ? $e->getMessage() : 'Could not create HR user. Please try again.');
            return false;
        }

        return true;
    }

    /**
     * @return array<string, string> permission key => label
     */
    public static function modulePermissionOptions(): array
    {
        return HrPermissionCatalog::labels();
    }

    /**
     * @return array<string, string>
     */
    public static function assignablePermissionOptions(User $actor): array
    {
        return self::modulePermissionOptions();
    }

    /**
     * @param string[] $permissions
     * @return string[]
     */
    public function filterPermissionsForActor(User $actor, array $permissions): array
    {
        return self::filterPermissionsForActorStatic($actor, $permissions);
    }

    /**
     * @param string[] $permissions
     * @return string[]
     */
    public static function filterPermissionsForActorStatic(User $actor, array $permissions): array
    {
        $allowed = array_keys(self::assignablePermissionOptions($actor));

        return array_values(array_intersect($permissions, $allowed));
    }

    /**
     * Grouped permission labels for the manage-HR UI.
     *
     * @return array<string, array{title:string,icon:string,items:array<string,string>}>
     */
    public static function permissionGroups(): array
    {
        return HrPermissionCatalog::permissionGroups();
    }

    /**
     * @return array<string, string[]>
     */
    public static function presets(): array
    {
        return HrPermissionCatalog::presets();
    }

    /**
     * Filter permission groups to what the actor may assign.
     *
     * @return array<string, array{title:string,icon:string,items:array<string,string>}>
     */
    public static function permissionGroupsForActor(User $actor): array
    {
        $allowed = array_keys(self::assignablePermissionOptions($actor));
        $groups = self::permissionGroups();
        $filtered = [];

        foreach ($groups as $key => $group) {
            $items = array_intersect_key($group['items'], array_flip($allowed));
            if ($items !== []) {
                $group['items'] = $items;
                $filtered[$key] = $group;
            }
        }

        return $filtered;
    }

    /**
     * @param mixed $permissions
     * @return string[]
     */
    private function normalizePermissions($permissions): array
    {
        if (!is_array($permissions)) {
            return [];
        }

        $normalized = [];
        foreach ($permissions as $permission) {
            $key = trim((string)$permission);
            if ($key !== '') {
                $normalized[] = $key;
            }
        }

        return array_values(array_unique($normalized));
    }
}
