<?php

namespace frontend\modules\employee\controllers;

use common\components\LoginPortal;
use common\helpers\EmployeeEngagementFilter;
use common\models\User;
use common\modules\employee\models\EmployeeChangeRequest;
use common\modules\employee\models\Employee;
use common\modules\employee\models\SchoolDepartment;
use common\modules\survey\models\Survey;
use common\modules\evaluation\services\EvaluationAccessService;
use common\modules\survey\services\SurveyAccessService;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Expression;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

class EmployeeController extends Controller
{
    /** Actions allowed when the linked employee record is terminated. */
    private const TERMINATED_PORTAL_ACTIONS = ['index', 'dashboard', 'profile'];

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (!$this->canAccessEmployeeModule()) {
            return false;
        }

        if (!in_array($action->id, self::TERMINATED_PORTAL_ACTIONS, true)) {
            $employee = $this->findLinkedEmployeeForCurrentUser();
            if ($employee !== null && $employee->isTerminated()) {
                Yii::$app->session->setFlash(
                    'warning',
                    'Your employment has ended. You can view your profile only; surveys, training, and other assignments are no longer available.'
                );
                Yii::$app->response->redirect(['dashboard']);

                return false;
            }
        }

        return true;
    }

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
                'actions' => [],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->redirect(['dashboard']);
    }

    public function actionDashboard()
    {
        $employee = $this->findCurrentEmployeeOrRenderFallback();
        if ($employee === null) {
            $this->view->params['bodyClass'] = 'bg-[#f8f4f8] text-slate-900 min-h-screen';
            return $this->render('no-employee', [
                'pageTitle' => 'Dashboard',
                'navLinksHtml' => $this->navLinksHtml('dashboard'),
            ]);
        }

        $this->view->params['bodyClass'] = 'bg-[#f8f4f8] text-slate-900 min-h-screen flex flex-col';

        $portalLocked = $employee->isTerminated();
        $departmentId = (int)$employee->department_id;

        $pendingSurveys = $portalLocked ? 0 : $this->countPendingAssignments(
            'surveys',
            'survey_employee_targets',
            'survey_targets',
            'survey_responses',
            'survey_id',
            $employee,
            $departmentId
        );
        $pendingEvaluations = $portalLocked ? 0 : $this->countPendingAssignments(
            'evaluations',
            'evaluation_employee_targets',
            'evaluation_targets',
            'evaluation_responses',
            'evaluation_id',
            $employee,
            $departmentId
        );

        $pendingTasks = $pendingSurveys + $pendingEvaluations;

        $helloText = $employee->full_name ? ('Hello, ' . $employee->full_name) : 'Hello';
        $surveyRingClass = $pendingTasks > 0 ? 'ring-2 ring-amber-400/30' : '';
        $actionNeededClass = $pendingTasks > 0 ? '' : 'hidden';

        return $this->render('dashboard', [
            'pageTitle' => 'Dashboard',
            'navLinksHtml' => $this->navLinksHtml('dashboard'),
            'portalLocked' => $portalLocked,
            'employee' => $employee,
            'helloText' => $helloText,
            'departmentName' => $employee->department->name ?? '—',
            'jobTitle' => $employee->job_title ?: '—',
            'statusText' => $employee->displayStatus ?: '—',
            'statusClasses' => $this->statusBadgeClass($employee->displayStatus),
            'positionLevelText' => $employee->getDisplayPositionLevel(),
            'employmentMetaText' => $this->employmentMetaText($employee),
            'pendingSurveys' => $pendingSurveys,
            'pendingEvaluations' => $pendingEvaluations,
            'pendingTasks' => $pendingTasks,
            'pendingTasksLabel' => $pendingTasks === 1 ? 'pending task' : 'pending tasks',
            'surveysCtaText' => $pendingTasks > 0 ? 'Start Evaluations' : 'View Surveys & Evaluations',
            'surveyRingClass' => $surveyRingClass,
            'actionNeededClass' => $actionNeededClass,
        ]);
    }

    public function actionProfile()
    {
        $employee = $this->findCurrentEmployeeOrRenderFallback();
        if ($employee === null) {
            $this->view->params['bodyClass'] = 'bg-[#f8f4f8] text-slate-900 min-h-screen';
            return $this->render('no-employee', [
                'pageTitle' => 'My Profile',
                'navLinksHtml' => $this->navLinksHtml('my_profile'),
            ]);
        }

        $this->view->params['bodyClass'] = 'bg-[#f9fafb] dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen';

        $statusClasses = $this->simpleStatusClass($employee->displayStatus);
        $locationKey = strtolower(trim((string)($employee->hasAttribute('location') ? $employee->location : '')));

        $locationUi = [
            'remote' => $this->locationOptionUi($locationKey, 'remote'),
            'hybrid' => $this->locationOptionUi($locationKey, 'hybrid'),
            'onsite' => $this->locationOptionUi($locationKey, 'onsite'),
        ];

        $managedDepartmentNames = $employee->getManagedDepartmentNames();
        $usesSchoolReporting = $employee->isTeacher() && SchoolDepartment::isEnabled();

        if ($usesSchoolReporting) {
            $departmentName = $employee->getReportingDepartmentName();
            $managerName = $employee->getSchoolDepartmentReportingManagerName();
            $managedDepartmentNames = $employee->getManagedSchoolDepartmentNames();
            $isDepartmentHead = $employee->isSchoolDepartmentHead();
        } else {
            $departmentName = $employee->department->name ?? '—';
            $managerName = $employee->manager->full_name ?? 'No Manager Assigned';
            $isDepartmentHead = $managedDepartmentNames !== [];
        }

        return $this->render('profile', [
            'pageTitle' => 'My Profile',
            'navLinksHtml' => $this->navLinksHtml('my_profile'),
            'portalLocked' => $employee->isTerminated(),
            'employee' => $employee,
            'departmentName' => $departmentName,
            'managerName' => $managerName,
            'isDepartmentHead' => $isDepartmentHead,
            'managedDepartmentNames' => $managedDepartmentNames,
            'managedDepartmentsText' => $managedDepartmentNames !== []
                ? implode(', ', $managedDepartmentNames)
                : '—',
            'usesSchoolReporting' => $usesSchoolReporting,
            'statusClasses' => $statusClasses,
            'joiningDateText' => $employee->hasAttribute('joining_date') && $employee->joining_date ? $employee->joining_date : '—',
            'locationUi' => $locationUi,
        ]);
    }

    public function actionRequest()
    {
        $employee = $this->findCurrentEmployeeOrRenderFallback();
        if ($employee !== null && $employee->isTerminated()) {
            throw new ForbiddenHttpException('Change requests are not available for terminated employees.');
        }

        if ($employee === null) {
            $this->view->params['bodyClass'] = 'bg-[#f8f4f8] text-slate-900 min-h-screen';
            return $this->render('no-employee', [
                'pageTitle' => 'Change Request',
                'navLinksHtml' => $this->navLinksHtml('my_profile'),
            ]);
        }

        $this->view->params['bodyClass'] = 'bg-[#f8f4f8] font-sans antialiased min-h-screen flex flex-col';

        $model = new EmployeeChangeRequest([
            'employee_id' => (int)$employee->id,
        ]);

        if ($model->load(Yii::$app->request->post())) {
            if ($model->hasAttribute('title') && trim((string)$model->title) === '') {
                $model->addError('title', 'Request title cannot be empty.');
            }
            if ($model->new_value === null || trim((string)$model->new_value) === '') {
                $model->addError('new_value', 'New content cannot be empty.');
            }

            if (!$model->hasErrors() && $model->save()) {
                Yii::$app->session->setFlash('success', 'Request submitted successfully.');
                return $this->refresh();
            }
        }

        $successMessage = (string)Yii::$app->session->getFlash('success', '');
        $errorMessage = $model->hasErrors() ? implode("\n", $model->getFirstErrors()) : '';
        $successVisibleClass = $successMessage !== '' ? '' : 'hidden';
        $errorVisibleClass = $errorMessage !== '' ? '' : 'hidden';

        $recentRequests = EmployeeChangeRequest::find()
            ->where(['employee_id' => (int)$employee->id])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(12)
            ->all();

        return $this->render('request', [
            'pageTitle' => 'Change Request',
            'navLinksHtml' => $this->navLinksHtml('my_profile'),
            'employee' => $employee,
            'model' => $model,
            'recentRequests' => $recentRequests,
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
            'successVisibleClass' => $successVisibleClass,
            'errorVisibleClass' => $errorVisibleClass,
            'urgencyOptions' => [
                EmployeeChangeRequest::URGENCY_NORMAL => EmployeeChangeRequest::URGENCY_NORMAL,
                EmployeeChangeRequest::URGENCY_URGENT => EmployeeChangeRequest::URGENCY_URGENT,
            ],
        ]);
    }

    public function actionSurveyDisplay()
    {
        $employee = $this->findCurrentEmployeeOrRenderFallback();
        if ($employee === null) {
            $this->view->params['bodyClass'] = 'bg-[#f8f4f8] text-slate-900 min-h-screen';
            return $this->render('no-employee', [
                'pageTitle' => 'Surveys & Evaluations',
                'navLinksHtml' => $this->navLinksHtml('survey_evaluation'),
            ]);
        }

        $this->view->params['bodyClass'] = 'bg-[#f8f4f8] text-slate-900 font-display';

        $search = trim((string)Yii::$app->request->get('search', ''));
        $typeFilter = trim((string)Yii::$app->request->get('type', 'all'));
        if (!in_array($typeFilter, ['all', 'survey', 'evaluation'], true)) {
            $typeFilter = 'all';
        }

        $surveyItems = [];
        if ($typeFilter !== 'evaluation' && SurveyAccessService::tableExists('{{%surveys}}')) {
            foreach (SurveyAccessService::findAssignedSurveys($employee, $search) as $survey) {
                $submitted = SurveyAccessService::hasSubmitted($survey, (int)$employee->id);
                [$canRespond] = SurveyAccessService::canRespond($survey, $employee);
                $time = SurveyAccessService::timeLeftLabel($survey);
                $surveyItems[] = [
                    'type' => 'survey',
                    'id' => (int)$survey->id,
                    'title' => (string)$survey->title,
                    'description' => $survey->getDescriptionPlain(),
                    'status' => (string)($survey->status ?? 'active'),
                    'statusClass' => SurveyAccessService::statusBadgeClass((string)($survey->status ?? 'active')),
                    'timeText' => $time['text'],
                    'timeClass' => $time['color'],
                    'submitted' => $submitted,
                    'canRespond' => $canRespond && !$submitted,
                    'url' => Url::to(['/employee/survey/respond', 'id' => (int)$survey->id]),
                ];
            }
        }

        $evaluationItems = [];
        if ($typeFilter !== 'survey' && EvaluationAccessService::tableExists('{{%evaluations}}')) {
            foreach (EvaluationAccessService::findAssignedEvaluations($employee, $search) as $evaluation) {
                $submitted = EvaluationAccessService::hasSubmitted($evaluation, (int)$employee->id);
                [$canRespond] = EvaluationAccessService::canRespond($evaluation, $employee);
                $time = EvaluationAccessService::timeLeftLabel($evaluation);
                $evaluationItems[] = [
                    'type' => 'evaluation',
                    'id' => (int)$evaluation->id,
                    'title' => (string)$evaluation->title,
                    'description' => $evaluation->getDescriptionPlain(),
                    'status' => (string)($evaluation->status ?? 'active'),
                    'statusClass' => EvaluationAccessService::statusBadgeClass((string)($evaluation->status ?? 'active')),
                    'timeText' => $time['text'],
                    'timeClass' => $time['color'],
                    'submitted' => $submitted,
                    'canRespond' => $canRespond && !$submitted,
                    'url' => Url::to(['/employee/evaluation/respond', 'id' => (int)$evaluation->id]),
                ];
            }
        }

        $surveyCount = SurveyAccessService::tableExists('{{%surveys}}')
            ? count(SurveyAccessService::findAssignedSurveys($employee))
            : 0;
        $evaluationCount = EvaluationAccessService::tableExists('{{%evaluations}}')
            ? count(EvaluationAccessService::findAssignedEvaluations($employee))
            : 0;
        $items = array_merge($surveyItems, $evaluationItems);
        $totalItems = count($items);

        return $this->render('survey-display', [
            'pageTitle' => 'Surveys & Evaluations',
            'employee' => $employee,
            'surveyCount' => $surveyCount,
            'evaluationCount' => $evaluationCount,
            'totalItems' => $totalItems,
            'search' => $search,
            'typeFilter' => $typeFilter,
            'items' => $items,
            'emptyVisibleClass' => $totalItems === 0 ? '' : 'hidden',
        ]);
    }

    public function actionSurveyForm($id = null)
    {
        $surveyId = (int)$id;
        if ($surveyId <= 0) {
            return $this->redirect(['/employee/employee/survey-display']);
        }

        return $this->redirect(['/employee/survey/respond', 'id' => $surveyId]);
    }

    public function actionEvaluationResponse($id = null)
    {
        $evaluationId = (int)$id;
        if ($evaluationId <= 0) {
            return $this->redirect(['/employee/employee/survey-display']);
        }

        return $this->redirect(['/employee/evaluation/respond', 'id' => $evaluationId]);
    }

    public function actionEvaluationResults($id = null)
    {
        return $this->redirect(['/employee/employee/survey-display']);
    }

    public function actionLms()
    {
        $employee = $this->findCurrentEmployeeOrRenderFallback();
        if ($employee === null) {
            $this->view->params['bodyClass'] = 'bg-[#f8f4f8] text-slate-900 min-h-screen';
            return $this->render('no-employee', [
                'pageTitle' => 'LMS',
                'navLinksHtml' => $this->navLinksHtml('lms'),
            ]);
        }

        if (!$employee->isTeacher()) {
            throw new ForbiddenHttpException('LMS access is limited to employees categorized as teachers.');
        }

        if (!$employee->canAccessTeacherLms()) {
            throw new ForbiddenHttpException('LMS access is not available while your employee status is Onboarding, Leave, or Terminated.');
        }

        return $this->redirect(['/lms/portal/teacher']);
    }

    private function hrUserHasLinkedEmployee(User $user): bool
    {
        if (!Employee::hasColumn('user_id')) {
            return false;
        }

        return Employee::find()->where(['user_id' => (int)$user->id])->exists();
    }

    private function canAccessEmployeeModule(): bool
    {
        if (Yii::$app->user->isGuest) {
            return true;
        }

        $user = Yii::$app->user->identity;
        if (!$user instanceof User) {
            throw new ForbiddenHttpException('Access denied.');
        }

        if ($user->isEmployee()) {
            return true;
        }

        if ($user->isHr()) {
            if (LoginPortal::useEmployeePortal() || $this->hrUserHasLinkedEmployee($user)) {
                return true;
            }

            Yii::$app->response->redirect($user->getHrHomeRoute());

            return false;
        }

        throw new ForbiddenHttpException('Access denied.');
    }

    private function findCurrentEmployeeOrRenderFallback(): ?Employee
    {
        if (Yii::$app->user->isGuest) {
            throw new ForbiddenHttpException('Login is required.');
        }

        $user = Yii::$app->user->identity;
        if (!$user instanceof User) {
            throw new ForbiddenHttpException('Login is required.');
        }

        if (!Employee::hasColumn('user_id')) {
            throw new InvalidConfigException('employees.user_id column is required.');
        }

        $employee = Employee::find()
            ->with(['department', 'manager', 'schoolDepartment', 'managedSchoolDepartments', 'deputySchoolDepartmentRecord', 'deputySchoolDepartmentRecord.head'])
            ->where(['user_id' => (int)$user->id])
            ->one();

        return $employee;
    }

    private function findLinkedEmployeeForCurrentUser(): ?Employee
    {
        if (Yii::$app->user->isGuest || !Employee::hasColumn('user_id')) {
            return null;
        }

        $user = Yii::$app->user->identity;
        if (!$user instanceof User) {
            return null;
        }

        return Employee::find()->where(['user_id' => (int)$user->id])->one();
    }

    private function navLinksHtml(string $currentPage): string
    {
        $items = [
            'dashboard' => ['route' => ['/employee/employee/dashboard'], 'label' => 'Dashboard'],
            'lms' => ['route' => ['/employee/employee/lms'], 'label' => 'LMS'],
            'my_profile' => ['route' => ['/employee/employee/profile'], 'label' => 'My Profile'],
            'survey_evaluation' => ['route' => ['/employee/employee/survey-display'], 'label' => 'Survey and Evaluations'],
        ];

        $employee = $this->findLinkedEmployeeForCurrentUser();
        if ($employee === null || !$employee->canAccessTeacherLms()) {
            unset($items['lms']);
        }

        $html = '';
        foreach ($items as $key => $item) {
            $active = $currentPage === $key;
            $class = 'text-sm font-semibold transition-colors py-4 -mb-4 border-b-2 ' . ($active
                ? 'text-[#eb652c] border-[#eb652c]'
                : 'text-white/90 border-transparent hover:text-white hover:border-white/50'
            );

            $html .= Html::a(Html::encode($item['label']), Url::to($item['route']), ['class' => $class]);
        }

        return $html;
    }

    private function employmentMetaText(Employee $employee): string
    {
        $employmentType = $employee->hasAttribute('employment_type') ? trim((string)$employee->employment_type) : '';
        $location = $employee->hasAttribute('location') ? trim((string)$employee->location) : '';

        if ($employmentType === '' && $location === '') {
            return '—';
        }
        if ($employmentType !== '' && $location !== '') {
            return $employmentType . ' • ' . $location;
        }

        return $employmentType !== '' ? $employmentType : $location;
    }

    private function locationOptionUi(string $current, string $option): array
    {
        $selected = $current === $option;

        return [
            'dotClass' => $selected ? 'border-4 border-primary ring-2 ring-primary/10' : 'border-slate-300',
            'textClass' => $selected ? 'font-bold text-slate-900 dark:text-white' : 'text-slate-600',
        ];
    }

    private function simpleStatusClass(string $status): string
    {
        $statusKey = strtolower(trim($status));

        return match ($statusKey) {
            'active' => 'bg-green-100 text-green-700',
            'onboarding' => 'bg-amber-100 text-amber-700',
            'inactive' => 'bg-red-100 text-red-700',
            'terminated' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    private function statusBadgeClass(string $status): string
    {
        $statusKey = strtolower(trim($status));

        return match ($statusKey) {
            'active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'onboarding' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'inactive' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            'terminated' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
        };
    }

    private function tableExists(string $table): bool
    {
        return Yii::$app->db->schema->getTableSchema($table, true) !== null;
    }

    private function countRows(string $table, array $where = []): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }

        $query = (new Query())->from($table);
        if ($where) {
            $query->where($where);
        }

        return (int)$query->count('*', Yii::$app->db);
    }

    private function countPendingAssignments(
        string $mainTable,
        string $employeeTargetTable,
        string $departmentTargetTable,
        string $responseTable,
        string $foreignKey,
        Employee $employee,
        int $departmentId
    ): int {
        if (
            !$this->tableExists($mainTable)
            || !$this->tableExists($employeeTargetTable)
            || !$this->tableExists($departmentTargetTable)
            || !$this->tableExists($responseTable)
        ) {
            return 0;
        }

        $employeeId = (int)$employee->id;

        return (int)$this->assignmentQuery($mainTable, $employeeTargetTable, $departmentTargetTable, $foreignKey, $employee, $departmentId)
            ->andWhere([
                'not exists',
                (new Query())
                    ->select(new Expression('1'))
                    ->from($responseTable)
                    ->where(["$responseTable.$foreignKey" => new Expression("$mainTable.id")])
                    ->andWhere(["$responseTable.employee_id" => $employeeId]),
            ])
            ->count('*', Yii::$app->db);
    }

    private function countAssigned(
        string $mainTable,
        string $employeeTargetTable,
        string $departmentTargetTable,
        string $foreignKey,
        Employee $employee,
        int $departmentId
    ): int {
        if (!$this->tableExists($mainTable) || !$this->tableExists($employeeTargetTable) || !$this->tableExists($departmentTargetTable)) {
            return 0;
        }

        return (int)$this->assignmentQuery($mainTable, $employeeTargetTable, $departmentTargetTable, $foreignKey, $employee, $departmentId)
            ->count('*', Yii::$app->db);
    }

    private function assignmentQuery(
        string $mainTable,
        string $employeeTargetTable,
        string $departmentTargetTable,
        string $foreignKey,
        Employee $employee,
        int $departmentId
    ): Query {
        $employeeId = (int)$employee->id;
        $employeeTargetExists = (new Query())
            ->select(new Expression('1'))
            ->from($employeeTargetTable)
            ->where(["$employeeTargetTable.$foreignKey" => new Expression("$mainTable.id")]);

        $departmentTargetExists = (new Query())
            ->select(new Expression('1'))
            ->from($departmentTargetTable)
            ->where(["$departmentTargetTable.$foreignKey" => new Expression("$mainTable.id")]);

        $query = (new Query())
            ->from($mainTable)
            ->where([
                'or',
                [
                    'and',
                    ['exists', $employeeTargetExists],
                    ['exists', (clone $employeeTargetExists)->andWhere(["$employeeTargetTable.employee_id" => $employeeId])],
                ],
                [
                    'and',
                    ['not exists', $employeeTargetExists],
                    [
                        'or',
                        ['not exists', $departmentTargetExists],
                        ['exists', (clone $departmentTargetExists)->andWhere(["$departmentTargetTable.department_id" => $departmentId])],
                    ],
                ],
            ]);

        if (!$employee->canReceiveEmployeePortalData()) {
            $query->andWhere('0=1');
        } else {
            EmployeeEngagementFilter::applySentAfterActiveToQuery(
                $query,
                $employee,
                $mainTable,
                $employeeTargetTable,
                $foreignKey
            );
        }

        return $query;
    }
}

