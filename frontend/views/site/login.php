<?php

/**
 * @var yii\web\View $this
 * @var \common\models\LoginForm $model
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'ERP';

$error = $model->hasErrors()
    ? ($model->getFirstError('password') ?: $model->getFirstError('email') ?: 'Wrong login/password')
    : null;
$message = (string)Yii::$app->session->getFlash('success', '');

?>
<div class="culturehr-login-page" data-portal="hr">
    <div class="culturehr-login-card">
        <header class="culturehr-login-header">
            <p class="culturehr-login-brand">ERP</p>
            <h1 class="culturehr-login-heading">Welcome Back</h1>
            <p class="culturehr-login-tagline">Sign in to the ERP portal</p>
        </header>

        <?= Html::beginForm(['site/login'], 'post', [
            'class' => 'culturehr-login-form',
            'id' => 'login-form',
            'role' => 'form',
            'autocomplete' => 'culturehr-hr-login',
        ]) ?>
            <div class="culturehr-login-field">
                <label for="culturehr_login">Work Email</label>
                <?= Html::activeTextInput($model, 'email', [
                    'id' => 'culturehr_login',
                    'autocomplete' => 'culturehr-hr-login username',
                    'autocapitalize' => 'off',
                    'required' => true,
                    'placeholder' => 'you@school.edu',
                ]) ?>
            </div>

            <div class="culturehr-login-field">
                <label for="culturehr_password">Password</label>
                <?= Html::activePasswordInput($model, 'password', [
                    'id' => 'culturehr_password',
                    'autocomplete' => 'culturehr-hr-login current-password',
                    'required' => true,
                    'maxlength' => 4096,
                    'placeholder' => '••••••••',
                ]) ?>
            </div>

            <div class="culturehr-login-options">
                <a class="culturehr-login-forgot" href="<?= Html::encode(Url::to(['/site/request-password-reset'])) ?>">Forgot password?</a>
            </div>

            <?php if ($error): ?>
                <p class="culturehr-login-alert culturehr-login-alert--error"><?= Html::encode($error) ?></p>
            <?php endif; ?>
            <?php if ($message !== ''): ?>
                <p class="culturehr-login-alert culturehr-login-alert--success"><?= Html::encode($message) ?></p>
            <?php endif; ?>

            <button type="submit" class="culturehr-login-submit">Log In</button>
        <?= Html::endForm() ?>

        <a class="culturehr-login-switch-btn" href="<?= Html::encode(Url::to(['/lms/portal/login', 'switch' => 1])) ?>" role="link">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z" fill="currentColor"/>
                <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z" fill="currentColor" opacity="0.85"/>
            </svg>
            <span>LMS Portal Login</span>
        </a>
    </div>
</div>
