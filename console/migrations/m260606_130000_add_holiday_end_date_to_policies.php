<?php

use yii\db\Migration;

class m260606_130000_add_holiday_end_date_to_policies extends Migration
{
    public function safeUp()
    {
        $policySchema = $this->db->schema->getTableSchema('{{%attendance_off_day_policy}}', true);
        if ($policySchema !== null && $policySchema->getColumn('holiday_end_date') === null) {
            $this->addColumn(
                '{{%attendance_off_day_policy}}',
                'holiday_end_date',
                $this->date()->null()->after('specific_off_date')
            );
        }
    }

    public function safeDown()
    {
        $policySchema = $this->db->schema->getTableSchema('{{%attendance_off_day_policy}}', true);
        if ($policySchema !== null && $policySchema->getColumn('holiday_end_date') !== null) {
            $this->dropColumn('{{%attendance_off_day_policy}}', 'holiday_end_date');
        }
    }
}
