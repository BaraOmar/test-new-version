<?php

namespace common\modules\employee\services;

use common\models\User;
use common\modules\employee\models\Employee;
use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolSectionAttendanceSession;
use common\modules\employee\models\SchoolStudent;
use common\modules\employee\models\SchoolStudentAttendanceRecord;
use common\modules\employee\models\SchoolSectionTimetableRow;
use Yii;
use yii\data\Pagination;
use yii\db\Transaction;

class SchoolStudentAttendanceService
{
    public static function isEnabled(): bool
    {
        return SchoolSectionAttendanceSession::isEnabled()
            && SchoolStudentAttendanceRecord::isEnabled();
    }

    public static function todayDate(): string
    {
        return date('Y-m-d');
    }

    /**
     * @return SchoolDepartment[]
     */
    public function findGatewayDepartments(User $user, array $departmentIds): array
    {
        if ($departmentIds === [] || !SchoolDepartment::isEnabled()) {
            return [];
        }

        return SchoolDepartment::find()
            ->with([
                'grades' => static function ($query) {
                    $query->orderBy(['name' => SORT_ASC]);
                },
                'grades.sections' => static function ($query) {
                    $query->with(['leadTeacher'])->orderBy(['name' => SORT_ASC]);
                },
            ])
            ->where(['id' => $departmentIds])
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildGatewaySectionSummaries(array $sectionIds, string $date): array
    {
        if ($sectionIds === [] || !self::isEnabled()) {
            return [];
        }

        $sessions = SchoolSectionAttendanceSession::find()
            ->where([
                'school_section_id' => $sectionIds,
                'attendance_date' => $date,
            ])
            ->indexBy('school_section_id')
            ->all();

        $summaries = [];
        foreach ($sectionIds as $sectionId) {
            $sectionId = (int)$sectionId;
            $session = $sessions[$sectionId] ?? null;
            $summaries[$sectionId] = $this->buildSectionAttendanceSummary($session);
        }

        return $summaries;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSectionAttendanceSummary(?SchoolSectionAttendanceSession $session): array
    {
        if ($session === null) {
            return [
                'logged' => false,
                'taken_at' => null,
                'taken_at_label' => null,
                'status_label' => 'Pending Today',
                'status_class' => 'text-red-600 bg-red-50 border-red-100',
            ];
        }

        $takenAt = trim((string)($session->taken_at ?? ''));
        $label = $takenAt !== '' ? $this->formatSessionTimestamp($takenAt) : 'Logged';

        return [
            'logged' => true,
            'taken_at' => $takenAt,
            'taken_at_label' => $label,
            'status_label' => $label,
            'status_class' => 'text-emerald-700 bg-emerald-50 border-emerald-100',
        ];
    }

    public function findSection(int $sectionId): ?SchoolSection
    {
        if ($sectionId <= 0 || !SchoolSection::isEnabled()) {
            return null;
        }

        return SchoolSection::find()
            ->with(['grade.department', 'leadTeacher'])
            ->where(['id' => $sectionId])
            ->one();
    }

    /**
     * @return SchoolStudent[]
     */
    public function findActiveStudentsForSection(SchoolSection $section): array
    {
        if (!SchoolStudent::isEnabled()) {
            return [];
        }

        $students = SchoolStudent::find()
            ->where([
                'school_section_id' => (int)$section->id,
                'status' => SchoolStudent::STATUS_ACTIVE,
            ])
            ->orderBy(['full_name' => SORT_ASC, 'student_code' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        usort(
            $students,
            static function (SchoolStudent $a, SchoolStudent $b): int {
                $byName = strcasecmp((string)$a->full_name, (string)$b->full_name);
                if ($byName !== 0) {
                    return $byName;
                }
                $byCode = strcmp((string)$a->student_code, (string)$b->student_code);
                if ($byCode !== 0) {
                    return $byCode;
                }

                return (int)$a->id <=> (int)$b->id;
            }
        );

        return $students;
    }

    public function findSession(int $sectionId, string $date): ?SchoolSectionAttendanceSession
    {
        if ($sectionId <= 0 || !self::isEnabled()) {
            return null;
        }

        return SchoolSectionAttendanceSession::find()
            ->with(['records.student', 'takenByUser'])
            ->where([
                'school_section_id' => $sectionId,
                'attendance_date' => $date,
            ])
            ->one();
    }

    /**
     * @return array<int, array{status:string,note:string}>
     */
    public function buildStudentRecordMap(?SchoolSectionAttendanceSession $session, array $students): array
    {
        $recordsByStudent = [];
        if ($session !== null) {
            foreach ($session->records as $record) {
                $recordsByStudent[(int)$record->school_student_id] = [
                    'status' => (string)$record->status,
                    'note' => trim((string)($record->note ?? '')),
                ];
            }
        }

        $map = [];
        foreach ($students as $student) {
            $studentId = (int)$student->id;
            $map[$studentId] = $recordsByStudent[$studentId] ?? [
                'status' => SchoolStudentAttendanceRecord::STATUS_PRESENT,
                'note' => '',
            ];
        }

        return $map;
    }

    /**
     * @param array<int, array{status?:string,note?:string}> $submittedRecords
     * @return array{success:bool,message:string,session:SchoolSectionAttendanceSession|null}
     */
    public function saveSessionAttendance(
        SchoolSection $section,
        string $date,
        array $submittedRecords,
        User $user,
        array $students
    ): array {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Student attendance is not available.', 'session' => null];
        }

        $date = trim($date);
        if ($date === '') {
            return ['success' => false, 'message' => 'Attendance date is required.', 'session' => null];
        }

        $normalizedDate = $this->normalizeDate($date);
        if ($normalizedDate === null) {
            return ['success' => false, 'message' => 'Attendance date is required.', 'session' => null];
        }

        $existingSession = SchoolSectionAttendanceSession::find()
            ->where([
                'school_section_id' => (int)$section->id,
                'attendance_date' => $normalizedDate,
            ])
            ->one();
        $dateValidation = $this->validateAttendanceDate($normalizedDate, $existingSession !== null);
        if (!$dateValidation['valid']) {
            return ['success' => false, 'message' => $dateValidation['message'], 'session' => null];
        }
        $date = (string)$dateValidation['date'];

        $lines = $this->normalizeSubmittedAttendanceLines($submittedRecords);
        if ($lines === []) {
            return ['success' => false, 'message' => 'At least one student attendance row is required.', 'session' => null];
        }

        $studentIds = array_map(static fn(SchoolStudent $student): int => (int)$student->id, $students);
        $studentIdLookup = array_fill_keys($studentIds, true);
        $validStatuses = SchoolStudentAttendanceRecord::statusOptions();

        foreach ($lines as $line) {
            if (!isset($studentIdLookup[$line['student_id']])) {
                return ['success' => false, 'message' => 'Student is not an active member of this section.', 'session' => null];
            }
            if (!isset($validStatuses[$line['status']])) {
                return ['success' => false, 'message' => 'Invalid attendance status.', 'session' => null];
            }
        }

        $transaction = Yii::$app->db->beginTransaction(Transaction::SERIALIZABLE);
        try {
            $session = SchoolSectionAttendanceSession::find()
                ->where([
                    'school_section_id' => (int)$section->id,
                    'attendance_date' => $date,
                ])
                ->one();

            if ($session === null) {
                $session = new SchoolSectionAttendanceSession([
                    'school_section_id' => (int)$section->id,
                    'attendance_date' => $date,
                ]);
            }

            $session->taken_by_user_id = (int)$user->id;
            $session->taken_at = date('Y-m-d H:i:s');
            if (!$session->save()) {
                $transaction->rollBack();

                return [
                    'success' => false,
                    'message' => 'Unable to save attendance session.',
                    'session' => null,
                ];
            }

            $existingRecords = SchoolStudentAttendanceRecord::find()
                ->where(['session_id' => (int)$session->id])
                ->indexBy('school_student_id')
                ->all();

            $touchedStudentIds = [];
            foreach ($lines as $line) {
                $studentId = $line['student_id'];
                $record = $existingRecords[$studentId] ?? new SchoolStudentAttendanceRecord([
                    'session_id' => (int)$session->id,
                    'school_student_id' => $studentId,
                ]);
                $record->status = $line['status'];
                $record->note = $line['notes'] !== '' ? $line['notes'] : null;
                $record->school_section_id = (int)$section->id;

                if (!$record->save()) {
                    $transaction->rollBack();

                    return [
                        'success' => false,
                        'message' => 'Unable to save one or more attendance records.',
                        'session' => null,
                    ];
                }

                $touchedStudentIds[] = $studentId;
            }

            foreach (array_values(array_unique($touchedStudentIds)) as $studentId) {
                $this->recalculateStudentAttendancePercent($studentId);
            }

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Attendance saved successfully.',
                'session' => $session,
            ];
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            Yii::error($exception->getMessage(), __METHOD__);

            return [
                'success' => false,
                'message' => 'Unable to save attendance right now.',
                'session' => null,
            ];
        }
    }

    /**
     * @param array<int|string, mixed> $submittedRecords
     * @return array<int, array{student_id:int,status:string,notes:string}>
     */
    private function normalizeSubmittedAttendanceLines(array $submittedRecords): array
    {
        $lines = [];
        $seen = [];
        foreach ($submittedRecords as $key => $payload) {
            if (!is_array($payload)) {
                continue;
            }
            $studentId = (int)($payload['student_id'] ?? $key);
            if ($studentId <= 0 || isset($seen[$studentId])) {
                continue;
            }
            $seen[$studentId] = true;
            $status = trim((string)($payload['status'] ?? SchoolStudentAttendanceRecord::STATUS_PRESENT));
            $notes = trim((string)($payload['notes'] ?? $payload['note'] ?? ''));
            $lines[] = [
                'student_id' => $studentId,
                'status' => $status !== '' ? $status : SchoolStudentAttendanceRecord::STATUS_PRESENT,
                'notes' => $notes,
            ];
        }

        return $lines;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildHistoryRows(SchoolSection $section, int $limit = 60): array
    {
        if (!self::isEnabled()) {
            return [];
        }

        $sessions = SchoolSectionAttendanceSession::find()
            ->with(['records', 'takenByUser'])
            ->where(['school_section_id' => (int)$section->id])
            ->orderBy(['attendance_date' => SORT_DESC, 'taken_at' => SORT_DESC])
            ->limit($limit)
            ->all();

        $rows = [];
        foreach ($sessions as $session) {
            $counts = $this->countStatuses($session->records);
            $rows[] = [
                'session_id' => (int)$session->id,
                'attendance_date' => (string)$session->attendance_date,
                'attendance_date_label' => $this->formatDisplayDate((string)$session->attendance_date),
                'taken_at_label' => $this->formatSessionTimestamp((string)($session->taken_at ?? '')),
                'taken_by_name' => $this->resolveTakenByName($session),
                'counts' => $counts,
                'attendance_percent' => $this->calculateAttendancePercent($counts),
            ];
        }

        return $rows;
    }

    /**
     * @param SchoolStudentAttendanceRecord[] $records
     * @return array{present:int,absent:int,late:int,excused:int,total:int}
     */
    public function countStatuses(array $records): array
    {
        $counts = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
            'total' => 0,
        ];

        foreach ($records as $record) {
            $status = (string)$record->status;
            if (!isset($counts[$status])) {
                continue;
            }
            $counts[$status]++;
            $counts['total']++;
        }

        return $counts;
    }

    public function calculateAttendancePercent(array $counts): float
    {
        $total = (int)($counts['total'] ?? 0);
        if ($total <= 0) {
            return 0.0;
        }

        $attended = (int)($counts['present'] ?? 0)
            + (int)($counts['late'] ?? 0);

        return round(($attended / $total) * 100, 1);
    }

    /**
     * @return array{valid:bool,date:?string,message:string}
     */
    public function validateAttendanceDate(string $date, bool $forEdit = false): array
    {
        if (trim($date) === '') {
            return [
                'valid' => false,
                'date' => null,
                'message' => 'Attendance date is required.',
            ];
        }

        $normalized = $this->normalizeDate($date);
        if ($normalized === null) {
            return [
                'valid' => false,
                'date' => null,
                'message' => 'Attendance date is required.',
            ];
        }

        $today = self::todayDate();
        if ($normalized > $today) {
            return [
                'valid' => false,
                'date' => $normalized,
                'message' => 'Future attendance dates are not allowed.',
            ];
        }

        if (!$forEdit && $normalized !== $today) {
            return [
                'valid' => false,
                'date' => $normalized,
                'message' => 'Attendance can only be taken for the current date.',
            ];
        }

        return [
            'valid' => true,
            'date' => $normalized,
            'message' => '',
        ];
    }

    /**
     * @return array{present:int,absent:int,late:int,excused:int,total:int}
     */
    public function emptyHistorySummary(): array
    {
        return [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
            'total' => 0,
        ];
    }

    public function hasActiveHistoryFilter(array $filters): bool
    {
        if ((int)($filters['department_id'] ?? 0) > 0
            || (int)($filters['grade_id'] ?? 0) > 0
            || (int)($filters['section_id'] ?? 0) > 0
            || (int)($filters['student_id'] ?? 0) > 0
            || (int)($filters['session_id'] ?? 0) > 0) {
            return true;
        }

        if (trim((string)($filters['date_from'] ?? '')) !== ''
            || trim((string)($filters['date_to'] ?? '')) !== '') {
            return true;
        }

        $status = strtolower(trim((string)($filters['status'] ?? 'all')));

        return $status !== '' && $status !== 'all';
    }

    /**
     * @param int[] $sectionIds
     * @return array{
     *   departments: array<int, array{id:int,name:string}>,
     *   grades: array<int, array{id:int,name:string,department_id:int}>,
     *   sections: array<int, array{id:int,name:string,grade_id:int,department_id:int,label:string}>,
     *   students: array<int, array{id:int,name:string,student_code:string,section_id:int}>,
     *   statuses: array<int, array{key:string,label:string}>
     * }
     */
    public function buildHistoryFilterOptions(array $sectionIds): array
    {
        if ($sectionIds === [] || !SchoolSection::isEnabled()) {
            return [
                'departments' => [],
                'grades' => [],
                'sections' => [],
                'students' => [],
                'statuses' => $this->historyStatusFilterOptions(),
            ];
        }

        $sections = SchoolSection::find()
            ->alias('section')
            ->with(['grade.department'])
            ->where(['section.id' => $sectionIds])
            ->orderBy(['section.name' => SORT_ASC])
            ->all();

        $departments = [];
        $grades = [];
        $sectionOptions = [];
        $sectionIdLookup = [];
        foreach ($sections as $section) {
            $sectionId = (int)$section->id;
            $sectionIdLookup[$sectionId] = true;
            $grade = $section->grade;
            $gradeId = (int)($grade?->id ?? 0);
            $department = $grade?->department;
            $departmentId = (int)($department?->id ?? 0);
            $gradeName = trim((string)($grade?->name ?? 'Grade'));
            $sectionName = trim((string)$section->name);
            if ($departmentId > 0) {
                $departments[$departmentId] = [
                    'id' => $departmentId,
                    'name' => trim((string)$department->name),
                ];
            }
            if ($gradeId > 0) {
                $grades[$gradeId] = [
                    'id' => $gradeId,
                    'name' => $gradeName,
                    'department_id' => $departmentId,
                ];
            }
            $sectionOptions[$sectionId] = [
                'id' => $sectionId,
                'name' => $sectionName,
                'grade_id' => $gradeId,
                'department_id' => $departmentId,
                'label' => $gradeName . ' · ' . $sectionName,
            ];
        }

        uasort($departments, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));
        uasort($grades, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));
        uasort($sectionOptions, static fn(array $a, array $b): int => strcmp($a['label'], $b['label']));

        $students = [];
        if (SchoolStudent::isEnabled()) {
            $activeStudents = SchoolStudent::find()
                ->where([
                    'school_section_id' => array_keys($sectionIdLookup),
                    'status' => SchoolStudent::STATUS_ACTIVE,
                ])
                ->orderBy(['full_name' => SORT_ASC, 'student_code' => SORT_ASC])
                ->all();
            foreach ($activeStudents as $student) {
                $students[] = [
                    'id' => (int)$student->id,
                    'name' => trim((string)$student->full_name),
                    'student_code' => trim((string)$student->student_code),
                    'section_id' => (int)$student->school_section_id,
                ];
            }
        }

        return [
            'departments' => array_values($departments),
            'grades' => array_values($grades),
            'sections' => array_values($sectionOptions),
            'students' => $students,
            'statuses' => $this->historyStatusFilterOptions(),
        ];
    }

