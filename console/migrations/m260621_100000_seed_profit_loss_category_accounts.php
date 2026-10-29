<?php

use yii\db\Migration;

class m260621_100000_seed_profit_loss_category_accounts extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_account}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $categories = [
            ['31000000', 'إيرادات', 'revenue'],
            ['32000000', 'مصروفات', 'expense'],
        ];

        foreach ($categories as [$code, $name, $type]) {
            $exists = (new \yii\db\Query())
                ->from($table)
                ->where(['code' => $code])
                ->exists();

            if ($exists) {
                $this->update($table, [
                    'name' => $name,
                    'account_type' => $type,
                    'is_active' => true,
                    'updated_at' => $now,
                ], ['code' => $code]);

                continue;
            }

            $this->insert($table, [
                'code' => $code,
                'name' => $name,
                'account_type' => $type,
                'parent_id' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function safeDown()
    {
        $table = '{{%finance_account}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $this->delete($table, ['code' => ['31000000', '32000000', '310000000', '320000000']]);
    }
}
