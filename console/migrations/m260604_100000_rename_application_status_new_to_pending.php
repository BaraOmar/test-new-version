<?php

use yii\db\Migration;

class m260604_100000_rename_application_status_new_to_pending extends Migration
{
    public function safeUp()
    {
        $table = '{{%applications}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $this->update($table, ['application_status' => 'Pending'], ['application_status' => 'New']);
    }

    public function safeDown()
    {
        $table = '{{%applications}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $this->update($table, ['application_status' => 'New'], ['application_status' => 'Pending']);
    }
}
