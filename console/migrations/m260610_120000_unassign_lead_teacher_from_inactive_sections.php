<?php

use yii\db\Migration;

class m260610_120000_unassign_lead_teacher_from_inactive_sections extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_section}}', true) === null) {
            return;
        }

        $this->update(
            '{{%school_section}}',
            ['lead_teacher_id' => null, 'lead_teacher_assigned_at' => null],
            ['and', ['status' => 'inactive'], ['not', ['lead_teacher_id' => null]]]
        );
    }

    public function safeDown()
    {
        // Cannot restore previous class teacher assignments.
    }
}
