<?php

use yii\db\Migration;

class m260611_141000_create_student_documents_and_siblings extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_student_document}}', true) === null) {
            $this->createTable('{{%school_student_document}}', [
                'id' => $this->primaryKey(),
                'school_student_id' => $this->integer()->notNull(),
                'file_name' => $this->string(255)->notNull(),
                'file_path' => $this->string(255)->notNull(),
                'mime_type' => $this->string(128)->null(),
                'file_size' => $this->integer()->null(),
                'created_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_student_document_student', '{{%school_student_document}}', 'school_student_id');
            if ($this->db->schema->getTableSchema('{{%school_student}}', true) !== null) {
                $this->addForeignKey(
                    'fk_student_document_student',
                    '{{%school_student_document}}',
                    'school_student_id',
                    '{{%school_student}}',
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
            }
        }

        if ($this->db->schema->getTableSchema('{{%school_student_sibling}}', true) === null) {
            $this->createTable('{{%school_student_sibling}}', [
                'id' => $this->primaryKey(),
                'school_student_id' => $this->integer()->notNull(),
                'sibling_student_id' => $this->integer()->notNull(),
                'created_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_student_sibling_student', '{{%school_student_sibling}}', 'school_student_id');
            $this->createIndex('idx_student_sibling_related', '{{%school_student_sibling}}', 'sibling_student_id');
            $this->createIndex(
                'uidx_student_sibling_pair',
                '{{%school_student_sibling}}',
                ['school_student_id', 'sibling_student_id'],
                true
            );
            if ($this->db->schema->getTableSchema('{{%school_student}}', true) !== null) {
                $this->addForeignKey(
                    'fk_student_sibling_student',
                    '{{%school_student_sibling}}',
                    'school_student_id',
                    '{{%school_student}}',
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
                $this->addForeignKey(
                    'fk_student_sibling_related',
                    '{{%school_student_sibling}}',
                    'sibling_student_id',
                    '{{%school_student}}',
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
            }
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_student_sibling}}', true) !== null) {
            $this->dropForeignKey('fk_student_sibling_related', '{{%school_student_sibling}}');
            $this->dropForeignKey('fk_student_sibling_student', '{{%school_student_sibling}}');
            $this->dropTable('{{%school_student_sibling}}');
        }
        if ($this->db->schema->getTableSchema('{{%school_student_document}}', true) !== null) {
            $this->dropForeignKey('fk_student_document_student', '{{%school_student_document}}');
            $this->dropTable('{{%school_student_document}}');
        }
    }
}
