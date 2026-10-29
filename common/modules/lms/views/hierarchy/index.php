<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolDepartment[] $departments
 * @var string $search
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Sections & Hierarchy | LMS';

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');

$this->registerCss(<<<'CSS'
.infra-dept-card { border: 1px solid #e2e8f0; border-radius: 1rem; overflow: hidden; background: #fff; box-shadow: 0 4px 24px rgba(15,23,42,.04); }
.infra-dept-head { background: linear-gradient(135deg, #642754 0%, #7c3a6b 100%); color: #fff; }
.infra-grade-row { border-top: 1px solid #e2e8f0; }
.infra-section-card { border: 1px solid #e2e8f0; border-radius: .875rem; background: #f8fafc; min-width: 12rem; }
CSS
);

?>
<?= $this->render('../layouts/_page-start') ?>
    <header class="space-y-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Sections &amp; Hierarchy</h2>
            <p class="text-slate-500 mt-2 text-lg">View and manage sections in your department.</p>
        </div>

        <?= Html::beginForm(['index'], 'get', ['id' => 'infra-search-form', 'class' => 'max-w-xl']) ?>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">search</span>
                <?= Html::textInput('search', $search, [
                    'id' => 'infra-search-input',
                    'placeholder' => 'Search grades or sections...',
                    'autocomplete' => 'off',
                    'class' => 'w-full h-11 pl-10 pr-4 rounded-lg border border-slate-200 bg-white text-sm focus:border-[#642754] focus:ring-[#642754]',
                ]) ?>
            </div>
        <?= Html::endForm() ?>
    </header>

    <?php if ($successMessage !== ''): ?>
        <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-medium"><?= Html::encode($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium"><?= Html::encode($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($departments === []): ?>
        <div class="p-10 text-center bg-white rounded-xl border border-slate-200 text-slate-500">
            No departments or sections found in your scope.
        </div>
    <?php endif; ?>

    <?php foreach ($departments as $department): ?>
        <?php $deanName = $department->head?->full_name ?? ''; ?>
        <article class="infra-dept-card">
            <div class="infra-dept-head px-5 py-4 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="size-12 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-3xl">account_balance</span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-xl font-black truncate"><?= Html::encode($department->name) ?></h3>
                        <?php if ($deanName !== ''): ?>
                            <p class="text-xs font-bold tracking-[0.12em] uppercase text-white/80 mt-1">
                                Head: <?= Html::encode($deanName) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($department->grades === []): ?>
                <div class="px-5 py-8 text-center text-sm text-slate-500">No grades configured for this department.</div>
            <?php endif; ?>

            <?php foreach ($department->grades as $grade): ?>
                <?php
                $sectionCount = count($grade->sections);
                $studentCount = 0;
                foreach ($grade->sections as $section) {
                    $studentCount += (int)$section->current_enrollment;
                }
                ?>
                <div class="infra-grade-row px-5 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <div>
                            <h4 class="text-lg font-bold text-slate-900"><?= Html::encode($grade->name) ?></h4>
                            <p class="text-sm text-slate-500"><?= (int)$sectionCount ?> Sections · <?= (int)$studentCount ?> Students</p>
                        </div>
                        <a href="<?= Url::to(['/lms/hierarchy/create-section', 'grade_id' => (int)$grade->id]) ?>"
                           class="inline-flex items-center h-9 px-3 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold no-underline">
                            + Add Section
                        </a>
                    </div>

                    <div class="flex gap-3 overflow-x-auto pb-1">
                        <?php if ($grade->sections === []): ?>
                            <p class="text-sm text-slate-400 py-4">No sections in this grade.</p>
                        <?php endif; ?>
                        <?php foreach ($grade->sections as $section): ?>
                            <?php $atCapacity = $section->isAtCapacity(); ?>
                            <div class="infra-section-card p-4 flex flex-col gap-3 shrink-0">
                                <div class="flex items-start justify-between gap-2">
                                    <h5 class="font-bold text-slate-900"><?= Html::encode($section->name) ?></h5>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?= $atCapacity ? 'bg-red-100 text-red-700' : 'bg-slate-200 text-slate-700' ?>">
                                        <?= (int)$section->current_enrollment ?>/<?= (int)$section->capacity_max ?>
                                    </span>
                                </div>
                                <div class="space-y-1 text-sm text-slate-600">
                                    <p class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-base text-slate-400">person</span>
                                        <?= Html::encode($section->leadTeacher?->full_name ?: 'Unassigned') ?>
                                    </p>
                                    <p class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-base text-slate-400">meeting_room</span>
                                        <?= Html::encode($section->room_number ?: '—') ?>
                                    </p>
                                </div>
                                <div class="mt-auto flex flex-col gap-2">
                                    <a href="<?= Url::to(['/lms/hierarchy/section', 'id' => (int)$section->id]) ?>"
                                       class="inline-flex items-center justify-center h-9 rounded-lg bg-[#642754] hover:bg-[#7c3a6b] text-white text-xs font-bold no-underline">
                                        Manage Section
                                    </a>
                                    <?= Html::beginForm(['/lms/hierarchy/delete-section', 'id' => (int)$section->id], 'post', ['class' => 'm-0']) ?>
                                        <button type="submit"
                                                class="inline-flex w-full items-center justify-center h-9 rounded-lg text-red-600 text-xs font-bold bg-transparent border border-red-200 cursor-pointer hover:bg-red-50"
                                                onclick="return confirm('Delete this section?');">
                                            Delete section
                                        </button>
                                    <?= Html::endForm() ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </article>
    <?php endforeach; ?>
<?= $this->render('../layouts/_page-end') ?>
