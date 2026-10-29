<?php

/**
 * @var yii\web\View $this
 * @var frontend\modules\hr\models\GrantEmployeePermissionsForm $model
 * @var array<int, array{id:int,label:string,search:string}> $employeeOptions
 * @var array<int, array<string, mixed>> $hrUsers
 * @var int $activeCount
 * @var array<string, array{title:string,icon:string,items:array<string,string>}> $permissionGroups
 * @var array<string, string[]> $permissionPresets
 * @var array<string, string> $moduleFilterOptions
 * @var string $search
 * @var string $moduleFilter
 * @var string $userName
 * @var bool $hasJoiningDate
 * @var bool $hasJobTitle
 * @var bool $hasPhone
 * @var bool $hasUserPhone
 * @var bool $hasEmploymentType
 * @var bool $hasPosition
 */

use frontend\modules\hr\models\GrantEmployeePermissionsForm;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'HR Management | CultureHR';

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');

$hasActiveFilters = $search !== '' || $moduleFilter !== '';
$moduleFilterLabels = ['none' => 'No permissions'] + $moduleFilterOptions;
$activeModuleLabel = $moduleFilter !== '' ? ($moduleFilterLabels[$moduleFilter] ?? $moduleFilter) : '';
$clearFiltersUrl = Url::to(['manage-hr']);

$displayOrDash = static function (?string $value): string {
    $text = trim((string)$value);

    return $text !== '' ? $text : '—';
};
$displayEmploymentType = static function (?string $value) use ($displayOrDash): string {
    $text = trim((string)$value);
    if ($text === '') {
        return '—';
    }

    return ucwords(str_replace('-', ' ', $text));
};
$isExternalMode = $model->account_type === GrantEmployeePermissionsForm::ACCOUNT_EXTERNAL;
$hasSelectedEmployee = !$isExternalMode && (int)$model->employee_id > 0;
$canSubmitForm = $isExternalMode || $hasSelectedEmployee;
$profileSectionClass = $hasSelectedEmployee
    ? 'rounded-xl border border-slate-200 bg-slate-50/60 p-4 md:p-5'
    : 'rounded-xl border border-slate-200 bg-slate-50/60 p-4 md:p-5 opacity-50 pointer-events-none hidden';
$externalSectionClass = $isExternalMode
    ? 'rounded-xl border border-slate-200 bg-slate-50/60 p-4 md:p-5'
    : 'rounded-xl border border-slate-200 bg-slate-50/60 p-4 md:p-5 hidden';
$employeePickerClass = $isExternalMode ? 'hidden' : '';
$editableSectionClass = $canSubmitForm
    ? 'rounded-xl border border-slate-200 bg-slate-50/80 p-4 md:p-5 space-y-4'
    : 'rounded-xl border border-slate-200 bg-slate-50/80 p-4 md:p-5 space-y-4 opacity-50 pointer-events-none';
$moduleSectionClass = $canSubmitForm ? '' : 'opacity-50 pointer-events-none';

?>

