<?php

namespace frontend\modules\hr\controllers;

use common\traits\HrAccessTrait;
use common\modules\employee\models\Department;
use common\modules\employee\models\Employee;
use Yii;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class DepartmentController extends Controller
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
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionDelete($id)
    {
        $this->requireHr();
        $this->requirePermission('employees');

        $model = Department::findOne(['id' => (int)$id]);
        if ($model === null) {
            Yii::$app->session->setFlash('error', 'Department not found.');
            return $this->redirect(['index']);
        }

        if (Employee::hasColumn('department_id')) {
            $employeeCount = (int)Employee::find()
                ->where(['department_id' => (int)$model->id])
                ->count();
            if ($employeeCount > 0) {
                Yii::$app->session->setFlash(
                    'error',
                    'Cannot delete this department while ' . $employeeCount . ' employee(s) are still assigned. Reassign them first.'
                );
                return $this->redirect(['index']);
            }
        }

        $name = (string)$model->name;
        try {
            if (!$model->delete()) {
                Yii::$app->session->setFlash('error', 'Department could not be deleted.');
                return $this->redirect(['index']);
            }
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Department could not be deleted. It may still be in use.');
            return $this->redirect(['index']);
        }

        Yii::$app->session->setFlash('success', 'Department "' . $name . '" was deleted successfully.');
        return $this->redirect(['index']);
    }

    public function actionIndex()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->view->params['bodyClass'] = 'bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen';

        $departments = Department::find()
            ->with(['manager'])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        $employeeCountsByDepartmentId = [];
        if (Employee::hasColumn('department_id')) {
            $rows = Employee::find()
                ->select(['department_id', 'cnt' => new Expression('COUNT(*)')])
                ->where(['not', ['department_id' => null]])
                ->groupBy(['department_id'])
                ->asArray()
                ->all();

            foreach ($rows as $row) {
                $deptId = (int)($row['department_id'] ?? 0);
                if ($deptId > 0) {
                    $employeeCountsByDepartmentId[$deptId] = (int)($row['cnt'] ?? 0);
                }
            }
        }

        $cards = [];
        $hasStatus = Department::hasColumn('status');
        $hasManager = Department::hasColumn('manager_id');

        foreach ($departments as $dept) {
            $deptId = (int)$dept->id;
            $cards[] = [
                'id' => $deptId,
                'name' => (string)($dept->name ?? ''),
                'managerName' => ($hasManager && $dept->manager) ? (string)$dept->manager->full_name : '',
                'status' => $hasStatus ? (string)($dept->status ?? '') : '',
                'employeeCount' => (int)($employeeCountsByDepartmentId[$deptId] ?? 0),
            ];
        }

        return $this->render('index', [
            'cards' => $cards,
            'userName' => $this->userDisplayName(),
        ]);
    }

    public function actionView($id)
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->view->params['bodyClass'] = 'bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen';

        $department = Department::find()
            ->with(['manager'])
            ->where(['id' => (int)$id])
            ->one();

        if ($department === null) {
            throw new NotFoundHttpException('Department not found.');
        }

        $hasStatus = Department::hasColumn('status');
        $hasManager = Department::hasColumn('manager_id');

        $members = $this->buildDepartmentMembersList($department, $hasManager);
        $employeeCount = count($members);

        $departmentData = [
            'id' => (int)$department->id,
            'name' => (string)($department->name ?? ''),
            'managerName' => ($hasManager && $department->manager) ? (string)$department->manager->full_name : '',
            'status' => $hasStatus ? (string)($department->status ?? '') : '',
            'employeeCount' => $employeeCount,
        ];

        return $this->render('view', [
            'department' => $departmentData,
            'members' => $members,
            'userName' => $this->userDisplayName(),
        ]);
    }

    public function actionUpdate($id)
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->view->params['bodyClass'] = 'bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen';

        $model = Department::find()->where(['id' => (int)$id])->one();
        if ($model === null) {
            throw new NotFoundHttpException('Department not found.');
        }

        $employees = $this->findActiveEmployeesForDepartmentHead();

        $hasManager = Department::hasColumn('manager_id');
        $hasStatus = Department::hasColumn('status');

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());

            if ($hasStatus && $model->hasAttribute('status') && !$model->status) {
                $model->status = 'active';
            }

            if ($model->save()) {
                if ($hasManager && Employee::hasColumn('manager_id') && Employee::hasColumn('department_id')) {
                    Employee::updateAll(
                        ['manager_id' => (int)$model->manager_id],
                        ['department_id' => (int)$model->id]
                    );
                }
                Yii::$app->session->setFlash('success', 'Department updated.');
                return $this->redirect(['/hr/department/index']);
            }
        }

        $successMessage = (string)Yii::$app->session->getFlash('success', '');
        $successVisibleClass = $successMessage !== '' ? '' : 'hidden';

        return $this->render('update', [
            'model' => $model,
            'employees' => $employees,
            'hasManager' => $hasManager,
            'hasStatus' => $hasStatus,
            'userName' => $this->userDisplayName(),
            'successMessage' => $successMessage,
            'successVisibleClass' => $successVisibleClass,
        ]);
    }

    public function actionCreate()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->view->params['bodyClass'] = 'bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen';

        $model = new Department();
        $employees = $this->findActiveEmployeesForDepartmentHead();

        $hasManager = Department::hasColumn('manager_id');
        $hasStatus = Department::hasColumn('status');

        if ($hasStatus && $model->hasAttribute('status') && !$model->status) {
            $model->status = 'active';
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Department created successfully');
            return $this->redirect(['/hr/hr/employees']);
        }

        $successMessage = (string)Yii::$app->session->getFlash('success', '');
        $successVisibleClass = $successMessage !== '' ? '' : 'hidden';

        return $this->render('create', [
            'model' => $model,
            'employees' => $employees,
            'hasManager' => $hasManager,
            'hasStatus' => $hasStatus,
            'userName' => $this->userDisplayName(),
            'successMessage' => $successMessage,
            'successVisibleClass' => $successVisibleClass,
        ]);
    }

    /**
     * Only active employees may be assigned as department head.
     *
     * @return Employee[]
     */
    private function findActiveEmployeesForDepartmentHead(): array
    {
        $query = Employee::find()->orderBy(['full_name' => SORT_ASC]);
        if (Employee::hasColumn('status')) {
            $query->andWhere(['status' => 'Active']);
        }

        return $query->all();
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

    /**
     * All people in the department roster, including the department head even if not assigned via department_id.
     *
     * @return array<int, array{
     *     id:int,
     *     full_name:string,
     *     job_title:string,
     *     email:string,
     *     joining_date:string,
     *     status_badge_class:string,
     *     status_badge_label:string,
     *     is_department_head:bool,
     *     is_managed_hr:bool
     * }>
     */
    private function buildDepartmentMembersList(Department $department, bool $hasManager): array
    {
        if (!Employee::hasColumn('department_id')) {
            return [];
        }

        $managerId = ($hasManager && $department->manager_id) ? (int)$department->manager_id : 0;
        $hasJoiningDate = Employee::hasColumn('joining_date');

        /** @var Employee[] $byId */
        $byId = [];
        $employees = Employee::find()
            ->where(['department_id' => (int)$department->id])
            ->orderBy(['full_name' => SORT_ASC])
            ->all();

        foreach ($employees as $employee) {
            $byId[(int)$employee->id] = $employee;
        }

        if ($managerId > 0 && !isset($byId[$managerId])) {
            $head = Employee::find()->where(['id' => $managerId])->one();
            if ($head !== null) {
                $byId[$managerId] = $head;
            }
        }

        if ($byId === []) {
            return [];
        }

        $members = [];
        foreach ($byId as $employee) {
            $empId = (int)$employee->id;
            $isHead = $managerId > 0 && $empId === $managerId;
            $status = method_exists($employee, 'getDisplayStatus')
                ? (string)$employee->getDisplayStatus()
                : (string)($employee->status ?? 'Active');
            $badge = $this->employeeStatusBadge($status);

            $members[] = [
                'id' => $empId,
                'full_name' => (string)$employee->full_name,
                'job_title' => trim((string)($employee->job_title ?? '')) !== '' ? (string)$employee->job_title : '—',
                'email' => trim((string)($employee->email ?? '')) !== '' ? (string)$employee->email : '—',
                'joining_date' => $hasJoiningDate && $employee->joining_date ? (string)$employee->joining_date : '—',
                'status_badge_class' => $badge['bg'],
                'status_badge_label' => $badge['label'],
                'is_department_head' => $isHead,
                'is_managed_hr' => $employee->isManagedHrEmployee(),
            ];
        }

        usort($members, static function (array $a, array $b): int {
            if ($a['is_department_head'] !== $b['is_department_head']) {
                return $b['is_department_head'] <=> $a['is_department_head'];
            }

            return strcasecmp($a['full_name'], $b['full_name']);
        });

        return $members;
    }

    /** @return array{bg: string, label: string} */
    private function employeeStatusBadge(string $status): array
    {
        $s = strtolower(trim($status));
        if ($s === 'active') {
            return ['bg' => 'bg-emerald-100 text-emerald-700', 'label' => 'Active'];
        }
        if ($s === 'onboarding') {
            return ['bg' => 'bg-amber-100 text-amber-700', 'label' => 'Onboarding'];
        }
        if ($s === 'leave') {
            return ['bg' => 'bg-slate-100 text-slate-700', 'label' => 'Leave'];
        }
        if ($s === 'terminated') {
            return ['bg' => 'bg-red-100 text-red-700', 'label' => 'Terminated'];
        }

        $label = trim($status) !== '' ? $status : 'Active';

        return ['bg' => 'bg-slate-100 text-slate-700', 'label' => $label];
    }
}

