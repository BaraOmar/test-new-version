<?php

/**
 * @var yii\web\View $this
 * @var string[] $indexRoute
 * @var string[] $jobsRoute
 * @var string[] $jobFormRoute
 * @var string[] $applicationsRoute
 * @var string[] $reportsRoute
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Recruit Management';

$cards = [
    [
        'title' => 'Post New Job',
        'description' => 'Create and publish new job openings to attract the best talent.',
        'icon' => 'add_circle',
        'iconClass' => 'bg-[#eb652c]/10 text-[#eb652c]',
        'url' => $jobFormRoute,
        'button' => 'Create Posting',
    ],
    [
        'title' => 'Job Management',
        'description' => 'Edit, close, or manage your active and draft job listings.',
        'icon' => 'work_history',
        'iconClass' => 'bg-[#642754]/10 text-[#642754]',
        'url' => $jobsRoute,
        'button' => 'View Jobs',
    ],
    [
        'title' => 'Applications',
        'description' => 'Review candidate profiles, screen resumes, and move pipeline stages.',
        'icon' => 'description',
        'iconClass' => 'bg-emerald-50 text-emerald-600',
        'url' => $applicationsRoute,
        'button' => 'Review Applicants',
    ],
    [
        'title' => 'Reports',
        'description' => 'Analyze recruitment data, hiring velocity, and application trends.',
        'icon' => 'bar_chart',
        'iconClass' => 'bg-amber-50 text-amber-600',
        'url' => $reportsRoute,
        'button' => 'Generate Reports',
    ],
];

?>

<div class="max-w-5xl mx-auto space-y-10">
    <header class="text-center lg:text-left">
        <h1 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">Recruiter Dashboard Hub</h1>
        <p class="text-slate-500 mt-2 text-lg">Select a section below to manage your recruitment portal.</p>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
        <?php foreach ($cards as $card): ?>
            <div class="group bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg hover:border-[#642754]/20 transition-all flex flex-col items-center text-center min-h-[320px]">
                <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 transition-transform group-hover:scale-105 <?= Html::encode($card['iconClass']) ?>">
                    <span class="material-symbols-outlined text-5xl"><?= Html::encode($card['icon']) ?></span>
                </div>
                <div class="space-y-2 flex-1">
                    <h2 class="text-xl font-bold text-slate-900"><?= Html::encode($card['title']) ?></h2>
                    <p class="text-slate-500 text-sm max-w-xs mx-auto leading-relaxed"><?= Html::encode($card['description']) ?></p>
                </div>
                <a href="<?= Url::to($card['url']) ?>"
                   class="w-full mt-6 font-bold py-3.5 rounded-xl transition-all flex items-center justify-center gap-2 no-underline bg-[#642754] hover:bg-[#5d245f] text-white shadow-md">
                    <?= Html::encode($card['button']) ?>
                    <span class="material-symbols-outlined text-lg">arrow_forward</span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
