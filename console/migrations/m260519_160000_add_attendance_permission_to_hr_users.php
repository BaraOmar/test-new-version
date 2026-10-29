<?php

use common\models\User;
use yii\db\Migration;

class m260519_160000_add_attendance_permission_to_hr_users extends Migration
{
    public function safeUp()
    {
        $userSchema = $this->db->schema->getTableSchema('{{%user}}', true);
        if ($userSchema === null
            || $userSchema->getColumn('permissions') === null
            || $userSchema->getColumn('role') === null) {
            return;
        }

        foreach (User::find()->where(['role' => ['hr', 'admin']])->all() as $user) {
            if ($user instanceof User) {
                $user->syncMissingHrModules();
            }
        }
    }

    public function safeDown()
    {
        echo "m260519_160000_add_attendance_permission_to_hr_users cannot be reverted.\n";

        return false;
    }
}
