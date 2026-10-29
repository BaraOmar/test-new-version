<?php

use common\modules\evaluation\models\Evaluation;
use common\modules\evaluation\models\EvaluationAnswer;
use common\modules\evaluation\models\EvaluationQuestion;
use common\modules\evaluation\services\EvaluationResultsService;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var Evaluation $evaluation */
/** @var EvaluationQuestion[] $questions */
/** @var int $responseCount */
/** @var int $uniqueRespondents */
/** @var array{count: int, employees: array<int, array{id: int, full_name: string, email: string}>} $pendingRespondents */
/** @var string $search */
/** @var array $groupedResponses */
/** @var array $questionStats */
/** @var string[] $targetDepartments */
/** @var string[] $targetEmployees */
/** @var string $targetLabel */

$this->title = 'Evaluation Responses';
echo $this->render('_builder-styles');

$isAnonymous = $evaluation->getIsAnonymous();
$status = $evaluation->hasAttribute('status') ? ($evaluation->status ?? 'draft') : 'active';
$statusClass = EvaluationResultsService::statusBadgeClass($status);
$targetLabel = $targetLabel ?? $evaluation->getTargetDisplayLabel();
$pendingCount = (int)($pendingRespondents['count'] ?? 0);
$pendingEmployees = $pendingRespondents['employees'] ?? [];

$indexRoute = Url::to(['index']);

$this->registerCss(<<<'CSS'
@keyframes barGrow { from { width: 0; } }
.evaluation-results-page .bar-animate { animation: barGrow 0.5s ease-out; }
CSS);

?>

