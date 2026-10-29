<?php

namespace common\modules\recruitment;

use common\modules\recruitment\models\Applicant;
use common\modules\recruitment\models\Application;
use common\modules\recruitment\models\Job;
use Yii;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
    public $controllerNamespace = 'common\modules\recruitment\controllers';

    public function ensureTables()
    {
        $db = Yii::$app->db;

        if ($db->schema->getTableSchema(Applicant::tableName(), true) === null) {
            $db->createCommand()->createTable(Applicant::tableName(), [
                'user_id' => 'pk',
                'role_id' => 'integer NOT NULL',
                'username' => 'varchar(255) NULL',
                'full_name' => 'varchar(255) NOT NULL',
                'email' => 'varchar(255) NOT NULL',
                'password_hash' => 'varchar(255) NOT NULL',
                'phone' => 'varchar(50) NULL',
                'headline' => 'varchar(255) NULL',
                'bio' => 'text NULL',
                'resume_path' => 'varchar(500) NULL',
                'resume_original_name' => 'varchar(255) NULL',
                'resume_uploaded_at' => 'datetime NULL',
                'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            ])->execute();
            $db->createCommand()->createIndex('idx_rms_users_email', Applicant::tableName(), 'email', true)->execute();
            $db->createCommand()->createIndex('idx_rms_users_role', Applicant::tableName(), 'role_id')->execute();
            $db->createCommand()->createIndex('idx_rms_users_username', Applicant::tableName(), 'username')->execute();
        } else {
            $this->addColumnIfMissing(Applicant::tableName(), 'role_id', 'integer NOT NULL DEFAULT 2');
            $this->addColumnIfMissing(Applicant::tableName(), 'username', 'varchar(255) NULL');
            $this->addColumnIfMissing(Applicant::tableName(), 'full_name', 'varchar(255) NULL');
            $this->addColumnIfMissing(Applicant::tableName(), 'email', 'varchar(255) NULL');
            $this->addColumnIfMissing(Applicant::tableName(), 'password_hash', 'varchar(255) NULL');
            $this->addColumnIfMissing(Applicant::tableName(), 'phone', 'varchar(50) NULL');
            $this->addColumnIfMissing(Applicant::tableName(), 'headline', 'varchar(255) NULL');
            $this->addColumnIfMissing(Applicant::tableName(), 'bio', 'text NULL');
            $this->addColumnIfMissing(Applicant::tableName(), 'resume_path', 'varchar(500) NULL');
            $this->addColumnIfMissing(Applicant::tableName(), 'resume_original_name', 'varchar(255) NULL');
            $this->addColumnIfMissing(Applicant::tableName(), 'resume_uploaded_at', 'datetime NULL');
            $this->addColumnIfMissing(Applicant::tableName(), 'created_at', 'datetime NULL');
        }

        if ($db->schema->getTableSchema(Job::tableName(), true) === null) {
            $db->createCommand()->createTable(Job::tableName(), [
                'job_id' => 'pk',
                'recruiter_id' => 'integer NOT NULL',
                'job_title' => 'varchar(255) NOT NULL',
                'department' => 'varchar(255) NULL',
                'location' => 'varchar(255) NULL',
                'description' => 'text NOT NULL',
                'status' => 'varchar(32) NOT NULL DEFAULT "draft"',
                'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            ])->execute();
            $db->createCommand()->createIndex('idx_rms_jobs_recruiter', Job::tableName(), 'recruiter_id')->execute();
            $db->createCommand()->createIndex('idx_rms_jobs_status', Job::tableName(), 'status')->execute();
        } else {
            $this->addColumnIfMissing(Job::tableName(), 'recruiter_id', 'integer NULL');
            $this->addColumnIfMissing(Job::tableName(), 'job_title', 'varchar(255) NULL');
            $this->addColumnIfMissing(Job::tableName(), 'department', 'varchar(255) NULL');
            $this->addColumnIfMissing(Job::tableName(), 'location', 'varchar(255) NULL');
            $this->addColumnIfMissing(Job::tableName(), 'description', 'text NULL');
            $this->addColumnIfMissing(Job::tableName(), 'status', 'varchar(32) NOT NULL DEFAULT "draft"');
            $this->addColumnIfMissing(Job::tableName(), 'created_at', 'datetime NULL');
        }

        if ($db->schema->getTableSchema(Application::tableName(), true) === null) {
            $db->createCommand()->createTable(Application::tableName(), [
                'application_id' => 'pk',
                'job_id' => 'integer NOT NULL',
                'applicant_id' => 'integer NOT NULL',
                'application_status' => 'varchar(64) NOT NULL DEFAULT "Pending"',
                'interview_at' => 'datetime NULL',
                'interview_type' => 'varchar(64) NULL',
                'feedback_rating' => 'integer NULL',
                'feedback_notes' => 'text NULL',
                'applied_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            ])->execute();
            $db->createCommand()->createIndex('idx_rms_applications_job', Application::tableName(), 'job_id')->execute();
            $db->createCommand()->createIndex('idx_rms_applications_applicant', Application::tableName(), 'applicant_id')->execute();
            $db->createCommand()->createIndex('idx_rms_applications_unique', Application::tableName(), ['job_id', 'applicant_id'], true)->execute();
        } else {
            $this->addColumnIfMissing(Application::tableName(), 'job_id', 'integer NULL');
            $this->addColumnIfMissing(Application::tableName(), 'applicant_id', 'integer NULL');
            $this->addColumnIfMissing(Application::tableName(), 'application_status', 'varchar(64) NOT NULL DEFAULT "Pending"');
            $this->addColumnIfMissing(Application::tableName(), 'interview_at', 'datetime NULL');
            $this->addColumnIfMissing(Application::tableName(), 'interview_type', 'varchar(64) NULL');
            $this->addColumnIfMissing(Application::tableName(), 'feedback_rating', 'integer NULL');
            $this->addColumnIfMissing(Application::tableName(), 'feedback_notes', 'text NULL');
            $this->addColumnIfMissing(Application::tableName(), 'applied_at', 'datetime NULL');
        }
    }

    private function addColumnIfMissing($table, $column, $type)
    {
        $schema = Yii::$app->db->schema->getTableSchema($table, true);
        if ($schema !== null && $schema->getColumn($column) === null) {
            Yii::$app->db->createCommand()->addColumn($table, $column, $type)->execute();
            Yii::$app->db->schema->refreshTableSchema($table);
        }
    }
}
