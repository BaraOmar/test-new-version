<?php

return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'inputValidator' => [
            'class' => \common\components\InputValidator::class,
        ],
        'rateLimiter' => [
            'class' => \common\components\RateLimiter::class,
        ],
        'authSecurity' => [
            'class' => \common\components\AuthSecurity::class,
        ],
        'formatter' => [
            'class' => \yii\i18n\Formatter::class,
            'currencyCode' => 'JOD',
            'defaultTimeZone' => 'Asia/Amman',
        ],
    ],
];
