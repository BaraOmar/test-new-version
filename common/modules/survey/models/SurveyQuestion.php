<?php

namespace common\modules\survey\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $survey_id
 * @property string $question_text
 * @property string $question_type
 * @property int $is_required
 * @property int|null $scale_min
 * @property int|null $scale_max
 * @property int|null $position
 * @property int $content_version
 */
class SurveyQuestion extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%survey_questions}}';
    }

    public function rules()
    {
        $rules = [
            [['survey_id', 'question_text', 'question_type'], 'required'],
            [['survey_id', 'is_required'], 'integer'],
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
            [['survey_id'], 'exist', 'targetClass' => Survey::class, 'targetAttribute' => ['survey_id' => 'id']],
        ];

        foreach (['scale_min', 'scale_max', 'position', 'content_version'] as $column) {
            if (self::hasColumn($column)) {
                $rules[] = [[$column], 'integer'];
                if ($column !== 'content_version') {
                    $rules[] = [[$column], 'default', 'value' => null];
                }
            }
        }
        if (self::hasColumn('content_version')) {
            $rules[] = [['content_version'], 'default', 'value' => 1];
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

    public function getSurvey()
    {
        return $this->hasOne(Survey::class, ['id' => 'survey_id']);
    }

    public function getOptions()
    {
        return $this->hasMany(SurveyQuestionOption::class, ['question_id' => 'id'])
            ->orderBy([SurveyQuestionOption::sortColumn() => SORT_ASC, 'id' => SORT_ASC]);
    }

    public static function hasColumn($column)
    {
        $schema = Yii::$app->db->schema->getTableSchema(static::tableName());
        return $schema !== null && $schema->getColumn($column) !== null;
    }
}