    /**
     * @return array<int, array{key:string,label:string}>
     */
    private function historyStatusFilterOptions(): array
    {
        $options = [['key' => 'all', 'label' => 'All']];
        foreach (SchoolStudentAttendanceRecord::statusOptions() as $key => $label) {
            $options[] = ['key' => $key, 'label' => $label];
        }

        return $options;
    }

    /**
     * @param int[] $sectionIds
     * @return array{rows: array<int, array<string, mixed>>, summary: array{present:int,absent:int,late:int,excused:int,total:int}}
     */
    public function searchAttendanceHistory(array $sectionIds, array $filters): array
    {
        $summary = $this->emptyHistorySummary();
        if ($sectionIds === [] || !self::isEnabled() || !$this->hasActiveHistoryFilter($filters)) {
            return ['rows' => [], 'summary' => $summary];
        }

        $allowedSectionIds = array_fill_keys(array_map('intval', $sectionIds), true);
        $sectionFilter = (int)($filters['section_id'] ?? 0);
        if ($sectionFilter > 0) {
            if (!isset($allowedSectionIds[$sectionFilter])) {
                return ['rows' => [], 'summary' => $summary];
            }
            $allowedSectionIds = [$sectionFilter => true];
        }

        $query = SchoolSectionAttendanceSession::find()
            ->alias('session')
            ->with(['records.student', 'records.section', 'section.grade.department', 'takenByUser'])
            ->innerJoin(['section' => SchoolSection::tableName()], 'section.id = session.school_section_id')
            ->innerJoin(['grade' => SchoolGrade::tableName()], 'grade.id = section.school_grade_id')
            ->where(['session.school_section_id' => array_keys($allowedSectionIds)]);

        $departmentFilter = (int)($filters['department_id'] ?? 0);
        if ($departmentFilter > 0) {
            $query->andWhere(['grade.school_department_id' => $departmentFilter]);
        }

        $gradeFilter = (int)($filters['grade_id'] ?? 0);
        if ($gradeFilter > 0) {
            $query->andWhere(['section.school_grade_id' => $gradeFilter]);
        }

        $sessionFilter = (int)($filters['session_id'] ?? 0);
        if ($sessionFilter > 0) {
            $query->andWhere(['session.id' => $sessionFilter]);
        }

        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $normalizedFrom = $this->normalizeDate($dateFrom);
            if ($normalizedFrom !== null) {
                $query->andWhere(['>=', 'session.attendance_date', $normalizedFrom]);
            }
        }

