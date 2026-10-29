<?php

/**
 * @var yii\web\View $this
 * @var common\modules\recruitment\models\Applicant $model
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Applicant Login | CultureHR Careers';
$this->params['applicantAuthHeader'] = true;

$success = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = '';
if ($model->hasErrors()) {
    $errorMessage = (string)($model->getFirstError('password') ?: $model->getFirstError('email') ?: 'Please check your credentials.');
}

$inputClass = 'w-full h-12 pl-11 pr-4 text-[15px] text-slate-900 placeholder:text-slate-400 bg-white border border-slate-200 rounded-xl shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#642754]/25 focus:border-[#642754] hover:border-slate-300';

?>

<div class="applicant-auth-card mx-auto w-full">
    <article class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-200/90 overflow-hidden">
        <div class="h-1 bg-gradient-to-r from-[#642754] via-[#7a3568] to-[#eb652c]" aria-hidden="true"></div>

        <div class="px-7 py-8 sm:px-10 sm:py-10">
            <header class="text-center mb-8">
                <div class="size-14 rounded-2xl bg-[#642754] text-white flex items-center justify-center font-bold text-2xl mx-auto mb-4 shadow-lg shadow-[#642754]/25 ring-4 ring-[#642754]/10">
                    C
                </div>
                <h1 class="text-2xl sm:text-[1.75rem] font-extrabold text-slate-900 tracking-tight leading-tight">
                    Applicant Login
                </h1>
                <p class="text-slate-500 text-sm sm:text-[15px] mt-2 max-w-[18rem] mx-auto leading-relaxed">
                    Sign in to browse open roles and track your applications
                </p>
            </header>

            <?php if ($success !== ''): ?>
                <div class="mb-6 flex gap-3 items-start p-4 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 text-sm" role="status">
                    <span class="material-symbols-outlined text-xl shrink-0 text-emerald-600">check_circle</span>
                    <p class="font-medium leading-snug pt-0.5"><?= Html::encode($success) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>
                <div class="mb-6 flex gap-3 items-start p-4 rounded-xl bg-red-50 border border-red-200/80 text-red-800 text-sm" role="alert">
                    <span class="material-symbols-outlined text-xl shrink-0 text-red-500">error</span>
                    <p class="font-medium leading-snug pt-0.5"><?= Html::encode($errorMessage) ?></p>
                </div>
            <?php endif; ?>

            <?= Html::beginForm(['login'], 'post', [
                'class' => 'space-y-5',
                'id' => 'applicant-login-form',
                'novalidate' => true,
            ]) ?>
                <div>
                    <label for="applicant-login-email" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Email address
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[22px] pointer-events-none" aria-hidden="true">mail</span>
                        <?= Html::activeInput('email', $model, 'email', [
                            'id' => 'applicant-login-email',
                            'required' => true,
                            'autofocus' => true,
                            'autocomplete' => 'email',
                            'inputmode' => 'email',
                            'class' => $inputClass,
                            'placeholder' => 'you@example.com',
                        ]) ?>
                    </div>
                    <?= Html::error($model, 'email', ['class' => 'text-red-600 text-xs mt-1.5 font-medium']) ?>
                </div>

                <div>
                    <label for="applicant-login-password" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Password
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[22px] pointer-events-none" aria-hidden="true">lock</span>
                        <?= Html::activePasswordInput($model, 'password', [
                            'id' => 'applicant-login-password',
                            'required' => true,
                            'autocomplete' => 'current-password',
                            'class' => $inputClass . ' pr-12',
                            'placeholder' => 'Enter your password',
                        ]) ?>
                        <button type="button"
                                id="applicant-toggle-password"
                                class="absolute right-2 top-1/2 -translate-y-1/2 size-9 flex items-center justify-center rounded-lg text-slate-400 hover:text-[#642754] hover:bg-slate-100 transition-colors"
                                aria-label="Show password"
                                tabindex="0">
                            <span class="material-symbols-outlined text-[22px]" id="applicant-toggle-password-icon">visibility</span>
                        </button>
                    </div>
                    <?= Html::error($model, 'password', ['class' => 'text-red-600 text-xs mt-1.5 font-medium']) ?>
                </div>

                <button type="submit"
                        class="w-full h-12 mt-1 bg-[#642754] hover:bg-[#532244] active:bg-[#4a1f3c] text-white text-[15px] font-bold rounded-xl shadow-md shadow-[#642754]/20 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#642754]/40 focus:ring-offset-2 flex items-center justify-center gap-2">
                    <span>Sign in</span>
                    <span class="material-symbols-outlined text-xl">arrow_forward</span>
                </button>
            <?= Html::endForm() ?>

            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-slate-200"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="bg-white px-3 text-xs font-medium text-slate-400 uppercase tracking-wider">or</span>
                </div>
            </div>

            <p class="text-center text-sm text-slate-600">
                New to CultureHR Careers?
                <a href="<?= Url::to(['register']) ?>"
                   class="inline-flex items-center gap-0.5 text-[#eb652c] font-bold no-underline hover:text-[#d85a24] hover:underline underline-offset-2 transition-colors">
                    Create an account
                    <span class="material-symbols-outlined text-base">chevron_right</span>
                </a>
            </p>
        </div>
    </article>

    <p class="text-center text-xs text-slate-400 mt-6 px-4 leading-relaxed">
        Secure applicant access · Browse jobs · Track application status
    </p>
</div>

<?php
$this->registerJs(<<<'JS'
(function () {
    const input = document.getElementById('applicant-login-password');
    const btn = document.getElementById('applicant-toggle-password');
    const icon = document.getElementById('applicant-toggle-password-icon');
    if (!input || !btn || !icon) {
        return;
    }
    btn.addEventListener('click', function () {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.textContent = show ? 'visibility_off' : 'visibility';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
})();
JS);
?>
