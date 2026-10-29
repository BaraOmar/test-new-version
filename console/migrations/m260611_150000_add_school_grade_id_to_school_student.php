<?php

use yii\db\Migration;

class m260611_150000_add_school_grade_id_to_school_student extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_student}}', true);
        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['school_grade_id'])) {
            $this->addColumn('{{%school_student}}', 'school_grade_id', $this->integer()->null()->after('school_section_id'));
            $this->createIndex('idx_school_student_grade', '{{%school_student}}', 'school_grade_id');
            if ($this->db->schema->getTableSchema('{{%school_grade}}', true) !== null) {
                $this->addForeignKey(
                    'fk_school_student_grade',
                    '{{%school_student}}',
                    'school_grade_id',
                    '{{%school_grade}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            }
        }

        if (isset($schema->columns['school_section_id'])) {
            $this->execute('
                UPDATE {{%school_student}} student
                INNER JOIN {{%school_section}} section ON section.id = student.school_section_id
                SET student.school_grade_id = section.school_grade_id
                WHERE student.school_grade_id IS NULL
            ');
        }

        if (isset($schema->columns['school_section_id']) && !$schema->columns['school_section_id']->allowNull) {
            $this->dropForeignKey('fk_school_student_section', '{{%school_student}}');
            $this->alterColumn('{{%school_student}}', 'school_section_id', $this->integer()->null());
            $this->addForeignKey(
                'fk_school_student_section',
                '{{%school_student}}',
                'school_section_id',
                '{{%school_section}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_student}}', true);
        if ($schema === null) {
            return;
        }

        if (isset($schema->columns['school_grade_id'])) {
            $this->dropForeignKey('fk_school_student_grade', '{{%school_student}}');
            $this->dropIndex('idx_school_student_grade', '{{%school_student}}');
            $this->dropColumn('{{%school_student}}', 'school_grade_id');
        }
    }
}