        $dateTo = trim((string)($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $normalizedTo = $this->normalizeDate($dateTo);
            if ($normalizedTo !== null) {
                $query->andWhere(['<=', 'session.attendance_date', $normalizedTo]);
            }
        }

        /** @var SchoolSectionAttendanceSession[] $sessions */
        $sessions = $query
            ->orderBy(['session.attendance_date' => SORT_DESC, 'session.school_section_id' => SORT_ASC])
            ->all();

        $studentFilter = (int)($filters['student_id'] ?? 0);
        $statusFilter = strtolower(trim((string)($filters['status'] ?? 'all')));
        $rosterBySection = [];
        $rows = [];

        foreach ($sessions as $session) {
            $section = $session->section;
            if ($section === null) {
                continue;
            }

            if (!isset($rosterBySection[(int)$section->id])) {
                $rosterStudents = $this->findActiveStudentsForSection($section);
                usort(
                    $rosterStudents,
                    static fn(SchoolStudent $a, SchoolStudent $b): int => strcmp(
                        mb_strtolower((string)$a->full_name),
                        mb_strtolower((string)$b->full_name)
                    )
                );
                $rosterBySection[(int)$section->id] = [];
                foreach ($rosterStudents as $index => $rosterStudent) {
                    $rosterBySection[(int)$section->id][(int)$rosterStudent->id] = $index + 1;
                }
            }

            foreach ($session->records as $record) {
                $student = $record->student;
                if ($student === null) {
                    continue;
                }

                if ($studentFilter > 0 && (int)$student->id !== $studentFilter) {
                    continue;
                }

                $status = (string)$record->status;
                if ($statusFilter !== '' && $statusFilter !== 'all' && $status !== $statusFilter) {
                    continue;
                }

                if (isset($summary[$status])) {
                    $summary[$status]++;
                }
                $summary['total']++;

                $grade = $section->grade;
                $department = $grade?->department;
                $snapshotSection = $record->section;
                $rows[] = [
                    'line_id' => (int)$record->id,
                    'session_id' => (int)$session->id,
                    'attendance_date' => (string)$session->attendance_date,
                    'attendance_date_label' => $this->formatDisplayDate((string)$session->attendance_date),
                    'student_id' => (int)$student->id,
                    'section_student_no' => $rosterBySection[(int)$section->id][(int)$student->id] ?? '',
                    'student_name' => trim((string)$student->full_name),
                    'student_code' => trim((string)$student->student_code),
                    'department_name' => trim((string)($department?->name ?? '')),
                    'grade_name' => trim((string)($grade?->name ?? '')),
                    'section_name' => trim((string)($snapshotSection?->name ?? $section->name)),
                    'section_id' => (int)($snapshotSection?->id ?? $section->id),
                    'status' => $status,
                    'status_label' => SchoolStudentAttendanceRecord::statusOptions()[$status] ?? ucfirst($status),
                    'note' => trim((string)($record->note ?? '')),
                    'notes' => trim((string)($record->note ?? '')),
                    'recorded_by' => $this->resolveTakenByName($session),
                    'recorded_at' => (string)($session->taken_at ?? ''),
                ];
            }
        }

        return ['rows' => $rows, 'summary' => $summary];
    }

