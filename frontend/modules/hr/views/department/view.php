<?php

/**
 * Department Details (HR module).
 *
 * @var yii\web\View $this
 * @var array{id:int,name:string,managerName:string,status:string,employeeCount:int} $department
 * @var array<int, array{id:int,full_name:string,job_title:string,email:string,joining_date:string,status_badge_class:string,status_badge_label:string,is_department_head:bool,is_managed_hr:bool}> $members
 * @var string $userName
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Department | CultureHR';

?>

<div class="flex h-screen overflow-hidden">
    <?= $this->render('@frontend/modules/hr/views/layouts/_navbar.php', ['currentPage' => 'employees']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('@frontend/modules/hr/views/layouts/_topbar.php', ['pageTitle' => 'Department', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="layout-content-container flex flex-col w-full max-w-[1400px] mx-auto px-6 lg:px-10 py-8 gap-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h1 class="text-slate-900 dark:text-slate-100 text-4xl font-black leading-tight tracking-tight truncate">
                            <?= Html::encode($department['name'] !== '' ? $department['name'] : ('Department #' . (int)$department['id'])) ?>
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 text-base font-normal">
                            <?= Html::encode($department['employeeCount'] . ' employee' . ($department['employeeCount'] === 1 ? '' : 's')) ?>
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <a href="<?= Url::to(['/hr/department/index']) ?>"
                           class="flex items-center gap-2 rounded-lg h-10 px-4 bg-white hover:bg-slate-50 text-slate-900 text-sm font-bold shadow-md border border-slate-200 transition-all">
                            <span class="material-symbols-outlined text-lg">arrow_back</span>
                            <span>Back</span>
                        </a>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                        <div class="text-slate-900 dark:text-slate-100 text-lg font-black">Department Information</div>
                        <div class="text-slate-500 dark:text-slate-400 text-sm">All saved data for this department.</div>
                    </div>
                    <div class="p-6 lg:p-8">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5">
                            <div class="min-h-[88px] p-5 lg:p-6 rounded-xl bg-slate-50 border border-slate-100 flex flex-col justify-center">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Department ID</div>
                                <div class="text-slate-900 font-black text-xl lg:text-2xl"><?= Html::encode((string)$department['id']) ?></div>
                            </div>
                            <div class="min-h-[88px] p-5 lg:p-6 rounded-xl bg-slate-50 border border-slate-100 flex flex-col justify-center">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Employees</div>
                                <div class="text-slate-900 font-black text-xl lg:text-2xl"><?= Html::encode((string)$department['employeeCount']) ?></div>
                            </div>
                            <div class="min-h-[88px] p-5 lg:p-6 rounded-xl bg-slate-50 border border-slate-100 flex flex-col justify-center min-w-0">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Manager</div>
                                <div class="text-slate-900 font-black text-lg lg:text-xl truncate" title="<?= Html::encode($department['managerName'] !== '' ? $department['managerName'] : 'N/A') ?>">
                                    <?= Html::encode($department['managerName'] !== '' ? $department['managerName'] : 'N/A') ?>
                                </div>
                            </div>
                            <div class="min-h-[88px] p-5 lg:p-6 rounded-xl bg-slate-50 border border-slate-100 flex flex-col justify-center">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Status</div>
                                <div class="text-slate-900 font-black text-xl lg:text-2xl capitalize"><?= Html::encode($department['status'] !== '' ? $department['status'] : 'N/A') ?></div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-200 mt-6 flex flex-wrap gap-3">
                            <a href="<?= Url::to(['/hr/department/update', 'id' => (int)$department['id']]) ?>"
                               class="inline-flex items-center gap-2 rounded-lg h-11 px-5 bg-[#6b2a6e] hover:bg-[#5d245f] text-white text-sm font-bold shadow-md transition-all">
                                <span class="material-symbols-outlined text-lg">edit</span>
                                <span>Update</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                        <div class="text-slate-900 dark:text-slate-100 text-lg font-black">Department Members</div>
                        <div class="text-slate-500 dark:text-slate-400 text-sm">
                            Everyone assigned to this department, including the department head.
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[960px] text-left border-collapse table-fixed">
                            <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                <th class="w-[22%] px-5 lg:px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Name</th>
                                <th class="w-[16%] px-5 lg:px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Job Title</th>
                                <th class="w-[24%] px-5 lg:px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Email</th>
                                <th class="w-[14%] px-5 lg:px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Role</th>
                                <th class="w-[12%] px-5 lg:px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Joining Date</th>
                                <th class="w-[12%] px-5 lg:px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php if ($members === []): ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                                        No members in this department yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($members as $member): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <td class="px-5 lg:px-6 py-4">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <?= $this->render('@frontend/modules/employee/views/layouts/_initials-avatar', [
                                                    'name' => (string)$member['full_name'],
                                                    'sizeClass' => 'size-8',
                                                    'textClass' => 'text-xs',
                                                ]) ?>
                                                <?php if (!empty($member['is_managed_hr'])): ?>
                                                    <span class="text-sm font-bold text-slate-900 truncate" title="<?= Html::encode((string)$member['full_name']) ?>">
                                                        <?= Html::encode((string)$member['full_name']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <a href="<?= Url::to(['/hr/hr/onboarding', 'id' => (int)$member['id']]) ?>"
                                                       class="text-sm font-bold text-slate-900 hover:text-[#6b2a6e] truncate"
                                                       title="<?= Html::encode((string)$member['full_name']) ?>">
                                                        <?= Html::encode((string)$member['full_name']) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-5 lg:px-6 py-4 text-sm text-slate-600 truncate"><?= Html::encode((string)$member['job_title']) ?></td>
                                        <td class="px-5 lg:px-6 py-4 text-sm text-slate-600 truncate" title="<?= Html::encode((string)$member['email']) ?>"><?= Html::encode((string)$member['email']) ?></td>
                                        <td class="px-5 lg:px-6 py-4">
                                            <?php if (!empty($member['is_department_head'])): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-[#6b2a6e]/10 text-[#6b2a6e]">
                                                    <span class="material-symbols-outlined text-sm">star</span>
                                                    Department head
                                                </span>
                                            <?php else: ?>
                                                <span class="text-sm text-slate-500">Member</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 lg:px-6 py-4 text-sm text-slate-600 whitespace-nowrap"><?= Html::encode((string)$member['joining_date']) ?></td>
                                        <td class="px-5 lg:px-6 py-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap <?= Html::encode((string)$member['status_badge_class']) ?>">
                                                <?= Html::encode((string)$member['status_badge_label']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

