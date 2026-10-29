<?php

namespace common\components;

use common\models\User;
use Yii;

class LoginPortal
{
    public const SESSION_EMPLOYEE_PORTAL = 'login_employee_portal';

    public static function canAccessHr(User $user): bool
    {
        if (!$user->isHr()) {
            return false;
        }

        if ($user->isSuperHrAdmin()) {
            return true;
        }

        return $user->getPermissionList() !== [];
    }

    public static function useEmployeePortal(): bool
    {
        return (bool)Yii::$app->session->get(self::SESSION_EMPLOYEE_PORTAL, false);
    }

    public static function setEmployeePortal(bool $enabled): void
    {
        if ($enabled) {
            Yii::$app->session->set(self::SESSION_EMPLOYEE_PORTAL, true);
        } else {
            Yii::$app->session->remove(self::SESSION_EMPLOYEE_PORTAL);
        }
    }

    /**
     * @return array<int, string> Yii route
     */
    public static function homeRoute(?User $user = null): array
    {
        $user = $user ?? Yii::$app->user->identity;
        if ($user instanceof User && self::canAccessHr($user) && !self::useEmployeePortal()) {
            return $user->getHrHomeRoute();
        }

        if ($user instanceof User && ($user->isParent() || $user->isStudent())) {
            return LmsPortal::homeRoute($user);
        }

        return ['/employee/employee/dashboard'];
    }
}
