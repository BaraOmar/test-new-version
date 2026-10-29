<?php

use yii\db\Migration;

class m260902_130000_add_lesson_brief_to_timetable_slot extends Migration
{
    public function safeUp()
    {
        $table = '{{%school_section_timetable_slot}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('lesson_brief') === null) {
            $this->addColumn($table, 'lesson_brief', $this->text()->null()->after('room_number'));
        }
    }

    public function safeDown()
    {
        $table = '{{%school_section_timetable_slot}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema($table, true)->getColumn('lesson_brief') !== null) {
            $this->dropColumn($table, 'lesson_brief');
        }
    }
}
