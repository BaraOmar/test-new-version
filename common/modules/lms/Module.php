<?php

namespace common\modules\lms;

use common\modules\lms\controllers\AttendanceController;
use common\modules\lms\controllers\CertificatesController;
use common\modules\lms\controllers\HierarchyController;
use common\modules\lms\controllers\MyClassesController;
use common\modules\lms\controllers\PortalController;
use common\modules\lms\controllers\StudentAffairsController;
use common\modules\lms\controllers\SubjectsController;
use common\modules\lms\controllers\TimetableController;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'common\modules\lms\controllers';

    public function init()
    {
        parent::init();
        \Yii::$app->user->loginUrl = ['/lms/portal/login'];
    }

    public $controllerMap = [
        'portal' => PortalController::class,
        'student-affairs' => StudentAffairsController::class,
        'hierarchy' => HierarchyController::class,
        'subjects' => SubjectsController::class,
        'timetable' => TimetableController::class,
        'attendance' => AttendanceController::class,
        'certificates' => CertificatesController::class,
        'my-classes' => MyClassesController::class,
    ];
}
