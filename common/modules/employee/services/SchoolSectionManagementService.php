<?php

namespace common\modules\employee\services;

use common\modules\employee\models\Employee;
use common\modules\employee\models\EmployeeSchoolSubject;
use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolSectionMaterial;
use common\modules\employee\models\SchoolStudent;
use common\modules\employee\models\SchoolSubject;
use common\modules\employee\models\SchoolSubjectGrade;
use yii\web\NotFoundHttpException;

class SchoolSectionManagementService
{
    public function findSection(int $id): SchoolSection
    {
        $with = ['grade.department', 'leadTeacher'];
        if (SchoolSectionMaterial::isEnabled()) {
            $with[] = 'materials.instructor';
            $with[] = 'materials.subject';
        }
        if (SchoolStudent::isEnabled()) {
            $with[] = 'students';
        }

        $section = SchoolSection::find()->with($with)->where(['id' => $id])->one();
        if ($section === null) {
            throw new NotFoundHttpException('Section not found.');
        }

        return $section;
    }

    /**
     * @return Employee[]
     */
    public function findTeachersForSchoolDepartment(?int $departmentId, array $includeEmployeeIds = []): array
    {
        if ($departmentId === null || $departmentId <= 0) {
            return [];
        }

        $query = Employee::find()->orderBy(['full_name' => SORT_ASC]);
        if (Employee::hasColumn('status')) {
            $query->andWhere(['status' => 'Active']);
        }
        if (Employee::hasColumn('teacher')) {
            $query->andWhere(['teacher' => 1]);
        }

        if (!Employee::hasColumn('school_department_id')) {
            return [];
        }

        $teachers = $query->andWhere(['school_department_id' => $departmentId])->all();

        return $this->mergeTeachersById($teachers, $includeEmployeeIds);
    }

    /**
     * @param Employee[] $teachers
     * @param int[] $employeeIds
     * @return Employee[]
     */
    public function mergeTeachersById(array $teachers, array $employeeIds): array
    {
        $employeeIds = array_values(array_unique(array_filter(array_map('intval', $employeeIds))));
        if ($employeeIds === []) {
            return $teachers;
        }

        $existingIds = array_map(static fn(Employee $teacher) => (int)$teacher->id, $teachers);
        $missingIds = array_values(array_diff($employeeIds, $existingIds));
        if ($missingIds === []) {
            return $teachers;
        }

        $extra = Employee::find()
            ->where(['id' => $missingIds])
            ->orderBy(['full_name' => SORT_ASC])
            ->all();

        return array_values(array_merge($teachers, $extra));
    }

    /**
     * @param Employee[] $departmentTeachers
     * @param Employee[] $extraTeachers
     * @return array<int, array<int, array{id:int,label:string}>>
     */
    public function buildInstructorsBySubject(?int $departmentId, array $departmentTeachers, array $extraTeachers = []): array
    {
        if ($departmentId === null || $departmentId <= 0) {
            return [];
        }

        $formatLabel = static function (Employee $teacher): string {
            $title = trim((string)($teacher->job_title ?? ''));

            return $title !== '' ? $teacher->full_name . ' (' . $title . ')' : $teacher->full_name;
        };

        $teacherById = [];
        foreach (array_merge($departmentTeachers, $extraTeachers) as $teacher) {
            $teacherById[(int)$teacher->id] = $teacher;
        }

        $instructorsBySubject = [];
        $appendInstructor = static function (int $subjectId, int $employeeId, string $label) use (&$instructorsBySubject): void {
            if (!isset($instructorsBySubject[$subjectId])) {
                $instructorsBySubject[$subjectId] = [];
            }
            foreach ($instructorsBySubject[$subjectId] as $item) {
                if ((int)$item['id'] === $employeeId) {
                    return;
                }
            }
            $instructorsBySubject[$subjectId][] = ['id' => $employeeId, 'label' => $label];
        };

        if (!EmployeeSchoolSubject::isEnabled()) {
            foreach (SchoolSubject::find()->where(['school_department_id' => $departmentId])->orderBy(['name' => SORT_ASC])->all() as $subject) {
                foreach ($departmentTeachers as $teacher) {
                    $appendInstructor((int)$subject->id, (int)$teacher->id, $formatLabel($teacher));
                }
            }

            return $instructorsBySubject;
        }

        $assignments = EmployeeSchoolSubject::find()
            ->alias('es')
            ->innerJoinWith(['subject subject', 'employee employee'])
            ->where(['subject.school_department_id' => $departmentId])
            ->orderBy(['employee.full_name' => SORT_ASC])
            ->all();

        foreach ($assignments as $assignment) {
            $employee = $assignment->employee;
            if ($employee === null) {
                continue;
            }

            $employeeId = (int)$employee->id;
            if ((int)($employee->school_department_id ?? 0) !== $departmentId) {
                continue;
            }
            if (Employee::hasColumn('teacher') && (int)$employee->teacher !== 1) {
                continue;
            }
            if (Employee::hasColumn('status') && strcasecmp((string)$employee->status, 'Active') !== 0) {
                continue;
            }

            $appendInstructor(
                (int)$assignment->school_subject_id,
                $employeeId,
                $formatLabel($teacherById[$employeeId] ?? $employee)
            );
        }

        return $instructorsBySubject;
    }

