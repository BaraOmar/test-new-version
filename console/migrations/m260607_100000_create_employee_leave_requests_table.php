<?php

use yii\db\Migration;

class m260607_100000_create_employee_leave_requests_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%employee_leave_request}}', true) !== null) {
            return;
        }

        $this->createTable('{{%employee_leave_request}}', [
            'id' => $this->primaryKey(),
            'employee_id' => $this->integer()->notNull(),
            'leave_type' => $this->string(32)->notNull(),
            'start_date' => $this->date()->notNull(),
            'end_date' => $this->date()->notNull(),
            'days_count' => $this->integer()->notNull()->defaultValue(1),
            'reason' => $this->text()->notNull(),
            'attachment_path' => $this->string(512)->null(),
            'attachment_name' => $this->string(255)->null(),
            'status' => $this->string(16)->notNull()->defaultValue('pending'),
            'reviewed_by' => $this->integer()->null(),
            'reviewed_at' => $this->dateTime()->null(),
            'hr_note' => $this->text()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex('idx_employee_leave_request_employee', '{{%employee_leave_request}}', 'employee_id');
        $this->createIndex('idx_employee_leave_request_status', '{{%employee_leave_request}}', 'status');
        $this->createIndex('idx_employee_leave_request_dates', '{{%employee_leave_request}}', ['start_date', 'end_date']);

        $this->addForeignKey(
            'fk_employee_leave_request_employee',
            '{{%employee_leave_request}}',
            'employee_id',
            '{{%employees}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%employee_leave_request}}', true) === null) {
            return;
        }

        $this->dropForeignKey('fk_employee_leave_request_employee', '{{%employee_leave_request}}');
        $this->dropTable('{{%employee_leave_request}}');
    }
}
