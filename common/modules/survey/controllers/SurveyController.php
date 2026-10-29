<?php

namespace common\modules\survey\controllers;

use common\models\User;
use common\modules\employee\models\Department;
use common\modules\employee\models\Employee;
use common\modules\survey\models\Survey;
use common\modules\survey\models\SurveyAnswer;
use common\modules\survey\models\SurveyEmployeeTarget;
use common\modules\survey\models\SurveyQuestion;
use common\modules\survey\models\SurveyQuestionOption;
use common\modules\survey\models\SurveyResponse;
use common\modules\survey\services\SurveyResultsService;
use common\modules\survey\models\SurveyTarget;
use common\modules\survey\services\SurveyAccessService;
use common\helpers\ScheduleWindowFilter;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\helpers\Url;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class SurveyController extends Controller
{
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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'send' => ['get', 'post'],
                    'respond' => ['get', 'post'],
                    'delete' => ['post'],
                    'update-schedule' => ['get', 'post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $this->requireHrAdmin();

        $request = Yii::$app->request;
        return $this->render('index', $this->buildIndexParams(
            trim((string)$request->get('search', '')),
            trim((string)$request->get('date_from', '')),
            trim((string)$request->get('date_to', '')),
            trim((string)$request->get('anonymous', '')),
            (int)$request->get('department_id', 0)
        ));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildIndexParams(
        string $search,
        string $dateFrom,
        string $dateTo,
        string $anonymous,
        int $departmentId
    ): array {
        $query = Survey::find()->alias('s')->with(['responses', 'departmentTargets.department', 'employeeTargets.employee']);

        if ($search !== '') {
            $query->andWhere(['like', 's.title', $search]);
        }
        [$dateFrom, $dateTo] = ScheduleWindowFilter::apply($query, 's', Survey::class, $dateFrom, $dateTo);
        if ($anonymous === 'anonymous' && Survey::hasColumn('is_anonymous')) {
            $query->andWhere(['is_anonymous' => 1]);
        } elseif ($anonymous === 'named' && Survey::hasColumn('is_anonymous')) {
            $query->andWhere(['is_anonymous' => 0]);
        }
        if ($departmentId > 0) {
            $query->andWhere([
                'exists',
                SurveyTarget::find()
                    ->alias('st')
                    ->where(new Expression('st.survey_id = s.id'))
                    ->andWhere(['st.department_id' => $departmentId]),
            ]);
        }

        $query->orderBy(Survey::hasColumn('created_at') ? ['s.created_at' => SORT_DESC, 's.id' => SORT_DESC] : ['s.id' => SORT_DESC]);

        return [
            'dataProvider' => new ActiveDataProvider([
                'query' => $query,
                'pagination' => false,
            ]),
            'departments' => Department::find()->orderBy(['name' => SORT_ASC])->all(),
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'anonymous' => $anonymous,
            'departmentId' => $departmentId,
            'filterRoute' => $this->hrIndexRoute(),
            'indexUrl' => Url::to($this->hrIndexRoute()),
        ];
    }

    public function actionCreate($employee_id = null)
    {
        $this->requireHrAdmin();

        $model = new Survey();
        if ($employee_id) {
            $model->target_type = 'employee';
            $model->employees = [(int)$employee_id];
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($employee_id && $model->target_type === 'employee') {
                $selected = array_values(array_filter(array_map('intval', (array)$model->employees)));
                if ($selected === []) {
                    $model->employees = [(int)$employee_id];
                }
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save(false)) {
                    throw new \RuntimeException('Survey could not be saved.');
                }

                $this->saveTargets($model);
                $this->saveQuestions($model);

                $transaction->commit();
                $this->flashLaunchResult($model);
                return $this->redirect($this->hrIndexRoute());
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::error($e->getMessage(), __METHOD__);
                $model->addError('title', 'Save failed: ' . $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $model,
            'departments' => Department::find()->orderBy(['name' => SORT_ASC])->all(),
            'employees' => Employee::find()->orderBy(['full_name' => SORT_ASC])->all(),
        ]);
    }

    public function actionUpdateSchedule($id)
    {
        $this->requireHrAdmin();

        $model = $this->findModel((int)$id);
        $model->scenario = 'schedule';
        $model->populateScheduleFromDb();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->syncScheduleToAttributes();
            $model->applyScheduleStatus();
            $attrs = ['start_at', 'end_at', 'duration_days'];
            if ($model->hasAttribute('status')) {
                $attrs[] = 'status';
            }
            if ($model->hasAttribute('updated_at')) {
                $model->updated_at = date('Y-m-d H:i:s');
                $attrs[] = 'updated_at';
            }

            if ($model->save(false, $attrs)) {
                Yii::$app->session->setFlash('success', 'Survey schedule updated.');
                return $this->redirect($this->hrIndexRoute());
            }

            Yii::$app->session->setFlash('error', 'Could not update survey schedule.');
        }

        return $this->render('update-schedule', [
            'model' => $model,
        ]);
    }

    public function actionSend($id)
    {
        $this->requireHrAdmin();

        $employee = Employee::findOne((int)$id);
        if ($employee === null) {
            throw new NotFoundHttpException('Employee not found.');
        }

        if (Yii::$app->request->isPost) {
            $surveyId = (int)Yii::$app->request->post('survey_id', 0);
            $survey = $this->findModel($surveyId);

            if (SurveyEmployeeTarget::find()->where(['survey_id' => $survey->id, 'employee_id' => $employee->id])->exists()) {
                Yii::$app->session->setFlash('error', 'This survey was already assigned to this employee.');
            } else {
                $target = new SurveyEmployeeTarget([
                    'survey_id' => $survey->id,
                    'employee_id' => $employee->id,
                ]);

                $saved = $target->save();
                if ($saved && $survey->hasAttribute('audience_type')) {
                    $survey->audience_type = 'employee';
                    $survey->save(false, ['audience_type']);
                }
                Yii::$app->session->setFlash($saved ? 'success' : 'error', $saved ? 'Survey assigned.' : 'Could not assign survey.');
            }

            return $this->refresh();
        }

        $assignedIds = SurveyEmployeeTarget::find()
            ->select('survey_id')
            ->where(['employee_id' => $employee->id])
            ->column();

        return $this->render('send', [
            'employee' => $employee,
            'surveys' => Survey::find()->orderBy(Survey::hasColumn('created_at') ? ['created_at' => SORT_DESC, 'id' => SORT_DESC] : ['id' => SORT_DESC])->all(),
            'assignedIds' => array_map('intval', $assignedIds),
        ]);
    }

    public function actionRespond($id)
    {
        $survey = $this->findModel((int)$id);
        $employee = $this->findCurrentEmployee();
        $questions = $survey->questions;

        [$canRespond, $denyReason] = SurveyAccessService::canRespond($survey, $employee);
        $hasSubmitted = SurveyAccessService::hasSubmitted($survey, (int)$employee->id);

        if (Yii::$app->request->isPost) {
            if ($hasSubmitted || !$canRespond) {
                Yii::$app->session->setFlash('error', $hasSubmitted ? 'You have already submitted this survey.' : $denyReason);
                return $this->refresh();
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                $response = new SurveyResponse([
                    'survey_id' => $survey->id,
                    'employee_id' => $employee->id,
                ]);
                if ($response->hasAttribute('created_at')) {
                    $response->created_at = date('Y-m-d H:i:s');
                }
                if (!$response->save()) {
                    throw new \RuntimeException('Could not create response.');
                }

                foreach ($questions as $question) {
                    $this->saveAnswerFromPost($question, $response, $survey, $employee);
                }

                $survey->ensureEmployeeTargetRecord((int)$employee->id);

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Survey submitted. Thank you.');
                return $this->redirect($this->respondSuccessRoute((int)$survey->id));
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->refresh();
            }
        }

        return $this->render('respond', $this->respondViewParams(
            $survey,
            $questions,
            $hasSubmitted,
            $canRespond,
            $denyReason
        ));
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
        return [
            'survey' => $survey,
            'questions' => $questions,
            'hasSubmitted' => $hasSubmitted,
            'canRespond' => $canRespond,
            'denyReason' => $denyReason,
            'backUrl' => null,
        ];
    }

    public function actionResults($id)
    {
        $this->requireHrAdmin();

        $survey = Survey::find()
            ->with(['questions.options', 'departmentTargets.department', 'employeeTargets.employee'])
            ->where(['id' => (int)$id])
            ->one();
        if ($survey === null) {
            throw new NotFoundHttpException('Survey not found.');
        }

        $survey->repairEmployeeTargetsFromResponses();
        $questions = $survey->questions;
        $search = trim((string)Yii::$app->request->get('search', ''));

        $results = (new SurveyResultsService())->build($survey, $questions, $search);

        $answerableQuestions = array_values(array_filter(
            $questions,
            static fn($q) => !in_array($q->question_type, ['section_break', 'section_title', 'image', 'video'], true)
        ));

        return $this->render('results', [
            'survey' => $survey,
            'questions' => $answerableQuestions,
            'responseCount' => $survey->getResponseCount(),
            'uniqueRespondents' => count($results['groupedResponses']),
            'pendingRespondents' => $results['pendingRespondents'],
            'search' => $search,
            'groupedResponses' => $results['groupedResponses'],
            'questionStats' => $results['questionStats'],
            'targetDepartments' => $results['targetDepartments'],
            'targetEmployees' => $results['targetEmployees'],
            'targetLabel' => $results['targetLabel'],
        ]);
    }

    public function actionDelete($id)
    {
        $this->requireHrAdmin();

        $survey = $this->findModel((int)$id);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SurveyAnswer::deleteAll(['survey_id' => $survey->id]);
            SurveyResponse::deleteAll(['survey_id' => $survey->id]);

            foreach (SurveyQuestion::find()->where(['survey_id' => $survey->id])->all() as $question) {
                SurveyQuestionOption::deleteAll(['question_id' => $question->id]);
            }
            SurveyQuestion::deleteAll(['survey_id' => $survey->id]);
            SurveyTarget::deleteAll(['survey_id' => $survey->id]);
            SurveyEmployeeTarget::deleteAll(['survey_id' => $survey->id]);

            $survey->delete();
            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Survey deleted successfully.');
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Could not delete survey.');
        }

        return $this->redirect($this->hrIndexRoute());
    }

    protected function saveAnswerFromPost(
        SurveyQuestion $question,
        SurveyResponse $response,
        Survey $survey,
        Employee $employee
    ): void {
        if (in_array($question->question_type, ['section_break', 'section_title', 'image', 'video'], true)) {
            return;
        }

        $field = 'q_' . $question->id;
        $raw = Yii::$app->request->post($field);

        if ($raw === null || $raw === '' || $raw === []) {
            if ((int)$question->is_required) {
                throw new \RuntimeException('Please answer all required questions.');
            }
            return;
        }

        if (is_array($raw)) {
            $value = implode(', ', array_map(static fn($v) => trim((string)$v), $raw));
        } else {
            $value = trim((string)$raw);
        }

        if ($value === '') {
            if ((int)$question->is_required) {
                throw new \RuntimeException('Please answer all required questions.');
            }
            return;
        }

        $answerText = null;
        $answerNumber = null;
        $type = (string)$question->question_type;

        if (in_array($type, ['rating', 'linear_scale'], true)) {
            if (is_numeric($value)) {
                $answerNumber = $value;
            } else {
                $answerText = $value;
            }
        } else {
            $answerText = $value;
        }

        $answer = new SurveyAnswer([
            'response_id' => $response->id,
            'survey_id' => $survey->id,
            'question_id' => $question->id,
            'employee_id' => $employee->id,
            'answer_text' => $answerText,
            'answer_number' => $answerNumber,
        ]);
        if ($answer->hasAttribute('created_at')) {
            $answer->created_at = date('Y-m-d H:i:s');
        }
        if (!$answer->save()) {
            throw new \RuntimeException('Could not save answer.');
        }
    }

    protected function saveTargets(Survey $survey): void
    {
        if ($survey->target_type === 'department') {
            $departmentIds = array_values(array_filter(array_map('intval', (array)$survey->departments)));
            foreach ($departmentIds as $departmentId) {
                (new SurveyTarget([
                    'survey_id' => $survey->id,
                    'department_id' => $departmentId,
                ]))->save(false);
            }

            if ($departmentIds !== []) {
                $this->syncDepartmentEmployeeTargets($survey, $departmentIds);
            }
        }

        if ($survey->target_type === 'employee') {
            foreach ((array)$survey->employees as $employeeId) {
                (new SurveyEmployeeTarget([
                    'survey_id' => $survey->id,
                    'employee_id' => (int)$employeeId,
                ]))->save(false);
            }
        }
    }

    /**
     * @param int[] $departmentIds
     */
    protected function syncDepartmentEmployeeTargets(Survey $survey, array $departmentIds): void
    {
        $employeeIds = [];
        if (Employee::hasColumn('department_id')) {
            $employeeIds = array_merge(
                $employeeIds,
                Employee::find()->select('id')->where(['department_id' => $departmentIds])->column()
            );
        }
        if (Department::hasColumn('manager_id')) {
            $employeeIds = array_merge(
                $employeeIds,
                Department::find()
                    ->select('manager_id')
                    ->where(['id' => $departmentIds])
                    ->andWhere(['not', ['manager_id' => null]])
                    ->column()
            );
        }

        foreach (array_unique(array_map('intval', $employeeIds)) as $employeeId) {
            $employeeId = (int)$employeeId;
            if ($employeeId <= 0) {
                continue;
            }

            $exists = SurveyEmployeeTarget::find()
                ->where(['survey_id' => $survey->id, 'employee_id' => $employeeId])
                ->exists();
            if ($exists) {
                continue;
            }

            (new SurveyEmployeeTarget([
                'survey_id' => $survey->id,
                'employee_id' => $employeeId,
            ]))->save(false);
        }
    }

    protected function flashLaunchResult(Survey $survey): void
    {
        if ($survey->target_type === 'department') {
            $recipientCount = (int)SurveyEmployeeTarget::find()
                ->where(['survey_id' => $survey->id])
                ->count();

            if ($recipientCount === 0) {
                Yii::$app->session->setFlash(
                    'warning',
                    'Survey saved, but no employees are linked to the selected department(s). '
                    . 'Assign staff to that department and/or set a department manager, then launch again.'
                );

                return;
            }

            Yii::$app->session->setFlash(
                'success',
                'Survey launched successfully. ' . $recipientCount . ' employee(s) in the selected department(s) can see it.'
            );

            return;
        }

        Yii::$app->session->setFlash('success', 'Survey launched successfully.');
    }

    protected function saveQuestions(Survey $survey): void
    {
        $position = 0;
        foreach ($survey->getQuestionPayload() as $payload) {
            $type = (string)($payload['type'] ?? 'short_text');
            $text = trim((string)($payload['text'] ?? ''));

            if ($type === 'image' || $type === 'video') {
                $text = trim((string)($payload['mediaUrl'] ?? $text));
                if ($text === '') {
                    $text = $type === 'image' ? 'Image' : 'Video';
                }
            } elseif ($type === 'section_break') {
                $text = trim((string)($payload['sectionTitle'] ?? $text)) ?: 'Section';
            } elseif ($type === 'section_title') {
                $text = $text ?: 'Section';
            }

            $question = new SurveyQuestion([
                'survey_id' => $survey->id,
                'question_text' => $text,
                'question_type' => $type,
                'is_required' => !empty($payload['required']) ? 1 : 0,
            ]);

            if ($question->hasAttribute('scale_min')) {
                $question->scale_min = in_array($type, ['rating', 'linear_scale'], true)
                    ? (int)($payload['scaleMin'] ?? 1) : null;
            }
            if ($question->hasAttribute('scale_max')) {
                $question->scale_max = in_array($type, ['rating', 'linear_scale'], true)
                    ? (int)($payload['scaleMax'] ?? 5) : null;
            }
            if ($question->hasAttribute('position')) {
                $question->position = $position;
            }
            if (!$question->save()) {
                $detail = implode(' ', $question->getFirstErrors()) ?: 'validation failed';
                throw new \RuntimeException('Could not save survey question (' . $type . '): ' . $detail);
            }

            if (in_array($type, ['multiple_choice', 'checkbox', 'dropdown'], true)) {
                $this->saveQuestionOptions($question, (array)($payload['options'] ?? []));
            } elseif ($type === 'linear_scale') {
                $labels = array_filter([
                    trim((string)($payload['scaleLabelMin'] ?? '')),
                    trim((string)($payload['scaleLabelMax'] ?? '')),
                ]);
                if ($labels !== []) {
                    $this->saveQuestionOptions($question, array_values($labels));
                }
            } elseif ($type === 'section_title' && trim((string)($payload['sectionTitle'] ?? '')) !== '') {
                $this->saveQuestionOptions($question, [trim((string)$payload['sectionTitle'])]);
            }

            $position++;
        }
    }

    /**
     * @param string[] $options
     */
    protected function saveQuestionOptions(SurveyQuestion $question, array $options): void
    {
        $sort = 1;
        foreach ($options as $optionText) {
            $optionText = trim((string)$optionText);
            if ($optionText === '') {
                continue;
            }

            $option = new SurveyQuestionOption(['question_id' => $question->id]);
            $option->setAttribute(SurveyQuestionOption::textColumn(), $optionText);
            $sortColumn = SurveyQuestionOption::sortColumn();
            if ($sortColumn !== 'id' && $option->hasAttribute($sortColumn)) {
                $option->setAttribute($sortColumn, $sort);
            }
            if (!$option->save()) {
                throw new \RuntimeException('Could not save survey option.');
            }
            $sort++;
        }
    }

    protected function findModel($id): Survey
    {
        $model = Survey::find()
            ->with(['questions.options'])
            ->where(['id' => $id])
            ->one();
        if ($model === null) {
            throw new NotFoundHttpException('Survey not found.');
        }

        return $model;
    }

    protected function findCurrentEmployee(): Employee
    {
        $userId = (int)Yii::$app->user->id;
        $employee = SurveyAccessService::findEmployeeByUserId($userId);
        if ($employee === null) {
            $employee = Employee::findOne($userId);
        }
        if ($employee === null) {
            throw new ForbiddenHttpException('Employee account is required.');
        }

        return $employee;
    }

    protected function requireHrAdmin(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !$user->isHr()) {
            throw new ForbiddenHttpException('HR access is required.');
        }

        if (!$user->hasPermission('surveys') && !$user->isSuperHrAdmin()) {
            throw new ForbiddenHttpException('You do not have permission to manage surveys.');
        }
    }

    protected function hrIndexRoute(): array
    {
        return ['/survey/survey/index'];
    }

    protected function respondSuccessRoute(int $surveyId): array
    {
        return ['respond', 'id' => $surveyId];
    }

    public static function statusInfo($status): array
    {
        $status = strtolower((string)$status ?: 'active');
        $badge = 'text-xs font-bold px-2 py-1 rounded';
        $map = [
            'active' => ['ACTIVE', $badge . ' text-green-600 bg-green-100'],
            'scheduled' => ['SCHEDULED', $badge . ' text-amber-600 bg-amber-100'],
            'closed' => ['CLOSED', $badge . ' text-rose-600 bg-rose-100'],
            'draft' => ['DRAFT', $badge . ' text-slate-600 bg-slate-100'],
        ];

        return $map[$status] ?? [strtoupper($status), $badge . ' text-slate-600 bg-slate-100'];
    }
}