<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => 'manage_hr']) ?>

    <main class="flex-1 flex flex-col overflow-hidden min-w-0">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'User Permissions', 'userName' => $userName]) ?>

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
                <?php if ($model->hasErrors()): ?>
                    <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm">
                        <p class="font-semibold mb-2">Could not save. Please fix the following:</p>
                        <?= Html::errorSummary($model, ['class' => 'list-disc pl-5 space-y-1']) ?>
                    </div>
                <?php endif; ?>

                <header>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">User Permissions</h1>
                    <p class="text-slate-500 mt-1">Grant dashboard access to an existing employee or create a new external user with custom permissions.</p>
                </header>

                <!-- Assign Permissions -->
                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex items-start gap-4">
                        <div class="size-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-2xl">admin_panel_settings</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Assign Permissions</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Choose an employee or create an external user, then set dashboard login credentials and module access.</p>
                        </div>
                    </div>

                    <div class="p-6 lg:p-8">
                        <?= Html::beginForm(['manage-hr'], 'post', ['class' => 'space-y-6', 'id' => 'grant-permissions-form']) ?>
                        <?= Html::hiddenInput('form_type', 'grant_permissions') ?>
                        <?= Html::activeHiddenInput($model, 'account_type', ['id' => 'permission-account-type']) ?>
                        <?= Html::activeHiddenInput($model, 'employee_id', ['id' => 'permission-employee-id']) ?>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <button type="button"
                                    data-account-type="<?= Html::encode(GrantEmployeePermissionsForm::ACCOUNT_EMPLOYEE) ?>"
                                    class="permission-account-toggle flex-1 h-11 px-4 rounded-xl border text-sm font-bold transition-colors <?= !$isExternalMode ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' ?>">
                                Existing Employee
                            </button>
                            <button type="button"
                                    data-account-type="<?= Html::encode(GrantEmployeePermissionsForm::ACCOUNT_EXTERNAL) ?>"
                                    class="permission-account-toggle flex-1 h-11 px-4 rounded-xl border text-sm font-bold transition-colors <?= $isExternalMode ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' ?>">
                                External User
                            </button>
                        </div>

                        <div id="permission-employee-picker" class="<?= Html::encode($employeePickerClass) ?> rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Select Employee</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl pointer-events-none">search</span>
                                <input type="text"
                                       id="permission-employee-combobox"
                                       autocomplete="off"
                                       placeholder="Search employees and teachers by name, email, or job title..."
                                       class="w-full h-11 pl-10 pr-10 rounded-lg border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                                <button type="button"
                                        id="permission-employee-clear"
                                        class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                        aria-label="Clear employee selection">
                                    <span class="material-symbols-outlined text-xl">close</span>
                                </button>
                            </div>
                            <div id="permission-employee-results"
                                 class="hidden mt-2 max-h-56 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-sm"></div>
                            <p id="permission-employee-hint" class="text-xs text-slate-500 mt-2">Includes all active employees and teachers in the system.</p>
                            <?= Html::error($model, 'employee_id', ['class' => 'text-red-600 text-xs mt-2']) ?>
                        </div>

                        <div id="permission-external-fields" class="<?= Html::encode($externalSectionClass) ?>">
                            <div class="mb-4">
                                <h3 class="text-sm font-bold text-slate-800">External User Profile</h3>
                                <p class="text-xs text-slate-500 mt-1">This person is not an employee in the system. Enter their basic contact details below.</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Full Name</label>
                                    <?= Html::activeTextInput($model, 'full_name', [
                                        'id' => 'permission-external-full-name',
                                        'class' => 'w-full h-11 px-4 rounded-lg border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500',
                                        'autocomplete' => 'name',
                                        'placeholder' => 'e.g. John Smith',
                                    ]) ?>
                                    <?= Html::error($model, 'full_name', ['class' => 'text-red-600 text-xs mt-1']) ?>
                                </div>
                                <?php if ($hasUserPhone): ?>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Phone Number</label>
                                    <?= Html::activeTextInput($model, 'phone', [
                                        'id' => 'permission-external-phone',
                                        'class' => 'w-full h-11 px-4 rounded-lg border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500',
                                        'autocomplete' => 'tel',
                                        'placeholder' => 'e.g. 0791234567',
                                        'data-digits-only' => true,
                                        'inputmode' => 'numeric',
                                        'pattern' => '[0-9]*',
                                    ]) ?>
                                    <?= Html::error($model, 'phone', ['class' => 'text-red-600 text-xs mt-1']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div id="permission-profile-fields" class="<?= Html::encode($profileSectionClass) ?>">
                            <div class="mb-4">
                                <h3 class="text-sm font-bold text-slate-800">Employee Profile</h3>
                                <p class="text-xs text-slate-500 mt-1">Existing employee data (read-only). Edit the employee record elsewhere if profile details need to change.</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Full Name</p>
                                    <p id="permission-display-full-name" class="text-sm font-medium text-slate-900"><?= Html::encode($displayOrDash($model->display_full_name)) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Work Email</p>
                                    <p id="permission-display-email" class="text-sm font-medium text-slate-900"><?= Html::encode($displayOrDash($model->display_email)) ?></p>
                                </div>
                                <?php if ($hasPhone): ?>
                                <div>
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Phone Number</p>
                                    <p id="permission-display-phone" class="text-sm font-medium text-slate-900"><?= Html::encode($displayOrDash($model->display_phone)) ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($hasJobTitle): ?>
                                <div>
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Job Title</p>
                                    <p id="permission-display-job-title" class="text-sm font-medium text-slate-900"><?= Html::encode($displayOrDash($model->display_job_title)) ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($hasPosition): ?>
                                <div>
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Position</p>
                                    <p id="permission-display-position" class="text-sm font-medium text-slate-900"><?= Html::encode($displayOrDash($model->display_position)) ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($hasJoiningDate): ?>
                                <div>
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Joining Date</p>
                                    <p id="permission-display-joining-date" class="text-sm font-medium text-slate-900"><?= Html::encode($displayOrDash($model->display_joining_date)) ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($hasEmploymentType): ?>
                                <div>
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Employment Type</p>
                                    <p id="permission-display-employment-type" class="text-sm font-medium text-slate-900"><?= Html::encode($displayEmploymentType($model->display_employment_type)) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div id="permission-login-fields" class="<?= Html::encode($editableSectionClass) ?>">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Dashboard Login</h3>
                                <p class="text-xs text-slate-500 mt-1"><?= $isExternalMode ? 'Set the email and password this external user will use to sign in.' : 'Create a unique email and password the employee will use to sign in to the HR dashboard.' ?></p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Dashboard Email</label>
                                    <?= Html::activeTextInput($model, 'dashboard_email', [
                                        'type' => 'email',
                                        'id' => 'permission-dashboard-email',
                                        'class' => 'w-full h-11 px-4 rounded-lg border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500',
                                        'autocomplete' => 'off',
                                        'placeholder' => 'e.g. bashar.omar@dashboard.culturehr.local',
                                    ]) ?>
                                    <?= Html::error($model, 'dashboard_email', ['class' => 'text-red-600 text-xs mt-1']) ?>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Dashboard Password</label>
                                    <div class="relative">
                                        <?= Html::activePasswordInput($model, 'password', [
                                            'id' => 'permission-password',
                                            'class' => 'w-full h-11 px-4 pr-11 rounded-lg border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 password-field',
                                            'autocomplete' => 'new-password',
                                            'placeholder' => 'Minimum 8 characters',
                                        ]) ?>
                                        <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" tabindex="-1" aria-label="Show password">
                                            <span class="material-symbols-outlined text-xl">visibility</span>
                                        </button>
                                    </div>
                                    <p id="permission-password-hint" class="text-xs text-slate-500 mt-1"><?= $isExternalMode ? 'Required when creating a new external user.' : 'Required when creating a new dashboard account.' ?></p>
                                    <?= Html::error($model, 'password', ['class' => 'text-red-600 text-xs mt-1']) ?>
                                </div>
                            </div>
                        </div>

                        <div id="permission-module-fields" class="<?= Html::encode($moduleSectionClass) ?>">
                        <?= $this->render('_hr-permission-fields', [
                            'formModel' => $model,
                            'selectedPermissions' => $model->permissions,
                            'permissionGroups' => $permissionGroups,
                            'permissionPresets' => $permissionPresets,
                            'checkboxClass' => 'permission-checkbox',
                        ]) ?>
                        </div>

                        <button type="submit"
                                id="permission-save-button"
                                <?= $canSubmitForm ? '' : 'disabled' ?>
                                class="w-full h-12 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-sm font-bold shadow-md transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-lg">verified_user</span>
                            <span id="permission-save-button-label"><?= $isExternalMode ? 'Create External User' : 'Save Dashboard Access' ?></span>
                        </button>

                        <?= Html::endForm() ?>
                    </div>
                </section>

                <!-- HR Accounts -->
                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100">
                        <h2 class="text-xl font-bold text-slate-900">Users With Permissions</h2>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mt-1">
                            <?= (int)$activeCount ?> Active Administrator<?= $activeCount === 1 ? '' : 's' ?> Found
                        </p>
                    </div>

                    <div class="px-6 py-4 border-b border-slate-100 space-y-3">
                        <?= Html::beginForm(['manage-hr'], 'get', ['class' => 'flex flex-col lg:flex-row lg:items-center gap-3', 'id' => 'hr-accounts-filter']) ?>
                            <div class="flex-1 relative min-w-0">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl pointer-events-none">search</span>
                                <input type="search" name="q" value="<?= Html::encode($search) ?>"
                                       placeholder="Filter by name or email..."
                                       class="w-full h-11 pl-10 pr-4 rounded-lg border border-slate-200 bg-slate-50 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3 sm:items-center lg:shrink-0">
                                <div class="sm:w-56 relative min-w-0">
                                    <select name="module"
                                            class="hr-module-filter w-full h-11 pl-4 pr-10 rounded-lg border border-slate-200 bg-slate-50 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500"
                                            onchange="this.form.submit()">
                                        <option value="">Filter by Module</option>
                                        <option value="none" <?= $moduleFilter === 'none' ? 'selected' : '' ?>>None</option>
                                        <?php foreach ($moduleFilterOptions as $key => $label): ?>
                                            <option value="<?= Html::encode($key) ?>" <?= $moduleFilter === $key ? 'selected' : '' ?>>
                                                <?= Html::encode($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xl">expand_more</span>
                                </div>
                                <?php if ($hasActiveFilters): ?>
                                    <a href="<?= Html::encode($clearFiltersUrl) ?>"
                                       class="inline-flex items-center justify-center h-11 px-5 rounded-lg text-sm font-semibold text-slate-700 border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 transition-colors whitespace-nowrap">
                                        Clear filters
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?= Html::endForm() ?>

                        <?php if ($hasActiveFilters): ?>
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide mr-1">Active filters</span>
                                <?php if ($search !== ''): ?>
                                    <span class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1.5 rounded-lg bg-blue-50 border border-blue-100 text-sm text-blue-800">
                                        <span class="text-xs font-semibold text-blue-600/80 uppercase tracking-wide">Search</span>
                                        <span class="font-medium"><?= Html::encode($search) ?></span>
                                        <a href="<?= Html::encode(Url::to(['manage-hr', 'module' => $moduleFilter])) ?>"
                                           class="ml-1 p-0.5 rounded-md text-blue-500 hover:bg-blue-100 hover:text-blue-700 transition-colors"
                                           title="Remove search filter"
                                           aria-label="Remove search filter">
                                            <span class="material-symbols-outlined text-base leading-none">close</span>
                                        </a>
                                    </span>
                                <?php endif; ?>
                                <?php if ($moduleFilter !== ''): ?>
                                    <span class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1.5 rounded-lg bg-slate-100 border border-slate-200 text-sm text-slate-800">
                                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Module</span>
                                        <span class="font-medium"><?= Html::encode($activeModuleLabel) ?></span>
                                        <a href="<?= Html::encode(Url::to(['manage-hr', 'q' => $search])) ?>"
                                           class="ml-1 p-0.5 rounded-md text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition-colors"
                                           title="Remove module filter"
                                           aria-label="Remove module filter">
                                            <span class="material-symbols-outlined text-base leading-none">close</span>
                                        </a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wide">User Details</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-400 uppercase tracking-wide">Role</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-400 uppercase tracking-wide">Permissions</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-400 uppercase tracking-wide">Status</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-400 uppercase tracking-wide text-right">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                            <?php if ($hrUsers === []): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                        No HR accounts match your filters.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($hrUsers as $hrUser): ?>
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="size-10 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                                <?= Html::encode((string)$hrUser['initials']) ?>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-900 truncate">
                                                    <?= Html::encode($hrUser['username'] !== '' ? $hrUser['username'] : '—') ?>
                                                </div>
                                                <?php if (!empty($hrUser['isSuperAdmin'])): ?>
                                                    <div class="text-slate-400 text-xs">Super administrator account</div>
                                                <?php elseif (!empty($hrUser['isExternal'])): ?>
                                                    <div class="text-slate-400 text-xs">External user</div>
                                                <?php elseif ((string)$hrUser['email'] !== ''): ?>
                                                    <div class="text-slate-500 text-xs truncate"><?= Html::encode((string)$hrUser['email']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wide <?= Html::encode((string)$hrUser['roleClass']) ?>">
                                            <?= Html::encode((string)$hrUser['roleLabel']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-wrap gap-1.5 min-w-[12rem] max-w-md xl:max-w-lg">
                                            <?php foreach ($hrUser['permissionPills'] as $pill): ?>
                                                <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                                    <?= Html::encode((string)$pill) ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if ((int)$hrUser['permissionOverflow'] > 0): ?>
                                                <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600">
                                                    +<?= (int)$hrUser['permissionOverflow'] ?> more
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <?php if ($hrUser['isActive']): ?>
                                            <span class="inline-flex items-center gap-1.5 text-emerald-600 text-sm font-medium">
                                                <span class="size-2 rounded-full bg-emerald-500"></span>
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 text-slate-500 text-sm font-medium">
                                                <span class="size-2 rounded-full bg-slate-400"></span>
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <a href="<?= Url::to(['view-hr', 'id' => (int)$hrUser['id'], 'q' => $search, 'module' => $moduleFilter]) ?>"
                                               class="p-2 rounded-lg text-slate-400 hover:text-[#642754] hover:bg-purple-50 transition-colors"
                                               title="View profile">
                                                <span class="material-symbols-outlined text-xl">visibility</span>
                                            </a>
                                        <?php if (!empty($hrUser['isSuperAdmin'])): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold text-slate-400 bg-slate-100"
                                                  title="Super admin accounts are protected">
                                                <span class="material-symbols-outlined text-base">lock</span>
                                                Protected
                                            </span>
                                        <?php else: ?>
                                                <a href="<?= Url::to(['update-hr', 'id' => (int)$hrUser['id'], 'q' => $search, 'module' => $moduleFilter]) ?>"
                                                   class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                                   title="Edit">
                                                    <span class="material-symbols-outlined text-xl">edit</span>
                                                </a>
                                                <?= Html::beginForm(['manage-hr-action'], 'post', ['class' => 'inline m-0']) ?>
                                                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                                                    <?= Html::hiddenInput('action', 'delete') ?>
                                                    <?= Html::hiddenInput('user_id', (int)$hrUser['id']) ?>
                                                    <?= Html::hiddenInput('redirect_back', Url::to(['manage-hr', 'q' => $search, 'module' => $moduleFilter])) ?>
                                                    <button type="submit"
                                                            onclick="return confirm('Remove this HR user? They will no longer be able to sign in.');"
                                                            class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                                            title="Delete">
                                                        <span class="material-symbols-outlined text-xl">delete</span>
                                                    </button>
                                                <?= Html::endForm() ?>
                                        <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>

<?php
$this->registerCss(<<<CSS
.hr-module-filter {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: none;
}
.hr-module-filter::-ms-expand {
    display: none;
}
CSS);

$presetsJson = json_encode($permissionPresets);
$employeeOptionsJson = json_encode($employeeOptions);
$profileUrlTemplate = json_encode(Url::to(['/hr/hr/employee-permission-profile', 'id' => '__EMPLOYEE_ID__']));
$accountEmployee = json_encode(GrantEmployeePermissionsForm::ACCOUNT_EMPLOYEE);
$accountExternal = json_encode(GrantEmployeePermissionsForm::ACCOUNT_EXTERNAL);
$this->registerJs(<<<JS
(function () {
    const presets = {$presetsJson};
    const employeeOptions = {$employeeOptionsJson};
    const profileUrlTemplate = {$profileUrlTemplate};
    const ACCOUNT_EMPLOYEE = {$accountEmployee};
    const ACCOUNT_EXTERNAL = {$accountExternal};

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

    bindPermissionControls('grant-permissions-form', 'permission-checkbox');

    const combobox = document.getElementById('permission-employee-combobox');
    const results = document.getElementById('permission-employee-results');
    const clearBtn = document.getElementById('permission-employee-clear');
    const employeeIdInput = document.getElementById('permission-employee-id');
    const accountTypeInput = document.getElementById('permission-account-type');
    const employeePicker = document.getElementById('permission-employee-picker');
    const externalFields = document.getElementById('permission-external-fields');
    const profileFields = document.getElementById('permission-profile-fields');
    const loginFields = document.getElementById('permission-login-fields');
    const moduleFields = document.getElementById('permission-module-fields');
    const saveButton = document.getElementById('permission-save-button');
    const hint = document.getElementById('permission-employee-hint');
    const passwordHint = document.getElementById('permission-password-hint');
    const accountToggles = document.querySelectorAll('.permission-account-toggle');

    function isExternalMode() {
        return accountTypeInput && accountTypeInput.value === ACCOUNT_EXTERNAL;
    }

    function updateAccountToggleStyles() {
        accountToggles.forEach(function (button) {
            const active = button.getAttribute('data-account-type') === accountTypeInput.value;
            button.classList.toggle('border-blue-600', active);
            button.classList.toggle('bg-blue-50', active);
            button.classList.toggle('text-blue-700', active);
            button.classList.toggle('border-slate-200', !active);
            button.classList.toggle('bg-white', !active);
            button.classList.toggle('text-slate-600', !active);
        });
    }

    function setAccountType(type) {
        if (!accountTypeInput) return;
        accountTypeInput.value = type;
        updateAccountToggleStyles();

        if (employeePicker) {
            employeePicker.classList.toggle('hidden', type === ACCOUNT_EXTERNAL);
        }
        if (externalFields) {
            externalFields.classList.toggle('hidden', type !== ACCOUNT_EXTERNAL);
        }
        if (profileFields) {
            profileFields.classList.toggle('hidden', type === ACCOUNT_EXTERNAL);
        }
        if (saveButton) {
            const label = document.getElementById('permission-save-button-label');
            if (label) {
                label.textContent = type === ACCOUNT_EXTERNAL ? 'Create External User' : 'Save Dashboard Access';
            }
        }
        if (passwordHint) {
            passwordHint.textContent = type === ACCOUNT_EXTERNAL
                ? 'Required when creating a new external user.'
                : 'Required when creating a new dashboard account.';
        }

        if (type === ACCOUNT_EXTERNAL) {
            if (employeeIdInput) employeeIdInput.value = '';
            if (combobox) combobox.value = '';
            if (clearBtn) clearBtn.classList.add('hidden');
            if (results) results.classList.add('hidden');
            setFormEnabled(true);
            return;
        }

        const hasEmployee = employeeIdInput && parseInt(employeeIdInput.value, 10) > 0;
        setFormEnabled(hasEmployee);
    }

    function setFieldValue(id, value) {
        const field = document.getElementById(id);
        if (!field) return;
        field.value = value || '';
    }

    function setDisplayValue(id, value) {
        const field = document.getElementById(id);
        if (!field) return;
        const text = value !== null && value !== undefined && String(value).trim() !== '' ? String(value) : '—';
        field.textContent = text;
    }

    function formatEmploymentType(value) {
        if (!value) return '—';
        return String(value).replace(/-/g, ' ').replace(/\b\w/g, function (char) {
            return char.toUpperCase();
        });
    }

    function setFormEnabled(enabled) {
        if (!isExternalMode() && profileFields) {
            profileFields.classList.toggle('hidden', !enabled);
            profileFields.classList.remove('opacity-50', 'pointer-events-none');
        }
        [loginFields, moduleFields].forEach(function (section) {
            if (!section) return;
            section.classList.toggle('opacity-50', !enabled);
            section.classList.toggle('pointer-events-none', !enabled);
        });
        if (saveButton) {
            saveButton.disabled = !enabled;
        }
    }

    accountToggles.forEach(function (button) {
        button.addEventListener('click', function () {
            setAccountType(button.getAttribute('data-account-type'));
        });
    });

    function renderResults(items) {
        if (!results) return;
        if (!items.length) {
            results.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">No employees found.</div>';
            results.classList.remove('hidden');
            return;
        }

        results.innerHTML = items.map(function (item) {
            return '<button type="button" data-employee-id="' + item.id + '" class="w-full text-left px-4 py-3 text-sm hover:bg-blue-50 border-b border-slate-100 last:border-b-0">' +
                item.label.replace(/</g, '&lt;') +
            '</button>';
        }).join('');
        results.classList.remove('hidden');

        results.querySelectorAll('[data-employee-id]').forEach(function (button) {
            button.addEventListener('click', function () {
                selectEmployee(parseInt(button.getAttribute('data-employee-id'), 10));
            });
        });
    }

    function filterEmployees(query) {
        const normalized = query.trim().toLowerCase();
        if (normalized === '') {
            return employeeOptions.slice(0, 12);
        }

        return employeeOptions.filter(function (item) {
            return item.search.indexOf(normalized) !== -1;
        }).slice(0, 12);
    }

    function selectEmployee(employeeId) {
        const selected = employeeOptions.find(function (item) {
            return item.id === employeeId;
        });
        if (!selected || !employeeIdInput) {
            return;
        }

        employeeIdInput.value = String(employeeId);
        if (combobox) {
            combobox.value = selected.label;
        }
        if (results) {
            results.classList.add('hidden');
        }
        if (clearBtn) {
            clearBtn.classList.remove('hidden');
        }
        if (hint) {
            hint.textContent = 'Loading employee profile...';
        }

        fetch(profileUrlTemplate.replace('__EMPLOYEE_ID__', encodeURIComponent(String(employeeId))), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Could not load employee profile.');
                }
                return response.json();
            })
            .then(function (data) {
                if (data.is_super_admin) {
                    if (hint) {
                        hint.textContent = 'This account is protected and cannot be edited here.';
                    }
                    setFormEnabled(false);
                    return;
                }

                setDisplayValue('permission-display-full-name', data.full_name);
                setDisplayValue('permission-display-email', data.email);
                setDisplayValue('permission-display-phone', data.phone);
                setDisplayValue('permission-display-job-title', data.job_title);
                setDisplayValue('permission-display-position', data.position);
                setDisplayValue('permission-display-joining-date', data.joining_date);
                setDisplayValue('permission-display-employment-type', formatEmploymentType(data.employment_type));
                setFieldValue('permission-dashboard-email', data.dashboard_email);
                setFieldValue('permission-password', '');
                setCheckboxes('.permission-checkbox', data.permissions || []);

                if (passwordHint) {
                    passwordHint.textContent = data.has_dashboard_account
                        ? 'Leave blank to keep the current password.'
                        : 'Required when creating a new dashboard account.';
                }

                const typeLabel = data.employee_type ? data.employee_type + ' • ' : '';
                const permissionCount = Array.isArray(data.permissions) ? data.permissions.length : 0;
                let accessLabel = 'No permissions assigned yet — select modules below.';
                if (permissionCount > 0) {
                    accessLabel = permissionCount + ' assigned permission' + (permissionCount === 1 ? '' : 's') + ' loaded.';
                }
                if (hint) {
                    hint.textContent = typeLabel + accessLabel;
                }
                setFormEnabled(true);
            })
            .catch(function () {
                if (hint) {
                    hint.textContent = 'Could not load employee profile. Try again.';
                }
                setFormEnabled(false);
            });
    }

    if (combobox) {
        combobox.addEventListener('input', function () {
            renderResults(filterEmployees(combobox.value));
        });
        combobox.addEventListener('focus', function () {
            renderResults(filterEmployees(combobox.value));
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (employeeIdInput) employeeIdInput.value = '';
            if (combobox) combobox.value = '';
            clearBtn.classList.add('hidden');
            if (results) results.classList.add('hidden');
            if (hint) hint.textContent = 'Includes all active employees and teachers in the system.';
            setCheckboxes('.permission-checkbox', []);
            setFieldValue('permission-dashboard-email', '');
            setFieldValue('permission-password', '');
            setDisplayValue('permission-display-full-name', '');
            setDisplayValue('permission-display-email', '');
            setDisplayValue('permission-display-phone', '');
            setDisplayValue('permission-display-job-title', '');
            setDisplayValue('permission-display-position', '');
            setDisplayValue('permission-display-joining-date', '');
            setDisplayValue('permission-display-employment-type', '');
            if (passwordHint) {
                passwordHint.textContent = 'Required when creating a new dashboard account.';
            }
            setFormEnabled(false);
        });
    }

    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = btn.parentElement.querySelector('.password-field');
            if (!input) return;
            const hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            btn.querySelector('.material-symbols-outlined').textContent = hidden ? 'visibility_off' : 'visibility';
        });
    });

    document.addEventListener('click', function (event) {
        if (!results || !combobox) return;
        if (results.contains(event.target) || combobox.contains(event.target)) {
            return;
        }
        results.classList.add('hidden');
    });

    if (employeeIdInput && employeeIdInput.value && !isExternalMode()) {
        const postedEmployeeId = parseInt(employeeIdInput.value, 10);
        if (!Number.isNaN(postedEmployeeId) && postedEmployeeId > 0) {
            const postedEmployee = employeeOptions.find(function (item) {
                return item.id === postedEmployeeId;
            });
            if (postedEmployee && combobox) {
                combobox.value = postedEmployee.label;
            }
            if (clearBtn) {
                clearBtn.classList.remove('hidden');
            }
            setFormEnabled(true);
            if (passwordHint && document.getElementById('permission-dashboard-email')?.value) {
                passwordHint.textContent = 'Leave blank to keep the current password.';
            }
        }
    } else if (isExternalMode()) {
        setAccountType(ACCOUNT_EXTERNAL);
    }
})();
JS);
?>
