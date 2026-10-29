<?php

/**
 * HR portal sidebar (shared light design).
 *
 * @var yii\web\View $this
 * @var string $currentPage
 * @var string|null $userName
 */

use common\components\LoginPortal;
use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolStudent;
use common\modules\finance\models\FinanceAccountParentProfile;
use yii\helpers\Url;

$currentPage = $currentPage ?? '';
$user = Yii::$app->user->identity;
$userName = $userName ?? 'Admin User';
$userRole = 'Org Admin';
$can = static fn(string $perm): bool => $user !== null && $user->isHr() && $user->hasPermission($perm);

$navItems = [];
if ($can('dashboard')) {
    $navItems['dashboard'] = ['url' => ['/hr/hr/dashboard'], 'icon' => 'home', 'label' => 'Dashboard'];
}
if ($can('employees_directory') || $can('employees')) {
    $navItems['employees'] = ['url' => ['/hr/hr/employees'], 'icon' => 'group', 'label' => 'Employees'];
}
if ($can('school_departments') || $can('school_departments_hierarchy') || $can('school_departments_create')) {
    $navItems['school_departments'] = [
        'url' => ['/hr/school-department/index'],
        'icon' => 'account_balance',
        'label' => 'School Departments',
        'children' => array_filter([
            'school_department_hierarchy' => $can('school_departments_hierarchy') ? [
                'url' => ['/hr/school-department/hierarchy'],
                'label' => 'Infrastructure Hierarchy',
            ] : null,
            'school_department_create' => $can('school_departments_create') ? [
                'url' => ['/hr/school-department/create'],
                'label' => 'Add New Department',
            ] : null,
        ]),
    ];
    if ($navItems['school_departments']['children'] === []) {
        unset($navItems['school_departments']['children']);
    }
}
if ($can('surveys')) {
    $navItems['surveys'] = ['url' => ['/hr/survey/index'], 'icon' => 'assignment', 'label' => 'Surveys'];
}
if ($can('evaluations')) {
    $navItems['evaluations'] = ['url' => ['/hr/evaluation/index'], 'icon' => 'star', 'label' => 'Evaluations'];
}
if ($can('subjects') && SchoolDepartment::isEnabled()) {
    $navItems['subjects'] = ['url' => ['/hr/school-department/subjects'], 'icon' => 'menu_book', 'label' => 'Subjects'];
}
if ($can('student_affairs') && SchoolDepartment::isEnabled() && SchoolStudent::isEnabled()) {
    $navItems['student_affairs'] = [
        'url' => ['/hr/student-affairs/students'],
        'icon' => 'school',
        'label' => 'Student Affairs',
        'children' => array_filter([
            'student_affairs_registry' => $can('student_affairs_registry') ? [
                'url' => ['/hr/student-affairs/students'],
                'label' => 'Student Registry',
            ] : null,
            'student_affairs_create' => $can('student_affairs_create') ? [
                'url' => ['/hr/student-affairs/create'],
                'label' => 'Add New Student',
            ] : null,
        ]),
    ];
    if ($navItems['student_affairs']['children'] === []) {
        unset($navItems['student_affairs']['children']);
    }
}
if ($can('student_health') && SchoolStudent::isEnabled()) {
    $navItems['student_health'] = [
        'url' => ['/hr/student-health/index'],
        'icon' => 'health_and_safety',
        'label' => 'Student Health',
        'children' => array_filter([
            'student_health_files' => $can('student_health_files') ? [
                'url' => ['/hr/student-health/files'],
                'label' => 'Health Files',
            ] : null,
            'student_health_clinic' => $can('student_health_clinic') ? [
                'url' => ['/hr/student-health/clinic-visits'],
                'label' => 'Record Visit',
            ] : null,
            'student_health_records' => $can('student_health_records') ? [
                'url' => ['/hr/student-health/clinic-visit-records'],
                'label' => 'Visit Records',
            ] : null,
        ]),
    ];
    if ($navItems['student_health']['children'] === []) {
        unset($navItems['student_health']['children']);
    }
}
if ($can('parents_directory') && FinanceAccountParentProfile::isEnabled()) {
    $navItems['parents_directory'] = [
        'url' => ['/hr/parents/index'],
        'icon' => 'family_restroom',
        'label' => 'Parents Directory',
    ];
}
if ($can('transportation') || $can('transportation_areas') || $can('transportation_buses') || $can('transportation_assign_buses')) {
    $navItems['transportation'] = [
        'url' => ['/hr/transportation/hub'],
        'icon' => 'directions_bus',
        'label' => 'Transportations',
        'children' => array_filter([
            'transportation_areas' => $can('transportation_areas') || $can('transportation') ? [
                'url' => ['/hr/transportation/areas'],
                'label' => 'Areas',
            ] : null,
            'transportation_buses' => $can('transportation_buses') || $can('transportation') ? [
                'url' => ['/hr/transportation/buses'],
                'label' => 'Create Buses',
            ] : null,
            'transportation_assign_buses' => $can('transportation_assign_buses') || $can('transportation') ? [
                'url' => ['/hr/transportation/assign-buses'],
                'label' => 'Assign Buses',
            ] : null,
        ]),
    ];
    if ($navItems['transportation']['children'] === []) {
        unset($navItems['transportation']['children']);
    }
}
if ($can('recruitment') || $can('recruitment_jobs') || $can('recruitment_reports') || $can('recruitment_applications') || $can('recruitment_application_status')) {
    $navItems['recruitment'] = [
        'url' => ['/hr/recruitment/index'],
        'icon' => 'person_search',
        'label' => 'Recruit Management',
        'children' => array_filter([
            'recruitment_jobs' => $can('recruitment_jobs') ? [
                'url' => ['/hr/recruitment/job-form'],
                'label' => 'Create a New Job',
            ] : null,
            'recruitment_reports' => $can('recruitment_reports') ? [
                'url' => ['/hr/recruitment/reports'],
                'label' => 'Report Generating',
            ] : null,
            'recruitment_applications' => $can('recruitment_applications') ? [
                'url' => ['/hr/recruitment/applications'],
                'label' => 'Applications & Interviews',
            ] : null,
            'recruitment_application_status' => $can('recruitment_application_status') ? [
                'url' => ['/hr/recruitment/application-status'],
                'label' => 'Application Status',
            ] : null,
        ]),
    ];
    if ($navItems['recruitment']['children'] === []) {
        unset($navItems['recruitment']['children']);
    }
}
if ($can('change_requests')) {
    $navItems['change_requests'] = ['url' => ['/hr/hr/requests'], 'icon' => 'edit_note', 'label' => 'Change Requests'];
}
if ($can('manage_hr')) {
    $navItems['manage_hr'] = ['url' => ['/hr/hr/manage-hr'], 'icon' => 'admin_panel_settings', 'label' => 'User Permissions'];
}

