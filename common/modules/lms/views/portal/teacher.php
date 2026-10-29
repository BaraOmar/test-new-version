<?php

/**
 * @var yii\web\View $this
 * @var common\models\User $user
 * @var common\modules\employee\models\Employee|null $employee
 * @var string $userName
 * @var string $userRole
 * @var string $currentPage
 */

use common\components\LmsPortal;
use yii\helpers\Html;
use yii\helpers\Url;

$portalType = LmsPortal::teacherPortalType($user);
$portalSubtitle = LmsPortal::teacherPortalSubtitle($user);
$leadershipTitle = LmsPortal::teacherPortalLeadershipTitle($user);
$welcomeMessage = LmsPortal::teacherPortalWelcomeMessage($user);
$userRole = LmsPortal::teacherPortalRoleLabel($user);

$this->title = $portalSubtitle . ' | LMS';

$departmentName = $employee !== null ? $employee->getReportingDepartmentName() : '—';
if ($departmentName === '' || $departmentName === '—') {
    $departmentName = '—';
}

$roleIcon = match ($portalType) {
    LmsPortal::TEACHER_PORTAL_TYPE_MANAGER => 'admin_panel_settings',
    LmsPortal::TEACHER_PORTAL_TYPE_DEPUTY => 'supervisor_account',
    default => 'school',
};

$moduleCards = [];
if (LmsPortal::canAccessStudentRegistry($user)) {
    $moduleCards[] = [
        'title' => 'Student Registry',
        'description' => 'View student profiles in your assigned scope (' . LmsPortal::studentRegistryScopeLabel($user) . ').',
        'icon' => 'groups',
        'iconClass' => 'bg-blue-50 text-blue-700',
        'url' => Url::to(['/lms/student-affairs/students']),
        'button' => 'Open Student Registry',
    ];
}
if (LmsPortal::canAccessTeacherAttendanceManagement($user)) {
    $moduleCards[] = [
        'title' => 'Attendance',
        'description' => LmsPortal::isClassTeacher($user) && !$user->isSuperHrAdmin()
            ? 'Take daily attendance for your homeroom section.'
            : 'Take and review daily student attendance.',
        'icon' => 'fact_check',
        'iconClass' => 'bg-indigo-50 text-indigo-700',
        'url' => Url::to(['/lms/attendance/index']),
        'button' => 'Take Attendance',
    ];
}
if (LmsPortal::canAccessStudentCertificateManagement($user)) {
    $employeeIsHead = $employee !== null && $employee->isSchoolDepartmentHead();
    $isHomeroomOnly = LmsPortal::isClassTeacher($user) && !$user->isSuperHrAdmin() && !$employeeIsHead;
    $moduleCards[] = [
        'title' => 'Student Certificates',
        'description' => $isHomeroomOnly
            ? 'Build academic report cards and section rankings.'
            : 'Issue academic certificates and view section rankings.',
        'icon' => 'workspace_premium',
        'iconClass' => 'bg-violet-50 text-violet-700',
        'url' => Url::to(['/lms/certificates/index']),
        'button' => 'Open Certificates',
    ];
}
if (LmsPortal::canAccessMyClasses($user)) {
    $moduleCards[] = [
        'title' => 'My Classes',
        'description' => 'Browse department sections and open courses, marks, and materials.',
        'icon' => 'class',
        'iconClass' => 'bg-sky-50 text-sky-700',
        'url' => Url::to(['/lms/my-classes/index']),
        'button' => 'Open My Classes',
    ];
}
$hierarchyLabel = LmsPortal::infrastructureHierarchyNavLabel($user);
if ($hierarchyLabel !== null) {
    $moduleCards[] = [
        'title' => $hierarchyLabel,
        'description' => LmsPortal::getInfrastructureAccessType($user) === LmsPortal::INFRASTRUCTURE_ACCESS_DEPARTMENT_LEADER
            ? 'View and manage all sections in your department.'
            : 'Manage materials and students in your assigned section.',
        'icon' => 'account_tree',
        'iconClass' => 'bg-violet-50 text-violet-700',
        'url' => Url::to(['/lms/hierarchy/index']),
        'button' => 'Open ' . $hierarchyLabel,
    ];
}
if (LmsPortal::canAccessLmsSubjects($user)) {
    $subjectsDescription = match (LmsPortal::getLmsSubjectsAccessType($user)) {
        LmsPortal::SUBJECTS_ACCESS_DEPARTMENT_LEADER => 'Create and manage department subjects, and review your teaching assignments.',
        LmsPortal::SUBJECTS_ACCESS_TEACHER => 'View your assigned subjects and the sections you teach.',
        default => 'View subjects in your learning scope.',
    };
    $moduleCards[] = [
        'title' => 'Subjects',
        'description' => $subjectsDescription,
        'icon' => 'menu_book',
        'iconClass' => 'bg-amber-50 text-amber-700',
        'url' => Url::to(['/lms/subjects/index']),
        'button' => 'Open Subjects',
    ];
}
if (LmsPortal::isDepartmentTimetableManager($user)) {
    $timetableDescription = $user->isSuperHrAdmin()
        ? 'Build and manage section timetables across all departments.'
        : 'Build and manage section timetables across your department.';
    $moduleCards[] = [
        'title' => 'Timetable',
        'description' => $timetableDescription,
        'icon' => 'calendar_month',
        'iconClass' => 'bg-sky-50 text-sky-700',
        'url' => Url::to(['/lms/timetable/index']),
        'button' => 'Open Timetable',
    ];
}
if (LmsPortal::canAccessOwnInstructorSchedule($user) || LmsPortal::canEditOwnLessonBrief($user)) {
    $moduleCards[] = [
        'title' => 'My Schedule',
        'description' => 'View your assigned teaching periods across grades and sections in your department.',
        'icon' => 'calendar_month',
        'iconClass' => 'bg-sky-50 text-sky-700',
        'url' => Url::to(['/lms/timetable/my-schedule']),
        'button' => 'View My Schedule',
    ];
}

