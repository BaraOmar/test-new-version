<?php

namespace frontend\modules\employee\controllers;

use common\modules\evaluation\controllers\EvaluationController as BaseEvaluationController;
use common\modules\evaluation\models\Evaluation;
use common\modules\evaluation\models\EvaluationQuestion;
use common\modules\evaluation\services\EvaluationAccessService;
use Yii;
use yii\helpers\Url;
use yii\web\ForbiddenHttpException;

class EvaluationController extends BaseEvaluationController
{
    /** @var string */
    public $portalLayout = '@frontend/modules/employee/views/evaluation/_layout';

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if ($action->id !== 'respond') {
            throw new ForbiddenHttpException('Access denied.');
        }

        $employee = EvaluationAccessService::findEmployeeByUserId((int)Yii::$app->user->id);
        if ($employee !== null && $employee->isTerminated()) {
            throw new ForbiddenHttpException('Your employment has ended. You can no longer answer evaluations.');
        }

        return true;
    }

    protected function requireHrAdmin(): void
    {
        // Not used on employee portal.
    }

    protected function respondSuccessRoute(int $evaluationId): array
    {
        return ['/employee/employee/survey-display'];
    }

    /**
     * @param EvaluationQuestion[] $questions
     * @return array<string, mixed>
     */
    protected function respondViewParams(
        Evaluation $evaluation,
        array $questions,
        bool $hasSubmitted,
        bool $canRespond,
        string $denyReason
    ): array {
        $params = parent::respondViewParams($evaluation, $questions, $hasSubmitted, $canRespond, $denyReason);
        $params['backUrl'] = Url::to(['/employee/employee/survey-display']);

        return $params;
    }

    public function render($view, $params = [])
    {
        $params['pageTitle'] = $params['pageTitle'] ?? ('Evaluation: ' . ($params['evaluation']->title ?? ''));
        $params['currentPage'] = 'survey_evaluation';
        $employee = EvaluationAccessService::findEmployeeByUserId((int)Yii::$app->user->id);
        $params['userName'] = $employee !== null ? (string)$employee->full_name : 'Employee';
        $params['userRole'] = ($employee !== null && $employee->job_title) ? (string)$employee->job_title : 'Employee';
        $params['legacyRespond'] = ($view === 'respond');
        $params['content'] = $this->renderPartial('@common/modules/evaluation/views/evaluation/' . $view, $params);

        return parent::render($this->portalLayout, $params);
    }
}
