<?php

/**
 * @var yii\web\View $this
 * @var string $pageTitle
 * @var common\modules\employee\models\Employee $employee
 * @var int $surveyCount
 * @var int $evaluationCount
 * @var int $totalItems
 * @var string $search
 * @var string $typeFilter
 * @var array<int, array<string, mixed>> $items
 * @var string $emptyVisibleClass
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $pageTitle ?? 'Surveys & Evaluations';

$buildFilterUrl = static function (string $type) use ($search): array {
    return ['/employee/employee/survey-display', 'type' => $type, 'search' => $search];
};

?>

<?= $this->render('../layouts/_shell-start', [
    'pageTitle' => $this->title,
    'currentPage' => 'survey_evaluation',
    'userName' => $employee->full_name ?? 'Employee',
    'userRole' => (isset($employee) && $employee->job_title) ? $employee->job_title : 'Employee',
]) ?>
        <div class="flex flex-col max-w-6xl mx-auto w-full px-6 lg:px-8 py-10 gap-8">
            <div class="flex flex-col gap-2">
                <h1 class="text-slate-900 dark:text-white text-4xl font-extrabold tracking-tight">My Surveys &amp; Evaluations</h1>
                <p class="text-slate-600 dark:text-slate-400 text-lg max-w-2xl">
                    Search, filter, and open your assigned surveys.
                </p>
            </div>

            <?= Html::beginForm(['/employee/employee/survey-display'], 'get', ['class' => 'flex flex-col sm:flex-row gap-3']) ?>
                <?= Html::textInput('search', $search, [
                    'class' => 'flex-1 h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800',
                    'placeholder' => 'Search by title...',
                ]) ?>
                <?= Html::hiddenInput('type', $typeFilter) ?>
                <button type="submit" class="h-11 px-5 rounded-lg bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold">
                    Search
                </button>
            <?= Html::endForm() ?>

            <div class="flex flex-col gap-6">
                <div class="flex border-b border-slate-200 dark:border-slate-800 gap-8">
                    <a href="<?= Html::encode(Url::to($buildFilterUrl('survey'))) ?>"
                       class="flex items-center gap-2 pb-4 pt-2 text-sm border-b-2 <?= $typeFilter === 'survey' ? 'border-[#eb652c] text-[#eb652c] font-bold' : 'border-transparent text-slate-500 font-semibold hover:text-slate-700' ?>">
                        Surveys
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100"><?= (int)$surveyCount ?></span>
                    </a>
                    <a href="<?= Html::encode(Url::to($buildFilterUrl('evaluation'))) ?>"
                       class="flex items-center gap-2 pb-4 pt-2 text-sm border-b-2 <?= $typeFilter === 'evaluation' ? 'border-[#eb652c] text-[#eb652c] font-bold' : 'border-transparent text-slate-500 font-semibold hover:text-slate-700' ?>">
                        Evaluations
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100"><?= (int)$evaluationCount ?></span>
                    </a>
                    <a href="<?= Html::encode(Url::to($buildFilterUrl('all'))) ?>"
                       class="flex items-center gap-2 pb-4 pt-2 text-sm border-b-2 <?= $typeFilter === 'all' ? 'border-[#eb652c] text-[#eb652c] font-bold' : 'border-transparent text-slate-500 font-semibold hover:text-slate-700' ?>">
                        All
                        <span class="text-xs px-2 py-0.5 rounded-full bg-[#eb652c]/10 text-[#eb652c]"><?= (int)($surveyCount + $evaluationCount) ?></span>
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-12 text-center shadow-sm <?= Html::encode($emptyVisibleClass) ?>">
                <div class="mx-auto mb-4 h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-3xl text-slate-400">inbox</span>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No matching items found</h3>
                <p class="text-slate-500">Try a different search or filter.</p>
            </div>

            <?php if ($items !== []): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($items as $item): ?>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm flex flex-col gap-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-bold uppercase tracking-wide <?= ($item['type'] ?? 'survey') === 'evaluation' ? 'text-[#642754]' : 'text-[#eb652c]' ?>">
                                            <?= ($item['type'] ?? 'survey') === 'evaluation' ? 'Evaluation' : 'Survey' ?>
                                        </span>
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-1"><?= Html::encode((string)$item['title']) ?></h3>
                                </div>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold <?= Html::encode((string)$item['statusClass']) ?>">
                                    <?= Html::encode(ucfirst((string)$item['status'])) ?>
                                </span>
                            </div>
                            <?php if ((string)$item['description'] !== ''): ?>
                                <p class="text-sm text-slate-500 line-clamp-2"><?= Html::encode((string)$item['description']) ?></p>
                            <?php endif; ?>
                            <p class="text-sm font-medium <?= Html::encode((string)$item['timeClass']) ?>"><?= Html::encode((string)$item['timeText']) ?></p>
                            <div class="mt-auto">
                                <?php if (!empty($item['submitted'])): ?>
                                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-600">
                                        <span class="material-symbols-outlined text-base">check_circle</span>
                                        Submitted
                                    </span>
                                <?php elseif (!empty($item['canRespond'])): ?>
                                    <a href="<?= Html::encode((string)$item['url']) ?>"
                                       class="inline-flex items-center justify-center w-full h-10 rounded-lg bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold">
                                        <?= ($item['type'] ?? 'survey') === 'evaluation' ? 'Take Evaluation' : 'Take Survey' ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-sm text-slate-500">Not available</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
<?= $this->render('../layouts/_shell-end') ?>
