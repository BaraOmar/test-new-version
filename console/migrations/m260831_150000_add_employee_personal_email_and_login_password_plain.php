<?php

use yii\db\Migration;

class m260831_150000_add_employee_personal_email_and_login_password_plain extends Migration
{
    public function safeUp()
    {
        $table = '{{%employees}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null) {
            return;
        }

        if ($schema->getColumn('personal_email') === null) {
            $this->addColumn($table, 'personal_email', $this->string(255)->null()->after('email'));
        }

        if ($schema->getColumn('login_password_plain') === null) {
            $after = $schema->getColumn('personal_email') !== null ? 'personal_email' : 'email';
            $this->addColumn($table, 'login_password_plain', $this->string(128)->null()->after($after));
        }

        if ($this->db->getTableSchema($table, true)?->getColumn('personal_email') !== null) {
            $this->execute(
                "UPDATE {$table} SET personal_email = email WHERE (personal_email IS NULL OR personal_email = '') AND email IS NOT NULL AND email <> ''"
            );
        }
    }

    public function safeDown()
    {
        $table = '{{%employees}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null) {
            return;
        }

        if ($schema->getColumn('login_password_plain') !== null) {
            $this->dropColumn($table, 'login_password_plain');
        }

        if ($schema->getColumn('personal_email') !== null) {
            $this->dropColumn($table, 'personal_email');
        }
    }
}
