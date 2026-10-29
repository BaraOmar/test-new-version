<?php

namespace common\modules\employee\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_section_id
 * @property string $start_time
 * @property string $end_time
 * @property string|null $label
 * @property int $sort_order
 * @property bool $is_break
 * @property bool $is_exam
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolSectionTimetableColumn extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%school_section_timetable_column}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules()
    {
        return [
            [['school_section_id', 'start_time', 'end_time'], 'required'],
            [['school_section_id', 'sort_order'], 'integer'],
            [['is_break', 'is_exam'], 'boolean'],
            [['label'], 'trim'],
            [['label'], 'string', 'max' => 64],
            [['start_time', 'end_time'], 'safe'],
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
        if ($label !== '') {
            return $label;
        }

        return $this->formatTime((string)$this->start_time);
    }

    public function formatTime(string $time): string
    {
        $timestamp = strtotime($time);

        return $timestamp !== false ? date('g:i A', $timestamp) : $time;
    }

    public function getTimeRangeLabel(): string
    {
        return $this->formatTime((string)$this->start_time) . ' – ' . $this->formatTime((string)$this->end_time);
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
