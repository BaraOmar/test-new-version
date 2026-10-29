<?php

/**
 * Legacy route — AttendanceController::actionSearch redirects to history.
 *
 * @var yii\web\View $this
 */

use yii\helpers\Url;

$this->title = 'Attendance History | LMS';
?>
<meta http-equiv="refresh" content="0;url=<?= htmlspecialchars(Url::to(['history']), ENT_QUOTES) ?>">
