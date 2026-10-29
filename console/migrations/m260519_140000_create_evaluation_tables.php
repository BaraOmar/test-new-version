<?php

use yii\db\Migration;

class m260519_140000_create_evaluation_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%evaluations}}', true) !== null) {
            return;
        }

        $this->createTable('{{%evaluations}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'status' => $this->string(32)->notNull()->defaultValue('active'),
            'audience_type' => $this->string(32)->null(),
            'is_anonymous' => $this->smallInteger()->notNull()->defaultValue(0),
            'start_at' => $this->dateTime()->null(),
            'end_at' => $this->dateTime()->null(),
            'duration_days' => $this->integer()->null(),
            'content_version' => $this->integer()->notNull()->defaultValue(1),
            'created_at' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
        ]);

        $this->createTable('{{%evaluation_questions}}', [
            'id' => $this->primaryKey(),
            'evaluation_id' => $this->integer()->notNull(),
            'question_text' => $this->text()->notNull(),
            'question_type' => $this->string(32)->notNull()->defaultValue('rating'),
            'is_required' => $this->smallInteger()->notNull()->defaultValue(0),
            'scale_min' => $this->integer()->null(),
            'scale_max' => $this->integer()->null(),
            'position' => $this->integer()->null(),
            'content_version' => $this->integer()->notNull()->defaultValue(1),
            'created_at' => $this->dateTime()->null(),
        ]);
        $this->createIndex('idx_evaluation_questions_evaluation_id', '{{%evaluation_questions}}', 'evaluation_id');
        $this->addForeignKey(
            'fk_evaluation_questions_evaluation',
            '{{%evaluation_questions}}',
            'evaluation_id',
            '{{%evaluations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%evaluation_question_options}}', [
            'id' => $this->primaryKey(),
            'question_id' => $this->integer()->notNull(),
            'option_text' => $this->string(255)->notNull(),
            'sort_order' => $this->integer()->null(),
        ]);
        $this->createIndex('idx_evaluation_question_options_question_id', '{{%evaluation_question_options}}', 'question_id');
        $this->addForeignKey(
            'fk_evaluation_question_options_question',
            '{{%evaluation_question_options}}',
            'question_id',
            '{{%evaluation_questions}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%evaluation_targets}}', [
            'evaluation_id' => $this->integer()->notNull(),
            'department_id' => $this->integer()->notNull(),
        ]);
        $this->addPrimaryKey('pk_evaluation_targets', '{{%evaluation_targets}}', ['evaluation_id', 'department_id']);
        $this->addForeignKey(
            'fk_evaluation_targets_evaluation',
            '{{%evaluation_targets}}',
            'evaluation_id',
            '{{%evaluations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%evaluation_employee_targets}}', [
            'evaluation_id' => $this->integer()->notNull(),
            'employee_id' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addPrimaryKey('pk_evaluation_employee_targets', '{{%evaluation_employee_targets}}', ['evaluation_id', 'employee_id']);
        $this->addForeignKey(
            'fk_evaluation_employee_targets_evaluation',
            '{{%evaluation_employee_targets}}',
            'evaluation_id',
            '{{%evaluations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%evaluation_responses}}', [
            'id' => $this->primaryKey(),
            'evaluation_id' => $this->integer()->notNull(),
            'employee_id' => $this->integer()->notNull(),
            'content_version' => $this->integer()->null(),
            'created_at' => $this->dateTime()->null(),
        ]);
        $this->createIndex('idx_evaluation_responses_evaluation_employee', '{{%evaluation_responses}}', ['evaluation_id', 'employee_id'], true);
        $this->addForeignKey(
            'fk_evaluation_responses_evaluation',
            '{{%evaluation_responses}}',
            'evaluation_id',
            '{{%evaluations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%evaluation_answers}}', [
            'id' => $this->primaryKey(),
            'response_id' => $this->integer()->notNull(),
            'evaluation_id' => $this->integer()->notNull(),
            'question_id' => $this->integer()->notNull(),
            'employee_id' => $this->integer()->notNull(),
            'answer_text' => $this->text()->null(),
            'answer_number' => $this->decimal(10, 2)->null(),
            'created_at' => $this->dateTime()->null(),
        ]);
        $this->createIndex('idx_evaluation_answers_evaluation_question', '{{%evaluation_answers}}', ['evaluation_id', 'question_id']);
        $this->addForeignKey(
            'fk_evaluation_answers_response',
            '{{%evaluation_answers}}',
            'response_id',
            '{{%evaluation_responses}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%evaluations}}', true) === null) {
            return;
        }

        $this->dropTable('{{%evaluation_answers}}');
        $this->dropTable('{{%evaluation_responses}}');
        $this->dropTable('{{%evaluation_employee_targets}}');
        $this->dropTable('{{%evaluation_targets}}');
        $this->dropTable('{{%evaluation_question_options}}');
        $this->dropTable('{{%evaluation_questions}}');
        $this->dropTable('{{%evaluations}}');
    }
}