    public function recalculateStudentAttendancePercent(int $studentId): void
    {
        if ($studentId <= 0 || !SchoolStudent::isEnabled() || !self::isEnabled()) {
            return;
        }

        $student = SchoolStudent::findOne($studentId);
        if ($student === null) {
            return;
        }

        $records = SchoolStudentAttendanceRecord::find()
            ->alias('record')
            ->innerJoin(
                ['session' => SchoolSectionAttendanceSession::tableName()],
                'session.id = record.session_id'
            )
            ->where(['record.school_student_id' => $studentId])
            ->all();

        if ($records === []) {
            return;
        }

        $counts = $this->countStatuses($records);
        $student->attendance_percent = $this->calculateAttendancePercent($counts);
        $student->save(false, ['attendance_percent', 'updated_at']);
    }

    public function normalizeDate(string $date): ?string
    {
        $date = trim($date);
        if ($date === '') {
            return self::todayDate();
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    public function formatDisplayDate(string $date): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        return date('M j, Y', $timestamp);
    }

    public function formatChipDate(string $date): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        return date('d/m/Y', $timestamp);
    }

    public function classTeacherName(SchoolSection $section): string
    {
        $name = trim((string)($section->leadTeacher?->full_name ?? ''));

        return $name !== '' ? $name : 'Unassigned';
    }

