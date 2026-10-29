<?php

use yii\db\Migration;

class m260616_100000_create_finance_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%finance_account}}', true) !== null) {
            return;
        }

        $this->createTable('{{%finance_account}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(32)->notNull(),
            'name' => $this->string(255)->notNull(),
            'account_type' => $this->string(32)->notNull(),
            'parent_id' => $this->integer()->null(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('idx_finance_account_code', '{{%finance_account}}', 'code', true);
        $this->createIndex('idx_finance_account_type', '{{%finance_account}}', 'account_type');
        $this->addForeignKey(
            'fk_finance_account_parent',
            '{{%finance_account}}',
            'parent_id',
            '{{%finance_account}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->createTable('{{%finance_journal_entry}}', [
            'id' => $this->primaryKey(),
            'entry_number' => $this->string(64)->notNull(),
            'entry_date' => $this->date()->notNull(),
            'description' => $this->text()->notNull(),
            'notes' => $this->text()->null(),
            'status' => $this->string(32)->notNull()->defaultValue('draft'),
            'total_debit' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'total_credit' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'created_by_user_id' => $this->integer()->null(),
            'approved_by_user_id' => $this->integer()->null(),
            'approved_at' => $this->dateTime()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('idx_finance_journal_entry_number', '{{%finance_journal_entry}}', 'entry_number', true);
        $this->createIndex('idx_finance_journal_entry_date', '{{%finance_journal_entry}}', 'entry_date');
        $this->createIndex('idx_finance_journal_entry_status', '{{%finance_journal_entry}}', 'status');

        if ($this->db->schema->getTableSchema('{{%user}}', true) !== null) {
            $this->addForeignKey(
                'fk_finance_journal_entry_created_by',
                '{{%finance_journal_entry}}',
                'created_by_user_id',
                '{{%user}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
            $this->addForeignKey(
                'fk_finance_journal_entry_approved_by',
                '{{%finance_journal_entry}}',
                'approved_by_user_id',
                '{{%user}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        $this->createTable('{{%finance_journal_entry_line}}', [
            'id' => $this->primaryKey(),
            'journal_entry_id' => $this->integer()->notNull(),
            'account_id' => $this->integer()->notNull(),
            'debit_amount' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'credit_amount' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'line_order' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('idx_finance_journal_entry_line_entry', '{{%finance_journal_entry_line}}', 'journal_entry_id');
        $this->createIndex('idx_finance_journal_entry_line_account', '{{%finance_journal_entry_line}}', 'account_id');
        $this->addForeignKey(
            'fk_finance_journal_entry_line_entry',
            '{{%finance_journal_entry_line}}',
            'journal_entry_id',
            '{{%finance_journal_entry}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_finance_journal_entry_line_account',
            '{{%finance_journal_entry_line}}',
            'account_id',
            '{{%finance_account}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $now = date('Y-m-d H:i:s');
        $accounts = [
            ['Cash', 'asset'],
            ['Al Rajhi Bank', 'asset'],
            ['Salary Expenses', 'expense'],
            ['Rent Expenses', 'expense'],
            ['Office Supplies Expenses', 'expense'],
            ['Tuition Revenue', 'revenue'],
            ['Grants Revenue', 'revenue'],
            ['Accounts Payable', 'liability'],
            ['Accounts Receivable', 'asset'],
            ['Retained Earnings', 'equity'],
        ];

        $sequence = 1;
        foreach ($accounts as [$name, $type]) {
            $this->insert('{{%finance_account}}', [
                'code' => (string)$sequence,
                'name' => $name,
                'account_type' => $type,
                'parent_id' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $sequence++;
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%finance_journal_entry_line}}', true) !== null) {
            $this->dropForeignKey('fk_finance_journal_entry_line_account', '{{%finance_journal_entry_line}}');
            $this->dropForeignKey('fk_finance_journal_entry_line_entry', '{{%finance_journal_entry_line}}');
            $this->dropTable('{{%finance_journal_entry_line}}');
        }

        if ($this->db->schema->getTableSchema('{{%finance_journal_entry}}', true) !== null) {
            if ($this->db->schema->getTableSchema('{{%user}}', true) !== null) {
                $this->dropForeignKey('fk_finance_journal_entry_approved_by', '{{%finance_journal_entry}}');
                $this->dropForeignKey('fk_finance_journal_entry_created_by', '{{%finance_journal_entry}}');
            }
            $this->dropTable('{{%finance_journal_entry}}');
        }

        if ($this->db->schema->getTableSchema('{{%finance_account}}', true) !== null) {
            $this->dropForeignKey('fk_finance_account_parent', '{{%finance_account}}');
            $this->dropTable('{{%finance_account}}');
        }
    }
}
