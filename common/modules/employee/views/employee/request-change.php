<?php

use common\modules\employee\models\EmployeeChangeRequest;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\modules\employee\models\Employee $employee */
/** @var EmployeeChangeRequest $model */

$this->title = 'Change Request';
?>

<div class="employee-request-change py-4">
    <h1 class="mb-1"><?= Html::encode($this->title) ?></h1>
    <p class="text-muted mb-4">Request HR approval for updates to your employee profile.</p>

    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>
            <?= $form->field($model, 'employee_id')->hiddenInput()->label(false) ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Your Name</label>
                    <input class="form-control" value="<?= Html::encode($employee->full_name) ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Your Email</label>
                    <input class="form-control" value="<?= Html::encode($employee->email) ?>" disabled>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'urgency')->dropDownList([
                        EmployeeChangeRequest::URGENCY_NORMAL => EmployeeChangeRequest::URGENCY_NORMAL,
                        EmployeeChangeRequest::URGENCY_URGENT => EmployeeChangeRequest::URGENCY_URGENT,
                    ]) ?>
                </div>
                <?php if ($model->hasAttribute('title')): ?>
                <div class="col-12"><?= $form->field($model, 'title')->textInput(['maxlength' => 255, 'placeholder' => 'e.g. Update my department']) ?></div>
                <?php endif; ?>
                <div class="col-12"><?= $form->field($model, 'old_value')->textarea(['rows' => 4])->label('Current Text / Content') ?></div>
                <div class="col-12"><?= $form->field($model, 'new_value')->textarea(['rows' => 4])->label('New Text / Content') ?></div>
            </div>
            <div class="mt-4">
                <?= Html::submitButton('Send Request', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
