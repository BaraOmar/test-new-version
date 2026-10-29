<?php

/**
 * @var yii\web\View $this
 * @var common\modules\recruitment\models\Application[] $applications
 */

use common\modules\recruitment\helpers\RecruitmentUi;
use common\modules\recruitment\models\Application as JobApplication;
use yii\helpers\Html;

$this->title = 'Application Status | CultureHR Careers';

?>

<div class="max-w-[1200px] mx-auto p-6 lg:p-10">
    <h2 class="text-3xl font-extrabold text-slate-900 mb-2">Application Status</h2>
    <p class="text-slate-500 mb-8">Track each application from submission through interview and decision.</p>

    <?php if ($applications === []): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
            <span class="material-symbols-outlined text-5xl text-slate-300 mb-3">inbox</span>
            <p class="text-slate-600 font-medium">You have not applied to any jobs yet.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden overflow-x-auto">
            <table class="w-full text-sm text-left min-w-[900px]">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold">
                    <tr>
                        <th class="px-5 py-3">Job</th>
                        <th class="px-5 py-3">Department</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Interview</th>
                        <th class="px-5 py-3">Rating</th>
                        <th class="px-5 py-3">Interview notes</th>
                        <th class="px-5 py-3">Applied</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($applications as $application): ?>
                        <?php
                        $job = $application->job;
                        $statusLabel = JobApplication::displayStatusForApplicant($application);
                        $rating = $application->feedback_rating !== null ? (int)$application->feedback_rating : null;
                        $notes = trim((string)($application->feedback_notes ?? ''));
                        ?>
                        <tr class="hover:bg-slate-50 align-top">
                            <td class="px-5 py-4 font-medium text-slate-900"><?= Html::encode($job->job_title ?? '—') ?></td>
                            <td class="px-5 py-4 text-slate-600"><?= Html::encode($job->department ?? '—') ?></td>
                            <td class="px-5 py-4">
                                <span class="text-xs font-bold px-2 py-1 rounded-full <?= RecruitmentUi::statusBadgeClass($statusLabel) ?>">
                                    <?= Html::encode($statusLabel) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <?php if ($application->interview_at): ?>
                                    <?= Html::encode(date('M j, Y g:i A', strtotime($application->interview_at))) ?>
                                    <?php if ($application->interview_type): ?>
                                        <span class="block text-xs text-slate-400 mt-0.5"><?= Html::encode($application->interview_type) ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4">
                                <?php if ($rating !== null): ?>
                                    <span class="text-[#eb652c] font-bold text-sm" title="<?= Html::encode(RecruitmentUi::formatInterviewRating($rating)) ?>">
                                        <?= Html::encode(RecruitmentUi::formatInterviewRating($rating)) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-300">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-slate-600 max-w-[220px]">
                                <?php if ($notes !== ''): ?>
                                    <p class="whitespace-pre-wrap leading-relaxed text-sm" title="<?= Html::encode($notes) ?>"><?= Html::encode($notes) ?></p>
                                <?php else: ?>
                                    <span class="text-slate-300">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-slate-500 whitespace-nowrap">
                                <?= $application->applied_at ? Html::encode(date('M j, Y', strtotime($application->applied_at))) : '—' ?>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <?php if ($application->canApplicantWithdraw()): ?>
                                    <?php
                                    $jobTitle = $job->job_title ?? 'this job';
                                    $confirmMsg = $statusLabel === JobApplication::STATUS_CLOSED
                                        ? 'Remove your application for ' . $jobTitle . '? This job posting is closed.'
                                        : 'Withdraw your application for ' . $jobTitle . '? You can apply again later if the job is still open.';
                                    ?>
                                    <?= Html::beginForm(['withdraw-application', 'id' => (int)$application->application_id], 'post', ['class' => 'inline m-0']) ?>
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 text-sm font-semibold text-red-600 hover:text-red-700 hover:underline bg-transparent border-0 cursor-pointer p-0"
                                                onclick="return confirm(<?= json_encode($confirmMsg) ?>);">
                                            <span class="material-symbols-outlined text-base">undo</span>
                                            Unsend
                                        </button>
                                    <?= Html::endForm() ?>
                                <?php else: ?>
                                    <span class="text-slate-300 text-sm">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-xs text-slate-400 mt-4 flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">info</span>
            Interview rating and notes appear here after your recruiter saves feedback.
        </p>
    <?php endif; ?>
</div>
