<?php

use yii\db\Migration;

class m260521_160000_create_employee_change_requests_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%employee_change_requests}}', true) !== null) {
            return;
        }

        $this->createTable('{{%employee_change_requests}}', [
            'id' => $this->primaryKey(),
            'employee_id' => $this->integer()->notNull(),
            'old_value' => $this->text()->null(),
            'new_value' => $this->text()->notNull(),
            'urgency' => $this->string(32)->notNull()->defaultValue('Normal'),
            'status' => $this->string(32)->notNull()->defaultValue('Pending'),
            'created_at' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
        ]);

        $this->createIndex('idx_employee_change_requests_employee', '{{%employee_change_requests}}', 'employee_id');
        $this->createIndex('idx_employee_change_requests_status', '{{%employee_change_requests}}', 'status');

        $this->addForeignKey(
            'fk_employee_change_requests_employee',
            '{{%employee_change_requests}}',
            'employee_id',
            '{{%employees}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%employee_change_requests}}', true) === null) {
            return;
        }

        $this->dropForeignKey('fk_employee_change_requests_employee', '{{%employee_change_requests}}');
        $this->dropTable('{{%employee_change_requests}}');
    }
}
