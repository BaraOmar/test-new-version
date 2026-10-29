<?php

namespace common\modules\finance\services;

use common\modules\employee\models\SchoolStudent;
use common\modules\finance\models\FinanceAccountParentChild;
use common\modules\finance\models\FinanceAccountParentProfile;

class FinanceParentEnrollmentService
{
    /**
     * @return array<int, array{
     *     id:int,
     *     label:string,
     *     email:string,
     *     phone:string,
     *     emergency_phone:string,
     *     guardian_name:string,
     *     children:array<int, array{
     *         id:int,
     *         label:string,
     *         first_name:string,
     *         father_name:string,
     *         grandfather_name:string,
     *         family_name:string
     *     }>
     * }>
     */
    public function buildEnrollmentOptions(): array
    {
        if (!FinanceAccountParentProfile::isEnabled() || !FinanceAccountParentChild::isEnabled()) {
            return [];
        }

        $enrolledKeys = $this->buildEnrolledStudentNameKeys();

        $profiles = FinanceAccountParentProfile::find()
            ->with(['children'])
            ->orderBy(['family_name' => SORT_ASC, 'first_name' => SORT_ASC])
            ->all();

        $options = [];
        foreach ($profiles as $profile) {
            if (!$profile instanceof FinanceAccountParentProfile) {
                continue;
            }

            $children = [];
            foreach ($profile->children as $child) {
                if (!$child instanceof FinanceAccountParentChild) {
                    continue;
                }

                if ($this->isChildAlreadyRegistered($child, $enrolledKeys)) {
                    continue;
                }

                $childLabel = $child->getFullName();
                if ($childLabel === '') {
                    $childLabel = 'Child ' . (int)$child->child_index;
                }

                $children[] = [
                    'id' => (int)$child->id,
                    'label' => $childLabel,
                    'first_name' => (string)$child->first_name,
                    'father_name' => (string)($child->second_name ?? ''),
                    'grandfather_name' => (string)($child->third_name ?? ''),
                    'family_name' => (string)$child->family_name,
                ];
            }

            if ($children === []) {
                continue;
            }

            $options[] = [
                'id' => (int)$profile->id,
                'label' => $profile->getFullName(),
                'email' => (string)($profile->email ?? ''),
                'phone' => (string)$profile->phone,
                'emergency_phone' => (string)$profile->emergency_phone_2,
                'guardian_name' => $profile->getFullName(),
                'children' => $children,
            ];
        }

        return $options;
    }

    public function linkChildToStudent(int $childId, int $studentId): void
    {
        if ($childId <= 0 || $studentId <= 0 || !FinanceAccountParentChild::isEnabled()) {
            return;
        }

        $child = FinanceAccountParentChild::findOne($childId);
        if ($child === null || !$child->hasAttribute('school_student_id')) {
            return;
        }

        $child->school_student_id = $studentId;
        $child->save(false, ['school_student_id', 'updated_at']);
    }

    /**
     * @param array<string, true> $enrolledKeys
     */
    private function isChildAlreadyRegistered(FinanceAccountParentChild $child, array $enrolledKeys): bool
    {
        if ($child->hasAttribute('school_student_id') && (int)($child->school_student_id ?? 0) > 0) {
            return true;
        }

        $key = $this->normalizeNameKey(
            (string)$child->first_name,
            (string)($child->second_name ?? ''),
            (string)($child->third_name ?? ''),
            (string)$child->family_name
        );

        return $key !== '' && isset($enrolledKeys[$key]);
    }

    /**
     * @return array<string, true>
     */
    private function buildEnrolledStudentNameKeys(): array
    {
        if (!SchoolStudent::hasNameParts()) {
            return [];
        }

        $keys = [];
        $students = SchoolStudent::find()
            ->select(['first_name', 'father_name', 'grandfather_name', 'family_name'])
            ->asArray()
            ->all();

        foreach ($students as $student) {
            $key = $this->normalizeNameKey(
                (string)($student['first_name'] ?? ''),
                (string)($student['father_name'] ?? ''),
                (string)($student['grandfather_name'] ?? ''),
                (string)($student['family_name'] ?? '')
            );
            if ($key !== '') {
                $keys[$key] = true;
            }
        }

        return $keys;
    }

    private function normalizeNameKey(string $first, string $second, string $third, string $family): string
    {
        $parts = array_map(
            static fn(string $value): string => mb_strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? '')),
            [$first, $second, $third, $family]
        );

        if ($parts[0] === '' || $parts[3] === '') {
            return '';
        }

        return implode('|', $parts);
    }
}
