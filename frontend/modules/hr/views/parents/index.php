<?php

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var string $search
 * @var string $userName
 * @var string $currentPage
 */

use common\modules\finance\models\FinanceAccountParentProfile;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Parents Directory | CultureHR';

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'parents_directory']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Parents Directory', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 space-y-6">
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

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-black text-slate-900">Parents Directory</h1>
                            <p class="text-sm text-slate-500 mt-1">Browse, view, and update registered parents and their children.</p>
                        </div>
                        <a href="<?= Html::encode(Url::to(['create'])) ?>"
                           class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold no-underline">
                            <span class="material-symbols-outlined text-lg">person_add</span>
                            Add Parent
                        </a>
                    </div>

                    <div class="px-5 py-4 border-b border-slate-100 space-y-3">
                        <?php
                        $hasActiveFilters = $search !== '';
                        $clearFiltersUrl = Url::to(['index']);
                        $totalCount = (int)$dataProvider->getTotalCount();
                        $showingText = $totalCount === 0
                            ? 'No parents found'
                            : ('Showing ' . $totalCount . ' parent' . ($totalCount === 1 ? '' : 's'));
                        ?>
                        <?= Html::beginForm(['index'], 'get', [
                            'class' => 'flex flex-col sm:flex-row sm:items-center gap-3',
                            'id' => 'parents-directory-filter',
                        ]) ?>
                            <div class="flex-1 relative min-w-0">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">search</span>
                                <input name="search" value="<?= Html::encode((string)$search) ?>"
                                       class="w-full h-11 pl-10 pr-4 rounded-lg border-slate-200 bg-slate-50 text-slate-900 placeholder:text-slate-400 focus:ring-primary focus:border-primary border text-sm"
                                       placeholder="Search by first name, last name, or phone..."
                                       type="text"
                                       autocomplete="off" />
                            </div>
                            <div class="flex flex-wrap gap-3 items-center">
                                <button type="submit"
                                        class="h-11 px-4 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors flex items-center justify-center"
                                        title="Apply search">
                                    <span class="material-symbols-outlined">search</span>
                                </button>
                                <?php if ($hasActiveFilters): ?>
                                    <a href="<?= Html::encode($clearFiltersUrl) ?>"
                                       class="inline-flex items-center justify-center h-11 px-5 rounded-lg text-sm font-semibold text-slate-700 border border-slate-200 bg-white hover:bg-slate-50 transition-colors whitespace-nowrap no-underline">
                                        Clear all
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?= Html::endForm() ?>

                        <?php if ($hasActiveFilters): ?>
                            <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-slate-100">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide mr-1">Active filters</span>
                                <span class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1.5 rounded-lg bg-blue-50 border border-blue-100 text-sm text-blue-800">
                                    <span class="text-xs font-semibold text-blue-600/80 uppercase tracking-wide">Search</span>
                                    <span class="font-medium"><?= Html::encode($search) ?></span>
                                    <a href="<?= Html::encode($clearFiltersUrl) ?>"
                                       class="ml-1 p-0.5 rounded-md text-blue-500 hover:bg-blue-100 hover:text-blue-700 transition-colors no-underline"
                                       title="Remove search filter"
                                       aria-label="Remove search filter">
                                        <span class="material-symbols-outlined text-base leading-none">close</span>
                                    </a>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                                    <th class="px-4 py-3 text-left">Parent Name</th>
                                    <th class="px-4 py-3 text-left">Phone</th>
                                    <th class="px-4 py-3 text-left">Email</th>
                                    <th class="px-4 py-3 text-left">Children</th>
                                    <th class="px-4 py-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if ($dataProvider->getTotalCount() === 0): ?>
                                    <tr>
                                        <td colspan="5" class="px-4 py-10 text-center text-slate-500">No parents found.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($dataProvider->getModels() as $profile): ?>
                                    <?php if (!$profile instanceof FinanceAccountParentProfile) {
                                        continue;
                                    } ?>
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="px-4 py-3 font-bold text-slate-900">
                                            <a href="<?= Html::encode(Url::to(['view', 'id' => (int)$profile->id])) ?>"
                                               class="text-[#1e3a8a] no-underline hover:underline">
                                                <?= Html::encode($profile->getFullName()) ?>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700"><?= Html::encode($profile->phone) ?></td>
                                        <td class="px-4 py-3 text-slate-700"><?= Html::encode($profile->email ?: '—') ?></td>
                                        <td class="px-4 py-3 text-slate-700"><?= (int)$profile->kids_count ?></td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="<?= Html::encode(Url::to(['view', 'id' => (int)$profile->id])) ?>"
                                                   class="inline-flex items-center h-9 px-3 rounded-lg border border-slate-200 text-slate-700 text-xs font-bold no-underline hover:bg-slate-50">
                                                    View
                                                </a>
                                                <a href="<?= Html::encode(Url::to(['update', 'id' => (int)$profile->id])) ?>"
                                                   class="inline-flex items-center h-9 px-3 rounded-lg bg-[#1e3a8a] text-white text-xs font-bold no-underline hover:bg-[#1e40af]">
                                                    Edit
                                                </a>
                                                <button type="button"
                                                        class="parent-delete-btn inline-flex items-center h-9 px-3 rounded-lg border border-red-200 bg-red-50 text-red-700 text-xs font-bold cursor-pointer hover:bg-red-100"
                                                        data-parent-id="<?= (int)$profile->id ?>"
                                                        data-parent-name="<?= Html::encode($profile->getFullName()) ?>">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <span class="text-sm text-slate-500"><?= Html::encode($showingText) ?></span>
                        <?= LinkPager::widget([
                            'pagination' => $dataProvider->pagination,
                            'options' => ['class' => 'flex flex-wrap gap-2 justify-center sm:justify-end'],
                            'linkOptions' => ['class' => 'px-3 py-1.5 rounded-lg border border-slate-200 text-sm no-underline text-slate-700'],
                            'disabledPageCssClass' => 'opacity-50 pointer-events-none',
                            'activePageCssClass' => 'bg-[#1e3a8a] text-white border-[#1e3a8a]',
                        ]) ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<div id="parent-delete-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/50" data-parent-delete-close></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-xl p-6" role="dialog" aria-modal="true" aria-labelledby="parent-delete-title">
            <div class="flex items-start gap-3 mb-4">
                <div class="size-10 rounded-full bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined">delete</span>
                </div>
                <div>
                    <h2 id="parent-delete-title" class="text-lg font-black text-slate-900">Delete parent?</h2>
                    <p class="text-sm text-slate-500 mt-1">
                        This will permanently remove
                        <strong id="parent-delete-name" class="text-slate-800"></strong>
                        and all registered children from the directory.
                    </p>
                </div>
            </div>

            <p class="text-sm text-slate-600 mb-2">
                Type <span class="font-bold text-red-700">delete</span> to confirm.
            </p>
            <?= Html::beginForm(['delete', 'id' => 0], 'post', ['id' => 'parent-delete-form']) ?>
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                <input type="text"
                       name="confirmation"
                       id="parent-delete-confirmation"
                       autocomplete="off"
                       placeholder="delete"
                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm mb-5">
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="button"
                            class="inline-flex items-center h-11 px-5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-bold cursor-pointer"
                            data-parent-delete-close>
                        Cancel
                    </button>
                    <button type="submit"
                            id="parent-delete-submit"
                            class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-red-600 text-white text-sm font-bold border-0 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                        <span class="material-symbols-outlined text-lg">delete</span>
                        Delete Parent
                    </button>
                </div>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>

