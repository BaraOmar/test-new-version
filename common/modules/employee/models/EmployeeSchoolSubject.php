<?php

namespace common\modules\employee\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $employee_id
 * @property int $school_subject_id
 * @property string $created_at
 */
class EmployeeSchoolSubject extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%employee_school_subject}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules()
    {
        return [
            [['employee_id', 'school_subject_id'], 'required'],
            [['employee_id', 'school_subject_id'], 'integer'],
            [['created_at'], 'safe'],
            [['employee_id'], 'exist', 'targetClass' => Employee::class, 'targetAttribute' => ['employee_id' => 'id']],
            [['school_subject_id'], 'exist', 'targetClass' => SchoolSubject::class, 'targetAttribute' => ['school_subject_id' => 'id']],
        ];
    }

    public function getEmployee()
    {
        return $this->hasOne(Employee::class, ['id' => 'employee_id']);
    }

    public function getSubject()
    {
        return $this->hasOne(SchoolSubject::class, ['id' => 'school_subject_id']);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && !$this->created_at) {
            $this->created_at = date('Y-m-d H:i:s');
        }

        return true;
    }
}
