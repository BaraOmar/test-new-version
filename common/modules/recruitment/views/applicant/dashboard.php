<?php

/**
 * @var yii\web\View $this
 * @var common\modules\recruitment\models\Job[] $jobs
 * @var string $search
 * @var string $department
 * @var string[] $departments
 * @var int[] $appliedJobIds
 */

use common\modules\recruitment\models\Job;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Active Job Openings | CultureHR Careers';

?>

<div class="max-w-[1400px] mx-auto p-6 lg:p-10">
    <h2 class="text-3xl font-extrabold text-slate-900 mb-8">Active Job Openings</h2>

    <?= Html::beginForm(['dashboard'], 'get', [
        'class' => 'bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex flex-wrap gap-4 mb-10 items-end',
    ]) ?>
        <div class="relative flex-1 min-w-[250px]">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
            <?= Html::textInput('search', $search, [
                'class' => 'pl-10 h-11 w-full rounded-lg border border-slate-300 focus:ring-2 focus:ring-[#642754]/30 focus:border-[#642754] outline-none',
                'placeholder' => 'Search job title...',
            ]) ?>
        </div>
        <?= Html::dropDownList('department', $department, array_merge(['' => 'All Departments'], array_combine($departments, $departments)), [
            'class' => 'h-11 rounded-lg border border-slate-300 px-4 min-w-[200px] focus:ring-2 focus:ring-[#642754]/30 focus:border-[#642754] outline-none bg-white',
        ]) ?>
        <button type="submit" class="h-11 px-8 rounded-lg bg-[#642754] hover:bg-[#58264a] text-white font-bold transition-colors">
            Search
        </button>
    <?= Html::endForm() ?>

    <?php if ($jobs === []): ?>
        <p class="text-slate-400 text-lg">No jobs found.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($jobs as $job): ?>
                <?php
                /** @var Job $job */
                $applied = in_array((int)$job->job_id, $appliedJobIds, true);
                ?>
                <article class="bg-white rounded-xl border border-slate-200 p-6 hover:shadow-lg hover:border-[#642754]/40 transition relative flex flex-col">
                    <?php if ($applied): ?>
                        <span class="absolute top-4 right-4 text-xs font-bold bg-[#642754] text-white px-3 py-1 rounded-full">Applied</span>
                    <?php endif; ?>

                    <h3 class="text-lg font-bold text-slate-900 mb-1 pr-20"><?= Html::encode($job->job_title) ?></h3>
                    <p class="text-sm text-slate-500"><?= Html::encode($job->department ?: 'General') ?></p>
                    <p class="text-sm text-slate-400 mt-2 line-clamp-2 flex-1">
                        <?= Html::encode(mb_strimwidth(strip_tags((string)$job->description), 0, 200, '...')) ?>
                    </p>

                    <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                        <div class="text-xs text-slate-500 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">location_on</span>
                            <?= Html::encode($job->location ?: '—') ?>
                        </div>
                        <?php if (!$applied): ?>
                            <?= Html::beginForm(['apply', 'id' => $job->job_id], 'post', ['class' => 'm-0']) ?>
                                <button type="submit" class="text-[#642754] font-bold text-sm hover:underline flex items-center gap-1 bg-transparent border-0 cursor-pointer p-0">
                                    Apply Now
                                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                                </button>
                            <?= Html::endForm() ?>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
