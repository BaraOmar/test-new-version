<?php

/**
 * @var yii\web\View $this
 * @var array<string, array{url: array|string, icon: string, label: string}> $navItems
 * @var string $currentPage
 * @var string $userName
 * @var string $userRole
 * @var array<int, array{url: array|string, icon: string, label: string}> $footerLinks
 */

use yii\helpers\Url;

$footerLinks = $footerLinks ?? [];

echo $this->render('@frontend/views/layouts/_portal-sidebar', [
    'navItems' => $navItems,
    'currentPage' => $currentPage,
    'brandName' => 'CultureHR LMS',
    'brandUrl' => Url::to(['/lms/portal/index']),
    'userName' => $userName,
    'userRole' => $userRole,
    'footerLinks' => $footerLinks,
    'showLogout' => true,
    'logoutUrl' => ['/lms/portal/logout'],
]);
