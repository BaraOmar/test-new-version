<?php

namespace console\controllers;

use common\modules\finance\services\FinanceParentProfileService;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class ParentsController extends Controller
{
    public function actionProvisionLogins(): int
    {
        $service = new FinanceParentProfileService();
        $result = $service->provisionMissingLogins();
        $this->stdout($result['message'] . PHP_EOL, $result['success'] ? Console::FG_GREEN : Console::FG_RED);

        foreach ($result['credentials'] as $row) {
            $this->stdout(PHP_EOL);
            $this->stdout('Parent:   ' . $row['parent'] . PHP_EOL);
            $this->stdout('Email:    ' . $row['email'] . PHP_EOL);
            $this->stdout('Password: ' . $row['password'] . PHP_EOL);
        }

        return $result['success'] ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }

    public function actionRefreshPasswords(): int
    {
        $result = (new FinanceParentProfileService())->refreshMissingVisiblePasswords();
        $this->stdout($result['message'] . PHP_EOL, $result['success'] ? Console::FG_GREEN : Console::FG_RED);

        foreach ($result['credentials'] as $row) {
            $this->stdout(PHP_EOL);
            $this->stdout('Parent:   ' . $row['parent'] . PHP_EOL);
            $this->stdout('Email:    ' . $row['email'] . PHP_EOL);
            $this->stdout('Password: ' . $row['password'] . PHP_EOL);
        }

        return $result['success'] ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }
}
