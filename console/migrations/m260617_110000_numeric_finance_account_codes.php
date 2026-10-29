<?php

use yii\db\Migration;

class m260617_110000_numeric_finance_account_codes extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_account}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $rows = (new \yii\db\Query())
            ->from($table)
            ->select(['id'])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if ($rows === []) {
            return;
        }

        foreach ($rows as $row) {
            $this->update($table, ['code' => 'tmp_' . (int)$row['id']], ['id' => (int)$row['id']]);
        }

        $sequence = 1;
        foreach ($rows as $row) {
            $this->update($table, ['code' => (string)$sequence], ['id' => (int)$row['id']]);
            $sequence++;
        }
    }

    public function safeDown()
    {
        // Irreversible: original alphanumeric codes cannot be restored reliably.
    }
}
