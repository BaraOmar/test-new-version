<?php

namespace common\modules\employee\services;

use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolStudent;
use common\modules\employee\models\SchoolStudentClinicVisit;
use common\modules\employee\models\SchoolStudentHealthFile;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

class SchoolStudentHealthService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function buildStudentQuery(array $filters): ActiveQuery
    {
        $search = trim((string)($filters['search'] ?? ''));
        $departmentFilter = (int)($filters['department_id'] ?? 0);
        $gradeFilter = (int)($filters['grade_id'] ?? 0);
        $sectionFilter = (int)($filters['section_id'] ?? 0);
        $allergyFilter = trim((string)($filters['allergy_status'] ?? ''));
        $riskFilter = trim((string)($filters['health_risk'] ?? ''));

        $query = SchoolStudent::find()
            ->alias('student')
            ->joinWith(['grade grade', 'section section', 'section.grade sectionGrade', 'healthFile healthFile'])
            ->orderBy(['student.full_name' => SORT_ASC]);

        if ($search !== '') {
            $searchConditions = [
                ['like', 'student.full_name', $search],
                ['like', 'student.student_code', $search],
            ];
            if (SchoolStudent::hasNameParts()) {
                $searchConditions[] = ['like', 'student.first_name', $search];
                $searchConditions[] = ['like', 'student.father_name', $search];
                $searchConditions[] = ['like', 'student.grandfather_name', $search];
                $searchConditions[] = ['like', 'student.family_name', $search];
            }
            $query->andWhere(array_merge(['or'], $searchConditions));
        }

        if ($departmentFilter > 0 && SchoolGrade::isEnabled()) {
            $query->andWhere([
                'or',
                ['grade.school_department_id' => $departmentFilter],
                ['sectionGrade.school_department_id' => $departmentFilter],
            ]);
        }

        if ($gradeFilter > 0 && SchoolStudent::hasColumn('school_grade_id')) {
            $query->andWhere([
                'or',
                ['student.school_grade_id' => $gradeFilter],
                ['section.school_grade_id' => $gradeFilter],
            ]);
        }

        if ($sectionFilter > 0) {
            $query->andWhere(['student.school_section_id' => $sectionFilter]);
        }

        if ($allergyFilter !== '' && SchoolStudentHealthFile::isEnabled()) {
            if ($allergyFilter === SchoolStudentHealthFile::ALLERGY_NONE) {
                $query->andWhere([
                    'or',
                    ['healthFile.id' => null],
                    ['healthFile.allergy_status' => SchoolStudentHealthFile::ALLERGY_NONE],
                ]);
            } else {
                $query->andWhere(['healthFile.allergy_status' => $allergyFilter]);
            }
        }

        if ($riskFilter !== '' && SchoolStudentHealthFile::isEnabled()) {
            if ($riskFilter === SchoolStudentHealthFile::RISK_NORMAL) {
                $query->andWhere([
                    'or',
                    ['healthFile.id' => null],
                    ['healthFile.health_risk' => SchoolStudentHealthFile::RISK_NORMAL],
                ]);
            } else {
                $query->andWhere(['healthFile.health_risk' => $riskFilter]);
            }
        }

        if (SchoolStudentHealthFile::isEnabled()) {
            $lastVisitFrom = $this->normalizeFilterDate((string)($filters['last_visit_from'] ?? ''));
            $lastVisitTo = $this->normalizeFilterDate((string)($filters['last_visit_to'] ?? ''));
            if ($lastVisitFrom !== null && $lastVisitTo !== null && $lastVisitFrom > $lastVisitTo) {
                [$lastVisitFrom, $lastVisitTo] = [$lastVisitTo, $lastVisitFrom];
            }
            if ($lastVisitFrom !== null) {
                $query->andWhere(['>=', 'healthFile.last_visit_date', $lastVisitFrom]);
            }
            if ($lastVisitTo !== null) {
                $query->andWhere(['<=', 'healthFile.last_visit_date', $lastVisitTo]);
            }
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function createDataProvider(array $filters, int $pageSize = 12): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => $this->buildStudentQuery($filters),
            'pagination' => [
                'pageSize' => $pageSize,
                'pageSizeParam' => 'per-page',
            ],
        ]);
    }

    /**
     * @return SchoolDepartment[]
     */
    public function findDepartments(): array
    {
        if (!SchoolDepartment::isEnabled()) {
            return [];
        }

        return SchoolDepartment::find()
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }

    /**
     * @return SchoolGrade[]
     */
    public function findGrades(int $departmentId = 0): array
    {
        if (!SchoolGrade::isEnabled()) {
            return [];
        }

        $query = SchoolGrade::find()->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC]);
        if ($departmentId > 0) {
            $query->andWhere(['school_department_id' => $departmentId]);
        }

        return $query->all();
    }

    /**
     * @return array<int, array<int, array{id:int,name:string}>>
     */
    public function buildGradesByDepartment(): array
    {
        $map = [];
        foreach (SchoolGrade::find()->orderBy(['school_department_id' => SORT_ASC, 'sort_order' => SORT_ASC, 'name' => SORT_ASC])->all() as $grade) {
            $departmentId = (int)$grade->school_department_id;
            $map[$departmentId][] = [
                'id' => (int)$grade->id,
                'name' => (string)$grade->name,
            ];
        }

        return $map;
    }

    /**
     * @return SchoolGrade[]
     */
    public function findAllGrades(): array
    {
        return $this->findGrades(0);
    }

    /**
     * @return array<int, string>
     */
    public function findSectionsForGrade(int $gradeId): array
    {
        if ($gradeId <= 0 || !SchoolSection::isEnabled()) {
            return [];
        }

        $sections = SchoolSection::find()
            ->where(['school_grade_id' => $gradeId])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        $options = [];
        foreach ($sections as $section) {
            $options[(int)$section->id] = (string)$section->name;
        }

        return $options;
    }

    /**
     * @return array<int, array<int, array{id:int,name:string}>>
     */
    public function buildSectionsByGrade(): array
    {
        $map = [];
        foreach (SchoolSection::find()->orderBy(['school_grade_id' => SORT_ASC, 'name' => SORT_ASC])->all() as $section) {
            $gradeId = (int)$section->school_grade_id;
            $map[$gradeId][] = [
                'id' => (int)$section->id,
                'name' => (string)$section->name,
            ];
        }

        return $map;
    }

    /**
     * @return SchoolStudent[]
     */
    public function findStudentsWithoutHealthFile(): array
    {
        if (!SchoolStudentHealthFile::isEnabled()) {
            return SchoolStudent::find()->orderBy(['full_name' => SORT_ASC])->all();
        }

        return SchoolStudent::find()
            ->alias('student')
            ->leftJoin(['healthFile' => SchoolStudentHealthFile::tableName()], 'healthFile.school_student_id = student.id')
            ->where(['healthFile.id' => null])
            ->orderBy(['student.full_name' => SORT_ASC])
            ->all();
    }

    public function findOrCreateHealthFile(SchoolStudent $student): SchoolStudentHealthFile
    {
        $file = $student->healthFile;
        if ($file instanceof SchoolStudentHealthFile) {
            return $file;
        }

        return new SchoolStudentHealthFile([
            'school_student_id' => (int)$student->id,
            'health_risk' => SchoolStudentHealthFile::RISK_NORMAL,
            'allergy_status' => SchoolStudentHealthFile::ALLERGY_NONE,
        ]);
    }

    /**
     * @return SchoolStudent[]
     */
    public function findStudentsForSelect(): array
    {
        return SchoolStudent::find()
            ->with(['grade', 'section', 'healthFile'])
            ->orderBy(['full_name' => SORT_ASC])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildStudentSummary(SchoolStudent $student): array
    {
        $file = $student->healthFile;
        $allergyLabel = $file?->getAllergyStatusLabel() ?? 'No Known Allergies';
        $allergyClass = ($file === null || $file->allergy_status === SchoolStudentHealthFile::ALLERGY_NONE)
            ? 'bg-slate-100 text-slate-600'
            : (($file && SchoolStudentHealthFile::hasColumn('allergy_criticality') && $file->allergy_criticality === SchoolStudentHealthFile::CRITICALITY_CRITICAL)
                ? 'bg-red-100 text-red-700'
                : 'bg-amber-100 text-amber-800');
        $risk = $file?->health_risk ?? SchoolStudentHealthFile::RISK_NORMAL;
        $alertBorder = $risk === SchoolStudentHealthFile::RISK_EMERGENCY
            ? 'border-l-red-500'
            : ($risk === SchoolStudentHealthFile::RISK_ALLERGY_WATCH ? 'border-l-amber-500' : 'border-l-sky-500');

        $chronicTags = [];
        if ($file && SchoolStudentHealthFile::hasColumn('chronic_condition_name') && trim((string)($file->chronic_condition_name ?? '')) !== '') {
            $chronicTags[] = trim((string)$file->chronic_condition_name);
        }
        foreach ($file?->getChronicConditionTags() ?? [] as $tag) {
            if (!in_array($tag, $chronicTags, true)) {
                $chronicTags[] = $tag;
            }
        }

        $emergencyName = trim((string)($student->guardian_name ?? ''));
        $emergencyPhone = trim((string)($student->guardian_phone ?? $student->emergency_contact ?? ''));
        $emergencyLabel = $emergencyName !== '' && $emergencyPhone !== ''
            ? $emergencyName . ' · ' . $emergencyPhone
            : ($emergencyName !== '' ? $emergencyName : ($emergencyPhone !== '' ? $emergencyPhone : '—'));

        return [
            'id' => (int)$student->id,
            'name' => $student->getDisplayFullName(),
            'student_code' => $student->student_code ?: ('STU-' . $student->id),
            'class_label' => $student->getClassYearLabel(),
            'allergy_label' => strtoupper($allergyLabel),
            'allergy_class' => $allergyClass,
            'chronic_tags' => $chronicTags,
            'emergency_contact' => $emergencyLabel,
            'guardian_name' => $emergencyName,
            'guardian_phone' => $emergencyPhone,
            'notify_email' => trim((string)($student->email ?? '')),
            'alert_border' => $alertBorder,
            'has_health_file' => $file !== null,
            'health_file_url' => $file ? \yii\helpers\Url::to(['/hr/student-health/view', 'id' => $file->id]) : null,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function createClinicVisitDataProvider(array $filters, int $pageSize = 20): ActiveDataProvider
    {
        $query = SchoolStudentClinicVisit::find()
            ->alias('visit')
            ->joinWith(['student student', 'student.grade grade', 'student.section section'])
            ->orderBy(['visit.visit_date' => SORT_DESC, 'visit.time_in' => SORT_DESC]);

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'student.full_name', $search],
                ['like', 'student.student_code', $search],
                ['like', 'visit.primary_reason', $search],
            ]);
        }

        $studentId = (int)($filters['student_id'] ?? 0);
        if ($studentId > 0) {
            $query->andWhere(['visit.school_student_id' => $studentId]);
        }

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $query->andWhere(['visit.status' => $status]);
        }

        $visitDateFrom = $this->normalizeFilterDate((string)($filters['date_from'] ?? ''));
        $visitDateTo = $this->normalizeFilterDate((string)($filters['date_to'] ?? ''));
        if ($visitDateFrom !== null && $visitDateTo !== null && $visitDateFrom > $visitDateTo) {
            [$visitDateFrom, $visitDateTo] = [$visitDateTo, $visitDateFrom];
        }
        if ($visitDateFrom !== null) {
            $query->andWhere(['>=', 'visit.visit_date', $visitDateFrom]);
        }
        if ($visitDateTo !== null) {
            $query->andWhere(['<=', 'visit.visit_date', $visitDateTo]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
                'pageSizeParam' => 'per-page',
            ],
        ]);
    }

    public function touchHealthFileLastVisit(SchoolStudent $student, string $visitDate): void
    {
        if (!SchoolStudentHealthFile::isEnabled()) {
            return;
        }

        $file = $this->findOrCreateHealthFile($student);
        if ($file->isNewRecord) {
            $file->save(false);
        }

        $file->last_visit_date = $visitDate;
        $file->save(false, ['last_visit_date', 'updated_at']);
    }

    public function normalizeFilterDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $value;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function normalizeDateRange(string $from, string $to): array
    {
        $from = $this->normalizeFilterDate($from) ?? '';
        $to = $this->normalizeFilterDate($to) ?? '';
        if ($from !== '' && $to !== '' && $from > $to) {
            return [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * @param array<string, mixed> $visitData
     * @return array<string, mixed>
     */
    public function buildParentNotificationContext(SchoolStudent $student, array $visitData): array
    {
        $actionTaken = (string)($visitData['action_taken'] ?? '');
        $actionLabel = SchoolStudentClinicVisit::actionTakenOptions()[$actionTaken] ?? $actionTaken;
        $visitDate = trim((string)($visitData['visit_date'] ?? date('Y-m-d')));
        $primaryReason = trim((string)($visitData['primary_reason'] ?? ''));
        $clinicalNotes = trim((string)($visitData['clinical_notes'] ?? ''));
        $guardianName = trim((string)($student->guardian_name ?? ''));
        $guardianPhone = trim((string)($student->guardian_phone ?? $student->emergency_contact ?? $student->phone ?? ''));
        $notifyEmail = trim((string)($student->email ?? ''));

        $messageLines = [
            'Dear ' . ($guardianName !== '' ? $guardianName : 'Parent/Guardian') . ',',
            '',
            'This is a clinic visit update for ' . $student->getDisplayFullName() . ' (' . ($student->student_code ?: 'STU-' . $student->id) . ').',
            '',
            'Visit date: ' . $visitDate,
            'Primary reason: ' . ($primaryReason !== '' ? $primaryReason : '—'),
            'Action taken: ' . $actionLabel,
        ];
        if ($clinicalNotes !== '') {
            $messageLines[] = '';
            $messageLines[] = 'Clinical notes:';
            $messageLines[] = $clinicalNotes;
        }
        $messageLines[] = '';
        $messageLines[] = 'Please contact the school clinic if you have any questions.';

        return [
            'student_id' => (int)$student->id,
            'student_name' => $student->getDisplayFullName(),
            'student_code' => $student->student_code ?: ('STU-' . $student->id),
            'guardian_name' => $guardianName,
            'guardian_phone' => $guardianPhone,
            'notify_email' => $notifyEmail,
            'action_taken' => $actionTaken,
            'action_label' => $actionLabel,
            'visit_date' => $visitDate,
            'primary_reason' => $primaryReason,
            'message' => implode("\n", $messageLines),
            'subject' => 'Clinic visit update — ' . $student->getDisplayFullName(),
        ];
    }

    /**
     * @param array<string, mixed> $visitData
     */
    public function sendParentClinicNotification(SchoolStudent $student, array $visitData, ?string $customMessage = null): bool
    {
        $context = $this->buildParentNotificationContext($student, $visitData);
        $email = trim((string)($context['notify_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $message = trim((string)($customMessage ?? ''));
        if ($message === '') {
            $message = (string)$context['message'];
        }

        return Yii::$app->mailer
            ->compose(
                ['html' => 'clinicVisitParentNotification-html', 'text' => 'clinicVisitParentNotification-text'],
                ['context' => $context, 'body' => $message]
            )
            ->setFrom([Yii::$app->params['supportEmail'] ?? Yii::$app->params['senderEmail'] ?? 'noreply@localhost' => Yii::$app->name])
            ->setTo($email)
            ->setSubject((string)$context['subject'])
            ->send();
    }
}
