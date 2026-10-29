<?php

use yii\db\Migration;

class m260902_151000_extend_student_section_mark_odoo_fields extends Migration
{
    public function safeUp()
    {
        $table = $this->db->schema->getTableSchema('{{%school_student_section_mark}}', true);
        if ($table === null) {
            return;
        }

        if (!isset($table->columns['mark_components_complete'])) {
            $this->addColumn(
                '{{%school_student_section_mark}}',
                'mark_components_complete',
                $this->boolean()->notNull()->defaultValue(false)->after('mark_final')
            );
        }

        if (!isset($table->columns['maximum_score'])) {
            $this->addColumn(
                '{{%school_student_section_mark}}',
                'maximum_score',
                $this->decimal(8, 2)->notNull()->defaultValue(100)->after('total')
            );
        }

        $this->execute("
            UPDATE {{%school_student_section_mark}}
            SET mark_components_complete = 1
            WHERE mark_first IS NOT NULL
              AND mark_second IS NOT NULL
              AND mark_third IS NOT NULL
              AND mark_final IS NOT NULL
        ");
    }

    public function safeDown()
    {
        $table = $this->db->schema->getTableSchema('{{%school_student_section_mark}}', true);
        if ($table === null) {
            return;
        }

        if (isset($table->columns['maximum_score'])) {
            $this->dropColumn('{{%school_student_section_mark}}', 'maximum_score');
        }
        if (isset($table->columns['mark_components_complete'])) {
            $this->dropColumn('{{%school_student_section_mark}}', 'mark_components_complete');
        }
    }
}
