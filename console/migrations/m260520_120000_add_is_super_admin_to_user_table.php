<?php

use yii\db\Migration;
use yii\db\Query;

class m260520_120000_add_is_super_admin_to_user_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%user}}', true);
        if ($schema === null) {
            return;
        }

        if ($schema->getColumn('is_super_admin') === null) {
            $this->addColumn('{{%user}}', 'is_super_admin', $this->boolean()->notNull()->defaultValue(0));
        }

        $this->update('{{%user}}', ['is_super_admin' => 0]);

        $superAdminId = $this->resolveSuperAdminUserId();
        if ($superAdminId !== null) {
            $this->update('{{%user}}', ['is_super_admin' => 1], ['id' => $superAdminId]);
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%user}}', true);
        if ($schema === null || $schema->getColumn('is_super_admin') === null) {
            return;
        }

        $this->dropColumn('{{%user}}', 'is_super_admin');
    }

    private function resolveSuperAdminUserId(): ?int
    {
        $rows = (new Query())
            ->from('{{%user}}')
            ->where(['role' => ['hr', 'admin']])
            ->orderBy(['id' => SORT_ASC])
            ->all($this->db);

        foreach ($rows as $row) {
            if (strtolower((string)($row['username'] ?? '')) === 'super_hr') {
                return (int)$row['id'];
            }
        }

        foreach ($rows as $row) {
            $permissions = json_decode((string)($row['permissions'] ?? ''), true);
            if (!is_array($permissions)) {
                continue;
            }
            if (in_array('manage_hr', $permissions, true) && in_array('assign_permissions', $permissions, true)) {
                return (int)$row['id'];
            }
        }

        return isset($rows[0]['id']) ? (int)$rows[0]['id'] : null;
    }
}
