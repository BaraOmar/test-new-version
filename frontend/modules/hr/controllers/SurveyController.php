<?php

namespace frontend\modules\hr\controllers;

use common\modules\survey\controllers\SurveyController as BaseSurveyController;
use common\traits\HrAccessTrait;
use Yii;

class SurveyController extends BaseSurveyController
{
    use HrAccessTrait;

    /** @var string */
    public $portalLayout = '@frontend/modules/hr/views/survey/_layout';

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireHr();
        $this->requirePermission('surveys');

        return true;
    }

    protected function requireHrAdmin(): void
    {
        // Handled in beforeAction.
    }

    protected function hrIndexRoute(): array
    {
        return ['/hr/survey/index'];
    }

    public function render($view, $params = [])
    {
        $params['userName'] = $this->userDisplayName();
        $params['pageTitle'] = $params['pageTitle'] ?? match ($view) {
            'create' => 'Survey Builder',
            'send' => 'Send Survey',
            'results' => 'Survey Results',
            'update-schedule' => 'Update Schedule',
            default => 'Surveys',
        };
        $params['currentPage'] = $params['currentPage'] ?? 'surveys';
        $params['fullBleed'] = $params['fullBleed'] ?? in_array($view, ['create', 'results'], true);
        $params['hideTopbar'] = $params['hideTopbar'] ?? in_array($view, ['create', 'results'], true);
        $params['content'] = $this->renderPartial('@common/modules/survey/views/survey/' . $view, $params);

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
