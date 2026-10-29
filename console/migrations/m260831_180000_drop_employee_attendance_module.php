<?php

use yii\db\Migration;

/**
 * Removes the employee attendance and leave module tables.
 */
class m260831_180000_drop_employee_attendance_module extends Migration
{
    public function safeUp()
    {
        $tables = [
            '{{%employee_leave_request}}' => 'fk_employee_leave_request_employee',
            '{{%employee_attendance}}' => 'fk_employee_attendance_employee',
        ];

        foreach ($tables as $table => $foreignKey) {
            if ($this->db->schema->getTableSchema($table, true) === null) {
                continue;
            }

            try {
                $this->dropForeignKey($foreignKey, $table);
            } catch (\Throwable) {
                // Foreign key may already be missing.
            }

            $this->dropTable($table);
        }

        foreach (['{{%attendance_off_day_policy}}', '{{%attendance_settings}}'] as $table) {
            if ($this->db->schema->getTableSchema($table, true) !== null) {
                $this->dropTable($table);
            }
        }
    }

    public function safeDown()
    {
        echo "This migration cannot be reverted.\n";

        return false;
    }
}
