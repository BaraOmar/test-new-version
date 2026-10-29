<?php

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var string $search
 * @var string $userName
 * @var string $currentPage
 */

use common\modules\transport\models\TransportArea;
use common\helpers\CurrencyHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Transportation Areas | CultureHR';

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../../layouts/_navbar', ['currentPage' => $currentPage ?? 'transportation_areas']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../../layouts/_topbar', ['pageTitle' => 'Areas', 'userName' => $userName]) ?>

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
                            <a href="<?= Html::encode(Url::to(['hub'])) ?>"
                               class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wide text-slate-500 no-underline hover:text-[#1e3a8a] mb-3">
                                <span class="material-symbols-outlined text-sm">arrow_back</span>
                                Back to Transportations
                            </a>
                            <h1 class="text-2xl font-black text-slate-900">Areas</h1>
                            <p class="text-sm text-slate-500 mt-1">Define geographic pickup zones for school transportation.</p>
                        </div>
                        <a href="<?= Html::encode(Url::to(['create-area'])) ?>"
                           class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold no-underline">
                            <span class="material-symbols-outlined text-lg">add</span>
                            Add Area
                        </a>
                    </div>

                    <div class="px-5 py-4 border-b border-slate-100">
                        <?= Html::beginForm(['areas'], 'get', ['class' => 'flex flex-wrap items-center gap-3']) ?>
                            <input type="search" name="search" value="<?= Html::encode($search) ?>"
                                   placeholder="Search areas..."
                                   class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm min-w-[16rem]">
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 h-11 px-5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-bold cursor-pointer">
                                <span class="material-symbols-outlined text-base">search</span>
                                Search
                            </button>
                            <a href="<?= Html::encode(Url::to(['areas'])) ?>"
                               class="inline-flex items-center gap-1.5 h-11 px-5 rounded-lg border border-slate-200 bg-slate-50 text-slate-600 text-sm font-bold no-underline hover:bg-slate-100">
                                <span class="material-symbols-outlined text-base">restart_alt</span>
                                Clear
                            </a>
                        <?= Html::endForm() ?>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                                    <th class="px-4 py-3 text-left">Area Name</th>
                                    <th class="px-4 py-3 text-left">Price</th>
                                    <th class="px-4 py-3 text-left">Description</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($dataProvider->getModels() as $area): ?>
                                    <?php if (!$area instanceof TransportArea) {
                                        continue;
                                    } ?>
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-4 py-3 font-semibold text-slate-900"><?= Html::encode($area->name) ?></td>
                                        <td class="px-4 py-3 font-semibold text-slate-900"><?= $area->price !== null ? CurrencyHelper::format((float)$area->price) : '—' ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= Html::encode($area->description ?: '—') ?></td>
                                        <td class="px-4 py-3">
                                            <?php if ($area->is_active): ?>
                                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700">Active</span>
                                            <?php else: ?>
                                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <a href="<?= Html::encode(Url::to(['update-area', 'id' => $area->id])) ?>"
                                                   class="text-[#1e3a8a] font-bold no-underline hover:underline">Edit</a>
                                                <?= Html::beginForm(['delete-area', 'id' => $area->id], 'post', ['class' => 'inline']) ?>
                                                    <button type="submit"
                                                            onclick="return confirm('Delete this area?');"
                                                            class="text-red-600 font-bold bg-transparent border-0 cursor-pointer hover:underline">
                                                        Delete
                                                    </button>
                                                <?= Html::endForm() ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($dataProvider->getTotalCount() === 0): ?>
                                    <tr>
                                        <td colspan="5" class="px-4 py-10 text-center text-slate-500">No areas found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($dataProvider->pagination->pageCount > 1): ?>
                        <div class="px-5 py-4 border-t border-slate-100">
                            <?= LinkPager::widget(['pagination' => $dataProvider->pagination]) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
