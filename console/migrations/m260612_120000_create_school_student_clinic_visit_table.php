<?php

use yii\db\Migration;

class m260612_120000_create_school_student_clinic_visit_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_student}}', true) === null) {
            return;
        }

        $this->createTable('{{%school_student_clinic_visit}}', [
            'id' => $this->primaryKey(),
            'school_student_id' => $this->integer()->notNull(),
            'visit_date' => $this->date()->notNull(),
            'time_in' => $this->time()->notNull(),
            'primary_reason' => $this->string(255)->notNull(),
            'visit_type' => $this->string(32)->notNull()->defaultValue('routine_check'),
            'status' => $this->string(32)->notNull()->defaultValue('in_progress'),
            'medicine_given' => $this->boolean()->notNull()->defaultValue(false),
            'medicine_name' => $this->string(128)->null(),
            'dosage' => $this->string(64)->null(),
            'administration_time' => $this->time()->null(),
            'pharmaceutical_notes' => $this->text()->null(),
            'action_taken' => $this->string(64)->notNull()->defaultValue('rested_returned'),
            'clinical_notes' => $this->text()->null(),
            'recorded_by_user_id' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(
            'idx_school_student_clinic_visit_student',
            '{{%school_student_clinic_visit}}',
            'school_student_id'
        );
        $this->createIndex(
            'idx_school_student_clinic_visit_date',
            '{{%school_student_clinic_visit}}',
            ['visit_date', 'time_in']
        );

        $this->addForeignKey(
            'fk_school_student_clinic_visit_student',
            '{{%school_student_clinic_visit}}',
            'school_student_id',
            '{{%school_student}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        if ($this->db->schema->getTableSchema('{{%user}}', true) !== null) {
            $this->addForeignKey(
                'fk_school_student_clinic_visit_user',
                '{{%school_student_clinic_visit}}',
                'recorded_by_user_id',
                '{{%user}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_student_clinic_visit}}', true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema('{{%user}}', true) !== null) {
            $this->dropForeignKey('fk_school_student_clinic_visit_user', '{{%school_student_clinic_visit}}');
        }
        $this->dropForeignKey('fk_school_student_clinic_visit_student', '{{%school_student_clinic_visit}}');
        $this->dropTable('{{%school_student_clinic_visit}}');
    }
}
