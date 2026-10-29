<?php

namespace common\traits;

use common\models\User;
use Yii;
use yii\web\ForbiddenHttpException;

trait HrAccessTrait
{
    protected function requireHr(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !$user->isHr()) {
            throw new ForbiddenHttpException('Access denied.');
        }

        if ($user->syncMissingHrModules()) {
            Yii::$app->user->identity = User::findOne($user->id);
        }
    }

    protected function requirePermission(string $permission): void
    {
        $this->requireHr();
        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !$user->hasPermission($permission)) {
            throw new ForbiddenHttpException('Access denied.');
        }
    }

    protected function requireManageHr(): void
    {
        $this->requirePermission('manage_hr');
    }
}
