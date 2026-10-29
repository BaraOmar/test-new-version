<?php

use yii\db\Migration;

class m260707_160000_add_primary_installment_id_to_finance_student_installment extends Migration
{
    public function safeUp(): void
    {
        $table = '{{%finance_student_installment}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('primary_installment_id') === null) {
            $this->addColumn(
                $table,
                'primary_installment_id',
                $this->integer()->null()->after('revenue_account_id')
            );
            $this->createIndex(
                'idx_finance_student_installment_primary_installment_id',
                $table,
                'primary_installment_id'
            );
            $this->addForeignKey(
                'fk_finance_student_installment_primary_installment_id',
                $table,
                'primary_installment_id',
                $table,
                'id',
                'CASCADE',
                'CASCADE'
            );
        }
    }

    public function safeDown(): void
    {
        $table = '{{%finance_student_installment}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('primary_installment_id') !== null) {
            $this->dropForeignKey('fk_finance_student_installment_primary_installment_id', $table);
            $this->dropIndex('idx_finance_student_installment_primary_installment_id', $table);
            $this->dropColumn($table, 'primary_installment_id');
        }
    }
}
