<?php

use yii\db\Migration;

class m260615_100000_add_max_score_to_school_subject_grade extends Migration
{
    public function safeUp()
    {
        $table = $this->db->schema->getTableSchema('{{%school_subject_grade}}', true);
        if ($table === null || isset($table->columns['max_score'])) {
            return;
        }

        $this->addColumn(
            '{{%school_subject_grade}}',
            'max_score',
            $this->decimal(8, 2)->notNull()->defaultValue(100)->after('credits')
        );

        $this->update('{{%school_subject_grade}}', ['max_score' => 100]);
    }

    public function safeDown()
    {
        $table = $this->db->schema->getTableSchema('{{%school_subject_grade}}', true);
        if ($table === null || !isset($table->columns['max_score'])) {
            return;
        }

        $this->dropColumn('{{%school_subject_grade}}', 'max_score');
    }
}
