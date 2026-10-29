<?php

namespace frontend\controllers;

use common\components\LoginPortal;
use common\models\LoginForm;
use common\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class AuthController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                    'check-hr' => ['get'],
                ],
            ],
        ];
    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(LoginPortal::homeRoute());
        }

        $this->layout = 'auth';
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $user = Yii::$app->user->identity;
            $useEmployeePortal = $user instanceof User
                && LoginPortal::canAccessHr($user)
                && (bool)Yii::$app->request->post('access_employee');
            LoginPortal::setEmployeePortal($useEmployeePortal);

            return $this->redirect(LoginPortal::homeRoute($user));
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        Yii::$app->session->destroy();

        return $this->redirect(['login']);
    }

    public function actionCheckHr($email)
    {
        Yii::$app->rateLimiter->check('check_hr', 30, 60, Yii::$app->request->userIP);
        Yii::$app->response->format = Response::FORMAT_JSON;
        $userClass = Yii::$app->user->identityClass;
        $email = Yii::$app->inputValidator->email($email);
        $user = $email ? $userClass::findByEmail($email) : null;

        return [
            'is_hr' => $user !== null && LoginPortal::canAccessHr($user),
        ];
    }
}
