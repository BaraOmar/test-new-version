<?php

namespace common\modules\employee\models;

use common\modules\employee\services\SchoolSectionMarksService;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_subject_id
 * @property int $school_grade_id
 * @property float $credits
 * @property float $max_score
 * @property float $max_first
 * @property float $max_second
 * @property float $max_third
 * @property float $max_final
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolSubjectGrade extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%school_subject_grade}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public static function hasColumn(string $column): bool
    {
        $schema = \Yii::$app->db->schema->getTableSchema(static::tableName(), true);

        return $schema !== null && isset($schema->columns[$column]);
    }

    public function rules()
    {
        $rules = [
            [['school_subject_id', 'school_grade_id'], 'required'],
            [['school_subject_id', 'school_grade_id'], 'integer'],
            [['credits'], 'number', 'min' => 0.01],
            [['credits'], 'default', 'value' => 0],
            [['created_at', 'updated_at'], 'safe'],
            [['school_subject_id'], 'exist', 'targetClass' => SchoolSubject::class, 'targetAttribute' => ['school_subject_id' => 'id']],
            [['school_grade_id'], 'exist', 'targetClass' => SchoolGrade::class, 'targetAttribute' => ['school_grade_id' => 'id']],
            [['school_grade_id'], 'validateGradeBelongsToSubjectDepartment'],
        ];

        if (self::hasColumn('max_score')) {
            $rules[] = [['max_score'], 'number', 'min' => 0.01];
            $rules[] = [['max_score'], 'default', 'value' => 100];
        }

        foreach (['max_first', 'max_second', 'max_third', 'max_final'] as $column) {
            if (self::hasColumn($column)) {
                $rules[] = [[$column], 'number', 'min' => 0.01];
                $rules[] = [[$column], 'default', 'value' => 25];
            }
        }

        return $rules;
    }

    /**
     * @return array{max_first:float,max_second:float,max_third:float,max_final:float}
     */
    public function termMaximaDict(): array
    {
        return [
            'max_first' => round((float)($this->max_first ?? 0), 2),
            'max_second' => round((float)($this->max_second ?? 0), 2),
            'max_third' => round((float)($this->max_third ?? 0), 2),
            'max_final' => round((float)($this->max_final ?? 0), 2),
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && self::hasColumn('max_score') && self::hasColumn('max_first')) {
            $maximum = (float)($this->max_score ?? 100);
            $hasAnyTerm = (float)($this->max_first ?? 0) > 0
                || (float)($this->max_second ?? 0) > 0
                || (float)($this->max_third ?? 0) > 0
                || (float)($this->max_final ?? 0) > 0;
            if (!$hasAnyTerm) {
                $split = SchoolSectionMarksService::evenTermMaxSplit($maximum);
                $this->max_first = $split['max_first'];
                $this->max_second = $split['max_second'];
                $this->max_third = $split['max_third'];
                $this->max_final = $split['max_final'];
            }
        }

        $now = date('Y-m-d H:i:s');
        if ($insert && !$this->created_at) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }

    public function validateGradeBelongsToSubjectDepartment(string $attribute): void
    {
        $subject = $this->subject ?? SchoolSubject::findOne(['id' => (int)$this->school_subject_id]);
        $grade = $this->grade ?? SchoolGrade::findOne(['id' => (int)$this->school_grade_id]);
        if ($subject === null || $grade === null) {
            return;
        }

        if ((int)$grade->school_department_id !== (int)$subject->school_department_id) {
            $this->addError($attribute, 'Selected grade does not belong to this department.');
        }
    }

    public function getSubject()
    {
        return $this->hasOne(SchoolSubject::class, ['id' => 'school_subject_id']);
    }

    public function getGrade()
    {
        return $this->hasOne(SchoolGrade::class, ['id' => 'school_grade_id']);
    }
}
