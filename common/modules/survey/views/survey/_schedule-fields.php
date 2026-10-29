<?php

/**
 * Survey open/close schedule fields (builder sidebar + update-schedule page).
 *
 * @var yii\web\View $this
 * @var common\modules\survey\models\Survey $model
 * @var string $endDateId
 */

use yii\helpers\Html;

$endDateId = $endDateId ?? 'endDate';
$inputClass = 'w-full rounded border border-slate-200 bg-white px-2 py-1.5 text-sm focus:border-[#642754] focus:ring-1 focus:ring-[#642754]';

?>

<div class="survey-schedule-fields space-y-4">
    <div class="rounded-lg border border-slate-200 p-3">
        <label class="flex items-center gap-2 text-xs font-bold text-slate-700 mb-2">
            <span class="material-symbols-outlined text-[#642754] text-sm">play_circle</span>
            Survey Opens
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <?= Html::activeInput('date', $model, 'start_date', [
                'class' => $inputClass,
                'onchange' => 'typeof updateEndDateMin === "function" && updateEndDateMin()',
            ]) ?>
            <?= Html::activeInput('time', $model, 'start_time', ['class' => $inputClass]) ?>
        </div>
        <p class="text-[10px] text-slate-500 mt-1">Leave empty to start immediately</p>
    </div>
    <div class="rounded-lg border border-slate-200 p-3">
        <label class="flex items-center gap-2 text-xs font-bold text-slate-700 mb-2">
            <span class="material-symbols-outlined text-[#642754] text-sm">stop_circle</span>
            Survey Closes
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <?= Html::activeInput('date', $model, 'end_date', ['id' => $endDateId, 'class' => $inputClass]) ?>
            <?= Html::activeInput('time', $model, 'end_time', ['class' => $inputClass]) ?>
        </div>
        <p class="text-[10px] text-slate-500 mt-1">Leave empty for no end date</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <button type="button" onclick="setDuration(7)" class="px-3 py-1.5 text-xs font-medium rounded-full border border-slate-200 hover:border-[#642754] hover:text-[#642754] transition-colors">7 Days</button>
        <button type="button" onclick="setDuration(14)" class="px-3 py-1.5 text-xs font-medium rounded-full border border-slate-200 hover:border-[#642754] hover:text-[#642754] transition-colors">14 Days</button>
        <button type="button" onclick="setDuration(30)" class="px-3 py-1.5 text-xs font-medium rounded-full border border-slate-200 hover:border-[#642754] hover:text-[#642754] transition-colors">30 Days</button>
        <button type="button" onclick="clearDuration()" class="px-3 py-1.5 text-xs font-medium rounded-full border border-slate-200 hover:border-[#642754] hover:text-[#642754] transition-colors">No limit</button>
    </div>
</div>
