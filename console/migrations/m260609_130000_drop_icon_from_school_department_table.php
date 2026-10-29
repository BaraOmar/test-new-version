<?php

use yii\db\Migration;

class m260609_130000_drop_icon_from_school_department_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_department}}', true);
        if ($schema !== null && isset($schema->columns['icon'])) {
            $this->dropColumn('{{%school_department}}', 'icon');
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_department}}', true);
        if ($schema !== null && !isset($schema->columns['icon'])) {
            $this->addColumn('{{%school_department}}', 'icon', $this->string(64)->notNull()->defaultValue('account_balance')->after('name'));
        }
    }
}
