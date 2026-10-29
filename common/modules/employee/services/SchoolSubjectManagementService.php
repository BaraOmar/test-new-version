<?php

namespace common\modules\employee\services;

use common\modules\employee\models\Employee;
use common\modules\employee\models\EmployeeSchoolSubject;
use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSectionMaterial;
use common\modules\employee\models\SchoolStudent;
use common\modules\employee\models\SchoolSubject;
use common\modules\employee\models\SchoolSubjectGrade;

class SchoolSubjectManagementService
{
    public const DEFAULT_SUBJECT_CREDITS = 1;

    /**
     * @return array<int, array<int, array{id:int,name:string}>>
     */
    public function loadGradesByDepartment(): array
    {
        $gradesByDepartment = [];
        foreach (SchoolGrade::find()->orderBy(['school_department_id' => SORT_ASC, 'sort_order' => SORT_ASC, 'name' => SORT_ASC])->all() as $grade) {
            $departmentId = (int)$grade->school_department_id;
            $gradesByDepartment[$departmentId][] = [
                'id' => (int)$grade->id,
                'name' => (string)$grade->name,
            ];
        }

        return $gradesByDepartment;
    }

    /**
     * @return array{savedCount:int,errors:string[]}
     */
    public function saveSubjectGradeAssignments(
        SchoolSubject $subject,
        int $departmentId,
        array $selectedGradeIds,
        array $gradeCredits,
        array $gradeMaxScores = []
    ): array {
        $errors = [];
        $savedCount = 0;

        foreach ($selectedGradeIds as $gradeId) {
            if ($gradeId <= 0) {
                continue;
            }

            $grade = SchoolGrade::findOne(['id' => $gradeId, 'school_department_id' => $departmentId]);
            if ($grade === null) {
                $errors[] = 'One or more selected grades are invalid for this department.';
                continue;
            }

            $subjectGrade = SchoolSubjectGrade::findOne([
                'school_subject_id' => (int)$subject->id,
                'school_grade_id' => $gradeId,
            ]) ?? new SchoolSubjectGrade([
                'school_subject_id' => (int)$subject->id,
                'school_grade_id' => $gradeId,
            ]);

            $credits = trim((string)($gradeCredits[$gradeId] ?? $gradeCredits[(string)$gradeId] ?? ''));
            if ($credits === '' || !is_numeric($credits) || (float)$credits <= 0) {
                $subjectGrade->credits = self::DEFAULT_SUBJECT_CREDITS;
            } else {
                $subjectGrade->credits = (float)$credits;
            }

            if (SchoolSubjectGrade::hasColumn('max_score')) {
                $maxScoreRaw = trim((string)($gradeMaxScores[$gradeId] ?? $gradeMaxScores[(string)$gradeId] ?? ''));
                if ($maxScoreRaw === '') {
                    $errors[] = 'Enter a maximum mark for each selected grade.';
                    continue;
                }
                if (!is_numeric($maxScoreRaw) || (float)$maxScoreRaw <= 0) {
                    $errors[] = 'Enter a valid maximum mark for each selected grade.';
                    continue;
                }
                $subjectGrade->max_score = (float)$maxScoreRaw;
            }

            if (!$subjectGrade->save()) {
                $errors[] = implode(' ', $subjectGrade->getFirstErrors()) ?: 'Unable to save grade assignment.';
                continue;
            }

            $savedCount++;
        }

        return [
            'savedCount' => $savedCount,
            'errors' => $errors,
        ];
    }

    /**
     * @return SchoolSectionMaterial[]
     */
    public function findStudentSectionMaterials(SchoolStudent $student): array
    {
        if (!SchoolSectionMaterial::isEnabled() || (int)$student->school_section_id <= 0) {
            return [];
        }

        return SchoolSectionMaterial::find()
            ->with(['subject', 'instructor'])
            ->where(['school_section_id' => (int)$student->school_section_id])
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }

    /**
     * @return EmployeeSchoolSubject[]
     */
    public function findTeacherSubjectAssignments(Employee $employee): array
    {
        if (!EmployeeSchoolSubject::isEnabled()) {
            return [];
        }

        return EmployeeSchoolSubject::find()
            ->with(['subject.department'])
            ->where(['employee_id' => (int)$employee->id])
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }

    /**
     * @return SchoolSectionMaterial[]
     */
    public function findTeacherSectionMaterials(Employee $employee): array
    {
        if (!SchoolSectionMaterial::isEnabled()) {
            return [];
        }

        $query = SchoolSectionMaterial::find()
            ->alias('material')
            ->with(['section.grade.department', 'subject'])
            ->innerJoinWith(['section section'])
            ->innerJoinWith(['section.grade grade'])
            ->where(['material.instructor_id' => (int)$employee->id])
            ->orderBy(['grade.name' => SORT_ASC, 'section.name' => SORT_ASC, 'material.name' => SORT_ASC]);

        if (Employee::hasColumn('school_department_id') && (int)($employee->school_department_id ?? 0) > 0) {
            $query->andWhere(['grade.school_department_id' => (int)$employee->school_department_id]);
        }

        return $query->all();
    }

    /**
     * @param int[] $departmentIds
     * @return SchoolSubject[]
     */
    public function findDepartmentSubjects(array $departmentIds): array
    {
        if ($departmentIds === []) {
            return [];
        }

        return SchoolSubject::find()
            ->with(['department', 'subjectGrades.grade'])
            ->where(['school_department_id' => $departmentIds])
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }
}
