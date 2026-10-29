<?php

/**
 * @var yii\web\View $this
 * @var common\models\User $user
 * @var string $error
 * @var string $userName
 * @var string $userRole
 * @var string $currentPage
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Change Password | LMS';

$this->registerJs(<<<'JS'
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.getAttribute('data-password-toggle'));
        if (!input) return;
        const icon = button.querySelector('.material-symbols-outlined');
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        if (icon) icon.textContent = showing ? 'visibility' : 'visibility_off';
        button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
});
JS);

?>
<?= $this->render('../layouts/_page-start') ?>
    <div class="w-full max-w-xl mx-auto">
        <header class="text-center mb-8">
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Change Password</h2>
            <p class="text-slate-500 mt-2 text-lg">Update the password you use to sign in to the parent portal.</p>
        </header>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
            <?php if ($error !== ''): ?>
                <div class="mb-4 p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium">
                    <?= Html::encode($error) ?>
                </div>
            <?php endif; ?>

            <?= Html::beginForm(['change-password'], 'post', ['class' => 'space-y-4']) ?>
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Current Password</label>
                    <div class="flex items-center gap-2 w-full">
                        <input type="password" id="current-password" name="current_password" required
                               class="flex-1 min-w-0 h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                        <button type="button" data-password-toggle="current-password"
                                class="shrink-0 inline-flex items-center justify-center p-1 text-slate-500 hover:text-[#1e3a8a] bg-transparent border-0 cursor-pointer"
                                aria-label="Show password">
                            <span class="material-symbols-outlined text-2xl leading-none">visibility</span>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">New Password</label>
                    <div class="flex items-center gap-2 w-full">
                        <input type="password" id="new-password" name="new_password" required minlength="8"
                               class="flex-1 min-w-0 h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                        <button type="button" data-password-toggle="new-password"
                                class="shrink-0 inline-flex items-center justify-center p-1 text-slate-500 hover:text-[#1e3a8a] bg-transparent border-0 cursor-pointer"
                                aria-label="Show password">
                            <span class="material-symbols-outlined text-2xl leading-none">visibility</span>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Confirm New Password</label>
                    <div class="flex items-center gap-2 w-full">
                        <input type="password" id="confirm-password" name="confirm_password" required minlength="8"
                               class="flex-1 min-w-0 h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                        <button type="button" data-password-toggle="confirm-password"
                                class="shrink-0 inline-flex items-center justify-center p-1 text-slate-500 hover:text-[#1e3a8a] bg-transparent border-0 cursor-pointer"
                                aria-label="Show password">
                            <span class="material-symbols-outlined text-2xl leading-none">visibility</span>
                        </button>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <a href="<?= Html::encode(Url::to(['parent'])) ?>"
                       class="inline-flex items-center h-11 px-5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-bold no-underline">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center h-11 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold border-0 cursor-pointer">
                        Save Password
                    </button>
                </div>
            <?= Html::endForm() ?>
        </div>
    </div>
<?= $this->render('../layouts/_page-end') ?>
