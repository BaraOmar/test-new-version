<?php

namespace frontend\modules\hr\controllers;

use common\modules\employee\models\Employee;
use common\modules\employee\models\EmployeeSchoolSubject;
use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolSectionMaterial;
use common\modules\employee\models\SchoolStudent;
use common\modules\employee\models\SchoolSubject;
use common\modules\employee\models\SchoolSubjectGrade;
use common\traits\HrAccessTrait;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SchoolDepartmentController extends Controller
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
                    'delete-grade' => ['POST'],
                    'delete-section' => ['POST'],
                    'delete-subject' => ['POST'],
                    'add-grade' => ['POST'],
                    'add-section' => ['POST'],
                    'add-subject' => ['POST'],
                    'update-section' => ['POST'],
                    'assign-class-teacher' => ['POST'],
                    'assign-material' => ['POST'],
                    'update-material' => ['POST'],
                    'delete-material' => ['POST'],
                    'assign-student' => ['POST'],
                    'remove-student' => ['POST'],
                    'save-section-details' => ['POST'],
                    'delete-subject-grade' => ['POST'],
                ],
            ],
        ];
    }

    public function actionHierarchy()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();
        $this->view->params['bodyClass'] = 'bg-gray-50 text-slate-900 min-h-screen';

        $search = trim((string)Yii::$app->request->get('search', ''));
        \common\modules\employee\services\SchoolDepartmentLeadershipService::reconcileStaleLeadership();
        $departments = $this->loadHierarchyDepartments($search);

        return $this->render('hierarchy', [
            'departments' => $departments,
            'search' => $search,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'school_department_hierarchy',
        ]);
    }

    public function actionIndex()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();
        $this->view->params['bodyClass'] = 'bg-gray-50 text-slate-900 min-h-screen';

        return $this->render('hub', [
            'userName' => $this->userDisplayName(),
            'currentPage' => 'school_departments',
        ]);
    }

    public function actionSubjects()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();
        $this->view->params['bodyClass'] = 'bg-gray-50 text-slate-900 min-h-screen';

        $departmentFilter = (int)Yii::$app->request->get('department_id', 0);
        $gradeFilter = (int)Yii::$app->request->get('grade_id', 0);
        $departments = SchoolDepartment::find()->orderBy(['name' => SORT_ASC])->all();
        $gradesByDepartment = $this->loadGradesByDepartment();

        if ($departmentFilter > 0 && $gradeFilter > 0) {
            $allowedGradeIds = array_map(
                static fn(array $grade) => (int)$grade['id'],
                $gradesByDepartment[$departmentFilter] ?? []
            );
            if (!in_array($gradeFilter, $allowedGradeIds, true)) {
                $gradeFilter = 0;
            }
        }

        $grades = $departmentFilter > 0
            ? SchoolGrade::find()->where(['school_department_id' => $departmentFilter])->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])->all()
            : SchoolGrade::find()->orderBy(['name' => SORT_ASC])->all();

        $rows = [];
        $totalCount = 0;
        $pagination = new Pagination(['totalCount' => 0, 'pageSize' => 8, 'pageParam' => 'page']);

        if (SchoolSubjectGrade::isEnabled()) {
            $query = SchoolSubjectGrade::find()
                ->alias('sg')
                ->innerJoinWith(['subject subject', 'subject.department department', 'grade grade'])
                ->orderBy(['department.name' => SORT_ASC, 'grade.name' => SORT_ASC, 'subject.name' => SORT_ASC]);

            if ($departmentFilter > 0) {
                $query->andWhere(['subject.school_department_id' => $departmentFilter]);
            }
            if ($gradeFilter > 0) {
                $query->andWhere(['sg.school_grade_id' => $gradeFilter]);
            }

            $totalCount = (int)$query->count();
            $pagination = new Pagination([
                'totalCount' => $totalCount,
                'pageSize' => 8,
                'pageParam' => 'page',
            ]);
            $rows = $query->offset($pagination->offset)->limit($pagination->limit)->all();
        }

        return $this->render('subject-list', [
            'departments' => $departments,
            'grades' => $grades,
            'gradesByDepartment' => $gradesByDepartment,
            'rows' => $rows,
            'pagination' => $pagination,
            'totalCount' => $totalCount,
            'departmentFilter' => $departmentFilter,
            'gradeFilter' => $gradeFilter,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'subjects',
        ]);
    }

    public function actionSubjectIndex()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        return $this->redirect(['subjects']);
    }

    public function actionCreateSubject()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();
        $this->view->params['bodyClass'] = 'bg-gray-50 text-slate-900 min-h-screen';

        if (Yii::$app->request->isPost) {
            return $this->processCreateSubject();
        }

        return $this->render('create-subject', [
            'departments' => SchoolDepartment::find()->orderBy(['name' => SORT_ASC])->all(),
            'gradesByDepartment' => $this->loadGradesByDepartment(),
            'userName' => $this->userDisplayName(),
            'currentPage' => 'subjects',
        ]);
    }

    public function actionGradesByDepartment(int $department_id): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        Yii::$app->response->format = Response::FORMAT_JSON;

        $grades = SchoolGrade::find()
            ->where(['school_department_id' => $department_id])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        return $this->asJson(array_map(
            static fn(SchoolGrade $grade) => ['id' => (int)$grade->id, 'name' => (string)$grade->name],
            $grades
        ));
    }

    private function processCreateSubject(): Response
    {
        if (!SchoolSubjectGrade::isEnabled()) {
            Yii::$app->session->setFlash('error', 'Subject credits are not available. Run migrations first.');
            return $this->redirect(['create-subject']);
        }

        $departmentId = (int)Yii::$app->request->post('school_department_id', 0);
        $name = trim((string)Yii::$app->request->post('name', ''));
        $gradeCredits = (array)Yii::$app->request->post('grade_credits', []);
        $gradeMaxScores = (array)Yii::$app->request->post('grade_max_scores', []);
        $selectedGradeIds = array_values(array_unique(array_map('intval', (array)Yii::$app->request->post('grade_ids', []))));

        if ($departmentId <= 0 || $name === '') {
            Yii::$app->session->setFlash('error', 'Department and subject name are required.');
            return $this->redirect(['create-subject']);
        }

        if ($selectedGradeIds === []) {
            Yii::$app->session->setFlash('error', 'Select at least one grade.');
            return $this->redirect(['create-subject']);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $subject = SchoolSubject::findOne([
                'school_department_id' => $departmentId,
                'name' => $name,
            ]);

            if ($subject === null) {
                $subject = new SchoolSubject([
                    'school_department_id' => $departmentId,
                    'name' => $name,
                ]);

                if (!$subject->save()) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', implode(' ', $subject->getFirstErrors()) ?: 'Unable to create subject.');
                    return $this->redirect(['create-subject']);
                }
            }

            $result = $this->saveSubjectGradeAssignments($subject, $departmentId, $selectedGradeIds, $gradeCredits, $gradeMaxScores);
            if ($result['errors'] !== []) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', implode(' ', array_unique($result['errors'])));
                return $this->redirect(['create-subject']);
            }

            if ($result['savedCount'] === 0) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Select at least one grade.');
                return $this->redirect(['create-subject']);
            }

            $transaction->commit();
            Yii::$app->session->setFlash('success', $result['savedCount'] > 1
                ? 'Subject saved with ' . $result['savedCount'] . ' grades assigned.'
                : 'Subject saved.');
        } catch (\Throwable) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Unable to create subject.');
            return $this->redirect(['create-subject']);
        }

        return $this->redirect(['subjects']);
    }

    private function processUpdateSubject(SchoolSubject $subject): Response
    {
        if (!SchoolSubjectGrade::isEnabled()) {
            Yii::$app->session->setFlash('error', 'Subject credits are not available. Run migrations first.');
            return $this->redirect(['update-subject', 'id' => (int)$subject->id]);
        }

        $name = trim((string)Yii::$app->request->post('name', ''));
        $gradeCredits = (array)Yii::$app->request->post('grade_credits', []);
        $gradeMaxScores = (array)Yii::$app->request->post('grade_max_scores', []);
        $selectedGradeIds = array_values(array_unique(array_map('intval', (array)Yii::$app->request->post('grade_ids', []))));
        $departmentId = (int)$subject->school_department_id;

        if ($name === '') {
            Yii::$app->session->setFlash('error', 'Subject name is required.');
            return $this->redirect(['update-subject', 'id' => (int)$subject->id]);
        }

        if ($selectedGradeIds === []) {
            Yii::$app->session->setFlash('error', 'Select at least one grade.');
            return $this->redirect(['update-subject', 'id' => (int)$subject->id]);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $subject->name = $name;
            if (!$subject->save()) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', implode(' ', $subject->getFirstErrors()) ?: 'Unable to update subject.');
                return $this->redirect(['update-subject', 'id' => (int)$subject->id]);
            }

            foreach (SchoolSubjectGrade::find()->where(['school_subject_id' => (int)$subject->id])->all() as $existingGrade) {
                if (!in_array((int)$existingGrade->school_grade_id, $selectedGradeIds, true)) {
                    $existingGrade->delete();
                }
            }

            $result = $this->saveSubjectGradeAssignments($subject, $departmentId, $selectedGradeIds, $gradeCredits, $gradeMaxScores);
            if ($result['errors'] !== []) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', implode(' ', array_unique($result['errors'])));
                return $this->redirect(['update-subject', 'id' => (int)$subject->id]);
            }

            if ($result['savedCount'] === 0) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Select at least one grade.');
                return $this->redirect(['update-subject', 'id' => (int)$subject->id]);
            }

            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Subject updated.');
        } catch (\Throwable) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Unable to update subject.');
            return $this->redirect(['update-subject', 'id' => (int)$subject->id]);
        }

        return $this->redirect(['view-subject', 'id' => (int)$subject->id]);
    }

    /**
     * @param int[] $selectedGradeIds
     * @param array<int|string, mixed> $gradeCredits
     * @return array{savedCount:int,errors:string[]}
     */
    private function saveSubjectGradeAssignments(
        SchoolSubject $subject,
        int $departmentId,
        array $selectedGradeIds,
        array $gradeCredits,
        array $gradeMaxScores = []
    ): array {
        $errors = [];
        $savedCount = 0;

        foreach ($selectedGradeIds as $gradeId) {
            if ($gradeId <= 0) {
                continue;
            }

            $grade = SchoolGrade::findOne(['id' => $gradeId, 'school_department_id' => $departmentId]);
            if ($grade === null) {
                $errors[] = 'One or more selected grades are invalid for this department.';
                continue;
            }

            $subjectGrade = SchoolSubjectGrade::findOne([
                'school_subject_id' => (int)$subject->id,
                'school_grade_id' => $gradeId,
            ]) ?? new SchoolSubjectGrade([
                'school_subject_id' => (int)$subject->id,
                'school_grade_id' => $gradeId,
            ]);

            $credits = trim((string)($gradeCredits[$gradeId] ?? $gradeCredits[(string)$gradeId] ?? ''));
            if ($credits === '' || !is_numeric($credits) || (float)$credits <= 0) {
                $subjectGrade->credits = \common\modules\employee\services\SchoolSubjectManagementService::DEFAULT_SUBJECT_CREDITS;
            } else {
                $subjectGrade->credits = (float)$credits;
            }

            if (SchoolSubjectGrade::hasColumn('max_score')) {
                $maxScoreRaw = trim((string)($gradeMaxScores[$gradeId] ?? $gradeMaxScores[(string)$gradeId] ?? ''));
                if ($maxScoreRaw === '') {
                    $errors[] = 'Enter a maximum mark for each selected grade.';
                    continue;
                }
                if (!is_numeric($maxScoreRaw) || (float)$maxScoreRaw <= 0) {
                    $errors[] = 'Enter a valid maximum mark for each selected grade.';
                    continue;
                }
                $subjectGrade->max_score = (float)$maxScoreRaw;
            }

            if (!$subjectGrade->save()) {
                $errors[] = implode(' ', $subjectGrade->getFirstErrors()) ?: 'Unable to save grade assignment.';
                continue;
            }

            $savedCount++;
        }

        return [
            'savedCount' => $savedCount,
            'errors' => $errors,
        ];
    }

    private function findSubject(int $id): SchoolSubject
    {
        if (!SchoolSubject::isEnabled()) {
            throw new NotFoundHttpException('Subject not found.');
        }

        $subject = SchoolSubject::find()
            ->where(['id' => $id])
            ->with(['department', 'subjectGrades.grade'])
            ->one();

        if ($subject === null) {
            throw new NotFoundHttpException('Subject not found.');
        }

        return $subject;
    }

    public function actionDeleteSubjectGrade(int $id): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $row = SchoolSubjectGrade::findOne(['id' => $id]);
        if ($row !== null) {
            $subjectId = (int)$row->school_subject_id;
            $row->delete();
            if (!SchoolSubjectGrade::find()->where(['school_subject_id' => $subjectId])->exists()) {
                SchoolSubject::findOne(['id' => $subjectId])?->delete();
            }
            Yii::$app->session->setFlash('success', 'Subject entry removed.');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['subjects']);
    }

    public function actionViewSubject(int $id)
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();
        $this->view->params['bodyClass'] = 'bg-gray-50 text-slate-900 min-h-screen';

        $subject = $this->findSubject($id);

        return $this->render('view-subject', [
            'subject' => $subject,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'subjects',
        ]);
    }

    public function actionUpdateSubject(int $id)
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();
        $this->view->params['bodyClass'] = 'bg-gray-50 text-slate-900 min-h-screen';

        $subject = $this->findSubject($id);

        if (Yii::$app->request->isPost) {
            return $this->processUpdateSubject($subject);
        }

        $assignedMaxScores = [];
        $assignedGradeIds = [];
        foreach ($subject->subjectGrades as $subjectGrade) {
            $gradeId = (int)$subjectGrade->school_grade_id;
            $assignedGradeIds[] = $gradeId;
            if (SchoolSubjectGrade::hasColumn('max_score')) {
                $assignedMaxScores[$gradeId] = (float)($subjectGrade->max_score ?? 100);
            }
        }

        return $this->render('update-subject', [
            'subject' => $subject,
            'gradesByDepartment' => $this->loadGradesByDepartment(),
            'assignedGradeIds' => $assignedGradeIds,
            'assignedMaxScores' => $assignedMaxScores,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'subjects',
        ]);
    }

    public function actionCreate()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();
        $this->view->params['bodyClass'] = 'bg-gray-50 text-slate-900 min-h-screen';

        $model = new SchoolDepartment();
        $leaders = [];
        $availableLeaders = [];
        $departmentGrades = [''];
        $subjectsDraft = [['name' => '', 'grade_ids' => [], 'grade_credits' => [], 'grade_max_scores' => []]];

        if ($model->load(Yii::$app->request->post())) {
            $departmentGrades = $this->parseDepartmentCreateGradeNames(Yii::$app->request->post('department_grades', []));
            $subjectsDraft = $this->parseDepartmentCreateSubjectsDraft((array)Yii::$app->request->post('subjects', []));

            if ($departmentGrades === []) {
                $departmentGrades = [''];
            }
            if ($subjectsDraft === []) {
                $subjectsDraft = [['name' => '', 'grade_ids' => [], 'grade_credits' => [], 'grade_max_scores' => []]];
            }

            $result = $this->processDepartmentCreateWithSubjects($model, $departmentGrades, $subjectsDraft);
            if ($result instanceof Response) {
                return $result;
            }
        }

        return $this->render('create', [
            'model' => $model,
            'leaders' => $leaders,
            'availableLeaders' => $availableLeaders,
            'departmentGrades' => $departmentGrades,
            'subjectsDraft' => $subjectsDraft,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'school_department_create',
        ]);
    }

    /**
     * @param mixed $rawGrades
     * @return string[]
     */
    private function parseDepartmentCreateGradeNames($rawGrades): array
    {
        $gradeNames = [];
        foreach ((array)$rawGrades as $gradeName) {
            $gradeName = trim((string)$gradeName);
            if ($gradeName !== '') {
                $gradeNames[] = $gradeName;
            }
        }

        return $gradeNames;
    }

    /**
     * @param array<int|string, mixed> $rawSubjects
     * @return array<int, array{name:string,grade_ids:int[],grade_credits:array<int|string,string>,grade_max_scores:array<int|string,string>}>
     */
    private function parseDepartmentCreateSubjectsDraft(array $rawSubjects): array
    {
        $subjectsDraft = [];
        foreach ($rawSubjects as $subjectRow) {
            if (!is_array($subjectRow)) {
                continue;
            }

            $subjectsDraft[] = [
                'name' => trim((string)($subjectRow['name'] ?? '')),
                'grade_ids' => array_values(array_unique(array_map('intval', (array)($subjectRow['grade_ids'] ?? [])))),
                'grade_credits' => (array)($subjectRow['grade_credits'] ?? []),
                'grade_max_scores' => (array)($subjectRow['grade_max_scores'] ?? []),
            ];
        }

        return $subjectsDraft;
    }

    /**
     * @param string[] $departmentGrades
     * @param array<int, array{name:string,grade_ids:int[],grade_credits:array<int|string,string>,grade_max_scores:array<int|string,string>}> $subjectsDraft
     */
    private function processDepartmentCreateWithSubjects(
        SchoolDepartment $model,
        array $departmentGrades,
        array $subjectsDraft
    ): ?Response {
        if (!SchoolSubjectGrade::isEnabled()) {
            Yii::$app->session->setFlash('error', 'Subject credits are not available. Run migrations first.');
            return null;
        }

        if ($departmentGrades === []) {
            Yii::$app->session->setFlash('error', 'Add at least one grade for this department.');
            return null;
        }

        if ($subjectsDraft === []) {
            Yii::$app->session->setFlash('error', 'Add at least one subject for this department.');
            return null;
        }

        $gradeCount = count($departmentGrades);
        $normalizedSubjects = [];
        foreach ($subjectsDraft as $subjectRow) {
            $name = trim((string)($subjectRow['name'] ?? ''));
            if ($name === '') {
                Yii::$app->session->setFlash('error', 'Enter a name for each subject.');
                return null;
            }

            $selectedGradeIds = array_values(array_unique(array_map('intval', (array)($subjectRow['grade_ids'] ?? []))));
            if ($selectedGradeIds === []) {
                Yii::$app->session->setFlash('error', 'Select at least one grade for each subject.');
                return null;
            }

            foreach ($selectedGradeIds as $gradeIndex) {
                if ($gradeIndex < 0 || $gradeIndex >= $gradeCount) {
                    Yii::$app->session->setFlash('error', 'One or more selected grades are invalid for this department.');
                    return null;
                }
            }

            $normalizedSubjects[] = [
                'name' => $name,
                'grade_ids' => $selectedGradeIds,
                'grade_credits' => (array)($subjectRow['grade_credits'] ?? []),
                'grade_max_scores' => (array)($subjectRow['grade_max_scores'] ?? []),
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model->head_id = null;
            $model->deputy_head_id = null;
            if (!$model->save()) {
                $transaction->rollBack();
                return null;
            }

            $gradeIdsByIndex = [];
            foreach ($departmentGrades as $sortOrder => $gradeName) {
                $grade = new SchoolGrade([
                    'school_department_id' => (int)$model->id,
                    'name' => $gradeName,
                    'sort_order' => (int)$sortOrder,
                ]);
                if (!$grade->save()) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', implode(' ', $grade->getFirstErrors()) ?: 'Unable to create grade.');
                    return null;
                }
                $gradeIdsByIndex[(int)$sortOrder] = (int)$grade->id;
            }

            $departmentId = (int)$model->id;
            $createdSubjectCount = 0;
            foreach ($normalizedSubjects as $subjectRow) {
                $subject = new SchoolSubject([
                    'school_department_id' => $departmentId,
                    'name' => $subjectRow['name'],
                ]);
                if (!$subject->save()) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', implode(' ', $subject->getFirstErrors()) ?: 'Unable to create subject.');
                    return null;
                }

                $selectedRealGradeIds = [];
                $gradeCredits = [];
                $gradeMaxScores = [];
                foreach ($subjectRow['grade_ids'] as $gradeIndex) {
                    $realGradeId = $gradeIdsByIndex[$gradeIndex] ?? 0;
                    if ($realGradeId <= 0) {
                        continue;
                    }
                    $selectedRealGradeIds[] = $realGradeId;
                    $gradeCredits[$realGradeId] = $subjectRow['grade_credits'][$gradeIndex]
                        ?? $subjectRow['grade_credits'][(string)$gradeIndex]
                        ?? '';
                    $gradeMaxScores[$realGradeId] = $subjectRow['grade_max_scores'][$gradeIndex]
                        ?? $subjectRow['grade_max_scores'][(string)$gradeIndex]
                        ?? '';
                }

                $result = $this->saveSubjectGradeAssignments(
                    $subject,
                    $departmentId,
                    $selectedRealGradeIds,
                    $gradeCredits,
                    $gradeMaxScores
                );
                if ($result['errors'] !== []) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', implode(' ', array_unique($result['errors'])));
                    return null;
                }
                if ($result['savedCount'] === 0) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'Select at least one grade for each subject.');
                    return null;
                }

                $createdSubjectCount++;
            }

            $transaction->commit();
            Yii::$app->session->setFlash(
                'success',
                'School department created with ' . count($departmentGrades) . ' grade(s) and '
                . $createdSubjectCount . ' subject(s). Assign leadership after teachers are added to this department.'
            );

            return $this->redirect(['update', 'id' => $departmentId]);
        } catch (\Throwable) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Unable to create school department.');
            return null;
        }
    }

    public function actionUpdate(int $id)
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();
        $this->view->params['bodyClass'] = 'bg-gray-50 text-slate-900 min-h-screen';

        $model = $this->findModel($id);
        $leaders = $this->findLeadersForSchoolDepartment((int)$model->id);
        $availableLeaders = $this->filterAvailableLeadershipCandidates($leaders, (int)$model->id, $model);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'School department updated successfully.');
            return $this->redirect(['hierarchy']);
        }

        return $this->render('update', [
            'model' => $model,
            'leaders' => $leaders,
            'availableLeaders' => $availableLeaders,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'school_department_hierarchy',
        ]);
    }

    public function actionView(int $id)
    {
        return $this->redirect(['update', 'id' => $id]);
    }

    public function actionSection(int $id)
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();
        $this->view->params['bodyClass'] = 'bg-slate-50 text-slate-900 min-h-screen';

        $section = $this->findSection($id);
        $department = $section->grade?->department;
        $departmentId = $department ? (int)$department->id : null;
        $assignedInstructorIds = [];
        if ($section->lead_teacher_id) {
            $assignedInstructorIds[] = (int)$section->lead_teacher_id;
        }
        foreach ($section->materials as $material) {
            if ($material->instructor_id) {
                $assignedInstructorIds[] = (int)$material->instructor_id;
            }
        }
        $teachers = $this->findTeachersForSchoolDepartment($departmentId);
        $instructorTeachers = $this->mergeTeachersById($teachers, $assignedInstructorIds);
        $classTeachers = $this->findAvailableClassTeachers($departmentId, (int)$section->id, $assignedInstructorIds);
        $subjectCatalog = $this->findSubjectCatalogForSection($section);
        $instructorsBySubject = $this->buildInstructorsBySubject($departmentId, $teachers, $instructorTeachers);

        $rosterSearch = trim((string)Yii::$app->request->get('roster', ''));
        $studentQuery = SchoolStudent::find()->where(['school_section_id' => (int)$section->id]);
        if ($rosterSearch !== '' && SchoolStudent::isEnabled()) {
            $studentQuery->andWhere([
                'or',
                ['like', 'full_name', $rosterSearch],
                ['like', 'email', $rosterSearch],
                ['like', 'student_code', $rosterSearch],
            ]);
        }

        $studentPagination = new Pagination([
            'totalCount' => SchoolStudent::isEnabled() ? (int)$studentQuery->count() : 0,
            'pageSize' => 8,
            'pageParam' => 'page',
        ]);
        $students = SchoolStudent::isEnabled()
            ? $studentQuery->offset($studentPagination->offset)->limit($studentPagination->limit)->all()
            : [];

        return $this->render('section', [
            'section' => $section,
            'department' => $department,
            'teachers' => $teachers,
            'instructorTeachers' => $instructorTeachers,
            'classTeachers' => $classTeachers,
            'subjects' => $subjectCatalog,
            'instructorsBySubject' => $instructorsBySubject,
            'materials' => SchoolSectionMaterial::isEnabled() ? $section->materials : [],
            'students' => $students,
            'studentPagination' => $studentPagination,
            'rosterSearch' => $rosterSearch,
            'eligibleStudents' => $this->findEligibleStudentsForSection($section),
            'userName' => $this->userDisplayName(),
            'currentPage' => 'school_department_hierarchy',
            'isNewSection' => false,
        ]);
    }

    public function actionSectionPrint(int $id)
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();
        $this->layout = false;

        $section = $this->findSection($id);
        $department = $section->grade?->department;

        return $this->render('section-print', [
            'section' => $section,
            'department' => $department,
            'materials' => SchoolSectionMaterial::isEnabled() ? $section->materials : [],
            'students' => SchoolStudent::isEnabled() ? $section->students : [],
        ]);
    }

    public function actionUpdateSection(int $id): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $section = $this->findSection($id);
        if ($section->load(Yii::$app->request->post()) && $section->save()) {
            Yii::$app->session->setFlash('success', 'Section updated.');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $section->getFirstErrors()) ?: 'Unable to update section.');
        }

        return $this->redirect(['section', 'id' => (int)$section->id]);
    }

    public function actionAssignClassTeacher(int $id): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $section = $this->findSection($id);
        $teacherId = (int)Yii::$app->request->post('lead_teacher_id', 0);
        $section->lead_teacher_id = $teacherId > 0 ? $teacherId : null;
        if ($section->save()) {
            Yii::$app->session->setFlash('success', 'Class teacher updated.');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $section->getFirstErrors()) ?: 'Unable to update class teacher.');
        }

        return $this->redirect(['section', 'id' => (int)$section->id]);
    }

    public function actionAssignMaterial(int $id): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        if (!SchoolSectionMaterial::isEnabled()) {
            Yii::$app->session->setFlash('error', 'Materials are not available. Run migrations first.');
            return $this->redirect(['section', 'id' => $id]);
        }

        $section = $this->findSection($id);
        $model = new SchoolSectionMaterial(['school_section_id' => (int)$section->id]);
        if ($model->load(Yii::$app->request->post())) {
            $model->school_section_id = (int)$section->id;
            if ($model->school_subject_id) {
                $subject = SchoolSubject::findOne(['id' => (int)$model->school_subject_id]);
                if ($subject !== null) {
                    if (trim($model->name) === '') {
                        $model->name = (string)$subject->name;
                    }
                    $model->icon = $this->iconForSubjectName($subject->name);
                }
            }
            if (trim($model->name) === '' && !$model->school_subject_id) {
                $model->addError('name', 'Select a subject or enter a material title.');
            } elseif ($model->save()) {
                Yii::$app->session->setFlash('success', 'Material assigned.');
                return $this->redirect(['section', 'id' => (int)$section->id]);
            }
        }

        Yii::$app->session->setFlash('error', implode(' ', $model->getFirstErrors()) ?: 'Unable to assign material.');
        return $this->redirect(['section', 'id' => (int)$section->id]);
    }

    public function actionUpdateMaterial(int $id, int $materialId): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $section = $this->findSection($id);
        $model = SchoolSectionMaterial::findOne(['id' => $materialId, 'school_section_id' => (int)$section->id]);
        if ($model === null) {
            throw new NotFoundHttpException('Material not found.');
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Material updated.');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $model->getFirstErrors()) ?: 'Unable to update material.');
        }

        return $this->redirect(['section', 'id' => (int)$section->id]);
    }

    public function actionDeleteMaterial(int $id, int $materialId): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $model = SchoolSectionMaterial::findOne(['id' => $materialId, 'school_section_id' => $id]);
        if ($model !== null) {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Material removed.');
        }

        return $this->redirect(['section', 'id' => $id]);
    }

    public function actionAssignStudent(int $id): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        if (!SchoolStudent::isEnabled()) {
            Yii::$app->session->setFlash('error', 'Student roster is not available. Run migrations first.');
            return $this->redirect(['section', 'id' => $id]);
        }

        $section = $this->findSection($id);
        $studentId = (int)Yii::$app->request->post('student_id', 0);
        $errors = [];
        if ($studentId <= 0 || !$this->assignStudentToSection($section, $studentId, $errors)) {
            Yii::$app->session->setFlash('error', implode(' ', $errors) ?: 'Select a student to assign.');
            return $this->redirect(['section', 'id' => (int)$section->id]);
        }

        Yii::$app->session->setFlash('success', 'Student assigned to section.');
        return $this->redirect(['section', 'id' => (int)$section->id]);
    }

    public function actionRemoveStudent(int $id, int $studentId): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $student = SchoolStudent::findOne(['id' => $studentId, 'school_section_id' => $id]);
        if ($student !== null) {
            $student->school_section_id = null;
            if ($student->save(false, ['school_section_id', 'updated_at'])) {
                $section = SchoolSection::findOne($id);
                if ($section !== null) {
                    $section->current_enrollment = (int)SchoolStudent::find()
                        ->where(['school_section_id' => $id])
                        ->count();
                    $section->save(false, ['current_enrollment', 'updated_at']);
                }
                Yii::$app->session->setFlash('success', 'Student removed from section.');
            }
        }

        return $this->redirect(['section', 'id' => (int)$section->id]);
    }

    public function actionSaveSectionDetails(int $id): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $section = $this->findSection($id);
        $errors = [];
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $teacherId = (int)Yii::$app->request->post('lead_teacher_id', 0);
            $section->lead_teacher_id = $teacherId > 0 ? $teacherId : null;
            if (!$section->save()) {
                $errors[] = implode(' ', $section->getFirstErrors()) ?: 'Unable to update class teacher.';
            }

            if (SchoolSectionMaterial::isEnabled()) {
                foreach ((array)Yii::$app->request->post('updated_materials', []) as $materialId => $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $material = SchoolSectionMaterial::findOne([
                        'id' => (int)$materialId,
                        'school_section_id' => (int)$section->id,
                    ]);
                    if ($material === null) {
                        continue;
                    }
                    if (!$this->populateAndSaveMaterial($material, $row)) {
                        $errors[] = implode(' ', $material->getFirstErrors()) ?: 'Unable to update material.';
                    }
                }

                foreach ((array)Yii::$app->request->post('new_materials', []) as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $material = new SchoolSectionMaterial(['school_section_id' => (int)$section->id]);
                    if (!$this->populateAndSaveMaterial($material, $row, true)) {
                        $errors[] = implode(' ', $material->getFirstErrors()) ?: 'Unable to add material.';
                    }
                }
            }

            if (SchoolStudent::isEnabled()) {
                foreach ((array)Yii::$app->request->post('new_students', []) as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $studentId = (int)($row['student_id'] ?? 0);
                    if ($studentId <= 0) {
                        continue;
                    }
                    if (!$this->assignStudentToSection($section, $studentId, $errors)) {
                        continue;
                    }
                }
            }

            if ($errors !== []) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', implode(' ', array_unique($errors)));
            } else {
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Section details saved.');
            }
        } catch (\Throwable) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Unable to save section details.');
        }

        return $this->redirect(['section', 'id' => (int)$section->id]);
    }

    public function actionAddGrade(): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $model = new SchoolGrade();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Grade added.');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $model->getFirstErrors()) ?: 'Unable to add grade.');
        }

        return $this->redirect(['hierarchy', 'search' => Yii::$app->request->post('search', '')]);
    }

    public function actionCreateSection(int $grade_id)
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();
        $this->view->params['bodyClass'] = 'bg-slate-50 text-slate-900 min-h-screen';

        $grade = SchoolGrade::find()->with('department')->where(['id' => $grade_id])->one();
        if ($grade === null) {
            throw new NotFoundHttpException('Grade not found.');
        }

        $section = new SchoolSection([
            'school_grade_id' => (int)$grade->id,
            'capacity_max' => 30,
            'current_enrollment' => 0,
        ]);
        $section->populateRelation('grade', $grade);

        $department = $grade->department;
        $departmentId = $department ? (int)$department->id : null;
        $teachers = $this->findTeachersForSchoolDepartment($departmentId);
        $instructorTeachers = $teachers;
        $classTeachers = $this->findAvailableClassTeachers($departmentId, null);
        $subjectCatalog = $this->findSubjectCatalogForSection($section);
        $instructorsBySubject = $this->buildInstructorsBySubject($departmentId, $teachers, $instructorTeachers);

        if ($section->load(Yii::$app->request->post()) && $section->save()) {
            Yii::$app->session->setFlash('success', 'Section created. You can now assign materials and students.');
            return $this->redirect(['section', 'id' => (int)$section->id]);
        }

        $studentPagination = new Pagination(['totalCount' => 0, 'pageSize' => 8]);

        return $this->render('section', [
            'section' => $section,
            'department' => $department,
            'teachers' => $teachers,
            'instructorTeachers' => $instructorTeachers,
            'classTeachers' => $classTeachers,
            'subjects' => $subjectCatalog,
            'instructorsBySubject' => $instructorsBySubject,
            'materials' => [],
            'students' => [],
            'studentPagination' => $studentPagination,
            'rosterSearch' => '',
            'eligibleStudents' => [],
            'userName' => $this->userDisplayName(),
            'currentPage' => 'school_department_hierarchy',
            'isNewSection' => true,
        ]);
    }

    public function actionAddSection(): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $model = new SchoolSection();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Section added.');
            return $this->redirect(['section', 'id' => (int)$model->id]);
        }

        Yii::$app->session->setFlash('error', implode(' ', $model->getFirstErrors()) ?: 'Unable to add section.');
        return $this->redirect(['hierarchy', 'search' => Yii::$app->request->post('search', '')]);
    }

    public function actionAddSubject(): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $model = new SchoolSubject();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Subject added.');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $model->getFirstErrors()) ?: 'Unable to add subject.');
        }

        return $this->redirect(['hierarchy', 'search' => Yii::$app->request->post('search', '')]);
    }

    public function actionDeleteGrade(int $id): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $grade = SchoolGrade::findOne(['id' => $id]);
        if ($grade !== null) {
            $grade->delete();
            Yii::$app->session->setFlash('success', 'Grade deleted.');
        }

        return $this->redirect(['hierarchy']);
    }

    public function actionDeleteSection(int $id): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $section = SchoolSection::findOne(['id' => $id]);
        if ($section !== null) {
            $section->delete();
            Yii::$app->session->setFlash('success', 'Section deleted.');
        }

        return $this->redirect(['hierarchy']);
    }

    public function actionDeleteSubject(int $id): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $subject = SchoolSubject::findOne(['id' => $id]);
        if ($subject !== null) {
            $subject->delete();
            Yii::$app->session->setFlash('success', 'Subject deleted.');
        }

        return $this->redirect(['hierarchy']);
    }

    public function actionDelete(int $id): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireSchoolDepartmentsEnabled();

        $model = $this->findModel($id);
        $name = (string)$model->name;
        $model->delete();

        Yii::$app->session->setFlash('success', 'School department "' . $name . '" was deleted.');
        return $this->redirect(['hierarchy']);
    }

    /**
     * @return SchoolDepartment[]
     */
    private function loadHierarchyDepartments(string $search): array
    {
        $query = SchoolDepartment::find()
            ->with([
                'head',
                'grades.sections.leadTeacher',
            ])
            ->orderBy(['name' => SORT_ASC]);

        if ($search !== '') {
            $query->andWhere(['like', 'name', $search]);
        }

        return $query->all();
    }

    private function findModel(int $id): SchoolDepartment
    {
        $model = SchoolDepartment::findOne(['id' => $id]);
        if ($model === null) {
            throw new NotFoundHttpException('School department not found.');
        }

        return $model;
    }

    private function requireSchoolDepartmentsEnabled(): void
    {
        if (!SchoolDepartment::isEnabled()) {
            throw new NotFoundHttpException('School departments are not enabled. Run database migrations first.');
        }
    }

    /**
     * Teachers eligible for head/deputy roles in a specific school department.
     *
     * @return Employee[]
     */
    private function findLeadersForSchoolDepartment(?int $departmentId = null): array
    {
        if ($departmentId === null || $departmentId <= 0 || !Employee::hasColumn('school_department_id')) {
            return [];
        }

        $query = Employee::find()->orderBy(['full_name' => SORT_ASC]);
        if (Employee::hasColumn('status')) {
            $query->andWhere(['status' => 'Active']);
        }
        if (Employee::hasColumn('teacher')) {
            $query->andWhere(['teacher' => 1]);
        }

        return $query->andWhere(['school_department_id' => $departmentId])->all();
    }

    /**
     * @param Employee[] $leaders
     * @return Employee[]
     */
    private function filterAvailableLeadershipCandidates(
        array $leaders,
        ?int $exceptDepartmentId = null,
        ?SchoolDepartment $department = null
    ): array {
        $occupiedIds = array_flip(SchoolDepartment::occupiedLeadershipEmployeeIds(null, $exceptDepartmentId));
        $alwaysInclude = [];
        if ($department !== null) {
            if ($department->head_id) {
                $alwaysInclude[(int)$department->head_id] = true;
            }
            if ($department->deputy_head_id) {
                $alwaysInclude[(int)$department->deputy_head_id] = true;
            }
        }

        if ($occupiedIds === [] && $alwaysInclude === []) {
            return $leaders;
        }

        return array_values(array_filter($leaders, static function (Employee $employee) use ($occupiedIds, $alwaysInclude) {
            $employeeId = (int)$employee->id;

            return isset($alwaysInclude[$employeeId]) || !isset($occupiedIds[$employeeId]);
        }));
    }

    /**
     * @return Employee[]
     */
    private function findTeachersForSchoolDepartment(?int $departmentId, array $includeEmployeeIds = []): array
    {
        if ($departmentId === null || $departmentId <= 0) {
            return [];
        }

        $query = Employee::find()->orderBy(['full_name' => SORT_ASC]);
        if (Employee::hasColumn('status')) {
            $query->andWhere(['status' => 'Active']);
        }
        if (Employee::hasColumn('teacher')) {
            $query->andWhere(['teacher' => 1]);
        }

        if (!Employee::hasColumn('school_department_id')) {
            return [];
        }

        $teachers = $query->andWhere(['school_department_id' => $departmentId])->all();

        return $this->mergeTeachersById($teachers, $includeEmployeeIds);
    }

    /**
     * @param Employee[] $teachers
     * @param int[] $employeeIds
     * @return Employee[]
     */
    private function mergeTeachersById(array $teachers, array $employeeIds): array
    {
        $employeeIds = array_values(array_unique(array_filter(array_map('intval', $employeeIds))));
        if ($employeeIds === []) {
            return $teachers;
        }

        $existingIds = array_map(static fn(Employee $teacher) => (int)$teacher->id, $teachers);
        $missingIds = array_values(array_diff($employeeIds, $existingIds));
        if ($missingIds === []) {
            return $teachers;
        }

        $extra = Employee::find()
            ->where(['id' => $missingIds])
            ->orderBy(['full_name' => SORT_ASC])
            ->all();

        return array_values(array_merge($teachers, $extra));
    }

    /**
     * @param Employee[] $departmentTeachers
     * @param Employee[] $extraTeachers
     * @return array<int, array<int, array{id:int,label:string}>>
     */
    private function buildInstructorsBySubject(?int $departmentId, array $departmentTeachers, array $extraTeachers = []): array
    {
        if ($departmentId === null || $departmentId <= 0) {
            return [];
        }

        $formatLabel = static function (Employee $teacher): string {
            $title = trim((string)($teacher->job_title ?? ''));

            return $title !== '' ? $teacher->full_name . ' (' . $title . ')' : $teacher->full_name;
        };

        $teacherById = [];
        foreach (array_merge($departmentTeachers, $extraTeachers) as $teacher) {
            $teacherById[(int)$teacher->id] = $teacher;
        }

        $instructorsBySubject = [];
        $appendInstructor = static function (int $subjectId, int $employeeId, string $label) use (&$instructorsBySubject): void {
            if (!isset($instructorsBySubject[$subjectId])) {
                $instructorsBySubject[$subjectId] = [];
            }
            foreach ($instructorsBySubject[$subjectId] as $item) {
                if ((int)$item['id'] === $employeeId) {
                    return;
                }
            }
            $instructorsBySubject[$subjectId][] = ['id' => $employeeId, 'label' => $label];
        };

        if (!EmployeeSchoolSubject::isEnabled()) {
            foreach (SchoolSubject::find()->where(['school_department_id' => $departmentId])->orderBy(['name' => SORT_ASC])->all() as $subject) {
                foreach ($departmentTeachers as $teacher) {
                    $appendInstructor((int)$subject->id, (int)$teacher->id, $formatLabel($teacher));
                }
            }

            return $instructorsBySubject;
        }

        $assignments = EmployeeSchoolSubject::find()
            ->alias('es')
            ->innerJoinWith(['subject subject', 'employee employee'])
            ->where(['subject.school_department_id' => $departmentId])
            ->orderBy(['employee.full_name' => SORT_ASC])
            ->all();

        foreach ($assignments as $assignment) {
            $employee = $assignment->employee;
            if ($employee === null) {
                continue;
            }

            $employeeId = (int)$employee->id;
            if ((int)($employee->school_department_id ?? 0) !== $departmentId) {
                continue;
            }
            if (Employee::hasColumn('teacher') && (int)$employee->teacher !== 1) {
                continue;
            }
            if (Employee::hasColumn('status') && strcasecmp((string)$employee->status, 'Active') !== 0) {
                continue;
            }

            $appendInstructor(
                (int)$assignment->school_subject_id,
                $employeeId,
                $formatLabel($teacherById[$employeeId] ?? $employee)
            );
        }

        return $instructorsBySubject;
    }

    /**
     * @return int[]
     */
    private function findAssignedClassTeacherIds(?int $excludeSectionId = null): array
    {
        $query = SchoolSection::find()
            ->select('lead_teacher_id')
            ->andWhere(['not', ['lead_teacher_id' => null]]);

        if ($excludeSectionId !== null && $excludeSectionId > 0) {
            $query->andWhere(['<>', 'id', $excludeSectionId]);
        }

        return array_values(array_unique(array_map('intval', $query->column())));
    }

    /**
     * Teachers eligible for class teacher assignment (excludes already-assigned lead teachers).
     *
     * @return Employee[]
     */
    private function findAvailableClassTeachers(?int $departmentId, ?int $excludeSectionId, array $includeEmployeeIds = []): array
    {
        $teachers = $this->findTeachersForSchoolDepartment($departmentId);
        $assignedIds = $this->findAssignedClassTeacherIds($excludeSectionId);
        if ($assignedIds !== []) {
            $teachers = array_values(array_filter(
                $teachers,
                static fn(Employee $teacher) => !in_array((int)$teacher->id, $assignedIds, true)
            ));
        }

        return $this->mergeTeachersById($teachers, $includeEmployeeIds);
    }

    private function findSection(int $id): SchoolSection
    {
        $with = ['grade.department', 'leadTeacher'];
        if (SchoolSectionMaterial::isEnabled()) {
            $with[] = 'materials.instructor';
            $with[] = 'materials.subject';
        }
        if (SchoolStudent::isEnabled()) {
            $with[] = 'students';
        }

        $section = SchoolSection::find()->with($with)->where(['id' => $id])->one();
        if ($section === null) {
            throw new NotFoundHttpException('Section not found.');
        }

        return $section;
    }

    /**
     * @return array<int, array<int, array{id:int,name:string}>>
     */
    private function loadGradesByDepartment(): array
    {
        $gradesByDepartment = [];
        foreach (SchoolGrade::find()->orderBy(['school_department_id' => SORT_ASC, 'sort_order' => SORT_ASC, 'name' => SORT_ASC])->all() as $grade) {
            $departmentId = (int)$grade->school_department_id;
            $gradesByDepartment[$departmentId][] = [
                'id' => (int)$grade->id,
                'name' => (string)$grade->name,
            ];
        }

        return $gradesByDepartment;
    }

    /**
     * @return array<int, array{id:int,name:string,credits:float|null}>
     */
    private function findSubjectCatalogForSection(SchoolSection $section): array
    {
        $gradeId = (int)($section->school_grade_id ?? $section->grade?->id ?? 0);
        $departmentId = (int)($section->grade?->school_department_id ?? 0);
        if ($gradeId <= 0 || $departmentId <= 0) {
            return [];
        }

        if (SchoolSubjectGrade::isEnabled()) {
            $catalog = [];
            foreach (SchoolSubjectGrade::find()
                ->alias('sg')
                ->innerJoinWith(['subject subject'])
                ->where([
                    'sg.school_grade_id' => $gradeId,
                    'subject.school_department_id' => $departmentId,
                ])
                ->orderBy(['subject.name' => SORT_ASC])
                ->all() as $subjectGrade) {
                $catalog[] = [
                    'id' => (int)$subjectGrade->subject->id,
                    'name' => (string)$subjectGrade->subject->name,
                    'credits' => (float)$subjectGrade->credits,
                ];
            }

            return $catalog;
        }

        return array_map(
            static fn(SchoolSubject $subject) => [
                'id' => (int)$subject->id,
                'name' => (string)$subject->name,
                'credits' => null,
            ],
            SchoolSubject::find()
                ->where(['school_department_id' => $departmentId])
                ->orderBy(['name' => SORT_ASC])
                ->all()
        );
    }

    private function iconForSubjectName(string $name): string
    {
        $normalized = strtolower(trim($name));
        if (str_contains($normalized, 'calc') || str_contains($normalized, 'math')) {
            return 'functions';
        }
        if (str_contains($normalized, 'science') || str_contains($normalized, 'bio') || str_contains($normalized, 'chem')) {
            return 'science';
        }
        if (str_contains($normalized, 'english') || str_contains($normalized, 'literature')) {
            return 'menu_book';
        }
        if (str_contains($normalized, 'art') || str_contains($normalized, 'music')) {
            return 'palette';
        }

        return 'menu_book';
    }

    private function populateAndSaveMaterial(SchoolSectionMaterial $model, array $row, bool $isNew = false): bool
    {
        if ($isNew || array_key_exists('school_subject_id', $row)) {
            $model->school_subject_id = !empty($row['school_subject_id']) ? (int)$row['school_subject_id'] : null;
        }
        if (array_key_exists('name', $row)) {
            $model->name = trim((string)$row['name']);
        }
        if (array_key_exists('instructor_id', $row)) {
            $model->instructor_id = !empty($row['instructor_id']) ? (int)$row['instructor_id'] : null;
        }
        if (array_key_exists('schedule', $row)) {
            $schedule = trim((string)$row['schedule']);
            $model->schedule = $schedule !== '' ? $schedule : null;
        }
        if (array_key_exists('status', $row)) {
            $status = (string)$row['status'];
            $model->status = in_array($status, [SchoolSectionMaterial::STATUS_ACTIVE, SchoolSectionMaterial::STATUS_PENDING], true)
                ? $status
                : SchoolSectionMaterial::STATUS_ACTIVE;
        }

        if ($model->school_subject_id) {
            $subject = SchoolSubject::findOne(['id' => (int)$model->school_subject_id]);
            if ($subject !== null) {
                if (trim($model->name) === '') {
                    $model->name = (string)$subject->name;
                }
                $model->icon = $this->iconForSubjectName($subject->name);
            }
        }

        if (trim($model->name) === '') {
            $model->addError('name', 'Material name is required.');

            return false;
        }

        return $model->save();
    }

    /**
     * @return SchoolStudent[]
     */
    private function findEligibleStudentsForSection(SchoolSection $section): array
    {
        if (!SchoolStudent::isEnabled()) {
            return [];
        }

        $gradeId = (int)$section->school_grade_id;
        if ($gradeId <= 0) {
            return [];
        }

        $query = SchoolStudent::find()
            ->alias('student')
            ->with('grade')
            ->where(['student.school_grade_id' => $gradeId])
            ->andWhere(['student.school_section_id' => null])
            ->orderBy(['student.full_name' => SORT_ASC]);

        $departmentId = (int)($section->grade?->school_department_id ?? 0);
        if ($departmentId > 0) {
            $query->innerJoinWith(['grade grade' => static function ($gradeQuery) use ($departmentId) {
                $gradeQuery->andWhere(['grade.school_department_id' => $departmentId]);
            }], false);
        }

        return $query->all();
    }

    private function assignStudentToSection(SchoolSection $section, int $studentId, array &$errors): bool
    {
        $student = SchoolStudent::find()->with('grade')->where(['id' => $studentId])->one();
        if ($student === null) {
            $errors[] = 'Student not found.';

            return false;
        }

        $gradeId = (int)$section->school_grade_id;
        if (SchoolStudent::hasColumn('school_grade_id') && (int)$student->school_grade_id !== $gradeId) {
            $errors[] = 'Student is not enrolled in this grade.';

            return false;
        }

        $departmentId = (int)($section->grade?->school_department_id ?? 0);
        if ($departmentId > 0 && (int)($student->grade?->school_department_id ?? 0) !== $departmentId) {
            $errors[] = 'Student does not belong to this department.';

            return false;
        }

        if ((int)$student->school_section_id === (int)$section->id) {
            return true;
        }

        if ($student->school_section_id !== null && (int)$student->school_section_id > 0) {
            $errors[] = 'Student is already assigned to another section.';

            return false;
        }

        $student->school_section_id = (int)$section->id;
        if (!$student->save(false, ['school_section_id', 'updated_at'])) {
            $errors[] = implode(' ', $student->getFirstErrors()) ?: 'Unable to assign student.';

            return false;
        }

        return true;
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
