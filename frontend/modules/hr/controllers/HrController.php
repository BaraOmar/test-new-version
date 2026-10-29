<?php

namespace frontend\modules\hr\controllers;

use common\traits\HrAccessTrait;
use common\modules\employee\models\Employee;
use common\modules\employee\models\EmployeeChangeRequest;
use common\modules\employee\services\EmployeePortalCredentialService;
use common\modules\evaluation\models\Evaluation;
use common\modules\survey\models\Survey;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use frontend\modules\hr\models\GrantEmployeePermissionsForm;
use frontend\modules\hr\models\ManageHrUserForm;
use frontend\modules\hr\models\OnboardingEmployeeForm;
use frontend\modules\hr\models\UpdateHrUserForm;
use common\modules\employee\models\Department;
use common\modules\employee\models\EmployeeSchoolSubject;
use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolSubject;
use common\models\User;
use yii\filters\VerbFilter;
use yii\data\Pagination;
use yii\web\Response;

class HrController extends Controller
{
    use HrAccessTrait;

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
                    'employee-action' => ['POST'],
                    'manage-hr-action' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $this->requireHr();
        $user = Yii::$app->user->identity;
        if ($user instanceof User) {
            return $this->redirect($user->getHrHomeRoute());
        }

        return $this->redirect(['dashboard']);
    }

    public function actionDashboard()
    {
        $this->requireHr();
        $this->requirePermission('dashboard');
        $this->view->params['bodyClass'] = 'bg-[#f8f4f8] text-slate-900 font-display';

        $totalEmployees = (int)Employee::find()->count();
        $totalSurveys = $this->safeCountTable('surveys');
        $totalSurveyResponses = $this->safeCountTable('survey_responses');

        $totalEvaluations = $this->safeCountTable('evaluations');
        $totalEvaluationResponses = $this->safeCountTable('evaluation_responses');

        $surveyRate = ($totalEmployees > 0 && $totalSurveys > 0)
            ? (int)round(($totalSurveyResponses / ($totalEmployees * $totalSurveys)) * 100)
            : 0;
        $evalRate = ($totalEmployees > 0 && $totalEvaluations > 0)
            ? (int)round(($totalEvaluationResponses / ($totalEmployees * $totalEvaluations)) * 100)
            : 0;

        $stats = [
            'employees' => $totalEmployees,
            'surveyRate' => $surveyRate,
            'evalRate' => $evalRate,
            'surveys' => $totalSurveys,
            'evaluations' => $totalEvaluations,
            'requests' => $this->safeCountTable('employee_change_requests'),
        ];

        $user = Yii::$app->user->identity;

        return $this->render('dashboard', [
            'stats' => $stats,
            'userName' => $this->userDisplayName(),
            'moduleAccess' => $this->buildModuleAccess($user),
        ]);
    }

    public function actionEmployees()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->view->params['bodyClass'] = 'bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen';

        $request = Yii::$app->request;
        $search = trim((string)$request->get('search', ''));
        $deptParam = trim((string)$request->get('dept', ''));
        $schoolDeptParam = trim((string)$request->get('school_dept', ''));
        $statusParam = trim((string)$request->get('status', ''));
        $joinDate = trim((string)$request->get('join_date', ''));

        $departmentsQuery = Department::find()->orderBy(['name' => SORT_ASC]);
        if (Department::hasColumn('status')) {
            $departmentsQuery->andWhere(['status' => 'active']);
        }
        $departments = $departmentsQuery->all();

        $deptFilter = $this->parseEmployeeDirectoryFilterParam($deptParam);
        $dept = 0;
        if ($deptFilter['mode'] === 'specific') {
            $dept = (int)$deptFilter['id'];
            $validDept = false;
            foreach ($departments as $department) {
                if ((int)$department->id === $dept) {
                    $validDept = true;
                    break;
                }
            }
            if (!$validDept) {
                $dept = 0;
                $deptFilter = ['mode' => 'none', 'id' => 0];
                $deptParam = '';
            }
        }

        $schoolDepartments = [];
        $hasSchoolDepartments = SchoolDepartment::isEnabled();
        $schoolDeptFilter = ['mode' => 'none', 'id' => 0];
        $schoolDept = 0;
        if ($hasSchoolDepartments) {
            $schoolDepartments = SchoolDepartment::find()->orderBy(['name' => SORT_ASC])->all();
            $schoolDeptFilter = $this->parseEmployeeDirectoryFilterParam($schoolDeptParam);
            if ($schoolDeptFilter['mode'] === 'specific') {
                $schoolDept = (int)$schoolDeptFilter['id'];
                $validSchoolDept = false;
                foreach ($schoolDepartments as $schoolDepartment) {
                    if ((int)$schoolDepartment->id === $schoolDept) {
                        $validSchoolDept = true;
                        break;
                    }
                }
                if (!$validSchoolDept) {
                    $schoolDept = 0;
                    $schoolDeptFilter = ['mode' => 'none', 'id' => 0];
                    $schoolDeptParam = '';
                }
            }
        } else {
            $schoolDeptParam = '';
        }

        $statusFilter = $this->parseEmployeeDirectoryFilterParam($statusParam);
        if ($statusFilter['mode'] === 'specific') {
            $allowedStatus = ['Active', 'Onboarding', 'Leave', 'Terminated'];
            if (!in_array((string)$statusFilter['id'], $allowedStatus, true)) {
                $statusFilter = ['mode' => 'none', 'id' => 0];
                $statusParam = '';
            }
        }

        $hasActiveFilters = $search !== ''
            || $deptFilter['mode'] !== 'none'
            || $schoolDeptFilter['mode'] !== 'none'
            || $statusFilter['mode'] !== 'none'
            || $joinDate !== '';

        $totalCount = 0;
        $pagination = new Pagination([
            'totalCount' => 0,
            'pageSize' => 10,
            'pageParam' => 'page',
        ]);
        $rows = [];
        $hasManagerId = Employee::hasColumn('manager_id');
        $hasStatus = Employee::hasColumn('status');
        $hasJoiningDate = Employee::hasColumn('joining_date');

        if ($hasActiveFilters) {
        $with = ['department', 'department.manager', 'manager'];
        if (Employee::hasColumn('school_department_id')) {
            $with[] = 'schoolDepartment';
            $with[] = 'schoolDepartment.head';
        }
        if (SchoolDepartment::isEnabled()) {
            $with[] = 'managedSchoolDepartments';
            $with[] = 'managedSchoolDepartments.head';
            $with[] = 'deputySchoolDepartmentRecord';
            $with[] = 'deputySchoolDepartmentRecord.head';
        }
        if (Employee::hasColumn('user_id')) {
            $with[] = 'linkedUser';
        }
        $query = Employee::find()->alias('e')->with($with)->orderBy(['e.id' => SORT_DESC]);
        if ($search !== '') {
            $searchConditions = [
                ['like', 'e.full_name', $search],
                ['like', 'e.email', $search],
            ];
            if (Employee::hasColumn('personal_email')) {
                $searchConditions[] = ['like', 'e.personal_email', $search];
            }
            $searchConditions[] = ['like', 'e.job_title', $search];
            $query->andWhere(array_merge(['or'], $searchConditions));
        }
        $this->applyEmployeeDirectoryDepartmentFilters(
            $query,
            $deptFilter,
            $dept,
            $schoolDeptFilter,
            $schoolDept,
            $hasSchoolDepartments
        );
        if ($hasStatus && $statusFilter['mode'] === 'specific') {
            $query->andWhere(['e.status' => (string)$statusFilter['id']]);
        }

        if ($hasJoiningDate && $joinDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $joinDate)) {
            $query->andWhere(['e.joining_date' => $joinDate]);
        }

        $totalCount = (int)$query->count();
        $pagination = new Pagination([
            'totalCount' => $totalCount,
            'pageSize' => 10,
            'pageParam' => 'page',
        ]);

        $employees = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $rows = [];
        foreach ($employees as $employee) {
            $status = $employee->getDisplayStatus();
            $badge = $this->statusBadge($status);
            $rows[] = [
                'id' => (int)$employee->id,
                'full_name' => (string)$employee->full_name,
                'job_title' => (string)$employee->job_title,
                'email' => $employee->getContactEmail() !== '' ? $employee->getContactEmail() : '—',
                'department_name' => $employee->getDisplayDepartmentName(),
                'manager_name' => $this->employeeDirectoryManagerName($employee, $hasManagerId),
                'joining_date' => $hasJoiningDate ? (string)($employee->joining_date ?? '') : '',
                'status' => $status,
                'status_badge_class' => $badge['bg'],
                'status_badge_label' => $badge['label'],
                'is_managed_hr' => $employee->isManagedHrEmployee(),
            ];
        }
        }

        return $this->render('employees', [
            'employees' => $rows,
            'pagination' => $pagination,
            'totalCount' => $totalCount,
            'hasActiveFilters' => $hasActiveFilters,
            'userName' => $this->userDisplayName(),
            'departments' => $departments,
            'schoolDepartments' => $schoolDepartments,
            'hasSchoolDepartments' => $hasSchoolDepartments,
            'search' => $search,
            'dept' => $dept,
            'deptParam' => $deptParam,
            'schoolDept' => $schoolDept,
            'schoolDeptParam' => $schoolDeptParam,
            'statusParam' => $statusParam,
            'joinDate' => $joinDate,
            'hasStatus' => $hasStatus,
            'hasJoiningDate' => $hasJoiningDate,
        ]);
    }

    private function applyEmployeeDirectoryDepartmentFilters(
        \yii\db\ActiveQuery $query,
        array $deptFilter,
        int $dept,
        array $schoolDeptFilter,
        int $schoolDept,
        bool $hasSchoolDepartments
    ): void {
        if (
            $deptFilter['mode'] === 'all'
            && (!$hasSchoolDepartments || $schoolDeptFilter['mode'] === 'all')
        ) {
            return;
        }

        $branches = [];

        if ($deptFilter['mode'] === 'specific' && $dept > 0 && Employee::hasColumn('department_id')) {
            $branches[] = ['e.department_id' => $dept];
        } elseif ($deptFilter['mode'] === 'all' && Employee::hasColumn('department_id')) {
            $branches[] = ['and', ['not', ['e.department_id' => null]], ['>', 'e.department_id', 0]];
        }

        if ($hasSchoolDepartments) {
            if ($schoolDeptFilter['mode'] === 'specific' && $schoolDept > 0) {
                $branches[] = [
                    'or',
                    Employee::hasColumn('school_department_id') ? ['e.school_department_id' => $schoolDept] : '0=1',
                    [
                        'exists',
                        SchoolDepartment::find()
                            ->alias('sd_filter')
                            ->where('sd_filter.head_id = e.id')
                            ->andWhere(['sd_filter.id' => $schoolDept]),
                    ],
                    [
                        'exists',
                        SchoolDepartment::find()
                            ->alias('sd_deputy_filter')
                            ->where('sd_deputy_filter.deputy_head_id = e.id')
                            ->andWhere(['sd_deputy_filter.id' => $schoolDept]),
                    ],
                ];
            } elseif ($schoolDeptFilter['mode'] === 'all') {
                $schoolAllBranches = [];
                if (Employee::hasColumn('school_department_id')) {
                    $schoolAllBranches[] = [
                        'and',
                        ['not', ['e.school_department_id' => null]],
                        ['>', 'e.school_department_id', 0],
                    ];
                }
                if (Employee::hasColumn('teacher')) {
                    $schoolAllBranches[] = ['e.teacher' => 1];
                }
                $schoolAllBranches[] = [
                    'exists',
                    SchoolDepartment::find()
                        ->alias('sd_any')
                        ->where('sd_any.head_id = e.id OR sd_any.deputy_head_id = e.id'),
                ];
                $branches[] = array_merge(['or'], $schoolAllBranches);
            }
        }

        if ($branches === []) {
            return;
        }

        if (count($branches) === 1) {
            $query->andWhere($branches[0]);

            return;
        }

        $query->andWhere(array_merge(['or'], $branches));
    }

    /**
     * @return array{mode:'none'|'all'|'specific',id:int|string}
     */
    private function parseEmployeeDirectoryFilterParam(string $value): array
    {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return ['mode' => 'none', 'id' => 0];
        }
        if (strtolower($value) === 'all') {
            return ['mode' => 'all', 'id' => 0];
        }
        if (ctype_digit($value)) {
            return ['mode' => 'specific', 'id' => (int)$value];
        }

        return ['mode' => 'specific', 'id' => $value];
    }

    public function actionEmployeeAction()
    {
        $this->requireHr();
        $this->requirePermission('employees');

        $request = Yii::$app->request;
        $action = (string)$request->post('action', '');
        $employeeId = (int)$request->post('employee_id', 0);
        $redirectBack = (string)$request->post('redirect_back', '');

        if ($redirectBack === '' || $redirectBack[0] !== '/') {
            $redirectBack = '/hr/hr/employees';
        }

        if ($employeeId <= 0) {
            return $this->redirect($redirectBack);
        }

        $employee = Employee::find()->where(['id' => $employeeId])->one();
        if ($employee === null) {
            return $this->redirect($redirectBack);
        }

        if ($employee->isManagedHrEmployee() && in_array($action, ['delete', 'change_status'], true)) {
            Yii::$app->session->setFlash(
                'error',
                'HR administrator accounts can only be managed from HR Management. You may send surveys or evaluations from here.'
            );
            return $this->redirect($redirectBack);
        }

        try {
            if ($action === 'delete') {
                if (Employee::hasColumn('manager_id')) {
                    Employee::updateAll(['manager_id' => null], ['manager_id' => $employeeId]);
                }
                $employee->delete();
            } elseif ($action === 'change_status') {
                if (Employee::hasColumn('status')) {
                    $newStatus = (string)$request->post('status', '');
                    $allowed = ['Active', 'Onboarding', 'Leave', 'Terminated'];
                    if (in_array($newStatus, $allowed, true)) {
                        $employee->status = $newStatus;
                        $employee->save(false, ['status']);
                    }
                }
            }
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
        }

        return $this->redirect($redirectBack);
    }

    public function actionViewEmployee($id)
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->view->params['bodyClass'] = 'bg-[#f8f4f8] font-display text-slate-900 min-h-screen flex';

        $employee = Employee::find()
            ->with(['department.manager', 'manager'])
            ->where(['id' => (int)$id])
            ->one();

        if ($employee === null) {
            Yii::$app->session->setFlash('error', 'Employee not found.');
            return $this->redirect(['/hr/hr/employees']);
        }

        if ($employee->isManagedHrEmployee()) {
            Yii::$app->session->setFlash('info', 'HR administrator profiles are managed from Manage HR.');
            return $this->redirect(['/hr/hr/manage-hr']);
        }

        $portalCredentialService = $this->employeePortalCredentialService();
        if (Employee::hasColumn('login_password_plain')) {
            $portalCredentialService->ensureVisiblePassword($employee);
            $employee->refresh();
        }

        return $this->render('view-employee', [
            'employee' => $employee,
            'userName' => $this->userDisplayName(),
            'hasJoiningDate' => Employee::hasColumn('joining_date'),
            'hasMaritalStatus' => Employee::hasColumn('marital_status'),
            'hasPositionLevel' => Employee::hasColumn('position_level'),
            'hasEmploymentType' => Employee::hasColumn('employment_type'),
            'hasEmployeeCategory' => Employee::hasColumn('teacher'),
            'hasPhone' => Employee::hasColumn('phone'),
            'hasLocation' => Employee::hasColumn('location'),
            'hasStatus' => Employee::hasColumn('status'),
            'hasPersonalEmail' => Employee::hasColumn('personal_email'),
            'hasPortalCredentials' => Employee::hasColumn('user_id'),
            'linkedUser' => $this->resolveLinkedUserForEmployee($employee),
        ]);
    }

    public function actionOnboarding($id = null)
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->view->params['bodyClass'] = 'bg-[#f8f4f8] font-display text-slate-900 min-h-screen flex';

        $hasJoiningDate = Employee::hasColumn('joining_date');
        $hasEmployeeCategory = Employee::hasColumn('teacher');

        $employee = null;
        if ($id !== null) {
            $employee = Employee::find()->with(['department', 'manager', 'schoolDepartment'])->where(['id' => (int)$id])->one();
            if ($employee === null) {
                throw new \yii\web\NotFoundHttpException('Employee not found.');
            }
            if ($employee->isManagedHrEmployee()) {
                Yii::$app->session->setFlash(
                    'error',
                    'HR administrator profiles are edited from Manage HR, not the employee directory.'
                );
                return $this->redirect(['/hr/hr/employees']);
            }
        }

        $model = $employee ? OnboardingEmployeeForm::fromEmployee($employee) : new OnboardingEmployeeForm();
        if ($employee) {
            $model->id = (int)$employee->id;
        }

        $departmentsQuery = Department::find()->orderBy(['name' => SORT_ASC]);
        if (Department::hasColumn('status')) {
            $departmentsQuery->andWhere(['status' => 'active']);
        }
        $departments = $departmentsQuery->all();

        $schoolDepartments = [];
        $schoolManagersByDepartment = [];
        $needsSchoolDepartments = SchoolDepartment::isEnabled()
            && ($employee === null || ($employee !== null && $employee->isTeacher()));
        if ($needsSchoolDepartments) {
            $schoolDepartmentsQuery = SchoolDepartment::find()->orderBy(['name' => SORT_ASC]);
            $schoolDepartmentsQuery->andWhere(['status' => 'active']);
            $schoolDepartments = $schoolDepartmentsQuery->all();
            $schoolManagersByDepartment = $this->buildSchoolManagersByDepartment(
                $schoolDepartments,
                $employee ? (int)$employee->id : null
            );
        }

        $linkedUser = null;
        if ($employee !== null && $employee->hasAttribute('user_id') && (int)$employee->user_id > 0) {
            $linkedUser = User::find()->where(['id' => (int)$employee->user_id])->one();
        }
        $hasLinkedUser = $linkedUser !== null;
        $portalCredentialService = $this->employeePortalCredentialService();
        if ($employee === null && trim((string)$model->portal_password) === '') {
            $model->portal_password = $portalCredentialService->generatePreviewPassword();
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $tx = Yii::$app->db->beginTransaction();
            try {
                $emp = $employee ?: new Employee();
                $emp->full_name = $model->full_name;
                $emp->job_title = $model->job_title;
                if ($emp->hasAttribute('personal_email')) {
                    $emp->personal_email = trim((string)$model->personal_email) !== ''
                        ? trim((string)$model->personal_email)
                        : null;
                }
                if ($employee === null && $emp->hasAttribute('email')) {
                    $emp->email = $portalCredentialService->generateUniquePortalEmail($emp);
                }
                if ($emp->hasAttribute('status') && (!$emp->status || trim((string)$emp->status) === '')) {
                    $emp->status = 'Active';
                }
                if ($emp->hasAttribute('joining_date')) {
                    $emp->joining_date = $model->joining_date;
                }
                if ($emp->hasAttribute('department_id') || $emp->hasAttribute('school_department_id')) {
                    $isTeacherSave = (int)$model->teacher === 1;
                    if ($isTeacherSave && $emp->hasAttribute('school_department_id')) {
                        $emp->school_department_id = $model->department_id ? (int)$model->department_id : null;
                        if ($emp->hasAttribute('department_id')) {
                            $emp->department_id = null;
                        }
                    } elseif ($emp->hasAttribute('department_id')) {
                        $emp->department_id = $model->department_id ? (int)$model->department_id : null;
                        if ($emp->hasAttribute('school_department_id')) {
                            $emp->school_department_id = null;
                        }
                    }
                }
                if ($emp->hasAttribute('manager_id')) {
                    $emp->manager_id = $model->manager_id ?: null;
                }
                foreach (['phone', 'marital_status', 'position_level', 'employment_type'] as $attr) {
                    if ($emp->hasAttribute($attr)) {
                        $emp->$attr = $model->$attr ?: null;
                    }
                }
                if ($emp->hasAttribute('teacher')) {
                    $emp->teacher = (int)$model->teacher === 1 ? 1 : 0;
                }
                if ($emp->hasAttribute('location')) {
                    $location = OnboardingEmployeeForm::normalizeWorkLocation($model->location);
                    if ($location === null && $employee !== null && $employee->hasAttribute('location')) {
                        $location = OnboardingEmployeeForm::normalizeWorkLocation(
                            $employee->location !== null ? (string)$employee->location : null
                        );
                    }
                    $emp->location = $location;
                }

                if (!$emp->save()) {
                    foreach ($emp->getErrors() as $field => $messages) {
                        $target = $model->hasProperty((string)$field) ? (string)$field : 'full_name';
                        foreach ($messages as $message) {
                            $model->addError($target, $message);
                        }
                    }
                    $tx->rollBack();
                } else {
                    $portalResult = $portalCredentialService->syncPortalAccount($emp, (string)$model->portal_password);
                    if (!$portalResult['success']) {
                        $model->addError('portal_password', (string)$portalResult['message']);
                        $tx->rollBack();
                    } else {
                        $employeeSaveAttrs = ['email', 'user_id', 'updated_at'];
                        if (Employee::hasColumn('login_password_plain')) {
                            $employeeSaveAttrs[] = 'login_password_plain';
                        }
                        if (!$emp->save(false, $employeeSaveAttrs)) {
                            $model->addError('full_name', 'Unable to save employee portal credentials.');
                            $tx->rollBack();
                        } elseif (!$this->saveEmployeeSubjectAssignments($emp, $model)) {
                            $tx->rollBack();
                        } else {
                            $tx->commit();
                            Yii::$app->session->setFlash('success', 'Employee saved.');
                            return $this->redirect(['/hr/hr/employees']);
                        }
                    }
                }
            } catch (\Throwable $e) {
                $tx->rollBack();
                Yii::error($e->getMessage(), __METHOD__);
                $model->addError('full_name', YII_DEBUG ? ('Unexpected error: ' . $e->getMessage()) : 'Unexpected error while saving.');
            }
        }

        return $this->renderOnboardingForm(
            $model,
            $employee,
            $departments,
            $schoolDepartments,
            $schoolManagersByDepartment,
            $hasJoiningDate,
            $hasEmployeeCategory,
            $hasLinkedUser,
            $portalCredentialService
        );
    }

    private function employeePortalCredentialService(): EmployeePortalCredentialService
    {
        return new EmployeePortalCredentialService();
    }

    private function renderOnboardingForm(
        OnboardingEmployeeForm $model,
        ?Employee $employee,
        array $departments,
        array $schoolDepartments,
        array $schoolManagersByDepartment,
        bool $hasJoiningDate,
        bool $hasEmployeeCategory,
        bool $hasLinkedUser,
        ?EmployeePortalCredentialService $portalCredentialService = null
    ) {
        $context = $this->resolveOnboardingDepartmentContext(
            $model,
            $employee,
            $departments,
            $schoolDepartments,
            $schoolManagersByDepartment
        );
        $hrManagersByDepartment = $this->buildManagersByDepartment($departments, $employee ? (int)$employee->id : null);
        $portalCredentialService ??= $this->employeePortalCredentialService();

        return $this->render('onboarding', [
            'model' => $model,
            'isEdit' => $employee !== null,
            'employeeId' => $employee ? (int)$employee->id : null,
            'departments' => $departments,
            'schoolDepartments' => $schoolDepartments,
            'departmentOptions' => $context['departmentOptions'],
            'managers' => $context['managers'],
            'userName' => $this->userDisplayName(),
            'errorList' => $model->getErrors(),
            'managersByDepartment' => $context['managersByDepartment'],
            'hrManagersByDepartment' => $hrManagersByDepartment,
            'schoolManagersByDepartment' => $schoolManagersByDepartment,
            'hasJoiningDate' => $hasJoiningDate,
            'hasLinkedUser' => $hasLinkedUser,
            'hasEmployeeCategory' => $hasEmployeeCategory,
            'usesSchoolDepartments' => (int)$model->teacher === 1 && $schoolDepartments !== [],
            'allowTeacherTypeSwitch' => $employee === null && $hasEmployeeCategory,
            'subjectsByDepartment' => $this->buildSubjectsByDepartment(),
            'hasTeacherSubjects' => EmployeeSchoolSubject::isEnabled() && SchoolSubject::isEnabled(),
            'portalEmailDomain' => $portalCredentialService->getDomain(),
            'hasPersonalEmail' => Employee::hasColumn('personal_email'),
            'hasPortalCredentials' => Employee::hasColumn('user_id'),
        ]);
    }

    /**
     * @return array<int, array<int, array{id:int,name:string}>>
     */
    private function buildSubjectsByDepartment(): array
    {
        if (!SchoolSubject::isEnabled()) {
            return [];
        }

        $subjectsByDepartment = [];
        foreach (SchoolSubject::find()->orderBy(['school_department_id' => SORT_ASC, 'name' => SORT_ASC])->all() as $subject) {
            $departmentId = (int)$subject->school_department_id;
            $subjectsByDepartment[$departmentId][] = [
                'id' => (int)$subject->id,
                'name' => (string)$subject->name,
            ];
        }

        return $subjectsByDepartment;
    }

    private function saveEmployeeSubjectAssignments(Employee $employee, OnboardingEmployeeForm $model): bool
    {
        if (!EmployeeSchoolSubject::isEnabled()) {
            return true;
        }

        if (!$employee->isTeacher()) {
            EmployeeSchoolSubject::deleteAll(['employee_id' => (int)$employee->id]);

            return true;
        }

        $departmentId = (int)($model->department_id ?? 0);
        $subjectIds = array_values(array_unique(array_filter(array_map('intval', $model->subject_ids ?? []))));

        if ($departmentId > 0 && SchoolSubject::isEnabled() && $subjectIds !== []) {
            $subjectIds = array_map(
                'intval',
                SchoolSubject::find()
                    ->select('id')
                    ->where([
                        'school_department_id' => $departmentId,
                        'id' => $subjectIds,
                    ])
                    ->column()
            );
        } else {
            $subjectIds = [];
        }

        $existingIds = array_map(
            'intval',
            EmployeeSchoolSubject::find()
                ->select('school_subject_id')
                ->where(['employee_id' => (int)$employee->id])
                ->column()
        );

        $toDelete = array_diff($existingIds, $subjectIds);
        if ($toDelete !== []) {
            EmployeeSchoolSubject::deleteAll([
                'employee_id' => (int)$employee->id,
                'school_subject_id' => $toDelete,
            ]);
        }

        $toInsert = array_diff($subjectIds, $existingIds);
        foreach ($toInsert as $subjectId) {
            $assignment = new EmployeeSchoolSubject([
                'employee_id' => (int)$employee->id,
                'school_subject_id' => (int)$subjectId,
            ]);
            if (!$assignment->save()) {
                $model->addError('subject_ids', implode(' ', $assignment->getFirstErrors()) ?: 'Unable to save subject assignments.');

                return false;
            }
        }

        return true;
    }

    /**
     * @param Department[] $departments
     * @param SchoolDepartment[] $schoolDepartments
     * @return array{departmentOptions: array, managers: Employee[], managersByDepartment: array}
     */
    private function resolveOnboardingDepartmentContext(
        OnboardingEmployeeForm $model,
        ?Employee $employee,
        array $departments,
        array $schoolDepartments,
        array $schoolManagersByDepartment
    ): array {
        $excludeEmployeeId = $employee ? (int)$employee->id : null;
        $usesSchoolDepartments = (int)$model->teacher === 1 && $schoolDepartments !== [];
        $departmentOptions = $usesSchoolDepartments ? $schoolDepartments : $departments;

        $selectedDepartmentId = (int)($model->department_id ?? 0);
        $departmentIds = array_map(static fn($dept) => (int)$dept->id, $departmentOptions);
        $hasSelectedDepartment = $selectedDepartmentId > 0 && in_array($selectedDepartmentId, $departmentIds, true);

        $managers = $hasSelectedDepartment
            ? ($usesSchoolDepartments
                ? $this->findSchoolDepartmentManagers($selectedDepartmentId, $excludeEmployeeId)
                : $this->findDepartmentManagers($selectedDepartmentId, $excludeEmployeeId))
            : [];

        if (!$hasSelectedDepartment) {
            $model->manager_id = null;
        }

        $managersByDepartment = $usesSchoolDepartments
            ? $schoolManagersByDepartment
            : $this->buildManagersByDepartment($departments, $excludeEmployeeId);

        return [
            'departmentOptions' => $departmentOptions,
            'managers' => $managers,
            'managersByDepartment' => $managersByDepartment,
        ];
    }

    public function actionManagersByDepartment($department_id = null)
    {
        $this->requireHr();
        $this->requirePermission('employees');
        Yii::$app->response->format = Response::FORMAT_JSON;

        $deptId = (int)$department_id;
        if ($deptId <= 0) {
            return ['items' => []];
        }
        $items = [];
        foreach ($this->findDepartmentManagers($deptId, null) as $mgr) {
            $items[] = [
                'id' => (int)$mgr->id,
                'label' => trim($mgr->full_name . ($mgr->job_title ? ' (' . $mgr->job_title . ')' : '')),
            ];
        }

        return ['items' => $items];
    }

    /**
     * Managers-by-department: only the assigned department head (manager_id).
     */
    private function findDepartmentManagers(?int $departmentId, ?int $excludeEmployeeId): array
    {
        if ($departmentId === null || $departmentId <= 0 || !Department::hasColumn('manager_id')) {
            return [];
        }

        $dept = Department::find()->where(['id' => $departmentId])->one();
        if ($dept === null || !$dept->manager_id) {
            return [];
        }

        $headId = (int)$dept->manager_id;
        if ($excludeEmployeeId !== null && $headId === $excludeEmployeeId) {
            return [];
        }

        $head = Employee::find()->where(['id' => $headId])->one();

        return $head !== null ? [$head] : [];
    }

    private function buildManagersByDepartment(array $departments, ?int $excludeEmployeeId): array
    {
        $result = [];
        foreach ($departments as $dept) {
            $deptId = (int)$dept->id;
            $result[$deptId] = [];
            foreach ($this->findDepartmentManagers($deptId, $excludeEmployeeId) as $mgr) {
                $result[$deptId][] = [
                    'id' => (int)$mgr->id,
                    'label' => trim($mgr->full_name . ($mgr->job_title ? ' (' . $mgr->job_title . ')' : '')),
                ];
            }
        }

        return $result;
    }

    private function findSchoolDepartmentManagers(?int $schoolDepartmentId, ?int $excludeEmployeeId): array
    {
        if ($schoolDepartmentId === null || $schoolDepartmentId <= 0 || !SchoolDepartment::isEnabled()) {
            return [];
        }

        $dept = SchoolDepartment::find()->where(['id' => $schoolDepartmentId])->one();
        if ($dept === null || !$dept->head_id) {
            return [];
        }

        $headId = (int)$dept->head_id;
        if ($excludeEmployeeId !== null && $headId === $excludeEmployeeId) {
            return [];
        }

        $head = Employee::find()->where(['id' => $headId])->one();

        return $head !== null ? [$head] : [];
    }

    /**
     * @param SchoolDepartment[] $schoolDepartments
     */
    private function buildSchoolManagersByDepartment(array $schoolDepartments, ?int $excludeEmployeeId): array
    {
        $result = [];
        foreach ($schoolDepartments as $dept) {
            $deptId = (int)$dept->id;
            $result[$deptId] = [];
            foreach ($this->findSchoolDepartmentManagers($deptId, $excludeEmployeeId) as $mgr) {
                $result[$deptId][] = [
                    'id' => (int)$mgr->id,
                    'label' => trim($mgr->full_name . ($mgr->job_title ? ' (' . $mgr->job_title . ')' : '')),
                ];
            }
        }

        return $result;
    }

    public function actionRequests()
    {
        $this->requireHr();
        $this->requirePermission('requests');
        $this->view->params['bodyClass'] = 'bg-[#f8f4f8] text-slate-900 font-display min-h-screen';

        $search = trim((string)Yii::$app->request->get('search', ''));
        [$status, $urgency] = $this->normalizeChangeRequestFilters(
            trim((string)Yii::$app->request->get('status', '')),
            trim((string)Yii::$app->request->get('urgency', ''))
        );
        $selectedId = (int)Yii::$app->request->get('request_id', 0);

        if (Yii::$app->request->isPost) {
            $postId = (int)Yii::$app->request->post('request_id', 0);
            $action = trim((string)Yii::$app->request->post('action', ''));
            $request = $postId > 0 ? EmployeeChangeRequest::findOne($postId) : null;

            if ($request !== null && in_array($action, ['approve', 'reject'], true)) {
                $request->status = $action === 'approve'
                    ? EmployeeChangeRequest::STATUS_APPROVED
                    : EmployeeChangeRequest::STATUS_REJECTED;
                if ($request->save(false)) {
                    Yii::$app->session->setFlash(
                        'success',
                        $action === 'approve' ? 'Request approved.' : 'Request rejected.'
                    );
                }
            }

            return $this->redirect([
                '/hr/hr/requests',
                'request_id' => $postId > 0 ? $postId : $selectedId,
                'search' => $search,
                'status' => $status,
                'urgency' => $urgency,
            ]);
        }

        $query = EmployeeChangeRequest::find()->alias('r')->joinWith(['employee e']);

        if ($search !== '') {
            if (ctype_digit($search)) {
                $conditions = [
                    'or',
                    ['like', 'e.full_name', $search],
                    ['r.id' => (int)$search],
                ];
                if (EmployeeChangeRequest::hasColumn('title')) {
                    $conditions[] = ['like', 'r.title', $search];
                }
                $query->andWhere($conditions);
            } else {
                $conditions = ['or', ['like', 'e.full_name', $search]];
                if (EmployeeChangeRequest::hasColumn('title')) {
                    $conditions[] = ['like', 'r.title', $search];
                }
                $query->andWhere($conditions);
            }
        }
        if ($status !== '' && $status !== 'All') {
            $query->andWhere(['r.status' => $status]);
        }
        if ($urgency !== '' && $urgency !== 'All') {
            $query->andWhere(['r.urgency' => $urgency]);
        }

        $requests = $query->orderBy(['r.created_at' => SORT_DESC, 'r.id' => SORT_DESC])->all();

        $selectedRequest = null;
        if ($selectedId > 0) {
            foreach ($requests as $request) {
                if ((int)$request->id === $selectedId) {
                    $selectedRequest = $request;
                    break;
                }
            }
            if ($selectedRequest === null) {
                $selectedRequest = EmployeeChangeRequest::find()
                    ->alias('r')
                    ->joinWith(['employee e'])
                    ->where(['r.id' => $selectedId])
                    ->one();
            }
        }

        return $this->render('requests', [
            'requests' => $requests,
            'selectedRequest' => $selectedRequest,
            'selectedId' => $selectedId,
            'search' => $search,
            'status' => $status,
            'urgency' => $urgency,
            'resultCount' => count($requests),
            'hasActiveFilters' => $search !== '' || $status !== '' || $urgency !== '',
            'userName' => $this->userDisplayName(),
        ]);
    }

    /**
     * @return array{0:string,1:string} [status, urgency]
     */
    private function normalizeChangeRequestFilters(string $status, string $urgency): array
    {
        if (strcasecmp($status, 'all') === 0) {
            $status = '';
        }
        $allowedStatus = [
            EmployeeChangeRequest::STATUS_PENDING,
            EmployeeChangeRequest::STATUS_APPROVED,
            EmployeeChangeRequest::STATUS_REJECTED,
        ];
        if (!in_array($status, $allowedStatus, true)) {
            $status = '';
        }

        if (strcasecmp($urgency, 'all') === 0) {
            $urgency = '';
        }
        $allowedUrgency = [
            EmployeeChangeRequest::URGENCY_NORMAL,
            EmployeeChangeRequest::URGENCY_URGENT,
        ];
        if (!in_array($urgency, $allowedUrgency, true)) {
            $urgency = '';
        }

        return [$status, $urgency];
    }

    public function actionSurveyDisplay()
    {
        return $this->redirect(['/hr/survey/index']);
    }

    public function actionEvaluationResults()
    {
        return $this->redirect(['/hr/evaluation/index']);
    }

    public function actionLms()
    {
        $this->requireHr();
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !$user->isSuperHrAdmin()) {
            throw new ForbiddenHttpException('LMS access is limited to the super admin.');
        }

        return $this->redirect(['/lms/portal/teacher']);
    }

    public function actionManageHr()
    {
        $this->requireManageHr();
        $this->view->params['bodyClass'] = 'bg-slate-100 text-slate-900 min-h-screen';

        $actor = Yii::$app->user->identity;
        if (!$actor instanceof User) {
            throw new ForbiddenHttpException('Access denied.');
        }

        $model = new GrantEmployeePermissionsForm();

        if (Yii::$app->request->isPost && (string)Yii::$app->request->post('form_type') === 'grant_permissions') {
            if ($model->load(Yii::$app->request->post())) {
                $postedPermissions = Yii::$app->request->post('permissions', []);
                $model->permissions = is_array($postedPermissions) ? $postedPermissions : [];

                if ($model->save($actor)) {
                    $successMessage = $model->isExternalAccount()
                        ? 'External user created successfully.'
                        : 'Dashboard access saved successfully.';
                    Yii::$app->session->setFlash('success', $successMessage);
                    return $this->redirect(['manage-hr']);
                }

                if (!$model->isExternalAccount()) {
                    $model->refreshDisplayFromEmployee();
                }
            }
        }

        $search = trim((string)Yii::$app->request->get('q', ''));
        $moduleFilter = trim((string)Yii::$app->request->get('module', ''));

        $hrUsers = $this->buildHrUserRows($search, $moduleFilter);
        $activeCount = count(array_filter($hrUsers, static fn(array $row): bool => $row['isActive']));

        return $this->render('manage-hr', [
            'model' => $model,
            'employeeOptions' => $this->buildEmployeePermissionPickerOptions(),
            'hrUsers' => $hrUsers,
            'activeCount' => $activeCount,
            'permissionGroups' => ManageHrUserForm::permissionGroupsForActor($actor),
            'permissionPresets' => ManageHrUserForm::presets(),
            'moduleFilterOptions' => ManageHrUserForm::modulePermissionOptions(),
            'search' => $search,
            'moduleFilter' => $moduleFilter,
            'userName' => $this->userDisplayName(),
            'hasJoiningDate' => Employee::hasColumn('joining_date'),
            'hasJobTitle' => Employee::hasColumn('job_title'),
            'hasPhone' => Employee::hasColumn('phone'),
            'hasUserPhone' => User::hasColumn('phone'),
            'hasEmploymentType' => Employee::hasColumn('employment_type'),
            'hasPosition' => Employee::hasColumn('position_level'),
        ]);
    }

    public function actionEmployeePermissionProfile(int $id)
    {
        $this->requireManageHr();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $employee = Employee::findOne(['id' => $id]);
        if ($employee === null) {
            throw new NotFoundHttpException('Employee not found.');
        }

        $linkedUser = $this->resolveLinkedUserForEmployee($employee);

        $form = new GrantEmployeePermissionsForm();
        $form->loadFromEmployee($employee);

        $teacherLabel = '';
        if ($employee->hasAttribute('teacher') && (int)$employee->teacher === 1) {
            $teacherLabel = 'Teacher';
        }

        $storedPermissions = $linkedUser !== null ? $linkedUser->getPermissionList() : [];

        return [
            'employee_id' => (int)$employee->id,
            'full_name' => $form->display_full_name,
            'email' => $form->display_email,
            'dashboard_email' => $form->dashboard_email,
            'has_dashboard_account' => $form->has_dashboard_account,
            'phone' => $form->display_phone,
            'job_title' => $form->display_job_title,
            'position' => $form->display_position,
            'joining_date' => $form->display_joining_date,
            'employment_type' => $form->display_employment_type,
            'permissions' => $form->permissions,
            'stored_permissions' => $storedPermissions,
            'has_hr_access' => $linkedUser !== null && ($linkedUser->isHr() || $storedPermissions !== []),
            'is_super_admin' => $linkedUser !== null && $linkedUser->isSuperHrAdmin(),
            'employee_type' => $teacherLabel,
        ];
    }

    private function resolveLinkedUserForEmployee(Employee $employee): ?User
    {
        if (Employee::hasColumn('user_id') && (int)$employee->user_id > 0) {
            $user = User::findOne(['id' => (int)$employee->user_id]);
            if ($user instanceof User) {
                return $user;
            }
        }

        $email = trim((string)($employee->email ?? ''));
        if ($email === '') {
            return null;
        }

        $user = User::find()->where(['email' => $email])->one();

        return $user instanceof User ? $user : null;
    }

    public function actionViewHr($id)
    {
        $this->requireManageHr();
        $this->view->params['bodyClass'] = 'bg-slate-100 text-slate-900 min-h-screen';

        $hrUser = User::findOne(['id' => (int)$id]);
        if ($hrUser === null || !$hrUser->isHr()) {
            Yii::$app->session->setFlash('error', 'HR user not found.');
            return $this->redirect(['manage-hr']);
        }

        $search = trim((string)Yii::$app->request->get('q', ''));
        $moduleFilter = trim((string)Yii::$app->request->get('module', ''));

        $employee = Employee::hasColumn('user_id')
            ? Employee::find()->where(['user_id' => (int)$hrUser->id])->one()
            : null;

        $badge = $hrUser->getHrRoleBadge();
        $permissions = $hrUser->getPermissionList();

        return $this->render('view-hr', [
            'hrUser' => $hrUser,
            'employee' => $employee,
            'roleLabel' => $badge['label'],
            'roleClass' => $badge['class'],
            'permissions' => $permissions,
            'permissionGroups' => ManageHrUserForm::permissionGroups(),
            'search' => $search,
            'moduleFilter' => $moduleFilter,
            'userName' => $this->userDisplayName(),
            'hasJoiningDate' => Employee::hasColumn('joining_date'),
            'hasJobTitle' => Employee::hasColumn('job_title'),
            'hasPhone' => Employee::hasColumn('phone'),
            'hasEmploymentType' => Employee::hasColumn('employment_type'),
        ]);
    }

    public function actionUpdateHr($id)
    {
        $this->requireManageHr();
        $this->view->params['bodyClass'] = 'bg-slate-100 text-slate-900 min-h-screen';

        $actor = Yii::$app->user->identity;
        if (!$actor instanceof User) {
            throw new ForbiddenHttpException('Access denied.');
        }

        $userId = (int)$id;
        $hrUser = User::findOne(['id' => $userId]);
        if ($hrUser === null || !$hrUser->isHr()) {
            Yii::$app->session->setFlash('error', 'HR user not found.');
            return $this->redirect(['manage-hr']);
        }

        if ($hrUser->isSuperHrAdmin()) {
            Yii::$app->session->setFlash('error', 'Super admin accounts cannot be edited.');
            return $this->redirect(['manage-hr']);
        }

        $model = new UpdateHrUserForm();
        $model->loadFromUser($hrUser);

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post())) {
                $postedPermissions = Yii::$app->request->post('permissions', []);
                $model->permissions = is_array($postedPermissions) ? $postedPermissions : [];

                if ($model->save($actor)) {
                    Yii::$app->session->setFlash('success', 'HR user updated successfully.');
                    $search = trim((string)Yii::$app->request->post('return_q', ''));
                    $moduleFilter = trim((string)Yii::$app->request->post('return_module', ''));
                    return $this->redirect(['manage-hr', 'q' => $search, 'module' => $moduleFilter]);
                }
            }
        }

        $search = trim((string)Yii::$app->request->get('q', ''));
        $moduleFilter = trim((string)Yii::$app->request->get('module', ''));

        return $this->render('update-hr', [
            'model' => $model,
            'hrUser' => $hrUser,
            'permissionGroups' => ManageHrUserForm::permissionGroupsForActor($actor),
            'permissionPresets' => ManageHrUserForm::presets(),
            'search' => $search,
            'moduleFilter' => $moduleFilter,
            'userName' => $this->userDisplayName(),
            'hasJoiningDate' => Employee::hasColumn('joining_date'),
            'hasJobTitle' => Employee::hasColumn('job_title'),
            'hasPhone' => Employee::hasColumn('phone'),
            'hasEmploymentType' => Employee::hasColumn('employment_type'),
        ]);
    }

    public function actionManageHrAction()
    {
        $this->requireManageHr();
        $actor = Yii::$app->user->identity;
        if (!$actor instanceof User) {
            throw new ForbiddenHttpException('Access denied.');
        }

        $request = Yii::$app->request;
        $action = (string)$request->post('action', '');
        $userId = (int)$request->post('user_id', 0);
        $redirectBack = (string)$request->post('redirect_back', '');
        if ($redirectBack === '' || $redirectBack[0] !== '/') {
            $redirectBack = '/hr/hr/manage-hr';
        }

        if ($userId <= 0) {
            return $this->redirect($redirectBack);
        }

        $user = User::findOne(['id' => $userId]);
        if ($user === null || !$user->isHr()) {
            Yii::$app->session->setFlash('error', 'HR user not found.');
            return $this->redirect($redirectBack);
        }

        if ($action === 'delete') {
            if ($user->isSuperHrAdmin()) {
                Yii::$app->session->setFlash('error', 'Super admin accounts cannot be deleted.');
                return $this->redirect($redirectBack);
            }

            if ($user->hasAttribute('status')) {
                $user->status = User::STATUS_DELETED;
                $user->save(false, ['status']);
            } else {
                $user->delete();
            }
            Yii::$app->session->setFlash('success', 'HR user removed successfully.');
        }

        return $this->redirect($redirectBack);
    }

    /**
     * @return array<int, array{id:int,label:string,search:string}>
     */
    private function buildEmployeePermissionPickerOptions(): array
    {
        $query = Employee::find()->orderBy(['full_name' => SORT_ASC]);
        if (Employee::hasColumn('status')) {
            $query->andWhere(['status' => 'Active']);
        }

        $options = [];
        foreach ($query->all() as $employee) {
            if (!$employee instanceof Employee) {
                continue;
            }

            $name = trim((string)$employee->full_name);
            if ($name === '') {
                continue;
            }

            $email = trim((string)($employee->email ?? ''));
            $jobTitle = Employee::hasColumn('job_title') ? trim((string)($employee->job_title ?? '')) : '';
            $type = $employee->hasAttribute('teacher') && (int)$employee->teacher === 1 ? 'Teacher' : 'Employee';
            $label = $name . ' — ' . $type;
            if ($jobTitle !== '') {
                $label .= ' (' . $jobTitle . ')';
            }

            $options[] = [
                'id' => (int)$employee->id,
                'label' => $label,
                'search' => strtolower(trim($name . ' ' . $email . ' ' . $jobTitle . ' ' . $type)),
            ];
        }

        return $options;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildHrUserRows(string $search, string $moduleFilter): array
    {
        $rows = [];
        foreach (User::findHrAccounts() as $hrUser) {
            if ($hrUser->hasAttribute('status') && (int)$hrUser->status === User::STATUS_DELETED) {
                continue;
            }

            $username = (string)($hrUser->username ?? '');
            $email = $hrUser->getAccountEmailForDisplay();
            if ($search !== '') {
                $haystack = strtolower(trim($username . ($email !== '' ? ' ' . $email : '')));
                if (!str_contains($haystack, strtolower($search))) {
                    continue;
                }
            }

            if ($moduleFilter === 'none') {
                if ($hrUser->getPermissionList() !== []) {
                    continue;
                }
            } elseif ($moduleFilter !== '' && !$hrUser->hasModulePermission($moduleFilter)) {
                continue;
            }

            $badge = $hrUser->getHrRoleBadge();
            $pills = $hrUser->getPermissionPills(2);

            $rows[] = [
                'id' => (int)$hrUser->id,
                'username' => $username,
                'email' => $email,
                'initials' => $hrUser->getInitials(),
                'isActive' => $hrUser->isActiveAccount(),
                'isSuperAdmin' => $hrUser->isSuperHrAdmin(),
                'isExternal' => !Employee::hasColumn('user_id')
                    || !Employee::find()->where(['user_id' => (int)$hrUser->id])->exists(),
                'roleLabel' => $badge['label'],
                'roleClass' => $badge['class'],
                'permissions' => $hrUser->getPermissionList(),
                'permissionPills' => $pills['visible'],
                'permissionOverflow' => $pills['overflow'],
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, bool>
     */
    private function buildModuleAccess(?User $user): array
    {
        $keys = ['employees', 'surveys', 'evaluations', 'recruitment', 'student_health', 'requests', 'lms', 'manage_hr'];
        $access = [];
        foreach ($keys as $key) {
            if ($key === 'lms') {
                $access[$key] = $user instanceof User && $user->isSuperHrAdmin();
                continue;
            }
            $access[$key] = $user instanceof User && $user->hasPermission($key);
        }

        return $access;
    }

    private function safeCountTable(string $table): int
    {
        $schema = Yii::$app->db->schema->getTableSchema($table, true);
        if ($schema === null) {
            return 0;
        }

        return (int)(new \yii\db\Query())->from($table)->count('*', Yii::$app->db);
    }

    private function statusBadge(string $status): array
    {
        $s = strtolower(trim($status));
        if ($s === 'active') {
            return ['bg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', 'label' => 'Active'];
        }
        if ($s === 'onboarding') {
            return ['bg' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'label' => 'Onboarding'];
        }
        if ($s === 'leave') {
            return ['bg' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400', 'label' => 'Leave'];
        }
        if ($s === 'terminated') {
            return ['bg' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', 'label' => 'Terminated'];
        }
        $label = trim($status) !== '' ? $status : 'Active';
        return ['bg' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400', 'label' => $label];
    }

    /**
     * Manager column reflects school department head for teachers; otherwise HR department head
     * or the employee's direct manager.
     */
    private function employeeDirectoryManagerName(Employee $employee, bool $hasEmployeeManagerId): string
    {
        if ($employee->isTeacher() && SchoolDepartment::isEnabled()) {
            $managerName = trim($employee->getSchoolDepartmentReportingManagerName());
            if ($managerName !== '' && $managerName !== 'No Manager Assigned') {
                return $managerName;
            }

            return '—';
        }

        $department = $employee->department;
        if ($department !== null && Department::hasColumn('manager_id') && $department->manager) {
            return (string)$department->manager->full_name;
        }

        if ($hasEmployeeManagerId && $employee->manager) {
            return (string)$employee->manager->full_name;
        }

        return '—';
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

