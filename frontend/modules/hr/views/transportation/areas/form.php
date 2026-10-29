<?php

/**
 * @var yii\web\View $this
 * @var common\modules\transport\models\TransportArea|null $area
 * @var string $userName
 * @var string $currentPage
 */

use yii\helpers\Html;
use yii\helpers\Url;

$isUpdate = $area !== null;
$this->title = ($isUpdate ? 'Edit Area' : 'Add Area') . ' | CultureHR';

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../../layouts/_navbar', ['currentPage' => $currentPage ?? 'transportation_areas']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../../layouts/_topbar', ['pageTitle' => $isUpdate ? 'Edit Area' : 'Add Area', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-3xl mx-auto px-6 lg:px-8 py-8">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h1 class="text-2xl font-black text-slate-900"><?= $isUpdate ? 'Edit Area' : 'Add Area' ?></h1>
                        <p class="text-sm text-slate-500 mt-1">Enter the area name, price, and optional description.</p>
                    </div>

                    <?= Html::beginForm($isUpdate ? ['update-area', 'id' => $area->id] : ['create-area'], 'post', ['class' => 'p-5 space-y-5']) ?>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5" for="area-name">Area Name</label>
                            <input type="text" id="area-name" name="name" required
                                   value="<?= Html::encode($area->name ?? '') ?>"
                                   class="w-full h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5" for="area-price">Price</label>
                            <input type="number" id="area-price" name="price" min="0" step="0.01"
                                   value="<?= Html::encode($area !== null && $area->price !== null ? (string)$area->price : '') ?>"
                                   class="w-full h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5" for="area-description">Description</label>
                            <textarea id="area-description" name="description" rows="4"
                                      class="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 text-sm"><?= Html::encode($area->description ?? '') ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5" for="area-status">Status</label>
                            <select id="area-status" name="is_active" class="w-full h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm">
                                <option value="1" <?= ($area === null || $area->is_active) ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= ($area !== null && !$area->is_active) ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 h-11 px-6 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold cursor-pointer border-0">
                                <?= $isUpdate ? 'Save Changes' : 'Create Area' ?>
                            </button>
                            <a href="<?= Html::encode(Url::to(['areas'])) ?>"
                               class="inline-flex items-center h-11 px-5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-bold no-underline">
                                Cancel
                            </a>
                        </div>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </main>
    </div>
</div>
