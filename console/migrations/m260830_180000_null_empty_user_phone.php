<?php

use yii\db\Migration;

class m260830_180000_null_empty_user_phone extends Migration
{
    public function safeUp()
    {
        $table = '{{%user}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn('phone') === null) {
            return;
        }

        $this->update($table, ['phone' => null], ['phone' => '']);
    }

    public function safeDown()
    {
        return true;
    }
}
