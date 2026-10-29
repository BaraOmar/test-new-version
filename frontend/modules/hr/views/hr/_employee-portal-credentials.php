<?php

/**
 * @var yii\web\View $this
 * @var string $mode view|create|edit
 * @var string $loginEmail
 * @var string $loginPassword
 * @var string $passwordInputId
 * @var string|null $passwordFieldName
 * @var string|null $emailHint
 * @var string|null $passwordHint
 * @var bool $passwordEditable
 */

use yii\helpers\Html;

$mode = $mode ?? 'view';
$loginEmail = trim((string)($loginEmail ?? ''));
$loginPassword = (string)($loginPassword ?? '');
$passwordInputId = (string)($passwordInputId ?? 'employee-portal-password');
$passwordFieldName = $passwordFieldName ?? null;
$emailHint = $emailHint ?? null;
$passwordHint = $passwordHint ?? null;
$passwordEditable = (bool)($passwordEditable ?? false);
$isView = $mode === 'view';

?>
<section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
        <span class="material-symbols-outlined text-[#1e3a8a]">lock</span>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Portal Credentials</h2>
    </div>
    <div class="p-6 lg:p-8 space-y-4">
        <?php if (!$isView): ?>
            <p class="text-sm text-slate-500 m-0">
                <?php if ($mode === 'edit'): ?>
                    These credentials are used to sign in to the employee portal.
                <?php else: ?>
                    Portal email and password are generated automatically from the employee name, the same way parent and student portal accounts are created.
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Login Email</p>
                <?php if ($isView): ?>
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 break-all"><?= Html::encode($loginEmail !== '' ? $loginEmail : '—') ?></p>
                <?php else: ?>
                    <input type="text" readonly
                           value="<?= Html::encode($loginEmail) ?>"
                           class="w-full h-11 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-sm text-slate-700 dark:text-slate-300">
                    <?php if ($emailHint !== null && $emailHint !== ''): ?>
                        <p class="text-xs text-slate-500 mt-2"><?= Html::encode($emailHint) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Login Password</p>
                <?php if ($isView && trim($loginPassword) === ''): ?>
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">—</p>
                    <p class="text-xs text-slate-500 mt-1">Changed by the employee or not available. Use Edit Employee to set a new portal password.</p>
                <?php else: ?>
                    <div class="flex items-center gap-2 w-full max-w-md">
                        <?php if ($isView): ?>
                            <input type="password" id="<?= Html::encode($passwordInputId) ?>" readonly
                                   value="<?= Html::encode($loginPassword) ?>"
                                   class="flex-1 min-w-0 h-11 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-mono">
                        <?php else: ?>
                            <input type="password"
                                   id="<?= Html::encode($passwordInputId) ?>"
                                   name="<?= Html::encode((string)$passwordFieldName) ?>"
                                   value="<?= Html::encode($loginPassword) ?>"
                                   <?= $passwordEditable ? '' : 'readonly' ?>
                                   autocomplete="new-password"
                                   class="flex-1 min-w-0 h-11 px-3 rounded-lg border border-slate-200 dark:border-slate-700 text-sm font-mono <?= $passwordEditable ? 'bg-white dark:bg-slate-800' : 'bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300' ?>">
                        <?php endif; ?>
                        <button type="button"
                                data-password-toggle="<?= Html::encode($passwordInputId) ?>"
                                class="shrink-0 inline-flex items-center justify-center p-1 text-slate-500 hover:text-[#1e3a8a] bg-transparent border-0 cursor-pointer"
                                aria-label="Show password">
                            <span class="material-symbols-outlined text-2xl leading-none">visibility</span>
                        </button>
                    </div>
                    <?php if ($passwordHint !== null && $passwordHint !== ''): ?>
                        <p class="text-xs text-slate-500 mt-2"><?= Html::encode($passwordHint) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
