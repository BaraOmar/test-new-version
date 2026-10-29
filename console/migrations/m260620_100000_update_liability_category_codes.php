<?php

use yii\db\Migration;

class m260620_100000_update_liability_category_codes extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_account}}';
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }

        $map = [
            '23000000' => ['230000000', 'ذمم دائنة'],
            '24000000' => ['240000000', 'حقوق ملكية'],
            '25000000' => ['250000000', 'دائنة أخرى'],
            '26000000' => ['260000000', 'مخصصات'],
            '230000000' => ['230000000', 'ذمم دائنة'],
            '240000000' => ['240000000', 'حقوق ملكية'],
            '250000000' => ['250000000', 'دائنة أخرى'],
            '260000000' => ['260000000', 'مخصصات'],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($map as $fromCode => [$toCode, $name]) {
            if ($fromCode === $toCode) {
                continue;
            }

            $exists = (new \yii\db\Query())->from($table)->where(['code' => $fromCode])->exists();
            if (!$exists) {
                continue;
            }

            $targetExists = (new \yii\db\Query())->from($table)->where(['code' => $toCode])->exists();
            if ($targetExists) {
                $this->delete($table, ['code' => $fromCode]);

                continue;
            }

            $this->update($table, [
                'code' => $toCode,
                'name' => $name,
                'account_type' => 'liability',
                'updated_at' => $now,
            ], ['code' => $fromCode]);
        }

        $categories = [
            ['230000000', 'ذمم دائنة'],
            ['240000000', 'حقوق ملكية'],
            ['250000000', 'دائنة أخرى'],
            ['260000000', 'مخصصات'],
        ];

        foreach ($categories as [$code, $name]) {
            if ((new \yii\db\Query())->from($table)->where(['code' => $code])->exists()) {
                $this->update($table, ['name' => $name, 'updated_at' => $now], ['code' => $code]);

                continue;
            }

            $this->insert($table, [
                'code' => $code,
                'name' => $name,
                'account_type' => 'liability',
                'parent_id' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function safeDown()
    {
    }
}
