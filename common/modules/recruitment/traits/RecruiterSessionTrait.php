<?php

namespace common\modules\recruitment\traits;

use yii\web\ForbiddenHttpException;
use Yii;

trait RecruiterSessionTrait
{
    protected function requireRecruiter(): void
    {
        if (!$this->currentRecruiterId()) {
            throw new ForbiddenHttpException('Recruiter access is required.');
        }
    }

    protected function currentRecruiterId(): int
    {
        return (int)Yii::$app->session->get('rms_recruiter_id');
    }
}
