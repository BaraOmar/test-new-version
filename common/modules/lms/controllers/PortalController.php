<?php

namespace common\modules\lms\controllers;

use common\components\LmsPortal;
use common\components\LoginPortal;
use common\models\LoginForm;
use common\models\User;
use common\modules\employee\models\Employee;
use common\modules\employee\models\SchoolStudent;
use common\modules\finance\models\FinanceAccountParentProfile;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class PortalController extends Controller
{
    public $layout = '@frontend/views/layouts/auth';

    public function init()
    {
        parent::init();
        $this->viewPath = '@common/modules/lms/views/portal';
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['index'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'student', 'teacher', 'parent', 'profile', 'change-password'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): Response
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['login']);
        }

        return $this->redirect(LmsPortal::homeRoute());
    }

    public function actionLogin()
    {
        $this->layout = '@frontend/views/layouts/culturehr-login';
        Yii::$app->response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        Yii::$app->response->headers->set('Pragma', 'no-cache');

        $switching = (bool)Yii::$app->request->get('switch', false);
        if (!Yii::$app->user->isGuest) {
            $current = Yii::$app->user->identity;
            if (!$switching && $current instanceof User && LmsPortal::canAccessLms($current)) {
                return $this->redirect(LmsPortal::homeRoute($current));
            }
            Yii::$app->user->logout();
        }

        $model = new LoginForm();
        $model->rememberMe = false;
        if ($model->load(Yii::$app->request->post())) {
            $email = trim((string)$model->email);
            $password = (string)$model->password;
            if ($email === '' || $password === '') {
                $model->password = '';
                $model->addError('email', 'Email and Password are required.');

                return $this->renderLmsLogin($model);
            }

            if ($model->login()) {
                $user = $model->getIdentity();
                $denial = $this->lmsLoginDenialMessage($user);
                if ($denial !== null) {
                    Yii::$app->user->logout();
                    $model->password = '';
                    $model->email = '';
                    $model->addError('email', $denial);

                    return $this->renderLmsLogin($model);
                }

                LoginPortal::setEmployeePortal(false);

                return $this->redirect(LmsPortal::homeRoute($user instanceof User ? $user : null));
            }

            $model->password = '';
            $model->clearErrors();
            $model->addError('password', 'Wrong login/password');
        }

        return $this->renderLmsLogin($model);
    }

    /**
     * @return string|null Odoo-matching denial copy, or null when LMS login is allowed.
     */
    private function lmsLoginDenialMessage(?User $user): ?string
    {
        if ($user instanceof User && LmsPortal::canAccessLms($user)) {
            return null;
        }

        if ($user instanceof User && LmsPortal::isTeacherEmployee($user) && !LmsPortal::canAccessTeacherLms($user)) {
            return 'Your teacher account is inactive. Contact your school administrator.';
        }

        if ($user instanceof User && $user->isStudent()) {
            $student = LmsPortal::findLinkedStudent($user);
            if ($student !== null && $student->status !== SchoolStudent::STATUS_ACTIVE) {
                return 'Your student account is inactive. Contact your school administrator.';
            }
        }

        if ($user instanceof User && LoginPortal::canAccessHr($user)) {
            return 'Only teachers, students, and the system administrator can sign in here. Use the ERP Admin Login page instead.';
        }

        return 'Only teachers, students, and the system administrator can sign in through CultureHR LMS.';
    }

    private function renderLmsLogin(LoginForm $model)
    {
        return $this->render('login', ['model' => $model]);
    }

    public function actionLogout(): Response
    {
        LoginPortal::setEmployeePortal(false);
        Yii::$app->user->logout();

        return $this->redirect(['login']);
    }

    public function actionStudent()
    {
        $this->layout = '@common/modules/lms/views/layouts/portal';
        $user = $this->requirePortalUser();

        if (!LmsPortal::isStudentPortalUser($user)) {
            if (LmsPortal::isParentPortalUser($user)) {
                return $this->redirect(['parent']);
            }
            if (LmsPortal::isTeacherPortalUser($user)) {
                return $this->redirect(['teacher']);
            }

            throw new ForbiddenHttpException('Student portal access is required.');
        }

        $student = SchoolStudent::find()
            ->with(['grade', 'section', 'grade.department'])
            ->where(['user_id' => (int)$user->id])
            ->one();

        $this->view->params['userName'] = LmsPortal::displayName($user);
        $this->view->params['userRole'] = LmsPortal::roleLabel($user);
        $this->view->params['portalUser'] = $user;
        $this->view->params['currentPage'] = 'lms_dashboard';

        return $this->render('student', [
            'user' => $user,
            'student' => $student,
            'userName' => LmsPortal::displayName($user),
            'userRole' => LmsPortal::roleLabel($user),
            'currentPage' => 'lms_dashboard',
        ]);
    }

    public function actionTeacher()
    {
        $this->layout = '@common/modules/lms/views/layouts/portal';
        $user = $this->requirePortalUser();

        if (!LmsPortal::isTeacherPortalUser($user)) {
            if (LmsPortal::isStudentPortalUser($user)) {
                return $this->redirect(['student']);
            }
            if (LmsPortal::isParentPortalUser($user)) {
                return $this->redirect(['parent']);
            }

            throw new ForbiddenHttpException('Teacher portal access is required.');
        }

        $employee = Employee::find()
            ->with(['department', 'schoolDepartment', 'managedSchoolDepartments', 'deputySchoolDepartmentRecord'])
            ->where(['user_id' => (int)$user->id])
            ->one();

        $this->view->params['userName'] = LmsPortal::displayName($user);
        $this->view->params['userRole'] = LmsPortal::roleLabel($user);
        $this->view->params['portalUser'] = $user;
        $this->view->params['currentPage'] = 'lms_dashboard';

        return $this->render('teacher', [
            'user' => $user,
            'employee' => $employee,
            'userName' => LmsPortal::displayName($user),
            'userRole' => LmsPortal::roleLabel($user),
            'currentPage' => 'lms_dashboard',
        ]);
    }

    public function actionParent()
    {
        $this->layout = '@common/modules/lms/views/layouts/portal';
        $user = $this->requirePortalUser();

        if (!LmsPortal::isParentPortalUser($user)) {
            return $this->redirect(LmsPortal::homeRoute($user));
        }

        $profile = FinanceAccountParentProfile::find()
            ->with(['children'])
            ->where(['user_id' => (int)$user->id])
            ->one();

        $this->view->params['userName'] = LmsPortal::displayName($user);
        $this->view->params['userRole'] = LmsPortal::roleLabel($user);
        $this->view->params['portalUser'] = $user;
        $this->view->params['currentPage'] = 'lms_dashboard';

        return $this->render('parent', [
            'user' => $user,
            'profile' => $profile,
            'userName' => LmsPortal::displayName($user),
            'userRole' => LmsPortal::roleLabel($user),
            'currentPage' => 'lms_dashboard',
        ]);
    }

    public function actionProfile()
    {
        $this->layout = '@common/modules/lms/views/layouts/portal';
        $user = $this->requirePortalUser();

        if (!LmsPortal::isParentPortalUser($user)) {
            return $this->redirect(LmsPortal::homeRoute($user));
        }

        $profile = FinanceAccountParentProfile::find()
            ->with(['children'])
            ->where(['user_id' => (int)$user->id])
            ->one();

        $this->view->params['userName'] = LmsPortal::displayName($user);
        $this->view->params['userRole'] = LmsPortal::roleLabel($user);
        $this->view->params['portalUser'] = $user;
        $this->view->params['currentPage'] = 'lms_my_profile';

        return $this->render('parent-profile', [
            'user' => $user,
            'profile' => $profile,
            'userName' => LmsPortal::displayName($user),
            'userRole' => LmsPortal::roleLabel($user),
            'currentPage' => 'lms_my_profile',
        ]);
    }

    public function actionChangePassword()
    {
        $this->layout = '@common/modules/lms/views/layouts/portal';
        $user = $this->requirePortalUser();

        if (!LmsPortal::isParentPortalUser($user)) {
            return $this->redirect(LmsPortal::homeRoute($user));
        }

        $error = '';
        if (Yii::$app->request->isPost) {
            $current = (string)Yii::$app->request->post('current_password', '');
            $new = (string)Yii::$app->request->post('new_password', '');
            $confirm = (string)Yii::$app->request->post('confirm_password', '');

            if (!$user->validatePassword($current)) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($new) < 8) {
                $error = 'New password must be at least 8 characters.';
            } elseif ($new !== $confirm) {
                $error = 'New password and confirmation do not match.';
            } else {
                $user->setPassword($new);
                $user->markPasswordChangedByUser();
                $user->updated_at = time();
                if ($user->save(false)) {
                    (new \common\modules\finance\services\FinanceParentProfileService())
                        ->clearStoredLoginPasswordForUser((int)$user->id);
                    Yii::$app->session->setFlash('success', 'Password updated successfully.');

                    return $this->redirect(['parent']);
                }
                $error = 'Unable to update password.';
            }
        }

        $this->view->params['userName'] = LmsPortal::displayName($user);
        $this->view->params['userRole'] = LmsPortal::roleLabel($user);
        $this->view->params['portalUser'] = $user;
        $this->view->params['currentPage'] = 'lms_change_password';

        return $this->render('change-password', [
            'user' => $user,
            'error' => $error,
            'userName' => LmsPortal::displayName($user),
            'userRole' => LmsPortal::roleLabel($user),
            'currentPage' => 'lms_change_password',
        ]);
    }

    private function requirePortalUser(): User
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !LmsPortal::canAccessLms($user)) {
            throw new ForbiddenHttpException('LMS access is required.');
        }

        return $user;
    }
}
