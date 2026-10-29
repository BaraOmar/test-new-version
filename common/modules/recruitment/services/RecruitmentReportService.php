<?php

namespace common\modules\recruitment\services;

use common\modules\recruitment\models\Application as JobApplication;
use common\modules\recruitment\models\Job;
use Yii;
use yii\db\Expression;
use yii\db\Query;

class RecruitmentReportService
{
    /**
     * @return array{
     *   jobs: Job[],
     *   metrics: array{total:int,pending:int,hired:int},
     *   statusDistribution: array<string,int>,
     *   trend: array<int, array{label:string,count:int}>,
     *   applicants: array<int, array<string, mixed>>
     * }
     */
    public function build(int $recruiterId, ?int $jobId = null, int $days = 30): array
    {
        $jobQuery = Job::find()->where(['recruiter_id' => $recruiterId])->orderBy(['created_at' => SORT_DESC]);
        $jobs = $jobQuery->all();

        $jobIds = $jobId > 0
            ? [(int)$jobId]
            : array_map(static fn(Job $j) => (int)$j->job_id, $jobs);

        if ($jobIds === []) {
            return [
                'jobs' => $jobs,
                'metrics' => ['total' => 0, 'pending' => 0, 'hired' => 0],
                'statusDistribution' => [],
                'trend' => [],
                'applicants' => [],
            ];
        }

        $since = date('Y-m-d H:i:s', strtotime('-' . max(1, $days) . ' days'));

        $baseApp = (new Query())
            ->from(['a' => JobApplication::tableName()])
            ->innerJoin(['j' => Job::tableName()], 'j.job_id = a.job_id')
            ->where(['j.recruiter_id' => $recruiterId])
            ->andWhere(['a.job_id' => $jobIds]);

        $total = (int)(clone $baseApp)->count('*', Yii::$app->db);

        $pending = (int)(clone $baseApp)
            ->andWhere(['a.application_status' => [
                JobApplication::STATUS_PENDING,
                JobApplication::STATUS_SCREENED,
                JobApplication::STATUS_INTERVIEWING,
            ]])
            ->count('*', Yii::$app->db);

        $hired = (int)(clone $baseApp)
            ->andWhere(['a.application_status' => JobApplication::STATUS_HIRED])
            ->count('*', Yii::$app->db);

        $statusDistribution = [];
        $rows = (clone $baseApp)
            ->select(['a.application_status', 'cnt' => new Expression('COUNT(*)')])
            ->groupBy(['a.application_status'])
            ->all(Yii::$app->db);
        foreach ($rows as $row) {
            $statusDistribution[(string)$row['application_status']] = (int)$row['cnt'];
        }

        $trend = [];
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = date('Y-m-d', strtotime('-' . ($i * 7 + 7) . ' days'));
            $weekEnd = date('Y-m-d', strtotime('-' . ($i * 7) . ' days'));
            $count = (int)(clone $baseApp)
                ->andWhere(['>=', 'a.applied_at', $weekStart . ' 00:00:00'])
                ->andWhere(['<=', 'a.applied_at', $weekEnd . ' 23:59:59'])
                ->count('*', Yii::$app->db);
            $trend[] = ['label' => 'Week ' . (4 - $i), 'count' => $count];
        }

        $applicantRows = (clone $baseApp)
            ->select([
                'a.application_id',
                'a.application_status',
                'a.applied_at',
                'j.job_title',
                'j.department',
                'u.full_name',
                'u.email',
            ])
            ->innerJoin(['u' => '{{%users}}'], 'u.user_id = a.applicant_id')
            ->andWhere(['>=', 'a.applied_at', $since])
            ->orderBy(['a.applied_at' => SORT_DESC])
            ->limit(50)
            ->all(Yii::$app->db);

        return [
            'jobs' => $jobs,
            'metrics' => [
                'total' => $total,
                'pending' => $pending,
                'hired' => $hired,
            ],
            'statusDistribution' => $statusDistribution,
            'trend' => $trend,
            'applicants' => $applicantRows,
        ];
    }
}
