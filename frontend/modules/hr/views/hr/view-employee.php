<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\Employee $employee
 * @var string $userName
 * @var bool $hasJoiningDate
 * @var bool $hasMaritalStatus
 * @var bool $hasPositionLevel
 * @var bool $hasEmploymentType
 * @var bool $hasEmployeeCategory
 * @var bool $hasPhone
 * @var bool $hasLocation
 * @var bool $hasStatus
 * @var bool $hasPersonalEmail
 * @var bool $hasPortalCredentials
 * @var common\models\User|null $linkedUser
 */

use common\modules\employee\models\Department;
use common\modules\employee\models\Employee;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'View Employee | CultureHR';

$display = static function (string $value): string {
    $value = trim($value);

    return $value !== '' ? $value : '—';
};

$field = static function (string $label, string $value) use ($display): string {
    return '<div class="flex flex-col gap-1">'
        . '<dt class="text-xs font-bold uppercase tracking-wide text-slate-500">' . Html::encode($label) . '</dt>'
        . '<dd class="text-sm text-slate-900 dark:text-slate-100 font-medium">' . Html::encode($display($value)) . '</dd>'
        . '</div>';
};

$managerName = '—';
$department = $employee->department;
if ($department !== null && Department::hasColumn('manager_id') && $department->manager) {
    $managerName = (string)$department->manager->full_name;
} elseif (Employee::hasColumn('manager_id') && $employee->manager) {
    $managerName = (string)$employee->manager->full_name;
}

?>

<div class="flex h-screen overflow-hidden w-full">
    <?= $this->render('../layouts/_navbar', ['currentPage' => 'employees']) ?>
    <div class="flex-1 min-w-0 overflow-auto">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'View Employee', 'userName' => $userName]) ?>

        <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="flex mb-6 text-sm font-medium text-slate-500 dark:text-slate-400">
                <ol class="flex items-center flex-wrap gap-x-2 gap-y-1">
                    <li><a class="hover:text-[#642754] no-underline" href="<?= Url::to(['/hr/hr/employees']) ?>">Employee Directory</a></li>
                    <li class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-base text-slate-300">chevron_right</span>
                        <span class="text-slate-900 dark:text-slate-100">View Employee</span>
                    </li>
                </ol>
            </nav>

            <div class="mb-8 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                <div class="flex gap-5 items-center">
                    <?= $this->render('@frontend/modules/employee/views/layouts/_initials-avatar', [
                        'name' => (string)$employee->full_name,
                        'sizeClass' => 'size-20',
                        'textClass' => 'text-2xl',
                    ]) ?>
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            <?= Html::encode($employee->full_name) ?>
                        </h1>
                        <p class="mt-1 text-lg text-[#642754] font-semibold"><?= Html::encode($employee->job_title) ?></p>
                        <?php if ($hasStatus): ?>
                            <span class="inline-flex mt-2 items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                <?= Html::encode($employee->getDisplayStatus()) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 shrink-0">
                    <a href="<?= Url::to(['/hr/hr/employees']) ?>"
                       class="inline-flex items-center gap-2 h-11 px-5 rounded-lg border-2 border-slate-200 text-slate-700 text-sm font-semibold no-underline hover:bg-slate-50">
                        <span class="material-symbols-outlined text-lg">arrow_back</span>
                        Back to directory
                    </a>
                    <a href="<?= Url::to(['/hr/hr/onboarding', 'id' => (int)$employee->id]) ?>"
                       class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-[#642754] text-white text-sm font-semibold no-underline hover:bg-[#532244]">
                        <span class="material-symbols-outlined text-lg">edit</span>
                        Edit employee
                    </a>
                </div>
            </div>

            <div class="space-y-6">
                <section class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#642754]">person</span>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Personal information</h2>
                    </div>
                    <dl class="p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?= $field('Full name', (string)$employee->full_name) ?>
                        <?php if ($hasPersonalEmail): ?>
                            <?= $field('Personal email', $employee->getContactEmail()) ?>
                        <?php endif; ?>
                        <?php if ($hasPhone): ?>
                            <?= $field('Phone number', (string)($employee->phone ?? '')) ?>
                        <?php endif; ?>
                        <?php if ($hasMaritalStatus): ?>
                            <?= $field('Marital status', $employee->getDisplayMaritalStatus()) ?>
                        <?php endif; ?>
                    </dl>
                </section>

                <?php if ($hasPortalCredentials): ?>
                    <?= $this->render('_employee-portal-credentials', [
                        'mode' => 'view',
                        'loginEmail' => (string)($linkedUser?->email ?? $employee->email ?? ''),
                        'loginPassword' => Employee::hasColumn('login_password_plain')
                            ? trim((string)($employee->login_password_plain ?? ''))
                            : '',
                        'passwordInputId' => 'employee-view-password',
                    ]) ?>
                <?php endif; ?>

                <section class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#642754]">work</span>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Professional details</h2>
                    </div>
                    <dl class="p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?= $field('Job title', (string)$employee->job_title) ?>
                        <?php if ($hasPositionLevel): ?>
                            <?= $field('Position level', $employee->getDisplayPositionLevel()) ?>
                        <?php endif; ?>
                        <?php if ($hasJoiningDate): ?>
                            <?= $field('Joining date', (string)($employee->joining_date ?? '')) ?>
                        <?php endif; ?>
                        <?php if ($hasEmploymentType): ?>
                            <?= $field('Employment type', $employee->getDisplayEmploymentType()) ?>
                        <?php endif; ?>
                        <?php if ($hasEmployeeCategory): ?>
                            <?= $field('Employee type', $employee->getDisplayEmployeeType()) ?>
                        <?php endif; ?>
                    </dl>
                </section>

                <section class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#642754]">corporate_fare</span>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Reporting structure</h2>
                    </div>
                    <dl class="p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?= $field('Department', (string)($employee->department->name ?? '')) ?>
                        <?= $field('Manager', $managerName) ?>
                        <?php if ($hasLocation): ?>
                            <?= $field('Work location', (string)($employee->location ?? '')) ?>
                        <?php endif; ?>
                    </dl>
                </section>
            </div>
        </main>
    </div>
</div>
<?php
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
