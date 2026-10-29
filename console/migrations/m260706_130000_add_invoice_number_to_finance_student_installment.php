<?php

use yii\db\Migration;

class m260706_130000_add_invoice_number_to_finance_student_installment extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_student_installment}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('invoice_number') !== null) {
            return;
        }

        $this->addColumn($table, 'invoice_number', $this->string(64)->null()->after('id'));
        $this->createIndex('idx_finance_student_installment_invoice_number', $table, 'invoice_number', true);

        $rows = (new \yii\db\Query())
            ->from($table)
            ->select(['id'])
            ->where(['invoice_number' => null])
            ->orderBy(['id' => SORT_ASC])
            ->all($this->db);

        $year = date('Y');
        $sequence = 1;
        foreach ($rows as $row) {
            $this->update($table, [
                'invoice_number' => 'INV-' . $year . '-' . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT),
            ], ['id' => (int)$row['id']]);
            $sequence++;
        }
    }

    public function safeDown()
    {
        $table = '{{%finance_student_installment}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('invoice_number') === null) {
            return;
        }

        $this->dropIndex('idx_finance_student_installment_invoice_number', $table);
        $this->dropColumn($table, 'invoice_number');
    }
}
