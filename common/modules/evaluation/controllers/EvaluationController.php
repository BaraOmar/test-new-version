<?php

namespace common\modules\evaluation\controllers;

use common\models\User;
use common\modules\employee\models\Department;
use common\modules\employee\models\Employee;
use common\modules\evaluation\models\Evaluation;
use common\modules\evaluation\models\EvaluationAnswer;
use common\modules\evaluation\models\EvaluationEmployeeTarget;
use common\modules\evaluation\models\EvaluationQuestion;
use common\modules\evaluation\models\EvaluationQuestionOption;
use common\modules\evaluation\models\EvaluationResponse;
use common\modules\evaluation\services\EvaluationResultsService;
use common\modules\evaluation\models\EvaluationTarget;
use common\modules\evaluation\services\EvaluationAccessService;
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

class EvaluationController extends Controller
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
        $query = Evaluation::find()->alias('e')->with(['responses', 'departmentTargets.department', 'employeeTargets.employee']);

        if ($search !== '') {
            $query->andWhere(['like', 'e.title', $search]);
        }
        [$dateFrom, $dateTo] = ScheduleWindowFilter::apply($query, 'e', Evaluation::class, $dateFrom, $dateTo);
        if ($anonymous === 'anonymous' && Evaluation::hasColumn('is_anonymous')) {
            $query->andWhere(['is_anonymous' => 1]);
        } elseif ($anonymous === 'named' && Evaluation::hasColumn('is_anonymous')) {
            $query->andWhere(['is_anonymous' => 0]);
        }
        if ($departmentId > 0) {
            $query->andWhere([
                'exists',
                EvaluationTarget::find()
                    ->alias('st')
                    ->where(new Expression('st.evaluation_id = e.id'))
                    ->andWhere(['st.department_id' => $departmentId]),
            ]);
        }

        $query->orderBy(Evaluation::hasColumn('created_at') ? ['e.created_at' => SORT_DESC, 'e.id' => SORT_DESC] : ['e.id' => SORT_DESC]);

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

        $model = new Evaluation();
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
                    throw new \RuntimeException('Evaluation could not be saved.');
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
                Yii::$app->session->setFlash('success', 'Evaluation schedule updated.');
                return $this->redirect($this->hrIndexRoute());
            }

            Yii::$app->session->setFlash('error', 'Could not update evaluation schedule.');
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
            $evaluationId = (int)Yii::$app->request->post('evaluation_id', 0);
            $evaluation = $this->findModel($evaluationId);

            if (EvaluationEmployeeTarget::find()->where(['evaluation_id' => $evaluation->id, 'employee_id' => $employee->id])->exists()) {
                Yii::$app->session->setFlash('error', 'This evaluation was already assigned to this employee.');
            } else {
                $target = new EvaluationEmployeeTarget([
                    'evaluation_id' => $evaluation->id,
                    'employee_id' => $employee->id,
                ]);

                $saved = $target->save();
                if ($saved && $evaluation->hasAttribute('audience_type')) {
                    $evaluation->audience_type = 'employee';
                    $evaluation->save(false, ['audience_type']);
                }
                Yii::$app->session->setFlash($saved ? 'success' : 'error', $saved ? 'Evaluation assigned.' : 'Could not assign evaluation.');
            }

            return $this->refresh();
        }

        $assignedIds = EvaluationEmployeeTarget::find()
            ->select('evaluation_id')
            ->where(['employee_id' => $employee->id])
            ->column();

        return $this->render('send', [
            'employee' => $employee,
            'evaluations' => Evaluation::find()->orderBy(Evaluation::hasColumn('created_at') ? ['created_at' => SORT_DESC, 'id' => SORT_DESC] : ['id' => SORT_DESC])->all(),
            'assignedIds' => array_map('intval', $assignedIds),
        ]);
    }

    public function actionRespond($id)
    {
        $evaluation = $this->findModel((int)$id);
        $employee = $this->findCurrentEmployee();
        $questions = $evaluation->questions;

        [$canRespond, $denyReason] = EvaluationAccessService::canRespond($evaluation, $employee);
        $hasSubmitted = EvaluationAccessService::hasSubmitted($evaluation, (int)$employee->id);

        if (Yii::$app->request->isPost) {
            if ($hasSubmitted || !$canRespond) {
                Yii::$app->session->setFlash('error', $hasSubmitted ? 'You have already submitted this evaluation.' : $denyReason);
                return $this->refresh();
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                $response = new EvaluationResponse([
                    'evaluation_id' => $evaluation->id,
                    'employee_id' => $employee->id,
                ]);
                if ($response->hasAttribute('created_at')) {
                    $response->created_at = date('Y-m-d H:i:s');
                }
                if (!$response->save()) {
                    throw new \RuntimeException('Could not create response.');
                }

                foreach ($questions as $question) {
                    $this->saveAnswerFromPost($question, $response, $evaluation, $employee);
                }

                $evaluation->ensureEmployeeTargetRecord((int)$employee->id);

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Evaluation submitted. Thank you.');
                return $this->redirect($this->respondSuccessRoute((int)$evaluation->id));
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->refresh();
            }
        }

        return $this->render('respond', $this->respondViewParams(
            $evaluation,
            $questions,
            $hasSubmitted,
            $canRespond,
            $denyReason
        ));
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
        return [
            'evaluation' => $evaluation,
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

        $evaluation = Evaluation::find()
            ->with(['questions.options', 'departmentTargets.department', 'employeeTargets.employee'])
            ->where(['id' => (int)$id])
            ->one();
        if ($evaluation === null) {
            throw new NotFoundHttpException('Evaluation not found.');
        }

        $evaluation->repairEmployeeTargetsFromResponses();
        $questions = $evaluation->questions;
        $search = trim((string)Yii::$app->request->get('search', ''));

        $results = (new EvaluationResultsService())->build($evaluation, $questions, $search);

        $answerableQuestions = array_values(array_filter(
            $questions,
            static fn($q) => !in_array($q->question_type, ['section_break', 'section_title', 'image', 'video'], true)
        ));

        return $this->render('results', [
            'evaluation' => $evaluation,
            'questions' => $answerableQuestions,
            'responseCount' => $evaluation->getResponseCount(),
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

        $evaluation = $this->findModel((int)$id);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            EvaluationAnswer::deleteAll(['evaluation_id' => $evaluation->id]);
            EvaluationResponse::deleteAll(['evaluation_id' => $evaluation->id]);

            foreach (EvaluationQuestion::find()->where(['evaluation_id' => $evaluation->id])->all() as $question) {
                EvaluationQuestionOption::deleteAll(['question_id' => $question->id]);
            }
            EvaluationQuestion::deleteAll(['evaluation_id' => $evaluation->id]);
            EvaluationTarget::deleteAll(['evaluation_id' => $evaluation->id]);
            EvaluationEmployeeTarget::deleteAll(['evaluation_id' => $evaluation->id]);

            $evaluation->delete();
            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Evaluation deleted successfully.');
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Could not delete evaluation.');
        }

        return $this->redirect($this->hrIndexRoute());
    }

    protected function saveAnswerFromPost(
        EvaluationQuestion $question,
        EvaluationResponse $response,
        Evaluation $evaluation,
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

        $answer = new EvaluationAnswer([
            'response_id' => $response->id,
            'evaluation_id' => $evaluation->id,
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

    protected function saveTargets(Evaluation $evaluation): void
    {
        if ($evaluation->target_type === 'department') {
            $departmentIds = array_values(array_filter(array_map('intval', (array)$evaluation->departments)));
            foreach ($departmentIds as $departmentId) {
                (new EvaluationTarget([
                    'evaluation_id' => $evaluation->id,
                    'department_id' => $departmentId,
                ]))->save(false);
            }

            if ($departmentIds !== []) {
                $this->syncDepartmentEmployeeTargets($evaluation, $departmentIds);
            }
        }

        if ($evaluation->target_type === 'employee') {
            foreach ((array)$evaluation->employees as $employeeId) {
                (new EvaluationEmployeeTarget([
                    'evaluation_id' => $evaluation->id,
                    'employee_id' => (int)$employeeId,
                ]))->save(false);
            }
        }
    }

    /**
     * @param int[] $departmentIds
     */
    protected function syncDepartmentEmployeeTargets(Evaluation $evaluation, array $departmentIds): void
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

            $exists = EvaluationEmployeeTarget::find()
                ->where(['evaluation_id' => $evaluation->id, 'employee_id' => $employeeId])
                ->exists();
            if ($exists) {
                continue;
            }

            (new EvaluationEmployeeTarget([
                'evaluation_id' => $evaluation->id,
                'employee_id' => $employeeId,
            ]))->save(false);
        }
    }

    protected function flashLaunchResult(Evaluation $evaluation): void
    {
        if ($evaluation->target_type === 'department') {
            $recipientCount = (int)EvaluationEmployeeTarget::find()
                ->where(['evaluation_id' => $evaluation->id])
                ->count();

            if ($recipientCount === 0) {
                Yii::$app->session->setFlash(
                    'warning',
                    'Evaluation saved, but no employees are linked to the selected department(s). '
                    . 'Assign staff to that department and/or set a department manager, then launch again.'
                );

                return;
            }

            Yii::$app->session->setFlash(
                'success',
                'Evaluation launched successfully. ' . $recipientCount . ' employee(s) in the selected department(s) can see it.'
            );

            return;
        }

        Yii::$app->session->setFlash('success', 'Evaluation launched successfully.');
    }

    protected function saveQuestions(Evaluation $evaluation): void
    {
        $position = 0;
        foreach ($evaluation->getQuestionPayload() as $payload) {
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

            $question = new EvaluationQuestion([
                'evaluation_id' => $evaluation->id,
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
                throw new \RuntimeException('Could not save evaluation question (' . $type . '): ' . $detail);
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
    protected function saveQuestionOptions(EvaluationQuestion $question, array $options): void
    {
        $sort = 1;
        foreach ($options as $optionText) {
            $optionText = trim((string)$optionText);
            if ($optionText === '') {
                continue;
            }

            $option = new EvaluationQuestionOption(['question_id' => $question->id]);
            $option->setAttribute(EvaluationQuestionOption::textColumn(), $optionText);
            $sortColumn = EvaluationQuestionOption::sortColumn();
            if ($sortColumn !== 'id' && $option->hasAttribute($sortColumn)) {
                $option->setAttribute($sortColumn, $sort);
            }
            if (!$option->save()) {
                throw new \RuntimeException('Could not save evaluation option.');
            }
            $sort++;
        }
    }

    protected function findModel($id): Evaluation
    {
        $model = Evaluation::find()
            ->with(['questions.options'])
            ->where(['id' => $id])
            ->one();
        if ($model === null) {
            throw new NotFoundHttpException('Evaluation not found.');
        }

        return $model;
    }

    protected function findCurrentEmployee(): Employee
    {
        $userId = (int)Yii::$app->user->id;
        $employee = EvaluationAccessService::findEmployeeByUserId($userId);
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

        if (!$user->hasPermission('evaluations') && !$user->isSuperHrAdmin()) {
            throw new ForbiddenHttpException('You do not have permission to manage evaluations.');
        }
    }

    protected function hrIndexRoute(): array
    {
        return ['/evaluation/evaluation/index'];
    }

    protected function respondSuccessRoute(int $evaluationId): array
    {
        return ['respond', 'id' => $evaluationId];
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
