<?php

use yii\db\Migration;

class m260705_180000_add_phone_to_user_table extends Migration
{
    public function safeUp()
    {
        $table = '{{%user}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn('phone') !== null) {
            return;
        }

        $this->addColumn($table, 'phone', $this->string(32)->null()->after('email'));
    }

    public function safeDown()
    {
        $table = '{{%user}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema !== null && $schema->getColumn('phone') !== null) {
            $this->dropColumn($table, 'phone');
        }
    }
}
