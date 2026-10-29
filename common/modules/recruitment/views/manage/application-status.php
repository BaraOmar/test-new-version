<?php

/**
 * @var yii\web\View $this
 * @var common\modules\recruitment\models\Application[] $applications
 * @var int $highlightId
 * @var string[] $indexRoute
 * @var string[] $applicationsRoute
 */

use common\modules\recruitment\helpers\RecruitmentUi;
use common\modules\recruitment\models\Application as JobApplication;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Application Status';

?>

<div class="max-w-5xl mx-auto space-y-6">
    <?= $this->render('_breadcrumb', [
        'indexRoute' => $indexRoute,
        'crumbs' => [
            ['label' => 'Applications & Interviews', 'url' => $applicationsRoute],
            ['label' => 'Application Status'],
        ],
    ]) ?>

    <header>
        <h1 class="text-2xl lg:text-3xl font-black text-slate-900">Application Status</h1>
        <p class="text-slate-500 text-sm mt-1">Track recruitment progress for this candidate across all applied positions.</p>
    </header>

    <?php if ($applications === []): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
            <p class="text-slate-600">No applications found.</p>
            <a href="<?= Url::to($applicationsRoute) ?>" class="inline-flex mt-4 text-[#642754] font-semibold no-underline">Back to applications</a>
        </div>
    <?php endif; ?>

    <?php foreach ($applications as $application): ?>
        <?php
        $job = $application->job;
        $applicant = $application->applicant;
        $timeline = RecruitmentUi::applicationTimeline($application);
        $highlight = (int)$application->application_id === $highlightId;
        $status = JobApplication::displayStatus($application->application_status);
        ?>
        <article class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm <?= $highlight ? 'ring-2 ring-[#642754]/30' : '' ?>">
            <div class="p-6">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8">
                    <div class="flex gap-4">
                        <div class="size-14 rounded-lg bg-[#642754]/10 flex items-center justify-center shrink-0 border border-[#642754]/20">
                            <span class="material-symbols-outlined text-[#642754] text-3xl">work</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900"><?= Html::encode($job->job_title ?? '') ?></h3>
                            <p class="text-slate-500 text-sm font-medium"><?= Html::encode($job->department ?? '') ?> • <?= Html::encode($job->location ?? '') ?></p>
                            <p class="text-slate-400 text-xs mt-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">calendar_today</span>
                                Applied <?= Html::encode($application->applied_at ? date('M j, Y', strtotime($application->applied_at)) : '—') ?>
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex self-start items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold uppercase <?= RecruitmentUi::statusBadgeClass($status) ?>">
                        <?php if ($status === JobApplication::STATUS_INTERVIEWING): ?>
                            <span class="size-2 bg-[#eb652c] rounded-full animate-pulse"></span>
                        <?php endif; ?>
                        <?= Html::encode($status) ?>
                    </span>
                </div>

                <div class="relative mb-6">
                    <div class="absolute top-5 left-0 w-full h-0.5 bg-slate-100 z-0"></div>
                    <div class="relative z-10 flex justify-between items-start gap-1">
                        <?php foreach ($timeline as $step): ?>
                            <?php
                            $done = $step['state'] === 'done';
                            $current = $step['state'] === 'current';
                            $upcoming = $step['state'] === 'upcoming';
                            ?>
                            <div class="flex flex-col items-center gap-2 w-1/5 <?= $upcoming ? 'opacity-50' : '' ?>">
                                <div class="size-10 rounded-full flex items-center justify-center
                                    <?= $done ? 'bg-[#642754] text-white' : ($current ? 'bg-white border-2 border-[#eb652c] text-[#eb652c] shadow-[0_0_0_4px_rgba(235,101,44,0.2)]' : 'bg-slate-100 border border-slate-200 text-slate-400') ?>">
                                    <span class="material-symbols-outlined text-[20px]"><?= $done ? 'check' : Html::encode($step['icon']) ?></span>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-bold <?= $current ? 'text-[#642754]' : 'text-slate-700' ?>"><?= Html::encode($step['label']) ?></p>
                                    <p class="text-[10px] <?= $current ? 'text-[#eb652c] font-medium' : 'text-slate-400' ?>"><?= Html::encode($step['sublabel']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($application->interview_at && $status === JobApplication::STATUS_INTERVIEWING): ?>
                    <div class="bg-slate-50 rounded-lg p-5 border border-slate-100">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-[#642754]">info</span>
                            <p class="text-sm font-bold text-slate-900">Interview details</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-[11px] font-bold uppercase text-slate-500">Date & time</p>
                                <p class="text-slate-800"><?= Html::encode(date('F j, Y - g:i A', strtotime($application->interview_at))) ?></p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold uppercase text-slate-500">Type</p>
                                <p class="text-slate-800"><?= Html::encode($application->interview_type ?? '—') ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex justify-end">
                <a href="<?= Url::to(array_merge($applicationsRoute, ['id' => $application->application_id])) ?>"
                   class="text-sm font-semibold text-[#642754] no-underline">Open in applications →</a>
            </div>
        </article>
    <?php endforeach; ?>

    <a href="<?= Url::to($applicationsRoute) ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 no-underline hover:text-[#642754]">
        <span class="material-symbols-outlined text-lg">arrow_back</span>
        Back to applications
    </a>
</div>
