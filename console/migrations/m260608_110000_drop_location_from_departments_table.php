<?php

use yii\db\Migration;

/**
 * Removes location from departments — no longer used.
 */
class m260608_110000_drop_location_from_departments_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%departments}}', true);
        if ($schema === null || !isset($schema->columns['location'])) {
            return;
        }

        $this->dropColumn('{{%departments}}', 'location');
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%departments}}', true);
        if ($schema === null || isset($schema->columns['location'])) {
            return;
        }

        $after = isset($schema->columns['status']) ? 'status' : 'name';
        $this->addColumn('{{%departments}}', 'location', $this->string(255)->null()->after($after));
    }
}
