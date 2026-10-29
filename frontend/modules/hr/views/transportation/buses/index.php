<?php

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var string $search
 * @var string $userName
 * @var string $currentPage
 */

use common\modules\transport\models\TransportBus;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Buses | CultureHR';

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../../layouts/_navbar', ['currentPage' => $currentPage ?? 'transportation_buses']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../../layouts/_topbar', ['pageTitle' => 'Buses', 'userName' => $userName]) ?>

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
                            <h1 class="text-2xl font-black text-slate-900">Buses</h1>
                            <p class="text-sm text-slate-500 mt-1">Register and manage school buses.</p>
                        </div>
                        <a href="<?= Html::encode(Url::to(['create-bus'])) ?>"
                           class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold no-underline">
                            <span class="material-symbols-outlined text-lg">add</span>
                            Add Bus
                        </a>
                    </div>

                    <div class="px-5 py-4 border-b border-slate-100">
                        <?= Html::beginForm(['buses'], 'get', ['class' => 'flex flex-wrap items-center gap-3']) ?>
                            <input type="search" name="search" value="<?= Html::encode($search) ?>"
                                   placeholder="Search by plate number..."
                                   class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm min-w-[16rem]">
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 h-11 px-5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-bold cursor-pointer">
                                <span class="material-symbols-outlined text-base">search</span>
                                Search
                            </button>
                            <a href="<?= Html::encode(Url::to(['buses'])) ?>"
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
                                    <th class="px-4 py-3 text-left">Plate Number</th>
                                    <th class="px-4 py-3 text-left">Capacity</th>
                                    <th class="px-4 py-3 text-left">Area</th>
                                    <th class="px-4 py-3 text-left">Driver</th>
                                    <th class="px-4 py-3 text-left">Supervisor</th>
                                    <th class="px-4 py-3 text-left">Students</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($dataProvider->getModels() as $bus): ?>
                                    <?php if (!$bus instanceof TransportBus) {
                                        continue;
                                    } ?>
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-4 py-3 font-semibold text-slate-900"><?= Html::encode($bus->plate_number) ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= (int)$bus->capacity ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= Html::encode($bus->area->name ?? '—') ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= Html::encode($bus->driver->full_name ?? '—') ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= Html::encode($bus->supervisor->full_name ?? '—') ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= (int)$bus->getAssignedStudentCount() ?></td>
                                        <td class="px-4 py-3">
                                            <?php if ($bus->is_active): ?>
                                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700">Active</span>
                                            <?php else: ?>
                                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <a href="<?= Html::encode(Url::to(['update-bus', 'id' => $bus->id])) ?>"
                                                   class="text-[#1e3a8a] font-bold no-underline hover:underline">Edit</a>
                                                <a href="<?= Html::encode(Url::to(['assign-bus', 'id' => $bus->id])) ?>"
                                                   class="text-[#642754] font-bold no-underline hover:underline">Assign</a>
                                                <?= Html::beginForm(['delete-bus', 'id' => $bus->id], 'post', ['class' => 'inline']) ?>
                                                    <button type="submit"
                                                            onclick="return confirm('Delete this bus and all student assignments?');"
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
                                        <td colspan="8" class="px-4 py-10 text-center text-slate-500">No buses found.</td>
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
