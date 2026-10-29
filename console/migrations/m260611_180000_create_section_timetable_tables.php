<?php

use yii\db\Migration;

class m260611_180000_create_section_timetable_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_section_timetable_row}}', true) === null) {
            $this->createTable('{{%school_section_timetable_row}}', [
                'id' => $this->primaryKey(),
                'school_section_id' => $this->integer()->notNull(),
                'day_of_week' => $this->tinyInteger()->notNull(),
                'label' => $this->string(32)->null(),
                'sort_order' => $this->smallInteger()->notNull()->defaultValue(0),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_timetable_row_section', '{{%school_section_timetable_row}}', 'school_section_id');
            $this->createIndex('uidx_timetable_row_section_day', '{{%school_section_timetable_row}}', ['school_section_id', 'day_of_week'], true);
            if ($this->db->schema->getTableSchema('{{%school_section}}', true) !== null) {
                $this->addForeignKey(
                    'fk_timetable_row_section',
                    '{{%school_section_timetable_row}}',
                    'school_section_id',
                    '{{%school_section}}',
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
            }
        }

        if ($this->db->schema->getTableSchema('{{%school_section_timetable_column}}', true) === null) {
            $this->createTable('{{%school_section_timetable_column}}', [
                'id' => $this->primaryKey(),
                'school_section_id' => $this->integer()->notNull(),
                'start_time' => $this->time()->notNull(),
                'end_time' => $this->time()->notNull(),
                'label' => $this->string(64)->null(),
                'sort_order' => $this->smallInteger()->notNull()->defaultValue(0),
                'is_break' => $this->boolean()->notNull()->defaultValue(false),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_timetable_column_section', '{{%school_section_timetable_column}}', 'school_section_id');
            if ($this->db->schema->getTableSchema('{{%school_section}}', true) !== null) {
                $this->addForeignKey(
                    'fk_timetable_column_section',
                    '{{%school_section_timetable_column}}',
                    'school_section_id',
                    '{{%school_section}}',
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
            }
        }

        if ($this->db->schema->getTableSchema('{{%school_section_timetable_slot}}', true) === null) {
            $this->createTable('{{%school_section_timetable_slot}}', [
                'id' => $this->primaryKey(),
                'school_section_id' => $this->integer()->notNull(),
                'row_id' => $this->integer()->notNull(),
                'column_id' => $this->integer()->notNull(),
                'school_section_material_id' => $this->integer()->null(),
                'instructor_id' => $this->integer()->null(),
                'room_number' => $this->string(64)->null(),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_timetable_slot_section', '{{%school_section_timetable_slot}}', 'school_section_id');
            $this->createIndex('uidx_timetable_slot_cell', '{{%school_section_timetable_slot}}', ['school_section_id', 'row_id', 'column_id'], true);
            $this->createIndex('idx_timetable_slot_instructor', '{{%school_section_timetable_slot}}', 'instructor_id');
            if ($this->db->schema->getTableSchema('{{%school_section}}', true) !== null) {
                $this->addForeignKey(
                    'fk_timetable_slot_section',
                    '{{%school_section_timetable_slot}}',
                    'school_section_id',
                    '{{%school_section}}',
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
            }
            if ($this->db->schema->getTableSchema('{{%school_section_timetable_row}}', true) !== null) {
                $this->addForeignKey(
                    'fk_timetable_slot_row',
                    '{{%school_section_timetable_slot}}',
                    'row_id',
                    '{{%school_section_timetable_row}}',
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
            }
            if ($this->db->schema->getTableSchema('{{%school_section_timetable_column}}', true) !== null) {
                $this->addForeignKey(
                    'fk_timetable_slot_column',
                    '{{%school_section_timetable_slot}}',
                    'column_id',
                    '{{%school_section_timetable_column}}',
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
            }
            if ($this->db->schema->getTableSchema('{{%school_section_material}}', true) !== null) {
                $this->addForeignKey(
                    'fk_timetable_slot_material',
                    '{{%school_section_timetable_slot}}',
                    'school_section_material_id',
                    '{{%school_section_material}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            }
            if ($this->db->schema->getTableSchema('{{%employees}}', true) !== null) {
                $this->addForeignKey(
                    'fk_timetable_slot_instructor',
                    '{{%school_section_timetable_slot}}',
                    'instructor_id',
                    '{{%employees}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            }
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_section_timetable_slot}}', true) !== null) {
            if ($this->db->schema->getTableSchema('{{%employees}}', true) !== null) {
                $this->dropForeignKey('fk_timetable_slot_instructor', '{{%school_section_timetable_slot}}');
            }
            if ($this->db->schema->getTableSchema('{{%school_section_material}}', true) !== null) {
                $this->dropForeignKey('fk_timetable_slot_material', '{{%school_section_timetable_slot}}');
            }
            $this->dropForeignKey('fk_timetable_slot_column', '{{%school_section_timetable_slot}}');
            $this->dropForeignKey('fk_timetable_slot_row', '{{%school_section_timetable_slot}}');
            $this->dropForeignKey('fk_timetable_slot_section', '{{%school_section_timetable_slot}}');
            $this->dropTable('{{%school_section_timetable_slot}}');
        }

        if ($this->db->schema->getTableSchema('{{%school_section_timetable_column}}', true) !== null) {
            $this->dropForeignKey('fk_timetable_column_section', '{{%school_section_timetable_column}}');
            $this->dropTable('{{%school_section_timetable_column}}');
        }

        if ($this->db->schema->getTableSchema('{{%school_section_timetable_row}}', true) !== null) {
            $this->dropForeignKey('fk_timetable_row_section', '{{%school_section_timetable_row}}');
            $this->dropTable('{{%school_section_timetable_row}}');
        }
    }
}
