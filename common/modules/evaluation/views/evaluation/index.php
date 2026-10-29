<?php

/**
 * Pixel UI reference: Legacy_Code/HRMS/evaluation_list.php
 *
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var common\modules\employee\models\Department[] $departments
 * @var string $search
 * @var string $dateFrom
 * @var string $dateTo
 * @var string $anonymous
 * @var int $departmentId
 * @var string $indexUrl
 * @var array<int|string, string> $filterRoute
 */

use common\modules\evaluation\controllers\EvaluationController;
use yii\helpers\Html;
use yii\helpers\Url;

$evaluations = $dataProvider->getModels();
$filterRoute = $filterRoute ?? ['/hr/evaluation/index'];
$indexUrl = $indexUrl ?? Url::to($filterRoute);
$count = count($evaluations);
?>

<div class="evaluation-index">
    <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
        <div class="w-full">
            <?= Html::beginForm($filterRoute, 'get', ['class' => 'mb-6 flex items-center gap-3 flex-wrap']) ?>
                <div class="relative w-full max-w-[420px]">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input
                        type="text"
                        name="search"
                        value="<?= Html::encode($search) ?>"
                        placeholder="Search for published evaluations..."
                        class="w-full h-11 pl-12 pr-4 rounded-xl border border-slate-200 focus:border-[#eb652c] focus:ring-2 focus:ring-[#eb652c]/20 outline-none bg-white shadow-sm"
                    />
                </div>

                <label class="flex flex-col text-[10px] font-bold uppercase text-slate-500">
                    From
                    <input
                        type="date"
                        name="date_from"
                        value="<?= Html::encode($dateFrom) ?>"
                        title="Include evaluations with due/start date on or after this day"
                        class="h-11 px-3 rounded-lg border border-slate-200 shadow-sm min-w-[150px] bg-white mt-0.5 normal-case font-normal text-sm text-slate-800"
                    />
                </label>
                <label class="flex flex-col text-[10px] font-bold uppercase text-slate-500">
                    To
                    <input
                        type="date"
                        name="date_to"
                        value="<?= Html::encode($dateTo) ?>"
                        title="Include evaluations with due/start date on or before this day"
                        class="h-11 px-3 rounded-lg border border-slate-200 shadow-sm min-w-[150px] bg-white mt-0.5 normal-case font-normal text-sm text-slate-800"
                    />
                </label>

                <select name="department_id" class="h-11 px-3 rounded-lg border border-slate-200 shadow-sm bg-white min-w-[190px]">
                    <option value="0">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= (int)$dept->id ?>" <?= (int)$departmentId === (int)$dept->id ? 'selected' : '' ?>>
                            <?= Html::encode($dept->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="anonymous" class="h-11 px-3 rounded-lg border border-slate-200 shadow-sm bg-white min-w-[150px]">
                    <option value="">All Evaluations</option>
                    <option value="anonymous" <?= $anonymous === 'anonymous' ? 'selected' : '' ?>>Anonymous Only</option>
                    <option value="named" <?= $anonymous === 'named' ? 'selected' : '' ?>>Non-Anonymous Only</option>
                </select>

                <button type="submit" class="h-11 px-5 bg-[#eb652c] hover:bg-[#d85a24] text-white rounded-lg font-semibold transition-all flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">tune</span>
                    Filter
                </button>

                <a href="<?= Html::encode($indexUrl) ?>" class="h-11 px-4 flex items-center border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 bg-white">
                    Reset
                </a>
            <?= Html::endForm() ?>

            <h2 class="text-2xl font-bold">Published Evaluations</h2>
            <p class="text-slate-500 text-sm">
                <?php if ($search !== ''): ?>
                    Showing <?= $count ?> results for &ldquo;<?= Html::encode($search) ?>&rdquo;
                <?php else: ?>
                    Showing <?= $count ?> evaluations
                <?php endif; ?>
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <div class="flex border rounded-lg overflow-hidden bg-white">
                <button type="button" id="gridViewBtn" class="w-10 h-10 flex items-center justify-center hover:bg-slate-100" title="Grid view">
                    <span class="material-symbols-outlined">grid_view</span>
                </button>
                <button type="button" id="listViewBtn" class="w-10 h-10 flex items-center justify-center border-l hover:bg-slate-100" title="List view">
                    <span class="material-symbols-outlined">view_list</span>
                </button>
            </div>

            <a href="<?= Url::to(['create']) ?>"
               class="bg-[#eb652c] hover:bg-[#d85a24] text-white px-5 h-10 flex items-center gap-2 rounded-lg font-semibold shadow-md transition-all">
                <span class="material-symbols-outlined">add</span>
                Create Evaluation
            </a>
        </div>
    </div>

    <?php if (!$evaluations): ?>
        <div class="bg-white p-12 rounded-xl border shadow-sm text-center text-slate-500">
            <span class="material-symbols-outlined text-5xl mb-3 opacity-40">quiz</span>
            <p class="font-medium">No evaluations found.</p>
            <p class="text-sm mt-1">Create an evaluation or adjust your filters.</p>
        </div>
    <?php else: ?>
        <div id="evaluationContainer" class="grid grid-cols-1 xl:grid-cols-2 2xl:grid-cols-3 gap-6">
            <?php foreach ($evaluations as $evaluation): ?>
                <?php
                [$statusLabel, $statusClass] = EvaluationController::statusInfo($evaluation->status ?? 'active');
                $responses = $evaluation->getResponseCount();
                $target = $evaluation->getTargetCount();
                $rate = $target > 0 ? round(($responses / $target) * 100, 1) : 0;
                $dueDateLabel = $evaluation->getDueDateLabel();
                $audienceLine = $evaluation->getAudienceDisplayLine();
                ?>
                <div class="bg-white p-6 rounded-xl border shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="<?= Html::encode($statusClass) ?>"><?= Html::encode($statusLabel) ?></span>
                            <?php if ($evaluation->getIsAnonymous()): ?>
                                <span class="text-xs font-bold text-[#eb652c] bg-[#eb652c]/10 px-2 py-1 rounded">Anonymous</span>
                            <?php else: ?>
                                <span class="text-xs font-bold text-amber-600 bg-amber-100 px-2 py-1 rounded">Non-Anonymous</span>
                            <?php endif; ?>
                        </div>

                        <h3 class="text-lg font-bold mt-3"><?= Html::encode($evaluation->title) ?></h3>

                        <div class="text-xs text-slate-500 mt-3 space-y-2">
                            <div class="flex items-start gap-1.5">
                                <span class="material-symbols-outlined text-sm text-[#642754] shrink-0 mt-0.5">event</span>
                                <span>
                                    <span class="font-semibold text-slate-700">Due:</span>
                                    <?php if ($dueDateLabel !== null): ?>
                                        <?= Html::encode($dueDateLabel) ?>
                                    <?php else: ?>
                                        <span class="text-slate-400">No end date</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="flex items-start gap-1.5">
                                <span class="material-symbols-outlined text-sm text-[#642754] shrink-0 mt-0.5">account_tree</span>
                                <span>
                                    <span class="font-semibold text-slate-700">Sent to:</span>
                                    <?= Html::encode($audienceLine) ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">group</span>
                                <?php if ($target > 0): ?>
                                    <?= (int)$responses ?>/<?= (int)$target ?> Respondents
                                <?php else: ?>
                                    <?= (int)$responses ?> Respondent<?= $responses === 1 ? '' : 's' ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="<?= Url::to(['update-schedule', 'id' => $evaluation->id]) ?>"
                               class="inline-flex items-center gap-2 px-3 py-1.5 border-2 border-[#642754] text-[#642754] hover:bg-[#642754]/5 text-xs font-semibold rounded-md transition-all no-underline">
                                <span class="material-symbols-outlined text-[16px]">schedule</span>
                                Edit schedule
                            </a>
                            <?= Html::beginForm(['delete', 'id' => $evaluation->id], 'post', [
                                'class' => 'inline',
                                'onsubmit' => "return confirm('Are you sure you want to delete this evaluation?');",
                            ]) ?>
                                <button type="submit" class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-md transition-all w-fit">
                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                    Delete
                                </button>
                            <?= Html::endForm() ?>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs font-bold">
                                <span>Response Rate</span>
                                <span class="text-indigo-600"><?= $rate ?>%</span>
                            </div>
                            <div class="h-2 bg-slate-200 rounded-full mt-1">
                                <div class="bg-indigo-500 h-2 rounded-full" style="width:<?= min(100, max(0, $rate)) ?>%"></div>
                            </div>
                        </div>

                        <a href="<?= Url::to(['results', 'id' => $evaluation->id]) ?>"
                           class="border border-slate-200 text-xs px-4 py-2 rounded-lg flex items-center gap-1 justify-center hover:bg-slate-50 transition-colors">
                            <span class="material-symbols-outlined text-sm">visibility</span>
                            View Responses
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$this->registerJs(<<<'JS'
(function () {
    const container = document.getElementById('evaluationContainer');
    const gridBtn = document.getElementById('gridViewBtn');
    const listBtn = document.getElementById('listViewBtn');
    if (!container || !gridBtn || !listBtn) return;
    gridBtn.onclick = function () {
        container.classList.remove('grid-cols-1');
        container.classList.add('xl:grid-cols-2', '2xl:grid-cols-3');
    };
    listBtn.onclick = function () {
        container.classList.remove('xl:grid-cols-2', '2xl:grid-cols-3');
        container.classList.add('grid-cols-1');
    };
})();
JS);
?>
