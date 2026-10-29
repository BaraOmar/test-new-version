<?php

namespace common\modules\employee\services;

use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolSectionMaterial;

class SchoolMyClassesService
{
    public static function isEnabled(): bool
    {
        return SchoolDepartment::isEnabled()
            && SchoolSection::isEnabled();
    }

    /**
     * @param int[] $departmentIds
     * @param int[]|null $sectionIds When set, only these sections are included.
     * @return SchoolDepartment[]
     */
    public function findDepartmentsWithSections(array $departmentIds, ?array $sectionIds = null): array
    {
        if ($departmentIds === [] || !self::isEnabled()) {
            return [];
        }

        if ($sectionIds !== null && $sectionIds === []) {
            return [];
        }

        return SchoolDepartment::find()
            ->with([
                'grades' => static function ($query) {
                    $query->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC]);
                },
                'grades.sections' => static function ($query) use ($sectionIds) {
                    $query->with(['leadTeacher', 'materials.subject'])
                        ->orderBy(['name' => SORT_ASC]);
                    if ($sectionIds !== null) {
                        $query->andWhere(['id' => $sectionIds]);
                    }
                },
            ])
            ->where(['id' => $departmentIds])
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSectionCard(SchoolSection $section): array
    {
        $gradeName = trim((string)($section->grade?->name ?? ''));
        $sectionName = trim((string)$section->name);
        $mentorName = trim((string)($section->leadTeacher?->full_name ?? ''));
        $materials = SchoolSectionMaterial::isEnabled() ? $section->materials : [];
        $subjectLabel = $this->resolvePrimarySubjectLabel($materials);

        return [
            'id' => (int)$section->id,
            'grade_name' => $gradeName,
            'section_name' => $sectionName,
            'title' => $gradeName !== '' ? $gradeName . ' · ' . $sectionName : $sectionName,
            'mentor_name' => $mentorName !== '' ? $mentorName : 'Not assigned',
            'student_count' => (int)$section->current_enrollment,
            'course_count' => count($materials),
            'subject_label' => $subjectLabel,
            'room_number' => trim((string)($section->room_number ?? '')),
        ];
    }

    /**
     * @param SchoolSectionMaterial[] $materials
     */
    private function resolvePrimarySubjectLabel(array $materials): string
    {
        if ($materials === []) {
            return 'No courses assigned';
        }

        if (count($materials) === 1) {
            $material = $materials[0];
            $subjectName = trim((string)($material->subject?->name ?? ''));
            $materialName = trim((string)$material->name);

            return $subjectName !== '' ? $subjectName : ($materialName !== '' ? $materialName : 'Assigned course');
        }

        $first = $materials[0];
        $firstName = trim((string)($first->subject?->name ?? $first->name ?? ''));

        return $firstName !== ''
            ? $firstName . ' +' . (count($materials) - 1) . ' more'
            : count($materials) . ' courses';
    }
}
