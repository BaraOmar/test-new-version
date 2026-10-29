<?php

use yii\db\Migration;

/**
 * Store HR-visible parent portal password for view/edit screens.
 */
class m260830_150000_add_parent_login_password_plain extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_account_parent_profile}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn('login_password_plain') !== null) {
            return;
        }

        $this->addColumn($table, 'login_password_plain', $this->string(128)->null()->after('email'));
    }

    public function safeDown()
    {
        $table = '{{%finance_account_parent_profile}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn('login_password_plain') === null) {
            return;
        }

        $this->dropColumn($table, 'login_password_plain');
    }
}
