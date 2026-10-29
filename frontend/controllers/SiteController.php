<?php

namespace frontend\controllers;

use frontend\models\ResendVerificationEmailForm;
use frontend\models\VerifyEmailForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\components\LmsPortal;
use common\components\LoginPortal;
use common\models\LoginForm;
use common\models\User;
use yii\web\Response;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'signup', 'admin-dashboard', 'employee-portal'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout', 'admin-dashboard', 'employee-portal'],
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

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }

        return $this->redirect(LoginPortal::homeRoute());
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        $this->layout = 'culturehr-login';
        Yii::$app->response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        Yii::$app->response->headers->set('Pragma', 'no-cache');

        $switching = (bool)Yii::$app->request->get('switch', false);
        if ($switching && !Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
            LoginPortal::setEmployeePortal(false);
        }

        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['site/index']);
        }

        $model = new LoginForm();
        $model->rememberMe = false;
        if ($model->load(Yii::$app->request->post())) {
            $email = trim((string)$model->email);
            $password = (string)$model->password;
            if ($email === '' || $password === '') {
                $model->password = '';
                $model->addError('email', 'Work Email and Password are required.');

                return $this->render('login', ['model' => $model]);
            }

            if ($model->login()) {
                $user = Yii::$app->user->identity;
                if (!$user instanceof User || !$this->canAccessCultureHrPortal($user)) {
                    Yii::$app->user->logout();
                    LoginPortal::setEmployeePortal(false);
                    $model->password = '';
                    $model->email = '';
                    $model->addError('email', $this->hrLoginDenialMessage($user instanceof User ? $user : null));

                    return $this->render('login', ['model' => $model]);
                }

                LoginPortal::setEmployeePortal(false);

                return $this->redirect(LoginPortal::homeRoute($user));
            }

            $model->password = '';
            $model->clearErrors();
            $model->addError('password', 'Wrong login/password');
        }

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        LoginPortal::setEmployeePortal(false);
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionEmployeePortal()
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !LoginPortal::canAccessHr($user)) {
            throw new ForbiddenHttpException('Access denied.');
        }

        LoginPortal::setEmployeePortal(true);

        return $this->redirect(['/employee/employee/dashboard']);
    }

    public function actionCheckHr($email = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->has('rateLimiter')) {
            Yii::$app->rateLimiter->check('check_hr', 30, 60, Yii::$app->request->userIP);
        }

        $user = User::findByEmail(trim((string)$email));

        return [
            'is_hr' => $user !== null && LoginPortal::canAccessHr($user),
        ];
    }

    public function actionAdminDashboard()
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !$user->hasPermission('manage_hr')) {
            throw new ForbiddenHttpException('Access denied.');
        }

        return $this->render('admin-dashboard');
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        }

        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post()) && $model->signup()) {
            Yii::$app->session->setFlash('success', 'Thank you for registration. Please check your inbox for verification email.');
            return $this->goHome();
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            }

            Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @throws BadRequestHttpException
     * @return yii\web\Response
     */
    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if ($model->verifyEmail()) {
            Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
            return $this->goHome();
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return mixed
     */
    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }
            Yii::$app->session->setFlash('error', 'Sorry, we are unable to resend verification email for the provided email address.');
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model
        ]);
    }

    private function canAccessCultureHrPortal(User $user): bool
    {
        return LoginPortal::canAccessHr($user) || $user->isEmployee();
    }

    private function hrLoginDenialMessage(?User $user): string
    {
        if ($user !== null && ($user->isStudent() || $user->isParent())) {
            return 'Students must sign in through the LMS Portal Login page.';
        }
        if ($user !== null && LmsPortal::canAccessLms($user)) {
            return 'You do not have HR access. Use the LMS Portal Login page instead.';
        }

        return 'You do not have access to the CultureHR portal.';
    }
}
