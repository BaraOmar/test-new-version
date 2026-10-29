<?php

use yii\db\Migration;

class m260611_100000_create_employee_school_subject_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%employee_school_subject}}', true) !== null) {
            return;
        }

        $this->createTable('{{%employee_school_subject}}', [
            'id' => $this->primaryKey(),
            'employee_id' => $this->integer()->notNull(),
            'school_subject_id' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex('idx_employee_school_subject_employee', '{{%employee_school_subject}}', 'employee_id');
        $this->createIndex('idx_employee_school_subject_subject', '{{%employee_school_subject}}', 'school_subject_id');
        $this->createIndex(
            'uidx_employee_school_subject',
            '{{%employee_school_subject}}',
            ['employee_id', 'school_subject_id'],
            true
        );

        if ($this->db->schema->getTableSchema('{{%employees}}', true) !== null) {
            $this->addForeignKey(
                'fk_employee_school_subject_employee',
                '{{%employee_school_subject}}',
                'employee_id',
                '{{%employees}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if ($this->db->schema->getTableSchema('{{%school_subject}}', true) !== null) {
            $this->addForeignKey(
                'fk_employee_school_subject_subject',
                '{{%employee_school_subject}}',
                'school_subject_id',
                '{{%school_subject}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%employee_school_subject}}', true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema('{{%school_subject}}', true) !== null) {
            $this->dropForeignKey('fk_employee_school_subject_subject', '{{%employee_school_subject}}');
        }
        if ($this->db->schema->getTableSchema('{{%employees}}', true) !== null) {
            $this->dropForeignKey('fk_employee_school_subject_employee', '{{%employee_school_subject}}');
        }

        $this->dropTable('{{%employee_school_subject}}');
    }
}
