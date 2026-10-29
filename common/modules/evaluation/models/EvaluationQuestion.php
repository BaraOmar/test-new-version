<?php

namespace common\modules\evaluation\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $evaluation_id
 * @property string $question_text
 * @property string $question_type
 * @property int $is_required
 * @property int|null $scale_min
 * @property int|null $scale_max
 * @property int|null $position
 */
class EvaluationQuestion extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%evaluation_questions}}';
    }

    public function rules()
    {
        $rules = [
            [['evaluation_id', 'question_text', 'question_type'], 'required'],
            [['evaluation_id', 'is_required'], 'integer'],
            [['question_text'], 'string'],
            [['question_type'], 'in', 'range' => [
                'rating',
                'multiple_choice',
                'checkbox',
                'dropdown',
                'short_text',
                'long_text',
                'linear_scale',
                'section_break',
                'section_title',
                'image',
                'video',
            ]],
            [['is_required'], 'default', 'value' => 0],
            [['evaluation_id'], 'exist', 'targetClass' => Evaluation::class, 'targetAttribute' => ['evaluation_id' => 'id']],
        ];

        foreach (['scale_min', 'scale_max', 'position'] as $column) {
            if (self::hasColumn($column)) {
                $rules[] = [[$column], 'integer'];
                $rules[] = [[$column], 'default', 'value' => null];
            }
        }

        if (self::hasColumn('created_at')) {
            $rules[] = [['created_at'], 'safe'];
        }

        return $rules;
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && $this->hasAttribute('created_at') && !$this->created_at) {
            $this->created_at = date('Y-m-d H:i:s');
        }

        return true;
    }

    public function getEvaluation()
    {
        return $this->hasOne(Evaluation::class, ['id' => 'evaluation_id']);
    }

    public function getOptions()
    {
        return $this->hasMany(EvaluationQuestionOption::class, ['question_id' => 'id'])
            ->orderBy([EvaluationQuestionOption::sortColumn() => SORT_ASC, 'id' => SORT_ASC]);
    }

    public static function hasColumn($column)
    {
        $schema = Yii::$app->db->schema->getTableSchema(static::tableName());
        return $schema !== null && $schema->getColumn($column) !== null;
    }
}
