<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolDepartment $model
 * @var common\modules\employee\models\Employee[] $leaders
 * @var common\modules\employee\models\Employee[] $availableLeaders
 * @var string $userName
 * @var string $currentPage
 */

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Edit School Department | CultureHR';

$leaderOptions = ArrayHelper::map($availableLeaders ?? $leaders, 'id', static function ($employee) {
    $title = trim((string)($employee->job_title ?? ''));
    return $title !== '' ? $employee->full_name . ' (' . $title . ')' : $employee->full_name;
});

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'school_department_hierarchy']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Edit School Department', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="max-w-4xl mx-auto px-6 lg:px-8 py-8">
                <a href="<?= Url::to(['/hr/school-department/hierarchy']) ?>"
                   class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-[#1e3a8a] no-underline mb-6">
                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                    Back to Infrastructure Hierarchy
                </a>

                <?= Html::beginForm(['update', 'id' => (int)$model->id], 'post', ['class' => 'space-y-6']) ?>

                <?php if ($model->hasErrors()): ?>
                    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                        <?= Html::errorSummary($model) ?>
                    </div>
                <?php endif; ?>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-lg font-bold text-slate-900">Department Details</h2>
                    </div>
                    <div class="p-6 sm:p-8 space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Department Name <span class="text-red-500">*</span>
                            </label>
                            <?= Html::activeTextInput($model, 'name', [
                                'class' => 'w-full rounded-lg border-slate-200 bg-slate-50 focus:border-[#1e3a8a] focus:ring-[#1e3a8a] h-12 px-4',
                            ]) ?>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                            <?= Html::activeDropDownList($model, 'status', [
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ], [
                                'class' => 'w-full h-12 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                            ]) ?>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-lg font-bold text-slate-900">Leadership</h2>
                        <p class="text-sm text-slate-500 mt-1">Only teachers assigned to this department can be selected as head or deputy.</p>
                    </div>
                    <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Head of Department / Dean <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <?= Html::activeDropDownList($model, 'head_id', $leaderOptions, [
                                'prompt' => 'Select Leader',
                                'class' => 'w-full h-12 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                            ]) ?>
                            <?= Html::error($model, 'head_id', ['class' => 'text-red-600 text-xs mt-1']) ?>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Deputy Head <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <?= Html::activeDropDownList($model, 'deputy_head_id', $leaderOptions, [
                                'prompt' => 'Not Assigned',
                                'class' => 'w-full h-12 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                            ]) ?>
                            <?= Html::error($model, 'deputy_head_id', ['class' => 'text-red-600 text-xs mt-1']) ?>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-between">
                    <a href="<?= Url::to(['/hr/school-department/hierarchy']) ?>" class="text-slate-500 font-semibold no-underline hover:text-slate-700">Cancel</a>
                    <button type="submit" class="inline-flex items-center gap-2 h-12 px-8 rounded-lg bg-[#1e3a8a] text-white text-sm font-bold">
                        Save Changes
                    </button>
                </div>

                <?= Html::endForm() ?>
            </div>
        </main>
    </div>
</div>
