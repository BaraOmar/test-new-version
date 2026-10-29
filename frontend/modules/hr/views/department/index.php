<?php

/**
 * View Departments (HR module).
 *
 * @var yii\web\View $this
 * @var array<int, array{id:int,name:string,managerName:string,status:string,employeeCount:int}> $cards
 * @var string $userName
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Departments | CultureHR';

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');

?>

<div class="flex h-screen overflow-hidden">
    <?= $this->render('@frontend/modules/hr/views/layouts/_navbar.php', ['currentPage' => 'employees']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('@frontend/modules/hr/views/layouts/_topbar.php', ['pageTitle' => 'Departments', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="layout-content-container flex flex-col w-full max-w-[1400px] mx-auto px-8 py-8 gap-6">
                <div class="flex flex-wrap justify-between items-end gap-4">
                    <div class="flex flex-col gap-1">
                        <h1 class="text-slate-900 dark:text-slate-100 text-4xl font-black leading-tight tracking-tight">Departments</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-base font-normal">Browse departments and view details.</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="<?= Url::to(['/hr/department/create']) ?>"
                           class="flex items-center gap-2 rounded-lg h-10 px-4 bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold shadow-md transition-all">
                            <span class="material-symbols-outlined text-lg">add</span>
                            <span>Create Department</span>
                        </a>
                        <a href="<?= Url::to(['/hr/hr/employees']) ?>"
                           class="flex items-center gap-2 rounded-lg h-10 px-4 bg-white hover:bg-slate-50 text-slate-900 text-sm font-bold shadow-md border border-slate-200 transition-all">
                            <span class="material-symbols-outlined text-lg">arrow_back</span>
                            <span>Back to Directory</span>
                        </a>
                    </div>
                </div>

                <?php if ($successMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-medium">
                        <?= Html::encode($successMessage) ?>
                    </div>
                <?php endif; ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium">
                        <?= Html::encode($errorMessage) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$cards): ?>
                    <div class="p-6 bg-white rounded-xl border border-slate-200 shadow-sm text-slate-600">
                        No departments found.
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php foreach ($cards as $card): ?>
                        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-5 flex flex-col gap-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-slate-900 dark:text-slate-100 text-lg font-black truncate">
                                        <?= Html::encode($card['name'] !== '' ? $card['name'] : ('Department #' . (int)$card['id'])) ?>
                                    </div>
                                    <div class="text-slate-500 dark:text-slate-400 text-sm">
                                        <?= Html::encode($card['employeeCount'] . ' employee' . ($card['employeeCount'] === 1 ? '' : 's')) ?>
                                    </div>
                                </div>
                                <span class="material-symbols-outlined text-slate-400">apartment</span>
                            </div>

                            <div class="text-sm text-slate-600 dark:text-slate-300 space-y-1">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Manager</span>
                                    <span class="font-semibold text-right">
                                        <?= Html::encode($card['managerName'] !== '' ? $card['managerName'] : 'N/A') ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Status</span>
                                    <span class="font-semibold text-right">
                                        <?= Html::encode($card['status'] !== '' ? $card['status'] : 'N/A') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="pt-2 flex flex-col gap-2">
                                <div class="flex gap-2">
                                    <a href="<?= Url::to(['/hr/department/view', 'id' => (int)$card['id']]) ?>"
                                       class="flex-1 flex items-center justify-center gap-2 rounded-lg h-10 px-3 bg-white hover:bg-slate-50 text-slate-900 text-sm font-bold shadow-md border border-slate-200 transition-all">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                        <span>View</span>
                                    </a>
                                    <a href="<?= Url::to(['/hr/department/update', 'id' => (int)$card['id']]) ?>"
                                       class="flex-1 flex items-center justify-center gap-2 rounded-lg h-10 px-3 bg-[#6b2a6e] hover:bg-[#5d245f] text-white text-sm font-bold shadow-md transition-all">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                        <span>Update</span>
                                    </a>
                                </div>
                                <?= Html::beginForm(['/hr/department/delete', 'id' => (int)$card['id']], 'post', ['class' => 'm-0']) ?>
                                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                                    <button type="submit"
                                            onclick="return confirm(<?= json_encode(
                                                'Delete ' . ($card['name'] !== '' ? '"' . $card['name'] . '"' : 'this department') . '? This cannot be undone.',
                                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                                            ) ?>);"
                                            class="w-full flex items-center justify-center gap-2 rounded-lg h-10 px-4 bg-white hover:bg-red-50 text-red-600 text-sm font-bold shadow-md border border-red-200 transition-all">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                        <span>Delete</span>
                                    </button>
                                <?= Html::endForm() ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</div>

