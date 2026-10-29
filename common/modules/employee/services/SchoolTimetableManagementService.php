<?php

namespace common\modules\employee\services;

use common\modules\employee\models\Employee;
use common\modules\employee\models\EmployeeSchoolSubject;
use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolSectionMaterial;
use common\modules\employee\models\SchoolSectionTimetableColumn;
use common\modules\employee\models\SchoolSectionTimetableRow;
use common\modules\employee\models\SchoolSectionTimetableSlot;
use common\models\User;
use common\components\LmsPortal;
use Yii;

class SchoolTimetableManagementService
{
    private const MSG_TEACHER_REQUIRED = 'Teacher is required.';
    private const MSG_NO_INSTRUCTOR = 'No instructor';
    private const MSG_NOT_TEACHER = 'The selected employee is not a Teacher.';
    private const MSG_INACTIVE_TEACHER = 'Inactive Teachers cannot be used for new assignments.';
    private const MSG_TEACHER_SUBJECT = 'This Teacher is not assigned to the selected Subject.';
    private const MSG_TEACHER_DEPARTMENT = 'Teacher must belong to the relevant School Department.';
    private const MSG_TEACHER_CONFLICT = 'Teacher is already assigned to another Section during this period.';
    private const MSG_LESSON_NOTE_CHARS = 'Lesson note is too long (max 100 characters).';
    private const MSG_LESSON_NOTE_WORDS = 'Lesson note is too long (max 15 words).';
    private const MSG_LESSON_NOTE_PERMISSION = 'Only the assigned subject teacher can edit this lesson note.';

    /**
     * @return array<int, string>
     */
    public static function dayNameMap(): array
    {
        return SchoolSectionTimetableRow::DAY_NAMES;
    }

    /**
     * Default school-week display order: Sunday through Thursday.
     *
     * @return int[]
     */
    public static function defaultSchoolWeekDayOrder(): array
    {
        return [7, 1, 2, 3, 4];
    }

    /**
     * @return SchoolSectionMaterial[]
     */
    public function findSectionMaterials(SchoolSection $section): array
    {
        if (!SchoolSectionMaterial::isEnabled()) {
            return [];
        }

        return SchoolSectionMaterial::find()
            ->with(['subject', 'instructor'])
            ->where(['school_section_id' => (int)$section->id])
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }

    /**
     * @return array{
     *   rows: array<int, array<string, mixed>>,
     *   columns: array<int, array<string, mixed>>,
     *   slots: array<string, array<string, mixed>>,
     *   materials: array<int, array<string, mixed>>
     * }
     */
    public function buildTimetablePayload(SchoolSection $section): array
    {
        if (!SchoolSectionTimetableRow::isEnabled() || !SchoolSectionTimetableColumn::isEnabled()) {
            throw new \RuntimeException('Timetable tables are not installed. Run: php yii migrate/up');
        }

        $this->ensureDefaultGrid((int)$section->id);

        $rows = SchoolSectionTimetableRow::find()
            ->where(['school_section_id' => (int)$section->id])
            ->orderBy(['sort_order' => SORT_ASC, 'day_of_week' => SORT_ASC])
            ->all();

        $columns = SchoolSectionTimetableColumn::find()
            ->where(['school_section_id' => (int)$section->id])
            ->orderBy(['sort_order' => SORT_ASC, 'start_time' => SORT_ASC])
            ->all();

        $slotModels = [];
        if (SchoolSectionTimetableSlot::isEnabled()) {
            $slotModels = SchoolSectionTimetableSlot::find()
                ->with(['material.subject', 'material.instructor', 'row', 'column'])
                ->where(['school_section_id' => (int)$section->id])
                ->all();
        }

        $slots = [];
        foreach ($slotModels as $slot) {
            $key = (int)$slot->row_id . ':' . (int)$slot->column_id;
            $material = $slot->material;
            $subjectName = trim((string)($material?->subject?->name ?? $material?->name ?? ''));
            $teacherName = trim((string)($material?->instructor?->full_name ?? ''));
            $slots[$key] = [
                'id' => (int)$slot->id,
                'row_id' => (int)$slot->row_id,
                'column_id' => (int)$slot->column_id,
                'material_id' => (int)($slot->school_section_material_id ?? 0),
                'instructor_id' => (int)($slot->instructor_id ?? 0) ?: (int)($material?->instructor_id ?? 0),
                'subject_name' => $subjectName,
                'teacher_name' => $teacherName,
                'room_number' => trim((string)($slot->room_number ?? $section->room_number ?? '')),
                'lesson_brief' => $this->formatLessonBrief($slot->lesson_brief ?? null),
            ];
        }

        $materials = [];
        foreach ($this->findSectionMaterials($section) as $material) {
            $materials[] = [
                'id' => (int)$material->id,
                'subject_name' => trim((string)($material->subject?->name ?? $material->name ?? '')),
                'teacher_name' => trim((string)($material->instructor?->full_name ?? '')),
                'teacher_id' => (int)($material->instructor_id ?? 0),
            ];
        }

        return [
            'rows' => array_map(static fn(SchoolSectionTimetableRow $row) => [
                'id' => (int)$row->id,
                'day_of_week' => (int)$row->day_of_week,
                'custom_label' => trim((string)($row->label ?? '')),
                'label' => $row->getDisplayLabel(),
            ], $rows),
            'columns' => array_map(static fn(SchoolSectionTimetableColumn $column) => [
                'id' => (int)$column->id,
                'start_time' => substr((string)$column->start_time, 0, 5),
                'end_time' => substr((string)$column->end_time, 0, 5),
                'label' => $column->getDisplayLabel(),
                'time_range' => $column->getTimeRangeLabel(),
                'is_break' => (bool)$column->is_break,
                'is_exam' => (bool)$column->is_exam,
            ], $columns),
            'slots' => $slots,
            'materials' => $materials,
        ];
    }

