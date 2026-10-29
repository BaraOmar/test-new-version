<?php

/**
 * Shared white page header (default site chrome).
 *
 * @var yii\web\View $this
 * @var string $pageTitle
 * @var string|null $pageSubtitle
 * @var string|null $headerActionsHtml
 */

use yii\helpers\Html;

$pageTitle = $pageTitle ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? null;
$headerActionsHtml = $headerActionsHtml ?? null;

?>

<header class="app-topbar flex items-start justify-between gap-6 px-8 py-5">
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-[#4a2a51] tracking-tight truncate"><?= Html::encode($pageTitle) ?></h1>
        <?php if ($pageSubtitle !== null && $pageSubtitle !== ''): ?>
            <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-[#f27a44]"><?= $pageSubtitle ?></p>
        <?php endif; ?>
    </div>
    <?php if ($headerActionsHtml !== null && $headerActionsHtml !== ''): ?>
        <div class="flex items-center gap-4 shrink-0 pt-0.5">
            <?= $headerActionsHtml ?>
        </div>
    <?php endif; ?>
</header>
