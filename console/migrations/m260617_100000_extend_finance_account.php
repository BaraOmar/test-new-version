<?php

use yii\db\Migration;

class m260617_100000_extend_finance_account extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_account}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $schema = $this->db->schema->getTableSchema($table, true);

        if ($schema->getColumn('description') === null) {
            $this->addColumn($table, 'description', $this->text()->null()->after('parent_id'));
        }
        if ($schema->getColumn('is_analytical') === null) {
            $this->addColumn($table, 'is_analytical', $this->boolean()->notNull()->defaultValue(false)->after('is_active'));
        }
        if ($schema->getColumn('is_vat') === null) {
            $this->addColumn($table, 'is_vat', $this->boolean()->notNull()->defaultValue(false)->after('is_analytical'));
        }
    }

    public function safeDown()
    {
        $table = '{{%finance_account}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $schema = $this->db->schema->getTableSchema($table, true);

        if ($schema->getColumn('is_vat') !== null) {
            $this->dropColumn($table, 'is_vat');
        }
        if ($schema->getColumn('is_analytical') !== null) {
            $this->dropColumn($table, 'is_analytical');
        }
        if ($schema->getColumn('description') !== null) {
            $this->dropColumn($table, 'description');
        }
    }
}
