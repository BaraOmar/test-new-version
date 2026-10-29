<?php

namespace common\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\Application;

class SecurityHeadersBehavior extends Behavior
{
    public $contentSecurityPolicy = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline'",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com https://fonts.googleapis.com",
        "img-src 'self' data: https:",
        "connect-src 'self'",
        "frame-ancestors 'self'",
    ];

    public function events()
    {
        return [
            Application::EVENT_BEFORE_REQUEST => 'applyHeaders',
        ];
    }

    public function applyHeaders()
    {
        if (Yii::$app instanceof \yii\console\Application) {
            return;
        }

        $headers = Yii::$app->response->headers;
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Content-Security-Policy', implode('; ', $this->contentSecurityPolicy));

        if (Yii::$app->request->isSecureConnection) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }
    }
}
