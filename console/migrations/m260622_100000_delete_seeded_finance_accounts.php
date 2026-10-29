<?php

use yii\db\Migration;

class m260622_100000_delete_seeded_finance_accounts extends Migration
{
    public function safeUp()
    {
        $accountTable = '{{%finance_account}}';
        $lineTable = '{{%finance_journal_entry_line}}';
        if ($this->db->schema->getTableSchema($accountTable, true) === null) {
            return;
        }

        $systemCodes = [
            '100000001',
            '100000002',
            '100000003',
            '12000000',
            '120000000',
            '13000000',
            '130000000',
            '14000000',
            '140000000',
            '200000001',
            '23000000',
            '230000000',
            '24000000',
            '240000000',
            '25000000',
            '250000000',
            '26000000',
            '260000000',
            '300000001',
            '31000000',
            '310000000',
            '32000000',
            '320000000',
            '400000001',
            '400000002',
            '500000001',
            '500000002',
            '500000003',
        ];

        $systemAccountIds = (new \yii\db\Query())
            ->from($accountTable)
            ->select('id')
            ->where(['code' => $systemCodes])
            ->column($this->db);

        if ($systemAccountIds === []) {
            return;
        }

        $this->update($accountTable, ['parent_id' => null], ['parent_id' => $systemAccountIds]);

        $referencedAccountIds = [];
        if ($this->db->schema->getTableSchema($lineTable, true) !== null) {
            $referencedAccountIds = (new \yii\db\Query())
                ->from($lineTable)
                ->select('account_id')
                ->where(['account_id' => $systemAccountIds])
                ->distinct()
                ->column($this->db);
        }

        $referencedAccountIds = array_map('intval', $referencedAccountIds);
        $deleteAccountIds = array_values(array_diff(array_map('intval', $systemAccountIds), $referencedAccountIds));

        if ($deleteAccountIds !== []) {
            $this->delete($accountTable, ['id' => $deleteAccountIds]);
        }

        if ($referencedAccountIds !== []) {
            $this->update($accountTable, [
                'is_active' => false,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $referencedAccountIds]);
        }
    }

    public function safeDown()
    {
        echo "m260622_100000_delete_seeded_finance_accounts cannot be reverted safely.\n";

        return false;
    }
}
