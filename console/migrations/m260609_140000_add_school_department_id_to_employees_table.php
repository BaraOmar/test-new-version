<?php

use yii\db\Migration;

class m260609_140000_add_school_department_id_to_employees_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null || isset($schema->columns['school_department_id'])) {
            return;
        }

        $this->addColumn('{{%employees}}', 'school_department_id', $this->integer()->null()->after('department_id'));
        $this->createIndex('idx_employees_school_department', '{{%employees}}', 'school_department_id');

        if ($this->db->schema->getTableSchema('{{%school_department}}', true) !== null) {
            $this->addForeignKey(
                'fk_employees_school_department',
                '{{%employees}}',
                'school_department_id',
                '{{%school_department}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%employees}}', true);
        if ($schema === null || !isset($schema->columns['school_department_id'])) {
            return;
        }

        if ($this->db->schema->getTableSchema('{{%school_department}}', true) !== null) {
            $this->dropForeignKey('fk_employees_school_department', '{{%employees}}');
        }

        $this->dropIndex('idx_employees_school_department', '{{%employees}}');
        $this->dropColumn('{{%employees}}', 'school_department_id');
    }
}
