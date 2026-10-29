<?php

namespace common\modules\employee\controllers;

use common\modules\employee\models\Department;
use common\modules\employee\models\Employee;
use common\modules\employee\models\EmployeeChangeRequest;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EmployeeController extends Controller
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
                    'delete' => ['post'],
                    'set-manager' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $this->requireHrAdmin();

        $request = Yii::$app->request;
        $search = trim((string)$request->get('search', ''));
        $dept = (int)$request->get('dept', 0);
        $pos = trim((string)$request->get('pos', 'all'));

        $query = Employee::find()
            ->alias('e')
            ->joinWith(['department d', 'manager m'])
            ->orderBy(['e.id' => SORT_DESC]);

        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'e.full_name', $search],
                ['like', 'e.email', $search],
                ['like', 'e.job_title', $search],
            ]);
        }

        if ($dept > 0) {
            $query->andWhere(['e.department_id' => $dept]);
        }

        if ($pos !== '' && $pos !== 'all') {
            $query->andWhere(['e.position_level' => $pos]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'departments' => Department::find()->orderBy(['name' => SORT_ASC])->all(),
            'search' => $search,
            'dept' => $dept,
            'pos' => $pos,
            'hasStatus' => Employee::hasColumn('status'),
            'hasIsManager' => Employee::hasColumn('is_manager'),
        ]);
    }

    public function actionDashboard()
    {
        $employee = $this->findCurrentEmployee();
        $departmentId = (int)$employee->department_id;

        $pendingSurveys = $this->countPendingAssignments(
            'surveys',
            'survey_employee_targets',
            'survey_targets',
            'survey_responses',
            'survey_id',
            $employee,
            $departmentId
        );
        $pendingEvaluations = $this->countPendingAssignments(
            'evaluations',
            'evaluation_employee_targets',
            'evaluation_targets',
            'evaluation_responses',
            'evaluation_id',
            $employee,
            $departmentId
        );

        $pendingTasks = $pendingSurveys + $pendingEvaluations;

        return $this->render('dashboard', [
            'employee' => $employee,
            'pendingSurveys' => $pendingSurveys,
            'pendingEvaluations' => $pendingEvaluations,
            'pendingTasks' => $pendingTasks,
        ]);
    }

    public function actionView($id = null)
    {
        $employee = $id === null ? $this->findCurrentEmployee() : $this->findModel((int)$id);

        $currentEmployee = $this->getEmployeeOrNull();
        if ($id !== null && (!$currentEmployee || (int)$employee->id !== (int)$currentEmployee->id)) {
            $this->requireHrAdmin();
        }

        $profile = [
            'phone' => $employee->hasAttribute('phone') && $employee->phone ? $employee->phone : '—',
            'maritalStatus' => $employee->getDisplayMaritalStatus(),
            'positionLevel' => $employee->getDisplayPositionLevel(),
            'joiningDate' => $employee->hasAttribute('joining_date') && $employee->joining_date ? $employee->joining_date : '—',
            'employmentType' => $employee->getDisplayEmploymentType(),
            'location' => $employee->hasAttribute('location') && $employee->location ? $employee->location : '—',
        ];

        return $this->render('view', [
            'employee' => $employee,
            'profile' => $profile,
        ]);
    }

    public function actionUpdate($id = null)
    {
        $this->requireHrAdmin();

        $employee = $id === null ? new Employee() : $this->findModel((int)$id);
        $isNewRecord = $employee->isNewRecord;

        if ($employee->load(Yii::$app->request->post())) {
            if ($isNewRecord && !$employee->plainPassword) {
                $employee->addError('plainPassword', 'Password cannot be blank.');
            }

            if (!$employee->hasErrors() && $employee->save()) {
                Yii::$app->session->setFlash('success', 'Employee saved.');
                return $this->redirect(['view', 'id' => $employee->id]);
            }
        }

        return $this->render('update', [
            'employee' => $employee,
            'departments' => ArrayHelper::map(Department::find()->orderBy(['name' => SORT_ASC])->all(), 'id', 'name'),
            'managers' => ArrayHelper::map(Employee::find()->orderBy(['full_name' => SORT_ASC])->all(), 'id', 'full_name'),
            'hasStatus' => Employee::hasColumn('status'),
            'hasIsManager' => Employee::hasColumn('is_manager'),
        ]);
    }

    public function actionDelete($id)
    {
        $this->requireHrAdmin();

        $employee = $this->findModel((int)$id);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Employee::updateAll(['manager_id' => null], ['manager_id' => $employee->id]);
            $employee->delete();
            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Employee deleted.');
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Unable to delete employee.');
            Yii::error($e->getMessage(), __METHOD__);
        }

        return $this->redirect(['index']);
    }

    public function actionRequestChange()
    {
        $employee = $this->findCurrentEmployee();
        $model = new EmployeeChangeRequest([
            'employee_id' => $employee->id,
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Request submitted successfully.');
            return $this->refresh();
        }

        return $this->render('request-change', [
            'employee' => $employee,
            'model' => $model,
        ]);
    }

    public function actionSetManager($id)
    {
        $this->requireHrAdmin();

        $employee = $this->findModel((int)$id);
        if ($employee->hasAttribute('is_manager')) {
            $employee->is_manager = 1;
        } elseif (stripos((string)$employee->job_title, 'manager') === false) {
            $employee->job_title = trim($employee->job_title . ' (manager)');
        }

        if ($employee->save(false)) {
            Yii::$app->session->setFlash('success', 'Employee marked as manager.');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    protected function findModel($id)
    {
        $model = Employee::find()->with(['department', 'manager'])->where(['id' => $id])->one();
        if ($model === null) {
            throw new NotFoundHttpException('Employee not found.');
        }

        return $model;
    }

    protected function findCurrentEmployee()
    {
        if (Yii::$app->user->isGuest) {
            Yii::$app->response->format = Response::FORMAT_HTML;
            throw new ForbiddenHttpException('Login is required.');
        }

        $employee = $this->getEmployeeOrNull();
        if ($employee === null) {
            throw new ForbiddenHttpException('Employee profile required.');
        }

        return $employee;
    }

    protected function getEmployeeOrNull()
    {
        if (Yii::$app->user->isGuest) {
            return null;
        }

        $user = Yii::$app->user->identity;
        if ($user === null) {
            return null;
        }

        $query = Employee::find()->with(['department', 'manager']);
        if (Employee::hasColumn('user_id')) {
            return $query->where(['user_id' => $user->id])->one();
        }

        return $query->where(['email' => $user->email])->one();
    }

    protected function requireHrAdmin()
    {
        $user = Yii::$app->user->identity;
        if ($user !== null && $user->role === 'admin') {
            return;
        }

        $employee = $this->findCurrentEmployee();
        if (!$employee->hasAttribute('is_hr_admin') || !(int)$employee->is_hr_admin) {
            throw new ForbiddenHttpException('HR administrator access is required.');
        }
    }

    protected function tableExists($table)
    {
        return Yii::$app->db->schema->getTableSchema($table, true) !== null;
    }

    protected function countRows($table, $where = [])
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

    protected function countPendingAssignments($mainTable, $employeeTargetTable, $departmentTargetTable, $responseTable, $foreignKey, Employee $employee, $departmentId)
    {
        if (!$this->tableExists($mainTable) || !$this->tableExists($employeeTargetTable) || !$this->tableExists($departmentTargetTable) || !$this->tableExists($responseTable)) {
            return 0;
        }

        $employeeId = (int)$employee->id;

        return (int)$this->assignmentQuery($mainTable, $employeeTargetTable, $departmentTargetTable, $foreignKey, $employee, $departmentId)
            ->andWhere([
                'not exists',
                (new Query())
                    ->select(new Expression('1'))
                    ->from([$responseTable])
                    ->where(["$responseTable.$foreignKey" => new \yii\db\Expression("$mainTable.id")])
                    ->andWhere(["$responseTable.employee_id" => $employeeId]),
            ])
            ->count('*', Yii::$app->db);
    }

    protected function countAssigned($mainTable, $employeeTargetTable, $departmentTargetTable, $foreignKey, Employee $employee, $departmentId)
    {
        if (!$this->tableExists($mainTable) || !$this->tableExists($employeeTargetTable) || !$this->tableExists($departmentTargetTable)) {
            return 0;
        }

        return (int)$this->assignmentQuery($mainTable, $employeeTargetTable, $departmentTargetTable, $foreignKey, $employee, $departmentId)
            ->count('*', Yii::$app->db);
    }

    protected function assignmentQuery($mainTable, $employeeTargetTable, $departmentTargetTable, $foreignKey, Employee $employee, $departmentId)
    {
        $employeeId = (int)$employee->id;
        $employeeTargetExists = (new Query())
            ->select(new Expression('1'))
            ->from([$employeeTargetTable])
            ->where(["$employeeTargetTable.$foreignKey" => new Expression("$mainTable.id")]);

        $departmentTargetExists = (new Query())
            ->select(new Expression('1'))
            ->from([$departmentTargetTable])
            ->where(["$departmentTargetTable.$foreignKey" => new Expression("$mainTable.id")]);

        $conditions = [
            ['exists', (clone $employeeTargetExists)->andWhere(["$employeeTargetTable.employee_id" => $employeeId])],
            [
                'and',
                ['not exists', $departmentTargetExists],
                ['not exists', $employeeTargetExists],
            ],
        ];

        if ($departmentId > 0) {
            $conditions[] = [
                'exists',
                (clone $departmentTargetExists)->andWhere(["$departmentTargetTable.department_id" => $departmentId]),
            ];
        }

        if (Department::hasColumn('manager_id')) {
            $managedDeptMatch = (new Query())
                ->select(new Expression('1'))
                ->from(["st" => $departmentTargetTable])
                ->innerJoin(['d' => Department::tableName()], 'd.id = st.department_id')
                ->where("st.$foreignKey = $mainTable.id")
                ->andWhere(['d.manager_id' => $employeeId]);
            $conditions[] = ['exists', $managedDeptMatch];
        }

        $query = (new Query())
            ->from($mainTable)
            ->where(array_merge(['or'], $conditions));

        \common\helpers\EmployeeEngagementFilter::applySentAfterActiveToQuery(
            $query,
            $employee,
            $mainTable,
            $employeeTargetTable,
            $foreignKey
        );

        return $query;
    }
}
