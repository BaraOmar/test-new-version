<?php

use yii\db\Migration;
use yii\db\Query;

class m260830_120000_unique_parent_profile_phone extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_account_parent_profile}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $duplicates = (new Query())
            ->from($table)
            ->select(['phone'])
            ->groupBy(['phone'])
            ->having('COUNT(*) > 1')
            ->column($this->db);

        foreach ($duplicates as $phone) {
            $ids = (new Query())
                ->from($table)
                ->select(['id'])
                ->where(['phone' => $phone])
                ->orderBy(['id' => SORT_ASC])
                ->column($this->db);

            // Keep the oldest profile; make later ones unique so the index can be added.
            array_shift($ids);
            foreach ($ids as $id) {
                $this->update($table, [
                    'phone' => substr((string)$phone, 0, 20) . '-dup' . (int)$id,
                ], ['id' => (int)$id]);
            }
        }

        $this->createIndex(
            'uniq_finance_account_parent_profile_phone',
            $table,
            'phone',
            true
        );
    }

    public function safeDown()
    {
        $table = '{{%finance_account_parent_profile}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $this->dropIndex('uniq_finance_account_parent_profile_phone', $table);
    }
}
