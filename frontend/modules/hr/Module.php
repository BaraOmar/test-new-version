<?php

namespace frontend\modules\hr;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'frontend\modules\hr\controllers';
    public $layout = 'legacy';
    public $defaultRoute = 'hr/dashboard';
}

