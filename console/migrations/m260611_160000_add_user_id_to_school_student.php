<?php

use yii\db\Migration;

class m260611_160000_add_user_id_to_school_student extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_student}}', true);
        if ($schema === null || isset($schema->columns['user_id'])) {
            return;
        }

        $this->addColumn('{{%school_student}}', 'user_id', $this->integer()->null()->after('school_grade_id'));
        $this->createIndex('idx_school_student_user', '{{%school_student}}', 'user_id', true);

        if ($this->db->schema->getTableSchema('{{%user}}', true) !== null) {
            $this->addForeignKey(
                'fk_school_student_user',
                '{{%school_student}}',
                'user_id',
                '{{%user}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_student}}', true);
        if ($schema === null || !isset($schema->columns['user_id'])) {
            return;
        }

        $this->dropForeignKey('fk_school_student_user', '{{%school_student}}');
        $this->dropIndex('idx_school_student_user', '{{%school_student}}');
        $this->dropColumn('{{%school_student}}', 'user_id');
    }
}
