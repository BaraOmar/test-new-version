<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\LoginForm $model */

$this->title = 'Sign in';
?>

<div class="auth-login">
    <h1 class="h3 text-center mb-2">Welcome Back</h1>
    <p class="text-muted text-center mb-4">Sign in to CultureHR</p>

    <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
        <?= $form->field($model, 'email')->textInput([
            'autofocus' => true,
            'autocomplete' => 'email',
            'placeholder' => 'admin@test.com',
        ]) ?>

        <?= $form->field($model, 'password')->passwordInput([
            'autocomplete' => 'current-password',
            'placeholder' => 'Password',
        ]) ?>

        <?= $form->field($model, 'rememberMe')->checkbox() ?>

        <div class="d-grid mt-4">
            <?= Html::submitButton('Log In', ['class' => 'btn btn-primary btn-lg']) ?>
        </div>
    <?php ActiveForm::end(); ?>
</div>
