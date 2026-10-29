<?php

use yii\db\Migration;

class m260517_120000_add_location_to_employees_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null) {
            return;
        }

        if ($schema->getColumn('location') === null) {
            $this->addColumn('{{%employees}}', 'location', $this->string(32)->null());
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null) {
            return;
        }

        if ($schema->getColumn('location') !== null) {
            $this->dropColumn('{{%employees}}', 'location');
        }
    }
}
