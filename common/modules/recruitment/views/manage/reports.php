<?php

/**
 * @var yii\web\View $this
 * @var array $report
 * @var int $jobId
 * @var int $days
 * @var string[] $indexRoute
 * @var string[] $reportsRoute
 * @var string[] $applicationsRoute
 */

use common\modules\recruitment\models\Job;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Recruitment Reports';

$metrics = $report['metrics'] ?? ['total' => 0, 'pending' => 0, 'hired' => 0];
$statusDistribution = $report['statusDistribution'] ?? [];
$trend = $report['trend'] ?? [];
$applicants = $report['applicants'] ?? [];
$jobs = $report['jobs'] ?? [];

$openJobs = 0;
foreach ($jobs as $job) {
    if ($job instanceof Job && $job->status === 'published') {
        $openJobs++;
    }
}

$interviewCount = 0;
foreach ($statusDistribution as $status => $count) {
    if ($status === 'Interviewing') {
        $interviewCount = (int)$count;
    }
}

$maxStatus = max(1, ...array_values($statusDistribution ?: [1]));
$maxWeek = max(1, ...array_map(static fn ($w) => (int)($w['count'] ?? 0), $trend ?: [['count' => 1]]));

$jobOptions = ['' => 'All jobs'];
foreach ($jobs as $job) {
    if ($job instanceof Job) {
        $jobOptions[(string)$job->job_id] = $job->job_title;
    }
}

?>

<div class="space-y-6">
    <?= $this->render('_breadcrumb', [
        'indexRoute' => $indexRoute,
        'crumbs' => [['label' => 'Report generating']],
    ]) ?>

    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900">Recruitment analytics</h1>
            <p class="text-slate-500 text-sm mt-1">Metrics, trends, and exportable applicant data</p>
        </div>
        <a href="<?= Url::to(['export-report', 'job_id' => $jobId, 'days' => $days]) ?>"
           class="h-11 px-5 bg-[#eb652c] hover:bg-[#d85a24] text-white rounded-lg text-sm font-semibold inline-flex items-center gap-2 no-underline shrink-0">
            <span class="material-symbols-outlined text-lg">download</span>
            Export CSV
        </a>
    </div>

    <?= Html::beginForm($reportsRoute, 'get', ['class' => 'bg-white rounded-xl border border-slate-200 p-4 flex flex-wrap gap-4 items-end']) ?>
        <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">Job</label>
            <?= Html::dropDownList('job_id', $jobId ?: '', $jobOptions, ['class' => 'block h-10 px-3 rounded-lg border border-slate-200 text-sm mt-1 min-w-[200px]']) ?>
        </div>
        <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">Period</label>
            <?= Html::dropDownList('days', $days, [30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last year'], ['class' => 'block h-10 px-3 rounded-lg border border-slate-200 text-sm mt-1']) ?>
        </div>
        <button type="submit" class="h-10 px-5 bg-[#642754] text-white rounded-lg text-sm font-semibold">Apply filters</button>
    <?= Html::endForm() ?>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
        $cards = [
            ['label' => 'Total applications', 'value' => $metrics['total'] ?? 0, 'icon' => 'groups'],
            ['label' => 'Open positions', 'value' => $openJobs, 'icon' => 'work'],
            ['label' => 'In pipeline', 'value' => $metrics['pending'] ?? 0, 'icon' => 'pending_actions'],
            ['label' => 'Hired', 'value' => $metrics['hired'] ?? 0, 'icon' => 'celebration'],
        ];
        foreach ($cards as $card):
        ?>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-slate-500 uppercase"><?= Html::encode($card['label']) ?></span>
                    <span class="material-symbols-outlined text-[#642754]"><?= $card['icon'] ?></span>
                </div>
                <p class="text-3xl font-black text-slate-900"><?= (int)$card['value'] ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-bold text-slate-900 mb-4">Status distribution</h3>
            <div class="space-y-3">
                <?php foreach ($statusDistribution as $status => $count): ?>
                    <?php $pct = round(100 * $count / $maxStatus); ?>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-slate-700"><?= Html::encode($status) ?></span>
                            <span class="text-slate-500"><?= (int)$count ?></span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-[#642754] rounded-full" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if ($statusDistribution === []): ?>
                    <p class="text-sm text-slate-500">No data for selected filters.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-bold text-slate-900 mb-1">Applications per week</h3>
            <p class="text-xs text-slate-500 mb-4"><?= (int)$interviewCount ?> currently in interview stage</p>
            <div class="flex items-end gap-2 h-40">
                <?php foreach ($trend as $week): ?>
                    <?php $h = max(8, (int)round(120 * ((int)($week['count'] ?? 0)) / $maxWeek)); ?>
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[10px] font-bold text-slate-600"><?= (int)($week['count'] ?? 0) ?></span>
                        <div class="w-full bg-[#642754]/80 rounded-t" style="height: <?= $h ?>px"></div>
                        <span class="text-[9px] text-slate-400 truncate w-full text-center"><?= Html::encode($week['label'] ?? '') ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($trend === []): ?>
                    <p class="text-sm text-slate-500">No trend data.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-bold text-slate-900">Applicant report</h3>
            <a href="<?= Url::to($applicationsRoute) ?>" class="text-sm font-semibold text-[#642754] no-underline">View all applications</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold">
                    <tr>
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Job</th>
                        <th class="px-6 py-3">Department</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Applied</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($applicants as $row): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium text-slate-900"><?= Html::encode($row['full_name'] ?? '') ?></td>
                            <td class="px-6 py-3 text-slate-600"><?= Html::encode($row['email'] ?? '') ?></td>
                            <td class="px-6 py-3 text-slate-600"><?= Html::encode($row['job_title'] ?? '') ?></td>
                            <td class="px-6 py-3 text-slate-600"><?= Html::encode($row['department'] ?? '') ?></td>
                            <td class="px-6 py-3"><?= Html::encode($row['application_status'] ?? '') ?></td>
                            <td class="px-6 py-3 text-slate-500"><?= Html::encode(!empty($row['applied_at']) ? date('M j, Y', strtotime($row['applied_at'])) : '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($applicants === []): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">No applicants in range.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
