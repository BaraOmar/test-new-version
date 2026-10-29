<?php

use yii\db\Migration;

/**
 * Allow parent profiles without a linked finance account (LMS Project).
 */
class m260823_100000_make_parent_profile_finance_account_optional extends Migration
{
    public function safeUp()
    {
        $table = '{{%finance_account_parent_profile}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn('finance_account_id') === null) {
            return;
        }

        $this->dropForeignKey('fk_finance_account_parent_profile_account', $table);
        $this->dropIndex('idx_finance_account_parent_profile_account', $table);
        $this->alterColumn($table, 'finance_account_id', $this->integer()->null());
        $this->createIndex('idx_finance_account_parent_profile_account', $table, 'finance_account_id');
        $this->addForeignKey(
            'fk_finance_account_parent_profile_account',
            $table,
            'finance_account_id',
            '{{%finance_account}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $table = '{{%finance_account_parent_profile}}';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn('finance_account_id') === null) {
            return;
        }

        $this->dropForeignKey('fk_finance_account_parent_profile_account', $table);
        $this->dropIndex('idx_finance_account_parent_profile_account', $table);
        $this->alterColumn($table, 'finance_account_id', $this->integer()->notNull());
        $this->createIndex('idx_finance_account_parent_profile_account', $table, 'finance_account_id', true);
        $this->addForeignKey(
            'fk_finance_account_parent_profile_account',
            $table,
            'finance_account_id',
            '{{%finance_account}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }
}
