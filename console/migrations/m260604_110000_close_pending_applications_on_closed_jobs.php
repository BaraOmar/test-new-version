<?php

use yii\db\Migration;

class m260604_110000_close_pending_applications_on_closed_jobs extends Migration
{
    public function safeUp()
    {
        $applications = '{{%applications}}';
        $jobs = '{{%jobs}}';
        if (
            $this->db->schema->getTableSchema($applications, true) === null
            || $this->db->schema->getTableSchema($jobs, true) === null
        ) {
            return;
        }

        $statuses = ['Pending', 'New', 'Screened', 'Interviewing', 'Offered'];
        $this->update(
            $applications,
            ['application_status' => 'Closed'],
            [
                'and',
                ['in', 'application_status', $statuses],
                [
                    'in',
                    'job_id',
                    (new \yii\db\Query())
                        ->select('job_id')
                        ->from($jobs)
                        ->where(['status' => 'closed']),
                ],
            ]
        );
    }

    public function safeDown()
    {
        // Cannot reliably restore prior application statuses.
    }
}
