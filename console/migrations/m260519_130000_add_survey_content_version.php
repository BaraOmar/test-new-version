<?php

use yii\db\Migration;

class m260519_130000_add_survey_content_version extends Migration
{
    public function safeUp()
    {
        $surveys = '{{%surveys}}';
        if ($this->db->schema->getTableSchema($surveys, true) !== null
            && $this->db->schema->getTableSchema($surveys, true)->getColumn('content_version') === null) {
            $this->addColumn($surveys, 'content_version', $this->integer()->notNull()->defaultValue(1));
        }

        $questions = '{{%survey_questions}}';
        if ($this->db->schema->getTableSchema($questions, true) !== null
            && $this->db->schema->getTableSchema($questions, true)->getColumn('content_version') === null) {
            $this->addColumn($questions, 'content_version', $this->integer()->notNull()->defaultValue(1));
        }

        $responses = '{{%survey_responses}}';
        if ($this->db->schema->getTableSchema($responses, true) !== null
            && $this->db->schema->getTableSchema($responses, true)->getColumn('content_version') === null) {
            $this->addColumn($responses, 'content_version', $this->integer()->null());
            $this->execute(
                'UPDATE {{%survey_responses}} SET content_version = 1 WHERE content_version IS NULL'
            );
        }
    }

    public function safeDown()
    {
        foreach ([
            '{{%surveys}}' => 'content_version',
            '{{%survey_questions}}' => 'content_version',
            '{{%survey_responses}}' => 'content_version',
        ] as $table => $column) {
            if ($this->db->schema->getTableSchema($table, true)?->getColumn($column) !== null) {
                $this->dropColumn($table, $column);
            }
        }
    }
}
