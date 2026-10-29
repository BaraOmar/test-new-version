<?php

use yii\db\Migration;

class m260707_130000_add_unique_phone_to_user_table extends Migration
{
    public function safeUp()
    {
        $table = '{{%user}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn('phone') === null) {
            return;
        }

        // Avoid duplicate index creation (portable enough for MySQL/MariaDB).
        $rawTableName = $this->db->schema->getRawTableName($table);
        $indexes = $this->db->createCommand('SHOW INDEX FROM ' . $this->db->quoteTableName($rawTableName))->queryAll();
        foreach ($indexes as $index) {
            $keyName = (string)($index['Key_name'] ?? '');
            $columnName = (string)($index['Column_name'] ?? '');
            $nonUnique = (int)($index['Non_unique'] ?? 1);
            if ($keyName === 'ux_user_phone' || ($columnName === 'phone' && $nonUnique === 0)) {
                return;
            }
        }

        $this->createIndex('ux_user_phone', $table, ['phone'], true);
    }

    public function safeDown()
    {
        $table = '{{%user}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn('phone') === null) {
            return;
        }

        $this->dropIndex('ux_user_phone', $table);
    }
}

