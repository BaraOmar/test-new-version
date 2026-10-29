<?php

use yii\db\Migration;

class m260507_081600_add_permissions_to_user_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%user}}', true);
        if ($schema !== null && $schema->getColumn('permissions') === null) {
            $this->addColumn('{{%user}}', 'permissions', $this->text()->null());
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%user}}', true);
        if ($schema !== null && $schema->getColumn('permissions') !== null) {
            $this->dropColumn('{{%user}}', 'permissions');
        }
    }
}

