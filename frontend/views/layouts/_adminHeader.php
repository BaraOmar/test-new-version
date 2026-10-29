<?php

use yii\helpers\Html;

/** @var string|null $pageTitle */

$pageTitle = $pageTitle ?? $this->title ?? 'Dashboard';
$identity = Yii::$app->user->identity;
$userName = $identity ? $identity->getDisplayName() : 'Admin User';
?>
<header class="d-flex align-items-center justify-content-between px-4 py-3 shadow-sm" style="background:#6B225E;">
    <div></div>
    <h1 class="h5 text-white mb-0"><?= Html::encode($pageTitle) ?></h1>
    <div class="text-end text-white">
        <div class="fw-bold"><?= Html::encode($userName) ?></div>
        <div class="small text-white-50">Org Admin</div>
    </div>
</header>