    /**
     * @return int[]
     */
    public function findAssignedClassTeacherIds(?int $excludeSectionId = null): array
    {
        $query = SchoolSection::find()
            ->select('lead_teacher_id')
            ->andWhere(['not', ['lead_teacher_id' => null]]);

        if ($excludeSectionId !== null && $excludeSectionId > 0) {
            $query->andWhere(['<>', 'id', $excludeSectionId]);
        }

        return array_values(array_unique(array_map('intval', $query->column())));
    }

    /**
     * @return Employee[]
     */
    public function findAvailableClassTeachers(?int $departmentId, ?int $excludeSectionId, array $includeEmployeeIds = []): array
    {
        $teachers = $this->findTeachersForSchoolDepartment($departmentId);
        $assignedIds = $this->findAssignedClassTeacherIds($excludeSectionId);
        if ($assignedIds !== []) {
            $teachers = array_values(array_filter(
                $teachers,
                static fn(Employee $teacher) => !in_array((int)$teacher->id, $assignedIds, true)
            ));
        }

        return $this->mergeTeachersById($teachers, $includeEmployeeIds);
    }

    /**
     * @return array<int, array{id:int,name:string,credits:float|null}>
     */
    public function findSubjectCatalogForSection(SchoolSection $section): array
    {
        $gradeId = (int)($section->school_grade_id ?? $section->grade?->id ?? 0);
        $departmentId = (int)($section->grade?->school_department_id ?? 0);
        if ($gradeId <= 0 || $departmentId <= 0) {
            return [];
        }

        if (SchoolSubjectGrade::isEnabled()) {
            $catalog = [];
            foreach (SchoolSubjectGrade::find()
                ->alias('sg')
                ->innerJoinWith(['subject subject'])
                ->where([
                    'sg.school_grade_id' => $gradeId,
                    'subject.school_department_id' => $departmentId,
                ])
                ->orderBy(['subject.name' => SORT_ASC])
                ->all() as $subjectGrade) {
                $catalog[] = [
                    'id' => (int)$subjectGrade->subject->id,
                    'name' => (string)$subjectGrade->subject->name,
                    'credits' => (float)$subjectGrade->credits,
                ];
            }

            return $catalog;
        }

        return array_map(
            static fn(SchoolSubject $subject) => [
                'id' => (int)$subject->id,
                'name' => (string)$subject->name,
                'credits' => null,
            ],
            SchoolSubject::find()
                ->where(['school_department_id' => $departmentId])
                ->orderBy(['name' => SORT_ASC])
                ->all()
        );
    }

    /**
     * @return SchoolStudent[]
     */
    public function findEligibleStudentsForSection(SchoolSection $section): array
    {
        if (!SchoolStudent::isEnabled()) {
            return [];
        }

        $gradeId = (int)$section->school_grade_id;
        if ($gradeId <= 0) {
            return [];
        }

        $query = SchoolStudent::find()
            ->alias('student')
            ->with('grade')
            ->where(['student.school_grade_id' => $gradeId])
            ->andWhere(['student.school_section_id' => null])
            ->orderBy(['student.full_name' => SORT_ASC]);

        $departmentId = (int)($section->grade?->school_department_id ?? 0);
        if ($departmentId > 0) {
            $query->innerJoinWith(['grade grade' => static function ($gradeQuery) use ($departmentId) {
                $gradeQuery->andWhere(['grade.school_department_id' => $departmentId]);
            }], false);
        }

        return $query->all();
    }

    public function assignStudentToSection(SchoolSection $section, int $studentId, array &$errors): bool
    {
        $student = SchoolStudent::find()->with('grade')->where(['id' => $studentId])->one();
        if ($student === null) {
            $errors[] = 'Student not found.';

            return false;
        }

        $gradeId = (int)$section->school_grade_id;
        if (SchoolStudent::hasColumn('school_grade_id') && (int)$student->school_grade_id !== $gradeId) {
            $errors[] = 'Student is not enrolled in this grade.';

            return false;
        }

        $departmentId = (int)($section->grade?->school_department_id ?? 0);
        if ($departmentId > 0 && (int)($student->grade?->school_department_id ?? 0) !== $departmentId) {
            $errors[] = 'Student does not belong to this department.';

            return false;
        }

        if ((int)$student->school_section_id === (int)$section->id) {
            return true;
        }

        if ($student->school_section_id !== null && (int)$student->school_section_id > 0) {
            $errors[] = 'Student is already assigned to another section.';

            return false;
        }

        $student->school_section_id = (int)$section->id;
        if (!$student->save(false, ['school_section_id', 'updated_at'])) {
            $errors[] = implode(' ', $student->getFirstErrors()) ?: 'Unable to assign student.';

            return false;
        }

        $section->syncEnrollmentCount();
        $section->save(false, ['current_enrollment', 'updated_at']);

        return true;
    }

