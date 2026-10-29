<?php

use yii\db\Migration;

/**
 * Link parent profiles to LMS portal login users.
 */
class m260830_140000_parent_portal_login extends Migration
{
    public function safeUp()
    {
        $profileTable = '{{%finance_account_parent_profile}}';
        $userTable = '{{%user}}';

        $profileSchema = $this->db->schema->getTableSchema($profileTable, true);
        if ($profileSchema !== null && $profileSchema->getColumn('user_id') === null) {
            $this->addColumn($profileTable, 'user_id', $this->integer()->null()->after('finance_account_id'));
            $this->createIndex('uniq_finance_account_parent_profile_user', $profileTable, 'user_id', true);
            $this->addForeignKey(
                'fk_finance_account_parent_profile_user',
                $profileTable,
                'user_id',
                $userTable,
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        $userSchema = $this->db->schema->getTableSchema($userTable, true);
        if ($userSchema !== null && $userSchema->getColumn('system_password_fingerprint') === null) {
            $this->addColumn($userTable, 'system_password_fingerprint', $this->string(64)->null());
            $this->createIndex(
                'idx_user_system_password_fingerprint',
                $userTable,
                'system_password_fingerprint'
            );
        }
        if ($userSchema !== null && $userSchema->getColumn('password_changed_by_user') === null) {
            $this->addColumn($userTable, 'password_changed_by_user', $this->boolean()->notNull()->defaultValue(false));
        }
    }

    public function safeDown()
    {
        $profileTable = '{{%finance_account_parent_profile}}';
        $userTable = '{{%user}}';

        $profileSchema = $this->db->schema->getTableSchema($profileTable, true);
        if ($profileSchema !== null && $profileSchema->getColumn('user_id') !== null) {
            $this->dropForeignKey('fk_finance_account_parent_profile_user', $profileTable);
            $this->dropIndex('uniq_finance_account_parent_profile_user', $profileTable);
            $this->dropColumn($profileTable, 'user_id');
        }

        $userSchema = $this->db->schema->getTableSchema($userTable, true);
        if ($userSchema !== null && $userSchema->getColumn('system_password_fingerprint') !== null) {
            $this->dropIndex('idx_user_system_password_fingerprint', $userTable);
            $this->dropColumn($userTable, 'system_password_fingerprint');
        }
        if ($userSchema !== null && $userSchema->getColumn('password_changed_by_user') !== null) {
            $this->dropColumn($userTable, 'password_changed_by_user');
        }
    }
}
