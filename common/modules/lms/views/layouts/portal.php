<?php

/**
 * @var yii\web\View $this
 * @var string $content
 * @var string|null $userName
 * @var string|null $userRole
 * @var string|null $currentPage
 * @var common\models\User|null $portalUser
 */

use common\components\LmsPortal;
use yii\helpers\Html;

echo $this->render('@frontend/views/layouts/_portal-header');

$portalUser = $portalUser ?? ($this->params['portalUser'] ?? Yii::$app->user->identity);
$userName = $userName ?? ($this->params['userName'] ?? null);
$userRole = $userRole ?? ($this->params['userRole'] ?? null);
$currentPage = $currentPage ?? ($this->params['currentPage'] ?? '');

if ($portalUser instanceof \common\models\User) {
    if ($userName === null || $userName === '' || $userName === 'User') {
        $userName = LmsPortal::displayName($portalUser);
    }
    if ($userRole === null || $userRole === '') {
        $userRole = LmsPortal::roleLabel($portalUser);
    }
}

$userName = $userName !== null && $userName !== '' ? $userName : 'User';
$userRole = $userRole ?? '';

$isTeacher = $portalUser instanceof \common\models\User && LmsPortal::isTeacherPortalUser($portalUser);
$isStudent = $portalUser instanceof \common\models\User && LmsPortal::isStudentPortalUser($portalUser);
$isParent = $portalUser instanceof \common\models\User && LmsPortal::isParentPortalUser($portalUser);
$isSuperAdmin = $portalUser instanceof \common\models\User && $portalUser->isSuperHrAdmin();
$teacherPortalSubtitle = 'Teacher Portal';
if ($isTeacher && $portalUser instanceof \common\models\User && !$isSuperAdmin) {
    $teacherPortalSubtitle = LmsPortal::teacherPortalSubtitle($portalUser);
}

