<?php

use yii\db\Migration;

class m260705_120000_add_child_name_to_finance_account_parent_child extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_account_parent_child}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || isset($schema->columns['child_name'])) {
            return;
        }

        $this->addColumn($table, 'child_name', $this->string(255)->notNull()->defaultValue(''));
    }

    public function safeDown()
    {
        $table = '{{%finance_account_parent_child}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || !isset($schema->columns['child_name'])) {
            return;
        }

        $this->dropColumn($table, 'child_name');
    }
}
