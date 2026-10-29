<?php

/**
 * @deprecated Use `_shell-start` and `_shell-end` instead.
 *
 * @var yii\web\View $this
 * @var string $pageTitle
 * @var string $currentPage
 * @var string $userName
 * @var string $userRole
 */

echo $this->render('_shell-start', [
    'pageTitle' => $pageTitle ?? ($this->title ?? 'Dashboard'),
    'currentPage' => $currentPage ?? 'dashboard',
    'userName' => $userName ?? 'Employee',
    'userRole' => $userRole ?? 'Employee',
]);
