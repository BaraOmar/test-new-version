<?php

use yii\db\Migration;

class m260617_120000_seed_asset_category_accounts extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_account}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $categories = [
            ['12000000', 'ثابتة', 'asset'],
            ['13000000', 'متداولة', 'asset'],
            ['14000000', 'ذمم مدينة', 'asset'],
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

        $this->delete($table, ['code' => ['12000000', '13000000', '14000000', '120000000', '130000000', '140000000']]);
    }
}
