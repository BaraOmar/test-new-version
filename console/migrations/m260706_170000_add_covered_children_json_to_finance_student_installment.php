<?php

use yii\db\Migration;

class m260706_170000_add_covered_children_json_to_finance_student_installment extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_student_installment}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('covered_children_json') === null) {
            $this->addColumn($table, 'covered_children_json', $this->text()->null()->after('title'));
        }
    }

    public function safeDown()
    {
        $table = '{{%finance_student_installment}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('covered_children_json') !== null) {
            $this->dropColumn($table, 'covered_children_json');
        }
    }
}
