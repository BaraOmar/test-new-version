<?php

namespace common\modules\employee\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_department_id
 * @property string $name
 * @property int $sort_order
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolGrade extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%school_grade}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules()
    {
        return [
            [['school_department_id', 'name'], 'required'],
            [['school_department_id', 'sort_order'], 'integer'],
            [['name'], 'trim'],
            [['name'], 'string', 'max' => 128],
            [['sort_order'], 'default', 'value' => 0],
            [['created_at', 'updated_at'], 'safe'],
            [['school_department_id'], 'exist', 'targetClass' => SchoolDepartment::class, 'targetAttribute' => ['school_department_id' => 'id']],
        ];
    }

    public function getDepartment()
    {
        return $this->hasOne(SchoolDepartment::class, ['id' => 'school_department_id']);
    }

    public function getSections()
    {
        return $this->hasMany(SchoolSection::class, ['school_grade_id' => 'id'])
            ->orderBy(['name' => SORT_ASC]);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        if ($insert && !$this->created_at) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }
}
