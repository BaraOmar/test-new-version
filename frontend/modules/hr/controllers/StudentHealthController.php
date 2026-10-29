<?php

namespace frontend\modules\hr\controllers;

use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolStudent;
use common\modules\employee\models\SchoolStudentClinicVisit;
use common\modules\employee\models\SchoolStudentHealthFile;
use common\modules\employee\services\SchoolStudentHealthService;
use common\traits\HrAccessTrait;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class StudentHealthController extends Controller
{
    use HrAccessTrait;

    private ?SchoolStudentHealthService $healthService = null;

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
                    'delete' => ['POST'],
                    'delete-clinic-visit' => ['POST'],
                    'notify-parents' => ['POST'],
                ],
            ],
        ];
    }

    private function healthService(): SchoolStudentHealthService
    {
        if ($this->healthService === null) {
            $this->healthService = new SchoolStudentHealthService();
        }

        return $this->healthService;
    }

    protected function requireStudentHealthAccess(): void
    {
        $this->requireStudentHealthHubAccess();
        if (!SchoolStudentHealthFile::isEnabled()) {
            throw new NotFoundHttpException('Student health files are not available. Run: php yii migrate/up');
        }
    }

    protected function requireStudentHealthHubAccess(): void
    {
        $this->requireHr();
        $user = Yii::$app->user->identity;
        if (!$user->hasPermission('student_health') && !$user->hasPermission('employees')) {
            throw new \yii\web\ForbiddenHttpException('Access denied.');
        }
        if (!SchoolStudent::isEnabled()) {
            throw new NotFoundHttpException('Student records are not available.');
        }
    }

    protected function requireClinicVisitsEnabled(): void
    {
        $this->requireStudentHealthAccess();
        if (!SchoolStudentClinicVisit::isEnabled()) {
            throw new NotFoundHttpException('Clinic visits are not available. Run: php yii migrate/up');
        }
    }

    public function actionIndex()
    {
        $this->requireStudentHealthHubAccess();
        $this->view->params['bodyClass'] = 'bg-slate-50 text-slate-900 min-h-screen';

        return $this->render('index', [
            'userName' => $this->userDisplayName(),
            'currentPage' => 'student_health',
        ]);
    }

    public function actionFiles()
    {
        $this->requireStudentHealthAccess();
        $this->view->params['bodyClass'] = 'bg-slate-50 text-slate-900 min-h-screen';

        $filters = [
            'search' => trim((string)Yii::$app->request->get('search', '')),
            'department_id' => (int)Yii::$app->request->get('department_id', 0),
            'grade_id' => (int)Yii::$app->request->get('grade_id', 0),
            'section_id' => (int)Yii::$app->request->get('section_id', 0),
            'allergy_status' => trim((string)Yii::$app->request->get('allergy_status', '')),
            'health_risk' => trim((string)Yii::$app->request->get('health_risk', '')),
            'last_visit_from' => trim((string)Yii::$app->request->get('last_visit_from', '')),
            'last_visit_to' => trim((string)Yii::$app->request->get('last_visit_to', '')),
        ];
        [$filters['last_visit_from'], $filters['last_visit_to']] = $this->healthService()->normalizeDateRange(
            $filters['last_visit_from'],
            $filters['last_visit_to']
        );
        $viewMode = Yii::$app->request->get('view', 'cards') === 'list' ? 'list' : 'cards';

        if ($filters['grade_id'] > 0 && $filters['department_id'] > 0) {
            $grade = SchoolGrade::findOne($filters['grade_id']);
            if ($grade === null || (int)$grade->school_department_id !== $filters['department_id']) {
                $filters['grade_id'] = 0;
                $filters['section_id'] = 0;
            }
        }

        if ($filters['section_id'] > 0 && $filters['grade_id'] > 0) {
            $section = SchoolSection::findOne($filters['section_id']);
            if ($section === null || (int)$section->school_grade_id !== $filters['grade_id']) {
                $filters['section_id'] = 0;
            }
        }

        $pageSize = $viewMode === 'list' ? 20 : 12;
        $dataProvider = $this->healthService()->createDataProvider($filters, $pageSize);
        $departments = $this->healthService()->findDepartments();
        $grades = $this->healthService()->findGrades($filters['department_id']);
        $sections = $filters['grade_id'] > 0
            ? $this->healthService()->findSectionsForGrade($filters['grade_id'])
            : [];

        return $this->render('files', [
            'userName' => $this->userDisplayName(),
            'currentPage' => 'student_health_files',
            'dataProvider' => $dataProvider,
            'filters' => $filters,
            'viewMode' => $viewMode,
            'departments' => $departments,
            'grades' => $grades,
            'sections' => $sections,
            'gradesByDepartment' => $this->healthService()->buildGradesByDepartment(),
            'sectionsByGrade' => $this->healthService()->buildSectionsByGrade(),
        ]);
    }

    public function actionCreate(int $student_id = 0)
    {
        $this->requireStudentHealthAccess();
        $this->view->params['bodyClass'] = 'bg-slate-50 text-slate-900 min-h-screen';

        $student = $student_id > 0 ? $this->findStudent($student_id) : null;
        if ($student !== null && $student->healthFile instanceof SchoolStudentHealthFile) {
            return $this->redirect(['update', 'id' => (int)$student->healthFile->id]);
        }

        $model = $student !== null
            ? $this->healthService()->findOrCreateHealthFile($student)
            : new SchoolStudentHealthFile();

        if ($model->isNewRecord && $student === null && Yii::$app->request->isPost) {
            $postedStudentId = (int)Yii::$app->request->post('school_student_id', 0);
            $student = $postedStudentId > 0 ? $this->findStudent($postedStudentId) : null;
            if ($student !== null) {
                $existing = $student->healthFile;
                if ($existing instanceof SchoolStudentHealthFile) {
                    return $this->redirect(['update', 'id' => (int)$existing->id]);
                }
                $model = $this->healthService()->findOrCreateHealthFile($student);
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Health file saved successfully.');
            return $this->redirect(['view', 'id' => (int)$model->id]);
        }

        return $this->render('form', [
            'userName' => $this->userDisplayName(),
            'currentPage' => 'student_health_files',
            'model' => $model,
            'student' => $student ?? $model->student,
            'studentsWithoutFile' => $this->healthService()->findStudentsWithoutHealthFile(),
            'isCreate' => true,
        ]);
    }

    public function actionUpdate(int $id)
    {
        $this->requireStudentHealthAccess();
        $this->view->params['bodyClass'] = 'bg-slate-50 text-slate-900 min-h-screen';

        $model = $this->findHealthFile($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Health file updated successfully.');
            return $this->redirect(['view', 'id' => (int)$model->id]);
        }

        return $this->render('form', [
            'userName' => $this->userDisplayName(),
            'currentPage' => 'student_health_files',
            'model' => $model,
            'student' => $model->student,
            'studentsWithoutFile' => [],
            'isCreate' => false,
        ]);
    }

    public function actionView(int $id)
    {
        $this->requireStudentHealthAccess();
        $this->view->params['bodyClass'] = 'bg-slate-50 text-slate-900 min-h-screen';

        $model = $this->findHealthFile($id);
        $clinicVisits = [];
        if (SchoolStudentClinicVisit::isEnabled()) {
            $clinicVisits = SchoolStudentClinicVisit::find()
                ->where(['school_student_id' => (int)$model->school_student_id])
                ->orderBy(['visit_date' => SORT_DESC, 'time_in' => SORT_DESC])
                ->limit(3)
                ->all();
        }

        return $this->render('view', [
            'userName' => $this->userDisplayName(),
            'currentPage' => 'student_health',
            'model' => $model,
            'student' => $model->student,
            'clinicVisits' => $clinicVisits,
        ]);
    }

    public function actionDelete(int $id): Response
    {
        $this->requireStudentHealthAccess();
        $model = $this->findHealthFile($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Health file deleted.');

        return $this->redirect(['files']);
    }

    public function actionClinicVisits(int $student_id = 0)
    {
        $this->requireClinicVisitsEnabled();
        $this->view->params['bodyClass'] = 'bg-slate-50 text-slate-900 min-h-screen';

        $model = new SchoolStudentClinicVisit([
            'visit_date' => date('Y-m-d'),
            'time_in' => date('H:i'),
            'visit_type' => SchoolStudentClinicVisit::TYPE_ROUTINE,
            'status' => SchoolStudentClinicVisit::STATUS_IN_PROGRESS,
            'action_taken' => SchoolStudentClinicVisit::ACTION_RESTED,
        ]);

        $selectedStudent = $student_id > 0 ? $this->findStudent($student_id) : null;
        if ($selectedStudent !== null) {
            $model->school_student_id = (int)$selectedStudent->id;
        }

        if ($model->load(Yii::$app->request->post())) {
            $model->medicine_given = (bool)Yii::$app->request->post('medicine_given', false);
            $user = Yii::$app->user->identity;
            if ($user !== null) {
                $model->recorded_by_user_id = (int)$user->id;
            }
            if ($model->save()) {
                $student = $this->findStudent((int)$model->school_student_id);
                $this->healthService()->touchHealthFileLastVisit($student, (string)$model->visit_date);
                Yii::$app->session->setFlash('success', 'Clinic visit saved successfully.');
                return $this->redirect(['clinic-visit-records', 'student_id' => (int)$model->school_student_id]);
            }
        }

        return $this->render('clinic-visits', [
            'userName' => $this->userDisplayName(),
            'currentPage' => 'student_health_clinic',
            'model' => $model,
            'students' => $this->healthService()->findStudentsForSelect(),
            'selectedStudent' => $selectedStudent,
            'studentSummary' => $selectedStudent ? $this->healthService()->buildStudentSummary($selectedStudent) : null,
            'summaryUrl' => \yii\helpers\Url::to(['student-summary']),
            'notifyParentsUrl' => \yii\helpers\Url::to(['notify-parents']),
            'parentNotifyActions' => SchoolStudentClinicVisit::parentNotificationActions(),
        ]);
    }

    public function actionNotifyParents(): Response
    {
        $this->requireClinicVisitsEnabled();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $studentId = (int)Yii::$app->request->post('student_id', 0);
        $actionTaken = trim((string)Yii::$app->request->post('action_taken', ''));
        if ($studentId <= 0 || !SchoolStudentClinicVisit::requiresParentNotification($actionTaken)) {
            return $this->asJson([
                'success' => false,
                'message' => 'Parent notification is only available for Parent Contacted, Sent Home, or Referred to Hospital.',
            ]);
        }

        $student = $this->findStudent($studentId);
        $visitData = [
            'action_taken' => $actionTaken,
            'visit_date' => trim((string)Yii::$app->request->post('visit_date', date('Y-m-d'))),
            'primary_reason' => trim((string)Yii::$app->request->post('primary_reason', '')),
            'clinical_notes' => trim((string)Yii::$app->request->post('clinical_notes', '')),
        ];
        $customMessage = trim((string)Yii::$app->request->post('message', ''));

        $context = $this->healthService()->buildParentNotificationContext($student, $visitData);
        if (trim((string)($context['notify_email'] ?? '')) === '') {
            return $this->asJson([
                'success' => false,
                'message' => 'No parent/guardian email is on file for this student. Use call or SMS instead.',
                'context' => $context,
            ]);
        }

        $sent = $this->healthService()->sendParentClinicNotification($student, $visitData, $customMessage !== '' ? $customMessage : null);
        if (!$sent) {
            return $this->asJson([
                'success' => false,
                'message' => 'Unable to send the notification email. Please try calling the guardian directly.',
                'context' => $context,
            ]);
        }

        return $this->asJson([
            'success' => true,
            'message' => 'Parent notification email sent to ' . $context['notify_email'] . '.',
            'context' => $context,
        ]);
    }

    public function actionClinicVisitRecords()
    {
        $this->requireClinicVisitsEnabled();
        $this->view->params['bodyClass'] = 'bg-slate-50 text-slate-900 min-h-screen';

        $filters = [
            'search' => trim((string)Yii::$app->request->get('search', '')),
            'student_id' => (int)Yii::$app->request->get('student_id', 0),
            'status' => trim((string)Yii::$app->request->get('status', '')),
            'date_from' => trim((string)Yii::$app->request->get('date_from', '')),
            'date_to' => trim((string)Yii::$app->request->get('date_to', '')),
        ];
        [$filters['date_from'], $filters['date_to']] = $this->healthService()->normalizeDateRange(
            $filters['date_from'],
            $filters['date_to']
        );

        $dataProvider = $this->healthService()->createClinicVisitDataProvider($filters);

        return $this->render('clinic-visit-records', [
            'userName' => $this->userDisplayName(),
            'currentPage' => 'student_health_records',
            'dataProvider' => $dataProvider,
            'filters' => $filters,
            'students' => $this->healthService()->findStudentsForSelect(),
        ]);
    }

    public function actionStudentSummary(int $student_id): Response
    {
        $this->requireClinicVisitsEnabled();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $student = $this->findStudent($student_id);

        return $this->asJson([
            'success' => true,
            'summary' => $this->healthService()->buildStudentSummary($student),
        ]);
    }

    public function actionDeleteClinicVisit(int $id): Response
    {
        $this->requireClinicVisitsEnabled();
        $visit = SchoolStudentClinicVisit::findOne($id);
        if ($visit === null) {
            throw new NotFoundHttpException('Clinic visit not found.');
        }
        $visit->delete();
        Yii::$app->session->setFlash('success', 'Clinic visit deleted.');

        return $this->redirect(['clinic-visit-records']);
    }

    private function findStudent(int $id): SchoolStudent
    {
        $student = SchoolStudent::find()
            ->with(['grade', 'section', 'healthFile'])
            ->where(['id' => $id])
            ->one();
        if ($student === null) {
            throw new NotFoundHttpException('Student not found.');
        }

        return $student;
    }

    private function findHealthFile(int $id): SchoolStudentHealthFile
    {
        $model = SchoolStudentHealthFile::find()
            ->with(['student.grade', 'student.section'])
            ->where(['id' => $id])
            ->one();
        if ($model === null) {
            throw new NotFoundHttpException('Health file not found.');
        }

        return $model;
    }

    private function userDisplayName(): string
    {
        $user = Yii::$app->user->identity;

        return trim((string)($user?->username ?? 'Admin User')) ?: 'Admin User';
    }
}
