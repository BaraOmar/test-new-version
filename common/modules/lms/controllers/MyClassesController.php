<?php

namespace common\modules\lms\controllers;

use common\components\LmsPortal;
use common\models\User;
use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolLmsCourseTopic;
use common\modules\employee\models\SchoolLmsTopicFile;
use common\modules\employee\models\SchoolSectionMaterial;
use common\modules\employee\models\SchoolSubject;
use common\modules\employee\models\SchoolSubjectGrade;
use common\modules\employee\services\SchoolLmsCourseMaterialsService;
use common\modules\employee\services\SchoolMyClassesService;
use common\modules\employee\services\SchoolSectionMarksService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class MyClassesController extends Controller
{
    public $layout = '@common/modules/lms/views/layouts/portal';

    private ?SchoolMyClassesService $classesService = null;
    private ?SchoolSectionMarksService $marksService = null;
    private ?SchoolLmsCourseMaterialsService $materialsService = null;

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
                    'save-marks' => ['POST'],
                    'save-term-maxima' => ['POST'],
                    'create-topic' => ['POST'],
                    'rename-topic' => ['POST'],
                    'delete-topic' => ['POST'],
                    'upload-topic-file' => ['POST'],
                    'delete-topic-file' => ['POST'],
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

        if (!SchoolMyClassesService::isEnabled()) {
            throw new NotFoundHttpException('My Classes is not available.');
        }

        if (!LmsPortal::canAccessMyClasses($user)) {
            throw new ForbiddenHttpException('You do not have permission to access My Classes.');
        }

        $this->view->params['userName'] = LmsPortal::displayName($user);
        $this->view->params['userRole'] = LmsPortal::roleLabel($user);
        $this->view->params['portalUser'] = $user;
        $this->view->params['currentPage'] = $this->resolveMyClassesCurrentPage($action->id);

        return true;
    }

    private function resolveMyClassesCurrentPage(string $actionId): string
    {
        $sectionId = (int)Yii::$app->request->get('section_id', 0);
        if ($sectionId > 0) {
            return 'lms_my_classes_section_' . $sectionId;
        }

        $gradeId = (int)Yii::$app->request->get('grade_id', 0);
        if ($gradeId > 0) {
            return 'lms_my_classes_grade_' . $gradeId;
        }

        return 'lms_my_classes';
    }

    private function classesService(): SchoolMyClassesService
    {
        if ($this->classesService === null) {
            $this->classesService = new SchoolMyClassesService();
        }

        return $this->classesService;
    }

    private function marksService(): SchoolSectionMarksService
    {
        if ($this->marksService === null) {
            $this->marksService = new SchoolSectionMarksService();
        }

        return $this->marksService;
    }

    private function materialsService(): SchoolLmsCourseMaterialsService
    {
        if ($this->materialsService === null) {
            $this->materialsService = new SchoolLmsCourseMaterialsService();
        }

        return $this->materialsService;
    }

    public function actionIndex()
    {
        $user = $this->requireClassesUser();
        $subjectFilter = (int)Yii::$app->request->get('subject_id', 0);
        $gradeFilter = (int)Yii::$app->request->get('grade_id', 0);
        $sectionFilter = (int)Yii::$app->request->get('section_id', 0);
        $view = $this->normalizeClassesView((string)Yii::$app->request->get('view', ''));
        $forcedDepartmentId = 0;

        if ($sectionFilter > 0) {
            $selectedSection = SchoolSection::find()
                ->with(['grade'])
                ->where(['id' => $sectionFilter])
                ->one();
            if ($selectedSection !== null && LmsPortal::canAccessMyClassesSection($user, $selectedSection)) {
                $gradeFilter = (int)$selectedSection->school_grade_id;
                $forcedDepartmentId = (int)($selectedSection->grade->school_department_id ?? 0);
            } else {
                $sectionFilter = 0;
            }
        }

        $accessibleDepartments = LmsPortal::getAccessibleDepartmentsForMyClasses($user);
        $departmentOptions = ArrayHelper::map($accessibleDepartments, 'id', 'name');

        $departmentId = $forcedDepartmentId > 0
            ? $forcedDepartmentId
            : (int)Yii::$app->request->get('department_id', 0);
        if ($departmentId <= 0 || !LmsPortal::canViewMyClassesDepartment($user, $departmentId)) {
            $departmentId = (int)array_key_first($departmentOptions);
        }
        if ($departmentId <= 0) {
            $departmentId = (int)($accessibleDepartments[0]->id ?? 0);
        }

        $departments = $departmentId > 0
            ? $this->classesService()->findDepartmentsWithSections(
                [$departmentId],
                LmsPortal::shouldFilterMyClassesSectionsForUser($user)
                    ? LmsPortal::getAccessibleSectionIdsForMyClasses($user)
                    : null
            )
            : [];

        $gradeOptions = $this->buildGradeOptions($departments);
        if ($gradeFilter > 0 && !isset($gradeOptions[$gradeFilter])) {
            $gradeFilter = 0;
        }

        $subjectOptions = $this->buildSubjectOptions($departmentId, $gradeFilter);
        if ($subjectFilter > 0 && !isset($subjectOptions[$subjectFilter])) {
            $subjectFilter = 0;
        }

        $departments = $this->filterDepartments($departments, $subjectFilter, $gradeFilter);

        return $this->render('index', [
            'departments' => $departments,
            'subjectFilter' => $subjectFilter,
            'subjectOptions' => $subjectOptions,
            'gradeFilter' => $gradeFilter,
            'gradeOptions' => $gradeOptions,
            'departmentId' => $departmentId,
            'departmentOptions' => $departmentOptions,
            'canChooseDepartment' => $user->isSuperHrAdmin() && $departmentOptions !== [],
            'selectedDepartmentName' => $departmentOptions[$departmentId] ?? '',
            'classesService' => $this->classesService(),
            'view' => $view,
            'selectedSectionId' => $sectionFilter,
        ]);
    }

    private function normalizeClassesView(string $view): string
    {
        return in_array($view, ['marks', 'exams'], true) ? $view : '';
    }

    public function actionMarks(int $section_id, int $material_id = 0)
    {
        if (!SchoolSectionMarksService::isEnabled()) {
            throw new NotFoundHttpException('Marks are not available. Run migrations first.');
        }

        $user = $this->requireClassesUser();
        $section = $this->findAccessibleSection($section_id);
        $viewableMaterials = LmsPortal::getViewableMaterialsForSection($user, $section);

        if ($viewableMaterials === []) {
            $allMaterials = LmsPortal::getMarksMaterialsForSection($section);

            return $this->render('marks-empty', [
                'section' => $section,
                'card' => $this->classesService()->buildSectionCard($section),
                'hasCourses' => $allMaterials !== [],
                'canManageCourses' => LmsPortal::canOpenMyClassesCourseOperations($user, $section),
            ]);
        }

        if ($material_id <= 0) {
            return $this->redirect(['view', 'section_id' => (int)$section->id]);
        }

        $material = $this->findViewableMaterial($section, $material_id, $user);
        $canEditMarks = LmsPortal::canManageMaterialMarks($user, $section, $material);
        $students = $this->marksService()->findActiveStudentsForSection($section);
        $subjectMaxScore = $this->marksService()->resolveSubjectMaxScore($material, $section);
        $passThreshold = $this->marksService()->resolvePassThreshold($subjectMaxScore);
        $termMaxima = $this->marksService()->resolveTermMaxima($material, $section);
        $markRows = $this->marksService()->buildMarkRows($students, $material, $subjectMaxScore, $termMaxima);
        $materialOptions = ArrayHelper::map(
            $viewableMaterials,
            'id',
            fn(SchoolSectionMaterial $item): string => $this->materialLabel($item)
        );

        return $this->render('marks', [
            'section' => $section,
            'material' => $material,
            'card' => $this->classesService()->buildSectionCard($section),
            'students' => $students,
            'markRows' => $markRows,
            'materialOptions' => $materialOptions,
            'materialId' => (int)$material->id,
            'subjectMaxScore' => $subjectMaxScore,
            'passThreshold' => $passThreshold,
            'termMaxima' => $termMaxima,
            'canEditMarks' => $canEditMarks,
            'academicSessionLabel' => $this->marksService()->resolveAcademicSessionLabel($students),
            'subjectLabel' => $materialOptions[(int)$material->id] ?? (string)$material->name,
        ]);
    }

    public function actionSaveMarks(): Response
    {
        if (!SchoolSectionMarksService::isEnabled()) {
            throw new NotFoundHttpException('Marks are not available. Run migrations first.');
        }

        $user = $this->requireClassesUser();
        $sectionId = (int)Yii::$app->request->post('section_id', 0);
        $materialId = (int)Yii::$app->request->post('material_id', 0);
        $section = $this->findAccessibleSection($sectionId);
        $material = $this->findMarkableMaterial($section, $materialId, $user);
        $students = $this->marksService()->findActiveStudentsForSection($section);
        $subjectMaxScore = $this->marksService()->resolveSubjectMaxScore($material, $section);
        $termMaxima = $this->marksService()->resolveTermMaxima($material, $section);
        $submittedMarks = (array)Yii::$app->request->post('marks', []);

        $result = $this->marksService()->saveMarks(
            $material,
            $students,
            $submittedMarks,
            $subjectMaxScore,
            $termMaxima,
            $user
        );
        if ($result['success']) {
            Yii::$app->session->setFlash('success', (string)$result['message']);
        } else {
            Yii::$app->session->setFlash('error', (string)$result['message']);
        }

        return $this->redirect(['marks', 'section_id' => (int)$section->id, 'material_id' => (int)$material->id]);
    }

    public function actionSaveTermMaxima(): Response
    {
        if (!SchoolSectionMarksService::isEnabled()) {
            throw new NotFoundHttpException('Marks are not available. Run migrations first.');
        }

        $user = $this->requireClassesUser();
        $sectionId = (int)Yii::$app->request->post('section_id', 0);
        $materialId = (int)Yii::$app->request->post('material_id', 0);
        $section = $this->findAccessibleSection($sectionId);
        $material = $this->findMarkableMaterial($section, $materialId, $user);

        $result = $this->marksService()->saveTermMaxima($material, $section, [
            'max_first' => Yii::$app->request->post('max_first'),
            'max_second' => Yii::$app->request->post('max_second'),
            'max_third' => Yii::$app->request->post('max_third'),
            'max_final' => Yii::$app->request->post('max_final'),
        ]);

        if ($result['success']) {
            Yii::$app->session->setFlash('success', $result['message']);
        } else {
            Yii::$app->session->setFlash('error', $result['message']);
        }

        return $this->redirect(['marks', 'section_id' => (int)$section->id, 'material_id' => (int)$material->id]);
    }

    public function actionView(int $section_id)
    {
        $user = $this->requireClassesUser();
        $section = $this->findAccessibleSection($section_id);
        $viewableMaterials = LmsPortal::getViewableMaterialsForSection($user, $section);

        if ($viewableMaterials === []) {
            $allMaterials = LmsPortal::getMarksMaterialsForSection($section);

            return $this->render('marks-empty', [
                'section' => $section,
                'card' => $this->classesService()->buildSectionCard($section),
                'hasCourses' => $allMaterials !== [],
                'canManageCourses' => LmsPortal::canOpenMyClassesCourseOperations($user, $section),
            ]);
        }

        $editableMaterialIds = array_map(
            static fn(SchoolSectionMaterial $item): int => (int)$item->id,
            LmsPortal::getEditableMaterialsForSection($user, $section)
        );

        return $this->render('select-material', [
            'section' => $section,
            'card' => $this->classesService()->buildSectionCard($section),
            'materials' => $viewableMaterials,
            'materialScores' => $this->buildMaterialScoreMap($section, $viewableMaterials),
            'editableMaterialIds' => $editableMaterialIds,
            'title' => 'View Actions',
            'description' => 'Choose a course to view marks or learning materials.',
        ]);
    }

    public function actionMaterials(int $section_id, int $material_id = 0)
    {
        $user = $this->requireClassesUser();
        $section = $this->findAccessibleSection($section_id);
        $viewableMaterials = LmsPortal::getViewableMaterialsForSection($user, $section);

        if ($viewableMaterials === []) {
            $allMaterials = LmsPortal::getMarksMaterialsForSection($section);

            return $this->render('marks-empty', [
                'section' => $section,
                'card' => $this->classesService()->buildSectionCard($section),
                'hasCourses' => $allMaterials !== [],
                'canManageCourses' => LmsPortal::canOpenMyClassesCourseOperations($user, $section),
            ]);
        }

        if ($material_id <= 0) {
            return $this->redirect(['view', 'section_id' => (int)$section->id]);
        }

        $material = $this->findViewableMaterial($section, $material_id, $user);
        $canManage = LmsPortal::canManageCourseMaterials($user, $section, $material);
        $storageReady = SchoolLmsCourseMaterialsService::isEnabled();
        if ($storageReady && $canManage) {
            $this->materialsService()->ensureDefaultTopic($material, $user);
        }

        return $this->render('materials', [
            'section' => $section,
            'material' => $material,
            'card' => $this->classesService()->buildSectionCard($section),
            'subjectLabel' => $this->materialLabel($material),
            'topics' => $storageReady ? $this->materialsService()->listTopics($material) : [],
            'canManage' => $canManage,
            'storageReady' => $storageReady,
            'acceptAttribute' => SchoolLmsCourseMaterialsService::acceptAttribute(),
            'allowedExtensions' => SchoolLmsCourseMaterialsService::allowedExtensions(),
            'maxSizeLabel' => SchoolLmsCourseMaterialsService::maxSizeLabel(),
            'maxUploadBytes' => SchoolLmsCourseMaterialsService::MAX_UPLOAD_SIZE,
        ]);
    }

    public function actionCreateTopic(): Response
    {
        [$user, $section, $material] = $this->requireManageableCourse();
        $result = $this->materialsService()->createTopic(
            $material,
            (string)Yii::$app->request->post('name', ''),
            $user
        );
        $this->flashResult($result);

        return $this->redirectToMaterials($section, $material);
    }

    public function actionRenameTopic(): Response
    {
        [$user, $section, $material] = $this->requireManageableCourse();
        $topic = $this->findCourseTopic((int)Yii::$app->request->post('topic_id', 0), $material);
        $result = $this->materialsService()->renameTopic($topic, (string)Yii::$app->request->post('name', ''));
        $this->flashResult($result);

        return $this->redirectToMaterials($section, $material, (int)$topic->id);
    }

    public function actionDeleteTopic(): Response
    {
        [$user, $section, $material] = $this->requireManageableCourse();
        $topic = $this->findCourseTopic((int)Yii::$app->request->post('topic_id', 0), $material);
        $result = $this->materialsService()->deleteTopic($topic);
        $this->flashResult($result);

        return $this->redirectToMaterials($section, $material);
    }

    public function actionUploadTopicFile(): Response
    {
        [$user, $section, $material] = $this->requireManageableCourse();
        $topic = $this->findCourseTopic((int)Yii::$app->request->post('topic_id', 0), $material);
        $result = $this->materialsService()->uploadFiles(
            $topic,
            UploadedFile::getInstancesByName('files'),
            $user
        );
        $this->flashResult($result);

        return $this->redirectToMaterials($section, $material, (int)$topic->id);
    }

    public function actionDeleteTopicFile(): Response
    {
        [$user, $section, $material] = $this->requireManageableCourse();
        $file = $this->findCourseFile((int)Yii::$app->request->post('file_id', 0), $material);
        $topicId = (int)$file->topic_id;
        $result = $this->materialsService()->deleteFile($file);
        $this->flashResult($result);

        return $this->redirectToMaterials($section, $material, $topicId);
    }

    public function actionDownloadTopicFile(int $id): Response
    {
        $user = $this->requireClassesUser();
        $file = SchoolLmsTopicFile::find()
            ->with(['topic', 'course'])
            ->where(['id' => $id])
            ->one();
        if ($file === null || $file->course === null) {
            throw new NotFoundHttpException('File not found.');
        }

        $section = $this->findAccessibleSection((int)$file->course->school_section_id);
        if (!LmsPortal::canViewMaterialMarks($user, $section, $file->course)) {
            throw new ForbiddenHttpException('You do not have permission to download this file.');
        }

        return $this->materialsService()->download($file);
    }

    public function actionExams(int $section_id)
    {
        return $this->renderSectionAction($section_id, 'exams', 'Exams', 'Create and manage exams for this section.');
    }

    public function actionOperations(int $section_id): Response
    {
        $user = $this->requireClassesUser();
        $section = $this->findAccessibleSection($section_id);

        if (!LmsPortal::canOpenMyClassesCourseOperations($user, $section)) {
            throw new ForbiddenHttpException('You do not have permission to manage this section.');
        }

        return $this->redirect(['/lms/hierarchy/section', 'id' => (int)$section->id]);
    }

    private function renderSectionAction(int $sectionId, string $actionKey, string $title, string $description): string
    {
        $section = $this->findAccessibleSection($sectionId);
        $card = $this->classesService()->buildSectionCard($section);

        return $this->render('section-action', [
            'section' => $section,
            'card' => $card,
            'actionKey' => $actionKey,
            'title' => $title,
            'description' => $description,
        ]);
    }

    private function requireClassesUser(): User
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User) {
            throw new ForbiddenHttpException('Authentication is required.');
        }

        return $user;
    }

    /**
     * @return array{0:User,1:SchoolSection,2:SchoolSectionMaterial}
     */
    private function requireManageableCourse(): array
    {
        $user = $this->requireClassesUser();
        $sectionId = (int)Yii::$app->request->post('section_id', 0);
        $materialId = (int)Yii::$app->request->post('material_id', 0);
        $section = $this->findAccessibleSection($sectionId);
        $material = $this->findViewableMaterial($section, $materialId, $user);
        if (!LmsPortal::canManageCourseMaterials($user, $section, $material)) {
            throw new ForbiddenHttpException('Only the assigned subject teacher can manage learning materials for this course.');
        }

        return [$user, $section, $material];
    }

    private function findCourseTopic(int $topicId, SchoolSectionMaterial $material): SchoolLmsCourseTopic
    {
        $topic = SchoolLmsCourseTopic::find()
            ->where([
                'id' => $topicId,
                'school_section_material_id' => (int)$material->id,
            ])
            ->one();
        if ($topic === null) {
            throw new NotFoundHttpException('Topic not found.');
        }

        return $topic;
    }

    private function findCourseFile(int $fileId, SchoolSectionMaterial $material): SchoolLmsTopicFile
    {
        $file = SchoolLmsTopicFile::find()
            ->where([
                'id' => $fileId,
                'school_section_material_id' => (int)$material->id,
            ])
            ->one();
        if ($file === null) {
            throw new NotFoundHttpException('File not found.');
        }

        return $file;
    }

    /**
     * @param array{success:bool,message:string} $result
     */
    private function flashResult(array $result): void
    {
        Yii::$app->session->setFlash($result['success'] ? 'success' : 'error', (string)$result['message']);
    }

    private function redirectToMaterials(SchoolSection $section, SchoolSectionMaterial $material, int $topicId = 0): Response
    {
        $url = [
            'materials',
            'section_id' => (int)$section->id,
            'material_id' => (int)$material->id,
        ];
        if ($topicId > 0) {
            $url['#'] = 'topic-' . $topicId;
        }

        return $this->redirect($url);
    }

    private function findAccessibleSection(int $sectionId): SchoolSection
    {
        $section = SchoolSection::find()
            ->with(['grade.department', 'leadTeacher', 'materials.subject'])
            ->where(['id' => $sectionId])
            ->one();

        if ($section === null) {
            throw new NotFoundHttpException('Section not found.');
        }

        $user = $this->requireClassesUser();
        if (!LmsPortal::canAccessMyClassesSection($user, $section)) {
            throw new ForbiddenHttpException('You do not have access to this section.');
        }

        return $section;
    }

    private function findViewableMaterial(SchoolSection $section, int $materialId, User $user): SchoolSectionMaterial
    {
        $material = SchoolSectionMaterial::find()
            ->with(['subject'])
            ->where([
                'id' => $materialId,
                'school_section_id' => (int)$section->id,
            ])
            ->one();

        if ($material === null) {
            throw new NotFoundHttpException('Course not found for this section.');
        }

        if (!LmsPortal::canViewMaterialMarks($user, $section, $material)) {
            throw new ForbiddenHttpException('You do not have permission to view marks for this course.');
        }

        return $material;
    }

    private function findMarkableMaterial(SchoolSection $section, int $materialId, User $user): SchoolSectionMaterial
    {
        $material = SchoolSectionMaterial::find()
            ->with(['subject'])
            ->where([
                'id' => $materialId,
                'school_section_id' => (int)$section->id,
            ])
            ->one();

        if ($material === null) {
            throw new NotFoundHttpException('Course not found for this section.');
        }

        if (!LmsPortal::canManageMaterialMarks($user, $section, $material)) {
            throw new ForbiddenHttpException('You do not have permission to manage marks for this course.');
        }

        return $material;
    }

    /**
     * @return array<int, string>
     */
    private function buildSubjectOptions(int $departmentId, int $gradeFilter): array
    {
        if ($departmentId <= 0 || !SchoolSubject::isEnabled()) {
            return [];
        }

        if (SchoolSubjectGrade::isEnabled()) {
            $query = SchoolSubjectGrade::find()
                ->alias('sg')
                ->innerJoinWith(['subject subject', 'grade grade'])
                ->where(['subject.school_department_id' => $departmentId])
                ->orderBy(['subject.name' => SORT_ASC]);

            if ($gradeFilter > 0) {
                $query->andWhere(['sg.school_grade_id' => $gradeFilter]);
            } else {
                $query->andWhere(['grade.school_department_id' => $departmentId]);
            }

            $options = [];
            foreach ($query->all() as $subjectGrade) {
                $subject = $subjectGrade->subject;
                if ($subject === null) {
                    continue;
                }

                $options[(int)$subject->id] = (string)$subject->name;
            }

            return $options;
        }

        return ArrayHelper::map(
            SchoolSubject::find()
                ->where(['school_department_id' => $departmentId])
                ->orderBy(['name' => SORT_ASC])
                ->all(),
            'id',
            'name'
        );
    }

    /**
     * @param SchoolDepartment[] $departments
     * @return array<int, string>
     */
    private function buildGradeOptions(array $departments): array
    {
        $options = [];
        foreach ($departments as $department) {
            foreach ($department->grades as $grade) {
                $options[(int)$grade->id] = (string)$grade->name;
            }
        }

        return $options;
    }

    /**
     * @param SchoolDepartment[] $departments
     * @return SchoolDepartment[]
     */
    private function filterDepartments(array $departments, int $subjectFilter, int $gradeFilter): array
    {
        if ($subjectFilter <= 0 && $gradeFilter <= 0) {
            return $departments;
        }

        $filtered = [];

        foreach ($departments as $department) {
            $grades = [];
            foreach ($department->grades as $grade) {
                if ($gradeFilter > 0 && (int)$grade->id !== $gradeFilter) {
                    continue;
                }

                $sections = [];
                foreach ($grade->sections as $section) {
                    if ($subjectFilter <= 0 || $this->sectionHasSubject($section, $subjectFilter)) {
                        $sections[] = $section;
                    }
                }

                if ($sections !== []) {
                    $gradeClone = clone $grade;
                    $gradeClone->populateRelation('sections', $sections);
                    $grades[] = $gradeClone;
                }
            }

            if ($grades !== []) {
                $departmentClone = clone $department;
                $departmentClone->populateRelation('grades', $grades);
                $filtered[] = $departmentClone;
            }
        }

        return $filtered;
    }

    private function sectionHasSubject(SchoolSection $section, int $subjectId): bool
    {
        if ($subjectId <= 0) {
            return true;
        }

        if (!SchoolSectionMaterial::isEnabled()) {
            return false;
        }

        foreach ($section->materials as $material) {
            if ((int)($material->school_subject_id ?? 0) === $subjectId) {
                return true;
            }
        }

        return false;
    }

    private function materialLabel(SchoolSectionMaterial $material): string
    {
        $subjectName = trim((string)($material->subject?->name ?? ''));
        $materialName = trim((string)$material->name);

        return $subjectName !== '' ? $subjectName : ($materialName !== '' ? $materialName : 'Course #' . (int)$material->id);
    }

    /**
     * @param SchoolSectionMaterial[] $materials
     * @return array<int, array{max:float,pass:float}>
     */
    private function buildMaterialScoreMap(SchoolSection $section, array $materials): array
    {
        $map = [];
        foreach ($materials as $material) {
            $maxScore = $this->marksService()->resolveSubjectMaxScore($material, $section);
            $map[(int)$material->id] = [
                'max' => $maxScore,
                'pass' => $this->marksService()->resolvePassThreshold($maxScore),
            ];
        }

        return $map;
    }
}
