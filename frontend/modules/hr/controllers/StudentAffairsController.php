<?php

namespace frontend\modules\hr\controllers;

use common\models\User;
use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolStudent;
use common\modules\employee\services\StudentPortalEmailService;
use common\modules\finance\services\FinanceParentEnrollmentService;
use common\modules\finance\services\FinanceParentProfileService;
use common\modules\transport\models\TransportArea;
use common\traits\HrAccessTrait;
use frontend\modules\hr\models\StudentPortalForm;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class StudentAffairsController extends Controller
{
    use HrAccessTrait;

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
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireStudentAffairsEnabled();

        return $this->redirect(['students']);
    }

    public function actionStudents()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireStudentAffairsEnabled();
        $this->view->params['bodyClass'] = 'bg-slate-50 text-slate-900 min-h-screen';

        $search = trim((string)Yii::$app->request->get('search', ''));
        $departmentFilter = (int)Yii::$app->request->get('department_id', 0);
        $gradeFilter = (int)Yii::$app->request->get('grade_id', 0);
        $sectionFilter = (int)Yii::$app->request->get('section_id', 0);
        $statusFilter = trim((string)Yii::$app->request->get('status', ''));

        if ($gradeFilter > 0 && $departmentFilter > 0) {
            $grade = SchoolGrade::findOne($gradeFilter);
            if ($grade === null || (int)$grade->school_department_id !== $departmentFilter) {
                $gradeFilter = 0;
                $sectionFilter = 0;
            }
        }
        if ($sectionFilter > 0 && $gradeFilter > 0) {
            $section = SchoolSection::findOne($sectionFilter);
            if ($section === null || (int)$section->school_grade_id !== $gradeFilter) {
                $sectionFilter = 0;
            }
        }

        $hasActiveFilters = $search !== ''
            || $departmentFilter > 0
            || $gradeFilter > 0
            || $sectionFilter > 0
            || $statusFilter !== '';

        $query = SchoolStudent::find()
            ->alias('student')
            ->joinWith([
                'grade grade',
                'grade.department',
                'section section',
                'section.grade sectionGrade',
                'section.grade.department',
            ]);

        if ($hasActiveFilters) {
            $query->orderBy(['student.full_name' => SORT_ASC]);
        } else {
            $query->orderBy(['student.id' => SORT_DESC]);
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
        if ($departmentFilter > 0) {
            $query->andWhere([
                'or',
                ['grade.school_department_id' => $departmentFilter],
                ['sectionGrade.school_department_id' => $departmentFilter],
            ]);
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

        $gradesQuery = SchoolGrade::find()->orderBy(['school_department_id' => SORT_ASC, 'sort_order' => SORT_ASC, 'name' => SORT_ASC]);
        if ($departmentFilter > 0) {
            $gradesQuery->andWhere(['school_department_id' => $departmentFilter]);
        }

        return $this->render('students', [
            'students' => $students,
            'pagination' => $pagination,
            'totalCount' => $totalCount,
            'search' => $search,
            'departmentFilter' => $departmentFilter,
            'gradeFilter' => $gradeFilter,
            'sectionFilter' => $sectionFilter,
            'statusFilter' => $statusFilter,
            'departments' => SchoolDepartment::find()->orderBy(['name' => SORT_ASC])->all(),
            'grades' => $gradesQuery->all(),
            'sections' => $this->loadSectionOptions($gradeFilter),
            'gradesByDepartment' => $this->buildGradesByDepartment(),
            'sectionsByGrade' => $this->buildSectionsByGrade(),
            'userName' => $this->userDisplayName(),
            'currentPage' => 'student_affairs_registry',
        ]);
    }

    public function actionCreate()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireStudentAffairsEnabled();
        $this->view->params['bodyClass'] = 'bg-slate-50 text-slate-900 min-h-screen';

        $model = new SchoolStudent([
            'status' => SchoolStudent::STATUS_ACTIVE,
            'academic_year' => SchoolStudent::defaultAcademicYear(),
        ]);
        $portalForm = StudentPortalForm::forStudent($model, false);

        if (Yii::$app->request->isPost && $this->saveEnrollment($model, $portalForm, false)) {
            Yii::$app->session->setFlash('success', 'Student profile saved successfully.');
            return $this->redirect(['view', 'id' => (int)$model->id]);
        }

        $this->ensureGeneratedPortalPassword($portalForm);

        return $this->renderEnrollmentForm($model, false, $portalForm);
    }

    public function actionView(int $id)
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireStudentAffairsEnabled();
        $this->view->params['bodyClass'] = 'bg-slate-50 text-slate-900 min-h-screen';

        $returnContext = $this->resolveViewReturnContext();

        return $this->render('view', [
            'model' => $this->findStudent($id),
            'userName' => $this->userDisplayName(),
            'currentPage' => $returnContext['currentPage'],
            'backUrl' => $returnContext['url'],
            'backLabel' => $returnContext['label'],
        ]);
    }

    public function actionUpdate(int $id)
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireStudentAffairsEnabled();
        $this->view->params['bodyClass'] = 'bg-slate-50 text-slate-900 min-h-screen';

        $model = $this->findStudent($id);
        $portalForm = StudentPortalForm::forStudent($model, true);
        if (Yii::$app->request->isPost && $this->saveEnrollment($model, $portalForm, true)) {
            Yii::$app->session->setFlash('success', 'Student profile updated successfully.');
            return $this->redirect(['students']);
        }

        $this->ensureGeneratedPortalPassword($portalForm);

        return $this->renderEnrollmentForm($model, true, $portalForm);
    }

    public function actionDelete(int $id): Response
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireStudentAffairsEnabled();

        $confirmation = strtolower(trim((string)Yii::$app->request->post('confirmation', '')));
        if ($confirmation !== 'delete') {
            Yii::$app->session->setFlash('error', 'Type delete to confirm removing this student.');

            return $this->redirect(['students']);
        }

        $model = $this->findStudent($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Student removed.');

        return $this->redirect(['students']);
    }

    public function actionExportExcel()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireStudentAffairsEnabled();

        $students = SchoolStudent::find()
            ->joinWith(['grade grade', 'section section', 'section.grade sectionGrade'])
            ->orderBy(['full_name' => SORT_ASC])
            ->all();

        $rows = [['Registration #', 'Full Name', 'Gender', 'Nationality', 'Birth Date', 'Department', 'Grade', 'Academic Year', 'Phone', 'Email', 'Status']];
        foreach ($students as $student) {
            $rows[] = [
                $student->getRegistrationLabel(),
                $student->getDisplayFullName(),
                $student->getGenderLabel(),
                $student->nationality ?: '—',
                $student->birth_date ?: '—',
                $student->getDepartmentLabel(),
                $student->grade?->name ?: ($student->section?->grade?->name ?: '—'),
                $student->academic_year ?: '—',
                $student->phone ?: '—',
                $student->email ?: '—',
                $student->getStatusLabel(),
            ];
        }

        $content = '';
        foreach ($rows as $row) {
            $content .= implode(',', array_map([$this, 'csvEscape'], $row)) . "\n";
        }

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="students-' . date('Y-m-d') . '.csv"');

        return "\xEF\xBB\xBF" . $content;
    }

    public function actionExportPdf()
    {
        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireStudentAffairsEnabled();
        $this->layout = false;

        $students = SchoolStudent::find()
            ->joinWith(['grade grade', 'section section', 'section.grade sectionGrade'])
            ->orderBy(['full_name' => SORT_ASC])
            ->all();

        return $this->render('export-pdf', ['students' => $students]);
    }

    private function saveEnrollment(SchoolStudent $model, StudentPortalForm $portalForm, bool $isEdit): bool
    {
        if (!$model->load(Yii::$app->request->post())) {
            return false;
        }

        $portalForm->isEdit = $isEdit;
        $portalForm->existingUserId = SchoolStudent::hasColumn('user_id') && (int)$model->user_id > 0
            ? (int)$model->user_id
            : $portalForm->existingUserId;
        $portalForm->load(Yii::$app->request->post());

        if (SchoolStudent::hasColumn('user_id') && trim((string)$portalForm->portal_password) === '') {
            if (!$isEdit || $portalForm->existingUserId === null) {
                $portalForm->portal_password = (new FinanceParentProfileService())->generatePreviewPassword();
            }
        }

        $this->applyEnrollmentAssignment($model);
        if ($model->hasErrors()) {
            return false;
        }

        if (SchoolStudent::hasColumn('user_id')) {
            if ($isEdit) {
                if (!$portalForm->validate()) {
                    return false;
                }
            } elseif (!$portalForm->validate(['portal_password'])) {
                // Portal email is generated after save, once the student ID exists.
                return false;
            }
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save()) {
                $transaction->rollBack();

                return false;
            }

            if (SchoolStudent::hasColumn('user_id')) {
                if (!$isEdit) {
                    $portalForm->portal_email = $this->portalEmailService()->generateUniquePortalEmail(
                        $model,
                        $portalForm->existingUserId
                    );
                    if (!$portalForm->validate()) {
                        $transaction->rollBack();

                        return false;
                    }
                }

                if (!$this->savePortalCredentials($model, $portalForm)) {
                    $transaction->rollBack();

                    return false;
                }
            }

            if (!$isEdit) {
                $financeChildId = (int)Yii::$app->request->post('finance_parent_child_id', 0);
                if ($financeChildId > 0) {
                    (new FinanceParentEnrollmentService())->linkChildToStudent($financeChildId, (int)$model->id);
                }
            }

            $transaction->commit();

            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            $message = 'Unable to save student profile.';
            if (stripos($e->getMessage(), 'ux_user_phone') !== false || stripos($e->getMessage(), "Duplicate entry '' for key") !== false) {
                $message = 'Unable to create the student portal login. Please try saving again.';
            }
            $model->addError('full_name', $message);

            return false;
        }
    }

    private function savePortalCredentials(SchoolStudent $model, StudentPortalForm $portalForm): bool
    {
        if (!SchoolStudent::hasColumn('user_id')) {
            return true;
        }

        $user = null;
        if ((int)$model->user_id > 0) {
            $user = User::findOne((int)$model->user_id);
        }

        if ($user === null) {
            $user = new User();
        }

        $now = time();
        if ($user->isNewRecord) {
            if ($user->hasAttribute('created_at')) {
                $user->created_at = $now;
            }
        }
        if ($user->hasAttribute('updated_at')) {
            $user->updated_at = $now;
        }
        if ($user->hasAttribute('username')) {
            $user->username = trim($portalForm->portal_email);
        }
        if ($user->hasAttribute('email')) {
            $user->email = trim($portalForm->portal_email);
        }
        if ($user->hasAttribute('role')) {
            $user->role = 'student';
        }
        if ($user->hasAttribute('status')) {
            $user->status = User::STATUS_ACTIVE;
        }
        if (method_exists($user, 'generateAuthKey') && ($user->isNewRecord || !$user->auth_key)) {
            $user->generateAuthKey();
        }
        // Login is email + password only. Do not copy the student phone onto the user row.
        if ($user->hasAttribute('phone')) {
            $user->phone = null;
        }

        $password = trim($portalForm->portal_password);
        $currentPlain = SchoolStudent::hasColumn('login_password_plain')
            ? trim((string)($model->login_password_plain ?? ''))
            : '';
        if ($password === '' && $user->isNewRecord) {
            $password = (new FinanceParentProfileService())->generatePreviewPassword();
            $portalForm->portal_password = $password;
        }

        $passwordChanged = false;
        if ($password !== '' && ($user->isNewRecord || $password !== $currentPlain)) {
            if (method_exists($user, 'assignSystemPassword')) {
                $user->assignSystemPassword($password);
            } else {
                if (method_exists($user, 'setPassword')) {
                    $user->setPassword($password);
                }
                if (method_exists($user, 'generateAuthKey')) {
                    $user->generateAuthKey();
                }
            }
            $passwordChanged = true;
        } elseif ($user->isNewRecord) {
            $portalForm->addError('portal_password', 'Portal Password cannot be blank.');

            return false;
        }

        if (!$user->save(false)) {
            foreach ($user->getFirstErrors() as $message) {
                $portalForm->addError('portal_email', $message);
            }

            return false;
        }

        if ((int)$model->user_id !== (int)$user->id) {
            $model->user_id = (int)$user->id;
            if (!$model->save(false, ['user_id', 'updated_at'])) {
                foreach ($model->getFirstErrors() as $message) {
                    $portalForm->addError('portal_email', $message);
                }

                return false;
            }
        }

        if ($passwordChanged && $password !== '' && SchoolStudent::hasColumn('login_password_plain')) {
            $model->login_password_plain = $password;
            if (!$model->save(false, ['login_password_plain', 'updated_at'])) {
                foreach ($model->getFirstErrors() as $message) {
                    $portalForm->addError('portal_password', $message);
                }

                return false;
            }
        }

        $portalForm->existingUserId = (int)$user->id;

        return true;
    }

    private function applyEnrollmentAssignment(SchoolStudent $model): void
    {
        $gradeId = (int)Yii::$app->request->post('enrollment_grade_id', 0);
        $departmentId = (int)Yii::$app->request->post('enrollment_department_id', 0);

        if (!SchoolStudent::hasColumn('school_grade_id')) {
            if ($gradeId > 0) {
                $model->addError('school_section_id', 'Run database migrations to enable grade-based enrollment.');
            }

            return;
        }

        if ($departmentId <= 0) {
            $model->addError('school_grade_id', 'Department cannot be blank.');

            return;
        }

        if ($gradeId <= 0) {
            $model->addError('school_grade_id', 'Grade Level cannot be blank.');

            return;
        }

        $grade = SchoolGrade::findOne(['id' => $gradeId]);
        if ($grade === null) {
            $model->addError('school_grade_id', 'Selected grade is invalid.');

            return;
        }

        if ($departmentId > 0 && (int)$grade->school_department_id !== $departmentId) {
            $model->addError('school_grade_id', 'Selected grade does not belong to the chosen department.');

            return;
        }

        $model->school_grade_id = $gradeId;
        $model->school_section_id = null;

        if (SchoolStudent::hasColumn('class_name')) {
            $model->class_name = trim((string)($grade->department?->name ?? '')) ?: null;
        }
    }

    private function portalEmailService(): StudentPortalEmailService
    {
        return new StudentPortalEmailService();
    }

    private function renderEnrollmentForm(SchoolStudent $model, bool $isEdit, StudentPortalForm $portalForm)
    {
        $selectedDepartmentId = (int)(
            $model->grade?->school_department_id
            ?? $model->section?->grade?->school_department_id
            ?? Yii::$app->request->post('enrollment_department_id', 0)
        );
        $selectedGradeId = (int)(
            $model->school_grade_id
            ?? $model->section?->school_grade_id
            ?? Yii::$app->request->post('enrollment_grade_id', 0)
        );

        return $this->render('form', [
            'model' => $model,
            'portalForm' => $portalForm,
            'isEdit' => $isEdit,
            'departments' => SchoolDepartment::find()->orderBy(['name' => SORT_ASC])->all(),
            'gradesByDepartment' => $this->buildGradesByDepartment(),
            'selectedDepartmentId' => $selectedDepartmentId,
            'selectedGradeId' => $selectedGradeId,
            'financeParentOptions' => !$isEdit ? (new FinanceParentEnrollmentService())->buildEnrollmentOptions() : [],
            'residenceAreaOptions' => $this->loadResidenceAreaOptions($model),
            'userName' => $this->userDisplayName(),
            'currentPage' => $isEdit ? 'student_affairs_registry' : 'student_affairs_create',
        ]);
    }

    private function ensureGeneratedPortalPassword(StudentPortalForm $portalForm): void
    {
        if (!SchoolStudent::hasColumn('user_id')) {
            return;
        }

        if (trim((string)$portalForm->portal_password) !== '') {
            return;
        }

        $portalForm->portal_password = (new FinanceParentProfileService())->generatePreviewPassword();
    }

    /**
     * @return array<int, string>
     */
    private function loadResidenceAreaOptions(SchoolStudent $model): array
    {
        if (!TransportArea::isEnabled() || !SchoolStudent::hasColumn('residence_area_id')) {
            return [];
        }

        $query = TransportArea::find()
            ->where(['is_active' => true])
            ->orderBy(['name' => SORT_ASC]);

        $selectedId = (int)($model->residence_area_id ?? 0);
        if ($selectedId > 0) {
            $query->orWhere(['id' => $selectedId]);
        }

        $options = [];
        foreach ($query->all() as $area) {
            $label = (string)$area->name;
            if (!(bool)$area->is_active) {
                $label .= ' (inactive)';
            }
            $options[(int)$area->id] = $label;
        }

        return $options;
    }

    private function findStudent(int $id): SchoolStudent
    {
        $with = ['grade.department', 'section.grade.department', 'user'];
        if (SchoolStudent::hasColumn('residence_area_id')) {
            $with[] = 'residenceArea';
        }

        $model = SchoolStudent::find()->with($with)->where(['id' => $id])->one();
        if ($model === null) {
            throw new NotFoundHttpException('Student not found.');
        }

        return $model;
    }

    private function requireStudentAffairsEnabled(): void
    {
        if (!SchoolStudent::isEnabled() || !SchoolDepartment::isEnabled()) {
            throw new NotFoundHttpException('Student Affairs is not available. Run database migrations first.');
        }
    }

    /**
     * @return array<int, string>
     */
    private function loadSectionOptions(int $gradeFilter = 0): array
    {
        $query = SchoolSection::find()->alias('section')->joinWith(['grade grade'])->orderBy(['grade.name' => SORT_ASC, 'section.name' => SORT_ASC]);
        if ($gradeFilter > 0) {
            $query->andWhere(['section.school_grade_id' => $gradeFilter]);
        }

        $options = [];
        foreach ($query->all() as $section) {
            $label = $gradeFilter > 0
                ? 'Section ' . $section->name
                : ((string)($section->grade?->name ?? 'Grade') . ' · Section ' . $section->name);
            $options[(int)$section->id] = $label;
        }

        return $options;
    }

    /**
     * @return array<int, array<int, array{id:int,name:string}>>
     */
    private function buildGradesByDepartment(): array
    {
        $map = [];
        foreach (SchoolGrade::find()->orderBy(['school_department_id' => SORT_ASC, 'sort_order' => SORT_ASC, 'name' => SORT_ASC])->all() as $grade) {
            $departmentId = (int)$grade->school_department_id;
            $map[$departmentId][] = [
                'id' => (int)$grade->id,
                'name' => (string)$grade->name,
            ];
        }

        return $map;
    }

    /**
     * @return array<int, array<int, array{id:int,name:string}>>
     */
    private function buildSectionsByGrade(): array
    {
        $map = [];
        foreach (SchoolSection::find()->with('grade')->orderBy(['school_grade_id' => SORT_ASC, 'name' => SORT_ASC])->all() as $section) {
            $gradeId = (int)$section->school_grade_id;
            $map[$gradeId][] = [
                'id' => (int)$section->id,
                'name' => (string)$section->name,
            ];
        }

        return $map;
    }

    private function csvEscape(string $value): string
    {
        $value = str_replace('"', '""', $value);

        return '"' . $value . '"';
    }

    private function userDisplayName(): string
    {
        $user = Yii::$app->user->identity;
        $name = '';
        if ($user !== null && method_exists($user, 'getDisplayName')) {
            $name = trim((string)$user->getDisplayName());
        }

        return $name !== '' ? $name : 'Admin User';
    }

    /**
     * @return array{url:string,label:string,currentPage:string}
     */
    private function resolveViewReturnContext(?string $return = null): array
    {
        $default = [
            'url' => Url::to(['/hr/student-affairs/students']),
            'label' => 'Back to Registry',
            'currentPage' => 'student_affairs_registry',
        ];

        $return = trim((string)($return ?? Yii::$app->request->get('return', '')));
        if ($return !== '' && $this->isSafeReturnUrl($return)) {
            $route = $this->extractReturnRoute($return);
            if ($route === 'hr/school-department/section') {
                return [
                    'url' => $return,
                    'label' => 'Back to Section',
                    'currentPage' => 'school_department_hierarchy',
                ];
            }
            if ($route === 'hr/student-affairs/students') {
                return [
                    'url' => $return,
                    'label' => 'Back to Registry',
                    'currentPage' => 'student_affairs_registry',
                ];
            }

            return [
                'url' => $return,
                'label' => 'Back',
                'currentPage' => 'student_affairs_registry',
            ];
        }

        $from = trim((string)Yii::$app->request->get('from', ''));
        if ($from === 'section') {
            $sectionId = (int)Yii::$app->request->get('section_id', 0);
            if ($sectionId > 0) {
                return [
                    'url' => Url::to(['/hr/school-department/section', 'id' => $sectionId]),
                    'label' => 'Back to Section',
                    'currentPage' => 'school_department_hierarchy',
                ];
            }
        }

        if ($from === 'students') {
            return $default;
        }

        return $default;
    }

    private function isSafeReturnUrl(string $return): bool
    {
        if ($return === '' || str_contains($return, '://') || str_starts_with($return, '//')) {
            return false;
        }

        if (!str_starts_with($return, '/')) {
            return false;
        }

        $route = $this->extractReturnRoute($return);

        return $route !== null && str_starts_with($route, 'hr/');
    }

    private function extractReturnRoute(string $return): ?string
    {
        $path = parse_url($return, PHP_URL_PATH);
        if (is_string($path) && preg_match('#^/hr/.+#', $path) === 1) {
            return ltrim($path, '/');
        }

        $query = parse_url($return, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return null;
        }

        parse_str($query, $params);
        if (empty($params['r'])) {
            return null;
        }

        return trim(urldecode((string)$params['r']), '/');
    }
}
