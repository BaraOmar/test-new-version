<?php

namespace frontend\modules\employee\controllers;

use common\modules\survey\controllers\SurveyController as BaseSurveyController;
use common\modules\survey\models\Survey;
use common\modules\survey\models\SurveyQuestion;
use common\modules\survey\services\SurveyAccessService;
use Yii;
use yii\helpers\Url;
use yii\web\ForbiddenHttpException;

class SurveyController extends BaseSurveyController
{
    /** @var string */
    public $portalLayout = '@frontend/modules/employee/views/survey/_layout';

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if ($action->id !== 'respond') {
            throw new ForbiddenHttpException('Access denied.');
        }

        $employee = SurveyAccessService::findEmployeeByUserId((int)Yii::$app->user->id);
        if ($employee !== null && $employee->isTerminated()) {
            throw new ForbiddenHttpException('Your employment has ended. You can no longer answer surveys.');
        }

        return true;
    }

    protected function requireHrAdmin(): void
    {
        // Not used on employee portal.
    }

    protected function respondSuccessRoute(int $surveyId): array
    {
        return ['/employee/employee/survey-display'];
    }

    /**
     * @param SurveyQuestion[] $questions
     * @return array<string, mixed>
     */
    protected function respondViewParams(
        Survey $survey,
        array $questions,
        bool $hasSubmitted,
        bool $canRespond,
        string $denyReason
    ): array {
        $params = parent::respondViewParams($survey, $questions, $hasSubmitted, $canRespond, $denyReason);
        $params['backUrl'] = Url::to(['/employee/employee/survey-display']);

        return $params;
    }

    public function render($view, $params = [])
    {
        $params['pageTitle'] = $params['pageTitle'] ?? ('Survey: ' . ($params['survey']->title ?? ''));
        $params['currentPage'] = 'survey_evaluation';
        $employee = SurveyAccessService::findEmployeeByUserId((int)Yii::$app->user->id);
        $params['userName'] = $employee !== null ? (string)$employee->full_name : 'Employee';
        $params['userRole'] = ($employee !== null && $employee->job_title) ? (string)$employee->job_title : 'Employee';
        $params['legacyRespond'] = ($view === 'respond');
        $params['content'] = $this->renderPartial('@common/modules/survey/views/survey/' . $view, $params);

        return parent::render($this->portalLayout, $params);
    }
}
