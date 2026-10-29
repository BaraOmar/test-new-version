<?php

use yii\db\Migration;

/**
 * Adds teacher flag (0 = employee, 1 = teacher) for onboarding.
 */
class m260608_100000_add_employee_category_to_employees_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null) {
            return;
        }

        if (isset($schema->columns['employee_category'])) {
            $this->dropColumn('{{%employees}}', 'employee_category');
            $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        }

        if ($schema === null || isset($schema->columns['teacher'])) {
            return;
        }

        $after = isset($schema->columns['employment_type'])
            ? 'employment_type'
            : (isset($schema->columns['joining_date']) ? 'joining_date' : 'job_title');

        $this->addColumn(
            '{{%employees}}',
            'teacher',
            $this->tinyInteger(1)->notNull()->defaultValue(0)->after($after)
        );
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null) {
            return;
        }

        if (isset($schema->columns['teacher'])) {
            $this->dropColumn('{{%employees}}', 'teacher');
        }
    }
}
