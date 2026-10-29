<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolDepartment[] $departments
 * @var int $subjectFilter
 * @var array<int, string> $subjectOptions
 * @var int $gradeFilter
 * @var array<int, string> $gradeOptions
 * @var int $departmentId
 * @var array<int, string> $departmentOptions
 * @var bool $canChooseDepartment
 * @var string $selectedDepartmentName
 * @var common\modules\employee\services\SchoolMyClassesService $classesService
 * @var string $view
 * @var int $selectedSectionId
 */

use common\components\LmsPortal;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'My Classes | LMS';

$portalUser = Yii::$app->user->identity;
$user = $portalUser instanceof \common\models\User ? $portalUser : null;

$selectedSectionId = (int)($selectedSectionId ?? 0);
$pageHeading = 'My Classes';
$pageDescription = 'Select a section to view courses, marks, and materials.';
$indexRouteParams = [];

$hasActiveFilters = $subjectFilter > 0 || $gradeFilter > 0 || ($canChooseDepartment && $departmentId > 0);
$gradeDropdown = ['' => 'All Grade Levels'] + $gradeOptions;
$subjectDropdown = ['' => 'All Subjects'] + $subjectOptions;

$this->registerCss(<<<'CSS'
.my-classes-card { border: 1px solid #e2e8f0; border-radius: 1rem; background: #fff; min-height: 100%; }
.my-classes-grade-block { border-top: 1px solid #e2e8f0; }
CSS
);

?>
<?= $this->render('../layouts/_page-start') ?>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight"><?= Html::encode($pageHeading) ?></h2>
            <p class="text-slate-500 mt-2 text-lg"><?= Html::encode($pageDescription) ?></p>
        </div>
        <?php if ($selectedDepartmentName !== ''): ?>
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 text-slate-600 text-xs font-bold uppercase tracking-wide">
                <span class="material-symbols-outlined text-base">apartment</span>
                <?= Html::encode($selectedDepartmentName) ?>
            </span>
        <?php endif; ?>
    </header>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <?= Html::beginForm(array_merge(['index'], $indexRouteParams), 'get', ['class' => 'space-y-4']) ?>
            <?php if ($view !== ''): ?>
                <?= Html::hiddenInput('view', $view) ?>
            <?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 items-end">
                <?php if ($canChooseDepartment): ?>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Department</label>
                        <?= Html::dropDownList('department_id', $departmentId ?: '', $departmentOptions, [
                            'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                        ]) ?>
                    </div>
                <?php else: ?>
                    <?= Html::hiddenInput('department_id', $departmentId) ?>
                <?php endif; ?>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Grade Level</label>
                    <?= Html::dropDownList('grade_id', $gradeFilter ?: '', $gradeDropdown, [
                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                    ]) ?>
                </div>
                <div class="xl:col-span-2">
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Subject</label>
                    <?= Html::dropDownList('subject_id', $subjectFilter ?: '', $subjectDropdown, [
                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                        'disabled' => $subjectOptions === [],
                    ]) ?>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                <button type="submit" class="inline-flex items-center gap-2 h-11 px-4 rounded-lg bg-[#1e3a8a] text-white text-sm font-bold border-0 cursor-pointer">
                    Apply Filters
                </button>
                <?php if ($hasActiveFilters): ?>
                    <a href="<?= Html::encode(Url::to(array_merge(['index', 'department_id' => $departmentId], $indexRouteParams))) ?>"
                       class="inline-flex items-center gap-2 h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-sm font-semibold no-underline">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        <?= Html::endForm() ?>
    </section>

    <?php if ($departments === []): ?>
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
            <span class="material-symbols-outlined text-5xl text-slate-300">class</span>
            <h3 class="text-lg font-bold text-slate-900 mt-4">No sections found</h3>
            <p class="text-sm text-slate-500 mt-2">No grades or sections match your current filters.</p>
        </section>
    <?php else: ?>
        <?php foreach ($departments as $department): ?>
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/70">
                    <h3 class="text-lg font-bold text-slate-900"><?= Html::encode($department->name) ?></h3>
                </div>

                <?php foreach ($department->grades as $grade): ?>
                    <?php if ($grade->sections === []) continue; ?>
                    <div class="my-classes-grade-block px-5 py-5">
                        <div class="mb-4">
                            <h4 class="text-xl font-black text-slate-900"><?= Html::encode($grade->name) ?></h4>
                            <p class="text-sm text-slate-500"><?= count($grade->sections) ?> Sections</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            <?php foreach ($grade->sections as $section): ?>
                                <?php
                                $card = $classesService->buildSectionCard($section);
                                $sectionId = (int)$card['id'];
                                $hasCourses = (int)($card['course_count'] ?? 0) > 0;
                                $canOpenSection = $user instanceof \common\models\User
                                    && LmsPortal::hasViewableMarksInSection($user, $section);
                                $isSelectedSection = $selectedSectionId > 0 && $selectedSectionId === $sectionId;
                                ?>
                                <article class="my-classes-card p-5 flex flex-col gap-4<?= $isSelectedSection ? ' ring-2 ring-[#4a2a51] ring-offset-2' : '' ?>">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="size-11 rounded-xl bg-[#1e3a8a]/10 text-[#1e3a8a] flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined">menu_book</span>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold uppercase tracking-wide">Active</span>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-xs font-bold uppercase tracking-wide text-[#1e3a8a]"><?= Html::encode((string)$card['grade_name']) ?></p>
                                        <h5 class="text-lg font-bold text-slate-900 mt-1"><?= Html::encode((string)$card['section_name']) ?></h5>
                                        <p class="text-sm text-slate-600 mt-1"><?= Html::encode((string)$card['subject_label']) ?></p>
                                    </div>

                                    <div class="flex flex-wrap gap-4 text-xs text-slate-500">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">groups</span>
                                            <?= (int)$card['student_count'] ?> Students
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">person</span>
                                            <?= Html::encode((string)$card['mentor_name']) ?>
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap gap-2 mt-auto">
                                        <?php if ($canOpenSection): ?>
                                            <a href="<?= Url::to(['view', 'section_id' => $sectionId]) ?>"
                                               class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-xs font-bold no-underline">
                                                View
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-slate-100 text-slate-400 text-xs font-bold cursor-not-allowed"
                                                  title="<?= $hasCourses ? 'None of the assigned courses are available with your current role.' : 'Assign courses to this section before opening it.' ?>">
                                                View
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
<?= $this->render('../layouts/_page-end') ?>
