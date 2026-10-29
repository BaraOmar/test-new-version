<?php

use yii\db\Migration;

class m260610_140000_add_unique_section_name_per_grade extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_section}}', true) === null) {
            return;
        }

        $indexes = $this->db->schema->getTableSchema('{{%school_section}}', true)->indexes ?? [];
        if (!isset($indexes['idx_school_section_grade_name'])) {
            $this->createIndex(
                'idx_school_section_grade_name',
                '{{%school_section}}',
                ['school_grade_id', 'name'],
                true
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_section}}', true) === null) {
            return;
        }

        $indexes = $this->db->schema->getTableSchema('{{%school_section}}', true)->indexes ?? [];
        if (isset($indexes['idx_school_section_grade_name'])) {
            $this->dropIndex('idx_school_section_grade_name', '{{%school_section}}');
        }
    }
}
