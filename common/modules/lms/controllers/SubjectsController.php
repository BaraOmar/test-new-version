<?php

namespace common\modules\lms\controllers;

use common\components\LmsPortal;
use common\models\User;
use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSubject;
use common\modules\employee\models\SchoolSubjectGrade;
use common\modules\employee\services\SchoolSubjectManagementService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SubjectsController extends Controller
{
    public $layout = '@common/modules/lms/views/layouts/portal';

    private ?SchoolSubjectManagementService $subjectService = null;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['GET', 'POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !LmsPortal::canAccessLms($user)) {
            throw new ForbiddenHttpException('LMS access is required.');
        }

        if (!LmsPortal::isSubjectsEnabled()) {
            throw new NotFoundHttpException('Subjects are not available.');
        }

        if ($action->id !== 'index' && !LmsPortal::canAccessLmsSubjects($user)) {
            throw new ForbiddenHttpException('You do not have permission to access subjects.');
        }

        if (in_array($action->id, ['create'], true) && !LmsPortal::canCreateLmsSubjects($user)) {
            throw new ForbiddenHttpException('You do not have permission to create subjects.');
        }

        $this->view->params['userName'] = LmsPortal::displayName($user);
        $this->view->params['userRole'] = LmsPortal::roleLabel($user);
        $this->view->params['portalUser'] = $user;
        $this->view->params['currentPage'] = 'lms_subjects';

        return true;
    }

    private function subjectService(): SchoolSubjectManagementService
    {
        if ($this->subjectService === null) {
            $this->subjectService = new SchoolSubjectManagementService();
        }

        return $this->subjectService;
    }

    public function actionIndex()
    {
        $user = $this->requireSubjectsUser();
        $accessType = LmsPortal::getLmsSubjectsAccessType($user);

        if ($accessType === LmsPortal::SUBJECTS_ACCESS_STUDENT) {
            $student = LmsPortal::findLinkedStudent($user);
            if ($student === null) {
                throw new ForbiddenHttpException('Student profile not found.');
            }

            return $this->render('index', [
                'accessType' => $accessType,
                'student' => $student,
                'materials' => $this->subjectService()->findStudentSectionMaterials($student),
            ]);
        }

        $employee = LmsPortal::findLinkedEmployee($user, true);
        if ($employee === null) {
            throw new ForbiddenHttpException('Employee profile not found.');
        }

        $assignedSubjects = [];
        $sectionMaterials = [];
        if (in_array($accessType, [LmsPortal::SUBJECTS_ACCESS_TEACHER, LmsPortal::SUBJECTS_ACCESS_DEPARTMENT_LEADER], true)) {
            $assignedSubjects = $this->subjectService()->findTeacherSubjectAssignments($employee);
            $sectionMaterials = $this->subjectService()->findTeacherSectionMaterials($employee);
        }

        $departmentSubjects = [];
        $departments = [];

        if ($accessType === LmsPortal::SUBJECTS_ACCESS_DEPARTMENT_LEADER) {
            $departmentIds = LmsPortal::getAccessibleDepartmentIds($user);
            $departments = SchoolDepartment::find()
                ->where(['id' => $departmentIds])
                ->orderBy(['name' => SORT_ASC])
                ->all();
            $departmentSubjects = $this->subjectService()->findDepartmentSubjects($departmentIds);
        }

        return $this->render('index', [
            'accessType' => $accessType,
            'employee' => $employee,
            'assignedSubjects' => $assignedSubjects,
            'sectionMaterials' => $sectionMaterials,
            'departmentSubjects' => $departmentSubjects,
            'departments' => $departments,
        ]);
    }

    public function actionCreate()
    {
        $user = $this->requireDepartmentLeader();
        $departmentIds = LmsPortal::getAccessibleDepartmentIds($user);
        $departments = SchoolDepartment::find()
            ->where(['id' => $departmentIds])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        if ($departments === []) {
            throw new ForbiddenHttpException('You do not have a department to manage.');
        }

        $gradesByDepartment = array_intersect_key(
            $this->subjectService()->loadGradesByDepartment(),
            array_flip($departmentIds)
        );

        if (Yii::$app->request->isPost) {
            return $this->processCreateSubject($departmentIds);
        }

        return $this->render('create', [
            'departments' => $departments,
            'gradesByDepartment' => $gradesByDepartment,
        ]);
    }

    public function actionGradesByDepartment(int $department_id): Response
    {
        $user = $this->requireDepartmentLeader();
        if (!LmsPortal::canManageLmsSubjectsInDepartment($user, $department_id)) {
            throw new ForbiddenHttpException('You cannot manage subjects in this department.');
        }

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

    private function processCreateSubject(array $allowedDepartmentIds): Response
    {
        if (!SchoolSubjectGrade::isEnabled()) {
            Yii::$app->session->setFlash('error', 'Subject credits are not available.');
            return $this->redirect(['create']);
        }

        $user = $this->requireDepartmentLeader();
        $departmentId = (int)Yii::$app->request->post('school_department_id', 0);
        $name = trim((string)Yii::$app->request->post('name', ''));
        $gradeCredits = (array)Yii::$app->request->post('grade_credits', []);
        $gradeMaxScores = (array)Yii::$app->request->post('grade_max_scores', []);
        $selectedGradeIds = array_values(array_unique(array_map('intval', (array)Yii::$app->request->post('grade_ids', []))));

        if (!LmsPortal::canManageLmsSubjectsInDepartment($user, $departmentId)) {
            Yii::$app->session->setFlash('error', 'You cannot create subjects in this department.');
            return $this->redirect(['create']);
        }

        if ($name === '') {
            Yii::$app->session->setFlash('error', 'Subject name is required.');
            return $this->redirect(['create']);
        }

        if ($selectedGradeIds === []) {
            Yii::$app->session->setFlash('error', 'Select at least one grade.');
            return $this->redirect(['create']);
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
                    return $this->redirect(['create']);
                }
            }

            $result = $this->subjectService()->saveSubjectGradeAssignments($subject, $departmentId, $selectedGradeIds, $gradeCredits, $gradeMaxScores);
            if ($result['errors'] !== []) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', implode(' ', array_unique($result['errors'])));
                return $this->redirect(['create']);
            }

            if ($result['savedCount'] === 0) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Select at least one grade.');
                return $this->redirect(['create']);
            }

            $transaction->commit();
            Yii::$app->session->setFlash('success', $result['savedCount'] > 1
                ? 'Subject saved with ' . $result['savedCount'] . ' grades assigned.'
                : 'Subject saved.');
        } catch (\Throwable) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Unable to create subject.');
            return $this->redirect(['create']);
        }

        return $this->redirect(['index']);
    }

    private function requireSubjectsUser(): User
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !LmsPortal::canAccessLmsSubjects($user)) {
            throw new ForbiddenHttpException('You do not have permission to access subjects.');
        }

        return $user;
    }

    private function requireDepartmentLeader(): User
    {
        $user = $this->requireSubjectsUser();
        if (!LmsPortal::canCreateLmsSubjects($user)) {
            throw new ForbiddenHttpException('Department leadership access is required.');
        }

        return $user;
    }
}
