<?php

use yii\db\Migration;

class m260706_100000_create_finance_payment_collection_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%finance_student_installment}}', true) !== null) {
            return;
        }

        $this->createTable('{{%finance_student_installment}}', [
            'id' => $this->primaryKey(),
            'parent_profile_id' => $this->integer()->null(),
            'school_student_id' => $this->integer()->null(),
            'title' => $this->string(255)->notNull(),
            'academic_year' => $this->string(32)->null(),
            'academic_term' => $this->string(64)->null(),
            'amount_due' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'amount_paid' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'includes_vat' => $this->boolean()->notNull()->defaultValue(true),
            'due_date' => $this->date()->notNull(),
            'status' => $this->string(32)->notNull()->defaultValue('pending'),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('idx_finance_student_installment_student', '{{%finance_student_installment}}', 'school_student_id');
        $this->createIndex('idx_finance_student_installment_parent', '{{%finance_student_installment}}', 'parent_profile_id');
        $this->createIndex('idx_finance_student_installment_due', '{{%finance_student_installment}}', 'due_date');

        if ($this->db->schema->getTableSchema('{{%finance_account_parent_profile}}', true) !== null) {
            $this->addForeignKey(
                'fk_finance_student_installment_parent',
                '{{%finance_student_installment}}',
                'parent_profile_id',
                '{{%finance_account_parent_profile}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        if ($this->db->schema->getTableSchema('{{%school_student}}', true) !== null) {
            $this->addForeignKey(
                'fk_finance_student_installment_student',
                '{{%finance_student_installment}}',
                'school_student_id',
                '{{%school_student}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        $this->createTable('{{%finance_payment_receipt}}', [
            'id' => $this->primaryKey(),
            'receipt_number' => $this->string(64)->notNull(),
            'parent_profile_id' => $this->integer()->notNull(),
            'school_student_id' => $this->integer()->null(),
            'primary_installment_id' => $this->integer()->null(),
            'paid_amount' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'discount_amount' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'net_amount' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'payment_date' => $this->date()->notNull(),
            'payment_method' => $this->string(32)->notNull(),
            'reference_number' => $this->string(128)->null(),
            'attachment_path' => $this->string(255)->null(),
            'auto_distribute' => $this->boolean()->notNull()->defaultValue(true),
            'notes' => $this->text()->null(),
            'journal_entry_id' => $this->integer()->null(),
            'created_by_user_id' => $this->integer()->null(),
            'is_printed' => $this->boolean()->notNull()->defaultValue(false),
            'printed_at' => $this->dateTime()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('idx_finance_payment_receipt_number', '{{%finance_payment_receipt}}', 'receipt_number', true);
        $this->createIndex('idx_finance_payment_receipt_parent', '{{%finance_payment_receipt}}', 'parent_profile_id');
        $this->createIndex('idx_finance_payment_receipt_student', '{{%finance_payment_receipt}}', 'school_student_id');
        $this->createIndex('idx_finance_payment_receipt_date', '{{%finance_payment_receipt}}', 'payment_date');

        if ($this->db->schema->getTableSchema('{{%finance_account_parent_profile}}', true) !== null) {
            $this->addForeignKey(
                'fk_finance_payment_receipt_parent',
                '{{%finance_payment_receipt}}',
                'parent_profile_id',
                '{{%finance_account_parent_profile}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if ($this->db->schema->getTableSchema('{{%school_student}}', true) !== null) {
            $this->addForeignKey(
                'fk_finance_payment_receipt_student',
                '{{%finance_payment_receipt}}',
                'school_student_id',
                '{{%school_student}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        if ($this->db->schema->getTableSchema('{{%finance_journal_entry}}', true) !== null) {
            $this->addForeignKey(
                'fk_finance_payment_receipt_journal',
                '{{%finance_payment_receipt}}',
                'journal_entry_id',
                '{{%finance_journal_entry}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        if ($this->db->schema->getTableSchema('{{%user}}', true) !== null) {
            $this->addForeignKey(
                'fk_finance_payment_receipt_created_by',
                '{{%finance_payment_receipt}}',
                'created_by_user_id',
                '{{%user}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        $this->createTable('{{%finance_payment_receipt_allocation}}', [
            'id' => $this->primaryKey(),
            'payment_receipt_id' => $this->integer()->notNull(),
            'installment_id' => $this->integer()->notNull(),
            'amount_applied' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('idx_finance_payment_receipt_allocation_receipt', '{{%finance_payment_receipt_allocation}}', 'payment_receipt_id');
        $this->createIndex('idx_finance_payment_receipt_allocation_installment', '{{%finance_payment_receipt_allocation}}', 'installment_id');

        $this->addForeignKey(
            'fk_finance_payment_receipt_allocation_receipt',
            '{{%finance_payment_receipt_allocation}}',
            'payment_receipt_id',
            '{{%finance_payment_receipt}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_finance_payment_receipt_allocation_installment',
            '{{%finance_payment_receipt_allocation}}',
            'installment_id',
            '{{%finance_student_installment}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_finance_payment_receipt_installment',
            '{{%finance_payment_receipt}}',
            'primary_installment_id',
            '{{%finance_student_installment}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_finance_payment_receipt_installment', '{{%finance_payment_receipt}}');
        $this->dropTable('{{%finance_payment_receipt_allocation}}');
        $this->dropTable('{{%finance_payment_receipt}}');
        $this->dropTable('{{%finance_student_installment}}');
    }
}
