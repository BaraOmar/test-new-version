<?php

use yii\db\Migration;

class m260705_110000_create_finance_account_parent_child extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%finance_account_parent_child}}', true) !== null) {
            return;
        }

        $this->createTable('{{%finance_account_parent_child}}', [
            'id' => $this->primaryKey(),
            'parent_profile_id' => $this->integer()->notNull(),
            'child_index' => $this->integer()->notNull(),
            'birth_certificate_path' => $this->string(255)->null(),
            'student_file_path' => $this->string(255)->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(
            'idx_finance_account_parent_child_profile_index',
            '{{%finance_account_parent_child}}',
            ['parent_profile_id', 'child_index'],
            true
        );

        $this->addForeignKey(
            'fk_finance_account_parent_child_profile',
            '{{%finance_account_parent_child}}',
            'parent_profile_id',
            '{{%finance_account_parent_profile}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%finance_account_parent_child}}', true) === null) {
            return;
        }

        $this->dropForeignKey('fk_finance_account_parent_child_profile', '{{%finance_account_parent_child}}');
        $this->dropTable('{{%finance_account_parent_child}}');
    }
}
