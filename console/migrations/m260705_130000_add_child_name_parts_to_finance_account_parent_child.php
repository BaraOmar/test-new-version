<?php

use yii\db\Migration;

class m260705_130000_add_child_name_parts_to_finance_account_parent_child extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_account_parent_child}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['first_name'])) {
            $this->addColumn($table, 'first_name', $this->string(128)->notNull()->defaultValue(''));
        }
        if (!isset($schema->columns['second_name'])) {
            $this->addColumn($table, 'second_name', $this->string(128)->null());
        }
        if (!isset($schema->columns['third_name'])) {
            $this->addColumn($table, 'third_name', $this->string(128)->null());
        }
        if (!isset($schema->columns['family_name'])) {
            $this->addColumn($table, 'family_name', $this->string(128)->notNull()->defaultValue(''));
        }

        if (isset($schema->columns['child_name'])) {
            $this->execute(
                'UPDATE {{%finance_account_parent_child}} SET first_name = child_name WHERE first_name = \'\' AND child_name <> \'\''
            );
            $this->dropColumn($table, 'child_name');
        }
    }

    public function safeDown()
    {
        $table = '{{%finance_account_parent_child}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['child_name'])) {
            $this->addColumn($table, 'child_name', $this->string(255)->notNull()->defaultValue(''));
            $this->execute(
                'UPDATE {{%finance_account_parent_child}} SET child_name = TRIM(CONCAT(first_name, \' \', COALESCE(family_name, \'\')))'
            );
        }

        foreach (['family_name', 'third_name', 'second_name', 'first_name'] as $column) {
            if (isset($schema->columns[$column])) {
                $this->dropColumn($table, $column);
            }
        }
    }
}
