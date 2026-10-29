<?php

use yii\db\Migration;

class m260706_180000_add_revenue_account_id_to_finance_student_installment extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_student_installment}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('revenue_account_id') === null) {
            $this->addColumn($table, 'revenue_account_id', $this->integer()->null()->after('parent_profile_id'));
            $this->createIndex('idx_finance_student_installment_revenue_account', $table, 'revenue_account_id');

            if ($this->db->schema->getTableSchema('{{%finance_account}}', true) !== null) {
                $this->addForeignKey(
                    'fk_finance_student_installment_revenue_account',
                    $table,
                    'revenue_account_id',
                    '{{%finance_account}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            }
        }

        $revenueAccountId = (int)$this->db->createCommand(
            "SELECT id FROM {{%finance_account}} WHERE [[code]] = :code LIMIT 1",
            [':code' => '310000004']
        )->queryScalar();

        if ($revenueAccountId <= 0) {
            $revenueAccountId = (int)$this->db->createCommand(
                "SELECT id FROM {{%finance_account}} WHERE [[name]] LIKE :name AND [[account_type]] = 'revenue' LIMIT 1",
                [':name' => '%أقساط مدرسية%']
            )->queryScalar();
        }

        if ($revenueAccountId > 0) {
            $this->update($table, ['revenue_account_id' => $revenueAccountId], [
                'or',
                ['revenue_account_id' => null],
                ['revenue_account_id' => 0],
            ]);
        }
    }

    public function safeDown()
    {
        $table = '{{%finance_student_installment}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('revenue_account_id') !== null) {
            $this->dropForeignKey('fk_finance_student_installment_revenue_account', $table);
            $this->dropIndex('idx_finance_student_installment_revenue_account', $table);
            $this->dropColumn($table, 'revenue_account_id');
        }
    }
}
