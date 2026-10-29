<?php

use yii\db\Migration;

class m260610_110000_rename_section_status_alert_to_inactive extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_section}}', true) === null) {
            return;
        }

        $this->update('{{%school_section}}', ['status' => 'inactive'], ['status' => 'alert']);
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_section}}', true) === null) {
            return;
        }

        $this->update('{{%school_section}}', ['status' => 'alert'], ['status' => 'inactive']);
    }
}
