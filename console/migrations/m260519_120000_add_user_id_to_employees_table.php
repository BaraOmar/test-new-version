<?php

use yii\db\Migration;

class m260519_120000_add_user_id_to_employees_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null) {
            return;
        }

        if ($schema->getColumn('user_id') === null) {
            $this->addColumn('{{%employees}}', 'user_id', $this->integer()->null()->after('manager_id'));
            $this->createIndex('idx_employees_user_id', '{{%employees}}', 'user_id', true);

            $userSchema = $this->db->schema->getTableSchema('{{%user}}', true);
            if ($userSchema !== null && $schema->getColumn('email') !== null && $userSchema->getColumn('email') !== null) {
                $employees = $this->db->quoteTableName('{{%employees}}');
                $user = $this->db->quoteTableName('{{%user}}');
                $this->execute(
                    "UPDATE {$employees} e
                     INNER JOIN {$user} u ON u.email = e.email
                     SET e.user_id = u.id
                     WHERE e.user_id IS NULL AND e.email IS NOT NULL AND e.email <> ''"
                );
            }

            if ($userSchema !== null) {
                $this->addForeignKey(
                    'fk_employee_user',
                    '{{%employees}}',
                    'user_id',
                    '{{%user}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            }
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null) {
            return;
        }

        if ($schema->getColumn('user_id') !== null) {
            try {
                $this->dropForeignKey('fk_employee_user', '{{%employees}}');
            } catch (\Throwable $e) {
                // FK may not exist on partial installs.
            }
            try {
                $this->dropIndex('idx_employees_user_id', '{{%employees}}');
            } catch (\Throwable $e) {
                // Index may not exist.
            }
            $this->dropColumn('{{%employees}}', 'user_id');
        }
    }
}
