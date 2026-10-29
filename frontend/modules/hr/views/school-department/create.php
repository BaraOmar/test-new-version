<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolDepartment $model
 * @var common\modules\employee\models\Employee[] $leaders
 * @var common\modules\employee\models\Employee[] $availableLeaders
 * @var string[] $departmentGrades
 * @var array<int, array{name:string,grade_ids:int[],grade_credits:array<int|string,string>,grade_max_scores:array<int|string,string>}> $subjectsDraft
 * @var string $userName
 * @var string $currentPage
 */

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Add New Department | CultureHR';

$leaderOptions = ArrayHelper::map($availableLeaders ?? $leaders, 'id', static function ($employee) {
    $title = trim((string)($employee->job_title ?? ''));
    return $title !== '' ? $employee->full_name . ' (' . $title . ')' : $employee->full_name;
});

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'school_department_create']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Add New Department', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 py-8">
                <nav class="text-sm text-slate-500 mb-6">
                    <a href="<?= Url::to(['/hr/school-department/hierarchy']) ?>" class="hover:text-[#1e3a8a] no-underline">Academic Settings</a>
                    <span class="mx-2">›</span>
                    <span class="text-slate-900 font-medium">Add New Department</span>
                </nav>

                <div class="mb-8">
                    <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">Department Registry</h1>
                    <p class="text-slate-500 mt-3 max-w-2xl leading-relaxed">
                        Create a new school department with its grades and subjects. A department cannot be saved until at least one subject is assigned to its grades.
                    </p>
                </div>

                <?php
                $formError = (string)Yii::$app->session->getFlash('error', '');
                ?>
                <?php if ($formError !== ''): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                        <?= Html::encode($formError) ?>
                    </div>
                <?php endif; ?>
                <?php if ($model->hasErrors()): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                        <?= Html::errorSummary($model) ?>
                    </div>
                <?php endif; ?>

                <?= Html::beginForm(['create'], 'post', ['id' => 'department-create-form', 'class' => 'space-y-6']) ?>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-lg font-bold text-slate-900">Department Details</h2>
                    </div>
                    <div class="p-6 sm:p-8">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Department Name <span class="text-red-500">*</span>
                        </label>
                        <?= Html::activeTextInput($model, 'name', [
                            'required' => true,
                            'placeholder' => 'e.g. Faculty of Computer Science',
                            'class' => 'w-full rounded-lg border-slate-200 bg-slate-50 focus:border-[#1e3a8a] focus:ring-[#1e3a8a] h-12 px-4',
                        ]) ?>
                        <?= Html::error($model, 'name', ['class' => 'text-red-600 text-xs mt-1']) ?>
                    </div>
                </section>

                <?= $this->render('_department-create-academic', [
                    'departmentGrades' => $departmentGrades ?? [''],
                    'subjectsDraft' => $subjectsDraft ?? [['name' => '', 'grade_ids' => [], 'grade_credits' => [], 'grade_max_scores' => []]],
                ]) ?>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-lg font-bold text-slate-900">Leadership</h2>
                        <p class="text-sm text-slate-500 mt-1">Assign a head and deputy after teachers have been added to this department.</p>
                    </div>
                    <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Head of Department / Dean <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl">person</span>
                                <?= Html::activeDropDownList($model, 'head_id', $leaderOptions, [
                                    'prompt' => 'Assign after department is created',
                                    'disabled' => true,
                                    'class' => 'w-full h-12 pl-10 pr-4 rounded-lg border border-slate-200 bg-slate-100 text-sm text-slate-500',
                                ]) ?>
                            </div>
                            <?= Html::error($model, 'head_id', ['class' => 'text-red-600 text-xs mt-1']) ?>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Deputy Head <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <?= Html::activeDropDownList($model, 'deputy_head_id', $leaderOptions, [
                                'prompt' => 'Assign after department is created',
                                'disabled' => true,
                                'class' => 'w-full h-12 px-4 rounded-lg border border-slate-200 bg-slate-100 text-sm text-slate-500',
                            ]) ?>
                            <?= Html::error($model, 'deputy_head_id', ['class' => 'text-red-600 text-xs mt-1']) ?>
                        </div>
                    </div>
                </section>

                <?= Html::activeHiddenInput($model, 'status', ['value' => 'active']) ?>

                <div class="flex items-center justify-between pt-2">
                    <a href="<?= Url::to(['/hr/school-department/hierarchy']) ?>" class="text-slate-500 font-semibold no-underline hover:text-slate-700">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 h-12 px-8 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold shadow-md transition-all">
                        Save Department &amp; Subjects
                    </button>
                </div>

                <?= Html::endForm() ?>
            </div>
        </main>
    </div>
</div>
