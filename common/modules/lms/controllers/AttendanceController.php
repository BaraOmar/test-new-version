<?php

namespace common\modules\lms\controllers;

use common\components\LmsPortal;
use common\models\User;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolStudentAttendanceRecord;
use common\modules\employee\services\SchoolStudentAttendanceService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AttendanceController extends Controller
{
    public $layout = '@common/modules/lms/views/layouts/portal';

    private ?SchoolStudentAttendanceService $attendanceService = null;

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
                    'save' => ['POST'],
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

        if (!SchoolStudentAttendanceService::isEnabled()) {
            throw new NotFoundHttpException('Student attendance is not available. Run migrations first.');
        }

        if (!LmsPortal::canAccessStudentAttendanceGateway($user)) {
            throw new ForbiddenHttpException('You do not have access to student attendance.');
        }

        $this->view->params['userName'] = LmsPortal::displayName($user);
        $this->view->params['userRole'] = LmsPortal::roleLabel($user);
        $this->view->params['portalUser'] = $user;
        $this->view->params['currentPage'] = 'lms_attendance';

        return true;
    }

    private function attendanceService(): SchoolStudentAttendanceService
    {
        if ($this->attendanceService === null) {
            $this->attendanceService = new SchoolStudentAttendanceService();
        }

        return $this->attendanceService;
    }

    public function actionIndex()
    {
        $user = $this->requireAttendanceUser();
        $sectionIds = LmsPortal::getAccessibleSectionIdsForAttendanceGateway($user);
        $filterOptions = $this->attendanceService()->buildHistoryFilterOptions($sectionIds);
        $today = SchoolStudentAttendanceService::todayDate();

        $filters = [
            'department_id' => '',
            'grade_id' => '',
            'section_id' => '',
        ];
        $autoSection = count($sectionIds) === 1;
        if ($autoSection) {
            $sectionId = (int)$sectionIds[0];
            $filters['section_id'] = (string)$sectionId;
            foreach ($filterOptions['sections'] as $section) {
                if ((int)$section['id'] === $sectionId) {
                    $filters['grade_id'] = (string)($section['grade_id'] ?? '');
                    $filters['department_id'] = (string)($section['department_id'] ?? '');
                    break;
                }
            }
        }

        return $this->render('index', [
            'eyebrow' => LmsPortal::attendancePortalEyebrow($user),
            'subtitle' => 'Select a section to take today’s attendance.',
            'filterOptions' => $filterOptions,
            'filters' => $filters,
            'todayIso' => $today,
            'todayChip' => $this->attendanceService()->formatChipDate($today),
            'autoSection' => $autoSection,
        ]);
    }

    public function actionTake(?int $section_id = null)
    {
        $user = $this->requireAttendanceUser();
        $sectionId = (int)($section_id ?: Yii::$app->request->get('section_id', 0));
        if ($sectionId <= 0) {
            Yii::$app->session->setFlash('warning', 'No section selected. Go back and choose a section first.');

            return $this->render('take', [
                'eyebrow' => LmsPortal::attendancePortalEyebrow($user),
                'header' => null,
                'lines' => [],
                'date' => SchoolStudentAttendanceService::todayDate(),
                'missingSection' => true,
            ]);
        }

        $section = $this->findAccessibleSection($sectionId);
        $today = SchoolStudentAttendanceService::todayDate();
        $requestedDate = trim((string)Yii::$app->request->get('date', $today));
        if ($requestedDate === '') {
            $requestedDate = $today;
        }

        $normalized = $this->attendanceService()->normalizeDate($requestedDate);
        if ($normalized === null) {
            throw new NotFoundHttpException('Attendance date is required.');
        }

        $session = $this->attendanceService()->findSession((int)$section->id, $normalized);
        $dateValidation = $this->attendanceService()->validateAttendanceDate($normalized, $session !== null);
        if (!$dateValidation['valid']) {
            Yii::$app->session->setFlash('error', $dateValidation['message']);
            if ($session === null) {
                return $this->redirect(['take', 'section_id' => (int)$section->id, 'date' => $today]);
            }

            throw new ForbiddenHttpException($dateValidation['message']);
        }

        $date = (string)$dateValidation['date'];
        $students = $this->attendanceService()->findActiveStudentsForSection($section);
        $session = $this->attendanceService()->findSession((int)$section->id, $date);
        $recordMap = $this->attendanceService()->buildStudentRecordMap($session, $students);

        $lines = [];
        foreach ($students as $index => $student) {
            $studentId = (int)$student->id;
            $record = $recordMap[$studentId] ?? ['status' => SchoolStudentAttendanceRecord::STATUS_PRESENT, 'note' => ''];
            $lines[] = [
                'student_id' => $studentId,
                'section_student_no' => $index + 1,
                'student_name' => trim((string)$student->full_name),
                'student_code' => trim((string)$student->student_code),
                'status' => (string)$record['status'],
                'notes' => (string)$record['note'],
            ];
        }

        return $this->render('take', [
            'eyebrow' => LmsPortal::attendancePortalEyebrow($user),
            'header' => [
                'section_id' => (int)$section->id,
                'section_name' => trim((string)$section->name),
                'grade_name' => trim((string)($section->grade?->name ?? '')),
                'department_name' => trim((string)($section->grade?->department?->name ?? '')),
                'class_teacher_name' => $this->attendanceService()->classTeacherName($section),
                'attendance_date' => $date,
                'is_existing' => $session !== null,
            ],
            'lines' => $lines,
            'date' => $date,
            'missingSection' => false,
        ]);
    }

    public function actionSave(): Response
    {
        $user = $this->requireAttendanceUser();
        $sectionId = (int)Yii::$app->request->post('section_id', 0);
        if ($sectionId <= 0) {
            Yii::$app->session->setFlash('error', 'No section selected for attendance.');

            return $this->redirect(['index']);
        }

        $section = $this->findAccessibleSection($sectionId);
        $date = trim((string)Yii::$app->request->post('date', ''));
        $submittedRecords = (array)Yii::$app->request->post('records', []);
        $students = $this->attendanceService()->findActiveStudentsForSection($section);
        $result = $this->attendanceService()->saveSessionAttendance(
            $section,
            $date,
            $submittedRecords,
            $user,
            $students
        );

        if ($result['success']) {
            Yii::$app->session->setFlash('success', $result['message']);

            return $this->redirect(['index']);
        }

        Yii::$app->session->setFlash('error', $result['message']);

        return $this->redirect(['take', 'section_id' => $sectionId, 'date' => $date !== '' ? $date : SchoolStudentAttendanceService::todayDate()]);
    }

    public function actionHistory()
    {
        $user = $this->requireAttendanceUser();
        $sectionIds = LmsPortal::getAccessibleSectionIdsForAttendanceGateway($user);
        $filterOptions = $this->attendanceService()->buildHistoryFilterOptions($sectionIds);

        $filters = [
            'department_id' => (int)Yii::$app->request->get('department_id', 0),
            'grade_id' => (int)Yii::$app->request->get('grade_id', 0),
            'section_id' => (int)Yii::$app->request->get('section_id', 0),
            'student_id' => (int)Yii::$app->request->get('student_id', 0),
            'date_from' => trim((string)Yii::$app->request->get('date_from', '')),
            'date_to' => trim((string)Yii::$app->request->get('date_to', '')),
            'status' => strtolower(trim((string)Yii::$app->request->get('status', 'all'))),
        ];

        $this->assertHistoryFiltersInScope($filters, $filterOptions, $sectionIds);

        $validStatuses = ['all', 'present', 'absent', 'late', 'excused'];
        if (!in_array($filters['status'], $validStatuses, true)) {
            $filters['status'] = 'all';
        }

        $hasSearched = (bool)Yii::$app->request->get('apply', 0);
        $hasActiveFilter = $this->attendanceService()->hasActiveHistoryFilter($filters);
        if ($hasSearched && !$hasActiveFilter) {
            Yii::$app->session->setFlash(
                'warning',
                'Choose at least one filter (department, grade, section, status, or date) before applying.'
            );
            $hasSearched = false;
        }

        $history = ($hasSearched && $hasActiveFilter)
            ? $this->attendanceService()->searchAttendanceHistory($sectionIds, $filters)
            : ['rows' => [], 'summary' => $this->attendanceService()->emptyHistorySummary()];

        return $this->render('history', [
            'eyebrow' => LmsPortal::attendancePortalEyebrow($user),
            'subtitle' => 'Review and edit attendance records within your scope.',
            'filterOptions' => $filterOptions,
            'filters' => $filters,
            'rows' => $history['rows'],
            'summary' => $history['summary'] + ['total' => (int)($history['summary']['total'] ?? 0)],
            'hasSearched' => $hasSearched,
        ]);
    }

    public function actionSearch()
    {
        return $this->redirect(array_merge(['history'], Yii::$app->request->get()));
    }

    public function actionView()
    {
        return $this->redirect([
            'take',
            'section_id' => (int)Yii::$app->request->get('section_id', 0),
            'date' => (string)Yii::$app->request->get('date', ''),
        ]);
    }

    private function requireAttendanceUser(): User
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User) {
            throw new ForbiddenHttpException('Authentication is required.');
        }

        return $user;
    }

    private function findAccessibleSection(int $sectionId): SchoolSection
    {
        if ($sectionId <= 0) {
            throw new ForbiddenHttpException('No section selected for attendance.');
        }

        $section = $this->attendanceService()->findSection($sectionId);
        if ($section === null) {
            throw new NotFoundHttpException('Section not found.');
        }

        $user = $this->requireAttendanceUser();
        if (!LmsPortal::canViewSectionAttendance($user, $section)) {
            throw new ForbiddenHttpException('You do not have access to this section.');
        }

        return $section;
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $filterOptions
     * @param int[] $sectionIds
     */
    private function assertHistoryFiltersInScope(array $filters, array $filterOptions, array $sectionIds): void
    {
        $managedDeptIds = array_fill_keys(array_map(
            static fn(array $row): int => (int)$row['id'],
            $filterOptions['departments'] ?? []
        ), true);
        $managedGradeIds = array_fill_keys(array_map(
            static fn(array $row): int => (int)$row['id'],
            $filterOptions['grades'] ?? []
        ), true);
        $managedSectionIds = array_fill_keys(array_map('intval', $sectionIds), true);
        $managedStudentIds = array_fill_keys(array_map(
            static fn(array $row): int => (int)$row['id'],
            $filterOptions['students'] ?? []
        ), true);

        if ((int)$filters['department_id'] > 0 && !isset($managedDeptIds[(int)$filters['department_id']])) {
            throw new ForbiddenHttpException('You do not have access to this school department.');
        }
        if ((int)$filters['grade_id'] > 0 && !isset($managedGradeIds[(int)$filters['grade_id']])) {
            throw new ForbiddenHttpException('You do not have access to this grade.');
        }
        if ((int)$filters['section_id'] > 0 && !isset($managedSectionIds[(int)$filters['section_id']])) {
            throw new ForbiddenHttpException('You do not have access to this section.');
        }
        if ((int)$filters['student_id'] > 0 && !isset($managedStudentIds[(int)$filters['student_id']])) {
            throw new ForbiddenHttpException('You do not have access to this student.');
        }
    }
}
