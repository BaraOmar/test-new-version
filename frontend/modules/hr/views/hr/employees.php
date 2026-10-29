<?php

/**
 * Pixel UI reference: `Legacy_Code/HRMS/Employee_directory.php`
 *
 * @var yii\web\View $this
 * @var array<int, array{id:int,full_name:string,job_title:string,email:string,department_name:string,manager_name:string,joining_date:string,status:string,status_badge_class:string,status_badge_label:string}> $employees
 * @var string $userName
 * @var common\modules\employee\models\Department[] $departments
 * @var common\modules\employee\models\SchoolDepartment[] $schoolDepartments
 * @var bool $hasSchoolDepartments
 * @var string $search
 * @var int $dept
 * @var int $schoolDept
 * @var string $deptParam
 * @var string $schoolDeptParam
 * @var string $statusParam
 * @var string $joinDate
 * @var bool $hasStatus
 * @var bool $hasJoiningDate
 * @var yii\data\Pagination $pagination
 * @var int $totalCount
 * @var bool $hasActiveFilters
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'HR Employee Directory | CultureHR';

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');

$hasActiveFilters = (bool)($hasActiveFilters ?? false);
$clearFiltersUrl = Url::to(['/hr/hr/employees']);
$from = $totalCount > 0 ? $pagination->offset + 1 : 0;
$to = min($pagination->offset + count($employees), $totalCount);

$this->registerCss(<<<'CSS'
.employee-directory-table {
    table-layout: fixed;
    width: 100%;
}
.employee-directory-table th,
.employee-directory-table td {
    overflow: hidden;
    vertical-align: middle;
}
.employee-directory-table .cell-truncate {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
CSS);

?>

<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => 'employees']) ?>
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Directory', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="layout-content-container flex flex-col w-full min-w-0 max-w-full mx-auto px-4 lg:px-6 py-8">
                <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
                    <div class="flex flex-col gap-1">
                        <h1 class="text-slate-900 dark:text-slate-100 text-4xl font-black leading-tight tracking-tight">Employee Directory</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-base font-normal">Manage your organization's talent and team structure.</p>
                    </div>

                    <div class="flex gap-3">
                        <a href="<?= Url::to(['/hr/department/index']) ?>"
                           class="flex items-center gap-2 rounded-lg h-10 px-4 bg-white hover:bg-slate-50 text-slate-900 text-sm font-bold shadow-md border border-slate-200 transition-all">
                            <span class="material-symbols-outlined text-lg">apartment</span>
                            <span>View Departments</span>
                        </a>
                        <a href="<?= Url::to(['/hr/department/create']) ?>"
                           class="flex items-center gap-2 rounded-lg h-10 px-4 bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold shadow-md transition-all">
                            <span class="material-symbols-outlined text-lg">add</span>
                            <span>Create Department</span>
                        </a>
                        <a href="<?= Url::to(['/hr/hr/onboarding']) ?>"
                           class="flex items-center gap-2 rounded-lg h-10 px-4 bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold shadow-md transition-all">
                            <span class="material-symbols-outlined text-lg">person_add</span>
                            <span>Add Employee</span>
                        </a>
                    </div>
                </div>

                <?php if ($successMessage !== ''): ?>
                    <div class="mb-4 p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-medium">
                        <?= Html::encode($successMessage) ?>
                    </div>
                <?php endif; ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="mb-4 p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium">
                        <?= Html::encode($errorMessage) ?>
                    </div>
                <?php endif; ?>

                <!-- Search + Filters (GET) -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm mb-6 space-y-3">
                <?= Html::beginForm(['/hr/hr/employees'], 'get', [
                    'class' => 'flex flex-col lg:flex-row lg:items-center gap-4',
                    'id' => 'employee-directory-filter',
                ]) ?>
                    <div class="flex-1 relative min-w-0">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">search</span>
                        <input name="search" value="<?= Html::encode((string)$search) ?>"
                               class="w-full h-11 pl-10 pr-4 rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:ring-primary focus:border-primary border text-sm"
                               placeholder="Search by name, email, or role..." type="text" />
                    </div>

                    <div class="flex flex-wrap gap-3 items-center">
                        <div class="relative min-w-[140px] flex-1 sm:flex-none">
                            <select name="dept" onchange="this.form.submit()"
                                    class="appearance-none w-full h-11 px-4 pr-10 rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm font-medium focus:ring-primary focus:border-primary border">
                                <option value="" <?= $deptParam === '' ? 'selected' : '' ?>>Department</option>
                                <option value="all" <?= $deptParam === 'all' ? 'selected' : '' ?>>All Departments</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= (int)$d->id ?>" <?= ($deptParam === (string)(int)$d->id) ? 'selected' : '' ?>>
                                        <?= Html::encode((string)$d->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></span>
                        </div>

                        <?php if ($hasSchoolDepartments): ?>
                            <div class="relative min-w-[180px] flex-1 sm:flex-none">
                                <select name="school_dept" onchange="this.form.submit()"
                                        class="appearance-none w-full h-11 px-4 pr-10 rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm font-medium focus:ring-primary focus:border-primary border">
                                    <option value="" <?= $schoolDeptParam === '' ? 'selected' : '' ?>>School Dept</option>
                                    <option value="all" <?= $schoolDeptParam === 'all' ? 'selected' : '' ?>>All School Depts</option>
                                    <?php foreach ($schoolDepartments as $schoolDepartment): ?>
                                        <option value="<?= (int)$schoolDepartment->id ?>" <?= ($schoolDeptParam === (string)(int)$schoolDepartment->id) ? 'selected' : '' ?>>
                                            <?= Html::encode((string)$schoolDepartment->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></span>
                            </div>
                        <?php endif; ?>

                        <div class="relative min-w-[160px] flex-1 sm:flex-none">
                            <input type="date" name="join_date" value="<?= Html::encode((string)($joinDate ?? '')) ?>"
                                   <?= $hasJoiningDate ? '' : 'disabled' ?>
                                   class="w-full h-11 px-4 rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm font-medium focus:ring-primary focus:border-primary border <?= $hasJoiningDate ? '' : 'opacity-60 cursor-not-allowed' ?>"
                                   title="Joining date" />
                        </div>

                        <div class="relative min-w-[140px] flex-1 sm:flex-none">
                            <select name="status" onchange="this.form.submit()"
                                    class="appearance-none w-full h-11 px-4 pr-10 rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm font-medium focus:ring-primary focus:border-primary border">
                                <option value="" <?= $statusParam === '' ? 'selected' : '' ?>>Status</option>
                                <option value="all" <?= $statusParam === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                <option value="Active" <?= ($statusParam === 'Active') ? 'selected' : '' ?>>Active</option>
                                <option value="Onboarding" <?= ($statusParam === 'Onboarding') ? 'selected' : '' ?>>Onboarding</option>
                                <option value="Leave" <?= ($statusParam === 'Leave') ? 'selected' : '' ?>>Leave</option>
                                <option value="Terminated" <?= ($statusParam === 'Terminated') ? 'selected' : '' ?>>Terminated</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></span>
                        </div>

                        <button type="submit"
                                class="h-11 px-4 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors flex items-center justify-center"
                                title="Apply filters">
                            <span class="material-symbols-outlined">search</span>
                        </button>
                        <?php if ($hasActiveFilters): ?>
                            <a href="<?= Html::encode($clearFiltersUrl) ?>"
                               class="inline-flex items-center justify-center h-11 px-5 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors whitespace-nowrap">
                                Clear all
                            </a>
                        <?php endif; ?>
                    </div>
                <?= Html::endForm() ?>

                <?php if ($hasActiveFilters): ?>
                    <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide mr-1">Active filters</span>
                        <?php if ($search !== ''): ?>
                            <span class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800 text-sm text-blue-800 dark:text-blue-200">
                                <span class="text-xs font-semibold text-blue-600/80 uppercase tracking-wide">Search</span>
                                <span class="font-medium"><?= Html::encode($search) ?></span>
                            </span>
                        <?php endif; ?>
                        <?php if ($deptParam === 'all'): ?>
                            <span class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-200">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Department</span>
                                <span class="font-medium">All</span>
                            </span>
                        <?php elseif ((int)$dept > 0): ?>
                            <?php
                            $deptName = 'Department';
                            foreach ($departments as $d) {
                                if ((int)$d->id === (int)$dept) {
                                    $deptName = (string)$d->name;
                                    break;
                                }
                            }
                            ?>
                            <span class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-200">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Department</span>
                                <span class="font-medium"><?= Html::encode($deptName) ?></span>
                            </span>
                        <?php endif; ?>
                        <?php if ($schoolDeptParam === 'all'): ?>
                            <span class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1.5 rounded-lg bg-violet-50 dark:bg-violet-900/30 border border-violet-100 dark:border-violet-800 text-sm text-violet-800 dark:text-violet-200">
                                <span class="text-xs font-semibold text-violet-600/80 uppercase tracking-wide">School Dept</span>
                                <span class="font-medium">All</span>
                            </span>
                        <?php elseif ((int)$schoolDept > 0): ?>
                            <?php
                            $schoolDeptName = 'School Department';
                            foreach ($schoolDepartments as $schoolDepartment) {
                                if ((int)$schoolDepartment->id === (int)$schoolDept) {
                                    $schoolDeptName = (string)$schoolDepartment->name;
                                    break;
                                }
                            }
                            ?>
                            <span class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1.5 rounded-lg bg-violet-50 dark:bg-violet-900/30 border border-violet-100 dark:border-violet-800 text-sm text-violet-800 dark:text-violet-200">
                                <span class="text-xs font-semibold text-violet-600/80 uppercase tracking-wide">School Dept</span>
                                <span class="font-medium"><?= Html::encode($schoolDeptName) ?></span>
                            </span>
                        <?php endif; ?>
                        <?php if (($joinDate ?? '') !== ''): ?>
                            <span class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-200">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Joining</span>
                                <span class="font-medium"><?= Html::encode($joinDate) ?></span>
                            </span>
                        <?php endif; ?>
                        <?php if ($statusParam === 'all'): ?>
                            <span class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-200">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</span>
                                <span class="font-medium">All</span>
                            </span>
                        <?php elseif ($statusParam !== ''): ?>
                            <span class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-200">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</span>
                                <span class="font-medium"><?= Html::encode($statusParam) ?></span>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                </div>

                <?php if ($hasActiveFilters): ?>
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden min-w-0">
                        <table class="employee-directory-table w-full text-left border-collapse">
                            <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                <th class="w-[17%] px-3 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Name</th>
                                <th class="w-[13%] px-3 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Job Title</th>
                                <th class="w-[20%] px-3 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Contact Email</th>
                                <th class="w-[12%] px-3 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Department</th>
                                <th class="w-[14%] px-3 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Manager</th>
                                <th class="w-[10%] px-3 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Joining Date</th>
                                <th class="w-[8%] px-3 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                                <th class="w-[6%] px-2 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-right">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php if (!$employees): ?>
                                <tr>
                                    <td colspan="8" class="px-3 py-8 text-sm text-slate-500 dark:text-slate-400">
                                        No employees found.
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($employees as $employee): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <?= $this->render('@frontend/modules/employee/views/layouts/_initials-avatar', [
                                                'name' => (string)$employee['full_name'],
                                                'sizeClass' => 'size-8',
                                                'textClass' => 'text-xs',
                                            ]) ?>
                                            <?php if (!empty($employee['is_managed_hr'])): ?>
                                                <span class="cell-truncate text-sm font-bold text-slate-900 dark:text-slate-100"
                                                      title="<?= Html::encode((string)$employee['full_name']) ?>">
                                                    <?= Html::encode((string)$employee['full_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <a class="cell-truncate text-sm font-bold text-slate-900 dark:text-slate-100 hover:text-[#642754] transition-colors"
                                                   title="<?= Html::encode((string)$employee['full_name']) ?>"
                                                   href="<?= Url::to(['/hr/hr/view-employee', 'id' => (int)$employee['id']]) ?>">
                                                    <?= Html::encode((string)$employee['full_name']) ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-slate-600 dark:text-slate-400">
                                        <span class="cell-truncate" title="<?= Html::encode((string)$employee['job_title']) ?>"><?= Html::encode((string)$employee['job_title']) ?></span>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-slate-600 dark:text-slate-400">
                                        <span class="cell-truncate" title="<?= Html::encode((string)$employee['email']) ?>"><?= Html::encode((string)$employee['email']) ?></span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="cell-truncate inline-flex max-w-full items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary"
                                              title="<?= Html::encode((string)$employee['department_name']) ?>">
                                            <?= Html::encode((string)$employee['department_name']) ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <?php if ((string)$employee['manager_name'] !== '—'): ?>
                                                <?= $this->render('@frontend/modules/employee/views/layouts/_initials-avatar', [
                                                    'name' => (string)$employee['manager_name'],
                                                    'sizeClass' => 'size-6',
                                                    'textClass' => 'text-[10px]',
                                                ]) ?>
                                            <?php endif; ?>
                                            <span class="cell-truncate text-sm text-slate-600 dark:text-slate-400"
                                                  title="<?= Html::encode((string)$employee['manager_name']) ?>">
                                                <?= Html::encode((string)$employee['manager_name']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                        <?= Html::encode((string)($employee['joining_date'] !== '' ? $employee['joining_date'] : '—')) ?>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap <?= Html::encode((string)$employee['status_badge_class']) ?>">
                                            <?= Html::encode((string)$employee['status_badge_label']) ?>
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 text-right">
                                        <div class="relative inline-block text-left">
                                            <button type="button" onclick="toggleMenu(<?= (int)$employee['id'] ?>, this)"
                                                    class="text-slate-400 hover:text-primary transition-colors">
                                                <span class="material-symbols-outlined">more_vert</span>
                                            </button>

                                            <div id="menu-<?= (int)$employee['id'] ?>"
                                                 class="hidden fixed w-56 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl z-[9999] overflow-hidden">
                                                <a href="<?= Url::to(['/hr/hr/view-employee', 'id' => (int)$employee['id']]) ?>"
                                                   class="block px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-700">
                                                    View Employee
                                                </a>
                                                <?php if (empty($employee['is_managed_hr'])): ?>
                                                    <a href="<?= Url::to(['/hr/hr/onboarding', 'id' => (int)$employee['id']]) ?>"
                                                       class="block px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-700">
                                                        Edit Employee
                                                    </a>

                                                    <?= Html::beginForm(['/hr/hr/employee-action'], 'post', ['class' => 'm-0']) ?>
                                                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                                                        <?= Html::hiddenInput('action', 'delete') ?>
                                                        <?= Html::hiddenInput('employee_id', (int)$employee['id']) ?>
                                                        <?= Html::hiddenInput('redirect_back', Yii::$app->request->url) ?>
                                                        <button type="submit"
                                                                onclick="return confirm('Are you sure you want to delete this employee?')"
                                                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                            Delete Employee
                                                        </button>
                                                    <?= Html::endForm() ?>
                                                <?php endif; ?>

                                                <a href="<?= Url::to(['/hr/survey/send', 'id' => (int)$employee['id']]) ?>"
                                                   class="block px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-700">
                                                    Send Survey
                                                </a>
                                                <a href="<?= Url::to(['/hr/evaluation/send', 'id' => (int)$employee['id']]) ?>"
                                                   class="block px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-700">
                                                    Send Evaluation
                                                </a>

                                                <?php if (empty($employee['is_managed_hr'])): ?>
                                                    <div class="border-t border-slate-200 dark:border-slate-700 my-1"></div>

                                                    <?php if ($hasStatus): ?>
                                                        <?php foreach (['Active', 'Onboarding', 'Leave', 'Terminated'] as $st): ?>
                                                            <?= Html::beginForm(['/hr/hr/employee-action'], 'post', ['class' => 'm-0']) ?>
                                                                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                                                                <?= Html::hiddenInput('action', 'change_status') ?>
                                                                <?= Html::hiddenInput('employee_id', (int)$employee['id']) ?>
                                                                <?= Html::hiddenInput('status', $st) ?>
                                                                <?= Html::hiddenInput('redirect_back', Yii::$app->request->url) ?>
                                                                <button type="submit"
                                                                        class="w-full text-left px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-700">
                                                                    Set <?= Html::encode($st) ?>
                                                                </button>
                                                            <?= Html::endForm() ?>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="px-4 py-2 text-xs text-slate-500 dark:text-slate-400">
                                                            Status feature needs a <b>status</b> column in employees.
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                        <span class="text-sm text-slate-500 dark:text-slate-400">
                            <?php if ($totalCount === 0): ?>
                                Showing 0 employees
                            <?php else: ?>
                                Showing <?= (int)$from ?> to <?= (int)$to ?> of <?= (int)$totalCount ?> employees
                            <?php endif; ?>
                        </span>
                        <?= LinkPager::widget([
                            'pagination' => $pagination,
                            'options' => ['class' => 'flex flex-wrap gap-2 justify-center sm:justify-end'],
                            'linkOptions' => ['class' => 'inline-flex items-center justify-center min-w-9 h-9 px-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm font-semibold text-slate-700 dark:text-slate-200 no-underline hover:bg-slate-100 dark:hover:bg-slate-800'],
                            'disabledListItemSubTagOptions' => ['class' => 'inline-flex items-center justify-center min-w-9 h-9 px-2 rounded-lg border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-sm text-slate-400'],
                            'activePageCssClass' => 'active',
                        ]) ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-12 text-center">
                    <div class="inline-flex size-14 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mb-4">
                        <span class="material-symbols-outlined text-3xl">filter_list</span>
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Apply a filter to view employees</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">
                        Use search, department, school department, joining date, or status filters above, then click the search button to load the directory.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
let activeMenu = null;
function toggleMenu(id, button) {
  const menu = document.getElementById('menu-' + id);
  document.querySelectorAll('[id^="menu-"]').forEach(m => m.classList.add('hidden'));
  if (activeMenu === menu) { activeMenu = null; return; }
  activeMenu = menu;
  const rect = button.getBoundingClientRect();
  const menuHeight = 320;
  let top = rect.bottom + 6;
  let left = rect.right - 220;
  if (window.innerHeight - rect.bottom < menuHeight) {
    top = rect.top - menuHeight - 6;
  }
  menu.style.top = top + "px";
  menu.style.left = left + "px";
  menu.classList.remove('hidden');
}
document.addEventListener('click', function(e) {
  if (!e.target.closest('[onclick^="toggleMenu"]') && !e.target.closest('[id^="menu-"]')) {
    document.querySelectorAll('[id^="menu-"]').forEach(m => m.classList.add('hidden'));
    activeMenu = null;
  }
});
</script>

