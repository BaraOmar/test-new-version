<?php

namespace common\behaviors;

use Yii;
use yii\base\Application;
use yii\base\Behavior;

class SecureSessionBehavior extends Behavior
{
    public $timeout = 1800;
    public $regenerateInterval = 900;
    public $loginRoute = ['/site/login'];

    public function events()
    {
        return [
            Application::EVENT_BEFORE_REQUEST => 'enforceSessionSecurity',
        ];
    }

    public function enforceSessionSecurity()
    {
        if (Yii::$app instanceof \yii\console\Application || Yii::$app->user->isGuest) {
            return;
        }

        $session = Yii::$app->session;
        $now = time();
        $lastActivity = (int)$session->get('__lastActivity', 0);
        if ($lastActivity && ($now - $lastActivity) > $this->timeout) {
            Yii::warning(['userId' => Yii::$app->user->id], 'security.sessionTimeout');
            Yii::$app->user->logout();
            $session->destroy();
            Yii::$app->response->redirect(array_merge($this->loginRoute, ['timeout' => 1]))->send();
            Yii::$app->end();
        }

        $session->set('__lastActivity', $now);
        $lastRegenerate = (int)$session->get('__lastRegenerate', 0);
        if (($now - $lastRegenerate) >= $this->regenerateInterval) {
            $session->regenerateID(true);
            $session->set('__lastRegenerate', $now);
        }
    }
}