    public function removeStudentFromSection(int $sectionId, int $studentId): bool
    {
        $student = SchoolStudent::findOne(['id' => $studentId, 'school_section_id' => $sectionId]);
        if ($student === null) {
            return false;
        }

        $student->school_section_id = null;
        if (!$student->save(false, ['school_section_id', 'updated_at'])) {
            return false;
        }

        $section = SchoolSection::findOne($sectionId);
        if ($section !== null) {
            $section->syncEnrollmentCount();
            $section->save(false, ['current_enrollment', 'updated_at']);
        }

        return true;
    }

    public function populateAndSaveMaterial(SchoolSectionMaterial $model, array $row, bool $isNew = false): bool
    {
        if ($isNew || array_key_exists('school_subject_id', $row)) {
            $model->school_subject_id = !empty($row['school_subject_id']) ? (int)$row['school_subject_id'] : null;
        }
        if (array_key_exists('name', $row)) {
            $model->name = trim((string)$row['name']);
        }
        if (array_key_exists('instructor_id', $row)) {
            $model->instructor_id = !empty($row['instructor_id']) ? (int)$row['instructor_id'] : null;
        }
        if (array_key_exists('schedule', $row)) {
            $schedule = trim((string)$row['schedule']);
            $model->schedule = $schedule !== '' ? $schedule : null;
        }
        if (array_key_exists('status', $row)) {
            $status = (string)$row['status'];
            $model->status = in_array($status, [SchoolSectionMaterial::STATUS_ACTIVE, SchoolSectionMaterial::STATUS_PENDING], true)
                ? $status
                : SchoolSectionMaterial::STATUS_ACTIVE;
        }

        if ($model->school_subject_id) {
            $subject = SchoolSubject::findOne(['id' => (int)$model->school_subject_id]);
            if ($subject !== null) {
                if (trim($model->name) === '') {
                    $model->name = (string)$subject->name;
                }
                $model->icon = $this->iconForSubjectName($subject->name);
            }
        }

        if (trim($model->name) === '') {
            $model->addError('name', 'Material name is required.');

            return false;
        }

        return $model->save();
    }

    public function iconForSubjectName(string $name): string
    {
        $normalized = strtolower(trim($name));
        if (str_contains($normalized, 'calc') || str_contains($normalized, 'math')) {
            return 'functions';
        }
        if (str_contains($normalized, 'science') || str_contains($normalized, 'bio') || str_contains($normalized, 'chem')) {
            return 'science';
        }
        if (str_contains($normalized, 'english') || str_contains($normalized, 'literature')) {
            return 'menu_book';
        }
        if (str_contains($normalized, 'art') || str_contains($normalized, 'music')) {
            return 'palette';
        }

        return 'menu_book';
    }

    /**
     * @return array{teachers:Employee[],instructorTeachers:Employee[],classTeachers:Employee[],subjects:array,instructorsBySubject:array}
     */
    public function buildSectionEditorData(SchoolSection $section): array
    {
        $department = $section->grade?->department;
        $departmentId = $department ? (int)$department->id : null;
        $assignedInstructorIds = [];
        if ($section->lead_teacher_id) {
            $assignedInstructorIds[] = (int)$section->lead_teacher_id;
        }
        foreach ($section->materials as $material) {
            if ($material->instructor_id) {
                $assignedInstructorIds[] = (int)$material->instructor_id;
            }
        }
        $teachers = $this->findTeachersForSchoolDepartment($departmentId);
        $instructorTeachers = $this->mergeTeachersById($teachers, $assignedInstructorIds);
        $classTeachers = $this->findAvailableClassTeachers($departmentId, (int)$section->id, $assignedInstructorIds);
        $subjectCatalog = $this->findSubjectCatalogForSection($section);
        $instructorsBySubject = $this->buildInstructorsBySubject($departmentId, $teachers, $instructorTeachers);

        return [
            'teachers' => $teachers,
            'instructorTeachers' => $instructorTeachers,
            'classTeachers' => $classTeachers,
            'subjects' => $subjectCatalog,
            'instructorsBySubject' => $instructorsBySubject,
        ];
    }
}
