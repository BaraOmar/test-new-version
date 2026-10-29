<?php

use yii\db\Migration;

class m260611_120000_extend_school_student_for_student_affairs extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_student}}', true);
        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['gender'])) {
            $this->addColumn('{{%school_student}}', 'gender', $this->string(16)->null()->after('email'));
        }
        if (!isset($schema->columns['nationality'])) {
            $this->addColumn('{{%school_student}}', 'nationality', $this->string(64)->null()->after('gender'));
        }
        if (!isset($schema->columns['birth_date'])) {
            $this->addColumn('{{%school_student}}', 'birth_date', $this->date()->null()->after('nationality'));
        }
        if (!isset($schema->columns['home_address'])) {
            $this->addColumn('{{%school_student}}', 'home_address', $this->string(255)->null()->after('birth_date'));
        }
        if (!isset($schema->columns['phone'])) {
            $this->addColumn('{{%school_student}}', 'phone', $this->string(32)->null()->after('home_address'));
        }
        if (!isset($schema->columns['academic_year'])) {
            $this->addColumn('{{%school_student}}', 'academic_year', $this->string(16)->null()->after('phone'));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_student}}', true);
        if ($schema === null) {
            return;
        }

        foreach (['academic_year', 'phone', 'home_address', 'birth_date', 'nationality', 'gender'] as $column) {
            if (isset($schema->columns[$column])) {
                $this->dropColumn('{{%school_student}}', $column);
            }
        }
    }
}
