<?php

namespace common\modules\lms\controllers;

use common\components\LmsPortal;
use common\models\User;
use common\modules\employee\models\Employee;
use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolSectionMaterial;
use common\modules\employee\models\SchoolStudent;
use common\modules\employee\models\SchoolSubject;
use common\modules\employee\services\SchoolSectionManagementService;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class HierarchyController extends Controller
{
    public $layout = '@common/modules/lms/views/layouts/portal';

    private ?SchoolSectionManagementService $sectionService = null;

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
                    'update-section' => ['POST'],
                    'assign-class-teacher' => ['POST'],
                    'assign-material' => ['POST'],
                    'update-material' => ['POST'],
                    'delete-material' => ['POST'],
                    'assign-student' => ['POST'],
                    'remove-student' => ['POST'],
                    'save-section-details' => ['POST'],
                    'delete-section' => ['POST'],
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

        if (!SchoolSection::isEnabled()) {
            throw new NotFoundHttpException('School sections are not available.');
        }

        $this->view->params['userName'] = LmsPortal::displayName($user);
        $this->view->params['userRole'] = LmsPortal::roleLabel($user);
        $this->view->params['portalUser'] = $user;
        $this->view->params['currentPage'] = $this->resolveCurrentPage($action->id);

        return true;
    }

    private function resolveCurrentPage(string $actionId): string
    {
        if ($actionId === 'section') {
            $user = Yii::$app->user->identity;
            if ($user instanceof User && LmsPortal::getInfrastructureAccessType($user) === LmsPortal::INFRASTRUCTURE_ACCESS_STUDENT) {
                return 'lms_my_profile';
            }
            if ($user instanceof User && (int)Yii::$app->request->get('student_id', 0) > 0) {
                return 'lms_student_registry';
            }
        }

        return match ($actionId) {
            'index', 'create-section' => 'lms_hierarchy',
            'section' => 'lms_section',
            default => 'lms_hierarchy',
        };
    }

    private function sectionService(): SchoolSectionManagementService
    {
        if ($this->sectionService === null) {
            $this->sectionService = new SchoolSectionManagementService();
        }

        return $this->sectionService;
    }

    public function actionIndex()
    {
        $user = $this->requireHierarchyUser();
        $accessType = LmsPortal::getInfrastructureAccessType($user);

        if ($accessType === LmsPortal::INFRASTRUCTURE_ACCESS_STUDENT) {
            throw new ForbiddenHttpException('Students should open section details from their profile.');
        }

        if ($accessType === LmsPortal::INFRASTRUCTURE_ACCESS_CLASS_TEACHER) {
            $sectionIds = LmsPortal::getAccessibleSectionIds($user);
            if (count($sectionIds) === 1) {
                return $this->redirect(['section', 'id' => $sectionIds[0]]);
            }
            if ($sectionIds === []) {
                throw new ForbiddenHttpException('You are not assigned to a section.');
            }

            $sections = SchoolSection::find()
                ->with(['grade.department', 'leadTeacher'])
                ->where(['id' => $sectionIds])
                ->orderBy(['name' => SORT_ASC])
                ->all();

            return $this->render('class-teacher-sections', [
                'sections' => $sections,
            ]);
        }

        $search = trim((string)Yii::$app->request->get('search', ''));
        $departments = LmsPortal::getAccessibleDepartmentsForHierarchy($user);
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $departments = array_values(array_filter($departments, static function ($department) use ($needle) {
                if (mb_strpos(mb_strtolower((string)$department->name), $needle) !== false) {
                    return true;
                }
                foreach ($department->grades as $grade) {
                    if (mb_strpos(mb_strtolower((string)$grade->name), $needle) !== false) {
                        return true;
                    }
                    foreach ($grade->sections as $section) {
                        if (mb_strpos(mb_strtolower((string)$section->name), $needle) !== false) {
                            return true;
                        }
                    }
                }

                return false;
            }));
        }

        return $this->render('index', [
            'departments' => $departments,
            'search' => $search,
        ]);
    }

    public function actionSection(int $id)
    {
        $user = $this->requireHierarchyUser();
        $section = $this->sectionService()->findSection($id);
        if (!LmsPortal::canViewSection($user, $section)) {
            throw new ForbiddenHttpException('You do not have permission to view this section.');
        }

        $accessMode = $this->resolveSectionAccessMode($user, $section);
        $editorData = $this->sectionService()->buildSectionEditorData($section);
        $department = $section->grade?->department;

        $rosterSearch = trim((string)Yii::$app->request->get('roster', ''));
        $students = [];
        $studentPagination = new Pagination(['totalCount' => 0, 'pageSize' => 8, 'pageParam' => 'page']);
        $eligibleStudents = [];

        if ($accessMode !== 'student') {
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
            $eligibleStudents = $this->sectionService()->findEligibleStudentsForSection($section);
        }

        return $this->render('section', array_merge([
            'section' => $section,
            'department' => $department,
            'materials' => SchoolSectionMaterial::isEnabled() ? $section->materials : [],
            'students' => $students,
            'studentPagination' => $studentPagination,
            'rosterSearch' => $rosterSearch,
            'eligibleStudents' => $eligibleStudents,
            'accessMode' => $accessMode,
            'isNewSection' => false,
            'toggleStudent' => $this->resolveToggleStudent($user, $section, $accessMode),
        ], $editorData));
    }

    public function actionCreateSection(int $grade_id)
    {
        $user = $this->requireDepartmentLeader();
        if (!LmsPortal::canCreateSectionInGrade($user, $grade_id)) {
            throw new ForbiddenHttpException('You cannot create sections in this grade.');
        }

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

        if ($section->load(Yii::$app->request->post()) && $section->save()) {
            Yii::$app->session->setFlash('success', 'Section created. You can now assign materials and students.');
            return $this->redirect(['section', 'id' => (int)$section->id]);
        }

        $editorData = $this->sectionService()->buildSectionEditorData($section);
        $studentPagination = new Pagination(['totalCount' => 0, 'pageSize' => 8]);

        return $this->render('section', array_merge([
            'section' => $section,
            'department' => $grade->department,
            'materials' => [],
            'students' => [],
            'studentPagination' => $studentPagination,
            'rosterSearch' => '',
            'eligibleStudents' => [],
            'accessMode' => 'department_leader',
            'isNewSection' => true,
        ], $editorData));
    }

    public function actionUpdateSection(int $id): Response
    {
        $this->requireSectionManager($id);
        $section = $this->sectionService()->findSection($id);
        $lockedGradeId = (int)$section->school_grade_id;

        if ($section->load(Yii::$app->request->post())) {
            $section->school_grade_id = $lockedGradeId;
            if ($section->save()) {
                Yii::$app->session->setFlash('success', 'Section updated.');
            } else {
                Yii::$app->session->setFlash('error', implode(' ', $section->getFirstErrors()) ?: 'Unable to update section.');
            }
        } else {
            Yii::$app->session->setFlash('error', 'Unable to update section.');
        }

        return $this->redirect(['section', 'id' => (int)$section->id]);
    }

    public function actionAssignClassTeacher(int $id): Response
    {
        $this->requireDepartmentLeaderForSection($id);
        $section = $this->sectionService()->findSection($id);
        $teacherId = (int)Yii::$app->request->post('lead_teacher_id', 0);
        $sectionDepartmentId = LmsPortal::getSectionDepartmentId($section);

        if ($teacherId > 0) {
            $teacher = Employee::findOne(['id' => $teacherId]);
            if (
                $teacher === null
                || $sectionDepartmentId <= 0
                || (int)($teacher->school_department_id ?? 0) !== $sectionDepartmentId
            ) {
                Yii::$app->session->setFlash('error', 'Selected teacher must belong to this section\'s department.');
                return $this->redirect(['section', 'id' => (int)$section->id]);
            }
        }

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
        $this->requireSectionManager($id);
        if (!SchoolSectionMaterial::isEnabled()) {
            Yii::$app->session->setFlash('error', 'Materials are not available.');
            return $this->redirect(['section', 'id' => $id]);
        }

        $section = $this->sectionService()->findSection($id);
        $model = new SchoolSectionMaterial(['school_section_id' => (int)$section->id]);
        if ($model->load(Yii::$app->request->post())) {
            $model->school_section_id = (int)$section->id;
            if ($model->school_subject_id) {
                $subject = SchoolSubject::findOne(['id' => (int)$model->school_subject_id]);
                if ($subject !== null) {
                    if (trim($model->name) === '') {
                        $model->name = (string)$subject->name;
                    }
                    $model->icon = $this->sectionService()->iconForSubjectName($subject->name);
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
        $this->requireSectionManager($id);
        $section = $this->sectionService()->findSection($id);
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
        $this->requireSectionManager($id);
        $model = SchoolSectionMaterial::findOne(['id' => $materialId, 'school_section_id' => $id]);
        if ($model !== null) {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Material removed.');
        }

        return $this->redirect(['section', 'id' => $id]);
    }

    public function actionAssignStudent(int $id): Response
    {
        $this->requireSectionManager($id);
        if (!SchoolStudent::isEnabled()) {
            Yii::$app->session->setFlash('error', 'Student roster is not available.');
            return $this->redirect(['section', 'id' => $id]);
        }

        $section = $this->sectionService()->findSection($id);
        $studentId = (int)Yii::$app->request->post('student_id', 0);
        $errors = [];
        if ($studentId <= 0 || !$this->sectionService()->assignStudentToSection($section, $studentId, $errors)) {
            Yii::$app->session->setFlash('error', implode(' ', $errors) ?: 'Select a student to assign.');
            return $this->redirect(['section', 'id' => (int)$section->id]);
        }

        Yii::$app->session->setFlash('success', 'Student assigned to section.');
        return $this->redirect(['section', 'id' => (int)$section->id]);
    }

    public function actionRemoveStudent(int $id, int $studentId): Response
    {
        $this->requireSectionManager($id);
        if ($this->sectionService()->removeStudentFromSection($id, $studentId)) {
            Yii::$app->session->setFlash('success', 'Student removed from section.');
        }

        return $this->redirect(['section', 'id' => (int)$id]);
    }

    public function actionSaveSectionDetails(int $id): Response
    {
        $user = $this->requireSectionManager($id);
        $section = $this->sectionService()->findSection($id);
        $canEditClassTeacher = LmsPortal::canManageSectionAsDepartmentLeader($user, $section);
        $errors = [];
        $transaction = Yii::$app->db->beginTransaction();

        try {
            if ($canEditClassTeacher) {
                $teacherId = (int)Yii::$app->request->post('lead_teacher_id', 0);
                $section->lead_teacher_id = $teacherId > 0 ? $teacherId : null;
                if (!$section->save()) {
                    $errors[] = implode(' ', $section->getFirstErrors()) ?: 'Unable to update class teacher.';
                }
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
                    if (!$this->sectionService()->populateAndSaveMaterial($material, $row)) {
                        $errors[] = implode(' ', $material->getFirstErrors()) ?: 'Unable to update material.';
                    }
                }

                foreach ((array)Yii::$app->request->post('new_materials', []) as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $material = new SchoolSectionMaterial(['school_section_id' => (int)$section->id]);
                    if (!$this->sectionService()->populateAndSaveMaterial($material, $row, true)) {
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
                    $this->sectionService()->assignStudentToSection($section, $studentId, $errors);
                }
            }

            if ($errors !== []) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', implode(' ', array_unique($errors)));
            } else {
                $section->syncEnrollmentCount();
                $section->save(false, ['current_enrollment', 'updated_at']);
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Section details saved.');
            }
        } catch (\Throwable) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Unable to save section details.');
        }

        return $this->redirect(['section', 'id' => (int)$section->id]);
    }

    public function actionDeleteSection(int $id): Response
    {
        $user = $this->requireDepartmentLeaderForSection($id);
        $section = SchoolSection::findOne(['id' => $id]);
        if ($section !== null) {
            $section->delete();
            Yii::$app->session->setFlash('success', 'Section deleted.');
        }

        return $this->redirect(['index']);
    }

    private function requireHierarchyUser(): User
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !LmsPortal::canAccessInfrastructureHierarchy($user)) {
            throw new ForbiddenHttpException('You do not have permission to access infrastructure hierarchy.');
        }

        return $user;
    }

    private function requireDepartmentLeader(): User
    {
        $user = $this->requireHierarchyUser();
        if (LmsPortal::getInfrastructureAccessType($user) !== LmsPortal::INFRASTRUCTURE_ACCESS_DEPARTMENT_LEADER) {
            throw new ForbiddenHttpException('Department leadership access is required.');
        }

        return $user;
    }

    private function requireDepartmentLeaderForSection(int $sectionId): User
    {
        $user = $this->requireDepartmentLeader();
        $section = $this->sectionService()->findSection($sectionId);
        if (!LmsPortal::canManageSectionAsDepartmentLeader($user, $section)) {
            throw new ForbiddenHttpException('You cannot manage this section.');
        }

        return $user;
    }

    private function requireSectionManager(int $sectionId): User
    {
        $user = $this->requireHierarchyUser();
        $section = $this->sectionService()->findSection($sectionId);
        if (
            !LmsPortal::canManageSectionAsDepartmentLeader($user, $section)
            && !LmsPortal::canManageSectionAsClassTeacher($user, $section)
        ) {
            throw new ForbiddenHttpException('You cannot manage this section.');
        }

        return $user;
    }

    private function resolveSectionAccessMode(User $user, SchoolSection $section): string
    {
        if (LmsPortal::getInfrastructureAccessType($user) === LmsPortal::INFRASTRUCTURE_ACCESS_STUDENT) {
            return 'student';
        }
        if (LmsPortal::canManageSectionAsDepartmentLeader($user, $section)) {
            return 'department_leader';
        }
        if (LmsPortal::canManageSectionAsClassTeacher($user, $section)) {
            return 'class_teacher';
        }

        return 'student';
    }

    private function resolveToggleStudent(User $user, SchoolSection $section, string $accessMode): ?SchoolStudent
    {
        if ($accessMode === 'student') {
            $student = LmsPortal::findLinkedStudent($user);

            return $student !== null && (int)$student->school_section_id === (int)$section->id
                ? $student
                : null;
        }

        $studentId = (int)Yii::$app->request->get('student_id', 0);
        if ($studentId <= 0) {
            return null;
        }

        $student = SchoolStudent::findOne($studentId);
        if (
            $student === null
            || (int)$student->school_section_id !== (int)$section->id
            || !LmsPortal::canToggleStudentProfileSection($user, $student)
        ) {
            return null;
        }

        return $student;
    }
}
