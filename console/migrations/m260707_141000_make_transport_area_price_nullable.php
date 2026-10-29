<?php

use yii\db\Migration;

class m260707_141000_make_transport_area_price_nullable extends Migration
{
    public function safeUp()
    {
        $table = '{{%transport_area}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $schema = $this->db->schema->getTableSchema($table, true);
        if (!isset($schema->columns['price'])) {
            return;
        }

        $this->alterColumn($table, 'price', $this->decimal(15, 2)->null());
    }

    public function safeDown()
    {
        $table = '{{%transport_area}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $schema = $this->db->schema->getTableSchema($table, true);
        if (!isset($schema->columns['price'])) {
            return;
        }

        $this->alterColumn($table, 'price', $this->decimal(15, 2)->notNull()->defaultValue(0));
    }
}
