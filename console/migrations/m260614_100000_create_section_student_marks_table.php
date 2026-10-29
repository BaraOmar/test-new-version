<?php

use yii\db\Migration;

class m260614_100000_create_section_student_marks_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_student}}', true) === null
            || $this->db->schema->getTableSchema('{{%school_section_material}}', true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema('{{%school_student_section_mark}}', true) !== null) {
            return;
        }

        $this->createTable('{{%school_student_section_mark}}', [
            'id' => $this->primaryKey(),
            'school_student_id' => $this->integer()->notNull(),
            'school_section_material_id' => $this->integer()->notNull(),
            'mark_first' => $this->decimal(6, 2)->null(),
            'mark_second' => $this->decimal(6, 2)->null(),
            'mark_third' => $this->decimal(6, 2)->null(),
            'mark_final' => $this->decimal(6, 2)->null(),
            'total' => $this->decimal(6, 2)->null(),
            'status' => $this->string(16)->null(),
            'updated_by_user_id' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(
            'idx_school_student_section_mark_student_material',
            '{{%school_student_section_mark}}',
            ['school_student_id', 'school_section_material_id'],
            true
        );
        $this->createIndex(
            'idx_school_student_section_mark_material',
            '{{%school_student_section_mark}}',
            'school_section_material_id'
        );

        $this->addForeignKey(
            'fk_school_student_section_mark_student',
            '{{%school_student_section_mark}}',
            'school_student_id',
            '{{%school_student}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_school_student_section_mark_material',
            '{{%school_student_section_mark}}',
            'school_section_material_id',
            '{{%school_section_material}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        if ($this->db->schema->getTableSchema('{{%user}}', true) !== null) {
            $this->addForeignKey(
                'fk_school_student_section_mark_user',
                '{{%school_student_section_mark}}',
                'updated_by_user_id',
                '{{%user}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_student_section_mark}}', true) === null) {
            return;
        }

        $table = $this->db->schema->getTableSchema('{{%school_student_section_mark}}', true);
        if ($table !== null && isset($table->foreignKeys['fk_school_student_section_mark_user'])) {
            $this->dropForeignKey('fk_school_student_section_mark_user', '{{%school_student_section_mark}}');
        }

        $this->dropForeignKey('fk_school_student_section_mark_material', '{{%school_student_section_mark}}');
        $this->dropForeignKey('fk_school_student_section_mark_student', '{{%school_student_section_mark}}');
        $this->dropTable('{{%school_student_section_mark}}');
    }
}
