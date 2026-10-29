<?php

namespace common\modules\employee\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_section_id
 * @property int $day_of_week
 * @property string|null $label
 * @property int $sort_order
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolSectionTimetableRow extends ActiveRecord
{
    public const DAY_NAMES = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    public static function tableName()
    {
        return '{{%school_section_timetable_row}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules()
    {
        return [
            [['school_section_id', 'day_of_week'], 'required'],
            [['school_section_id', 'day_of_week', 'sort_order'], 'integer'],
            [['day_of_week'], 'integer', 'min' => 1, 'max' => 7],
            [['label'], 'trim'],
            [['label'], 'string', 'max' => 32],
            [['created_at', 'updated_at'], 'safe'],
            [['school_section_id'], 'exist', 'targetClass' => SchoolSection::class, 'targetAttribute' => ['school_section_id' => 'id']],
        ];
    }

    public function getSection()
    {
        return $this->hasOne(SchoolSection::class, ['id' => 'school_section_id']);
    }

    public function getDisplayLabel(): string
    {
        $label = trim((string)($this->label ?? ''));

        return $label !== '' ? $label : (self::DAY_NAMES[(int)$this->day_of_week] ?? 'Day');
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        if ($insert && !$this->created_at) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }
}
