<?php

/**
 * @var yii\web\View $this
 * @var common\models\LoginForm $model
 * @var string|null $error
 * @var string|null $message
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'LMS';

$error = $error ?? ($model->hasErrors()
    ? ($model->getFirstError('password') ?: $model->getFirstError('email') ?: 'Wrong login/password')
    : null);
$message = $message ?? (string)Yii::$app->session->getFlash('success', '');

?>
<div class="culturehr-login-page" data-portal="lms">
    <div class="culturehr-login-card">
        <header class="culturehr-login-header">
            <p class="culturehr-login-brand">LMS</p>
            <h1 class="culturehr-login-heading">Welcome Back</h1>
            <p class="culturehr-login-tagline">Sign in to your learning portal</p>
        </header>

        <?= Html::beginForm(['login'], 'post', [
            'class' => 'culturehr-login-form',
            'role' => 'form',
            'autocomplete' => 'culturehr-lms-login',
        ]) ?>
            <div class="culturehr-login-field">
                <label for="culturehr_login">Email</label>
                <?= Html::activeTextInput($model, 'email', [
                    'id' => 'culturehr_login',
                    'autocomplete' => 'culturehr-lms-login username',
                    'autocapitalize' => 'off',
                    'required' => true,
                    'placeholder' => 'you@school.edu',
                    'value' => $model->email,
                ]) ?>
            </div>

            <div class="culturehr-login-field">
                <label for="culturehr_password">Password</label>
                <?= Html::activePasswordInput($model, 'password', [
                    'id' => 'culturehr_password',
                    'autocomplete' => 'culturehr-lms-login current-password',
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

            <button type="submit" class="culturehr-login-submit">Sign In to LMS</button>
        <?= Html::endForm() ?>

        <a class="culturehr-login-switch-btn" href="<?= Html::encode(Url::to(['/site/login', 'switch' => 1])) ?>" role="link">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z" fill="currentColor"/>
            </svg>
            <span>ERP Admin Login</span>
        </a>
    </div>
</div>
