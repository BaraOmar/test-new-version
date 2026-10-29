<?php

use yii\db\Migration;

class m260706_140000_add_parent_child_id_to_finance_student_installment extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_student_installment}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('parent_child_id') !== null) {
            return;
        }

        $this->addColumn($table, 'parent_child_id', $this->integer()->null()->after('school_student_id'));
        $this->createIndex('idx_finance_student_installment_parent_child', $table, 'parent_child_id');

        if ($this->db->schema->getTableSchema('{{%finance_account_parent_child}}', true) !== null) {
            $this->addForeignKey(
                'fk_finance_student_installment_parent_child',
                $table,
                'parent_child_id',
                '{{%finance_account_parent_child}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        $table = '{{%finance_student_installment}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('parent_child_id') === null) {
            return;
        }

        $this->dropForeignKey('fk_finance_student_installment_parent_child', $table);
        $this->dropIndex('idx_finance_student_installment_parent_child', $table);
        $this->dropColumn($table, 'parent_child_id');
    }
}
