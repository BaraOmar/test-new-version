<?php

namespace common\modules\employee\services;

use common\components\LmsPortal;
use common\models\User;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolSectionMaterial;
use common\modules\employee\models\SchoolStudent;
use common\modules\employee\models\SchoolStudentAttendanceRecord;
use common\modules\employee\models\SchoolStudentSectionMark;
use common\modules\employee\models\SchoolSubjectGrade;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class SchoolStudentCertificateService
{
    public const SCOPES = [
        'first' => ['mark_first'],
        'second' => ['mark_second'],
        'third' => ['mark_third'],
        'final' => ['mark_final'],
        'first_second' => ['mark_first', 'mark_second'],
        'first_second_third' => ['mark_first', 'mark_second', 'mark_third'],
        'all' => ['mark_first', 'mark_second', 'mark_third', 'mark_final'],
    ];

    public const SCOPE_LABELS = [
        'first' => 'First Month',
        'second' => 'Second Month',
        'third' => 'Third Month',
        'final' => 'Final Exam',
        'first_second' => 'First & Second Months',
        'first_second_third' => 'First, Second & Third Months',
        'all' => 'Full Year (First · Second · Third · Final)',
    ];

    private const FIELD_TO_TERM = [
        'mark_first' => 'max_first',
        'mark_second' => 'max_second',
        'mark_third' => 'max_third',
        'mark_final' => 'max_final',
    ];

    private ?SchoolSectionMarksService $marksService = null;

    public static function isEnabled(): bool
    {
        return SchoolStudent::isEnabled()
            && SchoolSection::isEnabled()
            && SchoolSectionMaterial::isEnabled();
    }

    public static function scopeRows(): array
    {
        $rows = [];
        foreach (array_keys(self::SCOPE_LABELS) as $key) {
            $rows[] = [
                'key' => $key,
                'label' => self::SCOPE_LABELS[$key],
            ];
        }

        return $rows;
    }

    public static function schoolName(): string
    {
        $name = trim((string)(Yii::$app->params['schoolName'] ?? ''));

        return $name !== '' ? $name : 'CultureHR';
    }

    private function marksService(): SchoolSectionMarksService
    {
        if ($this->marksService === null) {
            $this->marksService = new SchoolSectionMarksService();
        }

        return $this->marksService;
    }

    /**
     * @return array{0:string,1:string[]}
     */
    public function resolveScopeFields(?string $scope): array
    {
        $scope = strtolower(trim((string)$scope));
        if ($scope === '' || !isset(self::SCOPES[$scope])) {
            $scope = 'all';
        }

        return [$scope, self::SCOPES[$scope]];
    }

    public function ensureManageAccess(User $user): void
    {
        if (!LmsPortal::canAccessStudentCertificateManagement($user)) {
            throw new ForbiddenHttpException(
                'Only class teachers and department heads can create student certificates.'
            );
        }
    }

    public function ensureSectionAccess(User $user, int $sectionId): SchoolSection
    {
        $this->ensureManageAccess($user);
        $section = SchoolSection::find()
            ->with(['grade.department'])
            ->where(['id' => $sectionId])
            ->one();
        if ($section === null) {
            throw new NotFoundHttpException('Section not found.');
        }
        if (!in_array((int)$section->id, LmsPortal::getAccessibleSectionIdsForCertificates($user), true)) {
            throw new ForbiddenHttpException('You do not have access to certificates for this section.');
        }

        return $section;
    }

    public function ensureStudentAccess(User $user, int $studentId): SchoolStudent
    {
        $student = SchoolStudent::find()
            ->with(['section.grade.department', 'grade.department'])
            ->where(['id' => $studentId])
            ->one();
        if ($student === null) {
            throw new NotFoundHttpException('Student not found.');
        }

        if (LmsPortal::canAccessOwnStudentCertificate($user)) {
            $own = LmsPortal::findLinkedStudent($user);
            if ($own !== null && (int)$own->id === (int)$student->id) {
                return $student;
            }
        }

        if (LmsPortal::canAccessStudentCertificateManagement($user)) {
            $section = LmsPortal::resolveStudentSection($student);
            if ($section !== null
                && in_array((int)$section->id, LmsPortal::getAccessibleSectionIdsForCertificates($user), true)
            ) {
                return $student;
            }
            throw new ForbiddenHttpException('You do not have access to this student certificate.');
        }

        throw new ForbiddenHttpException('You do not have access to student certificates.');
    }

    /**
     * @return array<string, mixed>
     */
    public function getStudentCertificateOptions(User $user): array
    {
        $this->ensureManageAccess($user);
        $sectionIds = LmsPortal::getAccessibleSectionIdsForCertificates($user);
        $sections = $sectionIds === []
            ? []
            : SchoolSection::find()
                ->with(['grade.department'])
                ->where(['id' => $sectionIds])
                ->all();

        usort($sections, static function (SchoolSection $a, SchoolSection $b): int {
            $deptA = (string)($a->grade?->department?->name ?? '');
            $deptB = (string)($b->grade?->department?->name ?? '');
            $cmp = strcasecmp($deptA, $deptB);
            if ($cmp !== 0) {
                return $cmp;
            }
            $seqA = (int)($a->grade?->sort_order ?? 0);
            $seqB = (int)($b->grade?->sort_order ?? 0);
            if ($seqA !== $seqB) {
                return $seqA <=> $seqB;
            }
            $cmp = strcasecmp((string)($a->grade?->name ?? ''), (string)($b->grade?->name ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp((string)$a->name, (string)$b->name);
        });

        $departments = [];
        $grades = [];
        foreach ($sections as $section) {
            $grade = $section->grade;
            if ($grade === null) {
                continue;
            }
            $dept = $grade->department;
            if ($dept === null) {
                continue;
            }
            $departments[(int)$dept->id] = [
                'id' => (int)$dept->id,
                'name' => (string)($dept->name ?? ''),
            ];
            $gradeId = (int)$grade->id;
            if (!isset($grades[$gradeId])) {
                $grades[$gradeId] = [
                    'id' => $gradeId,
                    'name' => (string)($grade->name ?? ''),
                    'sequence' => (int)($grade->sort_order ?? 0),
                    'department_id' => (int)$dept->id,
                    'sections' => [],
                ];
            }
            $sectionName = trim((string)$section->name);
            $grades[$gradeId]['sections'][] = [
                'id' => (int)$section->id,
                'name' => $sectionName,
                'student_count' => $this->countActiveStudents((int)$section->id),
                'display_name' => $this->sectionDisplayName($sectionName),
            ];
        }

        $deptRows = array_values($departments);
        usort($deptRows, static fn(array $a, array $b): int => strcasecmp((string)$a['name'], (string)$b['name']));
        $gradeRows = array_values($grades);
        usort($gradeRows, static function (array $a, array $b): int {
            if ((int)$a['sequence'] !== (int)$b['sequence']) {
                return (int)$a['sequence'] <=> (int)$b['sequence'];
            }

            return strcasecmp((string)$a['name'], (string)$b['name']);
        });

        $defaultDepartmentId = count($deptRows) === 1 ? (int)$deptRows[0]['id'] : false;

        return [
            'ok' => true,
            'can_manage' => true,
            'departments' => $deptRows,
            'can_select_department' => count($deptRows) > 1,
            'default_department_id' => $defaultDepartmentId,
            'grades' => $gradeRows,
            'scopes' => self::scopeRows(),
            'page_title' => 'Student Certificates | LMS',
            'portal_eyebrow' => LmsPortal::attendancePortalEyebrow($user),
            'subtitle' => 'Choose a department, grade, section, and coverage period to build academic certificates.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSectionCertificateRoster(User $user, int $sectionId, ?string $scope): array
    {
        $section = $this->ensureSectionAccess($user, $sectionId);
        [$scope] = $this->resolveScopeFields($scope);
        $students = $this->findActiveStudentsForSection($section);
        $ranking = [];
        foreach ($students as $student) {
            $built = $this->buildStudentRows($student, $scope, $section);
            $ranking[] = [
                'student_id' => (int)$student->id,
                'student_name' => $student->getDisplayFullName(),
                'student_code' => (string)($student->student_code ?? ''),
                'average' => $built['average'],
                'average_label' => $built['average_label'],
                'aggregate_label' => $built['aggregate_label'],
                'aggregate_percent' => $built['aggregate_percent'],
                'scored_count' => $built['scored_count'],
                'subject_count' => $built['subject_count'],
            ];
        }

        usort($ranking, static function (array $a, array $b): int {
            $aMissing = $a['average'] === false;
            $bMissing = $b['average'] === false;
            if ($aMissing !== $bMissing) {
                return $aMissing <=> $bMissing;
            }
            if (!$aMissing) {
                $cmp = ((float)$b['average']) <=> ((float)$a['average']);
                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            return strcasecmp((string)$a['student_name'], (string)$b['student_name']);
        });

        $this->applyCompetitionRanks($ranking);

        return [
            'ok' => true,
            'section_id' => (int)$section->id,
            'section_name' => (string)($section->name ?? ''),
            'grade_name' => (string)($section->grade?->name ?? ''),
            'scope' => $scope,
            'scope_label' => self::SCOPE_LABELS[$scope] ?? $scope,
            'students' => $ranking,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getStudentCertificateData(User $user, int $studentId, ?string $scope): array
    {
        $student = $this->ensureStudentAccess($user, $studentId);
        [$scope] = $this->resolveScopeFields($scope);
        $section = LmsPortal::resolveStudentSection($student);
        $grade = $section?->grade ?? $student->grade;
        $built = $this->buildStudentRows($student, $scope, $section);
        $attendanceDays = $this->attendanceDays($student);

        $rank = false;
        $sectionSize = 0;
        if ($section !== null) {
            $mates = $this->findActiveStudentsForSection($section);
            $sectionSize = count($mates);
            $averages = [];
            foreach ($mates as $mate) {
                $avg = $this->buildStudentRows($mate, $scope, $section)['average'];
                if ($avg !== false) {
                    $averages[] = [(int)$mate->id, (float)$avg];
                }
            }
            usort($averages, static function (array $a, array $b): int {
                $cmp = $b[1] <=> $a[1];

                return $cmp !== 0 ? $cmp : ($a[0] <=> $b[0]);
            });
            $rank = $this->competitionRankForStudent($averages, (int)$student->id);
        }

        $canManage = LmsPortal::canAccessStudentCertificateManagement($user);
        $gradeName = (string)($grade?->name ?? '');
        $sectionName = (string)($section?->name ?? '');
        $classLabel = ($section !== null && $grade !== null)
            ? ($gradeName !== '' ? $gradeName : '—') . ' · Section ' . ($sectionName !== '' ? $sectionName : '—')
            : ($gradeName !== '' ? $gradeName : '—');

        return [
            'ok' => true,
            'can_manage' => $canManage,
            'read_only' => !$canManage,
            'scope' => $scope,
            'scope_label' => self::SCOPE_LABELS[$scope] ?? $scope,
            'school_name' => self::schoolName(),
            'school_tagline' => 'Excellence in Education',
            'title' => 'Academic Report Card',
            'academic_session_label' => $this->marksService()->resolveAcademicSessionLabel([$student]),
            'student' => [
                'id' => (int)$student->id,
                'name' => $student->getDisplayFullName(),
                'student_code' => (string)($student->student_code ?? ''),
                'grade_name' => $gradeName,
                'section_name' => $sectionName,
                'class_label' => $classLabel,
            ],
            'rank' => $rank,
            'section_size' => $sectionSize,
            'attendance_days' => $attendanceDays,
            'attendance_percent' => false,
            'attendance_label' => $attendanceDays === 1 ? '1 day' : ($attendanceDays . ' days'),
            'subjects' => $built['subjects'],
            'subject_count' => $built['subject_count'],
            'scored_count' => $built['scored_count'],
            'aggregate_score' => $built['aggregate_score'],
            'aggregate_max' => $built['aggregate_max'],
            'aggregate_label' => $built['aggregate_label'],
            'average' => $built['average'],
            'average_label' => $built['average_label'],
            'aggregate_percent' => $built['aggregate_percent'],
            'page_title' => 'Student Certificate | ' . ($student->getDisplayFullName() !== '' ? $student->getDisplayFullName() : 'Student'),
            'portal_eyebrow' => LmsPortal::attendancePortalEyebrow($user),
            'generated_on' => date('Y-m-d'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getMyStudentCertificatePage(User $user, ?string $scope): array
    {
        if (!LmsPortal::canAccessOwnStudentCertificate($user)) {
            throw new ForbiddenHttpException('Only students can open their own certificate here.');
        }
        $student = LmsPortal::findLinkedStudent($user);
        if ($student === null) {
            throw new ForbiddenHttpException('No student profile is linked to your account.');
        }
        $data = $this->getStudentCertificateData($user, (int)$student->id, $scope);
        $data['page_title'] = 'My Certificate | LMS';
        $data['scopes'] = self::scopeRows();

        return $data;
    }

    /**
     * @return SchoolStudent[]
     */
    private function findActiveStudentsForSection(SchoolSection $section): array
    {
        if (!SchoolStudent::isEnabled()) {
            return [];
        }

        return SchoolStudent::find()
            ->where([
                'school_section_id' => (int)$section->id,
                'status' => SchoolStudent::STATUS_ACTIVE,
            ])
            ->orderBy(['full_name' => SORT_ASC])
            ->all();
    }

    private function countActiveStudents(int $sectionId): int
    {
        if (!SchoolStudent::isEnabled()) {
            return 0;
        }

        return (int)SchoolStudent::find()
            ->where([
                'school_section_id' => $sectionId,
                'status' => SchoolStudent::STATUS_ACTIVE,
            ])
            ->count();
    }

    private function sectionDisplayName(string $name): string
    {
        if ($name !== '' && !str_starts_with(strtolower($name), 'section')) {
            return 'Section ' . $name;
        }

        return $name !== '' ? $name : 'Section';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStudentRows(SchoolStudent $student, string $scope, ?SchoolSection $section): array
    {
        [, $fields] = $this->resolveScopeFields($scope);
        $materials = $this->activeMaterials($section);
        $existing = [];
        if (SchoolStudentSectionMark::isEnabled() && $materials !== []) {
            $materialIds = array_map(static fn(SchoolSectionMaterial $material): int => (int)$material->id, $materials);
            $marks = SchoolStudentSectionMark::find()
                ->where([
                    'school_student_id' => (int)$student->id,
                    'school_section_material_id' => $materialIds,
                ])
                ->all();
            foreach ($marks as $mark) {
                $existing[(int)$mark->school_section_material_id] = $mark;
            }
        }

        $rows = [];
        $scoreTotal = 0.0;
        $maxTotal = 0.0;
        $scoredCount = 0;
        $percentTotal = 0.0;

        foreach ($materials as $material) {
            $mark = $existing[(int)$material->id] ?? null;
            [$score, $maximum, $complete] = $this->subjectScore($mark, $fields, $material, $section);
            $percent = false;
            if ($complete && $maximum) {
                $percent = round(($score / $maximum) * 100.0, 1);
            }
            $rows[] = [
                'subject_name' => $this->materialSubjectName($material),
                'material_id' => (int)$material->id,
                'score' => $complete ? $score : false,
                'maximum_score' => $maximum,
                'marks_label' => $complete ? ($this->formatNumber($score) . ' / ' . $this->formatNumber($maximum)) : '—',
                'percent' => $percent,
                'grade' => $complete ? $this->letterGrade($percent) : '—',
                'remarks' => $complete ? $this->remark($percent) : 'Incomplete',
                'complete' => $complete,
            ];
            $scoreTotal += (float)$score;
            $maxTotal += (float)$maximum;
            if ($maximum) {
                $percentTotal += $percent === false ? 0.0 : (float)$percent;
            }
            if ($complete) {
                $scoredCount++;
            }
        }

        $subjectCount = count($rows);
        if ($subjectCount > 0) {
            $aggregateScore = round($scoreTotal, 1);
            $aggregateMax = round($maxTotal, 1);
            $average = round($percentTotal / $subjectCount, 2);
            $aggregatePercent = $aggregateMax
                ? round(($aggregateScore / $aggregateMax) * 100.0, 1)
                : false;
        } else {
            $aggregateScore = false;
            $aggregateMax = false;
            $average = false;
            $aggregatePercent = false;
        }

        return [
            'subjects' => $rows,
            'subject_count' => $subjectCount,
            'scored_count' => $scoredCount,
            'aggregate_score' => $aggregateScore,
            'aggregate_max' => $aggregateMax,
            'aggregate_label' => $subjectCount
                ? ($this->formatNumber($aggregateScore) . ' / ' . $this->formatNumber($aggregateMax))
                : '—',
            'average' => $average,
            'average_label' => $average === false ? '—' : (number_format((float)$average, 2, '.', '') . '%'),
            'aggregate_percent' => $aggregatePercent,
        ];
    }

    /**
     * @return SchoolSectionMaterial[]
     */
    private function activeMaterials(?SchoolSection $section): array
    {
        if ($section === null || !SchoolSectionMaterial::isEnabled()) {
            return [];
        }

        $materials = SchoolSectionMaterial::find()
            ->with(['subject'])
            ->where([
                'school_section_id' => (int)$section->id,
                'status' => SchoolSectionMaterial::STATUS_ACTIVE,
            ])
            ->all();

        usort($materials, static function (SchoolSectionMaterial $a, SchoolSectionMaterial $b): int {
            $nameA = strtolower(trim((string)($a->subject?->name ?? $a->name ?? '')));
            $nameB = strtolower(trim((string)($b->subject?->name ?? $b->name ?? '')));
            $cmp = $nameA <=> $nameB;

            return $cmp !== 0 ? $cmp : ((int)$a->id <=> (int)$b->id);
        });

        return $materials;
    }

    private function materialSubjectName(SchoolSectionMaterial $material): string
    {
        $name = trim((string)($material->subject?->name ?? ''));
        if ($name !== '') {
            return $name;
        }
        $title = trim((string)($material->name ?? ''));

        return $title !== '' ? $title : 'Subject';
    }

    /**
     * @param string[] $fields
     * @return array{0:float,1:float,2:bool}
     */
    private function subjectScore(
        ?SchoolStudentSectionMark $mark,
        array $fields,
        ?SchoolSectionMaterial $material,
        ?SchoolSection $section
    ): array {
        if ($material !== null && $section !== null) {
            $maximum = $this->scopeMaximum($material, $section, $fields);
        } elseif ($mark !== null && (float)($mark->maximum_score ?? 0) > 0) {
            $maximum = round((float)$mark->maximum_score * (count($fields) / 4.0), 2);
        } else {
            $maximum = round(100.0 * (count($fields) / 4.0), 2);
        }

        if ($mark === null) {
            return [0.0, $maximum, false];
        }

        $values = [];
        foreach ($fields as $field) {
            $raw = $mark->$field ?? null;
            if ($raw === null || $raw === '') {
                return [0.0, $maximum, false];
            }
            $values[] = (float)$raw;
        }

        return [round(array_sum($values), 1), $maximum, true];
    }

    /**
     * @param string[] $fields
     */
    private function scopeMaximum(SchoolSectionMaterial $material, SchoolSection $section, array $fields): float
    {
        $termMaxima = $this->readTermMaxima($material, $section);
        $total = 0.0;
        foreach ($fields as $field) {
            $key = self::FIELD_TO_TERM[$field] ?? null;
            if ($key !== null) {
                $total += (float)($termMaxima[$key] ?? 0.0);
            }
        }
        if ($total > 0) {
            return round($total, 2);
        }

        $subjectMax = $this->marksService()->resolveSubjectMaxScore($material, $section);
        if (count($fields) >= 4) {
            return $subjectMax;
        }

        return round($subjectMax * (count($fields) / 4.0), 2);
    }

    /**
     * Read-only term maxima. Does not persist even-split corrections the way mark entry does.
     *
     * @return array{max_first:float,max_second:float,max_third:float,max_final:float}
     */
    private function readTermMaxima(SchoolSectionMaterial $material, SchoolSection $section): array
    {
        $maximum = $this->marksService()->resolveSubjectMaxScore($material, $section);
        $line = $this->marksService()->findSubjectGradeLine($material, $section);
        if ($line !== null && SchoolSubjectGrade::hasColumn('max_first')) {
            $term = $line->termMaximaDict();
            $termSum = round($term['max_first'] + $term['max_second'] + $term['max_third'] + $term['max_final'], 2);
            if (abs($termSum - $maximum) <= 0.009 && min($term) > 0) {
                return $term;
            }
        }

        return SchoolSectionMarksService::evenTermMaxSplit($maximum);
    }

    /**
     * @param mixed $percent
     */
    private function letterGrade($percent): string
    {
        if ($percent === false || $percent === null) {
            return '—';
        }
        $p = (float)$percent;
        if ($p >= 95) {
            return 'A+';
        }
        if ($p >= 90) {
            return 'A';
        }
        if ($p >= 85) {
            return 'A-';
        }
        if ($p >= 80) {
            return 'B+';
        }
        if ($p >= 75) {
            return 'B';
        }
        if ($p >= 70) {
            return 'B-';
        }
        if ($p >= 65) {
            return 'C+';
        }
        if ($p >= 60) {
            return 'C';
        }
        if ($p >= 50) {
            return 'D';
        }

        return 'F';
    }

    /**
     * @param mixed $percent
     */
    private function remark($percent): string
    {
        if ($percent === false || $percent === null) {
            return 'Incomplete';
        }
        $p = (float)$percent;
        if ($p >= 90) {
            return 'Excellent';
        }
        if ($p >= 80) {
            return 'Very Good';
        }
        if ($p >= 70) {
            return 'Good Progress';
        }
        if ($p >= 60) {
            return 'Satisfactory';
        }
        if ($p >= 50) {
            return 'Needs Improvement';
        }

        return 'Unsatisfactory';
    }

    /**
     * @param array<int, array<string, mixed>> $ranking
     */
    private function applyCompetitionRanks(array &$ranking): void
    {
        $prevKey = null;
        $currentRank = 0;
        foreach ($ranking as &$row) {
            $avg = $row['average'] ?? false;
            if ($avg === false || $avg === null) {
                $row['rank'] = false;
                continue;
            }
            $key = round((float)$avg, 2);
            if ($prevKey === null || $key !== $prevKey) {
                $currentRank++;
                $prevKey = $key;
            }
            $row['rank'] = $currentRank;
        }
        unset($row);
    }

    /**
     * @param array<int, array{0:int,1:float}> $averages
     * @return int|false
     */
    private function competitionRankForStudent(array $averages, int $studentId)
    {
        $prevKey = null;
        $currentRank = 0;
        foreach ($averages as [$sid, $avg]) {
            $key = round((float)$avg, 2);
            if ($prevKey === null || $key !== $prevKey) {
                $currentRank++;
                $prevKey = $key;
            }
            if ((int)$sid === $studentId) {
                return $currentRank;
            }
        }

        return false;
    }

    private function attendanceDays(SchoolStudent $student): int
    {
        if (!SchoolStudentAttendanceRecord::isEnabled()) {
            return 0;
        }

        return (int)SchoolStudentAttendanceRecord::find()
            ->where(['school_student_id' => (int)$student->id])
            ->andWhere(['status' => SchoolStudentAttendanceRecord::attendedStatuses()])
            ->count();
    }

    /**
     * @param mixed $value
     */
    private function formatNumber($value): string
    {
        $number = round((float)$value, 1);
        if (abs($number - round($number)) < 0.05) {
            return (string)(int)round($number);
        }

        return number_format($number, 1, '.', '');
    }
}
