<?php

use yii\db\Migration;

class m260606_120000_add_specific_off_date_to_policies extends Migration
{
    public function safeUp()
    {
        $policySchema = $this->db->schema->getTableSchema('{{%attendance_off_day_policy}}', true);
        if ($policySchema !== null && $policySchema->getColumn('specific_off_date') === null) {
            $this->addColumn(
                '{{%attendance_off_day_policy}}',
                'specific_off_date',
                $this->date()->null()->after('off_days')
            );
        }

        if ($this->db->schema->getTableSchema('{{%attendance_off_date}}', true) !== null) {
            $this->dropTable('{{%attendance_off_date}}');
        }
    }

    public function safeDown()
    {
        $policySchema = $this->db->schema->getTableSchema('{{%attendance_off_day_policy}}', true);
        if ($policySchema !== null && $policySchema->getColumn('specific_off_date') !== null) {
            $this->dropColumn('{{%attendance_off_day_policy}}', 'specific_off_date');
        }

        echo "m260606_120000_add_specific_off_date_to_policies cannot fully restore attendance_off_date.\n";

        return false;
    }
}
