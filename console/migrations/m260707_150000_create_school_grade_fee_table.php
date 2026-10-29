<?php

use yii\db\Migration;

class m260707_150000_create_school_grade_fee_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_grade}}', true) === null) {
            return;
        }

        if ($this->db->schema->getTableSchema('{{%school_grade_fee}}', true) !== null) {
            return;
        }

        $this->createTable('{{%school_grade_fee}}', [
            'id' => $this->primaryKey(),
            'school_grade_id' => $this->integer()->notNull(),
            'price' => $this->decimal(15, 2)->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex('idx_school_grade_fee_grade', '{{%school_grade_fee}}', 'school_grade_id', true);
        $this->addForeignKey(
            'fk_school_grade_fee_grade',
            '{{%school_grade_fee}}',
            'school_grade_id',
            '{{%school_grade}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_grade_fee}}', true) === null) {
            return;
        }

        $this->dropForeignKey('fk_school_grade_fee_grade', '{{%school_grade_fee}}');
        $this->dropTable('{{%school_grade_fee}}');
    }
}
