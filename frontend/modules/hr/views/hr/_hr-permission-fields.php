<?php

/**
 * @var yii\web\View $this
 * @var yii\base\Model $formModel
 * @var string[] $selectedPermissions
 * @var array<string, array{title:string,icon:string,items:array<string,string>}> $permissionGroups
 * @var array<string, string[]> $permissionPresets
 * @var string $checkboxClass
 */

use yii\helpers\Html;

?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Module Access Permissions</h3>
        <div class="flex flex-wrap items-center gap-2.5">
            <button type="button"
                    data-select-all
                    class="inline-flex items-center justify-center min-h-10 px-5 py-2.5 rounded-xl text-sm font-bold border-2 border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 hover:border-blue-300 active:scale-[0.98] transition-all shadow-sm">
                Select All
            </button>
            <button type="button"
                    data-clear-all
                    class="inline-flex items-center justify-center min-h-10 px-5 py-2.5 rounded-xl text-sm font-bold border-2 border-red-200 bg-red-50 text-red-600 hover:bg-red-100 hover:border-red-300 active:scale-[0.98] transition-all shadow-sm">
                Clear All
            </button>
        </div>
    </div>

    <div class="flex flex-wrap gap-2.5 mb-5">
        <button type="button" data-preset="full"
                class="px-5 py-2.5 rounded-full text-sm font-bold uppercase tracking-wide border border-slate-200 bg-slate-50 text-slate-700 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 active:scale-[0.98] transition-all">
            Full Access
        </button>
        <button type="button" data-preset="limited"
                class="px-5 py-2.5 rounded-full text-sm font-bold uppercase tracking-wide border border-slate-200 bg-slate-50 text-slate-700 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 active:scale-[0.98] transition-all">
            Basic Access
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 2xl:gap-5">
        <?php foreach ($permissionGroups as $group): ?>
            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                <div class="flex items-center gap-2 mb-3 text-slate-700">
                    <span class="material-symbols-outlined text-lg text-blue-600"><?= Html::encode($group['icon']) ?></span>
                    <span class="text-xs font-bold uppercase tracking-wide"><?= Html::encode($group['title']) ?></span>
                </div>
                <div class="space-y-2.5">
                    <?php foreach ($group['items'] as $key => $label): ?>
                        <label class="flex items-start gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="<?= Html::encode($key) ?>"
                                   class="<?= Html::encode($checkboxClass) ?> mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                <?= in_array($key, $selectedPermissions, true) ? 'checked' : '' ?>>
                            <span><?= Html::encode($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?= Html::error($formModel, 'permissions', ['class' => 'text-red-600 text-xs mt-3']) ?>
</div>
