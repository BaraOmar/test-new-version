<?php

use yii\db\Migration;

class m260613_100000_create_student_attendance_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_section}}', true) === null
            || $this->db->schema->getTableSchema('{{%school_student}}', true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema('{{%school_section_attendance_session}}', true) === null) {
            $this->createTable('{{%school_section_attendance_session}}', [
                'id' => $this->primaryKey(),
                'school_section_id' => $this->integer()->notNull(),
                'attendance_date' => $this->date()->notNull(),
                'taken_by_user_id' => $this->integer()->null(),
                'taken_at' => $this->dateTime()->null(),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);

            $this->createIndex(
                'idx_school_section_attendance_session_section_date',
                '{{%school_section_attendance_session}}',
                ['school_section_id', 'attendance_date'],
                true
            );
            $this->createIndex(
                'idx_school_section_attendance_session_date',
                '{{%school_section_attendance_session}}',
                'attendance_date'
            );

            $this->addForeignKey(
                'fk_school_section_attendance_session_section',
                '{{%school_section_attendance_session}}',
                'school_section_id',
                '{{%school_section}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if ($this->db->schema->getTableSchema('{{%school_student_attendance_record}}', true) === null) {
            $this->createTable('{{%school_student_attendance_record}}', [
                'id' => $this->primaryKey(),
                'session_id' => $this->integer()->notNull(),
                'school_student_id' => $this->integer()->notNull(),
                'status' => $this->string(16)->notNull()->defaultValue('present'),
                'note' => $this->string(255)->null(),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);

            $this->createIndex(
                'idx_school_student_attendance_record_session_student',
                '{{%school_student_attendance_record}}',
                ['session_id', 'school_student_id'],
                true
            );
            $this->createIndex(
                'idx_school_student_attendance_record_student',
                '{{%school_student_attendance_record}}',
                'school_student_id'
            );

            $this->addForeignKey(
                'fk_school_student_attendance_record_session',
                '{{%school_student_attendance_record}}',
                'session_id',
                '{{%school_section_attendance_session}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                'fk_school_student_attendance_record_student',
                '{{%school_student_attendance_record}}',
                'school_student_id',
                '{{%school_student}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if ($this->db->schema->getTableSchema('{{%user}}', true) !== null
            && $this->db->schema->getTableSchema('{{%school_section_attendance_session}}', true) !== null) {
            $sessionTable = $this->db->schema->getTableSchema('{{%school_section_attendance_session}}', true);
            if ($sessionTable !== null && !isset($sessionTable->foreignKeys['fk_school_section_attendance_session_user'])) {
                $this->addForeignKey(
                    'fk_school_section_attendance_session_user',
                    '{{%school_section_attendance_session}}',
                    'taken_by_user_id',
                    '{{%user}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            }
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_student_attendance_record}}', true) !== null) {
            $this->dropForeignKey('fk_school_student_attendance_record_student', '{{%school_student_attendance_record}}');
            $this->dropForeignKey('fk_school_student_attendance_record_session', '{{%school_student_attendance_record}}');
            $this->dropTable('{{%school_student_attendance_record}}');
        }

        if ($this->db->schema->getTableSchema('{{%school_section_attendance_session}}', true) !== null) {
            $sessionTable = $this->db->schema->getTableSchema('{{%school_section_attendance_session}}', true);
            if ($sessionTable !== null && isset($sessionTable->foreignKeys['fk_school_section_attendance_session_user'])) {
                $this->dropForeignKey('fk_school_section_attendance_session_user', '{{%school_section_attendance_session}}');
            }
            $this->dropForeignKey('fk_school_section_attendance_session_section', '{{%school_section_attendance_session}}');
            $this->dropTable('{{%school_section_attendance_session}}');
        }
    }
}
