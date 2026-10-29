<?php

/**
 * @var yii\web\View $this
 * @var array{employees:int,surveyRate:int,evalRate:int,surveys:int,evaluations:int,requests:int} $stats
 * @var string $userName
 * @var array<string, bool> $moduleAccess
 */

use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolStudent;
use common\modules\finance\models\FinanceAccountParentProfile;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'HR Management Hub - CultureHR';

$moduleAccess = $moduleAccess ?? [];

$statCards = [];
if (!empty($moduleAccess['employees'])) {
    $statCards[] = [
        'label' => 'Total Employees',
        'value' => (string)(int)$stats['employees'],
        'icon' => 'badge',
        'iconClass' => 'bg-[#eb652c]/10 text-[#eb652c]',
        'footer' => '<span class="text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded mr-2 inline-flex items-center gap-1"><span class="material-symbols-outlined text-sm">trending_up</span>2%</span><span class="text-slate-400">vs last month</span>',
    ];
}
if (!empty($moduleAccess['surveys'])) {
    $statCards[] = [
        'label' => 'Survey Response Rate',
        'value' => (int)$stats['surveyRate'] . '%',
        'icon' => 'fact_check',
        'iconClass' => 'bg-[#3cd458]/10 text-[#3cd458]',
        'footer' => '<span class="text-[#3cd458] font-bold bg-[#3cd458]/10 px-2 py-0.5 rounded-lg mr-2">On Track</span><span class="text-slate-400">Target: 80%</span>',
    ];
}
if (!empty($moduleAccess['evaluations'])) {
    $statCards[] = [
        'label' => 'Evaluation Response Rate',
        'value' => (int)$stats['evalRate'] . '%',
        'icon' => 'fact_check',
        'iconClass' => 'bg-[#3cd458]/10 text-[#3cd458]',
        'footer' => '<span class="text-[#3cd458] font-bold bg-[#3cd458]/10 px-2 py-0.5 rounded-lg mr-2">On Track</span><span class="text-slate-400">Target: 80%</span>',
    ];
}

$moduleCards = [];
if (!empty($moduleAccess['employees'])) {
    $moduleCards[] = [
        'title' => 'Employee Management',
        'description' => 'Add new team members, update profiles, and organize departmental structures.',
        'icon' => 'person_add',
        'iconClass' => 'bg-[#eb652c]/10 text-[#eb652c]',
        'url' => Url::to(['/hr/hr/employees']),
        'button' => 'Manage Employees',
    ];
}
if (!empty($moduleAccess['employees']) && SchoolDepartment::isEnabled()) {
    $moduleCards[] = [
        'title' => 'School Departments',
        'description' => 'Manage academic departments, grades, sections, and school infrastructure.',
        'icon' => 'account_balance',
        'iconClass' => 'bg-[#642754]/10 text-[#642754]',
        'url' => Url::to(['/hr/school-department/index']),
        'button' => 'Open School Departments',
    ];
    $moduleCards[] = [
        'title' => 'Subjects',
        'description' => 'Create subjects, assign grade credits, and manage the academic catalog.',
        'icon' => 'menu_book',
        'iconClass' => 'bg-emerald-50 text-emerald-600',
        'url' => Url::to(['/hr/school-department/subjects']),
        'button' => 'Open Subjects',
    ];
    if (SchoolStudent::isEnabled()) {
        $moduleCards[] = [
            'title' => 'Student Affairs',
            'description' => 'Manage enrollment, student profiles, and administrative records.',
            'icon' => 'school',
            'iconClass' => 'bg-blue-50 text-blue-700',
            'url' => Url::to(['/hr/student-affairs/students']),
            'button' => 'Open Student Affairs',
        ];
    }
    if (FinanceAccountParentProfile::isEnabled()) {
        $moduleCards[] = [
            'title' => 'Parents Directory',
            'description' => 'Browse registered parents, view their details, and update parent and children records.',
            'icon' => 'family_restroom',
            'iconClass' => 'bg-blue-50 text-blue-700',
            'url' => Url::to(['/hr/parents/index']),
            'button' => 'Open Parents Directory',
        ];
    }
}
if (!empty($moduleAccess['surveys'])) {
    $moduleCards[] = [
        'title' => 'Survey Management',
        'description' => 'Create and distribute engagement surveys to gather valuable feedback.',
        'icon' => 'content_paste',
        'iconClass' => 'bg-emerald-50 text-emerald-600',
        'url' => Url::to(['/hr/survey/index']),
        'button' => 'View Survey',
    ];
}
if (!empty($moduleAccess['evaluations'])) {
    $moduleCards[] = [
        'title' => 'Evaluation Management',
        'description' => 'Create and distribute evaluations to gather valuable feedback.',
        'icon' => 'bar_chart',
        'iconClass' => 'bg-emerald-50 text-emerald-600',
        'url' => Url::to(['/hr/evaluation/index']),
        'button' => 'View Evaluation',
    ];
}
if (!empty($moduleAccess['recruitment'])) {
    $moduleCards[] = [
        'title' => 'Recruit Management',
        'description' => 'Post job openings, manage applicants, schedule interviews, and hire new team members.',
        'icon' => 'person_search',
        'iconClass' => 'bg-[#642754]/10 text-[#642754]',
        'url' => Url::to(['/hr/recruitment/index']),
        'button' => 'Open Recruit Management',
    ];
}
if (SchoolStudent::isEnabled()
    && (!empty($moduleAccess['student_health']) || !empty($moduleAccess['employees']))) {
    $moduleCards[] = [
        'title' => 'Student Health',
        'description' => 'Manage student health files, record clinic visits, and review medical history.',
        'icon' => 'health_and_safety',
        'iconClass' => 'bg-rose-50 text-rose-700',
        'url' => Url::to(['/hr/student-health/index']),
        'button' => 'Open Student Health',
    ];
}
if (!empty($moduleAccess['requests'])) {
    $moduleCards[] = [
        'title' => 'Change Requests',
        'description' => 'Review and approve employee profile and employment change requests.',
        'icon' => 'pending_actions',
        'iconClass' => 'bg-[#eb652c]/10 text-[#eb652c]',
        'url' => Url::to(['/hr/hr/requests']),
        'button' => 'View Requests',
    ];
}
if (!empty($moduleAccess['lms'])) {
    $moduleCards[] = [
        'title' => 'LMS',
        'description' => 'Improve employee learning and development through our integrated Learning Management System.',
        'icon' => 'school',
        'iconClass' => 'bg-[#eb652c]/10 text-[#eb652c]',
        'url' => Url::to(['/lms/portal/teacher']),
        'button' => 'View LMS',
    ];
}

