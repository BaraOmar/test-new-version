<?php

use yii\db\Migration;

class m260902_120000_add_is_exam_to_section_timetable_column extends Migration
{
    public function safeUp()
    {
        $table = '{{%school_section_timetable_column}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('is_exam') === null) {
            $this->addColumn($table, 'is_exam', $this->boolean()->notNull()->defaultValue(false)->after('is_break'));
        }
    }

    public function safeDown()
    {
        $table = '{{%school_section_timetable_column}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('is_exam') !== null) {
            $this->dropColumn($table, 'is_exam');
        }
    }
}
