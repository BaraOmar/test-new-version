<?php

use yii\db\Migration;

/**
 * Adds marital_status, position_level, and employment_type for onboarding and employee profile.
 */
class m260522_120000_add_employee_profile_fields_to_employees_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['marital_status'])) {
            $after = isset($schema->columns['phone']) ? 'phone' : 'email';
            $this->addColumn('{{%employees}}', 'marital_status', $this->string(32)->null()->after($after));
        }

        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if (!isset($schema->columns['position_level'])) {
            $after = isset($schema->columns['job_title']) ? 'job_title' : 'marital_status';
            $this->addColumn('{{%employees}}', 'position_level', $this->string(8)->null()->after($after));
        }

        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if (!isset($schema->columns['employment_type'])) {
            $after = isset($schema->columns['joining_date']) ? 'joining_date' : 'position_level';
            $this->addColumn('{{%employees}}', 'employment_type', $this->string(32)->null()->after($after));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null) {
            return;
        }

        foreach (['employment_type', 'position_level', 'marital_status'] as $column) {
            if (isset($schema->columns[$column])) {
                $this->dropColumn('{{%employees}}', $column);
            }
        }
    }
}