$footerLinks = [];
if ($user !== null && $user->isSuperHrAdmin()) {
    $footerLinks[] = ['url' => ['/lms/portal/teacher'], 'icon' => 'school', 'label' => 'Open LMS'];
}
if ($user !== null && LoginPortal::canAccessHr($user)) {
    $footerLinks[] = ['url' => ['/site/employee-portal'], 'icon' => 'person', 'label' => 'My Employee Page'];
}

echo $this->render('@frontend/views/layouts/_portal-sidebar', [
    'navItems' => $navItems,
    'currentPage' => $currentPage,
    'brandName' => 'CultureHR',
    'brandUrl' => ['/hr/hr/dashboard'],
    'userName' => $userName,
    'userRole' => $userRole,
    'footerLinks' => $footerLinks,
    'showLogout' => true,
]);

$this->registerJs(<<<'JS'
(function () {
    function digitsOnly(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function bindDigitsOnly(input) {
        if (!input || input.dataset.digitsOnlyBound === '1') {
            return;
        }
        input.dataset.digitsOnlyBound = '1';
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('pattern', '[0-9]*');
        input.setAttribute('autocomplete', input.getAttribute('autocomplete') || 'tel');

        input.addEventListener('input', function () {
            var cleaned = digitsOnly(input.value);
            if (input.value !== cleaned) {
                input.value = cleaned;
            }
        });

        input.addEventListener('paste', function (event) {
            event.preventDefault();
            var text = (event.clipboardData || window.clipboardData).getData('text') || '';
            var cleaned = digitsOnly(text);
            var start = input.selectionStart || 0;
            var end = input.selectionEnd || 0;
            var next = input.value.slice(0, start) + cleaned + input.value.slice(end);
            input.value = digitsOnly(next);
            var caret = start + cleaned.length;
            input.setSelectionRange(caret, caret);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });

        input.addEventListener('keypress', function (event) {
            if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1) {
                return;
            }
            if (!/[0-9]/.test(event.key)) {
                event.preventDefault();
            }
        });

        var initial = digitsOnly(input.value);
        if (input.value !== initial) {
            input.value = initial;
        }
    }

    function bindAll() {
        document.querySelectorAll('[data-digits-only]').forEach(bindDigitsOnly);
    }

    bindAll();
    document.addEventListener('DOMContentLoaded', bindAll);
})();
JS
);