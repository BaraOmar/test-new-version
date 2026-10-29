<?php

/**
 * @var yii\web\View $this
 * @var frontend\modules\hr\models\UpdateHrUserForm $model
 * @var common\models\User $hrUser
 * @var array<string, array{title:string,icon:string,items:array<string,string>}> $permissionGroups
 * @var array<string, string[]> $permissionPresets
 * @var string $search
 * @var string $moduleFilter
 * @var string $userName
 * @var bool $hasJoiningDate
 * @var bool $hasJobTitle
 * @var bool $hasPhone
 * @var bool $hasEmploymentType
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Update HR User | CultureHR';

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');
$backUrl = Url::to(['manage-hr', 'q' => $search, 'module' => $moduleFilter]);
$isActive = $hrUser->hasAttribute('status') && (int)$hrUser->status === \common\models\User::STATUS_ACTIVE;

?>

<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => 'manage_hr']) ?>

    <main class="flex-1 flex flex-col overflow-hidden min-w-0">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Update HR User', 'userName' => $userName]) ?>

        <div class="flex-1 overflow-y-auto bg-slate-100">
            <div class="w-full min-w-0 max-w-full px-6 lg:px-10 xl:px-12 py-8 space-y-6">
                <?php if ($successMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-medium">
                        <?= Html::encode($successMessage) ?>
                    </div>
                <?php endif; ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium">
                        <?= Html::encode($errorMessage) ?>
                    </div>
                <?php endif; ?>

                <div class="flex flex-wrap items-center gap-4">
                    <a href="<?= Html::encode($backUrl) ?>"
                       class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-blue-600 transition-colors">
                        <span class="material-symbols-outlined text-xl">arrow_back</span>
                        Back to HR Accounts
                    </a>
                </div>

                <header>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Update HR User</h1>
                    <p class="text-slate-500 mt-1">Modify account details and module access for this HR administrator.</p>
                </header>

                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex items-start gap-4">
                        <div class="size-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-2xl">manage_accounts</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-xl font-bold text-slate-900"><?= Html::encode($model->full_name !== '' ? $model->full_name : 'HR User') ?></h2>
                            <p class="text-sm text-slate-500 mt-0.5"><?= Html::encode($model->email) ?></p>
                            <div class="mt-2">
                                <?php if ($isActive): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="size-2 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="size-2 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 lg:p-8">
                        <?= Html::beginForm(['update-hr', 'id' => (int)$model->user_id, 'q' => $search, 'module' => $moduleFilter], 'post', [
                            'class' => 'space-y-6',
                            'id' => 'update-hr-form',
                        ]) ?>
                        <?= Html::activeHiddenInput($model, 'user_id') ?>
                        <?= Html::hiddenInput('return_q', $search) ?>
                        <?= Html::hiddenInput('return_module', $moduleFilter) ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 xl:gap-6">
                            <div>
                                <div class="min-h-[2.75rem] mb-2 flex flex-col justify-end">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Full Name</label>
                                </div>
                                <?= Html::activeTextInput($model, 'full_name', [
                                    'class' => 'w-full h-11 px-4 rounded-lg border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500',
                                    'autocomplete' => 'name',
                                ]) ?>
                                <?= Html::error($model, 'full_name', ['class' => 'text-red-600 text-xs mt-1']) ?>
                            </div>
                            <div>
                                <div class="min-h-[2.75rem] mb-2 flex flex-col justify-end">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Dashboard Email</label>
                                </div>
                                <?= Html::activeTextInput($model, 'email', [
                                    'type' => 'email',
                                    'class' => 'w-full h-11 px-4 rounded-lg border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500',
                                    'autocomplete' => 'off',
                                ]) ?>
                                <?= Html::error($model, 'email', ['class' => 'text-red-600 text-xs mt-1']) ?>
                            </div>
                            <div>
                                <div class="min-h-[2.75rem] mb-2">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">New Password</label>
                                    <p class="text-xs text-slate-400 mt-0.5 leading-snug">Leave blank to keep the current password.</p>
                                </div>
                                <div class="relative">
                                    <?= Html::activePasswordInput($model, 'password', [
                                        'class' => 'w-full h-11 px-4 pr-11 rounded-lg border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 password-field',
                                        'autocomplete' => 'new-password',
                                    ]) ?>
                                    <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" tabindex="-1" aria-label="Show password">
                                        <span class="material-symbols-outlined text-xl">visibility</span>
                                    </button>
                                </div>
                                <?= Html::error($model, 'password', ['class' => 'text-red-600 text-xs mt-1']) ?>
                            </div>
                            <?php $showPhone = $hasPhone || ($model->is_external_account && \common\models\User::hasColumn('phone')); ?>
                            <?php if ($showPhone): ?>
                            <div>
                                <div class="min-h-[2.75rem] mb-2 flex flex-col justify-end">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Phone Number</label>
                                </div>
                                <?= Html::activeTextInput($model, 'phone', [
                                    'class' => 'w-full h-11 px-4 rounded-lg border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500',
                                    'autocomplete' => 'tel',
                                    'data-digits-only' => true,
                                    'inputmode' => 'numeric',
                                    'pattern' => '[0-9]*',
                                ]) ?>
                                <?= Html::error($model, 'phone', ['class' => 'text-red-600 text-xs mt-1']) ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!$model->is_external_account && $hasJobTitle): ?>
                            <div>
                                <div class="min-h-[2.75rem] mb-2 flex flex-col justify-end">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Job Title</label>
                                </div>
                                <?= Html::activeTextInput($model, 'job_title', [
                                    'class' => 'w-full h-11 px-4 rounded-lg border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500',
                                    'autocomplete' => 'organization-title',
                                ]) ?>
                                <?= Html::error($model, 'job_title', ['class' => 'text-red-600 text-xs mt-1']) ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!$model->is_external_account && $hasJoiningDate): ?>
                            <div>
                                <div class="min-h-[2.75rem] mb-2 flex flex-col justify-end">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Joining Date</label>
                                </div>
                                <?= Html::activeInput('date', $model, 'joining_date', [
                                    'max' => date('Y-m-d'),
                                    'class' => 'w-full h-11 px-4 rounded-lg border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500',
                                ]) ?>
                                <?= Html::error($model, 'joining_date', ['class' => 'text-red-600 text-xs mt-1']) ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!$model->is_external_account && $hasEmploymentType): ?>
                            <div>
                                <div class="min-h-[2.75rem] mb-2 flex flex-col justify-end">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Employment Type</label>
                                </div>
                                <?= Html::activeDropDownList($model, 'employment_type', [
                                    '' => 'Select type',
                                    'full-time' => 'Full-time',
                                    'part-time' => 'Part-time',
                                    'contract' => 'Contract',
                                    'intern' => 'Intern',
                                ], [
                                    'class' => 'w-full h-11 px-4 rounded-lg border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500',
                                ]) ?>
                                <?= Html::error($model, 'employment_type', ['class' => 'text-red-600 text-xs mt-1']) ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?= $this->render('_hr-permission-fields', [
                            'formModel' => $model,
                            'selectedPermissions' => $model->permissions,
                            'permissionGroups' => $permissionGroups,
                            'permissionPresets' => $permissionPresets,
                            'checkboxClass' => 'permission-checkbox',
                        ]) ?>

                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                            <button type="submit"
                                    class="flex-1 h-12 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow-md transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-lg">save</span>
                                Save Changes
                            </button>
                            <a href="<?= Html::encode($backUrl) ?>"
                               class="h-12 px-8 rounded-xl border border-slate-200 text-slate-700 text-sm font-bold flex items-center justify-center hover:bg-slate-50 transition-colors">
                                Cancel
                            </a>
                        </div>

                        <?= Html::endForm() ?>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>

<?php
$presetsJson = json_encode($permissionPresets);
$this->registerJs(<<<JS
(function () {
    const presets = {$presetsJson};

    function setCheckboxes(selector, values) {
        document.querySelectorAll(selector).forEach(function (box) {
            box.checked = values !== null && values.indexOf(box.value) !== -1;
        });
    }

    function bindPermissionControls(formId, checkboxClass) {
        const form = document.getElementById(formId);
        if (!form) return;
        const selector = '.' + checkboxClass;

        form.querySelectorAll('[data-preset]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const key = btn.getAttribute('data-preset');
                if (key === 'clear') {
                    setCheckboxes(selector, []);
                    return;
                }
                setCheckboxes(selector, presets[key] || []);
            });
        });

        const selectAll = form.querySelector('[data-select-all]');
        const clearAll = form.querySelector('[data-clear-all]');
        if (selectAll) {
            selectAll.addEventListener('click', function () {
                document.querySelectorAll(selector).forEach(function (box) { box.checked = true; });
            });
        }
        if (clearAll) {
            clearAll.addEventListener('click', function () {
                setCheckboxes(selector, []);
            });
        }
    }

    bindPermissionControls('update-hr-form', 'permission-checkbox');

    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = btn.parentElement.querySelector('.password-field');
            if (!input) return;
            const hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            btn.querySelector('.material-symbols-outlined').textContent = hidden ? 'visibility_off' : 'visibility';
        });
    });
})();
JS);
?>
