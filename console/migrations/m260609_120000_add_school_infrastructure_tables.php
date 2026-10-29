<?php

use yii\db\Migration;

class m260609_120000_add_school_infrastructure_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_grade}}', true) === null) {
            $this->createTable('{{%school_grade}}', [
                'id' => $this->primaryKey(),
                'school_department_id' => $this->integer()->notNull(),
                'name' => $this->string(128)->notNull(),
                'sort_order' => $this->integer()->notNull()->defaultValue(0),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_school_grade_department', '{{%school_grade}}', 'school_department_id');
            $this->addForeignKey(
                'fk_school_grade_department',
                '{{%school_grade}}',
                'school_department_id',
                '{{%school_department}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if ($this->db->schema->getTableSchema('{{%school_section}}', true) === null) {
            $this->createTable('{{%school_section}}', [
                'id' => $this->primaryKey(),
                'school_grade_id' => $this->integer()->notNull(),
                'name' => $this->string(128)->notNull(),
                'room_number' => $this->string(64)->null(),
                'capacity_max' => $this->integer()->notNull()->defaultValue(30),
                'current_enrollment' => $this->integer()->notNull()->defaultValue(0),
                'lead_teacher_id' => $this->integer()->null(),
                'status' => $this->string(16)->notNull()->defaultValue('active'),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_school_section_grade', '{{%school_section}}', 'school_grade_id');
            if ($this->db->schema->getTableSchema('{{%employees}}', true) !== null) {
                $this->addForeignKey(
                    'fk_school_section_lead_teacher',
                    '{{%school_section}}',
                    'lead_teacher_id',
                    '{{%employees}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            }
            $this->addForeignKey(
                'fk_school_section_grade',
                '{{%school_section}}',
                'school_grade_id',
                '{{%school_grade}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if ($this->db->schema->getTableSchema('{{%school_subject}}', true) === null) {
            $this->createTable('{{%school_subject}}', [
                'id' => $this->primaryKey(),
                'school_department_id' => $this->integer()->notNull(),
                'name' => $this->string(255)->notNull(),
                'code' => $this->string(32)->null(),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_school_subject_department', '{{%school_subject}}', 'school_department_id');
            $this->addForeignKey(
                'fk_school_subject_department',
                '{{%school_subject}}',
                'school_department_id',
                '{{%school_department}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_subject}}', true) !== null) {
            $this->dropForeignKey('fk_school_subject_department', '{{%school_subject}}');
            $this->dropTable('{{%school_subject}}');
        }

        if ($this->db->schema->getTableSchema('{{%school_section}}', true) !== null) {
            if ($this->db->schema->getTableSchema('{{%employees}}', true) !== null) {
                $this->dropForeignKey('fk_school_section_lead_teacher', '{{%school_section}}');
            }
            $this->dropForeignKey('fk_school_section_grade', '{{%school_section}}');
            $this->dropTable('{{%school_section}}');
        }

        if ($this->db->schema->getTableSchema('{{%school_grade}}', true) !== null) {
            $this->dropForeignKey('fk_school_grade_department', '{{%school_grade}}');
            $this->dropTable('{{%school_grade}}');
        }
    }
}
