<?php

use yii\db\Migration;

class m260521_100000_create_survey_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%surveys}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'status' => $this->string(32)->notNull()->defaultValue('active'),
            'audience_type' => $this->string(32)->null(),
            'is_anonymous' => $this->smallInteger()->notNull()->defaultValue(0),
            'start_at' => $this->dateTime()->null(),
            'end_at' => $this->dateTime()->null(),
            'duration_days' => $this->integer()->null(),
            'created_at' => $this->dateTime()->null(),
        ]);

        $this->createTable('{{%survey_questions}}', [
            'id' => $this->primaryKey(),
            'survey_id' => $this->integer()->notNull(),
            'question_text' => $this->text()->notNull(),
            'question_type' => $this->string(32)->notNull()->defaultValue('rating'),
            'is_required' => $this->smallInteger()->notNull()->defaultValue(0),
            'scale_min' => $this->integer()->null(),
            'scale_max' => $this->integer()->null(),
            'position' => $this->integer()->null(),
            'created_at' => $this->dateTime()->null(),
        ]);
        $this->createIndex('idx_survey_questions_survey_id', '{{%survey_questions}}', 'survey_id');
        $this->addForeignKey(
            'fk_survey_questions_survey',
            '{{%survey_questions}}',
            'survey_id',
            '{{%surveys}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%survey_question_options}}', [
            'id' => $this->primaryKey(),
            'question_id' => $this->integer()->notNull(),
            'option_text' => $this->string(255)->notNull(),
            'sort_order' => $this->integer()->null(),
        ]);
        $this->createIndex('idx_survey_question_options_question_id', '{{%survey_question_options}}', 'question_id');
        $this->addForeignKey(
            'fk_survey_question_options_question',
            '{{%survey_question_options}}',
            'question_id',
            '{{%survey_questions}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%survey_targets}}', [
            'survey_id' => $this->integer()->notNull(),
            'department_id' => $this->integer()->notNull(),
        ]);
        $this->addPrimaryKey('pk_survey_targets', '{{%survey_targets}}', ['survey_id', 'department_id']);
        $this->addForeignKey(
            'fk_survey_targets_survey',
            '{{%survey_targets}}',
            'survey_id',
            '{{%surveys}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%survey_employee_targets}}', [
            'survey_id' => $this->integer()->notNull(),
            'employee_id' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addPrimaryKey('pk_survey_employee_targets', '{{%survey_employee_targets}}', ['survey_id', 'employee_id']);
        $this->addForeignKey(
            'fk_survey_employee_targets_survey',
            '{{%survey_employee_targets}}',
            'survey_id',
            '{{%surveys}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%survey_responses}}', [
            'id' => $this->primaryKey(),
            'survey_id' => $this->integer()->notNull(),
            'employee_id' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->null(),
        ]);
        $this->createIndex('idx_survey_responses_survey_employee', '{{%survey_responses}}', ['survey_id', 'employee_id'], true);
        $this->addForeignKey(
            'fk_survey_responses_survey',
            '{{%survey_responses}}',
            'survey_id',
            '{{%surveys}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%survey_answers}}', [
            'id' => $this->primaryKey(),
            'response_id' => $this->integer()->notNull(),
            'survey_id' => $this->integer()->notNull(),
            'question_id' => $this->integer()->notNull(),
            'employee_id' => $this->integer()->notNull(),
            'answer_text' => $this->text()->null(),
            'answer_number' => $this->decimal(10, 2)->null(),
            'created_at' => $this->dateTime()->null(),
        ]);
        $this->createIndex('idx_survey_answers_survey_question', '{{%survey_answers}}', ['survey_id', 'question_id']);
        $this->addForeignKey(
            'fk_survey_answers_response',
            '{{%survey_answers}}',
            'response_id',
            '{{%survey_responses}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%survey_answers}}');
        $this->dropTable('{{%survey_responses}}');
        $this->dropTable('{{%survey_employee_targets}}');
        $this->dropTable('{{%survey_targets}}');
        $this->dropTable('{{%survey_question_options}}');
        $this->dropTable('{{%survey_questions}}');
        $this->dropTable('{{%surveys}}');
    }
}
