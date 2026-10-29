<?php

namespace common\modules\employee\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_section_id
 * @property int $row_id
 * @property int $column_id
 * @property int|null $school_section_material_id
 * @property int|null $instructor_id
 * @property string|null $room_number
 * @property string|null $lesson_brief
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolSectionTimetableSlot extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%school_section_timetable_slot}}';
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

    public function validateLessonBrief(string $attribute): void
    {
        $text = trim((string)($this->$attribute ?? ''));
        if ($text === '') {
            $this->$attribute = null;

            return;
        }

        if (mb_strlen($text) > 100) {
            $this->addError($attribute, 'Lesson note is too long (max 100 characters).');
        }

        $wordCount = count(array_filter(preg_split('/\s+/u', $text) ?: [], static fn(string $word): bool => $word !== ''));
        if ($wordCount > 15) {
            $this->addError($attribute, 'Lesson note is too long (max 15 words).');
        }
    }

    public function rules()
    {
        return [
            [['school_section_id', 'row_id', 'column_id'], 'required'],
            [['school_section_id', 'row_id', 'column_id', 'school_section_material_id', 'instructor_id'], 'integer'],
            [['room_number'], 'trim'],
            [['room_number'], 'string', 'max' => 64],
            [['lesson_brief'], 'trim'],
            [['lesson_brief'], 'string', 'max' => 100],
            [['lesson_brief'], 'validateLessonBrief'],
            [['created_at', 'updated_at'], 'safe'],
            [['school_section_id'], 'exist', 'targetClass' => SchoolSection::class, 'targetAttribute' => ['school_section_id' => 'id']],
            [['row_id'], 'exist', 'targetClass' => SchoolSectionTimetableRow::class, 'targetAttribute' => ['row_id' => 'id']],
            [['column_id'], 'exist', 'targetClass' => SchoolSectionTimetableColumn::class, 'targetAttribute' => ['column_id' => 'id']],
            [['school_section_material_id'], 'exist', 'targetClass' => SchoolSectionMaterial::class, 'targetAttribute' => ['school_section_material_id' => 'id'], 'skipOnEmpty' => true],
            [['instructor_id'], 'exist', 'targetClass' => Employee::class, 'targetAttribute' => ['instructor_id' => 'id'], 'skipOnEmpty' => true],
        ];
    }

    public function getSection()
    {
        return $this->hasOne(SchoolSection::class, ['id' => 'school_section_id']);
    }

    public function getRow()
    {
        return $this->hasOne(SchoolSectionTimetableRow::class, ['id' => 'row_id']);
    }

    public function getColumn()
    {
        return $this->hasOne(SchoolSectionTimetableColumn::class, ['id' => 'column_id']);
    }

    public function getMaterial()
    {
        return $this->hasOne(SchoolSectionMaterial::class, ['id' => 'school_section_material_id']);
    }

    public function getInstructor()
    {
        return $this->hasOne(Employee::class, ['id' => 'instructor_id']);
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
