<?php

namespace common\modules\employee;

use Yii;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'common\modules\employee\controllers';

    public function init()
    {
        parent::init();

        if (!(Yii::$app instanceof \yii\web\Application)) {
            return;
        }

        $basePath = str_replace('\\', '/', (string)Yii::$app->basePath);
        $isFrontendApp = str_ends_with($basePath, '/frontend') || str_contains($basePath, '/frontend/');

        if ($isFrontendApp) {
            $this->controllerNamespace = 'frontend\modules\employee\controllers';
            return;
        }

        if (Yii::$app->id === 'app-frontend') {
            $this->controllerNamespace = 'frontend\modules\employee\controllers';
        }
    }
}
