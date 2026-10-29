<?php

namespace common\modules\survey\models;

use Yii;
use yii\db\ActiveRecord;

class SurveyQuestionOption extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%survey_question_options}}';
    }

    public function rules()
    {
        $rules = [
            [['question_id', self::textColumn()], 'required'],
            [['question_id'], 'integer'],
            [[self::textColumn()], 'string', 'max' => 1000],
            [['question_id'], 'exist', 'targetClass' => SurveyQuestion::class, 'targetAttribute' => ['question_id' => 'id']],
        ];

        $sortColumn = self::sortColumn();
        if ($sortColumn !== 'id') {
            $rules[] = [[$sortColumn], 'integer'];
            $rules[] = [[$sortColumn], 'default', 'value' => 0];
        }

        return $rules;
    }

    public function getLabel()
    {
        foreach (['option_text', 'text', 'label'] as $column) {
            if ($this->hasAttribute($column) && $this->$column !== null && $this->$column !== '') {
                return $this->$column;
            }
        }

        return '';
    }

    public static function textColumn()
    {
        if (self::hasColumn('option_text')) {
            return 'option_text';
        }
        if (self::hasColumn('text')) {
            return 'text';
        }

        return 'option_text';
    }

    public static function sortColumn()
    {
        if (self::hasColumn('sort_order')) {
            return 'sort_order';
        }
        if (self::hasColumn('position')) {
            return 'position';
        }

        return 'id';
    }

    public static function hasColumn($column)
    {
        $schema = Yii::$app->db->schema->getTableSchema(static::tableName());
        return $schema !== null && $schema->getColumn($column) !== null;
    }
}