    /**
     * @return array{
     *   summary: array{present:int,absent:int,late:int,excused:int,total_days:int,attended_days:int,attendance_percentage:float},
     *   rows: array<int, array<string, mixed>>
     * }
     */
    public function buildMyAttendancePage(SchoolStudent $student): array
    {
        $summary = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
            'total_days' => 0,
            'attended_days' => 0,
            'attendance_percentage' => 0.0,
        ];

        if (!self::isEnabled()) {
            return ['summary' => $summary, 'rows' => []];
        }

        $records = SchoolStudentAttendanceRecord::find()
            ->alias('record')
            ->innerJoin(
                ['session' => SchoolSectionAttendanceSession::tableName()],
                'session.id = record.session_id'
            )
            ->with(['session.section'])
            ->where(['record.school_student_id' => (int)$student->id])
            ->orderBy(['session.id' => SORT_DESC, 'record.id' => SORT_DESC])
            ->all();

        $rows = [];
        foreach ($records as $record) {
            $session = $record->session;
            $status = (string)$record->status;
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
            $summary['total_days']++;
            $rows[] = [
                'attendance_date' => (string)($session?->attendance_date ?? ''),
                'status' => $status,
                'section_name' => trim((string)($session?->section?->name ?? '')),
                'notes' => trim((string)($record->note ?? '')),
            ];
        }

