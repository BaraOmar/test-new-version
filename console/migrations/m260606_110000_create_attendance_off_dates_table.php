<?php

use yii\db\Migration;

class m260606_110000_create_attendance_off_dates_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%attendance_off_date}}', true) !== null) {
            return;
        }

        $this->createTable('{{%attendance_off_date}}', [
            'id' => $this->primaryKey(),
            'off_date' => $this->date()->notNull(),
            'name' => $this->string(255)->notNull(),
            'status' => $this->string(16)->notNull()->defaultValue('active'),
            'created_at' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
        ]);

        $this->createIndex('idx_attendance_off_date_off_date', '{{%attendance_off_date}}', 'off_date', true);
        $this->createIndex('idx_attendance_off_date_status', '{{%attendance_off_date}}', 'status');
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%attendance_off_date}}', true) === null) {
            return;
        }

        $this->dropTable('{{%attendance_off_date}}');
    }
}
