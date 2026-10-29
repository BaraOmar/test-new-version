<?php

namespace common\modules\dashboard\controllers;

use common\modules\employee\models\Employee;
use Yii;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

class DashboardController extends Controller
{
    private const RESPONSE_RATE_TARGET = 80;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $this->requireHrAdmin();

        return $this->render('index', [
            'snapshot' => $this->buildAnalyticsSnapshot(),
            'navigationCards' => $this->buildNavigationCards(),
        ]);
    }

    protected function buildAnalyticsSnapshot()
    {
        $totalEmployees = $this->countRows('employees');
        $totalSurveys = $this->countRows('surveys');
        $surveyResponses = $this->countRows('survey_responses');
        $totalEvaluations = $this->countRows('evaluations');
        $evaluationResponses = $this->countRows('evaluation_responses');

        return [
            'totalEmployees' => $totalEmployees,
            'surveyRate' => $this->responseRate($surveyResponses, $totalEmployees, $totalSurveys),
            'evaluationRate' => $this->responseRate($evaluationResponses, $totalEmployees, $totalEvaluations),
            'surveyResponses' => $surveyResponses,
            'evaluationResponses' => $evaluationResponses,
            'totalSurveys' => $totalSurveys,
            'totalEvaluations' => $totalEvaluations,
            'responseRateTarget' => self::RESPONSE_RATE_TARGET,
        ];
    }

    protected function buildNavigationCards()
    {
        return [
            [
                'title' => 'Employee Management',
                'description' => 'Add team members, update profiles, and organize departmental structures.',
                'route' => ['/employee/employee/index'],
                'button' => 'View Employees',
                'icon' => 'people',
                'tone' => 'primary',
            ],
            [
                'title' => 'Survey Management',
                'description' => 'Create and distribute engagement surveys to gather employee feedback.',
                'route' => ['/survey/survey/index'],
                'button' => 'View Surveys',
                'icon' => 'checklist',
                'tone' => 'success',
            ],
            [
                'title' => 'Evaluation Management',
                'description' => 'Create and distribute performance evaluations across teams.',
                'route' => ['/evaluation/evaluation/index'],
                'button' => 'View Evaluations',
                'icon' => 'bar-chart',
                'tone' => 'success',
            ],
            [
                'title' => 'Learning Management',
                'description' => 'Prepare employee learning and development analytics integration.',
                'route' => ['/site/index'],
                'button' => 'View LMS',
                'icon' => 'mortarboard',
                'tone' => 'accent',
            ],
        ];
    }

    protected function responseRate($responses, $employees, $items)
    {
        if ($employees <= 0 || $items <= 0) {
            return 0;
        }

        return (int)round(($responses / ($employees * $items)) * 100);
    }

    protected function countRows($table)
    {
        if (!$this->tableExists($table)) {
            return 0;
        }

        return (int)(new Query())->from($table)->count('*', Yii::$app->db);
    }

    protected function tableExists($table)
    {
        return Yii::$app->db->schema->getTableSchema($table, true) !== null;
    }

    protected function requireHrAdmin()
    {
        $employee = Employee::findOne((int)Yii::$app->user->id);
        if ($employee === null || !$employee->hasAttribute('is_hr_admin') || !(int)$employee->is_hr_admin) {
            throw new ForbiddenHttpException('HR administrator access is required.');
        }
    }
}
