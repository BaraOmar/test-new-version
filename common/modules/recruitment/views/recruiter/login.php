<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Recruiter Login';
?>
<div class="recruitment-auth mx-auto" style="max-width: 480px;">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">Sign in with recruiter credentials to manage jobs and applicants.</p>

    <?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'email')->textInput(['label' => 'Email or Username', 'autofocus' => true]) ?>
    <?= $form->field($model, 'password')->passwordInput() ?>
    <?= Html::submitButton('Recruiter Sign In', ['class' => 'btn btn-primary']) ?>
    <?php ActiveForm::end(); ?>
</div>
