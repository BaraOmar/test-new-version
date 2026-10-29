<?php

namespace common\modules\finance\models;

use common\models\User;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int|null $finance_account_id
 * @property int|null $user_id
 * @property string $first_name
 * @property string|null $second_name
 * @property string|null $third_name
 * @property string $family_name
 * @property string $phone
 * @property string $emergency_phone_2
 * @property string|null $email
 * @property string|null $login_password_plain
 * @property int $kids_count
 * @property string|null $family_notebook_path
 * @property string|null $student_file_path
 * @property string $created_at
 * @property string $updated_at
 *
 * @property FinanceAccount|null $account
 * @property User|null $user
 * @property FinanceAccountParentChild[] $children
 */
class FinanceAccountParentProfile extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%finance_account_parent_profile}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules(): array
    {
        return [
            [['first_name', 'family_name', 'phone', 'emergency_phone_2', 'kids_count'], 'required'],
            [['finance_account_id', 'user_id', 'kids_count'], 'integer'],
            [['first_name', 'second_name', 'third_name', 'family_name'], 'string', 'max' => 128],
            [['phone', 'emergency_phone_2'], 'string', 'max' => 32],
            [
                ['phone', 'emergency_phone_2'],
                'match',
                'pattern' => '/^\d+$/',
                'message' => '{attribute} must contain numbers only.',
            ],
            [['phone'], 'unique', 'message' => 'This phone number is already used by another parent.'],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 255],
            [['login_password_plain'], 'string', 'max' => 128],
            [['family_notebook_path', 'student_file_path'], 'string', 'max' => 255],
            [['kids_count'], 'integer', 'min' => 1, 'max' => 20],
            [
                ['finance_account_id'],
                'exist',
                'targetClass' => FinanceAccount::class,
                'targetAttribute' => ['finance_account_id' => 'id'],
                'skipOnEmpty' => true,
            ],
            [
                ['user_id'],
                'exist',
                'targetClass' => User::class,
                'targetAttribute' => ['user_id' => 'id'],
                'skipOnEmpty' => true,
            ],
        ];
    }

    public function getAccount()
    {
        return $this->hasOne(FinanceAccount::class, ['id' => 'finance_account_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getChildren()
    {
        return $this->hasMany(FinanceAccountParentChild::class, ['parent_profile_id' => 'id'])
            ->orderBy(['child_index' => SORT_ASC]);
    }

    public function getFullName(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->second_name,
            $this->third_name,
            $this->family_name,
        ])));
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }
}
