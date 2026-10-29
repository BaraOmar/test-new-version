<?php

use yii\db\Migration;

class m260506_120000_add_role_to_user_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%user}}', true);
        if ($schema !== null && $schema->getColumn('role') === null) {
            $this->addColumn('{{%user}}', 'role', $this->string(50)->defaultValue('employee'));
        }

        $this->update('{{%user}}', ['role' => 'employee'], ['role' => null]);
        $this->update('{{%user}}', ['role' => 'admin'], ['username' => 'admin']);
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%user}}', true);
        if ($schema !== null && $schema->getColumn('role') !== null) {
            $this->dropColumn('{{%user}}', 'role');
        }
    }
}
