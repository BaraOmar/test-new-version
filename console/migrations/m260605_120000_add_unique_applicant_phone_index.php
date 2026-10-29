<?php

use yii\db\Migration;

class m260605_120000_add_unique_applicant_phone_index extends Migration
{
    public function safeUp()
    {
        $table = '{{%users}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn('phone') === null) {
            return;
        }

        $indexes = $this->db->schema->getTableIndexes($table, true);
        foreach ($indexes as $index) {
            if ($index->name === 'idx_rms_users_phone_role') {
                return;
            }
        }

        $this->createIndex('idx_rms_users_phone_role', $table, ['phone', 'role_id'], true);
    }

    public function safeDown()
    {
        $table = '{{%users}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $this->dropIndex('idx_rms_users_phone_role', $table);
    }
}
