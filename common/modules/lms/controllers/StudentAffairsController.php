<?php

namespace common\modules\lms\controllers;

use common\components\LmsPortal;
use common\models\User;
use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolStudent;
use common\modules\employee\models\SchoolStudentClinicVisit;
use common\modules\employee\models\SchoolStudentHealthFile;
use common\modules\employee\services\SchoolStudentAttendanceService;
use common\modules\employee\services\SchoolSectionMarksService;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class StudentAffairsController extends Controller
{
    public $layout = '@common/modules/lms/views/layouts/portal';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
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

        if (!LmsPortal::isStudentAffairsEnabled()) {
            throw new NotFoundHttpException('Student Affairs is not available.');
        }

        $this->view->params['userName'] = LmsPortal::displayName($user);
        $this->view->params['userRole'] = LmsPortal::roleLabel($user);
        $this->view->params['portalUser'] = $user;
        $this->view->params['currentPage'] = $this->resolveCurrentPage($action->id);

        return true;
    }

    private function resolveCurrentPage(string $actionId): string
    {
        return match ($actionId) {
            'students' => 'lms_student_registry',
            'view' => 'lms_student_registry',
            'attendance' => 'lms_student_registry',
            'marks' => 'lms_student_registry',
            'profile' => 'lms_my_profile',
            'health-record' => 'lms_my_health',
            'attendance-log' => 'lms_my_attendance',
            'marks-log' => 'lms_my_marks',
            default => 'lms_dashboard',
        };
    }

    public function actionStudents()
    {
        $user = $this->requireRegistryUser();

        $search = trim((string)Yii::$app->request->get('search', ''));
        $gradeFilter = (int)Yii::$app->request->get('grade_id', 0);
        $sectionFilter = (int)Yii::$app->request->get('section_id', 0);
        $statusFilter = trim((string)Yii::$app->request->get('status', ''));

        $query = LmsPortal::buildAccessibleStudentsQuery($user);
        if ($query === null) {
            throw new ForbiddenHttpException('You do not have permission to view the student registry.');
        }

        if ($search !== '') {
            $searchConditions = [
                ['like', 'student.full_name', $search],
            ];
            if (SchoolStudent::hasNameParts()) {
                $searchConditions[] = ['like', 'student.first_name', $search];
                $searchConditions[] = ['like', 'student.father_name', $search];
                $searchConditions[] = ['like', 'student.grandfather_name', $search];
                $searchConditions[] = ['like', 'student.family_name', $search];
            }
            $query->andWhere(array_merge(['or'], $searchConditions));
        }

        if ($gradeFilter > 0 && !$this->isGradeAccessible($user, $gradeFilter)) {
            $gradeFilter = 0;
            $sectionFilter = 0;
        }
        if ($sectionFilter > 0 && !$this->isSectionAccessible($user, $sectionFilter)) {
            $sectionFilter = 0;
        }

        if ($gradeFilter > 0) {
            if (SchoolStudent::hasColumn('school_grade_id')) {
                $query->andWhere([
                    'or',
                    ['student.school_grade_id' => $gradeFilter],
                    ['section.school_grade_id' => $gradeFilter],
                ]);
            } else {
                $query->andWhere(['section.school_grade_id' => $gradeFilter]);
            }
        }
        if ($sectionFilter > 0) {
            $query->andWhere(['student.school_section_id' => $sectionFilter]);
        }
        if ($statusFilter !== '' && isset(SchoolStudent::statusOptions()[$statusFilter])) {
            $query->andWhere(['student.status' => $statusFilter]);
        }

        $totalCount = (int)$query->count();
        $pagination = new Pagination([
            'totalCount' => $totalCount,
            'pageSize' => 10,
            'pageParam' => 'page',
        ]);

        $students = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $filterOptions = $this->buildFilterOptions($user);
        $sectionOptions = $filterOptions['sections'];
        if ($gradeFilter > 0) {
            $sectionOptions = ArrayHelper::map(
                $filterOptions['sectionsByGrade'][$gradeFilter] ?? [],
                'id',
                'label'
            );
        }

        return $this->render('students', [
            'students' => $students,
            'pagination' => $pagination,
            'totalCount' => $totalCount,
            'search' => $search,
            'gradeFilter' => $gradeFilter,
            'sectionFilter' => $sectionFilter,
            'statusFilter' => $statusFilter,
            'gradeOptions' => $filterOptions['grades'],
            'sectionOptions' => $sectionOptions,
            'sectionsByGrade' => $filterOptions['sectionsByGrade'],
            'scopeLabel' => LmsPortal::studentRegistryScopeLabel($user),
        ]);
    }

    public function actionView(int $id)
    {
        $user = $this->requireRegistryUser();
        $model = LmsPortal::findAccessibleStudent($user, $id);
        if ($model === null) {
            throw new NotFoundHttpException('Student not found.');
        }

        return $this->render('view', [
            'model' => $model,
            'backUrl' => ['/lms/student-affairs/students'],
            'backLabel' => 'Back to Student Registry',
        ]);
    }

    public function actionProfile()
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !LmsPortal::canAccessOwnStudentProfile($user)) {
            throw new ForbiddenHttpException('You do not have permission to view this profile.');
        }

        $model = LmsPortal::findLinkedStudent($user);
        if ($model === null) {
            throw new NotFoundHttpException('Student profile not found.');
        }

        return $this->render('profile', [
            'model' => $model,
        ]);
    }

    public function actionHealthRecord()
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !LmsPortal::canAccessOwnStudentHealthRecord($user)) {
            throw new ForbiddenHttpException('You do not have permission to view this health record.');
        }

        $student = LmsPortal::findLinkedStudent($user);
        if ($student === null) {
            throw new NotFoundHttpException('Student profile not found.');
        }

        $model = $student->healthFile;
        if (!$model instanceof SchoolStudentHealthFile) {
            throw new NotFoundHttpException('Health record not found.');
        }

        $clinicVisits = [];
        if (SchoolStudentClinicVisit::isEnabled()) {
            $clinicVisits = SchoolStudentClinicVisit::find()
                ->where(['school_student_id' => (int)$student->id])
                ->orderBy(['visit_date' => SORT_DESC, 'time_in' => SORT_DESC])
                ->limit(3)
                ->all();
        }

        return $this->render('health-record', [
            'model' => $model,
            'student' => $student,
            'clinicVisits' => $clinicVisits,
        ]);
    }

    public function actionAttendanceLog()
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !LmsPortal::canAccessOwnStudentAttendanceLog($user)) {
            throw new ForbiddenHttpException('You do not have permission to view this attendance log.');
        }

        $student = LmsPortal::findLinkedStudent($user);
        if ($student === null) {
            throw new NotFoundHttpException('Student profile not found.');
        }

        return $this->renderStudentAttendanceLog($user, $student, false);
    }

    public function actionAttendance(int $id)
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User) {
            throw new ForbiddenHttpException('Authentication is required.');
        }

        $student = LmsPortal::findAccessibleStudent($user, $id);
        if ($student === null) {
            throw new NotFoundHttpException('Student not found.');
        }

        if (!LmsPortal::canViewStudentAttendanceLog($user, $student)) {
            throw new ForbiddenHttpException('You do not have permission to view this attendance log.');
        }

        return $this->renderStudentAttendanceLog($user, $student, true);
    }

    public function actionMarksLog()
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !LmsPortal::canAccessOwnStudentMarks($user)) {
            throw new ForbiddenHttpException('You do not have permission to view your marks.');
        }

        $student = LmsPortal::findLinkedStudent($user);
        if ($student === null) {
            throw new NotFoundHttpException('Student profile not found.');
        }

        return $this->renderStudentMarks($user, $student, false);
    }

    public function actionMarks(int $id)
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User) {
            throw new ForbiddenHttpException('Authentication is required.');
        }

        $student = LmsPortal::findAccessibleStudent($user, $id);
        if ($student === null) {
            throw new NotFoundHttpException('Student not found.');
        }

        if (!LmsPortal::canViewStudentMarks($user, $student)) {
            throw new ForbiddenHttpException('You do not have permission to view this student\'s marks.');
        }

        return $this->renderStudentMarks($user, $student, true);
    }

    private function renderStudentMarks(User $user, SchoolStudent $student, bool $isStaffView): string
    {
        if (!SchoolSectionMarksService::isEnabled()) {
            throw new NotFoundHttpException('Student marks are not available.');
        }

        $service = new SchoolSectionMarksService();
        $gradebook = $service->buildStudentGradebook($student);

        return $this->render('marks-log', [
            'student' => $student,
            'isStaffView' => $isStaffView,
            'gradebook' => $gradebook,
            'backUrl' => $isStaffView ? ['/lms/student-affairs/view', 'id' => (int)$student->id] : null,
            'backLabel' => $isStaffView ? 'Back to Student Profile' : null,
        ]);
    }

    private function renderStudentAttendanceLog(User $user, SchoolStudent $student, bool $isStaffView): string
    {
        if (!SchoolStudentAttendanceService::isEnabled()) {
            throw new NotFoundHttpException('Student attendance is not available.');
        }

        $service = new SchoolStudentAttendanceService();
        $logData = $service->buildMyAttendancePage($student);

        return $this->render('attendance-log', [
            'student' => $student,
            'isStaffView' => $isStaffView,
            'summary' => $logData['summary'],
            'rows' => $logData['rows'],
            'eyebrow' => $isStaffView
                ? LmsPortal::attendancePortalEyebrow($user)
                : 'Student Portal',
            'backUrl' => $isStaffView ? ['/lms/student-affairs/view', 'id' => (int)$student->id] : null,
            'backLabel' => $isStaffView ? 'Back to Student Profile' : null,
        ]);
    }

    private function requireRegistryUser(): User
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !LmsPortal::canAccessStudentRegistry($user)) {
            throw new ForbiddenHttpException('You do not have permission to view the student registry.');
        }

        return $user;
    }

    private function isGradeAccessible(User $user, int $gradeId): bool
    {
        foreach ($this->buildFilterOptions($user)['grades'] as $grade) {
            if ((int)$grade->id === $gradeId) {
                return true;
            }
        }

        return false;
    }

    private function isSectionAccessible(User $user, int $sectionId): bool
    {
        $filterOptions = $this->buildFilterOptions($user);
        foreach ($filterOptions['sectionsByGrade'] as $sections) {
            foreach ($sections as $section) {
                if ((int)$section['id'] === $sectionId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array{
     *     grades: SchoolGrade[],
     *     sections: array<int, string>,
     *     sectionsByGrade: array<int, array<int, array{id:int,name:string,label:string}>>
     * }
     */
    private function buildFilterOptions(User $user): array
    {
        $accessType = LmsPortal::studentAffairsAccessType($user);
        $sections = [];

        if ($accessType === LmsPortal::STUDENT_AFFAIRS_ACCESS_CLASS_TEACHER) {
            $sectionIds = LmsPortal::getAccessibleSectionIds($user);
            $sections = SchoolSection::find()
                ->alias('section')
                ->joinWith(['grade grade'])
                ->where(['section.id' => $sectionIds])
                ->orderBy(['grade.name' => SORT_ASC, 'section.name' => SORT_ASC])
                ->all();
        } elseif ($accessType === LmsPortal::STUDENT_AFFAIRS_ACCESS_DEPARTMENT_LEADER) {
            $departmentIds = LmsPortal::getAccessibleDepartmentIds($user);
            $gradeIds = SchoolGrade::find()
                ->select('id')
                ->where(['school_department_id' => $departmentIds])
                ->column();

            if ($gradeIds !== []) {
                $sections = SchoolSection::find()
                    ->alias('section')
                    ->joinWith(['grade grade'])
                    ->where(['section.school_grade_id' => $gradeIds])
                    ->orderBy(['grade.name' => SORT_ASC, 'section.name' => SORT_ASC])
                    ->all();
            }
        }

        $gradeIds = [];
        $sectionOptions = [];
        $sectionsByGrade = [];
        foreach ($sections as $section) {
            $gradeId = (int)$section->school_grade_id;
            $gradeIds[] = $gradeId;
            $label = ((string)($section->grade?->name ?? 'Grade'))
                . ' · Section '
                . (string)$section->name;
            $sectionOptions[(int)$section->id] = $label;
            $sectionsByGrade[$gradeId][] = [
                'id' => (int)$section->id,
                'name' => (string)$section->name,
                'label' => $label,
            ];
        }

        $grades = $gradeIds === []
            ? []
            : SchoolGrade::find()
                ->where(['id' => array_values(array_unique($gradeIds))])
                ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
                ->all();

        return [
            'grades' => $grades,
            'sections' => $sectionOptions,
            'sectionsByGrade' => $sectionsByGrade,
        ];
    }
}
