<?php

use yii\db\Migration;

class m260902_140000_add_section_snapshot_to_attendance_record extends Migration
{
    public function safeUp()
    {
        $recordTable = $this->db->schema->getTableSchema('{{%school_student_attendance_record}}', true);
        if ($recordTable === null) {
            return;
        }

        if (!isset($recordTable->columns['school_section_id'])) {
            $this->addColumn(
                '{{%school_student_attendance_record}}',
                'school_section_id',
                $this->integer()->null()->after('school_student_id')
            );
            $this->createIndex(
                'idx_school_student_attendance_record_section',
                '{{%school_student_attendance_record}}',
                'school_section_id'
            );
        }

        if ($this->db->schema->getTableSchema('{{%school_section}}', true) !== null) {
            $recordTable = $this->db->schema->getTableSchema('{{%school_student_attendance_record}}', true);
            if ($recordTable !== null && !isset($recordTable->foreignKeys['fk_school_student_attendance_record_section'])) {
                $this->addForeignKey(
                    'fk_school_student_attendance_record_section',
                    '{{%school_student_attendance_record}}',
                    'school_section_id',
                    '{{%school_section}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            }
        }

        if ($this->db->schema->getTableSchema('{{%school_section_attendance_session}}', true) !== null) {
            $this->execute('
                UPDATE {{%school_student_attendance_record}} AS record
                INNER JOIN {{%school_section_attendance_session}} AS session ON session.id = record.session_id
                SET record.school_section_id = session.school_section_id
                WHERE record.school_section_id IS NULL
            ');
        }
    }

    public function safeDown()
    {
        $recordTable = $this->db->schema->getTableSchema('{{%school_student_attendance_record}}', true);
        if ($recordTable === null || !isset($recordTable->columns['school_section_id'])) {
            return;
        }

        if (isset($recordTable->foreignKeys['fk_school_student_attendance_record_section'])) {
            $this->dropForeignKey('fk_school_student_attendance_record_section', '{{%school_student_attendance_record}}');
        }
        $this->dropIndex('idx_school_student_attendance_record_section', '{{%school_student_attendance_record}}');
        $this->dropColumn('{{%school_student_attendance_record}}', 'school_section_id');
    }
}
