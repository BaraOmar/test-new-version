<?php

use yii\db\Migration;

class m260830_170000_add_student_login_password_plain extends Migration
{
    public function safeUp()
    {
        $table = '{{%school_student}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn('login_password_plain') !== null) {
            return;
        }

        $after = $schema->getColumn('residence_area_id') !== null ? 'residence_area_id' : 'home_address';
        $this->addColumn($table, 'login_password_plain', $this->string(128)->null()->after($after));
    }

    public function safeDown()
    {
        $table = '{{%school_student}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn('login_password_plain') === null) {
            return;
        }

        $this->dropColumn($table, 'login_password_plain');
    }
}
