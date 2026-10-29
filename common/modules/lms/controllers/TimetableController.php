<?php

namespace common\modules\lms\controllers;

use common\components\LmsPortal;
use common\models\User;
use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\services\SchoolTimetableManagementService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class TimetableController extends Controller
{
    public $layout = '@common/modules/lms/views/layouts/portal';

    private ?SchoolTimetableManagementService $timetableService = null;

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
                    'save-grid' => ['POST'],
                    'save-slot' => ['POST'],
                    'save-lesson-brief' => ['POST'],
                    'clear-slots' => ['POST'],
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

        if (!LmsPortal::isTimetableEnabled()) {
            throw new NotFoundHttpException('Timetable is not available.');
        }

        if (!LmsPortal::canAccessLmsTimetable($user)) {
            throw new ForbiddenHttpException('You do not have permission to access the timetable.');
        }

        $this->view->params['userName'] = LmsPortal::displayName($user);
        $this->view->params['userRole'] = LmsPortal::roleLabel($user);
        $this->view->params['portalUser'] = $user;
        $this->view->params['currentPage'] = $action->id === 'my-schedule' ? 'lms_my_schedule' : 'lms_timetable';

        return true;
    }

    private function timetableService(): SchoolTimetableManagementService
    {
        if ($this->timetableService === null) {
            $this->timetableService = new SchoolTimetableManagementService();
        }

        return $this->timetableService;
    }

    public function actionIndex()
    {
        $user = $this->requireTimetableUser();
        $accessType = LmsPortal::getLmsTimetableAccessType($user);

        if ($accessType === LmsPortal::TIMETABLE_ACCESS_INSTRUCTOR) {
            return $this->redirect(['my-schedule']);
        }

        $departments = LmsPortal::getAccessibleDepartmentsForTimetable($user);
        $canManage = $accessType === LmsPortal::TIMETABLE_ACCESS_DEPARTMENT_LEADER;

        $initialDepartmentId = 0;
        $initialGradeId = 0;
        $initialSectionId = 0;
        $isStudentView = $accessType === LmsPortal::TIMETABLE_ACCESS_STUDENT;
        $studentScheduleLabel = '';

        if ($accessType === LmsPortal::TIMETABLE_ACCESS_STUDENT) {
            $student = LmsPortal::findLinkedStudent($user);
            $section = $student !== null ? LmsPortal::resolveStudentSection($student) : null;
            if ($section !== null) {
                $initialSectionId = (int)$section->id;
                $initialGradeId = (int)$section->school_grade_id;
                $initialDepartmentId = (int)($section->grade?->school_department_id ?? 0);
            }
            if ($student !== null) {
                $studentScheduleLabel = $student->getClassYearLabel();
                if ($section !== null) {
                    $departmentName = trim((string)($section->grade?->department?->name ?? ''));
                    if ($departmentName !== '' && !str_contains($studentScheduleLabel, $departmentName)) {
                        $studentScheduleLabel .= ' · ' . $departmentName;
                    }
                }
            }
        }

        $gradesByDepartment = [];
        $sectionsByGrade = [];
        $allowedSectionIds = LmsPortal::getAccessibleSectionIdsForTimetable($user);
        foreach ($departments as $department) {
            $departmentId = (int)$department->id;
            $grades = $this->timetableService()->findGradesForDepartment($departmentId);
            $gradesByDepartment[$departmentId] = array_map(
                static fn(SchoolGrade $grade) => ['id' => (int)$grade->id, 'name' => (string)$grade->name],
                $grades
            );

            foreach ($grades as $grade) {
                $gradeId = (int)$grade->id;
                $sections = $this->timetableService()->findSectionsForGrade($gradeId);
                if ($allowedSectionIds !== []) {
                    $sections = array_values(array_filter(
                        $sections,
                        static fn(SchoolSection $section) => in_array((int)$section->id, $allowedSectionIds, true)
                    ));
                }

                $sectionsByGrade[$gradeId] = array_map(
                    static fn(SchoolSection $section) => [
                        'id' => (int)$section->id,
                        'name' => (string)$section->name,
                        'label' => trim((string)$grade->name) !== ''
                            ? $grade->name . ' - Section ' . $section->name
                            : 'Section ' . $section->name,
                    ],
                    $sections
                );
            }
        }

        if (!LmsPortal::isTimetableStorageReady()) {
            Yii::$app->session->setFlash('error', 'Timetable database tables are missing. Run: php yii migrate/up');
        }

        $canEditLessonBrief = LmsPortal::canEditOwnLessonBrief($user);
        $currentEmployeeId = 0;
        if ($canEditLessonBrief) {
            $employee = LmsPortal::findLinkedEmployee($user, true);
            $currentEmployeeId = $employee !== null ? (int)$employee->id : 0;
        }

        return $this->render('index', [
            'accessType' => $accessType,
            'departments' => $departments,
            'gradesByDepartment' => $gradesByDepartment,
            'sectionsByGrade' => $sectionsByGrade,
            'canManage' => $canManage,
            'canEditLessonBrief' => $canEditLessonBrief,
            'currentEmployeeId' => $currentEmployeeId,
            'dayNames' => SchoolTimetableManagementService::dayNameMap(),
            'initialDepartmentId' => $initialDepartmentId,
            'initialGradeId' => $initialGradeId,
            'initialSectionId' => $initialSectionId,
            'isStudentView' => $isStudentView,
            'studentScheduleLabel' => $studentScheduleLabel,
        ]);
    }

    public function actionMySchedule()
    {
        $user = $this->requireTimetableUser();
        if (!LmsPortal::canAccessOwnInstructorSchedule($user) && !LmsPortal::canEditOwnLessonBrief($user)) {
            throw new ForbiddenHttpException('You do not have permission to view this schedule.');
        }

        $employee = LmsPortal::findLinkedEmployee($user, true);
        if ($employee === null || (int)$employee->school_department_id <= 0) {
            throw new ForbiddenHttpException('Your teaching department is not configured.');
        }

        if (!LmsPortal::isTimetableStorageReady()) {
            Yii::$app->session->setFlash('error', 'Timetable database tables are missing. Run: php yii migrate/up');
        }

        $schedule = $this->timetableService()->buildInstructorSchedulePayload($employee);

        return $this->render('my-schedule', [
            'schedule' => $schedule,
            'dayNames' => SchoolTimetableManagementService::dayNameMap(),
        ]);
    }

    public function actionGradesByDepartment(int $department_id): Response
    {
        $user = $this->requireTimetableUser();
        if (LmsPortal::canAccessOwnInstructorSchedule($user)) {
            throw new ForbiddenHttpException('You cannot browse department timetables.');
        }
        if (!$this->canAccessDepartment($user, $department_id)) {
            throw new ForbiddenHttpException('You cannot access this department.');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        $grades = $this->timetableService()->findGradesForDepartment($department_id);

        return $this->asJson(array_map(
            static fn(SchoolGrade $grade) => ['id' => (int)$grade->id, 'name' => (string)$grade->name],
            $grades
        ));
    }

    /** @deprecated Use actionGradesByDepartment */
    public function actionGrades(int $department_id): Response
    {
        return $this->actionGradesByDepartment($department_id);
    }

    public function actionSectionsByGrade(int $grade_id): Response
    {
        $user = $this->requireTimetableUser();
        if (LmsPortal::canAccessOwnInstructorSchedule($user)) {
            throw new ForbiddenHttpException('You cannot browse department timetables.');
        }
        $grade = SchoolGrade::findOne($grade_id);
        if ($grade === null || !$this->canAccessDepartment($user, (int)$grade->school_department_id)) {
            throw new ForbiddenHttpException('You cannot access this grade.');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        $allowedSectionIds = LmsPortal::getAccessibleSectionIdsForTimetable($user);
        $sections = $this->timetableService()->findSectionsForGrade($grade_id);
        if ($allowedSectionIds !== []) {
            $sections = array_values(array_filter(
                $sections,
                static fn(SchoolSection $section) => in_array((int)$section->id, $allowedSectionIds, true)
            ));
        }

        return $this->asJson(array_map(
            static fn(SchoolSection $section) => [
                'id' => (int)$section->id,
                'name' => (string)$section->name,
                'label' => trim((string)($grade->name ?? '')) !== ''
                    ? $grade->name . ' - Section ' . $section->name
                    : 'Section ' . $section->name,
            ],
            $sections
        ));
    }

    /** @deprecated Use actionSectionsByGrade */
    public function actionSections(int $grade_id): Response
    {
        return $this->actionSectionsByGrade($grade_id);
    }

    public function actionLoad(int $section_id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $user = $this->requireTimetableUser();
            if (LmsPortal::canAccessOwnInstructorSchedule($user)) {
                throw new ForbiddenHttpException('Use My Schedule to view your assigned teaching periods.');
            }
            $section = $this->findAccessibleSection($user, $section_id);

            if (!LmsPortal::isTimetableEnabled()) {
                return $this->asJson([
                    'success' => false,
                    'error' => 'Timetable storage is not ready. Run: php yii migrate/up',
                ]);
            }

            $payload = $this->timetableService()->buildTimetablePayload($section);
            $payload['success'] = true;
            $payload['section'] = [
                'id' => (int)$section->id,
                'name' => (string)$section->name,
                'room_number' => trim((string)($section->room_number ?? '')),
                'grade_name' => trim((string)($section->grade?->name ?? '')),
                'department_name' => trim((string)($section->grade?->department?->name ?? '')),
            ];
            $payload['can_manage'] = LmsPortal::canManageLmsTimetableForSection($user, $section);

            return $this->asJson($payload);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);

            return $this->asJson([
                'success' => false,
                'error' => $e->getMessage() !== '' ? $e->getMessage() : 'Unable to load timetable.',
            ]);
        }
    }

    public function actionSaveGrid(): Response
    {
        $user = $this->requireTimetableUser();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $sectionId = (int)Yii::$app->request->post('section_id', 0);
        $section = $this->findAccessibleSection($user, $sectionId, true);
        $rows = (array)Yii::$app->request->post('rows', []);
        $columns = (array)Yii::$app->request->post('columns', []);

        $result = $this->timetableService()->saveGrid((int)$section->id, $rows, $columns);
        if (!$result['success']) {
            return $this->asJson(['success' => false, 'errors' => $result['errors']]);
        }

        return $this->asJson([
            'success' => true,
            'data' => $this->timetableService()->buildTimetablePayload($section),
        ]);
    }

    public function actionSaveSlot(): Response
    {
        $user = $this->requireTimetableUser();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $sectionId = (int)Yii::$app->request->post('section_id', 0);
        $section = $this->findAccessibleSection($user, $sectionId, true);
        $rowId = (int)Yii::$app->request->post('row_id', 0);
        $columnId = (int)Yii::$app->request->post('column_id', 0);
        $materialId = Yii::$app->request->post('material_id');
        $materialId = $materialId === null || $materialId === '' ? null : (int)$materialId;

        $result = $this->timetableService()->assignMaterialToCell($section, $rowId, $columnId, $materialId);
        if (!$result['success']) {
            return $this->asJson(['success' => false, 'errors' => $result['errors']]);
        }

        return $this->asJson(['success' => true, 'slot' => $result['slot']]);
    }

    public function actionSaveLessonBrief(): Response
    {
        $user = $this->requireTimetableUser();
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!LmsPortal::canEditOwnLessonBrief($user)) {
            throw new ForbiddenHttpException('Only assigned teachers can edit lesson notes.');
        }

        $slotId = (int)Yii::$app->request->post('slot_id', 0);
        $lessonBrief = Yii::$app->request->post('lesson_brief');

        $result = $this->timetableService()->updateSlotLessonBrief($user, $slotId, $lessonBrief === null ? null : (string)$lessonBrief);
        if (!$result['success']) {
            return $this->asJson(['success' => false, 'errors' => $result['errors']]);
        }

        return $this->asJson([
            'success' => true,
            'message' => 'Lesson note saved.',
            'slot' => $result['slot'],
        ]);
    }

    public function actionClearSlots(): Response
    {
        $user = $this->requireTimetableUser();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $sectionId = (int)Yii::$app->request->post('section_id', 0);
        $section = $this->findAccessibleSection($user, $sectionId, true);

        $result = $this->timetableService()->clearSectionSlots((int)$section->id);
        if (!$result['success']) {
            return $this->asJson(['success' => false, 'errors' => $result['errors']]);
        }

        return $this->asJson(['success' => true, 'slots' => []]);
    }

    public function actionCourseOptions(int $section_id, int $row_id, int $column_id): Response
    {
        $user = $this->requireTimetableUser();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $section = $this->findAccessibleSection($user, $section_id, true);

        return $this->asJson([
            'success' => true,
            'courses' => $this->timetableService()->getCourseOptionsForCell($section, $row_id, $column_id),
        ]);
    }

    private function requireTimetableUser(): User
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !LmsPortal::canAccessLmsTimetable($user)) {
            throw new ForbiddenHttpException('You do not have permission to access the timetable.');
        }

        return $user;
    }

    private function canAccessDepartment(User $user, int $departmentId): bool
    {
        if ($departmentId <= 0) {
            return false;
        }

        if ($user->isSuperHrAdmin() && LmsPortal::canAccessLmsTimetable($user)) {
            return SchoolDepartment::find()->where(['id' => $departmentId])->exists();
        }

        foreach (LmsPortal::getAccessibleDepartmentsForTimetable($user) as $department) {
            if ((int)$department->id === $departmentId) {
                return true;
            }
        }

        return false;
    }

    private function findAccessibleSection(User $user, int $sectionId, bool $requireManage = false): SchoolSection
    {
        if ($sectionId <= 0) {
            throw new NotFoundHttpException('Section not found.');
        }

        $section = SchoolSection::find()
            ->with(['grade.department'])
            ->where(['id' => $sectionId])
            ->one();
        if ($section === null) {
            throw new NotFoundHttpException('Section not found.');
        }

        if ($requireManage) {
            if (!LmsPortal::canManageLmsTimetableForSection($user, $section)) {
                throw new ForbiddenHttpException('You cannot edit this section timetable.');
            }

            return $section;
        }

        if (!LmsPortal::canViewLmsTimetableForSection($user, $section)) {
            throw new ForbiddenHttpException('You cannot view this section timetable.');
        }

        return $section;
    }
}
