<?php

use yii\db\Migration;

class m260619_100000_seed_liability_category_accounts extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_account}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $categories = [
            ['230000000', 'ذمم دائنة', 'liability'],
            ['240000000', 'حقوق ملكية', 'liability'],
            ['250000000', 'دائنة أخرى', 'liability'],
            ['260000000', 'مخصصات', 'liability'],
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

        $this->delete($table, ['code' => ['23000000', '24000000', '25000000', '26000000', '230000000', '240000000', '250000000', '260000000']]);
    }
}