?>
<?= $this->render('../layouts/_page-start') ?>
    <header>
        <h2 class="text-3xl font-black text-slate-900 tracking-tight">Welcome, <?= Html::encode($userName) ?></h2>
        <p class="text-slate-500 mt-2 text-lg"><?= Html::encode($welcomeMessage) ?></p>
    </header>

    <?php if ($leadershipTitle !== null): ?>
        <div class="rounded-2xl border border-[#642754]/20 bg-gradient-to-r from-[#642754]/10 to-[#eb652c]/10 px-6 py-5 flex items-start gap-4">
            <div class="w-12 h-12 shrink-0 rounded-xl bg-white flex items-center justify-center text-[#642754] shadow-sm">
                <span class="material-symbols-outlined text-2xl"><?= Html::encode($roleIcon) ?></span>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-[#642754]"><?= Html::encode($portalSubtitle) ?></p>
                <p class="text-xl font-bold text-slate-900 mt-1"><?= Html::encode($leadershipTitle) ?></p>
                <p class="text-sm text-slate-600 mt-2 leading-relaxed"><?= Html::encode($welcomeMessage) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Overview</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between min-h-[140px]">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">Role</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1"><?= Html::encode($userRole) ?></p>
                    </div>
                    <div class="w-12 h-12 shrink-0 rounded-xl flex items-center justify-center bg-[#642754]/10 text-[#642754]">
                        <span class="material-symbols-outlined"><?= Html::encode($roleIcon) ?></span>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between min-h-[140px]">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">School Department</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1"><?= Html::encode($departmentName) ?></p>
                    </div>
                    <div class="w-12 h-12 shrink-0 rounded-xl flex items-center justify-center bg-[#eb652c]/10 text-[#eb652c]">
                        <span class="material-symbols-outlined">account_balance</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Modules</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($moduleCards as $card): ?>
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-shadow flex flex-col items-center text-center h-full min-h-[360px]">
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shrink-0 <?= Html::encode($card['iconClass']) ?>">
                        <span class="material-symbols-outlined text-4xl"><?= Html::encode($card['icon']) ?></span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3"><?= Html::encode($card['title']) ?></h3>
                    <p class="text-slate-500 mb-8 max-w-sm leading-relaxed flex-1"><?= Html::encode($card['description']) ?></p>
                    <a href="<?= Html::encode($card['url']) ?>"
                       class="w-full bg-[#eb652c] hover:bg-[#d85a24] text-white font-bold py-3.5 px-6 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 mt-auto no-underline">
                        <?= Html::encode($card['button']) ?>
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?= $this->render('../layouts/_page-end') ?>
