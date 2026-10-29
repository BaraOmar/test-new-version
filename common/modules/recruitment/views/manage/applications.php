<?php

/**
 * @var yii\web\View $this
 * @var common\modules\recruitment\models\Application[] $applications
 * @var common\modules\recruitment\models\Application|null $selected
 * @var string $filter
 * @var string $search
 * @var int $selectedId
 * @var int $newCount
 * @var string[] $indexRoute
 * @var string[] $applicationsRoute
 */

use common\modules\recruitment\helpers\RecruitmentUi;
use common\modules\recruitment\models\Application as JobApplication;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Applications & Interviews';

$filterOptions = ['All' => 'All', JobApplication::STATUS_SCREENED => 'Screened', JobApplication::STATUS_REJECTED => 'Rejected'];

?>

<div class="flex flex-col flex-1 min-h-0">
    <div class="px-6 lg:px-8 pt-6 pb-3 bg-white border-b border-slate-200 shrink-0">
        <?= $this->render('_breadcrumb', [
            'indexRoute' => $indexRoute,
            'crumbs' => [['label' => 'Applications & Interviews']],
        ]) ?>
    </div>

    <div class="flex flex-1 min-h-0 overflow-hidden">
        <aside class="w-full max-w-[380px] bg-white border-r border-slate-200 flex flex-col shrink-0">
            <div class="p-4 border-b border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-lg text-slate-900">Applicants</h2>
                    <?php if ($newCount > 0): ?>
                        <span class="bg-[#642754]/10 text-[#642754] text-xs font-bold px-2 py-1 rounded-full"><?= (int)$newCount ?> Pending</span>
                    <?php endif; ?>
                </div>
                <div class="flex gap-2 mb-3">
                    <?php foreach ($filterOptions as $key => $label): ?>
                        <a href="<?= Url::to(['applications', 'filter' => $key, 'q' => $search, 'id' => $selectedId]) ?>"
                           class="flex-1 text-center text-xs font-bold py-2 px-2 rounded-lg no-underline <?= $filter === $key ? 'bg-[#642754] text-white' : 'bg-slate-100 text-slate-600 hover:bg-[#642754]/10' ?>">
                            <?= Html::encode($label) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?= Html::beginForm(['applications'], 'get', ['class' => 'relative']) ?>
                    <?= Html::hiddenInput('filter', $filter) ?>
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-lg">search</span>
                    <?= Html::textInput('q', $search, [
                        'class' => 'w-full pl-10 pr-3 py-2 bg-slate-100 border-0 rounded-lg text-sm focus:ring-2 focus:ring-[#642754]/30',
                        'placeholder' => 'Filter by name or job',
                    ]) ?>
                <?= Html::endForm() ?>
            </div>
            <div class="flex-1 overflow-y-auto">
                <?php if ($applications === []): ?>
                    <p class="p-6 text-sm text-slate-500">No applications found yet.</p>
                <?php endif; ?>
                <?php foreach ($applications as $app): ?>
                    <?php
                    $active = (int)$app->application_id === $selectedId;
                    $applicant = $app->applicant;
                    $status = JobApplication::displayStatus($app->application_status);
                    ?>
                    <a href="<?= Url::to(['applications', 'id' => $app->application_id, 'filter' => $filter, 'q' => $search]) ?>"
                       class="block p-4 border-b border-slate-100 hover:bg-[#642754]/5 transition-colors no-underline <?= $active ? 'border-l-4 border-l-[#642754] bg-[#642754]/5' : 'border-l-4 border-l-transparent' ?>">
                        <div class="flex gap-3">
                            <div class="size-11 rounded-full bg-[#642754]/10 text-[#642754] font-bold flex items-center justify-center shrink-0">
                                <?= strtoupper(substr((string)($applicant->full_name ?? 'U'), 0, 1)) ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex justify-between gap-2">
                                    <h4 class="font-bold text-sm text-slate-900 truncate"><?= Html::encode($applicant->full_name ?? 'Unknown') ?></h4>
                                    <span class="text-[10px] text-slate-400 shrink-0"><?= Html::encode(RecruitmentUi::timeAgo($app->applied_at)) ?></span>
                                </div>
                                <?php
                                $appliedJobTitle = trim((string)($app->job->job_title ?? ''));
                                $applicantHeadline = trim((string)($applicant->headline ?? ''));
                                ?>
                                <p class="text-xs text-slate-600 truncate mb-1" title="<?= Html::encode($appliedJobTitle) ?>">
                                    <span class="font-semibold text-slate-800"><?= Html::encode($appliedJobTitle !== '' ? $appliedJobTitle : '—') ?></span>
                                    <?php if ($applicantHeadline !== '' && strcasecmp($applicantHeadline, $appliedJobTitle) !== 0): ?>
                                        <span class="text-slate-400"> · <?= Html::encode($applicantHeadline) ?></span>
                                    <?php endif; ?>
                                </p>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?= RecruitmentUi::statusBadgeClass($status) ?>"><?= Html::encode($status) ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <section class="flex-1 bg-slate-50 overflow-y-auto">
            <?php if ($selected === null): ?>
                <div class="p-12 text-center text-slate-500">Select an applicant from the list.</div>
            <?php else: ?>
                <?php $applicant = $selected->applicant; ?>
                <?php $job = $selected->job; ?>
                <div class="p-6 lg:p-8 border-b border-slate-200 bg-white sticky top-0 z-10">
                    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                        <div class="flex gap-5 items-center">
                            <div class="size-20 rounded-2xl bg-[#642754]/10 text-[#642754] text-3xl font-black flex items-center justify-center border-4 border-slate-100">
                                <?= strtoupper(substr((string)($applicant->full_name ?? 'U'), 0, 1)) ?>
                            </div>
                            <div>
                                <h1 class="text-2xl font-black text-slate-900"><?= Html::encode($applicant->full_name ?? '') ?></h1>
                                <p class="text-[#642754] font-semibold mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="material-symbols-outlined text-lg">work</span>
                                    <span>Applied for: <?= Html::encode(trim((string)($job->job_title ?? '')) !== '' ? $job->job_title : '—') ?></span>
                                    <?php if (!empty($job->department)): ?>
                                        <span class="text-slate-400 font-medium">· <?= Html::encode($job->department) ?></span>
                                    <?php endif; ?>
                                </p>
                                <?php
                                $detailHeadline = trim((string)($applicant->headline ?? ''));
                                $detailJobTitle = trim((string)($job->job_title ?? ''));
                                if ($detailHeadline !== '' && strcasecmp($detailHeadline, $detailJobTitle) !== 0):
                                ?>
                                    <p class="text-sm text-slate-500 mt-0.5">Profile headline: <?= Html::encode($detailHeadline) ?></p>
                                <?php endif; ?>
                                <p class="text-sm text-slate-500 mt-1 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base">mail</span>
                                    <?= Html::encode($applicant->email ?? '') ?>
                                </p>
                                <?php if (!empty($applicant->phone)): ?>
                                    <p class="text-sm text-slate-500 mt-1 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-base">phone</span>
                                        <?= Html::encode($applicant->phone) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="<?= Url::to(['application-status', 'id' => $selected->application_id]) ?>"
                               class="h-10 px-4 border-2 border-[#642754] text-[#642754] rounded-lg text-sm font-semibold inline-flex items-center gap-1 no-underline hover:bg-[#642754]/5">
                                <span class="material-symbols-outlined text-lg">timeline</span>
                                View status timeline
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-6 lg:p-8 grid grid-cols-1 xl:grid-cols-3 gap-8">
                    <div class="xl:col-span-2 space-y-6">
                        <?= Html::beginForm(['applications'], 'post', ['class' => 'bg-white rounded-xl border border-slate-200 p-5']) ?>
                            <?= Html::hiddenInput('action', 'update_status') ?>
                            <?= Html::hiddenInput('application_id', $selected->application_id) ?>
                            <?= Html::hiddenInput('filter', $filter) ?>
                            <?= Html::hiddenInput('q', $search) ?>
                            <label class="text-xs font-bold text-slate-500 uppercase">Update status</label>
                            <div class="flex gap-2 mt-2">
                                <?= Html::dropDownList('status', $selected->application_status, JobApplication::statusOptions(), ['class' => 'flex-1 h-10 px-3 rounded-lg border border-slate-200 text-sm']) ?>
                                <button type="submit" class="h-10 px-4 bg-[#642754] text-white rounded-lg text-sm font-semibold">Save status</button>
                            </div>
                        <?= Html::endForm() ?>

                        <div class="bg-white rounded-xl border border-slate-200 p-6">
                            <h3 class="font-bold text-slate-900 mb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[#642754]">person</span>
                                Professional summary
                            </h3>
                            <p class="text-slate-600 text-sm leading-relaxed whitespace-pre-wrap"><?= Html::encode($applicant->bio ?? 'No bio provided.') ?></p>
                        </div>

                        <div class="bg-[#642754]/5 rounded-xl border border-[#642754]/10 p-6">
                            <h3 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                                <span class="material-symbols-outlined">rate_review</span>
                                Interview feedback
                            </h3>
                            <?php
                            $savedRating = $selected->feedback_rating !== null ? (int)$selected->feedback_rating : null;
                            $savedNotes = trim((string)($selected->feedback_notes ?? ''));
                            $hasSavedFeedback = $savedRating !== null || $savedNotes !== '';
                            ?>
                            <?php if ($hasSavedFeedback): ?>
                                <div class="mb-4 p-4 rounded-lg bg-white border border-[#642754]/15">
                                    <p class="text-[10px] font-bold uppercase text-slate-500 mb-2">Saved for applicant</p>
                                    <p class="text-sm text-slate-800 mb-2">
                                        <span class="font-semibold text-slate-600">Rating:</span>
                                        <span class="text-[#eb652c] font-bold ml-1"><?= Html::encode(RecruitmentUi::formatInterviewRating($savedRating)) ?></span>
                                    </p>
                                    <p class="text-sm text-slate-800">
                                        <span class="font-semibold text-slate-600 block mb-1">Interview notes:</span>
                                        <span class="whitespace-pre-wrap leading-relaxed"><?= $savedNotes !== '' ? Html::encode($savedNotes) : '—' ?></span>
                                    </p>
                                </div>
                            <?php endif; ?>
                            <?= Html::beginForm(['applications'], 'post', ['class' => 'space-y-3']) ?>
                                <?= Html::hiddenInput('action', 'save_feedback') ?>
                                <?= Html::hiddenInput('application_id', $selected->application_id) ?>
                                <?= Html::hiddenInput('filter', $filter) ?>
                                <?= Html::hiddenInput('q', $search) ?>
                                <div>
                                    <label class="text-[10px] font-bold uppercase text-slate-500">Rating (1–5)</label>
                                    <?= Html::dropDownList('rating', $selected->feedback_rating, [
                                        '' => 'Select rating',
                                        1 => '1 — Poor',
                                        2 => '2 — Fair',
                                        3 => '3 — Good',
                                        4 => '4 — Very good',
                                        5 => '5 — Excellent',
                                    ], ['class' => 'w-full h-10 px-3 rounded-lg border border-slate-200 text-sm mt-1']) ?>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold uppercase text-slate-500">Interview notes</label>
                                    <?= Html::textarea('notes', $selected->feedback_notes, [
                                        'class' => 'w-full rounded-lg border border-slate-200 p-3 text-sm min-h-[100px] mt-1',
                                        'placeholder' => 'Notes shared with the applicant after the interview...',
                                    ]) ?>
                                </div>
                                <button type="submit" class="h-10 px-4 bg-[#642754] text-white rounded-lg text-sm font-semibold hover:bg-[#532244]">Save feedback</button>
                            <?= Html::endForm() ?>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="bg-[#642754]/5 px-4 py-3 border-b border-slate-200">
                                <h4 class="text-xs font-bold uppercase text-[#642754]">Schedule interview</h4>
                            </div>
                            <?= Html::beginForm(['applications'], 'post', ['class' => 'p-4 space-y-3']) ?>
                                <?= Html::hiddenInput('action', 'schedule_interview') ?>
                                <?= Html::hiddenInput('application_id', $selected->application_id) ?>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500 uppercase">Date</label>
                                    <?= Html::input('date', 'interview_date', $selected->interview_at ? date('Y-m-d', strtotime($selected->interview_at)) : '', ['class' => 'w-full h-10 px-3 rounded-lg border border-slate-200 text-sm mt-1']) ?>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500 uppercase">Time</label>
                                    <?= Html::input('time', 'interview_time', $selected->interview_at ? date('H:i', strtotime($selected->interview_at)) : '', ['class' => 'w-full h-10 px-3 rounded-lg border border-slate-200 text-sm mt-1']) ?>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500 uppercase">Type</label>
                                    <?= Html::dropDownList('interview_type', $selected->interview_type, [
                                        'Google Meet' => 'Google Meet',
                                        'Zoom' => 'Zoom',
                                        'In-Person Office' => 'In-Person Office',
                                    ], ['class' => 'w-full h-10 px-3 rounded-lg border border-slate-200 text-sm mt-1']) ?>
                                </div>
                                <button type="submit" class="w-full h-10 bg-[#eb652c] hover:bg-[#d85a24] text-white rounded-lg text-sm font-semibold">Confirm schedule</button>
                            <?= Html::endForm() ?>
                        </div>

                        <?php if ($applicant && $applicant->resume_path): ?>
                            <?php
                            $resumeName = $applicant->resume_original_name ?: 'Resume on file';
                            $resumeViewUrl = Url::to(['download-applicant-resume', 'id' => (int)$selected->application_id, 'inline' => 1]);
                            $resumeDownloadUrl = Url::to(['download-applicant-resume', 'id' => (int)$selected->application_id]);
                            ?>
                            <div class="bg-white rounded-xl border border-slate-200 p-4">
                                <h4 class="text-xs font-bold uppercase text-slate-500 mb-2">Resume</h4>
                                <p class="text-sm text-slate-800 font-medium truncate mb-3" title="<?= Html::encode($resumeName) ?>">
                                    <?= Html::encode($resumeName) ?>
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <a href="<?= Html::encode($resumeViewUrl) ?>"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg bg-[#642754] text-white text-sm font-semibold no-underline hover:bg-[#532244] transition-colors">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                        View
                                    </a>
                                    <a href="<?= Html::encode($resumeDownloadUrl) ?>"
                                       class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg border-2 border-[#642754] text-[#642754] text-sm font-semibold no-underline hover:bg-[#642754]/5 transition-colors">
                                        <span class="material-symbols-outlined text-lg">download</span>
                                        Download
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($selected->application_status !== JobApplication::STATUS_HIRED): ?>
                            <?= Html::beginForm(['convert-applicant', 'id' => $selected->application_id], 'post') ?>
                                <button type="submit" class="w-full h-11 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold" onclick="return confirm('Convert this applicant to an employee?')">
                                    Convert to employee
                                </button>
                            <?= Html::endForm() ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
