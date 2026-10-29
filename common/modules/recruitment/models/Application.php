<?php

namespace common\modules\recruitment\models;

use yii\db\ActiveRecord;

/**
 * @property int $application_id
 * @property int $job_id
 * @property int $applicant_id
 * @property string $application_status
 * @property string|null $interview_at
 * @property string|null $interview_type
 * @property int|null $feedback_rating
 * @property string|null $feedback_notes
 * @property string|null $applied_at
 */
class Application extends ActiveRecord
{
    /** Initial status after apply, before HR takes action. */
    public const STATUS_PENDING = 'Pending';

    /** @deprecated Use STATUS_PENDING */
    public const STATUS_NEW = 'Pending';
    public const STATUS_SCREENED = 'Screened';
    public const STATUS_INTERVIEWING = 'Interviewing';
    public const STATUS_OFFERED = 'Offered';
    public const STATUS_REJECTED = 'Rejected';
    public const STATUS_HIRED = 'Hired';
    /** Set when the job posting is closed while the application is still active. */
    public const STATUS_CLOSED = 'Closed';

    public static function tableName()
    {
        return '{{%applications}}';
    }

    public static function primaryKey()
    {
        return ['application_id'];
    }

    public function rules()
    {
        return [
            [['job_id', 'applicant_id'], 'required'],
            [['job_id', 'applicant_id', 'feedback_rating'], 'integer'],
            [['feedback_notes'], 'string'],
            [['application_status'], 'in', 'range' => self::allowedStatuses()],
            [['application_status'], 'default', 'value' => self::STATUS_PENDING],
            [['interview_type'], 'in', 'range' => ['Google Meet', 'Zoom', 'In-Person Office'], 'skipOnEmpty' => true],
            [['interview_at', 'applied_at'], 'safe'],
            [['feedback_rating'], 'integer', 'min' => 1, 'max' => 5, 'skipOnEmpty' => true],
            [['job_id', 'applicant_id'], 'unique', 'targetAttribute' => ['job_id', 'applicant_id']],
        ];
    }

    public static function statusOptions()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SCREENED => 'Screened',
            self::STATUS_INTERVIEWING => 'Interviewing',
            self::STATUS_OFFERED => 'Offered',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_HIRED => 'Hired',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    /**
     * Application statuses moved to Closed when the job posting is closed.
     *
     * @return string[]
     */
    public static function statusesClosedWhenJobCloses(): array
    {
        return [
            self::STATUS_PENDING,
            'New',
            self::STATUS_SCREENED,
            self::STATUS_INTERVIEWING,
            self::STATUS_OFFERED,
        ];
    }

    public static function closeActiveApplicationsForJob(int $jobId): int
    {
        return (int)static::updateAll(
            ['application_status' => self::STATUS_CLOSED],
            [
                'and',
                ['job_id' => $jobId],
                ['in', 'application_status', self::statusesClosedWhenJobCloses()],
            ]
        );
    }

    /**
     * @return string[]
     */
    public static function allowedStatuses(): array
    {
        return array_values(array_unique(array_merge(array_keys(self::statusOptions()), ['New'])));
    }

    public static function displayStatus(?string $status): string
    {
        $status = trim((string)$status);

        return $status === 'New' ? self::STATUS_PENDING : $status;
    }

    /**
     * Status shown to applicants (accounts for closed job postings).
     */
    public static function displayStatusForApplicant(self $application): string
    {
        $status = self::displayStatus($application->application_status);
        $job = $application->job;
        if (
            $job !== null
            && (string)$job->status === Job::STATUS_CLOSED
            && in_array($status, self::statusesClosedWhenJobCloses(), true)
        ) {
            return self::STATUS_CLOSED;
        }

        return $status;
    }

    /**
     * Whether the applicant may withdraw (unsend) this application.
     */
    public function canApplicantWithdraw(): bool
    {
        $status = self::displayStatus($this->application_status);

        if (in_array($status, [
            self::STATUS_SCREENED,
            self::STATUS_INTERVIEWING,
            self::STATUS_OFFERED,
            self::STATUS_HIRED,
            self::STATUS_REJECTED,
        ], true)) {
            return false;
        }

        return in_array($status, [self::STATUS_PENDING, self::STATUS_CLOSED], true);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && empty($this->applied_at)) {
            $this->applied_at = date('Y-m-d H:i:s');
        }

        return true;
    }

    public function getJob()
    {
        return $this->hasOne(Job::class, ['job_id' => 'job_id']);
    }

    public function getApplicant()
    {
        return $this->hasOne(Applicant::class, ['user_id' => 'applicant_id']);
    }
}
