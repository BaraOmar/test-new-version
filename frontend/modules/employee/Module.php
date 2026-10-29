<?php

namespace frontend\modules\employee;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'frontend\modules\employee\controllers';
    public $layout = 'legacy';
    public $defaultRoute = 'employee/dashboard';
}

