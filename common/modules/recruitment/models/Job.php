<?php

namespace common\modules\recruitment\models;

use yii\db\ActiveRecord;

/**
 * @property int $job_id
 * @property int $recruiter_id
 * @property string $job_title
 * @property string|null $department
 * @property string|null $location
 * @property string $description
 * @property string $status
 * @property string|null $created_at
 */
class Job extends ActiveRecord
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';

    public static function tableName()
    {
        return '{{%jobs}}';
    }

    public static function primaryKey()
    {
        return ['job_id'];
    }

    public function rules()
    {
        return [
            [['recruiter_id', 'job_title', 'description'], 'required'],
            [['recruiter_id'], 'integer'],
            [['description'], 'string'],
            [['job_title', 'department', 'location'], 'string', 'max' => 255],
            [['status'], 'in', 'range' => array_keys(self::statusOptions())],
            [['status'], 'default', 'value' => self::STATUS_DRAFT],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'job_title' => 'Job Title',
            'recruiter_id' => 'Recruiter',
        ];
    }

    public static function statusOptions()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    public function getRecruiter()
    {
        return $this->hasOne(Applicant::class, ['user_id' => 'recruiter_id']);
    }

    public function getApplications()
    {
        return $this->hasMany(Application::class, ['job_id' => 'job_id']);
    }
}
