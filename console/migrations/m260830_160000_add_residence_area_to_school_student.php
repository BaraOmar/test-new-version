<?php

use yii\db\Migration;

class m260830_160000_add_residence_area_to_school_student extends Migration
{
    public function safeUp()
    {
        $studentTable = '{{%school_student}}';
        $areaTable = '{{%transport_area}}';

        $schema = $this->db->schema->getTableSchema($studentTable, true);
        if ($schema === null || $schema->getColumn('residence_area_id') !== null) {
            return;
        }

        $this->addColumn(
            $studentTable,
            'residence_area_id',
            $this->integer()->null()->after('home_address')
        );
        $this->createIndex('idx_school_student_residence_area_id', $studentTable, 'residence_area_id');

        if ($this->db->schema->getTableSchema($areaTable, true) !== null) {
            $this->addForeignKey(
                'fk_school_student_residence_area',
                $studentTable,
                'residence_area_id',
                $areaTable,
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        $studentTable = '{{%school_student}}';
        $schema = $this->db->schema->getTableSchema($studentTable, true);
        if ($schema === null || $schema->getColumn('residence_area_id') === null) {
            return;
        }

        foreach ($schema->foreignKeys as $name => $fk) {
            if ($name === 'fk_school_student_residence_area') {
                $this->dropForeignKey('fk_school_student_residence_area', $studentTable);
                break;
            }
        }

        $this->dropIndex('idx_school_student_residence_area_id', $studentTable);
        $this->dropColumn($studentTable, 'residence_area_id');
    }
}
