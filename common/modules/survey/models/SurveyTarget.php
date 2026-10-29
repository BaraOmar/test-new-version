<?php

namespace common\modules\survey\models;

use common\modules\employee\models\Department;
use yii\db\ActiveRecord;

class SurveyTarget extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%survey_targets}}';
    }

    public function rules()
    {
        return [
            [['survey_id', 'department_id'], 'required'],
            [['survey_id', 'department_id'], 'integer'],
            [['survey_id'], 'exist', 'targetClass' => Survey::class, 'targetAttribute' => ['survey_id' => 'id']],
            [['department_id'], 'exist', 'targetClass' => Department::class, 'targetAttribute' => ['department_id' => 'id']],
        ];
    }

    public function getDepartment()
    {
        return $this->hasOne(Department::class, ['id' => 'department_id']);
    }
}
