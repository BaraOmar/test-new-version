<?php

namespace common\modules\employee\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_grade_id
 * @property string $name
 * @property string|null $room_number
 * @property int $capacity_max
 * @property int $current_enrollment
 * @property int|null $lead_teacher_id
 * @property string|null $lead_teacher_assigned_at
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolSection extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%school_section}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules()
    {
        return [
            [['school_grade_id', 'name'], 'required'],
            [['school_grade_id', 'capacity_max', 'current_enrollment', 'lead_teacher_id'], 'integer'],
            [['name'], 'trim'],
            [['name'], 'string', 'max' => 128],
            [['name'], 'validateName'],
            [['room_number'], 'trim'],
            [['room_number'], 'filter', 'filter' => static fn($value) => $value === '' ? null : $value],
            [['room_number'], 'string', 'max' => 64],
            [['room_number'], 'validateRoomNumber'],
            [['capacity_max'], 'default', 'value' => 30],
            [['current_enrollment'], 'default', 'value' => 0],
            [['current_enrollment'], 'compare', 'compareAttribute' => 'capacity_max', 'operator' => '<=', 'type' => 'number'],
            [['lead_teacher_id'], 'filter', 'filter' => static fn($v) => $v === '' || $v === null ? null : (int)$v],
            [['lead_teacher_id'], 'exist', 'targetClass' => Employee::class, 'targetAttribute' => ['lead_teacher_id' => 'id'], 'skipOnEmpty' => true],
            [['lead_teacher_id'], 'validateLeadTeacher'],
            [['created_at', 'updated_at', 'lead_teacher_assigned_at'], 'safe'],
            [['school_grade_id'], 'exist', 'targetClass' => SchoolGrade::class, 'targetAttribute' => ['school_grade_id' => 'id']],
        ];
    }

    public function getGrade()
    {
        return $this->hasOne(SchoolGrade::class, ['id' => 'school_grade_id']);
    }

    public function getLeadTeacher()
    {
        return $this->hasOne(Employee::class, ['id' => 'lead_teacher_id']);
    }

    public function getMaterials()
    {
        return $this->hasMany(SchoolSectionMaterial::class, ['school_section_id' => 'id'])
            ->orderBy(['name' => SORT_ASC]);
    }

    public function getStudents()
    {
        return $this->hasMany(SchoolStudent::class, ['school_section_id' => 'id'])
            ->orderBy(['full_name' => SORT_ASC]);
    }

    public function syncEnrollmentCount(): void
    {
        if (!SchoolStudent::isEnabled()) {
            return;
        }

        $this->current_enrollment = (int)SchoolStudent::find()->where(['school_section_id' => (int)$this->id])->count();
    }

    public function isAtCapacity(): bool
    {
        return $this->capacity_max > 0 && $this->current_enrollment >= $this->capacity_max;
    }

    public function validateName(string $attribute): void
    {
        $name = trim((string)($this->$attribute ?? ''));
        if ($name === '' || !$this->school_grade_id) {
            return;
        }

        $query = static::find()->where([
            'school_grade_id' => (int)$this->school_grade_id,
            'name' => $name,
        ]);

        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id', (int)$this->id]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'This section name is already used in this grade.');
        }
    }

    public function validateRoomNumber(string $attribute): void
    {
        $room = trim((string)($this->$attribute ?? ''));
        if ($room === '') {
            return;
        }

        $query = static::find()->where(['room_number' => $room]);

        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id', (int)$this->id]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'This room number is already assigned to another section.');
        }
    }

    public function validateLeadTeacher(string $attribute): void
    {
        if ($this->$attribute === null || $this->$attribute === '') {
            return;
        }

        $teacherId = (int)$this->$attribute;
        $query = static::find()->where(['lead_teacher_id' => $teacherId]);

        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id', (int)$this->id]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'This teacher is already assigned as class teacher for another section.');
        }

        if (!Employee::hasColumn('school_department_id') || !$this->school_grade_id) {
            return;
        }

        $grade = $this->grade ?? SchoolGrade::findOne(['id' => (int)$this->school_grade_id]);
        if ($grade === null) {
            return;
        }

        $departmentId = (int)($grade->school_department_id ?? 0);
        if ($departmentId <= 0) {
            return;
        }

        $employee = Employee::findOne(['id' => $teacherId]);
        if ($employee === null) {
            return;
        }

        if ((int)($employee->school_department_id ?? 0) !== $departmentId) {
            $this->addError($attribute, 'Selected teacher must belong to this section\'s department.');
        }
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

        if (!$insert && array_key_exists('lead_teacher_id', $this->getDirtyAttributes())) {
            $this->lead_teacher_assigned_at = $this->lead_teacher_id ? $now : null;
        } elseif ($insert && $this->lead_teacher_id && !$this->lead_teacher_assigned_at) {
            $this->lead_teacher_assigned_at = $now;
        }

        return true;
    }
}
