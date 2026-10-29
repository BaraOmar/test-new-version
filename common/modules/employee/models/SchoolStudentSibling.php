<?php

namespace common\modules\employee\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_student_id
 * @property int $sibling_student_id
 * @property string $created_at
 */
class SchoolStudentSibling extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%school_student_sibling}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules()
    {
        return [
            [['school_student_id', 'sibling_student_id'], 'required'],
            [['school_student_id', 'sibling_student_id'], 'integer'],
            [['created_at'], 'safe'],
            [['school_student_id'], 'exist', 'targetClass' => SchoolStudent::class, 'targetAttribute' => ['school_student_id' => 'id']],
            [['sibling_student_id'], 'exist', 'targetClass' => SchoolStudent::class, 'targetAttribute' => ['sibling_student_id' => 'id']],
        ];
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
