<?php

namespace common\modules\employee\services;

use common\models\User;
use common\modules\employee\models\SchoolStudent;
use Yii;

class StudentPortalEmailService
{
    /**
     * Build a unique portal email from student name parts and student ID.
     *
     * Format: first.father.family.{id}@students.culturehr.local
     * Numeric suffixes are appended only if that address already exists.
     */
    public function generateUniquePortalEmail(SchoolStudent $student, ?int $excludeUserId = null): string
    {
        $baseLocalPart = $this->buildLocalPart($student);
        $domain = $this->getDomain();

        $candidate = $baseLocalPart . '@' . $domain;
        if (!$this->emailExists($candidate, $excludeUserId)) {
            return $candidate;
        }

        for ($suffix = 2; $suffix <= 99; $suffix++) {
            $candidate = $baseLocalPart . '.' . $suffix . '@' . $domain;
            if (!$this->emailExists($candidate, $excludeUserId)) {
                return $candidate;
            }
        }

        return $baseLocalPart . '.' . uniqid('', false) . '@' . $domain;
    }

    public function buildLocalPart(SchoolStudent $student): string
    {
        $segments = [];

        if (SchoolStudent::hasNameParts()) {
            foreach (['first_name', 'father_name', 'family_name'] as $attribute) {
                $segment = $this->slugSegment((string)($student->$attribute ?? ''));
                if ($segment !== '') {
                    $segments[] = $segment;
                }
            }
        } else {
            $segment = $this->slugSegment((string)($student->full_name ?? ''));
            if ($segment !== '') {
                $segments[] = $segment;
            }
        }

        if ($segments === []) {
            $segments[] = 'student';
        }

        $studentId = (int)($student->id ?? 0);
        if ($studentId > 0) {
            $segments[] = (string)$studentId;
        }

        return $this->trimLocalPart(implode('.', $segments));
    }

    public function getDomain(): string
    {
        $domain = trim((string)(Yii::$app->params['studentPortalEmailDomain'] ?? 'students.culturehr.local'));

        return $domain !== '' ? $domain : 'students.culturehr.local';
    }

    private function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $existing = User::findByEmail($email);

        return $existing !== null && (int)$existing->id !== (int)$excludeUserId;
    }

    private function slugSegment(string $value): string
    {
        $value = mb_strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^\p{L}\p{N}]+/u', '-', $value) ?? '';
        $value = trim($value, '-');
        $value = preg_replace('/-+/', '-', $value) ?? '';

        return $value;
    }

    private function trimLocalPart(string $localPart): string
    {
        $localPart = strtolower(trim($localPart, '.-'));
        if (strlen($localPart) > 200) {
            $localPart = substr($localPart, 0, 200);
            $localPart = rtrim($localPart, '.-');
        }

        return $localPart !== '' ? $localPart : 'student';
    }
}
