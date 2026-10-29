<?php

use yii\db\Migration;

class m260606_100000_create_attendance_configuration_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%attendance_settings}}', true) === null) {
            $this->createTable('{{%attendance_settings}}', [
                'id' => $this->primaryKey(),
                'shift_start_time' => $this->time()->notNull()->defaultValue('09:00:00'),
                'shift_end_time' => $this->time()->notNull()->defaultValue('18:00:00'),
                'grace_window_start' => $this->time()->notNull()->defaultValue('09:00:00'),
                'grace_window_end' => $this->time()->notNull()->defaultValue('10:00:00'),
                'off_day_policy_name' => $this->string(255)->notNull()->defaultValue('Standard Weekend Shift'),
                'off_days' => $this->text()->notNull(),
                'off_day_duration_hours' => $this->integer()->notNull()->defaultValue(48),
                'updated_at' => $this->dateTime()->null(),
            ]);

            $this->insert('{{%attendance_settings}}', [
                'shift_start_time' => '09:00:00',
                'shift_end_time' => '18:00:00',
                'grace_window_start' => '09:00:00',
                'grace_window_end' => '10:00:00',
                'off_day_policy_name' => 'Standard Weekend Shift',
                'off_days' => json_encode(['sat', 'sun'], JSON_THROW_ON_ERROR),
                'off_day_duration_hours' => 48,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($this->db->schema->getTableSchema('{{%attendance_off_day_policy}}', true) === null) {
            $this->createTable('{{%attendance_off_day_policy}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string(255)->notNull(),
                'off_days' => $this->text()->notNull(),
                'days_label' => $this->string(255)->notNull()->defaultValue(''),
                'duration_hours' => $this->integer()->notNull()->defaultValue(48),
                'status' => $this->string(16)->notNull()->defaultValue('active'),
                'sort_order' => $this->integer()->notNull()->defaultValue(0),
                'created_at' => $this->dateTime()->null(),
                'updated_at' => $this->dateTime()->null(),
            ]);

            $now = date('Y-m-d H:i:s');
            $this->batchInsert('{{%attendance_off_day_policy}}', [
                'name',
                'off_days',
                'days_label',
                'duration_hours',
                'status',
                'sort_order',
                'created_at',
                'updated_at',
            ], [
                [
                    'Standard Weekend Shift',
                    json_encode(['sat', 'sun'], JSON_THROW_ON_ERROR),
                    'Sat, Sun',
                    48,
                    'active',
                    0,
                    $now,
                    $now,
                ],
                [
                    'Public Holiday Rest',
                    json_encode([], JSON_THROW_ON_ERROR),
                    'Variable',
                    48,
                    'inactive',
                    1,
                    $now,
                    $now,
                ],
            ]);
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%attendance_off_day_policy}}', true) !== null) {
            $this->dropTable('{{%attendance_off_day_policy}}');
        }
        if ($this->db->schema->getTableSchema('{{%attendance_settings}}', true) !== null) {
            $this->dropTable('{{%attendance_settings}}');
        }
    }
}
