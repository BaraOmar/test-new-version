<?php

use yii\db\Migration;

class m260902_150000_add_term_maxima_to_subject_grade extends Migration
{
    public function safeUp()
    {
        $table = $this->db->schema->getTableSchema('{{%school_subject_grade}}', true);
        if ($table === null) {
            return;
        }

        $columns = [
            'max_first' => $this->decimal(8, 2)->notNull()->defaultValue(25),
            'max_second' => $this->decimal(8, 2)->notNull()->defaultValue(25),
            'max_third' => $this->decimal(8, 2)->notNull()->defaultValue(25),
            'max_final' => $this->decimal(8, 2)->notNull()->defaultValue(25),
        ];

        foreach ($columns as $name => $type) {
            if (!isset($table->columns[$name])) {
                $this->addColumn('{{%school_subject_grade}}', $name, $type);
            }
        }

        if (isset($table->columns['max_score'])) {
            $this->execute('
                UPDATE {{%school_subject_grade}}
                SET max_first = ROUND(max_score / 4, 2),
                    max_second = ROUND(max_score / 4, 2),
                    max_third = ROUND(max_score / 4, 2),
                    max_final = max_score - (ROUND(max_score / 4, 2) * 3)
                WHERE max_first = 25 AND max_second = 25 AND max_third = 25 AND max_final = 25
            ');
        }
    }

    public function safeDown()
    {
        $table = $this->db->schema->getTableSchema('{{%school_subject_grade}}', true);
        if ($table === null) {
            return;
        }

        foreach (['max_first', 'max_second', 'max_third', 'max_final'] as $column) {
            if (isset($table->columns[$column])) {
                $this->dropColumn('{{%school_subject_grade}}', $column);
            }
        }
    }
}
