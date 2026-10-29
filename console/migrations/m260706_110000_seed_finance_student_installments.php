<?php

use yii\db\Migration;
use yii\db\Query;

class m260706_110000_seed_finance_student_installments extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%finance_student_installment}}', true) === null) {
            return;
        }

        $exists = (new Query())
            ->from('{{%finance_student_installment}}')
            ->exists($this->db);

        if ($exists) {
            return;
        }

        if ($this->db->schema->getTableSchema('{{%school_student}}', true) === null) {
            return;
        }

        $students = (new Query())
            ->from('{{%school_student}}')
            ->select(['id', 'guardian_phone', 'guardian_name'])
            ->where(['status' => 'active'])
            ->limit(200)
            ->all($this->db);

        if ($students === []) {
            return;
        }

        $year = date('Y');
        $now = date('Y-m-d H:i:s');

        foreach ($students as $student) {
            $parentProfileId = $this->resolveParentProfileId($student);
            $titles = [
                'قسط الفصل الدراسي الأول - ' . $year,
                'قسط الفصل الدراسي الثاني - ' . $year,
            ];

            foreach ($titles as $index => $title) {
                $this->insert('{{%finance_student_installment}}', [
                    'parent_profile_id' => $parentProfileId,
                    'school_student_id' => (int)$student['id'],
                    'title' => $title,
                    'academic_year' => $year,
                    'academic_term' => $index === 0 ? 'first' : 'second',
                    'amount_due' => 4500,
                    'amount_paid' => 0,
                    'includes_vat' => true,
                    'due_date' => $index === 0 ? ($year . '-01-15') : ($year . '-06-15'),
                    'status' => 'pending',
                    'sort_order' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function safeDown()
    {
        $this->delete('{{%finance_student_installment}}');
    }

    /**
     * @param array<string, mixed> $student
     */
    private function resolveParentProfileId(array $student): ?int
    {
        if ($this->db->schema->getTableSchema('{{%finance_account_parent_profile}}', true) === null) {
            return null;
        }

        $phone = trim((string)($student['guardian_phone'] ?? ''));
        if ($phone !== '') {
            $id = (new Query())
                ->select('id')
                ->from('{{%finance_account_parent_profile}}')
                ->where(['phone' => $phone])
                ->scalar($this->db);
            if ($id !== false) {
                return (int)$id;
            }
        }

        $guardianName = trim((string)($student['guardian_name'] ?? ''));
        if ($guardianName !== '') {
            $profiles = (new Query())
                ->select(['id', 'first_name', 'family_name'])
                ->from('{{%finance_account_parent_profile}}')
                ->all($this->db);

            foreach ($profiles as $profile) {
                $fullName = trim((string)$profile['first_name'] . ' ' . (string)$profile['family_name']);
                if ($fullName !== '' && mb_stripos($guardianName, $fullName) !== false) {
                    return (int)$profile['id'];
                }
            }
        }

        return null;
    }
}
