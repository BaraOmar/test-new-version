<?php

use yii\db\Migration;

class m260610_150000_add_subject_grade_credits_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_subject}}', true) !== null) {
            $indexes = $this->db->schema->getTableSchema('{{%school_subject}}', true)->indexes ?? [];
            if (!isset($indexes['idx_school_subject_department_name'])) {
                $this->createIndex(
                    'idx_school_subject_department_name',
                    '{{%school_subject}}',
                    ['school_department_id', 'name'],
                    true
                );
            }
        }

        if ($this->db->schema->getTableSchema('{{%school_subject_grade}}', true) !== null) {
            return;
        }

        $this->createTable('{{%school_subject_grade}}', [
            'id' => $this->primaryKey(),
            'school_subject_id' => $this->integer()->notNull(),
            'school_grade_id' => $this->integer()->notNull(),
            'credits' => $this->decimal(8, 2)->notNull()->defaultValue(0),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex('idx_subject_grade_subject', '{{%school_subject_grade}}', 'school_subject_id');
        $this->createIndex('idx_subject_grade_grade', '{{%school_subject_grade}}', 'school_grade_id');
        $this->createIndex(
            'idx_subject_grade_unique',
            '{{%school_subject_grade}}',
            ['school_subject_id', 'school_grade_id'],
            true
        );

        $this->addForeignKey(
            'fk_subject_grade_subject',
            '{{%school_subject_grade}}',
            'school_subject_id',
            '{{%school_subject}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        if ($this->db->schema->getTableSchema('{{%school_grade}}', true) !== null) {
            $this->addForeignKey(
                'fk_subject_grade_grade',
                '{{%school_subject_grade}}',
                'school_grade_id',
                '{{%school_grade}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_subject_grade}}', true) !== null) {
            if ($this->db->schema->getTableSchema('{{%school_grade}}', true) !== null) {
                $this->dropForeignKey('fk_subject_grade_grade', '{{%school_subject_grade}}');
            }
            $this->dropForeignKey('fk_subject_grade_subject', '{{%school_subject_grade}}');
            $this->dropTable('{{%school_subject_grade}}');
        }

        $indexes = $this->db->schema->getTableSchema('{{%school_subject}}', true)->indexes ?? [];
        if (isset($indexes['idx_school_subject_department_name'])) {
            $this->dropIndex('idx_school_subject_department_name', '{{%school_subject}}');
        }
    }
}
