<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\modules\employee\models\Employee $employee */
/** @var array $departments */
/** @var array $managers */
/** @var bool $hasStatus */
/** @var bool $hasIsManager */

$this->title = $employee->isNewRecord ? 'Add Employee' : 'Update Employee';
unset($managers[$employee->id]);
?>

<div class="employee-update py-4">
    <h1 class="mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="row g-3">
                <div class="col-md-6"><?= $form->field($employee, 'full_name')->textInput(['maxlength' => true]) ?></div>
                <div class="col-md-6"><?= $form->field($employee, 'email')->input('email', ['maxlength' => true]) ?></div>
                <div class="col-md-6"><?= $form->field($employee, 'plainPassword')->passwordInput()->hint($employee->isNewRecord ? 'Required for new employees.' : 'Leave blank to keep the current password.') ?></div>
                <div class="col-md-6"><?= $form->field($employee, 'phone')->textInput(['maxlength' => true]) ?></div>
                <div class="col-md-6"><?= $form->field($employee, 'marital_status')->dropDownList([
                    'single' => 'Single',
                    'married' => 'Married',
                    'divorced' => 'Divorced',
                    'widowed' => 'Widowed',
                ], ['prompt' => 'Select marital status']) ?></div>
                <div class="col-md-6"><?= $form->field($employee, 'job_title')->textInput(['maxlength' => true]) ?></div>
                <div class="col-md-4"><?= $form->field($employee, 'position_level')->dropDownList([
                    'l1' => 'Entry (L1)',
                    'l2' => 'Intermediate (L2)',
                    'l3' => 'Senior (L3)',
                    'l4' => 'Staff (L4)',
                    'l5' => 'Director (L5)',
                ], ['prompt' => 'Select level']) ?></div>
                <div class="col-md-4"><?= $form->field($employee, 'joining_date')->input('date', ['max' => date('Y-m-d')]) ?></div>
                <div class="col-md-4"><?= $form->field($employee, 'employment_type')->dropDownList([
                    'full-time' => 'Full-time',
                    'part-time' => 'Part-time',
                    'contract' => 'Contract',
                    'intern' => 'Intern',
                ], ['prompt' => 'Select type']) ?></div>
                <div class="col-md-6"><?= $form->field($employee, 'department_id')->dropDownList($departments, ['prompt' => 'Select department']) ?></div>
                <div class="col-md-6"><?= $form->field($employee, 'manager_id')->dropDownList($managers, ['prompt' => 'No manager']) ?></div>
                <div class="col-md-6"><?= $form->field($employee, 'location')->dropDownList(['Remote' => 'Remote', 'Hybrid' => 'Hybrid', 'Onsite' => 'On-site'], ['prompt' => 'Select location']) ?></div>
                <?php if ($hasStatus): ?>
                    <div class="col-md-6"><?= $form->field($employee, 'status')->dropDownList(['Active' => 'Active', 'Onboarding' => 'Onboarding', 'Leave' => 'Leave', 'Terminated' => 'Terminated'], ['prompt' => 'Select status']) ?></div>
                <?php endif; ?>
                <div class="col-md-6"><?= $form->field($employee, 'is_hr_admin')->checkbox() ?></div>
                <?php if ($hasIsManager): ?>
                    <div class="col-md-6"><?= $form->field($employee, 'is_manager')->checkbox() ?></div>
                <?php endif; ?>
            </div>

            <div class="mt-4 d-flex gap-2">
                <?= Html::submitButton('Save Employee', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