        $attended = (int)$summary['present'] + (int)$summary['late'];
        $total = (int)$summary['total_days'];
        $summary['attended_days'] = $attended;
        $summary['attendance_percentage'] = $total > 0 ? round(($attended / $total) * 100, 1) : 0.0;

        return ['summary' => $summary, 'rows' => $rows];
    }

    public function formatSessionTimestamp(string $timestamp): string
    {
        $value = strtotime($timestamp);
        if ($value === false) {
            return $timestamp;
        }

        $today = self::todayDate();
        $datePart = date('Y-m-d', $value);
        $timePart = date('g:i A', $value);

        if ($datePart === $today) {
            return 'Today, ' . $timePart;
        }

        return date('M j, Y g:i A', $value);
    }

    public function defaultLogStartDate(): string
    {
        return date('Y-m-01');
    }

    public function defaultLogEndDate(): string
    {
        return self::todayDate();
    }

    /**
     * @return array{
     *   summary: array{percent:float,present:int,absent:int,late:int,excused:int,total:int},
     *   rows: array<int, array<string, mixed>>,
     *   pagination: Pagination
     * }
     */
    public function buildStudentAttendanceLogData(
        SchoolStudent $student,
        string $startDate,
        string $endDate,
        int $page = 1,
        int $pageSize = 10
    ): array {
        $startDate = $this->normalizeDate($startDate) ?? $this->defaultLogStartDate();
        $endDate = $this->normalizeDate($endDate) ?? $this->defaultLogEndDate();
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $baseQuery = SchoolStudentAttendanceRecord::find()
            ->alias('record')
            ->innerJoin(
                ['session' => SchoolSectionAttendanceSession::tableName()],
                'session.id = record.session_id'
            )
            ->where(['record.school_student_id' => (int)$student->id])
            ->andWhere(['between', 'session.attendance_date', $startDate, $endDate]);

        $summaryRecords = (clone $baseQuery)
            ->with(['session'])
            ->orderBy(['session.attendance_date' => SORT_ASC])
            ->all();

        $summaryCounts = $this->countStatuses($summaryRecords);
        $summary = [
            'percent' => $this->calculateAttendancePercent($summaryCounts),
            'present' => (int)$summaryCounts['present'],
            'absent' => (int)$summaryCounts['absent'],
            'late' => (int)$summaryCounts['late'],
            'excused' => (int)$summaryCounts['excused'],
            'total' => (int)$summaryCounts['total'],
        ];

        $totalCount = (int)(clone $baseQuery)->count();
        $pagination = new Pagination([
            'totalCount' => $totalCount,
            'pageSize' => max(1, $pageSize),
            'pageParam' => 'page',
        ]);
        if ($page > 0) {
            // URL page numbers are 1-based; Pagination uses a 0-based index.
            $pagination->setPage(max(0, $page - 1));
        }

        /** @var SchoolStudentAttendanceRecord[] $records */
        $records = (clone $baseQuery)
            ->with(['session'])
            ->orderBy(['session.attendance_date' => SORT_DESC, 'record.id' => SORT_DESC])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $rows = [];
        foreach ($records as $record) {
            $session = $record->session;
            $attendanceDate = (string)($session?->attendance_date ?? '');
            $dayOfWeek = $attendanceDate !== '' ? (int)date('N', strtotime($attendanceDate)) : 0;
            $status = (string)$record->status;
            $note = trim((string)($record->note ?? ''));
            $takenAt = trim((string)($session?->taken_at ?? ''));

            $rows[] = [
                'attendance_date' => $attendanceDate,
                'date_label' => $this->formatDisplayDate($attendanceDate),
                'day_label' => SchoolSectionTimetableRow::DAY_NAMES[$dayOfWeek] ?? '—',
                'status' => $status,
                'status_label' => SchoolStudentAttendanceRecord::statusOptions()[$status] ?? ucfirst($status),
                'time_in_label' => $this->formatStudentTimeInLabel($status, $takenAt),
                'remarks' => $this->formatStudentRemarks($status, $note),
            ];
        }

        return [
            'summary' => $summary,
            'rows' => $rows,
            'pagination' => $pagination,
        ];
    }

    public function formatStudentTimeInLabel(string $status, string $takenAt): string
    {
        if (!in_array($status, [SchoolStudentAttendanceRecord::STATUS_PRESENT, SchoolStudentAttendanceRecord::STATUS_LATE], true)) {
            return '--:-- --';
        }

        if ($takenAt === '') {
            return '--:-- --';
        }

        $timestamp = strtotime($takenAt);
        if ($timestamp === false) {
            return '--:-- --';
        }

        return date('g:i A', $timestamp);
    }

    public function formatStudentRemarks(string $status, string $note): string
    {
        if ($note !== '') {
            return $note;
        }

        return match ($status) {
            SchoolStudentAttendanceRecord::STATUS_PRESENT => 'On time',
            SchoolStudentAttendanceRecord::STATUS_LATE => 'Late arrival',
            SchoolStudentAttendanceRecord::STATUS_ABSENT => 'No log entry',
            SchoolStudentAttendanceRecord::STATUS_EXCUSED => 'Excused absence',
            default => '—',
        };
    }

    private function resolveTakenByName(SchoolSectionAttendanceSession $session): string
    {
        $user = $session->takenByUser;
        if ($user === null) {
            return '—';
        }

        $employee = Employee::find()->where(['user_id' => (int)$user->id])->one();
        if ($employee !== null && trim((string)$employee->full_name) !== '') {
            return trim((string)$employee->full_name);
        }

        return trim((string)($user->username ?? '')) !== '' ? trim((string)$user->username) : '—';
    }
}
