<?php

namespace common\modules\employee\models;

use common\models\User;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_section_id
 * @property string $attendance_date
 * @property int|null $taken_by_user_id
 * @property string|null $taken_at
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolSectionAttendanceSession extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%school_section_attendance_session}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules()
    {
        return [
            [['school_section_id', 'attendance_date'], 'required'],
            [['school_section_id', 'taken_by_user_id'], 'integer'],
            [['attendance_date'], 'date', 'format' => 'php:Y-m-d'],
            [['taken_at', 'created_at', 'updated_at'], 'safe'],
            [['school_section_id'], 'exist', 'targetClass' => SchoolSection::class, 'targetAttribute' => ['school_section_id' => 'id']],
            [['taken_by_user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['taken_by_user_id' => 'id'], 'skipOnEmpty' => true],
        ];
    }

    public function getSection()
    {
        return $this->hasOne(SchoolSection::class, ['id' => 'school_section_id']);
    }

    public function getTakenByUser()
    {
        return $this->hasOne(User::class, ['id' => 'taken_by_user_id']);
    }

    public function getRecords()
    {
        return $this->hasMany(SchoolStudentAttendanceRecord::class, ['session_id' => 'id'])
            ->orderBy(['id' => SORT_ASC]);
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
