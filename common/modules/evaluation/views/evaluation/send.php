<?php

/**
 * Pixel UI reference: Legacy_Code/HRMS/send_evaluation.php
 *
 * @var yii\web\View $this
 * @var common\modules\employee\models\Employee $employee
 * @var common\modules\evaluation\models\Evaluation[] $evaluations
 * @var int[] $assignedIds
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Send Evaluation';
?>

<div class="evaluation-send max-w-2xl mx-auto">
    <a href="<?= Url::to(['/hr/hr/employees']) ?>" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-[#642754] mb-6">
        <span class="material-symbols-outlined text-lg">arrow_back</span>
        Back to Directory
    </a>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
        <h1 class="text-xl font-bold text-slate-900 mb-1">Send Direct Evaluation</h1>
        <p class="text-slate-500 text-sm mb-4">
            Assign an evaluation to <strong><?= Html::encode($employee->full_name) ?></strong>. Only this employee will receive it.
        </p>
        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg">
            <div class="size-10 rounded-full bg-[#642754]/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[#642754]">person</span>
            </div>
            <div class="min-w-0">
                <p class="font-semibold text-slate-900"><?= Html::encode($employee->full_name) ?></p>
                <p class="text-sm text-slate-500 truncate">
                    <?= Html::encode($employee->email) ?> · <?= Html::encode($employee->job_title ?: '—') ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <h2 class="px-6 py-4 font-semibold text-slate-900 border-b border-slate-200">Select an evaluation to send</h2>

        <a href="<?= Url::to(['create', 'employee_id' => (int)$employee->id]) ?>"
           class="flex items-center gap-4 px-6 py-4 hover:bg-[#642754]/5 border-b border-slate-100 transition-colors no-underline">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#eb652c]/10 text-[#eb652c]">
                <span class="material-symbols-outlined">add</span>
            </span>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-[#642754]">Create Evaluation</p>
                <p class="text-xs text-slate-500">Open the builder to create a new evaluation for this employee</p>
            </div>
            <span class="material-symbols-outlined text-slate-400 shrink-0">chevron_right</span>
        </a>

        <?php if (!$evaluations): ?>
            <div class="p-8 text-center text-slate-500">
                <span class="material-symbols-outlined text-4xl mb-2 opacity-50">quiz</span>
                <p>No published evaluations yet. Use <strong>Create Evaluation</strong> above, or create one from the Evaluations list.</p>
            </div>
        <?php else: ?>
            <?php $form = ActiveForm::begin(['options' => ['class' => 'divide-y divide-slate-100']]); ?>
                <?php foreach ($evaluations as $evaluation): ?>
                    <?php $alreadyAssigned = in_array((int)$evaluation->id, $assignedIds, true); ?>
                    <label class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50 cursor-pointer <?= $alreadyAssigned ? 'opacity-60' : '' ?>">
                        <input
                            type="radio"
                            name="evaluation_id"
                            value="<?= (int)$evaluation->id ?>"
                            <?= $alreadyAssigned ? 'disabled' : '' ?>
                            class="rounded-full border-slate-300 text-[#642754] focus:ring-[#642754]"
                        />
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 truncate"><?= Html::encode($evaluation->title) ?></p>
                            <p class="text-xs text-slate-500">
                                Created <?= Html::encode(Yii::$app->formatter->asDate($evaluation->created_at, 'php:M j, Y')) ?>
                                <?php if (!empty($evaluation->status)): ?>
                                    · <?= Html::encode($evaluation->status) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if ($alreadyAssigned): ?>
                            <span class="text-xs font-semibold text-emerald-600 shrink-0">Already assigned</span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
                <div class="px-6 py-4 bg-slate-50">
                    <?php
                    $firstName = explode(' ', trim((string)$employee->full_name))[0] ?: 'Employee';
                    ?>
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-[#eb652c] text-white font-semibold rounded-lg hover:bg-[#d85a24] transition-all">
                        Send Evaluation to <?= Html::encode($firstName) ?>
                    </button>
                </div>
            <?php ActiveForm::end(); ?>
        <?php endif; ?>
    </div>
</div>
