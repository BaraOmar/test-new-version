<?php

namespace common\modules\employee\services;

use common\models\User;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolSectionMaterial;
use common\modules\employee\models\SchoolStudent;
use common\modules\employee\models\SchoolStudentSectionMark;
use common\modules\employee\models\SchoolSubjectGrade;
use Yii;
use yii\db\Transaction;

class SchoolSectionMarksService
{
    public const DEFAULT_SUBJECT_MAX_SCORE = 100.0;
    public const PASS_THRESHOLD_RATIO = 0.5;
    public const MARK_INPUT_MAX_LENGTH = 5;

    public static function isEnabled(): bool
    {
        return SchoolStudentSectionMark::isEnabled()
            && SchoolStudent::isEnabled()
            && SchoolSectionMaterial::isEnabled();
    }

    /**
     * @return array{max_first:float,max_second:float,max_third:float,max_final:float}
     */
    public static function evenTermMaxSplit(float $maximumMark): array
    {
        $maximum = round($maximumMark, 2);
        if ($maximum <= 0) {
            return [
                'max_first' => 25.0,
                'max_second' => 25.0,
                'max_third' => 25.0,
                'max_final' => 25.0,
            ];
        }

        $quarter = round($maximum / 4.0, 2);
        $final = round($maximum - ($quarter * 3), 2);

        return [
            'max_first' => $quarter,
            'max_second' => $quarter,
            'max_third' => $quarter,
            'max_final' => $final,
        ];
    }

    /**
     * @return SchoolStudent[]
     */
    public function findActiveStudentsForSection(SchoolSection $section): array
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

    public function findSubjectGradeLine(SchoolSectionMaterial $material, SchoolSection $section): ?SchoolSubjectGrade
    {
        $subjectId = (int)($material->school_subject_id ?? 0);
        $gradeId = (int)($section->school_grade_id ?? 0);
        if ($subjectId <= 0 || $gradeId <= 0 || !SchoolSubjectGrade::isEnabled()) {
            return null;
        }

        return SchoolSubjectGrade::findOne([
            'school_subject_id' => $subjectId,
            'school_grade_id' => $gradeId,
        ]);
    }

    public function resolveSubjectMaxScore(SchoolSectionMaterial $material, SchoolSection $section): float
    {
        $line = $this->findSubjectGradeLine($material, $section);
        if ($line !== null) {
            if (SchoolSubjectGrade::hasColumn('max_score')) {
                $maxScore = (float)($line->max_score ?? 0);
                if ($maxScore > 0) {
                    return round($maxScore, 2);
                }
            }
            if (SchoolSubjectGrade::hasColumn('maximum_mark')) {
                $maxScore = (float)($line->maximum_mark ?? 0);
                if ($maxScore > 0) {
                    return round($maxScore, 2);
                }
            }
        }

        return self::DEFAULT_SUBJECT_MAX_SCORE;
    }

    /**
     * @return array{max_first:float,max_second:float,max_third:float,max_final:float}
     */
    public function resolveTermMaxima(SchoolSectionMaterial $material, SchoolSection $section): array
    {
        $maximum = $this->resolveSubjectMaxScore($material, $section);
        $line = $this->findSubjectGradeLine($material, $section);
        if ($line !== null && SchoolSubjectGrade::hasColumn('max_first')) {
            $term = $line->termMaximaDict();
            $termSum = round($term['max_first'] + $term['max_second'] + $term['max_third'] + $term['max_final'], 2);
            if (abs($termSum - $maximum) <= 0.009 && min($term) > 0) {
                return $term;
            }

            $split = self::evenTermMaxSplit($maximum);
            $line->max_first = $split['max_first'];
            $line->max_second = $split['max_second'];
            $line->max_third = $split['max_third'];
            $line->max_final = $split['max_final'];
            $line->save(false);

            return $split;
        }

        return self::evenTermMaxSplit($maximum);
    }