<div class="evaluation-results-page flex flex-col flex-1 min-h-0 h-full">
    <header class="flex h-16 items-center justify-between border-b border-white/10 bg-[#642754] px-6 shrink-0 z-20">
        <div class="flex items-center gap-4">
            <button type="button" onclick="history.back()" class="p-2 hover:bg-white/10 rounded-lg transition-colors text-white" aria-label="Go back">
                <span class="material-symbols-outlined">arrow_back</span>
            </button>
            <div>
                <h1 class="text-lg font-bold leading-tight text-white"><?= Html::encode($evaluation->title) ?></h1>
                <p class="text-xs text-white/80">Evaluation Responses</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= Html::encode($indexRoute) ?>" class="flex items-center justify-center rounded-lg border-2 border-white/60 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition-colors no-underline">
                Back to Evaluations
            </a>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto bg-[#f8f4f8] p-8 no-scrollbar">
        <div class="max-w-6xl mx-auto space-y-6">

            <!-- Evaluation Info Card -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?= $statusClass ?>">
                                <?= Html::encode(ucfirst($status)) ?>
                            </span>
                            <?php if ($isAnonymous): ?>
                                <span class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold uppercase bg-emerald-100 text-emerald-600">
                                    <span class="material-symbols-outlined text-sm">lock</span>
                                    Anonymous
                                </span>
                            <?php else: ?>
                                <span class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold uppercase bg-amber-100 text-amber-600">
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                    Non-Anonymous
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-slate-600 text-sm whitespace-pre-line"><?= Html::encode($evaluation->getDescriptionPlain() ?: 'No description') ?></p>
                    </div>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-slate-400">calendar_today</span>
                            <span class="text-slate-600">Opens: <?= Html::encode(EvaluationResultsService::formatDate($evaluation->start_at ?? null)) ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-slate-400">event_busy</span>
                            <span class="text-slate-600">Closes: <?= Html::encode(EvaluationResultsService::formatDate($evaluation->end_at ?? null)) ?></span>
                        </div>
                        <div class="flex items-center gap-2 min-w-0 max-w-xl">
                            <span class="material-symbols-outlined text-slate-400 shrink-0">groups</span>
                            <span class="text-slate-600">
                                <span class="font-medium text-slate-700">Target:</span>
                                <?= Html::encode($targetLabel) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-[#642754]/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[#642754]">how_to_reg</span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold"><?= (int)$responseCount ?></p>
                            <p class="text-xs text-slate-500">Total Responses</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 md:col-span-1">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-amber-600">hourglass_empty</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold"><?= $pendingCount ?></p>
                            <p class="text-xs text-slate-500">Awaiting Response</p>
                        </div>
                    </div>
                    <?php if ($pendingEmployees !== []): ?>
                        <details class="mt-3 group">
                            <summary class="flex items-center justify-between gap-2 cursor-pointer list-none text-sm font-semibold text-[#642754] hover:text-[#5d245f] [&::-webkit-details-marker]:hidden">
                                <span>View employees</span>
                                <span class="material-symbols-outlined text-lg transition-transform group-open:rotate-180">expand_more</span>
                            </summary>
                            <ul class="mt-2 max-h-44 overflow-y-auto rounded-lg border border-slate-100 divide-y divide-slate-100">
                                <?php foreach ($pendingEmployees as $pending): ?>
                                    <li class="px-3 py-2 text-sm">
                                        <span class="font-medium text-slate-800"><?= Html::encode((string)$pending['full_name']) ?></span>
                                        <?php if ((string)($pending['email'] ?? '') !== ''): ?>
                                            <span class="block text-xs text-slate-500 truncate"><?= Html::encode((string)$pending['email']) ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php elseif ($pendingCount === 0 && (int)$responseCount === 0 && $pendingEmployees === []): ?>
                        <p class="mt-2 text-xs text-amber-700 font-medium">No employees are assigned to this evaluation yet.</p>
                    <?php elseif ($pendingCount === 0): ?>
                        <p class="mt-2 text-xs text-emerald-600 font-medium">Everyone in the audience has responded.</p>
                    <?php endif; ?>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center">
                            <span class="material-symbols-outlined text-amber-600">schedule</span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold"><?= $evaluation->hasAttribute('duration_days') && $evaluation->duration_days ? (int)$evaluation->duration_days : 'N/A' ?></p>
                            <p class="text-xs text-slate-500">Duration (Days)</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                            <span class="material-symbols-outlined text-blue-600">help</span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold"><?= count($questions) ?></p>
                            <p class="text-xs text-slate-500">Questions</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Response Statistics -->
            <?php if ($questionStats !== []): ?>
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#642754]">analytics</span>
                        Response Statistics
                    </h2>
                    <div class="space-y-6">
                        <?php foreach ($questions as $q): ?>
                            <?php if (!isset($questionStats[$q->id])) {
                                continue;
                            }
                            $stats = $questionStats[$q->id];
                            $qType = $q->question_type;
                            ?>
                            <div class="border-b border-slate-100 pb-6 last:border-0 last:pb-0">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="material-symbols-outlined text-slate-400 text-lg"><?= EvaluationResultsService::questionTypeIcon($qType) ?></span>
                                    <p class="text-sm font-medium"><?= Html::encode($q->question_text) ?></p>
                                    <span class="text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded"><?= Html::encode(EvaluationResultsService::questionTypeLabel($qType)) ?></span>
                                </div>

                                <?php if ($stats['type'] === 'choice'): ?>
                                    <div class="space-y-3">
                                        <?php foreach ($stats['counts'] as $option => $count):
                                            $percent = $stats['total'] > 0 ? (int)round(($count / $stats['total']) * 100) : 0;
                                            ?>
                                            <div class="flex items-center gap-3">
                                                <div class="w-32 text-sm text-slate-600 truncate" title="<?= Html::encode($option) ?>"><?= Html::encode($option) ?></div>
                                                <div class="flex-1 h-6 bg-slate-200 rounded-lg overflow-hidden">
                                                    <div class="h-full bg-[#642754] bar-animate flex items-center justify-end pr-2 text-white text-xs" style="width:<?= $percent ?>%"><?= $percent ?>%</div>
                                                </div>
                                                <div class="w-10 text-sm text-slate-500 text-right"><?= (int)$count ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($stats['type'] === 'rating'): ?>
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <span class="px-2 py-1 text-xs font-bold rounded bg-[#642754]/10 text-[#642754]">
                                                <?= $qType === 'linear_scale' ? 'LINEAR SCALE' : 'RATING SCALE' ?>
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-3xl font-bold text-[#642754]"><?= Html::encode((string)$stats['average']) ?></p>
                                            <p class="text-xs text-slate-500">AVG SCORE</p>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <?php
                                        $max = $stats['scale_max'] ?? ($q->scale_max ?: 5);
                                        $min = $stats['scale_min'] ?? ($q->scale_min ?: 1);
                                        for ($i = $max; $i >= $min; $i--):
                                            $count = $stats['distribution'][$i] ?? 0;
                                            $percent = $stats['total'] > 0 ? (int)round(($count / $stats['total']) * 100) : 0;
                                            $starLabel = $qType === 'linear_scale' ? (string)$i : $i . ' Stars';
                                            ?>
                                            <div class="flex items-center gap-3">
                                                <div class="w-20 text-sm text-slate-600"><?= Html::encode($starLabel) ?></div>
                                                <div class="flex-1 h-8 bg-slate-200 rounded-lg overflow-hidden">
                                                    <div class="h-full bg-[#642754] bar-animate flex items-center pl-3 text-white text-sm font-semibold" style="width:<?= $percent ?>%"><?= $percent ?>%</div>
                                                </div>
                                                <div class="w-10 text-sm text-slate-500 text-right"><?= (int)$count ?></div>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Search + respondent count -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <?php if (!$isAnonymous): ?>
                    <form method="get" action="<?= Html::encode(Url::to(['results', 'id' => $evaluation->id])) ?>" class="flex items-center gap-3 w-full md:w-auto flex-wrap">
                        <div class="relative w-full md:w-80">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                            <input type="text" name="search" value="<?= Html::encode($search) ?>" placeholder="Search respondent name..."
                                   class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-[#642754] focus:border-[#642754] transition" />
                        </div>
                        <button type="submit" class="bg-[#eb652c] hover:bg-[#d85a24] text-white px-4 py-2.5 text-sm font-semibold rounded-lg shadow-sm transition-all">Search</button>
                        <a href="<?= Html::encode(Url::to(['results', 'id' => $evaluation->id])) ?>" class="px-4 py-2.5 text-sm font-medium border border-slate-200 rounded-lg hover:bg-slate-50 transition no-underline text-slate-700">Reset</a>
                    </form>
                <?php endif; ?>

                <div class="text-sm text-slate-500 flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">group</span>
                    Showing <span class="font-semibold"><?= (int)$uniqueRespondents ?></span> respondents
                </div>
            </div>

            <!-- Individual Responses -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="text-lg font-bold mb-4 flex items-center gap-2 flex-wrap">
                    <span class="material-symbols-outlined text-[#642754]">format_list_bulleted</span>
                    Individual Responses
                    <?php if ($isAnonymous): ?>
                        <span class="text-xs font-normal text-emerald-600 bg-emerald-100 px-2 py-1 rounded-full">Respondent identities hidden</span>
                    <?php else: ?>
                        <span class="text-xs font-normal text-amber-600 bg-amber-100 px-2 py-1 rounded-full">Respondent identities visible</span>
                    <?php endif; ?>
                </h2>

                <?php if ($groupedResponses === []): ?>
                    <div class="text-center py-12">
                        <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">inbox</span>
                        <p class="text-slate-500">No responses yet</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php
                        $responseNum = 1;
                        foreach ($groupedResponses as $response): ?>
                            <div class="border border-slate-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-100 flex-wrap gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-[#642754]/10 flex items-center justify-center">
                                            <?php if (!$isAnonymous): ?>
                                                <span class="material-symbols-outlined text-[#642754]">person</span>
                                            <?php else: ?>
                                                <span class="material-symbols-outlined text-[#642754]">shield</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if ($isAnonymous): ?>
                                                <p class="font-bold">Anonymous Respondent #<?= $responseNum ?></p>
                                                <p class="text-xs text-slate-500">Identity hidden due to anonymity settings</p>
                                            <?php else: ?>
                                                <p class="font-bold"><?= Html::encode($response['employee_name'] ?? 'Unknown') ?></p>
                                                <p class="text-xs text-slate-500"><?= Html::encode($response['employee_email'] ?? 'No email') ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-slate-500">Submitted</p>
                                        <p class="text-sm font-medium"><?= Html::encode(EvaluationResultsService::formatDate($response['submitted_at'])) ?></p>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <?php foreach ($response['answers'] as $ans): ?>
                                        <?php
                                        /** @var EvaluationAnswer $ans */
                                        $q = $ans->question;
                                        if ($q === null) {
                                            continue;
                                        }
                                        if (in_array($q->question_type, ['section_break', 'section_title', 'image', 'video'], true)) {
                                            continue;
                                        }
                                        $qType = $q->question_type;
                                        ?>
                                        <div class="bg-slate-50 rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="material-symbols-outlined text-slate-400 text-sm"><?= EvaluationResultsService::questionTypeIcon($qType) ?></span>
                                                <p class="text-xs text-slate-500"><?= Html::encode($q->question_text) ?></p>
                                            </div>
                                            <div class="font-medium pl-6">
                                                <?php if (in_array($qType, ['rating', 'linear_scale'], true)): ?>
                                                    <?php
                                                    $scaleMax = $q->scale_max ? (int)$q->scale_max : 5;
                                                    $val = (int)EvaluationResultsService::answerDisplayText($q, $ans);
                                                    ?>
                                                    <span class="flex items-center gap-1 flex-wrap">
                                                        <?php if ($qType === 'rating'): ?>
                                                            <?php for ($i = 1; $i <= $scaleMax; $i++): ?>
                                                                <span class="material-symbols-outlined text-lg <?= $i <= $val ? 'text-[#642754]' : 'text-slate-300' ?>" style="<?= $i <= $val ? "font-variation-settings:'FILL' 1" : '' ?>">star</span>
                                                            <?php endfor; ?>
                                                        <?php else: ?>
                                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-[#642754]/10 text-[#642754] font-bold"><?= $val ?></span>
                                                        <?php endif; ?>
                                                        <span class="ml-2 text-sm text-slate-500">(<?= $val ?: 'N/A' ?>)</span>
                                                    </span>
                                                <?php elseif ($qType === 'checkbox'): ?>
                                                    <span class="flex items-center gap-2 flex-wrap">
                                                        <?php foreach (EvaluationResultsService::answerChoiceParts($q, $ans) as $part): ?>
                                                            <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[#642754] text-base">check_box</span><?= Html::encode($part) ?></span>
                                                        <?php endforeach; ?>
                                                        <?php if (EvaluationResultsService::answerChoiceParts($q, $ans) === []): ?>
                                                            <span class="text-slate-500">N/A</span>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php elseif ($qType === 'multiple_choice'): ?>
                                                    <span class="flex items-center gap-2">
                                                        <span class="material-symbols-outlined text-[#642754]">radio_button_checked</span>
                                                        <span><?= Html::encode(EvaluationResultsService::answerDisplayText($q, $ans)) ?></span>
                                                    </span>
                                                <?php elseif ($qType === 'dropdown'): ?>
                                                    <span class="flex items-center gap-2">
                                                        <span class="material-symbols-outlined text-[#642754]">arrow_drop_down_circle</span>
                                                        <span><?= Html::encode(EvaluationResultsService::answerDisplayText($q, $ans)) ?></span>
                                                    </span>
                                                <?php elseif ($qType === 'long_text'): ?>
                                                    <div class="bg-white rounded p-3 text-sm whitespace-pre-wrap"><?= Html::encode(EvaluationResultsService::answerDisplayText($q, $ans)) ?></div>
                                                <?php else: ?>
                                                    <span><?= Html::encode(EvaluationResultsService::answerDisplayText($q, $ans)) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php
                            $responseNum++;
                        endforeach;
                        ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>
