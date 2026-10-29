<?php

/**
 * @var yii\web\View $this
 * @var common\modules\evaluation\models\Evaluation $model
 */

use common\modules\evaluation\services\EvaluationResultsService;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Update Schedule';
$endDateId = 'evaluationScheduleEndDate';

[$statusLabel, $statusClass] = \common\modules\evaluation\controllers\EvaluationController::statusInfo($model->status ?? 'active');

?>

<div class="max-w-xl mx-auto">
    <div class="mb-6">
        <a href="<?= Html::encode(Url::to(['index'])) ?>" class="text-sm text-slate-600 hover:text-[#642754] no-underline inline-flex items-center gap-1">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            Back to evaluations
        </a>
        <h1 class="text-2xl font-bold text-slate-900 mt-3">Update schedule</h1>
        <p class="text-sm text-slate-500 mt-1">
            Change when <strong><?= Html::encode($model->title) ?></strong> opens and closes. Questions and audience are not changed.
        </p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center gap-2 mb-6 flex-wrap">
            <span class="<?= Html::encode($statusClass) ?>"><?= Html::encode($statusLabel) ?></span>
            <?php if ($model->hasAttribute('start_at') && $model->start_at): ?>
                <span class="text-xs text-slate-500">Opens: <?= Html::encode(EvaluationResultsService::formatDate($model->start_at)) ?></span>
            <?php endif; ?>
            <?php if ($model->hasAttribute('end_at') && $model->end_at): ?>
                <span class="text-xs text-slate-500">Closes: <?= Html::encode(EvaluationResultsService::formatDate($model->end_at)) ?></span>
            <?php endif; ?>
        </div>

        <?php $form = ActiveForm::begin([
            'id' => 'evaluation-schedule-form',
            'options' => ['class' => 'space-y-6'],
        ]); ?>

        <?= $this->render('_schedule-fields', ['model' => $model, 'endDateId' => $endDateId]) ?>

        <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
            <?= Html::submitButton('Save schedule', ['class' => 'inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold transition-all']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'text-sm font-medium text-slate-600 hover:text-slate-900 no-underline px-3 py-2']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?= $this->render('_schedule-scripts', ['endDateId' => $endDateId]) ?>
