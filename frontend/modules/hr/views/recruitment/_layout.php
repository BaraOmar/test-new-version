<?php

/**
 * @var yii\web\View $this
 * @var string $content
 * @var string $userName
 * @var string $pageTitle
 * @var string $currentPage
 * @var bool $fullBleed
 */

use yii\helpers\Html;

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');

?>

<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'recruitment']) ?>
    <main class="flex-1 flex flex-col overflow-hidden min-w-0">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => $pageTitle ?? 'Recruit Management', 'userName' => $userName]) ?>
        <div class="flex-1 <?= !empty($fullBleed) ? 'flex flex-col min-h-0 overflow-hidden bg-gray-50' : 'overflow-y-auto bg-gray-50 p-6 lg:p-8' ?>">
            <?php if ($successMessage !== ''): ?>
                <div class="<?= !empty($fullBleed) ? 'mx-6 mt-4' : 'mb-4' ?> p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-medium shrink-0">
                    <?= Html::encode($successMessage) ?>
                </div>
            <?php endif; ?>
            <?php if ($errorMessage !== ''): ?>
                <div class="<?= !empty($fullBleed) ? 'mx-6 mt-4' : 'mb-4' ?> p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium shrink-0">
                    <?= Html::encode($errorMessage) ?>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </div>
    </main>
</div>
