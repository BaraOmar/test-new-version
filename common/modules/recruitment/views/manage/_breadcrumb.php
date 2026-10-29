<?php

/**
 * @var yii\web\View $this
 * @var string[] $indexRoute
 * @var string $current
 * @var string[][] $crumbs Additional crumbs after hub: [['label'=>'...', 'url'=>[...]], ...]
 */

use yii\helpers\Html;
use yii\helpers\Url;

$indexRoute = $indexRoute ?? ['index'];
$crumbs = $crumbs ?? [];
$current = $current ?? '';

?>

<nav class="flex items-center gap-2 text-sm text-slate-500 mb-6 flex-wrap">
    <a href="<?= Url::to($indexRoute) ?>" class="hover:text-[#642754] font-medium">Recruit Management</a>
    <?php foreach ($crumbs as $crumb): ?>
        <span class="material-symbols-outlined text-base text-slate-400">chevron_right</span>
        <?php if (!empty($crumb['url'])): ?>
            <a href="<?= Url::to($crumb['url']) ?>" class="hover:text-[#642754] font-medium"><?= Html::encode($crumb['label']) ?></a>
        <?php else: ?>
            <span class="text-slate-800 font-semibold"><?= Html::encode($crumb['label']) ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($current !== ''): ?>
        <span class="material-symbols-outlined text-base text-slate-400">chevron_right</span>
        <span class="text-slate-800 font-semibold"><?= Html::encode($current) ?></span>
    <?php endif; ?>
</nav>
