<?php

use yii\db\Migration;

class m260519_120000_add_survey_updated_at extends Migration
{
    public function safeUp()
    {
        $table = '{{%surveys}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }
        if ($this->db->schema->getTableSchema($table, true)->getColumn('updated_at') !== null) {
            return;
        }

        $this->addColumn($table, 'updated_at', $this->dateTime()->null()->after('created_at'));
    }

    public function safeDown()
    {
        $table = '{{%surveys}}';
        if ($this->db->schema->getTableSchema($table, true)?->getColumn('updated_at') !== null) {
            $this->dropColumn($table, 'updated_at');
        }
    }
}
