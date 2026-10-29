<?php

namespace common\modules\finance\models;

use common\modules\employee\models\SchoolStudent;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $parent_profile_id
 * @property int $child_index
 * @property string $first_name
 * @property string|null $second_name
 * @property string|null $third_name
 * @property string $family_name
 * @property int|null $school_student_id
 * @property string|null $birth_certificate_path
 * @property string|null $student_file_path
 * @property string $created_at
 * @property string $updated_at
 *
 * @property FinanceAccountParentProfile $profile
 */
class FinanceAccountParentChild extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%finance_account_parent_child}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules(): array
    {
        return [
            [['parent_profile_id', 'child_index', 'first_name', 'family_name'], 'required'],
            [['parent_profile_id', 'child_index', 'school_student_id'], 'integer'],
            [['child_index'], 'integer', 'min' => 1, 'max' => 20],
            [['first_name', 'second_name', 'third_name', 'family_name'], 'string', 'max' => 128],
            [['birth_certificate_path', 'student_file_path'], 'string', 'max' => 255],
            [
                ['parent_profile_id'],
                'exist',
                'targetClass' => FinanceAccountParentProfile::class,
                'targetAttribute' => ['parent_profile_id' => 'id'],
            ],
            [
                ['school_student_id'],
                'exist',
                'targetClass' => SchoolStudent::class,
                'targetAttribute' => ['school_student_id' => 'id'],
                'skipOnEmpty' => true,
            ],
        ];
    }

    public function getStudent()
    {
        return $this->hasOne(SchoolStudent::class, ['id' => 'school_student_id']);
    }

    public function getProfile()
    {
        return $this->hasOne(FinanceAccountParentProfile::class, ['id' => 'parent_profile_id']);
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
