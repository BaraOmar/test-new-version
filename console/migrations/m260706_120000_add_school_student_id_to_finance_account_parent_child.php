<?php

use yii\db\Migration;
use yii\db\Query;

class m260706_120000_add_school_student_id_to_finance_account_parent_child extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_account_parent_child}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('school_student_id') !== null) {
            return;
        }

        $this->addColumn($table, 'school_student_id', $this->integer()->null()->after('family_name'));
        $this->createIndex('idx_finance_account_parent_child_student', $table, 'school_student_id');

        if ($this->db->schema->getTableSchema('{{%school_student}}', true) !== null) {
            $this->addForeignKey(
                'fk_finance_account_parent_child_student',
                $table,
                'school_student_id',
                '{{%school_student}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        $this->backfillStudentLinks();
    }

    public function safeDown()
    {
        $table = '{{%finance_account_parent_child}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('school_student_id') === null) {
            return;
        }

        $this->dropForeignKey('fk_finance_account_parent_child_student', $table);
        $this->dropIndex('idx_finance_account_parent_child_student', $table);
        $this->dropColumn($table, 'school_student_id');
    }

    private function backfillStudentLinks(): void
    {
        if ($this->db->schema->getTableSchema('{{%school_student}}', true) === null) {
            return;
        }

        $children = (new Query())
            ->from('{{%finance_account_parent_child}}')
            ->select([
                'id',
                'parent_profile_id',
                'first_name',
                'second_name',
                'third_name',
                'family_name',
            ])
            ->where(['school_student_id' => null])
            ->all($this->db);

        foreach ($children as $child) {
            $studentId = $this->matchStudentId($child);
            if ($studentId === null) {
                continue;
            }

            $this->update(
                '{{%finance_account_parent_child}}',
                ['school_student_id' => $studentId],
                ['id' => (int)$child['id']]
            );
        }
    }

    /**
     * @param array<string, mixed> $child
     */
    private function matchStudentId(array $child): ?int
    {
        $profile = (new Query())
            ->from('{{%finance_account_parent_profile}}')
            ->select(['phone', 'emergency_phone_2', 'first_name', 'family_name'])
            ->where(['id' => (int)$child['parent_profile_id']])
            ->one($this->db);

        $students = (new Query())
            ->from('{{%school_student}}')
            ->select(['id', 'full_name', 'first_name', 'father_name', 'grandfather_name', 'family_name', 'guardian_name', 'guardian_phone'])
            ->where(['status' => 'active'])
            ->all($this->db);

        $childFirst = mb_strtolower(trim((string)($child['first_name'] ?? '')));
        $childFamily = mb_strtolower(trim((string)($child['family_name'] ?? '')));
        $phones = [];
        if (is_array($profile)) {
            foreach (['phone', 'emergency_phone_2'] as $key) {
                $normalized = $this->normalizePhone((string)($profile[$key] ?? ''));
                if ($normalized !== '') {
                    $phones[] = $normalized;
                }
            }
        }

        foreach ($students as $student) {
            $studentId = (int)$student['id'];
            $studentPhone = $this->normalizePhone((string)($student['guardian_phone'] ?? ''));
            foreach ($phones as $phone) {
                if ($studentPhone !== '' && ($studentPhone === $phone || str_ends_with($studentPhone, $phone) || str_ends_with($phone, $studentPhone))) {
                    if ($this->childMatchesStudent($child, $student)) {
                        return $studentId;
                    }
                }
            }
        }

        foreach ($students as $student) {
            if ($this->childMatchesStudent($child, $student)) {
                return (int)$student['id'];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $child
     * @param array<string, mixed> $student
     */
    private function childMatchesStudent(array $child, array $student): bool
    {
        $childFirst = mb_strtolower(trim((string)($child['first_name'] ?? '')));
        $childFamily = mb_strtolower(trim((string)($child['family_name'] ?? '')));
        if ($childFirst === '' || $childFamily === '') {
            return false;
        }

        $fullName = mb_strtolower(trim((string)($student['full_name'] ?? '')));
        if ($fullName !== '' && str_contains($fullName, $childFirst) && str_contains($fullName, $childFamily)) {
            return true;
        }

        $studentFirst = mb_strtolower(trim((string)($student['first_name'] ?? '')));
        $studentFamily = mb_strtolower(trim((string)($student['family_name'] ?? '')));

        return $studentFirst === $childFirst && $studentFamily === $childFamily;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) > 9) {
            $digits = substr($digits, -9);
        }

        return $digits;
    }
}
