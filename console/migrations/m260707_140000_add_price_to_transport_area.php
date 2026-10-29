<?php

use yii\db\Migration;

class m260707_140000_add_price_to_transport_area extends Migration
{
    public function safeUp()
    {
        $table = '{{%transport_area}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $schema = $this->db->schema->getTableSchema($table, true);
        if (!isset($schema->columns['price'])) {
            $this->addColumn($table, 'price', $this->decimal(15, 2)->null()->after('description'));
        }
    }

    public function safeDown()
    {
        $table = '{{%transport_area}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $schema = $this->db->schema->getTableSchema($table, true);
        if (isset($schema->columns['price'])) {
            $this->dropColumn($table, 'price');
        }
    }
}
