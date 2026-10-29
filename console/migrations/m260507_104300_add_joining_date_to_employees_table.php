<?php

use yii\db\Migration;

class m260507_104300_add_joining_date_to_employees_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null) {
            return;
        }

        if ($schema->getColumn('joining_date') === null) {
            // Keep nullable for compatibility with existing rows.
            $this->addColumn('{{%employees}}', 'joining_date', $this->date()->null()->after('job_title'));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null) {
            return;
        }

        if ($schema->getColumn('joining_date') !== null) {
            $this->dropColumn('{{%employees}}', 'joining_date');
        }
    }
}

