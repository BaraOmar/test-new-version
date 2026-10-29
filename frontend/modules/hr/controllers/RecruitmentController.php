<?php

namespace frontend\modules\hr\controllers;

use common\modules\recruitment\controllers\ManageController;
use common\modules\recruitment\services\RecruitmentHrService;
use common\traits\HrAccessTrait;
use Yii;

class RecruitmentController extends ManageController
{
    use HrAccessTrait;

    /** @var string */
    public $portalLayout = '@frontend/modules/hr/views/recruitment/_layout';

    /** @var string[] */
    protected array $indexRoute = ['/hr/recruitment/index'];

    /** @var string[] */
    protected array $jobsRoute = ['/hr/recruitment/jobs'];

    /** @var string[] */
    protected array $applicationsRoute = ['/hr/recruitment/applications'];

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireHr();
        $this->requirePermission('recruitment');

        $user = Yii::$app->user->identity;
        if ($user === null) {
            return false;
        }

        RecruitmentHrService::bindHrRecruiterSession($user);

        return true;
    }

    public function render($view, $params = [])
    {
        $params['userName'] = $this->userDisplayName();
        $params['pageTitle'] = $params['pageTitle'] ?? match ($view) {
            'jobs' => 'Job Management',
            'job-form' => 'Create a New Job',
            'applications' => 'Applications & Interviews',
            'application-status' => 'Application Status',
            'reports' => 'Report Generating',
            default => 'Recruit Management',
        };
        $params['fullBleed'] = $params['fullBleed'] ?? $view === 'applications';
        $params['currentPage'] = match ($view) {
            'jobs', 'job-form' => 'recruitment_jobs',
            'applications' => 'recruitment_applications',
            'application-status' => 'recruitment_application_status',
            'reports' => 'recruitment_reports',
            default => 'recruitment',
        };
        $params['content'] = $this->renderPartial('@common/modules/recruitment/views/manage/' . $view, $params);

        return parent::render($this->portalLayout, $params);
    }

    private function userDisplayName(): string
    {
        $user = Yii::$app->user->identity;
        $name = '';
        if ($user !== null) {
            $name = trim((string)($user->username ?? $user->email ?? ''));
        }

        return $name !== '' ? $name : 'Admin User';
    }
}
