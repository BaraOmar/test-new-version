<?php

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var string $search
 * @var string $status
 * @var string $department
 * @var array{total:int,published:int,draft:int,closed:int} $stats
 * @var string[] $departments
 * @var string[] $indexRoute
 * @var string[] $jobFormRoute
 */

use common\modules\recruitment\models\Job;
use common\modules\recruitment\helpers\RecruitmentUi;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Job Management';

$models = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();

?>

<div class="max-w-[1400px] mx-auto">
    <?= $this->render('_breadcrumb', [
        'indexRoute' => $indexRoute,
        'crumbs' => [['label' => 'Job Management']],
    ]) ?>

    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Job Management</h1>
            <p class="text-slate-500 mt-1">Edit, close, or promote your job listings.</p>
        </div>
        <a href="<?= Url::to($jobFormRoute) ?>"
           class="bg-[#eb652c] hover:bg-[#d85a24] text-white px-5 h-11 flex items-center gap-2 rounded-xl font-semibold shadow-md no-underline">
            <span class="material-symbols-outlined">add</span>
            Post new job
        </a>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <?php
        $statCards = [
            ['Total Jobs', $stats['total'] ?? 0, 'work'],
            ['Published', $stats['published'] ?? 0, 'public'],
            ['Drafts', $stats['draft'] ?? 0, 'edit_note'],
            ['Closed', $stats['closed'] ?? 0, 'archive'],
        ];
        foreach ($statCards as [$label, $value, $icon]):
        ?>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide"><?= Html::encode($label) ?></p>
                        <p class="text-2xl font-black text-slate-900 mt-1"><?= (int)$value ?></p>
                    </div>
                    <span class="material-symbols-outlined text-[#642754] text-3xl opacity-80"><?= Html::encode($icon) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?= Html::beginForm(['jobs'], 'get', ['class' => 'bg-white rounded-xl border border-slate-200 p-4 mb-6 flex flex-wrap gap-4 items-end']) ?>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Search title</label>
            <?= Html::textInput('search', $search, ['class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 text-sm', 'placeholder' => 'Search title, department, location']) ?>
        </div>
        <div class="w-48">
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Department</label>
            <?= Html::dropDownList('department', $department, ['' => 'All Departments'] + array_combine($departments, $departments), ['class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 text-sm']) ?>
        </div>
        <div class="w-40">
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
            <?= Html::dropDownList('status', $status, ['' => 'All Status'] + Job::statusOptions(), ['class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 text-sm']) ?>
        </div>
        <button type="submit" class="h-11 px-6 bg-[#642754] hover:bg-[#5d245f] text-white rounded-lg text-sm font-semibold inline-flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">search</span>
            Search
        </button>
    <?= Html::endForm() ?>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <?php if ($models === []): ?>
            <p class="p-10 text-center text-slate-500">No jobs found.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-left text-xs font-bold text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Department</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Applications</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($models as $job): ?>
                            <?php /** @var Job $job */ ?>
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3 font-bold text-slate-900"><?= Html::encode($job->job_title) ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= Html::encode($job->department ?: '—') ?></td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-bold px-3 py-1 rounded-full <?= RecruitmentUi::jobStatusBadgeClass($job->status) ?>">
                                        <?= Html::encode(ucfirst($job->status)) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3"><?= count($job->applications) ?></td>
                                <td class="px-4 py-3 text-slate-500"><?= $job->created_at ? Html::encode(date('M d, Y', strtotime($job->created_at))) : '—' ?></td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="<?= Url::to(array_merge($jobFormRoute, ['id' => $job->job_id])) ?>" class="text-[#642754] font-semibold hover:underline mr-3">Edit</a>
                                    <?php if ($job->status === Job::STATUS_CLOSED): ?>
                                        <?= Html::beginForm(['open-job', 'id' => $job->job_id], 'post', ['class' => 'inline']) ?>
                                            <button type="submit" class="text-emerald-600 font-semibold hover:underline mr-3" onclick="return confirm('Reopen this job? It will be published and visible to applicants again.')">Open</button>
                                        <?= Html::endForm() ?>
                                    <?php else: ?>
                                        <?= Html::beginForm(['close-job', 'id' => $job->job_id], 'post', ['class' => 'inline']) ?>
                                            <button type="submit" class="text-amber-600 font-semibold hover:underline mr-3" onclick="return confirm('Close this job?')">Close</button>
                                        <?= Html::endForm() ?>
                                    <?php endif; ?>
                                    <?= Html::beginForm(['delete-job', 'id' => $job->job_id], 'post', ['class' => 'inline']) ?>
                                        <button type="submit" class="text-red-600 font-semibold hover:underline" onclick="return confirm('Delete permanently?')">Delete</button>
                                    <?= Html::endForm() ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination && $pagination->pageCount > 1): ?>
                <div class="px-4 py-3 border-t border-slate-100"><?= LinkPager::widget(['pagination' => $pagination]) ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