<?php
$deleteUrlTemplate = Url::to(['delete', 'id' => '__ID__']);
$deleteUrlTemplateJs = Json::encode($deleteUrlTemplate);
$this->registerJs(<<<JS
(function () {
    var modal = document.getElementById('parent-delete-modal');
    var form = document.getElementById('parent-delete-form');
    var nameEl = document.getElementById('parent-delete-name');
    var confirmInput = document.getElementById('parent-delete-confirmation');
    var submitBtn = document.getElementById('parent-delete-submit');
    var deleteUrlTemplate = {$deleteUrlTemplateJs};

    if (!modal || !form || !nameEl || !confirmInput || !submitBtn) {
        return;
    }

    function openModal(parentId, parentName) {
        form.action = deleteUrlTemplate.replace('__ID__', String(parentId));
        nameEl.textContent = parentName || 'this parent';
        confirmInput.value = '';
        submitBtn.disabled = true;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        confirmInput.focus();
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        confirmInput.value = '';
        submitBtn.disabled = true;
    }

    function syncSubmitState() {
        submitBtn.disabled = confirmInput.value.trim().toLowerCase() !== 'delete';
    }

    document.querySelectorAll('.parent-delete-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(btn.getAttribute('data-parent-id'), btn.getAttribute('data-parent-name'));
        });
    });

    modal.querySelectorAll('[data-parent-delete-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    confirmInput.addEventListener('input', syncSubmitState);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
})();
JS);
?>
