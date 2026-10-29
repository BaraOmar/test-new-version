<?php

use yii\db\Migration;

class m260618_100000_fix_asset_account_code_bases extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_account}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $rows = (new \yii\db\Query())
            ->from($table)
            ->select(['id', 'code', 'parent_id'])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if ($rows === []) {
            return;
        }

        foreach ($rows as $row) {
            $this->update($table, ['code' => 'tmp_' . (int)$row['id']], ['id' => (int)$row['id']]);
        }

        $categoryMap = [
            '120000000' => '12000000',
            '130000000' => '13000000',
            '140000000' => '14000000',
            '12000000' => '12000000',
            '13000000' => '13000000',
            '14000000' => '14000000',
        ];

        $categoryIds = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $originalCode = (string)$row['code'];
            if (isset($categoryMap[$originalCode])) {
                $newCode = $categoryMap[$originalCode];
                $this->update($table, ['code' => $newCode], ['id' => $id]);
                $categoryIds[$newCode] = $id;
            }
        }

        if (!isset($categoryIds['12000000'])) {
            $now = date('Y-m-d H:i:s');
            $this->insert($table, [
                'code' => '12000000',
                'name' => 'ثابتة',
                'account_type' => 'asset',
                'parent_id' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $categoryIds['12000000'] = (int)$this->db->getLastInsertID();
        }

        $prefixByParentCode = [
            '12000000' => 12000000,
            '13000000' => 13000000,
            '14000000' => 14000000,
        ];

        $nextByPrefix = [
            10000000 => 100000001,
            12000000 => 120000001,
            13000000 => 130000001,
            14000000 => 140000001,
            20000000 => 200000001,
            30000000 => 300000001,
            40000000 => 400000001,
            50000000 => 500000001,
        ];

        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $originalCode = (string)$row['code'];
            if (isset($categoryMap[$originalCode])) {
                continue;
            }

            $parentId = $row['parent_id'] !== null ? (int)$row['parent_id'] : null;
            $prefix = 10000000;
            if ($parentId !== null) {
                $parentCode = (new \yii\db\Query())
                    ->from($table)
                    ->select('code')
                    ->where(['id' => $parentId])
                    ->scalar();
                if (is_string($parentCode) && isset($prefixByParentCode[$parentCode])) {
                    $prefix = $prefixByParentCode[$parentCode];
                }
            } else {
                $accountType = (new \yii\db\Query())
                    ->from($table)
                    ->select('account_type')
                    ->where(['id' => $id])
                    ->scalar();
                $prefix = match ($accountType) {
                    'liability' => 20000000,
                    'equity' => 30000000,
                    'revenue' => 40000000,
                    'expense' => 50000000,
                    default => 10000000,
                };
            }

            $nextCode = $nextByPrefix[$prefix];
            $nextByPrefix[$prefix] = $nextCode + 1;
            $this->update($table, ['code' => (string)$nextCode], ['id' => $id]);
        }
    }

    public function safeDown()
    {
        // Irreversible data correction.
    }
}
