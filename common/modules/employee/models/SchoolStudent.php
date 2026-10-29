<?php

namespace common\modules\employee\models;

use common\models\User;
use common\modules\finance\models\FinanceAccountParentChild;
use common\modules\finance\models\FinanceAccountParentProfile;
use common\modules\transport\models\TransportArea;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int|null $school_section_id
 * @property int|null $school_grade_id
 * @property int|null $user_id
 * @property string $student_code
 * @property string $full_name
 * @property string|null $first_name
 * @property string|null $father_name
 * @property string|null $grandfather_name
 * @property string|null $family_name
 * @property string|null $email
 * @property string|null $gender
 * @property string|null $nationality
 * @property string|null $birth_date
 * @property string|null $home_address
 * @property int|null $residence_area_id
 * @property string|null $login_password_plain
 * @property string|null $phone
 * @property string|null $academic_year
 * @property string|null $blood_group
 * @property string|null $religion
 * @property string|null $portrait_path
 * @property string|null $class_name
 * @property string|null $guardian_name
 * @property string|null $guardian_phone
 * @property string|null $emergency_contact
 * @property string|null $medical_notes
 * @property float $attendance_percent
 * @property string|null $grade_average
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolStudent extends ActiveRecord
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_GRADUATED = 'graduated';

    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';

    public const MIN_ENROLLMENT_AGE_YEARS = 2;
    public const MAX_STUDENT_AGE_YEARS = 25;

    public const RELIGION_MUSLIM = 'muslim';
    public const RELIGION_CHRISTIAN = 'christian';

    public static function tableName()
    {
        return '{{%school_student}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_GRADUATED => 'Graduated',
        ];
    }

    public static function genderOptions(): array
    {
        return [
            self::GENDER_MALE => 'Male',
            self::GENDER_FEMALE => 'Female',
        ];
    }

    public static function bloodGroupOptions(): array
    {
        return [
            'A+' => 'A+',
            'A-' => 'A-',
            'B+' => 'B+',
            'B-' => 'B-',
            'AB+' => 'AB+',
            'AB-' => 'AB-',
            'O+' => 'O+',
            'O-' => 'O-',
        ];
    }

    public static function religionOptions(): array
    {
        return [
            self::RELIGION_MUSLIM => 'Muslim',
            self::RELIGION_CHRISTIAN => 'Christian',
        ];
    }

    public static function defaultAcademicYear(): string
    {
        $year = (int)date('Y');
        $month = (int)date('n');

        return $month >= 8 ? $year . '/' . ($year + 1) : ($year - 1) . '/' . $year;
    }

    public static function hasNameParts(): bool
    {
        return self::hasColumn('first_name')
            && self::hasColumn('father_name')
            && self::hasColumn('grandfather_name')
            && self::hasColumn('family_name');
    }

    public static function composeFullName(
        ?string $firstName,
        ?string $fatherName,
        ?string $grandfatherName,
        ?string $familyName
    ): string {
        $parts = array_values(array_filter([
            trim((string)$firstName),
            trim((string)$fatherName),
            trim((string)$grandfatherName),
            trim((string)$familyName),
        ], static fn(string $part) => $part !== ''));

        return implode(' ', $parts);
    }

    public function getDisplayFullName(): string
    {
        if (self::hasNameParts()) {
            $composed = self::composeFullName(
                $this->first_name,
                $this->father_name,
                $this->grandfather_name,
                $this->family_name
            );
            if ($composed !== '') {
                return $composed;
            }
        }

        return trim((string)$this->full_name);
    }

    public function beforeValidate()
    {
        if (self::hasNameParts()) {
            $this->full_name = self::composeFullName(
                $this->first_name,
                $this->father_name,
                $this->grandfather_name,
                $this->family_name
            );
        }

        return parent::beforeValidate();
    }

    public function rules()
    {
        $rules = [
            [['full_name'], 'string', 'max' => 255],
            [['full_name', 'email', 'student_code', 'grade_average', 'nationality', 'home_address', 'phone', 'academic_year'], 'trim'],
            [['email'], 'default', 'value' => null],
            [['email'], 'email', 'skipOnEmpty' => true],
            [['email'], 'string', 'max' => 255],
            [['student_code'], 'string', 'max' => 32],
            [['student_code'], 'unique'],
            [['grade_average'], 'string', 'max' => 8],
            [['attendance_percent'], 'number', 'min' => 0, 'max' => 100],
            [['attendance_percent'], 'default', 'value' => 0],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['status'], 'in', 'range' => array_keys(self::statusOptions())],
            [['created_at', 'updated_at', 'birth_date'], 'safe'],
        ];

        if (self::hasNameParts()) {
            $rules[] = [['first_name', 'father_name', 'grandfather_name', 'family_name'], 'required'];
            $rules[] = [['first_name', 'father_name', 'grandfather_name', 'family_name'], 'trim'];
            $rules[] = [['first_name', 'father_name', 'grandfather_name', 'family_name'], 'string', 'max' => 64];
            $rules[] = [['full_name'], 'required'];
        } else {
            $rules[] = [['full_name'], 'required'];
        }

        if (self::hasColumn('school_grade_id')) {
            $rules[] = [['school_grade_id'], 'required'];
            $rules[] = [['school_grade_id'], 'integer'];
            $rules[] = [['school_grade_id'], 'exist', 'targetClass' => SchoolGrade::class, 'targetAttribute' => ['school_grade_id' => 'id']];
        }

        if (self::hasColumn('user_id')) {
            $rules[] = [['user_id'], 'integer'];
            $rules[] = [['user_id'], 'default', 'value' => null];
            $rules[] = [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id'], 'skipOnEmpty' => true];
        }

        if (self::hasColumn('school_section_id')) {
            $rules[] = [['school_section_id'], 'integer'];
            $rules[] = [['school_section_id'], 'default', 'value' => null];
            $rules[] = [['school_section_id'], 'exist', 'targetClass' => SchoolSection::class, 'targetAttribute' => ['school_section_id' => 'id'], 'skipOnEmpty' => true];
            if (!self::hasColumn('school_grade_id')) {
                $rules[] = [['school_section_id'], 'required'];
            }
        }

        if (self::hasColumn('gender')) {
            $rules[] = [['gender'], 'required'];
            $rules[] = [['gender'], 'in', 'range' => array_keys(self::genderOptions())];
            $rules[] = [['gender'], 'string', 'max' => 16];
        }
        if (self::hasColumn('nationality')) {
            $rules[] = [['nationality'], 'required'];
            $rules[] = [['nationality'], 'string', 'max' => 64];
        }
        if (self::hasColumn('home_address')) {
            $rules[] = [['home_address'], 'string', 'max' => 100];
            $rules[] = [['home_address'], 'default', 'value' => null];
        }
        if (self::hasColumn('residence_area_id')) {
            $rules[] = [['residence_area_id'], 'filter', 'filter' => static function ($value) {
                return $value === '' || $value === null ? null : (int)$value;
            }];
            $rules[] = [['residence_area_id'], 'integer'];
            $rules[] = [['residence_area_id'], 'default', 'value' => null];
            if (TransportArea::isEnabled()) {
                $rules[] = [
                    ['residence_area_id'],
                    'exist',
                    'targetClass' => TransportArea::class,
                    'targetAttribute' => ['residence_area_id' => 'id'],
                    'skipOnEmpty' => true,
                    'message' => 'Select a valid area of residence.',
                ];
            }
        }
        if (self::hasColumn('phone')) {
            $rules[] = [['phone'], 'required'];
            $rules[] = [['phone'], 'string', 'max' => 32];
            $rules[] = [['phone'], 'match', 'pattern' => '/^\d+$/', 'message' => 'Parent Phone Number must contain numbers only.'];
        }
        if (self::hasColumn('academic_year')) {
            $rules[] = [['academic_year'], 'required'];
            $rules[] = [['academic_year'], 'string', 'max' => 16];
            $rules[] = [['academic_year'], 'default', 'value' => self::defaultAcademicYear()];
        }
        if (self::hasColumn('birth_date')) {
            $rules[] = [['birth_date'], 'required'];
            $rules[] = [['birth_date'], 'date', 'format' => 'php:Y-m-d'];
            $rules[] = [['birth_date'], 'validateBirthDate'];
        }
        foreach (['blood_group', 'religion', 'portrait_path', 'class_name', 'guardian_name', 'guardian_phone', 'emergency_contact'] as $column) {
            if (self::hasColumn($column)) {
                $rules[] = [[$column], 'trim'];
            }
        }
        if (self::hasColumn('blood_group')) {
            $rules[] = [['blood_group'], 'required'];
            $rules[] = [['blood_group'], 'in', 'range' => array_keys(self::bloodGroupOptions())];
        }
        if (self::hasColumn('religion')) {
            $rules[] = [['religion'], 'required'];
            $rules[] = [['religion'], 'in', 'range' => array_keys(self::religionOptions())];
            $rules[] = [['religion'], 'string', 'max' => 64];
        }
        if (self::hasColumn('portrait_path')) {
            $rules[] = [['portrait_path'], 'string', 'max' => 255];
        }
        if (self::hasColumn('class_name')) {
            $rules[] = [['class_name'], 'string', 'max' => 128];
        }
        if (self::hasColumn('guardian_name')) {
            $rules[] = [['guardian_name'], 'required'];
            $rules[] = [['guardian_name'], 'string', 'max' => 255];
        }
        if (self::hasColumn('guardian_phone')) {
            $rules[] = [['guardian_phone'], 'required'];
            $rules[] = [['guardian_phone'], 'string', 'max' => 32];
            $rules[] = [['guardian_phone'], 'match', 'pattern' => '/^\d+$/', 'message' => 'Contact Phone must contain numbers only.'];
        }
        if (self::hasColumn('emergency_contact')) {
            $rules[] = [['emergency_contact'], 'required'];
            $rules[] = [['emergency_contact'], 'string', 'max' => 32];
            $rules[] = [['emergency_contact'], 'match', 'pattern' => '/^\d+$/', 'message' => 'Emergency Contact must contain numbers only.'];
        }
        if (self::hasColumn('medical_notes')) {
            $rules[] = [['medical_notes'], 'trim'];
            $rules[] = [['medical_notes'], 'required'];
            $rules[] = [['medical_notes'], 'string', 'max' => 250];
        }

        return $rules;
    }

    public static function hasColumn(string $column): bool
    {
        $schema = \Yii::$app->db->schema->getTableSchema(static::tableName(), true);

        return $schema !== null && isset($schema->columns[$column]);
    }

    public function attributeLabels()
    {
        return [
            'school_section_id' => 'Class / Section',
            'school_grade_id' => 'Grade Level',
            'user_id' => 'Portal Account',
            'student_code' => 'Registration #',
            'full_name' => 'Full Name',
            'first_name' => 'First Name',
            'father_name' => 'Father Name',
            'grandfather_name' => 'Grandfather Name',
            'family_name' => 'Family Name',
            'email' => 'Contact Email',
            'gender' => 'Gender',
            'nationality' => 'Nationality',
            'birth_date' => 'Birth Date',
            'home_address' => 'Address Details',
            'residence_area_id' => 'Area of Residence',
            'login_password_plain' => 'Portal Password',
            'phone' => 'Parent Phone Number',
            'academic_year' => 'Academic Year',
            'blood_group' => 'Blood Group',
            'religion' => 'Religion',
            'class_name' => 'Class',
            'guardian_name' => 'Guardian Name',
            'guardian_phone' => 'Contact Phone',
            'emergency_contact' => 'Emergency Contact',
            'medical_notes' => 'Medical Notes / Allergies',
            'attendance_percent' => 'Attendance %',
            'grade_average' => 'Grade Average',
            'status' => 'Status',
        ];
    }

    public function getResidenceArea()
    {
        return $this->hasOne(TransportArea::class, ['id' => 'residence_area_id']);
    }

    public function getSection()
    {
        return $this->hasOne(SchoolSection::class, ['id' => 'school_section_id']);
    }

    public function getGrade()
    {
        return $this->hasOne(SchoolGrade::class, ['id' => 'school_grade_id']);
    }

    public function getHealthFile()
    {
        return $this->hasOne(SchoolStudentHealthFile::class, ['school_student_id' => 'id']);
    }

    public function getClinicVisits()
    {
        return $this->hasMany(SchoolStudentClinicVisit::class, ['school_student_id' => 'id'])
            ->orderBy(['visit_date' => SORT_DESC, 'time_in' => SORT_DESC]);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function hasPortalAccount(): bool
    {
        return self::hasColumn('user_id') && (int)$this->user_id > 0;
    }

    public function getParentChildLink()
    {
        if (!FinanceAccountParentChild::isEnabled()) {
            return null;
        }

        return FinanceAccountParentChild::find()
            ->where(['school_student_id' => (int)$this->id])
            ->one();
    }

    public function getLinkedParentProfile(): ?FinanceAccountParentProfile
    {
        $child = $this->getParentChildLink();

        return $child instanceof FinanceAccountParentChild ? $child->profile : null;
    }

    public function getParentContactEmail(): ?string
    {
        $profile = $this->getLinkedParentProfile();
        if ($profile === null) {
            return null;
        }

        $email = trim((string)($profile->email ?? ''));

        return $email !== '' ? $email : null;
    }

    public function getGuardianContactEmail(): ?string
    {
        $parentEmail = $this->getParentContactEmail();
        if ($parentEmail !== null) {
            return $parentEmail;
        }

        $email = trim((string)($this->email ?? ''));
        if ($email === '') {
            return null;
        }

        $portalEmail = trim((string)($this->user->email ?? ''));
        if ($portalEmail !== '' && strcasecmp($email, $portalEmail) === 0) {
            return null;
        }

        return $email;
    }

    public function getSiblingLinks()
    {
        return $this->hasMany(SchoolStudentSibling::class, ['school_student_id' => 'id']);
    }

    public function getSiblings()
    {
        return $this->hasMany(self::class, ['id' => 'sibling_student_id'])
            ->viaTable('{{%school_student_sibling}}', ['school_student_id' => 'id']);
    }

    public function getPortraitUrl(): ?string
    {
        if (!self::hasColumn('portrait_path') || trim((string)$this->portrait_path) === '') {
            return null;
        }

        return \Yii::getAlias('@web') . '/' . ltrim((string)$this->portrait_path, '/');
    }

    public function getInitials(): string
    {
        if (self::hasNameParts()) {
            $first = strtoupper(substr(trim((string)$this->first_name), 0, 1));
            $family = strtoupper(substr(trim((string)$this->family_name), 0, 1));
            $initials = $first . $family;
            if ($initials !== '') {
                return $initials;
            }
        }

        $parts = preg_split('/\s+/', trim($this->getDisplayFullName())) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : strtoupper(substr(trim($this->getDisplayFullName()), 0, 1));
    }

    public function getRegistrationLabel(): string
    {
        $code = trim((string)$this->student_code);

        return $code === '' ? '—' : ('#' . ltrim($code, '#'));
    }

    public function getStatusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? ucfirst((string)$this->status);
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'bg-emerald-100 text-emerald-700',
            self::STATUS_INACTIVE => 'bg-slate-200 text-slate-700',
            self::STATUS_SUSPENDED => 'bg-amber-100 text-amber-800',
            self::STATUS_GRADUATED => 'bg-blue-100 text-blue-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public function getGenderLabel(): string
    {
        if (!$this->gender) {
            return '—';
        }

        return self::genderOptions()[$this->gender] ?? ucfirst((string)$this->gender);
    }

    public function getReligionLabel(): string
    {
        if (!$this->religion) {
            return '—';
        }

        return self::religionOptions()[$this->religion] ?? ucfirst((string)$this->religion);
    }

    public function getClassYearLabel(): string
    {
        $gradeName = trim((string)($this->grade?->name ?? $this->section?->grade?->name ?? ''));
        $sectionName = trim((string)($this->section?->name ?? ''));
        $year = trim((string)($this->academic_year ?? ''));

        $classLabel = $gradeName !== '' && $sectionName !== ''
            ? $gradeName . '-' . $sectionName
            : ($gradeName !== '' ? $gradeName : ($sectionName !== '' ? $sectionName : '—'));

        return $year !== '' ? $classLabel . ', ' . $year : $classLabel;
    }

    public function validateBirthDate(string $attribute): void
    {
        if (!self::hasColumn('birth_date')) {
            return;
        }

        $value = trim((string)$this->$attribute);
        if ($value === '') {
            return;
        }

        $dob = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($dob === false || $dob->format('Y-m-d') !== $value) {
            $this->addError($attribute, 'Birth date must be a valid date.');

            return;
        }

        $today = new \DateTimeImmutable('today');
        if ($dob > $today) {
            $this->addError($attribute, 'Birth date cannot be in the future.');

            return;
        }

        $youngestAllowed = $today->sub(new \DateInterval('P' . self::MIN_ENROLLMENT_AGE_YEARS . 'Y'));
        if ($dob > $youngestAllowed) {
            $this->addError($attribute, 'Student must be at least ' . self::MIN_ENROLLMENT_AGE_YEARS . ' years old.');

            return;
        }

        $oldestAllowed = $today->sub(new \DateInterval('P' . self::MAX_STUDENT_AGE_YEARS . 'Y'));
        if ($dob < $oldestAllowed) {
            $this->addError($attribute, 'Student age cannot exceed ' . self::MAX_STUDENT_AGE_YEARS . ' years.');
        }
    }

    public function getBirthDateObject(): ?\DateTimeImmutable
    {
        if (!self::hasColumn('birth_date')) {
            return null;
        }

        $value = trim((string)($this->birth_date ?? ''));
        if ($value === '') {
            return null;
        }

        $dob = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($dob === false || $dob->format('Y-m-d') !== $value) {
            return null;
        }

        return $dob;
    }

    public function getAge(): ?int
    {
        $dob = $this->getBirthDateObject();
        if ($dob === null) {
            return null;
        }

        $today = new \DateTimeImmutable('today');
        if ($dob > $today) {
            return null;
        }

        $age = $dob->diff($today)->y;
        if ($age < 0 || $age > self::MAX_STUDENT_AGE_YEARS) {
            return null;
        }

        return $age;
    }

    public function getAgeLabel(): string
    {
        $age = $this->getAge();

        return $age !== null ? (string)$age : '—';
    }

    public function getAgeGenderLabel(): string
    {
        $parts = [];
        $age = $this->getAge();
        if ($age !== null) {
            $parts[] = (string)$age;
        }
        if (self::hasColumn('gender') && trim((string)($this->gender ?? '')) !== '') {
            $gender = self::genderOptions()[$this->gender] ?? ucfirst((string)$this->gender);
            $parts[] = $gender;
        }

        return $parts !== [] ? implode(' / ', $parts) : '—';
    }

    public function getDepartmentLabel(): string
    {
        $department = $this->grade?->department ?? $this->section?->grade?->department;

        return trim((string)($department?->name ?? '')) ?: '—';
    }

    public function getGradeBadgeClass(): string
    {
        $grade = strtoupper(trim((string)$this->grade_average));
        if ($grade === '' || $grade === '—') {
            return 'bg-slate-100 text-slate-600';
        }
        if (str_starts_with($grade, 'A')) {
            return 'bg-blue-100 text-blue-700';
        }
        if (str_starts_with($grade, 'B')) {
            return 'bg-slate-200 text-slate-700';
        }
        if (str_starts_with($grade, 'C') || str_starts_with($grade, 'D') || str_starts_with($grade, 'F')) {
            return 'bg-red-100 text-red-700';
        }

        return 'bg-slate-100 text-slate-600';
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && trim((string)$this->student_code) === '') {
            $this->student_code = self::generateStudentCode();
        }

        if (self::hasColumn('academic_year') && trim((string)$this->academic_year) === '') {
            $this->academic_year = self::defaultAcademicYear();
        }

        $now = date('Y-m-d H:i:s');
        if ($insert && !$this->created_at) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        $this->syncSectionEnrollment();
    }

    public function afterDelete()
    {
        parent::afterDelete();
        $this->syncSectionEnrollment();
    }

    public static function generateStudentCode(): string
    {
        $year = date('Y');
        $next = (int)static::find()->where(['like', 'student_code', $year . '-%', false])->count() + 1;
        do {
            $code = sprintf('%s-%04d', $year, $next);
            $next++;
        } while (static::find()->where(['student_code' => $code])->exists());

        return $code;
    }

    private function syncSectionEnrollment(): void
    {
        if (!SchoolSection::isEnabled()) {
            return;
        }

        $sectionId = (int)$this->school_section_id;
        if ($sectionId <= 0) {
            return;
        }

        $section = SchoolSection::findOne(['id' => $sectionId]);
        if ($section === null) {
            return;
        }

        $count = (int)static::find()->where(['school_section_id' => $sectionId])->count();
        $section->current_enrollment = $count;
        $section->save(false, ['current_enrollment', 'updated_at']);
    }
}
