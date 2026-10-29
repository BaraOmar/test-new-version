<?php

use yii\db\Migration;

class m260705_100000_create_finance_account_parent_profile extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%finance_account_parent_profile}}', true) !== null) {
            return;
        }

        $this->createTable('{{%finance_account_parent_profile}}', [
            'id' => $this->primaryKey(),
            'finance_account_id' => $this->integer()->notNull(),
            'first_name' => $this->string(128)->notNull(),
            'second_name' => $this->string(128)->null(),
            'third_name' => $this->string(128)->null(),
            'family_name' => $this->string(128)->notNull(),
            'phone' => $this->string(32)->notNull(),
            'emergency_phone_2' => $this->string(32)->notNull(),
            'email' => $this->string(255)->null(),
            'kids_count' => $this->integer()->notNull()->defaultValue(1),
            'family_notebook_path' => $this->string(255)->null(),
            'student_file_path' => $this->string(255)->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(
            'idx_finance_account_parent_profile_account',
            '{{%finance_account_parent_profile}}',
            'finance_account_id',
            true
        );

        $this->addForeignKey(
            'fk_finance_account_parent_profile_account',
            '{{%finance_account_parent_profile}}',
            'finance_account_id',
            '{{%finance_account}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%finance_account_parent_profile}}', true) === null) {
            return;
        }

        $this->dropForeignKey('fk_finance_account_parent_profile_account', '{{%finance_account_parent_profile}}');
        $this->dropTable('{{%finance_account_parent_profile}}');
    }
}
