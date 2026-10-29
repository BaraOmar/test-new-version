<?php

/**
 * Pixel UI reference: Legacy_Code/HRMS/send_survey.php
 *
 * @var yii\web\View $this
 * @var common\modules\employee\models\Employee $employee
 * @var common\modules\survey\models\Survey[] $surveys
 * @var int[] $assignedIds
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Send Survey';
?>

<div class="survey-send max-w-2xl mx-auto">
    <a href="<?= Url::to(['/hr/hr/employees']) ?>" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-[#642754] mb-6">
        <span class="material-symbols-outlined text-lg">arrow_back</span>
        Back to Directory
    </a>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
        <h1 class="text-xl font-bold text-slate-900 mb-1">Send Direct Survey</h1>
        <p class="text-slate-500 text-sm mb-4">
            Assign a survey to <strong><?= Html::encode($employee->full_name) ?></strong>. Only this employee will receive it.
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
        <h2 class="px-6 py-4 font-semibold text-slate-900 border-b border-slate-200">Select a survey to send</h2>

        <?php if (!$surveys): ?>
            <div class="p-8 text-center text-slate-500">
                <span class="material-symbols-outlined text-4xl mb-2 opacity-50">quiz</span>
                <p>No surveys available. Create one from the Survey Builder first.</p>
                <a href="<?= Url::to(['create', 'employee_id' => $employee->id]) ?>" class="inline-flex items-center gap-2 mt-4 text-[#642754] font-semibold hover:underline">
                    <span class="material-symbols-outlined text-lg">add</span>
                    Create Survey
                </a>
            </div>
        <?php else: ?>
            <?php $form = ActiveForm::begin(['options' => ['class' => 'divide-y divide-slate-100']]); ?>
                <?php foreach ($surveys as $survey): ?>
                    <?php $alreadyAssigned = in_array((int)$survey->id, $assignedIds, true); ?>
                    <label class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50 cursor-pointer <?= $alreadyAssigned ? 'opacity-60' : '' ?>">
                        <input
                            type="radio"
                            name="survey_id"
                            value="<?= (int)$survey->id ?>"
                            <?= $alreadyAssigned ? 'disabled' : '' ?>
                            class="rounded-full border-slate-300 text-[#642754] focus:ring-[#642754]"
                        />
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 truncate"><?= Html::encode($survey->title) ?></p>
                            <p class="text-xs text-slate-500">
                                Created <?= Html::encode(Yii::$app->formatter->asDate($survey->created_at, 'php:M j, Y')) ?>
                                <?php if (!empty($survey->status)): ?>
                                    · <?= Html::encode($survey->status) ?>
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
                        Send Survey to <?= Html::encode($firstName) ?>
                    </button>
                </div>
            <?php ActiveForm::end(); ?>
        <?php endif; ?>
    </div>
</div>
