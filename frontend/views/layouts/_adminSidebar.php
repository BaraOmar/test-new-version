<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var string|null $currentPage */

$currentPage = $currentPage ?? '';
$navItems = [
    'dashboard' => ['route' => ['/dashboard/dashboard/index'], 'label' => 'Dashboard'],
    'employees' => ['route' => ['/employee/employee/index'], 'label' => 'Employees'],
    'surveys' => ['route' => ['/survey/survey/index'], 'label' => 'Surveys'],
    'evaluations' => ['route' => ['/evaluation/evaluation/index'], 'label' => 'Evaluations'],
    'lms' => ['route' => ['/lms/portal/teacher'], 'label' => 'LMS'],
];
?>
<aside class="d-flex flex-column flex-shrink-0 p-3" style="width:16rem; min-height:100vh; background:#6B225E;">
    <nav class="nav nav-pills flex-column gap-1 flex-grow-1">
        <?php foreach ($navItems as $key => $item): ?>
            <?= Html::a($item['label'], $item['route'], [
                'class' => 'nav-link ' . ($currentPage === $key ? 'active' : 'text-white'),
                'style' => $currentPage === $key ? 'background:#F47F37;' : '',
            ]) ?>
        <?php endforeach; ?>
    </nav>
    <?= Html::beginForm(['/auth/logout'], 'post') ?>
        <?= Html::submitButton('Logout', ['class' => 'btn btn-link text-white text-decoration-none w-100 text-start px-3']) ?>
    <?= Html::endForm() ?>
</aside>
