<?php

use yii\db\Migration;

class m260612_100000_create_school_student_health_file_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_student}}', true) === null) {
            return;
        }

        $this->createTable('{{%school_student_health_file}}', [
            'id' => $this->primaryKey(),
            'school_student_id' => $this->integer()->notNull(),
            'health_risk' => $this->string(32)->notNull()->defaultValue('normal_risk'),
            'allergy_status' => $this->string(32)->notNull()->defaultValue('none'),
            'allergy_detail' => $this->string(255)->null(),
            'chronic_conditions' => $this->text()->null(),
            'medical_history' => $this->text()->null(),
            'last_visit_date' => $this->date()->null(),
            'notes' => $this->text()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(
            'idx_school_student_health_file_student',
            '{{%school_student_health_file}}',
            'school_student_id',
            true
        );

        $this->addForeignKey(
            'fk_school_student_health_file_student',
            '{{%school_student_health_file}}',
            'school_student_id',
            '{{%school_student}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_student_health_file}}', true) === null) {
            return;
        }

        $this->dropForeignKey('fk_school_student_health_file_student', '{{%school_student_health_file}}');
        $this->dropTable('{{%school_student_health_file}}');
    }
}
