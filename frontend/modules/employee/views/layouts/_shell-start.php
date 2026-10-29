<?php

/**
 * Opens employee portal shell: sidebar + topbar + scrollable content area.
 *
 * @var yii\web\View $this
 * @var string $pageTitle
 * @var string $currentPage
 * @var string|null $userName
 * @var string|null $userRole
 */

use common\components\LmsPortal;
use common\models\User;
use yii\helpers\Url;

$pageTitle = $pageTitle ?? ($this->title ?? 'Dashboard');
$currentPage = $currentPage ?? '';
$userName = $userName ?? 'Employee';
$userRole = $userRole ?? 'Employee';
$portalLocked = !empty($portalLocked);

$navItems = [
    'dashboard' => ['url' => ['/employee/employee/dashboard'], 'icon' => 'home', 'label' => 'Dashboard'],
    'lms' => ['url' => ['/employee/employee/lms'], 'icon' => 'school', 'label' => 'LMS'],
    'my_profile' => ['url' => ['/employee/employee/profile'], 'icon' => 'person', 'label' => 'My Profile'],
    'survey_evaluation' => ['url' => ['/employee/employee/survey-display'], 'icon' => 'assignment', 'label' => 'Survey and Evaluations'],
];

$user = Yii::$app->user->identity;
$footerLinks = [];
if (
    !$portalLocked
    && $user instanceof User
    && LmsPortal::canAccessTeacherLms($user)
) {
    $footerLinks[] = [
        'url' => ['/employee/employee/lms'],
        'icon' => 'school',
        'label' => 'Open LMS Portal',
    ];
    unset($navItems['lms']);
} elseif (!($user instanceof User && LmsPortal::canAccessTeacherLms($user))) {
    unset($navItems['lms']);
}

if ($portalLocked) {
    $navItems = array_intersect_key($navItems, array_flip(['dashboard', 'my_profile']));
}

?>
<div class="flex h-screen overflow-hidden bg-gray-50 text-slate-900">
<?= $this->render('@frontend/views/layouts/_portal-sidebar', [
    'navItems' => $navItems,
    'currentPage' => $currentPage,
    'brandName' => 'CultureHR',
    'brandUrl' => ['/employee/employee/dashboard'],
    'userName' => $userName,
    'userRole' => $userRole,
    'footerLinks' => $footerLinks,
    'showLogout' => true,
]) ?>
<main class="flex-1 flex flex-col overflow-hidden min-w-0">
    <?php if (empty($legacyRespond)): ?>
    <?= $this->render('@frontend/views/layouts/_portal-topbar', [
        'pageTitle' => $pageTitle,
        'pageSubtitle' => null,
        'headerActionsHtml' => null,
    ]) ?>
    <?php endif; ?>
    <div class="flex-1 overflow-y-auto <?= !empty($legacyRespond) ? 'bg-[#f8f4f8] p-0' : 'bg-gray-50' ?>">
