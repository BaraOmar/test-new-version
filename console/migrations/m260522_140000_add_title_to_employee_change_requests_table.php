<?php

use yii\db\Migration;

class m260522_140000_add_title_to_employee_change_requests_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%employee_change_requests}}', true);
        if ($schema === null || isset($schema->columns['title'])) {
            return;
        }

        $this->addColumn('{{%employee_change_requests}}', 'title', $this->string(255)->null()->after('employee_id'));
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%employee_change_requests}}', true);
        if ($schema === null || !isset($schema->columns['title'])) {
            return;
        }

        $this->dropColumn('{{%employee_change_requests}}', 'title');
    }
}
