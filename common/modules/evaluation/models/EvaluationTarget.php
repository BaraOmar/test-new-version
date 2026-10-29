<?php

namespace common\modules\evaluation\models;

use common\modules\employee\models\Department;
use yii\db\ActiveRecord;

class EvaluationTarget extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%evaluation_targets}}';
    }

    public function rules()
    {
        return [
            [['evaluation_id', 'department_id'], 'required'],
            [['evaluation_id', 'department_id'], 'integer'],
            [['evaluation_id'], 'exist', 'targetClass' => Evaluation::class, 'targetAttribute' => ['evaluation_id' => 'id']],
            [['department_id'], 'exist', 'targetClass' => Department::class, 'targetAttribute' => ['department_id' => 'id']],
        ];
    }

    public function getDepartment()
    {
        return $this->hasOne(Department::class, ['id' => 'department_id']);
    }
}
