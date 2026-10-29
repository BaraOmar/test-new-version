<?php

namespace frontend\modules\hr\models;

use common\components\HrPermissionCatalog;
use common\models\User;
use common\modules\employee\models\Employee;
use Yii;
use yii\base\Model;

class UpdateHrUserForm extends Model
{
    use HrEmployeeProfileTrait;

    public ?int $user_id = null;
    public ?int $employee_id = null;
    public bool $is_external_account = false;
    public string $email = '';
    public string $password = '';
    /** @var string[] */
    public array $permissions = [];

    public function rules(): array
    {
        if ($this->is_external_account) {
            $required = ['user_id', 'email', 'full_name'];
            if (User::hasColumn('phone')) {
                $required[] = 'phone';
            }

            return [
                [$required, 'required'],
                [['user_id', 'employee_id'], 'integer'],
                [['full_name'], 'trim'],
                [['full_name'], 'match', 'pattern' => '/^[a-zA-Z\s]+$/', 'message' => '{attribute} must contain letters and spaces only.'],
                [['full_name'], 'string', 'max' => 255],
                [['email'], 'trim'],
                ['email', 'email'],
                [
                    'email',
                    'unique',
                    'targetClass' => User::class,
                    'targetAttribute' => 'email',
                    'filter' => function ($query) {
                        if ($this->user_id) {
                            $query->andWhere(['<>', 'id', (int)$this->user_id]);
                        }
                    },
                    'message' => 'This email is already in use.',
                ],
                [['email'], 'string', 'max' => 255],
                [['phone'], 'trim'],
                [['phone'], 'string', 'max' => 32],
                [
                    ['phone'],
                    'unique',
                    'targetClass' => User::class,
                    'targetAttribute' => 'phone',
                    'filter' => function ($query) {
                        if ($this->user_id) {
                            $query->andWhere(['<>', 'id', (int)$this->user_id]);
                        }
                    },
                    'skipOnEmpty' => true,
                    'when' => static fn(): bool => User::hasColumn('phone'),
                    'message' => 'This phone number is already in use.',
                ],
                ['password', 'string', 'min' => 8],
                ['permissions', 'validatePermissions'],
            ];
        }

        $required = array_merge(['user_id', 'email'], $this->employeeProfileRequiredAttributes());

        $rules = [
            [$required, 'required'],
            [['user_id', 'employee_id'], 'integer'],
            [['email'], 'trim'],
            ['email', 'email'],
            [
                'email',
                'unique',
                'targetClass' => User::class,
                'targetAttribute' => 'email',
                'filter' => function ($query) {
                    if ($this->user_id) {
                        $query->andWhere(['<>', 'id', (int)$this->user_id]);
                    }
                },
                'message' => 'This email is already in use.',
            ],
            [
                'email',
                'unique',
                'targetClass' => Employee::class,
                'targetAttribute' => 'email',
                'filter' => function ($query) {
                    if ($this->employee_id) {
                        $query->andWhere(['<>', 'id', (int)$this->employee_id]);
                    }
                },
                'message' => 'This email is already in use.',
            ],
            [['email'], 'string', 'max' => 255],
            ['password', 'string', 'min' => 8],
            ['permissions', 'validatePermissions'],
        ];

        return array_merge($rules, $this->employeeProfileRules($this->employee_id));
    }

    public function attributeLabels(): array
    {
        return array_merge($this->employeeProfileAttributeLabels(), [
            'email' => 'Work Email',
            'password' => 'New Password',
            'permissions' => 'Permissions',
        ]);
    }

    public function loadFromUser(User $user): void
    {
        $this->user_id = (int)$user->id;
        $this->email = (string)($user->email ?? '');
        $this->password = '';
        $this->permissions = HrPermissionCatalog::expandForDisplay($user->getPermissionList());

        $employee = $this->findEmployeeForUser((int)$user->id);
        if ($employee !== null) {
            $this->is_external_account = false;
            $this->employee_id = (int)$employee->id;
            $this->loadEmployeeProfileFromEmployee($employee);
        } else {
            $this->is_external_account = true;
            $this->full_name = (string)($user->username ?? '');
            if (User::hasColumn('phone')) {
                $this->phone = $user->phone !== null ? (string)$user->phone : null;
            }
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

    public function save(User $actor): bool
    {
        if (!$actor->hasPermission('manage_hr') || !$this->validate()) {
            return false;
        }

        $user = User::findOne(['id' => (int)$this->user_id]);
        if ($user === null || !$user->isHr()) {
            $this->addError('user_id', 'HR user not found.');
            return false;
        }

        if ($user->isSuperHrAdmin()) {
            $this->addError('user_id', 'Super admin accounts cannot be modified.');
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
            if ($user->hasAttribute('username')) {
                $user->username = $this->full_name;
            }
            if ($user->hasAttribute('email')) {
                $user->email = $this->email;
            }
            if ($this->is_external_account && $user->hasAttribute('phone')) {
                $user->phone = trim((string)$this->phone) !== '' ? trim((string)$this->phone) : null;
            }
            if ($user->hasAttribute('permissions')) {
                $user->permissions = json_encode(array_values($permissions), JSON_UNESCAPED_SLASHES);
            }
            if ($user->hasAttribute('updated_at')) {
                $user->updated_at = time();
            }
            if ($this->password !== '' && method_exists($user, 'setPassword')) {
                $user->setPassword($this->password);
                if (method_exists($user, 'generateAuthKey')) {
                    $user->generateAuthKey();
                }
            }

            if (!$user->save()) {
                $this->addEmployeeProfileErrors($user, 'email');
                $tx->rollBack();
                return false;
            }

            if ($this->is_external_account) {
                $tx->commit();

                return true;
            }

            $employee = $this->employee_id
                ? Employee::findOne(['id' => (int)$this->employee_id])
                : null;
            if ($employee === null) {
                $employee = new Employee();
            }

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
            $this->addError('full_name', YII_DEBUG ? $e->getMessage() : 'Could not update HR user. Please try again.');
            return false;
        }

        return true;
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
