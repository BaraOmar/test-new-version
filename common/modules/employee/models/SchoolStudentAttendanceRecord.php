<?php

namespace common\modules\employee\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $session_id
 * @property int $school_student_id
 * @property int|null $school_section_id
 * @property string $status
 * @property string|null $note
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolStudentAttendanceRecord extends ActiveRecord
{
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';
    public const STATUS_EXCUSED = 'excused';

    public static function tableName()
    {
        return '{{%school_student_attendance_record}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PRESENT => 'Present',
            self::STATUS_ABSENT => 'Absent',
            self::STATUS_LATE => 'Late',
            self::STATUS_EXCUSED => 'Excused',
        ];
    }

    public static function attendedStatuses(): array
    {
        return [
            self::STATUS_PRESENT,
            self::STATUS_LATE,
        ];
    }

    public function rules()
    {
        return [
            [['session_id', 'school_student_id', 'status'], 'required'],
            [['session_id', 'school_student_id', 'school_section_id'], 'integer'],
            [['status'], 'in', 'range' => array_keys(self::statusOptions())],
            [['note'], 'trim'],
            [['note'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'safe'],
            [['session_id'], 'exist', 'targetClass' => SchoolSectionAttendanceSession::class, 'targetAttribute' => ['session_id' => 'id']],
            [['school_student_id'], 'exist', 'targetClass' => SchoolStudent::class, 'targetAttribute' => ['school_student_id' => 'id']],
            [['school_section_id'], 'exist', 'targetClass' => SchoolSection::class, 'targetAttribute' => ['school_section_id' => 'id'], 'skipOnEmpty' => true],
        ];
    }

    public function getSection()
    {
        return $this->hasOne(SchoolSection::class, ['id' => 'school_section_id']);
    }

    public function getSession()
    {
        return $this->hasOne(SchoolSectionAttendanceSession::class, ['id' => 'session_id']);
    }

    public function getStudent()
    {
        return $this->hasOne(SchoolStudent::class, ['id' => 'school_student_id']);
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
