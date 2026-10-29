<?php

use yii\db\Migration;

class m260507_111700_add_status_to_employees_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null) {
            return;
        }

        if ($schema->getColumn('status') === null) {
            $this->addColumn('{{%employees}}', 'status', $this->string(32)->notNull()->defaultValue('Active')->after('manager_id'));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null) {
            return;
        }

        if ($schema->getColumn('status') !== null) {
            $this->dropColumn('{{%employees}}', 'status');
        }
    }
}

