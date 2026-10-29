<?php

use yii\db\Migration;

class m260610_130000_drop_status_from_school_section extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_section}}', true);
        if ($schema === null || !isset($schema->columns['status'])) {
            return;
        }

        $this->dropColumn('{{%school_section}}', 'status');
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_section}}', true);
        if ($schema === null || isset($schema->columns['status'])) {
            return;
        }

        $this->addColumn(
            '{{%school_section}}',
            'status',
            $this->string(16)->notNull()->defaultValue('active')->after('lead_teacher_id')
        );
    }
}
