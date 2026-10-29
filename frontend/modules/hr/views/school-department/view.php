<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolDepartment $model
 * @var string $userName
 * @var string $currentPage
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'School Department | CultureHR';

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'school_department_hierarchy']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'School Department', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="max-w-4xl mx-auto px-6 lg:px-8 py-8 space-y-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 mb-2">Academic Settings</p>
                        <h1 class="text-3xl font-black text-slate-900"><?= Html::encode($model->name) ?></h1>
                        <p class="text-slate-500 mt-2 capitalize">Status: <?= Html::encode($model->status) ?></p>
                    </div>
                    <div class="flex gap-2">
                        <a href="<?= Url::to(['/hr/school-department/hierarchy']) ?>"
                           class="inline-flex items-center gap-2 h-10 px-4 rounded-lg border border-slate-200 bg-white text-sm font-semibold no-underline text-slate-700">
                            Back
                        </a>
                        <a href="<?= Url::to(['/hr/school-department/update', 'id' => (int)$model->id]) ?>"
                           class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-[#1e3a8a] text-white text-sm font-semibold no-underline">
                            Edit
                        </a>
                    </div>
                </div>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-lg font-bold text-slate-900">Leadership</h2>
                    </div>
                    <dl class="p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">Head / Dean</dt>
                            <dd class="text-sm font-semibold text-slate-900"><?= Html::encode($model->head?->full_name ?: '—') ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">Deputy Head</dt>
                            <dd class="text-sm font-semibold text-slate-900"><?= Html::encode($model->deputyHead?->full_name ?: '—') ?></dd>
                        </div>
                    </dl>
                </section>

                <?= Html::beginForm(['delete', 'id' => (int)$model->id], 'post', ['class' => 'text-right']) ?>
                    <button type="submit"
                            class="text-sm font-semibold text-red-600 bg-transparent border-0 cursor-pointer hover:text-red-700"
                            onclick="return confirm('Delete this school department?');">
                        Delete department
                    </button>
                <?= Html::endForm() ?>
            </div>
        </main>
    </div>
</div>
