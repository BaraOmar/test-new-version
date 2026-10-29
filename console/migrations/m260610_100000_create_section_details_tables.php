<?php

use yii\db\Migration;

class m260610_100000_create_section_details_tables extends Migration
{
    public function safeUp()
    {
        $sectionSchema = $this->db->schema->getTableSchema('{{%school_section}}', true);
        if ($sectionSchema !== null && !isset($sectionSchema->columns['lead_teacher_assigned_at'])) {
            $this->addColumn(
                '{{%school_section}}',
                'lead_teacher_assigned_at',
                $this->dateTime()->null()->after('lead_teacher_id')
            );
        }

        if ($this->db->schema->getTableSchema('{{%school_section_material}}', true) === null) {
            $this->createTable('{{%school_section_material}}', [
                'id' => $this->primaryKey(),
                'school_section_id' => $this->integer()->notNull(),
                'school_subject_id' => $this->integer()->null(),
                'name' => $this->string(255)->notNull(),
                'icon' => $this->string(64)->notNull()->defaultValue('menu_book'),
                'instructor_id' => $this->integer()->null(),
                'schedule' => $this->string(128)->null(),
                'status' => $this->string(16)->notNull()->defaultValue('active'),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_section_material_section', '{{%school_section_material}}', 'school_section_id');
            $this->addForeignKey(
                'fk_section_material_section',
                '{{%school_section_material}}',
                'school_section_id',
                '{{%school_section}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
            if ($this->db->schema->getTableSchema('{{%school_subject}}', true) !== null) {
                $this->addForeignKey(
                    'fk_section_material_subject',
                    '{{%school_section_material}}',
                    'school_subject_id',
                    '{{%school_subject}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            }
            if ($this->db->schema->getTableSchema('{{%employees}}', true) !== null) {
                $this->addForeignKey(
                    'fk_section_material_instructor',
                    '{{%school_section_material}}',
                    'instructor_id',
                    '{{%employees}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            }
        }

        if ($this->db->schema->getTableSchema('{{%school_student}}', true) === null) {
            $this->createTable('{{%school_student}}', [
                'id' => $this->primaryKey(),
                'school_section_id' => $this->integer()->notNull(),
                'student_code' => $this->string(32)->notNull(),
                'full_name' => $this->string(255)->notNull(),
                'email' => $this->string(255)->null(),
                'attendance_percent' => $this->decimal(5, 2)->notNull()->defaultValue(0),
                'grade_average' => $this->string(8)->null(),
                'status' => $this->string(16)->notNull()->defaultValue('active'),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_school_student_section', '{{%school_student}}', 'school_section_id');
            $this->createIndex('idx_school_student_code', '{{%school_student}}', 'student_code', true);
            $this->addForeignKey(
                'fk_school_student_section',
                '{{%school_student}}',
                'school_section_id',
                '{{%school_section}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_student}}', true) !== null) {
            $this->dropForeignKey('fk_school_student_section', '{{%school_student}}');
            $this->dropTable('{{%school_student}}');
        }

        if ($this->db->schema->getTableSchema('{{%school_section_material}}', true) !== null) {
            if ($this->db->schema->getTableSchema('{{%employees}}', true) !== null) {
                $this->dropForeignKey('fk_section_material_instructor', '{{%school_section_material}}');
            }
            if ($this->db->schema->getTableSchema('{{%school_subject}}', true) !== null) {
                $this->dropForeignKey('fk_section_material_subject', '{{%school_section_material}}');
            }
            $this->dropForeignKey('fk_section_material_section', '{{%school_section_material}}');
            $this->dropTable('{{%school_section_material}}');
        }

        $sectionSchema = $this->db->schema->getTableSchema('{{%school_section}}', true);
        if ($sectionSchema !== null && isset($sectionSchema->columns['lead_teacher_assigned_at'])) {
            $this->dropColumn('{{%school_section}}', 'lead_teacher_assigned_at');
        }
    }
}
