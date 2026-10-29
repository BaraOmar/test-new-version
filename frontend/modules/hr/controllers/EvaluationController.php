<?php

namespace frontend\modules\hr\controllers;

use common\modules\evaluation\controllers\EvaluationController as BaseEvaluationController;
use common\traits\HrAccessTrait;
use Yii;

class EvaluationController extends BaseEvaluationController
{
    use HrAccessTrait;

    /** @var string */
    public $portalLayout = '@frontend/modules/hr/views/evaluation/_layout';

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireHr();
        $this->requirePermission('evaluations');

        return true;
    }

    protected function requireHrAdmin(): void
    {
        // Handled in beforeAction.
    }

    protected function hrIndexRoute(): array
    {
        return ['/hr/evaluation/index'];
    }

    public function render($view, $params = [])
    {
        $params['userName'] = $this->userDisplayName();
        $params['pageTitle'] = $params['pageTitle'] ?? match ($view) {
            'create' => 'Evaluation Builder',
            'send' => 'Send Evaluation',
            'results' => 'Evaluation Results',
            'update-schedule' => 'Update Schedule',
            default => 'Evaluations',
        };
        $params['currentPage'] = $params['currentPage'] ?? 'evaluations';
        $params['fullBleed'] = $params['fullBleed'] ?? in_array($view, ['create', 'results'], true);
        $params['hideTopbar'] = $params['hideTopbar'] ?? in_array($view, ['create', 'results'], true);
        $params['content'] = $this->renderPartial('@common/modules/evaluation/views/evaluation/' . $view, $params);

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
