<?php

namespace common\modules\evaluation\models;

use common\modules\employee\models\Employee;
use Yii;
use yii\db\ActiveRecord;

class EvaluationAnswer extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%evaluation_answers}}';
    }

    public function rules()
    {
        $rules = [
            [['response_id', 'evaluation_id', 'question_id', 'employee_id'], 'required'],
            [['response_id', 'evaluation_id', 'question_id', 'employee_id'], 'integer'],
            [['answer_text'], 'string'],
            [['answer_number'], 'number'],
            [['response_id'], 'exist', 'targetClass' => EvaluationResponse::class, 'targetAttribute' => ['response_id' => 'id']],
            [['question_id'], 'exist', 'targetClass' => EvaluationQuestion::class, 'targetAttribute' => ['question_id' => 'id']],
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

    public function getQuestion()
    {
        return $this->hasOne(EvaluationQuestion::class, ['id' => 'question_id']);
    }

    public function getResponse()
    {
        return $this->hasOne(EvaluationResponse::class, ['id' => 'response_id']);
    }

    public static function hasColumn($column)
    {
        $schema = Yii::$app->db->schema->getTableSchema(static::tableName());
        return $schema !== null && $schema->getColumn($column) !== null;
    }
}