$navItems = [];
if ($isStudent) {
    $navItems['lms_dashboard'] = ['url' => ['/lms/portal/student'], 'icon' => 'home', 'label' => 'Dashboard'];
    if ($portalUser instanceof \common\models\User && LmsPortal::canAccessOwnStudentProfile($portalUser)) {
        $navItems['lms_my_profile'] = ['url' => ['/lms/student-affairs/profile'], 'icon' => 'person', 'label' => 'My Profile'];
    }
    if ($portalUser instanceof \common\models\User && LmsPortal::canAccessOwnStudentHealthRecord($portalUser)) {
        $navItems['lms_my_health'] = ['url' => ['/lms/student-affairs/health-record'], 'icon' => 'medical_information', 'label' => 'My Health Record'];
    }
    if ($portalUser instanceof \common\models\User && LmsPortal::canAccessOwnStudentAttendanceLog($portalUser)) {
        $navItems['lms_my_attendance'] = ['url' => ['/lms/student-affairs/attendance-log'], 'icon' => 'fact_check', 'label' => 'My Attendance'];
    }
    if ($portalUser instanceof \common\models\User && LmsPortal::canAccessOwnStudentMarks($portalUser)) {
        $navItems['lms_my_marks'] = ['url' => ['/lms/student-affairs/marks-log'], 'icon' => 'grade', 'label' => 'My Marks'];
    }
    if ($portalUser instanceof \common\models\User && LmsPortal::canAccessOwnStudentCertificate($portalUser)) {
        $navItems['lms_my_certificate'] = ['url' => ['/lms/certificates/my'], 'icon' => 'workspace_premium', 'label' => 'My Certificate'];
    }
    if ($portalUser instanceof \common\models\User && LmsPortal::canAccessLmsSubjects($portalUser)) {
        $navItems['lms_subjects'] = ['url' => ['/lms/subjects/index'], 'icon' => 'menu_book', 'label' => 'Subjects'];
    }
    if ($portalUser instanceof \common\models\User && LmsPortal::canAccessOwnStudentSchedule($portalUser)) {
        $navItems['lms_timetable'] = ['url' => ['/lms/timetable/index'], 'icon' => 'calendar_month', 'label' => 'My Schedule'];
    }
} elseif ($isParent) {
    $navItems['lms_dashboard'] = ['url' => ['/lms/portal/parent'], 'icon' => 'home', 'label' => 'Dashboard'];
    $navItems['lms_my_profile'] = ['url' => ['/lms/portal/profile'], 'icon' => 'person', 'label' => 'My Profile'];
    $navItems['lms_change_password'] = ['url' => ['/lms/portal/change-password'], 'icon' => 'lock_reset', 'label' => 'Change Password'];
} elseif ($isTeacher) {
    $navItems['lms_dashboard'] = ['url' => ['/lms/portal/teacher'], 'icon' => 'home', 'label' => 'Dashboard'];
    if ($portalUser instanceof \common\models\User && LmsPortal::canAccessStudentRegistry($portalUser)) {
        $navItems['lms_student_registry'] = ['url' => ['/lms/student-affairs/students'], 'icon' => 'groups', 'label' => 'Student Registry'];
    }
    if ($portalUser instanceof \common\models\User) {
        $hierarchyLabel = LmsPortal::infrastructureHierarchyNavLabel($portalUser);
        if ($hierarchyLabel !== null) {
            $navItems['lms_hierarchy'] = ['url' => ['/lms/hierarchy/index'], 'icon' => 'account_tree', 'label' => $hierarchyLabel];
        }
        if (LmsPortal::canAccessLmsSubjects($portalUser)) {
            $navItems['lms_subjects'] = ['url' => ['/lms/subjects/index'], 'icon' => 'menu_book', 'label' => 'Subjects'];
        }
        if (LmsPortal::isDepartmentTimetableManager($portalUser)) {
            $navItems['lms_timetable'] = ['url' => ['/lms/timetable/index'], 'icon' => 'calendar_month', 'label' => 'Timetable'];
        }
        if (LmsPortal::canAccessOwnInstructorSchedule($portalUser) || LmsPortal::canEditOwnLessonBrief($portalUser)) {
            $navItems['lms_my_schedule'] = ['url' => ['/lms/timetable/my-schedule'], 'icon' => 'calendar_month', 'label' => 'My Schedule'];
        }
        if (LmsPortal::canAccessTeacherAttendanceManagement($portalUser)) {
            $navItems['lms_attendance'] = ['url' => ['/lms/attendance/index'], 'icon' => 'fact_check', 'label' => 'Attendance'];
        }
        if (LmsPortal::canAccessStudentCertificateManagement($portalUser)) {
            $navItems['lms_certificates'] = ['url' => ['/lms/certificates/index'], 'icon' => 'workspace_premium', 'label' => 'Student Certificates'];
        }
        if (LmsPortal::canAccessMyClasses($portalUser)) {
            $navItems['lms_my_classes'] = [
                'url' => ['/lms/my-classes/index'],
                'icon' => 'class',
                'label' => 'My Classes',
                'children' => LmsPortal::getMyClassesNavChildren($portalUser),
            ];
        }
    }
}

$footerLinks = [];
if ($portalUser instanceof \common\models\User && LmsPortal::isTeacherEmployee($portalUser)) {
    $footerLinks[] = ['url' => ['/employee/employee/dashboard'], 'icon' => 'badge', 'label' => 'My Employee Page'];
}
if ($isSuperAdmin) {
    $footerLinks[] = ['url' => ['/hr/hr/dashboard'], 'icon' => 'admin_panel_settings', 'label' => 'HR Dashboard'];
}

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="bg-gray-50 text-slate-900 min-h-screen">
<?php $this->beginBody() ?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('_portal-sidebar', [
        'navItems' => $navItems,
        'currentPage' => $currentPage,
        'userName' => $userName,
        'userRole' => $userRole,
        'footerLinks' => $footerLinks,
    ]) ?>
    <main class="flex-1 flex flex-col overflow-hidden min-w-0">
        <?= $this->render('@frontend/views/layouts/_portal-topbar', [
            'pageTitle' => $this->title,
            'pageSubtitle' => $isStudent ? 'Student Portal' : ($isParent ? 'Parent Portal' : ($isTeacher ? $teacherPortalSubtitle : 'LMS')),
            'headerActionsHtml' => null,
        ]) ?>
        <div class="flex-1 overflow-y-auto bg-gray-50">
            <?= $content ?>
        </div>
    </main>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