if (!empty($moduleAccess['manage_hr'])) {
    $moduleCards[] = [
        'title' => 'Manage HR',
        'description' => 'Provision HR accounts, assign module permissions, and maintain administrator access.',
        'icon' => 'admin_panel_settings',
        'iconClass' => 'bg-blue-50 text-blue-600',
        'url' => Url::to(['/hr/hr/manage-hr']),
        'button' => 'Manage HR Users',
    ];
}

$gridCols = static function (int $count): string {
    if ($count <= 1) {
        return 'grid-cols-1 max-w-md';
    }
    if ($count === 2) {
        return 'grid-cols-1 sm:grid-cols-2';
    }

    return 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3';
};

/** Module-style cards always use the same responsive grid (no narrow single-card column). */
$moduleGridCols = 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3';

?>

<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => 'dashboard']) ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Dashboard', 'userName' => $userName]) ?>
        <div class="flex-1 overflow-y-auto bg-gray-50">
            <div class="w-full min-w-0 px-6 lg:px-10 xl:px-12 py-8 space-y-10">
                <header>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight">HR Management Hub</h2>
                    <p class="text-slate-500 mt-2 text-lg">Select a section below to manage your organization.</p>
                </header>

                <?php if ($statCards !== []): ?>
                <section>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Overview</h3>
                    <div class="grid <?= $gridCols(count($statCards)) ?> gap-6 w-full">
                        <?php foreach ($statCards as $card): ?>
                            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between min-h-[140px]">
                                <div class="flex justify-between items-start gap-4 mb-4">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-500 uppercase tracking-wide"><?= Html::encode($card['label']) ?></p>
                                        <p class="text-3xl font-bold text-slate-900 mt-1"><?= Html::encode($card['value']) ?></p>
                                    </div>
                                    <div class="w-12 h-12 shrink-0 rounded-xl flex items-center justify-center <?= Html::encode($card['iconClass']) ?>">
                                        <span class="material-symbols-outlined"><?= Html::encode($card['icon']) ?></span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center text-sm gap-1"><?= $card['footer'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($moduleCards !== []): ?>
                <section>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Modules</h3>
                    <div class="grid <?= $moduleGridCols ?> gap-6 w-full">
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
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