    public function ensureDefaultGrid(int $sectionId): void
    {
        if (!SchoolSectionTimetableRow::isEnabled() || !SchoolSectionTimetableColumn::isEnabled()) {
            return;
        }

        $hasRows = SchoolSectionTimetableRow::find()->where(['school_section_id' => $sectionId])->exists();
        $hasColumns = SchoolSectionTimetableColumn::find()->where(['school_section_id' => $sectionId])->exists();
        if ($hasRows && $hasColumns) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        if (!$hasRows) {
            foreach ([1, 2, 3, 4, 5] as $index => $day) {
                $row = new SchoolSectionTimetableRow([
                    'school_section_id' => $sectionId,
                    'day_of_week' => $day,
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $row->save(false);
            }
        }

        if (!$hasColumns) {
            $defaults = [
                ['08:00:00', '09:00:00', 'Period 1', false],
                ['09:00:00', '10:00:00', 'Period 2', false],
                ['10:00:00', '10:15:00', 'Break', true],
                ['10:15:00', '11:15:00', 'Period 3', false],
                ['11:15:00', '12:15:00', 'Period 4', false],
            ];
            foreach ($defaults as $index => [$start, $end, $label, $isBreak]) {
                $column = new SchoolSectionTimetableColumn([
                    'school_section_id' => $sectionId,
                    'start_time' => $start,
                    'end_time' => $end,
                    'label' => $label,
                    'sort_order' => $index,
                    'is_break' => $isBreak,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $column->save(false);
            }
        }
    }

    /**
     * @param array<int, array{day_of_week:int,label?:string}> $rows
     * @param array<int, array{start_time:string,end_time:string,label?:string,is_break?:bool,is_exam?:bool}> $columns
     * @return array{success:bool,errors:string[]}
     */
    public function saveGrid(int $sectionId, array $rows, array $columns): array
    {
        if ($columns === []) {
            return ['success' => false, 'errors' => ['Add at least one time slot.']];
        }

        if (count($rows) > 7) {
            return ['success' => false, 'errors' => ['You can add up to 7 week days only.']];
        }

        $usedDays = [];
        foreach ($rows as $rowData) {
            $day = (int)($rowData['day_of_week'] ?? 0);
            if ($day < 1 || $day > 7) {
                return ['success' => false, 'errors' => ['Invalid day selected.']];
            }
            if (isset($usedDays[$day])) {
                return ['success' => false, 'errors' => ['Each week day can only be added once.']];
            }
            $usedDays[$day] = true;
        }

        $columnError = $this->validateColumnTimeSlots($columns);
        if ($columnError !== null) {
            return ['success' => false, 'errors' => [$columnError]];
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            SchoolSectionTimetableSlot::deleteAll(['school_section_id' => $sectionId]);
            SchoolSectionTimetableRow::deleteAll(['school_section_id' => $sectionId]);
            SchoolSectionTimetableColumn::deleteAll(['school_section_id' => $sectionId]);

            $now = date('Y-m-d H:i:s');
            foreach (array_values($rows) as $index => $rowData) {
                $day = (int)($rowData['day_of_week'] ?? 0);

                $row = new SchoolSectionTimetableRow([
                    'school_section_id' => $sectionId,
                    'day_of_week' => $day,
                    'label' => trim((string)($rowData['label'] ?? '')) ?: null,
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                if (!$row->save()) {
                    $transaction->rollBack();

                    return ['success' => false, 'errors' => $row->getFirstErrors() ?: ['Unable to save timetable days.']];
                }
            }

            foreach (array_values($columns) as $index => $columnData) {
                $start = $this->normalizeTime((string)($columnData['start_time'] ?? ''));
                $end = $this->normalizeTime((string)($columnData['end_time'] ?? ''));

                $isExam = !empty($columnData['is_exam']);
                $isBreak = !$isExam && !empty($columnData['is_break']);

                $column = new SchoolSectionTimetableColumn([
                    'school_section_id' => $sectionId,
                    'start_time' => $start,
                    'end_time' => $end,
                    'label' => trim((string)($columnData['label'] ?? '')) ?: null,
                    'sort_order' => $index,
                    'is_break' => $isBreak,
                    'is_exam' => $isExam,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                if (!$column->save()) {
                    $transaction->rollBack();

                    return ['success' => false, 'errors' => $column->getFirstErrors() ?: ['Unable to save timetable hours.']];
                }
            }

            $transaction->commit();

            return ['success' => true, 'errors' => []];
        } catch (\Throwable $e) {
            $transaction->rollBack();

            return ['success' => false, 'errors' => ['Unable to save timetable layout.']];
        }
    }

    /**
     * @return array{success:bool,errors:string[]}
     */
    public function clearSectionSlots(int $sectionId): array
    {
        if (!SchoolSectionTimetableSlot::isEnabled()) {
            return ['success' => true, 'errors' => []];
        }

        SchoolSectionTimetableSlot::deleteAll(['school_section_id' => $sectionId]);

        return ['success' => true, 'errors' => []];
    }

    /**
     * @return array{success:bool,errors:string[],slot?:array<string,mixed>|null}
     */
    public function assignMaterialToCell(
        SchoolSection $section,
        int $rowId,
        int $columnId,
        ?int $materialId
    ): array {
        $row = SchoolSectionTimetableRow::findOne(['id' => $rowId, 'school_section_id' => (int)$section->id]);
        $column = SchoolSectionTimetableColumn::findOne(['id' => $columnId, 'school_section_id' => (int)$section->id]);
        if ($row === null || $column === null) {
            return ['success' => false, 'errors' => ['Timetable cell not found.']];
        }

        if ($column->is_break) {
            return ['success' => false, 'errors' => ['Cannot assign a subject to a break period.']];
        }

        if ($column->is_exam) {
            return ['success' => false, 'errors' => ['Cannot assign a subject to an exam period.']];
        }

        $slot = SchoolSectionTimetableSlot::findOne([
            'school_section_id' => (int)$section->id,
            'row_id' => $rowId,
            'column_id' => $columnId,
        ]) ?? new SchoolSectionTimetableSlot([
            'school_section_id' => (int)$section->id,
            'row_id' => $rowId,
            'column_id' => $columnId,
        ]);

        if ($materialId === null || $materialId <= 0) {
            if (!$slot->isNewRecord) {
                $slot->delete();
            }

            return ['success' => true, 'errors' => [], 'slot' => null];
        }

        $material = SchoolSectionMaterial::find()
            ->with(['instructor', 'subject'])
            ->where([
                'id' => $materialId,
                'school_section_id' => (int)$section->id,
            ])
            ->one();
        if ($material === null) {
            return ['success' => false, 'errors' => ['Selected subject is not part of this section.']];
        }

        $teacher = $material->instructor;
        $validationErrors = $this->validateSlotAssignment(
            $section,
            $row,
            $column,
            $material,
            $teacher,
            $slot->isNewRecord ? null : (int)$slot->id,
            $slot->isNewRecord
        );
        if ($validationErrors !== []) {
            return ['success' => false, 'errors' => $validationErrors];
        }

        $instructorId = (int)($material->instructor_id ?? 0);

        $slot->school_section_material_id = $materialId;
        $slot->instructor_id = $instructorId > 0 ? $instructorId : null;
        $slot->room_number = trim((string)($section->room_number ?? '')) ?: null;

        if (!$slot->save()) {
            return ['success' => false, 'errors' => $slot->getFirstErrors() ?: ['Unable to save timetable slot.']];
        }

        $subjectName = trim((string)($material->subject?->name ?? $material->name ?? ''));

        return [
            'success' => true,
            'errors' => [],
            'slot' => [
                'id' => (int)$slot->id,
                'row_id' => $rowId,
                'column_id' => $columnId,
                'material_id' => $materialId,
                'instructor_id' => $instructorId,
                'subject_name' => $subjectName,
                'teacher_name' => trim((string)($material->instructor?->full_name ?? '')),
                'room_number' => trim((string)($slot->room_number ?? '')),
                'lesson_brief' => $this->formatLessonBrief($slot->lesson_brief ?? null),
            ],
        ];
    }

    /**
     * @return array{success:bool,errors:string[],slot?:array<string,mixed>|null}
     */
    public function updateSlotLessonBrief(User $user, int $slotId, ?string $lessonBrief): array
    {
        if (!SchoolSectionTimetableSlot::isEnabled()) {
            return ['success' => false, 'errors' => ['Timetable storage is not ready. Run: php yii migrate/up']];
        }

        if (!SchoolSectionTimetableSlot::hasColumn('lesson_brief')) {
            return ['success' => false, 'errors' => ['Lesson notes are not available yet. Run: php yii migrate/up']];
        }

        $slot = SchoolSectionTimetableSlot::find()
            ->with(['material.subject', 'material.instructor', 'row', 'column', 'section.grade'])
            ->where(['id' => $slotId])
            ->one();
        if (!$slot instanceof SchoolSectionTimetableSlot) {
            return ['success' => false, 'errors' => ['Timetable slot not found.']];
        }

        $employee = LmsPortal::findLinkedEmployee($user, true);
        $slotInstructorId = (int)($slot->instructor_id ?? 0);
        $materialInstructorId = (int)($slot->material?->instructor_id ?? 0);
        $assignedInstructorId = $slotInstructorId > 0 ? $slotInstructorId : $materialInstructorId;
        if ($employee === null || $assignedInstructorId <= 0 || $assignedInstructorId !== (int)$employee->id) {
            return ['success' => false, 'errors' => [self::MSG_LESSON_NOTE_PERMISSION]];
        }

        $text = $this->normalizeLessonBrief($lessonBrief);
        $briefError = $this->validateLessonBriefText($text);
        if ($briefError !== null) {
            return ['success' => false, 'errors' => [$briefError]];
        }

        $slot->lesson_brief = $text !== '' ? $text : null;
        if (!$slot->save()) {
            return ['success' => false, 'errors' => $slot->getFirstErrors() ?: ['Unable to save lesson note.']];
        }

        $material = $slot->material;

        return [
            'success' => true,
            'errors' => [],
            'slot' => [
                'id' => (int)$slot->id,
                'row_id' => (int)$slot->row_id,
                'column_id' => (int)$slot->column_id,
                'material_id' => (int)($slot->school_section_material_id ?? 0),
                'instructor_id' => (int)($slot->instructor_id ?? 0),
                'subject_name' => trim((string)($material?->subject?->name ?? $material?->name ?? '')),
                'teacher_name' => trim((string)($material?->instructor?->full_name ?? '')),
                'lesson_brief' => $this->formatLessonBrief($slot->lesson_brief),
                'can_edit_lesson_brief' => true,
            ],
        ];
    }

    private function normalizeLessonBrief(?string $lessonBrief): string
    {
        return trim((string)$lessonBrief);
    }

    private function formatLessonBrief(mixed $lessonBrief): string
    {
        if (!SchoolSectionTimetableSlot::hasColumn('lesson_brief')) {
            return '';
        }

        return trim((string)$lessonBrief);
    }

    private function validateLessonBriefText(string $text): ?string
    {
        if ($text === '') {
            return null;
        }

        if (mb_strlen($text) > 100) {
            return self::MSG_LESSON_NOTE_CHARS;
        }

        $wordCount = count(array_filter(preg_split('/\s+/u', $text) ?: [], static fn(string $word): bool => $word !== ''));
        if ($wordCount > 15) {
            return self::MSG_LESSON_NOTE_WORDS;
        }

        return null;
    }

    /**
     * @return array<int, array{id:int,subject_name:string,teacher_name:string,teacher_id:int,available:bool,unavailable_reason:string,status_label:string}>
     */
    public function getCourseOptionsForCell(SchoolSection $section, int $rowId, int $columnId): array
    {
        $row = SchoolSectionTimetableRow::findOne(['id' => $rowId, 'school_section_id' => (int)$section->id]);
        $column = SchoolSectionTimetableColumn::findOne(['id' => $columnId, 'school_section_id' => (int)$section->id]);
        if ($row === null || $column === null || $column->is_break || $column->is_exam) {
            return [];
        }

        $existingSlot = SchoolSectionTimetableSlot::findOne([
            'school_section_id' => (int)$section->id,
            'row_id' => $rowId,
            'column_id' => $columnId,
        ]);
        $excludeSlotId = $existingSlot !== null ? (int)$existingSlot->id : null;
        $forNewAssignment = $existingSlot === null;

        $options = [];
        foreach ($this->findSectionMaterials($section) as $material) {
            $teacher = $material->instructor;
            $instructorId = (int)($material->instructor_id ?? 0);
            $available = true;
            $unavailableReason = '';
            $statusLabel = 'Available';

            if ($teacher === null || $instructorId <= 0) {
                $available = false;
                $unavailableReason = self::MSG_NO_INSTRUCTOR;
                $statusLabel = self::MSG_NO_INSTRUCTOR;
            } else {
                $validationErrors = $this->validateSlotAssignment(
                    $section,
                    $row,
                    $column,
                    $material,
                    $teacher,
                    $excludeSlotId,
                    $forNewAssignment
                );
                if ($validationErrors !== []) {
                    $available = false;
                    $unavailableReason = $validationErrors[0];
                    $statusLabel = 'Unavailable';
                }
            }

            $options[] = [
                'id' => (int)$material->id,
                'subject_name' => trim((string)($material->subject?->name ?? $material->name ?? '')),
                'teacher_name' => trim((string)($material->instructor?->full_name ?? '')),
                'teacher_id' => $instructorId,
                'available' => $available,
                'unavailable_reason' => $unavailableReason,
                'status_label' => $statusLabel,
            ];
        }

        return $options;
    }

    /**
     * Central validation for timetable slot assignment (aligned with Odoo validate_slot_assignment).
     *
     * @return string[]
     */
    private function validateSlotAssignment(
        SchoolSection $section,
        SchoolSectionTimetableRow $row,
        SchoolSectionTimetableColumn $column,
        SchoolSectionMaterial $material,
        ?Employee $teacher,
        ?int $excludeSlotId = null,
        bool $forNewAssignment = true
    ): array {
        $errors = [];

        if ($column->is_break) {
            $errors[] = 'Cannot assign a subject to a break period.';
        }
        if ($column->is_exam) {
            $errors[] = 'Cannot assign a subject to an exam period.';
        }

        if ((int)$material->school_section_id !== (int)$section->id) {
            $errors[] = 'The selected Course must belong to the selected Section.';
        }

        if ($teacher === null || (int)$teacher->id <= 0) {
            $errors[] = self::MSG_TEACHER_REQUIRED;

            return $errors;
        }

        if ($teacher->hasAttribute('teacher') && !$teacher->isTeacher()) {
            $errors[] = self::MSG_NOT_TEACHER;
        }

        if ($forNewAssignment && $teacher->isTerminated()) {
            $errors[] = self::MSG_INACTIVE_TEACHER;
        }

        $departmentError = $this->validateTeacherDepartment($teacher, $section);
        if ($departmentError !== null) {
            $errors[] = $departmentError;
        }

        $subjectError = $this->validateTeacherSubject($teacher, $material);
        if ($subjectError !== null) {
            $errors[] = $subjectError;
        }

        if ($errors !== []) {
            return $errors;
        }

        $conflict = $this->findInstructorConflict(
            (int)$teacher->id,
            (int)$row->day_of_week,
            (string)$column->start_time,
            (string)$column->end_time,
            $excludeSlotId,
            (int)$section->id
        );
        if ($conflict !== null) {
            $errors[] = self::MSG_TEACHER_CONFLICT;
        }

        return $errors;
    }

    private function validateTeacherDepartment(Employee $teacher, SchoolSection $section): ?string
    {
        if (!Employee::hasColumn('school_department_id')) {
            return null;
        }

        $departmentId = (int)($section->grade?->school_department_id ?? 0);
        if ($departmentId <= 0) {
            return null;
        }

        if ((int)($teacher->school_department_id ?? 0) !== $departmentId) {
            return self::MSG_TEACHER_DEPARTMENT;
        }

        return null;
    }

    private function validateTeacherSubject(Employee $teacher, SchoolSectionMaterial $material): ?string
    {
        $subjectId = (int)($material->school_subject_id ?? 0);
        if ($subjectId <= 0 || !EmployeeSchoolSubject::isEnabled()) {
            return null;
        }

        if (!$this->teacherHasSubjectAssignments((int)$teacher->id)) {
            return null;
        }

        $assigned = EmployeeSchoolSubject::findOne([
            'employee_id' => (int)$teacher->id,
            'school_subject_id' => $subjectId,
        ]);
        if ($assigned === null) {
            return self::MSG_TEACHER_SUBJECT;
        }

        return null;
    }

    private function teacherHasSubjectAssignments(int $employeeId): bool
    {
        if (!EmployeeSchoolSubject::isEnabled() || $employeeId <= 0) {
            return false;
        }

        return EmployeeSchoolSubject::find()
            ->where(['employee_id' => $employeeId])
            ->exists();
    }

    /**
     * @return array{section_label:string,time_label:string,day_label:string,period_label:string,same_section:bool}|null
     */
    public function findInstructorConflict(
        int $instructorId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeSlotId = null,
        ?int $currentSectionId = null
    ): ?array {
        if ($instructorId <= 0) {
            return null;
        }

        $start = $this->normalizeTime($startTime);
        $end = $this->normalizeTime($endTime);
        if ($start === null || $end === null) {
            return null;
        }

        $materialTable = SchoolSectionMaterial::tableName();
        $query = SchoolSectionTimetableSlot::find()
            ->alias('slot')
            ->innerJoin(['row' => SchoolSectionTimetableRow::tableName()], 'row.id = slot.row_id')
            ->innerJoin(['column' => SchoolSectionTimetableColumn::tableName()], 'column.id = slot.column_id')
            ->leftJoin(['material' => $materialTable], 'material.id = slot.school_section_material_id')
            ->leftJoin(['section' => SchoolSection::tableName()], 'section.id = slot.school_section_id')
            ->leftJoin(['grade' => SchoolGrade::tableName()], 'grade.id = section.school_grade_id')
            ->where(['row.day_of_week' => $dayOfWeek])
            ->andWhere(['column.is_break' => false, 'column.is_exam' => false])
            ->andWhere(['not', ['slot.school_section_material_id' => null]])
            ->andWhere([
                'or',
                ['slot.instructor_id' => $instructorId],
                ['material.instructor_id' => $instructorId],
            ]);

        if ($excludeSlotId !== null && $excludeSlotId > 0) {
            $query->andWhere(['<>', 'slot.id', $excludeSlotId]);
        }

        /** @var SchoolSectionTimetableSlot[] $candidates */
        $candidates = $query->all();
        foreach ($candidates as $candidate) {
            $candidateColumn = $candidate->column;
            $candidateRow = $candidate->row;
            if ($candidateColumn === null || $candidateRow === null) {
                continue;
            }

            $otherStart = $this->normalizeTime((string)$candidateColumn->start_time);
            $otherEnd = $this->normalizeTime((string)$candidateColumn->end_time);
            if ($otherStart === null || $otherEnd === null) {
                continue;
            }

            if ($start < $otherEnd && $end > $otherStart) {
                $section = $candidate->section;
                $gradeName = trim((string)($section?->grade?->name ?? ''));
                $sectionName = trim((string)($section?->name ?? ''));
                $sectionLabel = $gradeName !== '' && $sectionName !== ''
                    ? $gradeName . ' - Section ' . $sectionName
                    : ($sectionName !== '' ? 'Section ' . $sectionName : 'another section');

                return [
                    'section_label' => $sectionLabel,
                    'time_label' => $candidateColumn->getTimeRangeLabel(),
                    'day_label' => $candidateRow->getDisplayLabel(),
                    'period_label' => $candidateColumn->getDisplayLabel(),
                    'same_section' => $currentSectionId !== null
                        && (int)$candidate->school_section_id === $currentSectionId,
                ];
            }
        }

        return null;
    }

    private function normalizeTime(string $time): ?string
    {
        $time = trim($time);
        if ($time === '') {
            return null;
        }

        if (preg_match('/^\d{1,2}:\d{2}$/', $time) === 1) {
            $time .= ':00';
        }

        $timestamp = strtotime($time);

        return $timestamp !== false ? date('H:i:s', $timestamp) : null;
    }

    /**
     * @param array<int, array{start_time?:string,end_time?:string,label?:string,is_break?:mixed}> $columns
     */
    private function validateColumnTimeSlots(array $columns): ?string
    {
        $intervals = [];
        foreach (array_values($columns) as $index => $columnData) {
            $start = $this->normalizeTime((string)($columnData['start_time'] ?? ''));
            $end = $this->normalizeTime((string)($columnData['end_time'] ?? ''));
            if ($start === null || $end === null || $start >= $end) {
                return 'Each time slot needs a valid start and end time.';
            }

            $label = trim((string)($columnData['label'] ?? ''));
            if ($label === '') {
                $label = 'Time slot ' . ($index + 1);
            }

            $intervals[] = [
                'label' => $label,
                'start' => $start,
                'end' => $end,
                'start_label' => date('g:i A', strtotime($start)),
                'end_label' => date('g:i A', strtotime($end)),
            ];
        }

        $count = count($intervals);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $left = $intervals[$i];
                $right = $intervals[$j];
                if ($left['start'] < $right['end'] && $right['start'] < $left['end']) {
                    return sprintf(
                        '%s (%s–%s) overlaps with %s (%s–%s). A period must end before the next one starts.',
                        $left['label'],
                        $left['start_label'],
                        $left['end_label'],
                        $right['label'],
                        $right['start_label'],
                        $right['end_label']
                    );
                }
            }
        }

        return null;
    }

    /**
     * @return SchoolGrade[]
     */
    public function findGradesForDepartment(int $departmentId): array
    {
        return SchoolGrade::find()
            ->where(['school_department_id' => $departmentId])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();
    }

    /**
     * @return SchoolSection[]
     */
    public function findSectionsForGrade(int $gradeId): array
    {
        return SchoolSection::find()
            ->where(['school_grade_id' => $gradeId])
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }

    /**
     * @return array{
     *   teacher_name: string,
     *   department_name: string,
     *   entries: array<int, array<string, mixed>>,
     *   by_day: array<int, array<int, array<string, mixed>>>
     * }
     */
    public function buildInstructorSchedulePayload(Employee $employee): array
    {
        if (!SchoolSectionTimetableSlot::isEnabled()) {
            throw new \RuntimeException('Timetable tables are not installed. Run: php yii migrate/up');
        }

        $departmentId = (int)$employee->school_department_id;
        $employeeId = (int)$employee->id;
        $departmentName = '';
        if ($departmentId > 0) {
            $departmentName = trim((string)(SchoolDepartment::find()->select(['name'])->where(['id' => $departmentId])->scalar() ?: ''));
        }

        if ($departmentId <= 0 || $employeeId <= 0) {
            return [
                'teacher_name' => trim((string)$employee->full_name),
                'department_name' => $departmentName,
                'entries' => [],
                'by_day' => [],
                'rows' => [],
                'columns' => [],
                'cells' => [],
            ];
        }

        $slots = SchoolSectionTimetableSlot::find()
            ->alias('slot')
            ->innerJoinWith([
                'column' => static function ($query) {
                    $query->andWhere(['is_break' => false, 'is_exam' => false]);
                },
                'row',
                'section.grade',
            ])
            ->joinWith(['material.subject'])
            ->andWhere([SchoolGrade::tableName() . '.school_department_id' => $departmentId])
            ->andWhere([
                'or',
                ['slot.instructor_id' => $employeeId],
                [SchoolSectionMaterial::tableName() . '.instructor_id' => $employeeId],
            ])
            ->all();

        $entries = [];
        foreach ($slots as $slot) {
            if (!$slot instanceof SchoolSectionTimetableSlot) {
                continue;
            }

            $row = $slot->row;
            $column = $slot->column;
            $section = $slot->section;
            $material = $slot->material;
            if ($row === null || $column === null || $section === null) {
                continue;
            }

            $slotInstructorId = (int)($slot->instructor_id ?? 0);
            $materialInstructorId = (int)($material?->instructor_id ?? 0);
            if ($slotInstructorId !== $employeeId && $materialInstructorId !== $employeeId) {
                continue;
            }

            $gradeName = trim((string)($section->grade?->name ?? ''));
            $sectionName = trim((string)$section->name);
            $classLabel = $this->formatInstructorClassLabel($gradeName, $sectionName);

            $columnKey = substr((string)$column->start_time, 0, 5) . '|' . substr((string)$column->end_time, 0, 5) . '|' . $column->getDisplayLabel();

            $entries[] = [
                'id' => (int)$slot->id,
                'day_of_week' => (int)$row->day_of_week,
                'day_label' => $row->getDisplayLabel(),
                'period_label' => $column->getDisplayLabel(),
                'time_range' => $column->getTimeRangeLabel(),
                'start_time' => substr((string)$column->start_time, 0, 5),
                'end_time' => substr((string)$column->end_time, 0, 5),
                'column_key' => $columnKey,
                'subject_name' => trim((string)($material?->subject?->name ?? $material?->name ?? 'Subject')),
                'class_label' => $classLabel,
                'grade_name' => $gradeName,
                'section_name' => $sectionName,
                'room_number' => trim((string)($slot->room_number ?? $section->room_number ?? '')),
                'lesson_brief' => $this->formatLessonBrief($slot->lesson_brief ?? null),
                'section_id' => (int)$section->id,
            ];
        }

        usort($entries, static function (array $left, array $right): int {
            $dayCompare = (int)$left['day_of_week'] <=> (int)$right['day_of_week'];
            if ($dayCompare !== 0) {
                return $dayCompare;
            }

            return strcmp((string)$left['start_time'], (string)$right['start_time']);
        });

        $byDay = [];
        foreach ($entries as $entry) {
            $byDay[(int)$entry['day_of_week']][] = $entry;
        }

        $grid = $this->buildInstructorScheduleGrid($entries);

        return [
            'teacher_name' => trim((string)$employee->full_name),
            'department_name' => $departmentName,
            'entries' => $entries,
            'by_day' => $byDay,
            'rows' => $grid['rows'],
            'columns' => $grid['columns'],
            'cells' => $grid['cells'],
        ];
    }

    private function formatInstructorClassLabel(string $gradeName, string $sectionName): string
    {
        if ($gradeName !== '' && $sectionName !== '') {
            $sectionDisplay = preg_match('/^section\s/i', $sectionName) === 1
                ? $sectionName
                : 'Section ' . $sectionName;

            return $gradeName . ' · ' . $sectionDisplay;
        }

        if ($gradeName !== '') {
            return $gradeName;
        }

        return $sectionName !== '' ? $sectionName : '—';
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @return array{rows: array<int, array<string, mixed>>, columns: array<int, array<string, mixed>>, cells: array<string, array<string, mixed>>}
     */
    private function buildInstructorScheduleGrid(array $entries): array
    {
        $columnMap = [];
        $rowMap = [];
        $cells = [];

        foreach ($entries as $entry) {
            $dayOfWeek = (int)($entry['day_of_week'] ?? 0);
            $columnKey = (string)($entry['column_key'] ?? '');
            if ($dayOfWeek <= 0 || $columnKey === '') {
                continue;
            }

            if (!isset($columnMap[$columnKey])) {
                $columnMap[$columnKey] = [
                    'key' => $columnKey,
                    'period_label' => (string)($entry['period_label'] ?? ''),
                    'time_range' => (string)($entry['time_range'] ?? ''),
                    'start_time' => (string)($entry['start_time'] ?? ''),
                ];
            }

            $rowMap[$dayOfWeek] = (string)($entry['day_label'] ?? ('Day ' . $dayOfWeek));
            $cells[$dayOfWeek . ':' . $columnKey] = $entry;
        }

        $columns = array_values($columnMap);
        usort($columns, static fn(array $left, array $right): int => strcmp(
            (string)($left['start_time'] ?? ''),
            (string)($right['start_time'] ?? '')
        ));

        $dayNames = self::dayNameMap();
        $rows = [];
        foreach (self::defaultSchoolWeekDayOrder() as $dayOfWeek) {
            $rows[] = [
                'day_of_week' => $dayOfWeek,
                'day_label' => $rowMap[$dayOfWeek] ?? ($dayNames[$dayOfWeek] ?? 'Day'),
            ];
        }

        return [
            'rows' => $rows,
            'columns' => $columns,
            'cells' => $cells,
        ];
    }
}
