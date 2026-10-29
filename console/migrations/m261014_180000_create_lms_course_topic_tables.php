<?php

use yii\db\Migration;

class m261014_180000_create_lms_course_topic_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_section_material}}', true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema('{{%school_lms_course_topic}}', true) === null) {
            $this->createTable('{{%school_lms_course_topic}}', [
                'id' => $this->primaryKey(),
                'school_section_material_id' => $this->integer()->notNull(),
                'name' => $this->string(255)->notNull(),
                'sequence' => $this->integer()->notNull()->defaultValue(10),
                'created_by_user_id' => $this->integer()->null(),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_lms_topic_course', '{{%school_lms_course_topic}}', 'school_section_material_id');
            $this->addForeignKey(
                'fk_lms_topic_course',
                '{{%school_lms_course_topic}}',
                'school_section_material_id',
                '{{%school_section_material}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if ($this->db->schema->getTableSchema('{{%school_lms_topic_file}}', true) === null) {
            $this->createTable('{{%school_lms_topic_file}}', [
                'id' => $this->primaryKey(),
                'topic_id' => $this->integer()->notNull(),
                'school_section_material_id' => $this->integer()->notNull(),
                'title' => $this->string(255)->notNull(),
                'original_name' => $this->string(255)->notNull(),
                'stored_name' => $this->string(255)->notNull(),
                'storage_path' => $this->string(500)->notNull(),
                'extension' => $this->string(16)->notNull(),
                'mime_type' => $this->string(150)->null(),
                'file_size' => $this->integer()->notNull()->defaultValue(0),
                'sequence' => $this->integer()->notNull()->defaultValue(10),
                'uploaded_by_user_id' => $this->integer()->null(),
                'created_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_lms_topic_file_topic', '{{%school_lms_topic_file}}', 'topic_id');
            $this->createIndex('idx_lms_topic_file_course', '{{%school_lms_topic_file}}', 'school_section_material_id');
            $this->addForeignKey(
                'fk_lms_topic_file_topic',
                '{{%school_lms_topic_file}}',
                'topic_id',
                '{{%school_lms_course_topic}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                'fk_lms_topic_file_course',
                '{{%school_lms_topic_file}}',
                'school_section_material_id',
                '{{%school_section_material}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_lms_topic_file}}', true) !== null) {
            $this->dropForeignKey('fk_lms_topic_file_course', '{{%school_lms_topic_file}}');
            $this->dropForeignKey('fk_lms_topic_file_topic', '{{%school_lms_topic_file}}');
            $this->dropTable('{{%school_lms_topic_file}}');
        }
        if ($this->db->schema->getTableSchema('{{%school_lms_course_topic}}', true) !== null) {
            $this->dropForeignKey('fk_lms_topic_course', '{{%school_lms_course_topic}}');
            $this->dropTable('{{%school_lms_course_topic}}');
        }
    }
}
