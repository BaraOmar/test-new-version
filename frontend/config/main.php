<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'defaultRoute' => 'site/index',
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'as securityHeaders' => [
        'class' => \common\behaviors\SecurityHeadersBehavior::class,
    ],
    'as secureSession' => [
        'class' => \common\behaviors\SecureSessionBehavior::class,
    ],
    'modules' => [
        'employee' => [
            'class' => \frontend\modules\employee\Module::class,
        ],
        'hr' => [
            'class' => \frontend\modules\hr\Module::class,
        ],
        'evaluation' => [
            'class' => \common\modules\evaluation\Module::class,
        ],
        'survey' => [
            'class' => \common\modules\survey\Module::class,
        ],
        'dashboard' => [
            'class' => \common\modules\dashboard\Module::class,
        ],
        'lms' => [
            'class' => \common\modules\lms\Module::class,
        ],
        'recruitment' => [
            'class' => \common\modules\recruitment\Module::class,
        ],
        'document' => [
            'class' => \common\modules\document\Module::class,
        ],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-frontend',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['site/login'],
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'advanced-frontend',
            'timeout' => 1800,
            'cookieParams' => [
                'httpOnly' => true,
                'sameSite' => \yii\web\Cookie::SAME_SITE_STRICT,
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        /*
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        */
    ],
    'params' => $params,
];