    /**
     * @param array{max_first?:mixed,max_second?:mixed,max_third?:mixed,max_final?:mixed} $termMaxima
     * @return array{success:bool,message:string,term_maxima?:array<string,float>}
     */
    public function saveTermMaxima(
        SchoolSectionMaterial $material,
        SchoolSection $section,
        array $termMaxima
    ): array {
        $line = $this->findSubjectGradeLine($material, $section);
        if ($line === null) {
            return [
                'success' => false,
                'message' => 'This subject is not assigned to the section grade, so term maxima cannot be saved.',
            ];
        }

        $maximum = $this->resolveSubjectMaxScore($material, $section);
        $labels = [
            'max_first' => 'First',
            'max_second' => 'Second',
            'max_third' => 'Third',
            'max_final' => 'Final',
        ];
        $vals = [];
        foreach ($labels as $key => $label) {
            $raw = $termMaxima[$key] ?? null;
            if ($raw === null || $raw === '') {
                return ['success' => false, 'message' => $label . ' term maximum must be numeric.'];
            }
            if (!is_numeric($raw)) {
                return ['success' => false, 'message' => $label . ' term maximum must be numeric.'];
            }
            $value = round((float)$raw, 2);
            if ($value <= 0) {
                return ['success' => false, 'message' => $label . ' term maximum must be greater than zero.'];
            }
            $vals[$key] = $value;
        }

        $termSum = round(array_sum($vals), 2);
        if (abs($termSum - round($maximum, 2)) > 0.009) {
            return [
                'success' => false,
                'message' => 'First + Second + Third + Final maxima (' . $this->formatScoreLabel($termSum)
                    . ') must equal the subject maximum mark (' . $this->formatScoreLabel($maximum) . ').',
            ];
        }

        foreach ($vals as $key => $value) {
            $line->$key = $value;
        }
        if (!$line->save()) {
            return [
                'success' => false,
                'message' => 'Unable to save term maxima.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Term maxima saved successfully.',
            'term_maxima' => $line->termMaximaDict(),
        ];
    }

    public function formatScoreLabel(float $score): string
    {
        return rtrim(rtrim(number_format($score, 1, '.', ''), '0'), '.');
    }

    public function resolvePassThreshold(float $subjectMaxScore): float
    {
        return round($subjectMaxScore * self::PASS_THRESHOLD_RATIO, 2);
    }

    public function exceedsSubjectMaxTotal(?float $total, float $subjectMaxScore): bool
    {
        return $total !== null && $total > $subjectMaxScore;
    }

    /**
     * @param array{first:float|null,second:float|null,third:float|null,final:float|null} $marks
     * @param array{max_first:float,max_second:float,max_third:float,max_final:float} $termMaxima
     */
    public function exceedsAnyTermMax(array $marks, array $termMaxima): bool
    {
        foreach (SchoolStudentSectionMark::MARK_FIELDS as $key => $column) {
            $value = $marks[$key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $termKey = SchoolStudentSectionMark::TERM_MAX_FIELDS[$key];
            $termMax = (float)($termMaxima[$termKey] ?? 0);
            if ((float)$value > $termMax) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{first:float|null,second:float|null,third:float|null,final:float|null} $marks
     */
    public function hasAllMarks(array $marks): bool
    {
        foreach (array_keys(SchoolStudentSectionMark::MARK_FIELDS) as $key) {
            if (!array_key_exists($key, $marks) || $marks[$key] === null || $marks[$key] === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array{first:float|null,second:float|null,third:float|null,final:float|null} $marks
     */
    public function calculateTotal(array $marks): ?float
    {
        if (!$this->hasAllMarks($marks)) {
            return null;
        }

        $sum = (float)$marks['first']
            + (float)$marks['second']
            + (float)$marks['third']
            + (float)$marks['final'];

        return round($sum, 1);
    }

    public function calculateStatus(?float $total, float $subjectMaxScore, bool $complete = true): string
    {
        if (!$complete || $total === null || $this->exceedsSubjectMaxTotal($total, $subjectMaxScore)) {
            return SchoolStudentSectionMark::STATUS_INCOMPLETE;
        }

        return $total >= $this->resolvePassThreshold($subjectMaxScore)
            ? SchoolStudentSectionMark::STATUS_PASS
            : SchoolStudentSectionMark::STATUS_FAILED;
    }

    /**
     * @param SchoolStudent[] $students
     * @return array<int, array<string, mixed>>
     */
    public function buildMarkRows(
        array $students,
        SchoolSectionMaterial $material,
        float $subjectMaxScore,
        array $termMaxima
    ): array {
        if ($students === []) {
            return [];
        }

        $studentIds = array_map(static fn(SchoolStudent $student): int => (int)$student->id, $students);
        $existingMarks = SchoolStudentSectionMark::find()
            ->where([
                'school_section_material_id' => (int)$material->id,
                'school_student_id' => $studentIds,
            ])
            ->indexBy('school_student_id')
            ->all();

        $rows = [];
        foreach ($students as $student) {
            $studentId = (int)$student->id;
            $mark = $existingMarks[$studentId] ?? null;
            $first = $this->nullableMark($mark?->mark_first);
            $second = $this->nullableMark($mark?->mark_second);
            $third = $this->nullableMark($mark?->mark_third);
            $final = $this->nullableMark($mark?->mark_final);
            $marks = [
                'first' => $first,
                'second' => $second,
                'third' => $third,
                'final' => $final,
            ];
            $complete = $mark !== null && SchoolStudentSectionMark::hasColumn('mark_components_complete')
                ? (bool)$mark->mark_components_complete
                : $this->hasAllMarks($marks);
            $total = $complete ? $this->calculateTotal($marks) : null;
            $overTermMax = $this->exceedsAnyTermMax($marks, $termMaxima);
            $overMax = $this->exceedsSubjectMaxTotal($total, $subjectMaxScore) || $overTermMax;
            $status = $overMax
                ? SchoolStudentSectionMark::STATUS_INCOMPLETE
                : $this->calculateStatus($total, $subjectMaxScore, $complete);

            $rows[$studentId] = [
                'student' => $student,
                'first' => $first,
                'second' => $second,
                'third' => $third,
                'final' => $final,
                'total' => $total,
                'status' => $status,
                'over_max' => $overMax,
                'complete' => $complete,
            ];
        }

        return $rows;
    }

    /**
     * @param SchoolStudent[] $students
     * @param array<int, array<string, mixed>> $submittedMarks
     * @param array{max_first:float,max_second:float,max_third:float,max_final:float} $termMaxima
     * @return array{success:bool,message:string,savedCount:int}
     */
    public function saveMarks(
        SchoolSectionMaterial $material,
        array $students,
        array $submittedMarks,
        float $subjectMaxScore,
        array $termMaxima,
        User $user
    ): array {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Marks are not available. Run migrations first.', 'savedCount' => 0];
        }

        $studentIdLookup = [];
        foreach ($students as $student) {
            $studentIdLookup[(int)$student->id] = true;
        }

        $termFieldMap = SchoolStudentSectionMark::TERM_MAX_FIELDS;
        $markFieldMap = SchoolStudentSectionMark::MARK_FIELDS;
        $prepared = [];
        $errors = [];

        foreach ($submittedMarks as $studentId => $payload) {
            $studentId = (int)$studentId;
            if (!isset($studentIdLookup[$studentId]) || !is_array($payload)) {
                continue;
            }

            $vals = [];
            $markMap = [];
            $filled = 0;
            foreach ($markFieldMap as $key => $column) {
                $termKey = $termFieldMap[$key];
                $termMax = (float)($termMaxima[$termKey] ?? $subjectMaxScore);
                $label = ucfirst($key === 'final' ? 'Final' : $key);
                $parsed = $this->parseSubmittedMark($payload[$key] ?? null, $termMax, $errors, $label);
                if ($parsed === false) {
                    continue 2;
                }
                $vals[$column] = $parsed;
                $markMap[$key] = $parsed;
                if ($parsed !== null) {
                    $filled++;
                }
            }

            $complete = $filled === 4;
            if ($complete) {
                $total = $this->calculateTotal($markMap);
                if ($this->exceedsSubjectMaxTotal($total, $subjectMaxScore)) {
                    $errors[] = 'Total marks cannot exceed the subject maximum of '
                        . $this->formatScoreLabel($subjectMaxScore) . '.';
                    continue;
                }
            }

            $prepared[] = [
                'student_id' => $studentId,
                'vals' => $vals,
                'filled' => $filled,
                'complete' => $complete,
                'total' => $complete ? $this->calculateTotal($markMap) : null,
            ];
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => implode(' ', array_unique($errors)),
                'savedCount' => 0,
            ];
        }

        $transaction = Yii::$app->db->beginTransaction(Transaction::SERIALIZABLE);
        try {
            $savedCount = 0;
            $existingMarks = SchoolStudentSectionMark::find()
                ->where(['school_section_material_id' => (int)$material->id])
                ->indexBy('school_student_id')
                ->all();

            foreach ($prepared as $row) {
                $studentId = (int)$row['student_id'];
                if ($row['filled'] === 0) {
                    if (isset($existingMarks[$studentId])) {
                        $existingMarks[$studentId]->delete();
                        $savedCount++;
                    }
                    continue;
                }

                $mark = $existingMarks[$studentId] ?? new SchoolStudentSectionMark([
                    'school_student_id' => $studentId,
                    'school_section_material_id' => (int)$material->id,
                ]);

                $mark->mark_first = $row['vals']['mark_first'];
                $mark->mark_second = $row['vals']['mark_second'];
                $mark->mark_third = $row['vals']['mark_third'];
                $mark->mark_final = $row['vals']['mark_final'];
                if (SchoolStudentSectionMark::hasColumn('mark_components_complete')) {
                    $mark->mark_components_complete = (bool)$row['complete'];
                }
                $mark->total = $row['total'];
                if (SchoolStudentSectionMark::hasColumn('maximum_score')) {
                    $mark->maximum_score = $subjectMaxScore;
                }
                $mark->status = $this->calculateStatus($row['total'], $subjectMaxScore, (bool)$row['complete']);
                $mark->updated_by_user_id = (int)$user->id;

                if (!$mark->save()) {
                    $transaction->rollBack();

                    return [
                        'success' => false,
                        'message' => implode(' ', $mark->getFirstErrors()) ?: 'Unable to save marks for one or more students.',
                        'savedCount' => 0,
                    ];
                }

                $savedCount++;
            }

            $transaction->commit();

            return [
                'success' => true,
                'message' => $savedCount > 0 ? 'Marks saved successfully.' : 'No marks were updated.',
                'savedCount' => $savedCount,
            ];
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            Yii::error($exception->getMessage(), __METHOD__);

            return [
                'success' => false,
                'message' => 'Unable to save marks right now.',
                'savedCount' => 0,
            ];
        }
    }

    /**
     * @return array{
     *     academicSessionLabel:string,
     *     sectionLabel:string,
     *     rows:array<int, array<string, mixed>>
     * }
     */
    public function buildStudentGradebook(SchoolStudent $student): array
    {
        $sectionId = (int)$student->school_section_id;
        if ($sectionId <= 0 || !SchoolSection::isEnabled()) {
            return [
                'academicSessionLabel' => $this->resolveAcademicSessionLabel([$student]),
                'sectionLabel' => '',
                'rows' => [],
            ];
        }

        $section = SchoolSection::find()
            ->with(['grade', 'materials.subject'])
            ->where(['id' => $sectionId])
            ->one();

        if ($section === null) {
            return [
                'academicSessionLabel' => $this->resolveAcademicSessionLabel([$student]),
                'sectionLabel' => '',
                'rows' => [],
            ];
        }

        $gradeName = trim((string)($section->grade?->name ?? ''));
        $sectionLabel = $gradeName !== '' ? $gradeName . ' · ' . $section->name : (string)$section->name;

        $materials = SchoolSectionMaterial::find()
            ->with(['subject'])
            ->where(['school_section_id' => $sectionId])
            ->andWhere(['status' => [
                SchoolSectionMaterial::STATUS_ACTIVE,
                SchoolSectionMaterial::STATUS_PENDING,
            ]])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        if ($materials === []) {
            return [
                'academicSessionLabel' => $this->resolveAcademicSessionLabel([$student]),
                'sectionLabel' => $sectionLabel,
                'rows' => [],
            ];
        }

        $materialIds = array_map(static fn(SchoolSectionMaterial $material): int => (int)$material->id, $materials);
        $existingMarks = SchoolStudentSectionMark::find()
            ->where([
                'school_student_id' => (int)$student->id,
                'school_section_material_id' => $materialIds,
            ])
            ->indexBy('school_section_material_id')
            ->all();

        $rows = [];
        foreach ($materials as $material) {
            $subjectMaxScore = $this->resolveSubjectMaxScore($material, $section);
            $termMaxima = $this->resolveTermMaxima($material, $section);
            $passThreshold = $this->resolvePassThreshold($subjectMaxScore);
            $mark = $existingMarks[(int)$material->id] ?? null;
            $first = $this->nullableMark($mark?->mark_first);
            $second = $this->nullableMark($mark?->mark_second);
            $third = $this->nullableMark($mark?->mark_third);
            $final = $this->nullableMark($mark?->mark_final);
            $marks = [
                'first' => $first,
                'second' => $second,
                'third' => $third,
                'final' => $final,
            ];
            $complete = $mark !== null && SchoolStudentSectionMark::hasColumn('mark_components_complete')
                ? (bool)$mark->mark_components_complete
                : $this->hasAllMarks($marks);
            $total = $complete ? $this->calculateTotal($marks) : null;
            $overTermMax = $this->exceedsAnyTermMax($marks, $termMaxima);
            $overMax = $this->exceedsSubjectMaxTotal($total, $subjectMaxScore) || $overTermMax;
            $status = $overMax
                ? SchoolStudentSectionMark::STATUS_INCOMPLETE
                : $this->calculateStatus($total, $subjectMaxScore, $complete);

            $subjectName = trim((string)($material->subject?->name ?? ''));
            $materialName = trim((string)$material->name);
            $subjectLabel = $subjectName !== '' ? $subjectName : ($materialName !== '' ? $materialName : 'Course #' . (int)$material->id);
            $subjectCode = trim((string)($material->subject?->code ?? ''));
            if ($subjectCode === '' && $materialName !== '' && $materialName !== $subjectName) {
                $subjectCode = $materialName;
            }

            $rows[] = [
                'material_id' => (int)$material->id,
                'subject_label' => $subjectLabel,
                'subject_code' => $subjectCode,
                'first' => $first,
                'second' => $second,
                'third' => $third,
                'final' => $final,
                'total' => $total,
                'status' => $status,
                'over_max' => $overMax,
                'max_score' => $subjectMaxScore,
                'pass_threshold' => $passThreshold,
            ];
        }

        return [
            'academicSessionLabel' => $this->resolveAcademicSessionLabel([$student]),
            'sectionLabel' => $sectionLabel,
            'rows' => $rows,
        ];
    }

    public function resolveAcademicSessionLabel(array $students): string
    {
        foreach ($students as $student) {
            $year = trim((string)($student->academic_year ?? ''));
            if ($year !== '') {
                return 'Academic Session ' . $year;
            }
        }

        if (SchoolStudent::hasColumn('academic_year')) {
            return 'Academic Session ' . SchoolStudent::defaultAcademicYear();
        }

        return 'Academic Session';
    }

    /**
     * @param mixed $value
     */
    private function parseSubmittedMark($value, float $maxScore, array &$errors, string $label): float|null|false
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string)$value);
        if ($raw === '') {
            return null;
        }

        if (strlen($raw) > self::MARK_INPUT_MAX_LENGTH) {
            $errors[] = $label . ' marks cannot exceed ' . self::MARK_INPUT_MAX_LENGTH . ' digits.';

            return false;
        }

        if (!is_numeric($raw)) {
            $errors[] = $label . ' marks must be numeric.';

            return false;
        }

        $mark = round((float)$raw, 1);
        if ($mark < 0) {
            $errors[] = $label . ' marks cannot be negative.';

            return false;
        }

        if ($mark > $maxScore) {
            $errors[] = $label . ' marks cannot exceed ' . $this->formatScoreLabel($maxScore) . '.';

            return false;
        }

        return $mark;
    }

    /**
     * @param mixed $value
     */
    private function nullableMark($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float)$value, 1);
    }
}
