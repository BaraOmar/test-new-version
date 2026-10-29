<?php

/**
 * @var yii\web\View $this
 * @var common\modules\recruitment\models\Applicant $model
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Applicant Registration | CultureHR Careers';
$this->params['applicantAuthHeader'] = true;

$inputClass = 'w-full mt-1 px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#642754]/30 focus:border-[#642754] outline-none';

?>

<div class="applicant-auth-card mx-auto w-full">
    <h1 class="text-2xl sm:text-3xl font-extrabold text-center mb-6 text-slate-900 tracking-tight">Create Your Account</h1>

    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-200/90 p-7 sm:p-8">
        <?php if ($model->hasErrors()): ?>
            <div class="mb-5 flex gap-3 items-start p-4 rounded-xl bg-red-50 border border-red-200/80 text-red-800 text-sm" role="alert">
                <span class="material-symbols-outlined text-xl shrink-0 text-red-500">error</span>
                <p class="font-medium leading-snug pt-0.5"><?= Html::encode(implode(' ', $model->getFirstErrors())) ?></p>
            </div>
        <?php endif; ?>

        <?= Html::beginForm(['register'], 'post', ['class' => 'space-y-5', 'id' => 'applicant-register-form']) ?>
            <div>
                <label class="text-sm font-bold text-slate-700" for="applicant-register-name">Full Name</label>
                <?= Html::activeInput('text', $model, 'full_name', [
                    'id' => 'applicant-register-name',
                    'required' => true,
                    'autocomplete' => 'name',
                    'class' => $inputClass,
                    'placeholder' => 'Enter your full name',
                ]) ?>
                <?= Html::error($model, 'full_name', ['class' => 'text-red-600 text-xs mt-1.5 font-medium']) ?>
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700" for="applicant-register-email">Email Address</label>
                <?= Html::activeInput('email', $model, 'email', [
                    'id' => 'applicant-register-email',
                    'required' => true,
                    'autocomplete' => 'email',
                    'inputmode' => 'email',
                    'class' => $inputClass,
                    'placeholder' => 'example@university.edu',
                ]) ?>
                <?= Html::error($model, 'email', ['class' => 'text-red-600 text-xs mt-1.5 font-medium']) ?>
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700" for="applicant-register-phone">Phone Number</label>
                <?= Html::activeInput('tel', $model, 'phone', [
                    'id' => 'applicant-register-phone',
                    'required' => true,
                    'autocomplete' => 'tel',
                    'inputmode' => 'tel',
                    'class' => $inputClass,
                    'placeholder' => 'e.g. 0791234567',
                ]) ?>
                <?= Html::error($model, 'phone', ['class' => 'text-red-600 text-xs mt-1.5 font-medium']) ?>
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700" for="applicant-register-password">Password</label>
                <?= Html::activePasswordInput($model, 'password', [
                    'id' => 'applicant-register-password',
                    'required' => true,
                    'autocomplete' => 'new-password',
                    'class' => $inputClass,
                    'placeholder' => 'Min. 8 characters',
                ]) ?>
                <?= Html::error($model, 'password', ['class' => 'text-red-600 text-xs mt-1.5 font-medium']) ?>
            </div>
            <div class="flex items-start gap-2">
                <input type="checkbox" required class="mt-1 rounded border-slate-300 text-[#642754] focus:ring-[#642754]" id="terms">
                <label for="terms" class="text-xs text-slate-600">I agree to the Terms &amp; Privacy Policy</label>
            </div>
            <button type="submit" class="w-full h-12 bg-[#642754] hover:bg-[#58264a] text-white font-bold rounded-xl transition-colors shadow-md shadow-[#642754]/20">
                Create Account
            </button>
        <?= Html::endForm() ?>

        <p class="text-center text-sm mt-6 text-slate-600">
            Already have an account?
            <a href="<?= Url::to(['login']) ?>" class="text-[#642754] font-bold no-underline hover:underline">Log in</a>
        </p>
    </div>
</div>
