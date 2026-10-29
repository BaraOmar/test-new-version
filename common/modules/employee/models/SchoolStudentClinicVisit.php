<?php

namespace common\modules\employee\models;

use common\models\User;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_student_id
 * @property string $visit_date
 * @property string $time_in
 * @property string $primary_reason
 * @property string $visit_type
 * @property string $status
 * @property bool $medicine_given
 * @property string|null $medicine_name
 * @property string|null $dosage
 * @property string|null $administration_time
 * @property string|null $pharmaceutical_notes
 * @property string $action_taken
 * @property string|null $clinical_notes
 * @property int|null $recorded_by_user_id
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolStudentClinicVisit extends ActiveRecord
{
    public const TYPE_ROUTINE = 'routine_check';
    public const TYPE_ILLNESS = 'illness';
    public const TYPE_INJURY = 'injury';
    public const TYPE_EMERGENCY = 'emergency';
    public const TYPE_FOLLOW_UP = 'follow_up';

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REFERRED = 'referred';
    public const STATUS_SENT_HOME = 'sent_home';

    public const ACTION_RESTED = 'rested_returned';
    public const ACTION_PARENT = 'parent_contacted';
    public const ACTION_SENT_HOME = 'sent_home';
    public const ACTION_HOSPITAL = 'referred_hospital';
    public const ACTION_MONITORED = 'monitored';

    public static function tableName()
    {
        return '{{%school_student_clinic_visit}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public static function visitTypeOptions(): array
    {
        return [
            self::TYPE_ROUTINE => 'Routine Check',
            self::TYPE_ILLNESS => 'Illness',
            self::TYPE_INJURY => 'Injury',
            self::TYPE_EMERGENCY => 'Emergency',
            self::TYPE_FOLLOW_UP => 'Follow-up',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REFERRED => 'Referred',
            self::STATUS_SENT_HOME => 'Sent Home',
        ];
    }

    public static function actionTakenOptions(): array
    {
        return [
            self::ACTION_RESTED => 'Rested & Returned to Class',
            self::ACTION_PARENT => 'Parent Contacted',
            self::ACTION_SENT_HOME => 'Sent Home',
            self::ACTION_HOSPITAL => 'Referred to Hospital',
            self::ACTION_MONITORED => 'Monitored in Clinic',
        ];
    }

    /**
     * @return string[]
     */
    public static function parentNotificationActions(): array
    {
        return [
            self::ACTION_PARENT,
            self::ACTION_SENT_HOME,
            self::ACTION_HOSPITAL,
        ];
    }

    public static function requiresParentNotification(?string $actionTaken): bool
    {
        return in_array((string)$actionTaken, self::parentNotificationActions(), true);
    }

    public function rules()
    {
        return [
            [['school_student_id', 'visit_date', 'time_in', 'primary_reason'], 'required'],
            [['school_student_id', 'recorded_by_user_id'], 'integer'],
            [['medicine_given'], 'boolean'],
            [['medicine_given'], 'default', 'value' => false],
            [['visit_type'], 'default', 'value' => self::TYPE_ROUTINE],
            [['visit_type'], 'in', 'range' => array_keys(self::visitTypeOptions())],
            [['status'], 'default', 'value' => self::STATUS_IN_PROGRESS],
            [['status'], 'in', 'range' => array_keys(self::statusOptions())],
            [['action_taken'], 'default', 'value' => self::ACTION_RESTED],
            [['action_taken'], 'in', 'range' => array_keys(self::actionTakenOptions())],
            [['primary_reason'], 'trim'],
            [['primary_reason'], 'string', 'max' => 255],
            [['medicine_name'], 'string', 'max' => 128],
            [['dosage'], 'string', 'max' => 64],
            [['pharmaceutical_notes', 'clinical_notes'], 'trim'],
            [['pharmaceutical_notes', 'clinical_notes'], 'string'],
            [['visit_date', 'time_in', 'administration_time', 'created_at', 'updated_at'], 'safe'],
            [['school_student_id'], 'exist', 'targetClass' => SchoolStudent::class, 'targetAttribute' => ['school_student_id' => 'id']],
            [['recorded_by_user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['recorded_by_user_id' => 'id'], 'skipOnEmpty' => true],
        ];
    }

    public function attributeLabels()
    {
        return [
            'visit_date' => 'Visit Date',
            'time_in' => 'Time In',
            'primary_reason' => 'Primary Reason',
            'visit_type' => 'Visit Type',
            'status' => 'Status',
            'medicine_given' => 'Medicine Given',
            'medicine_name' => 'Medicine Name',
            'dosage' => 'Dosage',
            'administration_time' => 'Administration Time',
            'pharmaceutical_notes' => 'Pharmaceutical Notes',
            'action_taken' => 'Action Taken',
            'clinical_notes' => "Nurse's Clinical Notes",
        ];
    }

    public function getStudent()
    {
        return $this->hasOne(SchoolStudent::class, ['id' => 'school_student_id']);
    }

    public function getRecordedBy()
    {
        return $this->hasOne(User::class, ['id' => 'recorded_by_user_id']);
    }

    public function getVisitTypeLabel(): string
    {
        return self::visitTypeOptions()[$this->visit_type] ?? $this->visit_type;
    }

    public function getStatusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function getActionTakenLabel(): string
    {
        return self::actionTakenOptions()[$this->action_taken] ?? $this->action_taken;
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (!$this->medicine_given) {
            $this->medicine_name = null;
            $this->dosage = null;
            $this->administration_time = null;
            $this->pharmaceutical_notes = null;
        }

        $now = date('Y-m-d H:i:s');
        if ($insert && !$this->created_at) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }
}
