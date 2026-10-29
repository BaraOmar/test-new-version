<?php

namespace common\modules\evaluation\models;

use common\modules\employee\models\Employee;
use Yii;
use yii\db\ActiveRecord;

class EvaluationEmployeeTarget extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%evaluation_employee_targets}}';
    }

    public function rules()
    {
        $rules = [
            [['evaluation_id', 'employee_id'], 'required'],
            [['evaluation_id', 'employee_id'], 'integer'],
            [['evaluation_id'], 'exist', 'targetClass' => Evaluation::class, 'targetAttribute' => ['evaluation_id' => 'id']],
            [['employee_id'], 'exist', 'targetClass' => Employee::class, 'targetAttribute' => ['employee_id' => 'id']],
        ];

        if (self::hasColumn('created_at')) {
            $rules[] = [['created_at'], 'safe'];
        }

        return $rules;
    }

    public function getEmployee()
    {
        return $this->hasOne(Employee::class, ['id' => 'employee_id']);
    }

    public static function hasColumn($column)
    {
        $schema = Yii::$app->db->schema->getTableSchema(static::tableName());
        return $schema !== null && $schema->getColumn($column) !== null;
    }
}
