<?php

namespace common\modules\employee\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_section_id
 * @property int|null $school_subject_id
 * @property string $name
 * @property string $icon
 * @property int|null $instructor_id
 * @property string|null $schedule
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolSectionMaterial extends ActiveRecord
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PENDING = 'pending';

    public static function tableName()
    {
        return '{{%school_section_material}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules()
    {
        return [
            [['school_section_id'], 'required'],
            [['school_section_id', 'school_subject_id', 'instructor_id'], 'integer'],
            [['name', 'schedule'], 'trim'],
            [['name'], 'string', 'max' => 255],
            [['name'], 'validateNameOrSubject'],
            [['schedule'], 'string', 'max' => 128],
            [['icon'], 'string', 'max' => 64],
            [['icon'], 'default', 'value' => 'menu_book'],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_PENDING]],
            [['school_subject_id'], 'required', 'when' => static fn(): bool => SchoolSubjectGrade::isEnabled()],
            [['school_subject_id'], 'validateSubjectForSection', 'when' => static fn(): bool => SchoolSubjectGrade::isEnabled()],
            [['school_subject_id'], 'validateUniqueMaterialInSection'],
            [['name'], 'validateUniqueMaterialInSection', 'when' => static fn(): bool => !SchoolSubjectGrade::isEnabled()],
            [['instructor_id'], 'filter', 'filter' => static fn($v) => $v === '' || $v === null ? null : (int)$v],
            [['school_subject_id'], 'filter', 'filter' => static fn($v) => $v === '' || $v === null ? null : (int)$v],
            [['school_section_id'], 'exist', 'targetClass' => SchoolSection::class, 'targetAttribute' => ['school_section_id' => 'id']],
            [['school_subject_id'], 'exist', 'targetClass' => SchoolSubject::class, 'targetAttribute' => ['school_subject_id' => 'id'], 'skipOnEmpty' => true],
            [['instructor_id'], 'exist', 'targetClass' => Employee::class, 'targetAttribute' => ['instructor_id' => 'id'], 'skipOnEmpty' => true],
            [['instructor_id'], 'validateInstructorForSection'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function getSection()
    {
        return $this->hasOne(SchoolSection::class, ['id' => 'school_section_id']);
    }

    public function getSubject()
    {
        return $this->hasOne(SchoolSubject::class, ['id' => 'school_subject_id']);
    }

    public function getInstructor()
    {
        return $this->hasOne(Employee::class, ['id' => 'instructor_id']);
    }

    public function getTopics()
    {
        return $this->hasMany(SchoolLmsCourseTopic::class, ['school_section_material_id' => 'id'])
            ->orderBy(['sequence' => SORT_ASC, 'id' => SORT_ASC]);
    }

    public function getStatusLabel(): string
    {
        return $this->status === self::STATUS_PENDING ? 'Pending' : 'Active';
    }

    public function validateNameOrSubject(string $attribute): void
    {
        if ($this->school_subject_id || SchoolSubjectGrade::isEnabled()) {
            return;
        }

        if (trim((string)$this->name) === '') {
            $this->addError($attribute, 'Material name is required when no subject is selected.');
        }
    }

    public function validateSubjectForSection(string $attribute): void
    {
        if (!$this->school_subject_id || !$this->school_section_id) {
            return;
        }

        $section = $this->section ?? SchoolSection::findOne(['id' => (int)$this->school_section_id]);
        if ($section === null) {
            return;
        }

        $gradeId = (int)$section->school_grade_id;
        if ($gradeId <= 0) {
            $this->addError($attribute, 'Unable to validate subject for this section.');

            return;
        }

        $subjectGrade = SchoolSubjectGrade::findOne([
            'school_subject_id' => (int)$this->school_subject_id,
            'school_grade_id' => $gradeId,
        ]);

        if ($subjectGrade === null) {
            $this->addError($attribute, 'Selected subject is not assigned to this section\'s grade.');
        }
    }

    public function validateInstructorForSection(string $attribute): void
    {
        if (!$this->instructor_id || !$this->school_section_id) {
            return;
        }

        if (!Employee::hasColumn('school_department_id')) {
            return;
        }

        $section = $this->section ?? SchoolSection::findOne(['id' => (int)$this->school_section_id]);
        if ($section === null) {
            return;
        }

        $departmentId = (int)($section->grade?->school_department_id ?? 0);
        if ($departmentId <= 0) {
            return;
        }

        $employee = Employee::findOne(['id' => (int)$this->instructor_id]);
        if ($employee === null) {
            return;
        }

        if ((int)($employee->school_department_id ?? 0) !== $departmentId) {
            $this->addError($attribute, 'Selected instructor must belong to this section\'s department.');

            return;
        }

        if ((int)$this->school_subject_id > 0 && EmployeeSchoolSubject::isEnabled()) {
            $assigned = EmployeeSchoolSubject::findOne([
                'employee_id' => (int)$this->instructor_id,
                'school_subject_id' => (int)$this->school_subject_id,
            ]);
            if ($assigned === null) {
                $this->addError($attribute, 'Selected instructor is not assigned to this subject.');
            }
        }
    }

    public function validateUniqueMaterialInSection(string $attribute): void
    {
        if (!$this->school_section_id) {
            return;
        }

        $query = static::find()->where(['school_section_id' => (int)$this->school_section_id]);
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id', (int)$this->id]);
        }

        if ((int)$this->school_subject_id > 0) {
            if ($query->andWhere(['school_subject_id' => (int)$this->school_subject_id])->exists()) {
                $this->addError($attribute, 'This subject is already assigned to this section.');
            }

            return;
        }

        $name = mb_strtolower(trim((string)$this->name));
        if ($name === '') {
            return;
        }

        foreach ($query->all() as $existing) {
            if (mb_strtolower(trim((string)$existing->name)) === $name) {
                $this->addError($attribute, 'This material is already assigned to this section.');
                return;
            }
        }
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->school_subject_id && trim($this->name) === '') {
            $subject = $this->subject ?? SchoolSubject::findOne(['id' => $this->school_subject_id]);
            if ($subject !== null) {
                $this->name = (string)$subject->name;
            }
        }

        $now = date('Y-m-d H:i:s');
        if ($insert && !$this->created_at) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }
}
