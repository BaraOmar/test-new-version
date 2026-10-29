<?php

namespace common\modules\recruitment\services;

use common\models\User;
use common\modules\recruitment\models\Applicant;
use Yii;

class RecruitmentHrService
{
    /**
     * Links the signed-in HR user to a recruiter profile and session used by recruitment controllers.
     */
    public static function bindHrRecruiterSession(User $user): Applicant
    {
        $module = Yii::$app->getModule('recruitment');
        if ($module instanceof \common\modules\recruitment\Module) {
            $module->ensureTables();
        }

        $recruiter = self::ensureRecruiterForUser($user);
        Yii::$app->session->set('rms_recruiter_id', (int)$recruiter->user_id);
        Yii::$app->session->set('rms_recruiter_name', (string)$recruiter->full_name);

        return $recruiter;
    }

    public static function ensureRecruiterForUser(User $user): Applicant
    {
        $email = trim((string)$user->email);
        if ($email === '') {
            throw new \RuntimeException('HR user email is required for recruit management.');
        }

        $recruiter = Applicant::find()
            ->where(['role_id' => Applicant::ROLE_RECRUITER])
            ->andWhere([
                'or',
                ['email' => $email],
                ['username' => $email],
            ])
            ->one();

        if ($recruiter !== null) {
            return $recruiter;
        }

        $recruiter = new Applicant([
            'role_id' => Applicant::ROLE_RECRUITER,
            'full_name' => trim((string)($user->username ?? $email)),
            'email' => $email,
            'username' => $email,
        ]);
        $recruiter->password = bin2hex(random_bytes(12));

        if (!$recruiter->save()) {
            throw new \RuntimeException('Could not create recruiter profile: ' . implode(' ', $recruiter->getFirstErrors()));
        }

        return $recruiter;
    }
}
