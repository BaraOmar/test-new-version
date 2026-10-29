<?php

namespace common\modules\survey\models;

use common\modules\employee\models\Employee;
use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $survey_id
 * @property int $employee_id
 * @property string|null $created_at
 * @property int|null $content_version
 */
class SurveyResponse extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%survey_responses}}';
    }

    public function rules()
    {
        $rules = [
            [['survey_id', 'employee_id'], 'required'],
            [['survey_id', 'employee_id'], 'integer'],
            [['survey_id'], 'exist', 'targetClass' => Survey::class, 'targetAttribute' => ['survey_id' => 'id']],
            [['employee_id'], 'exist', 'targetClass' => Employee::class, 'targetAttribute' => ['employee_id' => 'id']],
        ];

        if (self::hasColumn('created_at')) {
            $rules[] = [['created_at'], 'safe'];
        }
        if (self::hasColumn('content_version')) {
            $rules[] = [['content_version'], 'integer'];
        }

        return $rules;
    }

    public function getSurvey()
    {
        return $this->hasOne(Survey::class, ['id' => 'survey_id']);
    }

    public function getEmployee()
    {
        return $this->hasOne(Employee::class, ['id' => 'employee_id']);
    }

    public function getAnswers()
    {
        return $this->hasMany(SurveyAnswer::class, ['response_id' => 'id']);
    }

    public static function hasColumn($column)
    {
        $schema = Yii::$app->db->schema->getTableSchema(static::tableName());
        return $schema !== null && $schema->getColumn($column) !== null;
    }
}
