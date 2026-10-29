<?php

use yii\db\Migration;

class m260612_110000_add_student_health_permission_to_hr_users extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%user}}', true) === null) {
            return;
        }

        $rows = $this->db->createCommand(
            "SELECT id, permissions FROM {{%user}} WHERE role = 'hr' AND permissions IS NOT NULL AND permissions != ''"
        )->queryAll();

        foreach ($rows as $row) {
            $decoded = json_decode((string)($row['permissions'] ?? ''), true);
            if (!is_array($decoded)) {
                continue;
            }

            $permissions = array_values(array_map('strval', $decoded));
            if (!in_array('employees', $permissions, true) || in_array('student_health', $permissions, true)) {
                continue;
            }

            $permissions[] = 'student_health';
            sort($permissions);

            $this->update('{{%user}}', [
                'permissions' => json_encode($permissions, JSON_UNESCAPED_SLASHES),
            ], ['id' => (int)$row['id']]);
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%user}}', true) === null) {
            return;
        }

        $rows = $this->db->createCommand(
            "SELECT id, permissions FROM {{%user}} WHERE role = 'hr' AND permissions IS NOT NULL AND permissions != ''"
        )->queryAll();

        foreach ($rows as $row) {
            $decoded = json_decode((string)($row['permissions'] ?? ''), true);
            if (!is_array($decoded)) {
                continue;
            }

            $permissions = array_values(array_filter(
                array_map('strval', $decoded),
                static fn(string $perm) => $perm !== 'student_health'
            ));

            $this->update('{{%user}}', [
                'permissions' => $permissions === [] ? null : json_encode($permissions, JSON_UNESCAPED_SLASHES),
            ], ['id' => (int)$row['id']]);
        }
    }
}
