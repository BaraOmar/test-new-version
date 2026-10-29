<?php

use yii\db\Migration;

class m260707_120000_add_journal_entry_id_to_finance_student_installment extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%finance_student_installment}}', true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema('{{%finance_student_installment}}', true)->getColumn('journal_entry_id') !== null) {
            return;
        }

        $this->addColumn(
            '{{%finance_student_installment}}',
            'journal_entry_id',
            $this->integer()->null()->after('revenue_account_id')
        );
        $this->createIndex(
            'idx_finance_student_installment_journal',
            '{{%finance_student_installment}}',
            'journal_entry_id'
        );

        if ($this->db->schema->getTableSchema('{{%finance_journal_entry}}', true) !== null) {
            $this->addForeignKey(
                'fk_finance_student_installment_journal',
                '{{%finance_student_installment}}',
                'journal_entry_id',
                '{{%finance_journal_entry}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%finance_student_installment}}', true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema('{{%finance_student_installment}}', true)->getColumn('journal_entry_id') === null) {
            return;
        }

        $this->dropForeignKey('fk_finance_student_installment_journal', '{{%finance_student_installment}}');
        $this->dropIndex('idx_finance_student_installment_journal', '{{%finance_student_installment}}');
        $this->dropColumn('{{%finance_student_installment}}', 'journal_entry_id');
    }
}
