<?php

use yii\db\Migration;

class m260611_170000_add_student_name_parts extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_student}}', true);
        if ($schema === null) {
            return;
        }

        $columns = [
            'first_name' => $this->string(64)->null()->after('full_name'),
            'father_name' => $this->string(64)->null()->after('first_name'),
            'grandfather_name' => $this->string(64)->null()->after('father_name'),
            'family_name' => $this->string(64)->null()->after('grandfather_name'),
        ];

        foreach ($columns as $name => $type) {
            if (!isset($schema->columns[$name])) {
                $this->addColumn('{{%school_student}}', $name, $type);
            }
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_student}}', true);
        if ($schema === null) {
            return;
        }

        foreach (['family_name', 'grandfather_name', 'father_name', 'first_name'] as $column) {
            if (isset($schema->columns[$column])) {
                $this->dropColumn('{{%school_student}}', $column);
            }
        }
    }
}
