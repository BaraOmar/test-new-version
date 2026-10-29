<?php

/**
 * @var yii\web\View $this
 * @var common\modules\transport\models\TransportBus|null $bus
 * @var string $userName
 * @var string $currentPage
 */

use yii\helpers\Html;
use yii\helpers\Url;

$isUpdate = $bus !== null;
$this->title = ($isUpdate ? 'Edit Bus' : 'Add Bus') . ' | CultureHR';

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../../layouts/_navbar', ['currentPage' => $currentPage ?? 'transportation_buses']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../../layouts/_topbar', ['pageTitle' => $isUpdate ? 'Edit Bus' : 'Add Bus', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-3xl mx-auto px-6 lg:px-8 py-8">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h1 class="text-2xl font-black text-slate-900"><?= $isUpdate ? 'Edit Bus' : 'Add Bus' ?></h1>
                        <p class="text-sm text-slate-500 mt-1">Register a bus with plate number and passenger capacity.</p>
                    </div>

                    <?= Html::beginForm($isUpdate ? ['update-bus', 'id' => $bus->id] : ['create-bus'], 'post', ['class' => 'p-5 space-y-5']) ?>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5" for="bus-plate">Plate Number</label>
                            <input type="text" id="bus-plate" name="plate_number" required
                                   value="<?= Html::encode($bus->plate_number ?? '') ?>"
                                   class="w-full h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5" for="bus-capacity">Capacity</label>
                            <input type="number" id="bus-capacity" name="capacity" min="1" max="200" required
                                   value="<?= Html::encode((string)($bus->capacity ?? 30)) ?>"
                                   class="w-full h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5" for="bus-notes">Notes</label>
                            <textarea id="bus-notes" name="notes" rows="3"
                                      class="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 text-sm"><?= Html::encode($bus->notes ?? '') ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5" for="bus-status">Status</label>
                            <select id="bus-status" name="is_active" class="w-full h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm">
                                <option value="1" <?= ($bus === null || $bus->is_active) ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= ($bus !== null && !$bus->is_active) ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 h-11 px-6 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold cursor-pointer border-0">
                                <?= $isUpdate ? 'Save Changes' : 'Create Bus' ?>
                            </button>
                            <a href="<?= Html::encode(Url::to(['buses'])) ?>"
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
