<?php

use yii\db\Migration;

class m260605_140000_create_employee_attendance_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%employee_attendance}}', true) !== null) {
            return;
        }

        $this->createTable('{{%employee_attendance}}', [
            'id' => $this->primaryKey(),
            'employee_id' => $this->integer()->notNull(),
            'work_date' => $this->date()->notNull(),
            'clock_in_at' => $this->dateTime()->notNull(),
            'clock_out_at' => $this->dateTime()->null(),
            'created_at' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
        ]);

        $this->createIndex('idx_employee_attendance_employee', '{{%employee_attendance}}', 'employee_id');
        $this->createIndex('idx_employee_attendance_work_date', '{{%employee_attendance}}', 'work_date');
        $this->createIndex(
            'idx_employee_attendance_employee_date',
            '{{%employee_attendance}}',
            ['employee_id', 'work_date']
        );

        $this->addForeignKey(
            'fk_employee_attendance_employee',
            '{{%employee_attendance}}',
            'employee_id',
            '{{%employees}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%employee_attendance}}', true) === null) {
            return;
        }

        $this->dropForeignKey('fk_employee_attendance_employee', '{{%employee_attendance}}');
        $this->dropTable('{{%employee_attendance}}');
    }
}
