<?php

use yii\db\Migration;

class m260609_100000_create_school_department_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_department}}', true) !== null) {
            return;
        }

        $this->createTable('{{%school_department}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'head_id' => $this->integer()->null(),
            'deputy_head_id' => $this->integer()->null(),
            'status' => $this->string(16)->notNull()->defaultValue('active'),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex('idx_school_department_name', '{{%school_department}}', 'name', true);
        $this->createIndex('idx_school_department_status', '{{%school_department}}', 'status');

        if ($this->db->schema->getTableSchema('{{%employees}}', true) !== null) {
            $this->addForeignKey(
                'fk_school_department_head',
                '{{%school_department}}',
                'head_id',
                '{{%employees}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
            $this->addForeignKey(
                'fk_school_department_deputy_head',
                '{{%school_department}}',
                'deputy_head_id',
                '{{%employees}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_department}}', true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema('{{%employees}}', true) !== null) {
            $this->dropForeignKey('fk_school_department_deputy_head', '{{%school_department}}');
            $this->dropForeignKey('fk_school_department_head', '{{%school_department}}');
        }

        $this->dropTable('{{%school_department}}');
    }
}
