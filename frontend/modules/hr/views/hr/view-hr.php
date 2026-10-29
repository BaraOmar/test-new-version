<?php

/**
 * @var yii\web\View $this
 * @var common\models\User $hrUser
 * @var common\modules\employee\models\Employee|null $employee
 * @var string $roleLabel
 * @var string $roleClass
 * @var string[] $permissions
 * @var array<string, array{title:string,icon:string,items:array<string,string>}> $permissionGroups
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

$this->title = 'HR Profile | CultureHR';

$backUrl = Url::to(['manage-hr', 'q' => $search, 'module' => $moduleFilter]);
$displayName = trim((string)($hrUser->username ?? ''));
$email = $hrUser->getAccountEmailForDisplay();
$isActive = $hrUser->isActiveAccount();
$isSuperAdmin = $hrUser->isSuperHrAdmin();
$permissionSet = array_flip($permissions);

$display = static function (?string $value): string {
    $value = trim((string)$value);

    return $value !== '' ? $value : '—';
};

$formatDate = static function (?string $value): string {
    $value = trim((string)$value);
    if ($value === '') {
        return '—';
    }
    $ts = strtotime($value);

    return $ts !== false ? date('M j, Y', $ts) : $value;
};

?>

<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => 'manage_hr']) ?>

    <main class="flex-1 flex flex-col overflow-hidden min-w-0">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'HR Profile', 'userName' => $userName]) ?>

        <div class="flex-1 overflow-y-auto bg-slate-100">
            <div class="w-full min-w-0 max-w-full px-6 lg:px-10 xl:px-12 py-8 space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <a href="<?= Html::encode($backUrl) ?>"
                       class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-blue-600 transition-colors no-underline">
                        <span class="material-symbols-outlined text-xl">arrow_back</span>
                        Back to HR Accounts
                    </a>
                    <?php if (!$isSuperAdmin): ?>
                        <a href="<?= Html::encode(Url::to(['update-hr', 'id' => (int)$hrUser->id, 'q' => $search, 'module' => $moduleFilter])) ?>"
                           class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-[#642754] hover:bg-[#5d245f] text-white text-sm font-bold shadow-sm no-underline">
                            <span class="material-symbols-outlined text-lg">edit</span>
                            Edit HR User
                        </a>
                    <?php endif; ?>
                </div>

                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 lg:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                        <div class="size-16 rounded-full bg-blue-600 text-white flex items-center justify-center text-lg font-bold shrink-0">
                            <?= Html::encode($hrUser->getInitials()) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight truncate">
                                <?= Html::encode($displayName !== '' ? $displayName : 'HR User') ?>
                            </h1>
                            <?php if ($email !== ''): ?>
                                <p class="text-slate-500 mt-1"><?= Html::encode($email) ?></p>
                            <?php elseif ($isSuperAdmin): ?>
                                <p class="text-slate-400 mt-1 text-sm">Super administrator account</p>
                            <?php endif; ?>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wide <?= Html::encode($roleClass) ?>">
                                    <?= Html::encode($roleLabel) ?>
                                </span>
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
                </section>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100">
                            <h2 class="text-lg font-bold text-slate-900">Account details</h2>
                        </div>
                        <dl class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Full name</dt>
                                <dd class="mt-1 font-medium text-slate-900"><?= Html::encode($display($employee?->full_name ?? $displayName)) ?></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Work email</dt>
                                <dd class="mt-1 font-medium text-slate-900"><?= Html::encode($display($email)) ?></dd>
                            </div>
                            <?php if ($hasJobTitle): ?>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Job title</dt>
                                    <dd class="mt-1 font-medium text-slate-900"><?= Html::encode($display($employee?->job_title ?? '')) ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ($hasPhone): ?>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Phone</dt>
                                    <dd class="mt-1 font-medium text-slate-900"><?= Html::encode($display($employee?->phone ?? '')) ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ($hasJoiningDate): ?>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Joining date</dt>
                                    <dd class="mt-1 font-medium text-slate-900"><?= Html::encode($formatDate($employee?->joining_date ?? '')) ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ($hasEmploymentType): ?>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Employment type</dt>
                                    <dd class="mt-1 font-medium text-slate-900"><?= Html::encode($display($employee?->employment_type ?? '')) ?></dd>
                                </div>
                            <?php endif; ?>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">User ID</dt>
                                <dd class="mt-1 font-medium text-slate-900">#<?= (int)$hrUser->id ?></dd>
                            </div>
                        </dl>
                    </section>

                    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100">
                            <h2 class="text-lg font-bold text-slate-900">Module permissions</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Modules this HR administrator can access.</p>
                        </div>
                        <div class="px-6 py-5 space-y-5">
                            <?php if ($isSuperAdmin): ?>
                                <div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                                    Super admin accounts have full access to all HR modules.
                                </div>
                            <?php endif; ?>

                            <?php foreach ($permissionGroups as $group): ?>
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="material-symbols-outlined text-lg text-[#642754]"><?= Html::encode((string)$group['icon']) ?></span>
                                        <h3 class="text-sm font-bold text-slate-900"><?= Html::encode((string)$group['title']) ?></h3>
                                    </div>
                                    <ul class="space-y-2">
                                        <?php foreach ($group['items'] as $key => $label): ?>
                                            <?php $enabled = $isSuperAdmin || isset($permissionSet[$key]); ?>
                                            <li class="flex items-center gap-2 text-sm <?= $enabled ? 'text-slate-800' : 'text-slate-400' ?>">
                                                <span class="material-symbols-outlined text-base <?= $enabled ? 'text-emerald-600' : 'text-slate-300' ?>">
                                                    <?= $enabled ? 'check_circle' : 'radio_button_unchecked' ?>
                                                </span>
                                                <?= Html::encode((string)$label) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>

                            <?php if (!$isSuperAdmin && isset($permissionSet['manage_hr'])): ?>
                                <div class="pt-2 border-t border-slate-100">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="material-symbols-outlined text-lg text-[#642754]">admin_panel_settings</span>
                                        <h3 class="text-sm font-bold text-slate-900">System</h3>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-slate-800">
                                        <span class="material-symbols-outlined text-base text-emerald-600">check_circle</span>
                                        Manage HR
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>
</div>
