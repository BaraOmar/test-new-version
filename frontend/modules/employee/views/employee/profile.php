<?php

/**
 * @var yii\web\View $this
 * @var string $pageTitle
 * @var string $navLinksHtml
 * @var common\modules\employee\models\Employee $employee
 * @var string $departmentName
 * @var string $managerName
 * @var string $statusClasses
 * @var string $joiningDateText
 * @var bool $isDepartmentHead
 * @var string[] $managedDepartmentNames
 * @var string $managedDepartmentsText
 * @var bool $usesSchoolReporting
 * @var array{remote:array{dotClass:string,textClass:string},hybrid:array{dotClass:string,textClass:string},onsite:array{dotClass:string,textClass:string}} $locationUi
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $pageTitle ?? 'My Profile';

$portalLocked = !empty($portalLocked);
$usesSchoolReporting = !empty($usesSchoolReporting);
$departmentLabel = $usesSchoolReporting ? 'School Department' : 'Department';
$departmentHeadLabel = $usesSchoolReporting ? 'School Department Head' : 'Department Head';
$managedDepartmentsLabel = $usesSchoolReporting ? 'School Department(s) Managed' : 'Department(s) Managed';
$headOfLabel = $usesSchoolReporting ? 'Head of School Department' : 'Head of';

?>

<?= $this->render('../layouts/_shell-start', [
    'pageTitle' => $this->title,
    'currentPage' => 'my_profile',
    'userName' => $employee->full_name,
    'userRole' => $employee->job_title ?: 'Employee',
    'portalLocked' => $portalLocked,
]) ?>
            <div class="flex flex-col max-w-[1024px] mx-auto w-full flex-1 gap-8 px-6 sm:px-10 lg:px-12 py-10">
                    <?php if ($portalLocked): ?>
                        <?= $this->render('../layouts/_portal-terminated-banner') ?>
                    <?php endif; ?>
                    <section class="flex flex-col mb-4">
                        <div class="flex w-full flex-col gap-6 md:flex-row md:items-center justify-between">
                            <div class="flex gap-6 items-center">
                                <?= $this->render('../layouts/_initials-avatar', [
                                    'name' => $employee->full_name,
                                    'sizeClass' => 'size-32',
                                    'rounded' => 'rounded-2xl',
                                    'textClass' => 'text-2xl',
                                ]) ?>
                                <div class="flex flex-col gap-1">
                                    <h1 class="text-slate-900 dark:text-white text-3xl font-extrabold leading-tight tracking-tight font-display">
                                        <?= Html::encode($employee->full_name) ?>
                                    </h1>
                                    <p class="text-primary font-semibold text-lg">
                                        <?= Html::encode($employee->job_title) ?>
                                        <div class="mt-2">
                                            <span class="px-3 py-1 text-xs font-bold rounded-full <?= Html::encode($statusClasses) ?>">
                                                <?= Html::encode($employee->displayStatus) ?>
                                            </span>
                                        </div>
                                    </p>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-slate-500 dark:text-slate-400 text-sm mt-1">
                                        <span>
                                            <span class="font-semibold text-slate-700 dark:text-slate-300"><?= Html::encode($departmentLabel) ?>:</span>
                                            <?= Html::encode($departmentName) ?>
                                        </span>
                                        <?php if ($isDepartmentHead): ?>
                                            <span>
                                                <span class="font-semibold text-slate-700 dark:text-slate-300"><?= Html::encode($headOfLabel) ?>:</span>
                                                <?= Html::encode($managedDepartmentsText) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="flex flex-col gap-8">
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-10 shadow-sm">
                            <div class="flex items-center gap-4 mb-10">
                                <div class="size-11 rounded-xl bg-[#eb652c]/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[#eb652c] text-2xl">person</span>
                                </div>
                                <h3 class="text-2xl font-bold text-[#141121] dark:text-white font-display">Personal Information</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
                                <div class="flex flex-col gap-2">
                                    <span class="text-slate-700 dark:text-slate-300 text-sm font-bold">Full Name<span class="text-rose-500 ml-1">*</span></span>
                                    <div class="flex items-center h-[52px] px-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 font-medium w-full shadow-sm bg-slate-50/50 cursor-default">
                                        <?= Html::encode($employee->full_name) ?>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-slate-700 dark:text-slate-300 text-sm font-bold">Email<span class="text-rose-500 ml-1">*</span></span>
                                    <div class="flex items-center h-[52px] px-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 font-medium w-full shadow-sm bg-slate-50/50 cursor-default">
                                        <?= Html::encode($employee->email) ?>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-slate-700 dark:text-slate-300 text-sm font-bold">Phone Number</span>
                                    <div class="flex items-center h-[52px] px-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 font-medium w-full shadow-sm bg-slate-50/50 cursor-default">
                                        <?= Html::encode($employee->hasAttribute('phone') && $employee->phone ? $employee->phone : '—') ?>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-slate-700 dark:text-slate-300 text-sm font-bold">Marital Status</span>
                                    <div class="flex items-center h-[52px] px-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 font-medium w-full shadow-sm bg-slate-50/50 cursor-default justify-between">
                                        <span><?= Html::encode($employee->getDisplayMaritalStatus()) ?></span>
                                        <span class="material-symbols-outlined text-slate-400"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-10 shadow-sm">
                            <div class="flex items-center gap-4 mb-10">
                                <div class="size-11 rounded-xl bg-[#eb652c]/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[#eb652c] text-2xl">work</span>
                                </div>
                                <h3 class="text-2xl font-bold text-[#141121] dark:text-white font-display">Professional Details</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
                                <div class="flex flex-col gap-2">
                                    <span class="text-slate-700 dark:text-slate-300 text-sm font-bold">Job Title</span>
                                    <div class="flex items-center h-[52px] px-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 font-medium w-full shadow-sm bg-slate-50/50 cursor-default">
                                        <?= Html::encode($employee->job_title) ?>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-slate-700 dark:text-slate-300 text-sm font-bold">Position Level</span>
                                    <div class="flex items-center h-[52px] px-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 font-medium w-full shadow-sm bg-slate-50/50 cursor-default justify-between">
                                        <span><?= Html::encode($employee->getDisplayPositionLevel()) ?></span>
                                        <span class="material-symbols-outlined text-slate-400"></span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-slate-700 dark:text-slate-300 text-sm font-bold">Joining Date<span class="text-rose-500 ml-1">*</span></span>
                                    <div class="flex items-center h-[52px] px-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 font-medium w-full shadow-sm bg-slate-50/50 cursor-default justify-between">
                                        <span><?= Html::encode($joiningDateText) ?></span>
                                        <span class="material-symbols-outlined text-slate-400 text-xl">calendar_today</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-slate-700 dark:text-slate-300 text-sm font-bold">Employment Type</span>
                                    <div class="flex items-center h-[52px] px-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 font-medium w-full shadow-sm bg-slate-50/50 cursor-default justify-between">
                                        <span><?= Html::encode($employee->getDisplayEmploymentType()) ?></span>
                                        <span class="material-symbols-outlined text-slate-400"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-10 shadow-sm">
                            <div class="flex items-center gap-4 mb-10">
                                <div class="size-11 rounded-xl bg-[#eb652c]/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[#eb652c] text-2xl">account_tree</span>
                                </div>
                                <h3 class="text-2xl font-bold text-[#141121] dark:text-white font-display">Reporting Structure</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-10">
                                <div class="flex flex-col gap-2">
                                    <span class="text-slate-700 dark:text-slate-300 text-sm font-bold">Manager<span class="text-rose-500 ml-1">*</span></span>
                                    <div class="flex items-center h-[52px] px-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 font-medium w-full shadow-sm bg-slate-50/50 cursor-default gap-3">
                                        <?= $this->render('../layouts/_initials-avatar', [
                                            'name' => $managerName !== 'No Manager Assigned' ? $managerName : '',
                                            'sizeClass' => 'size-8',
                                            'textClass' => 'text-xs',
                                        ]) ?>
                                        <span><?= Html::encode($managerName) ?></span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-slate-700 dark:text-slate-300 text-sm font-bold"><?= Html::encode($departmentLabel) ?><span class="text-rose-500 ml-1">*</span></span>
                                    <div class="flex items-center h-[52px] px-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 font-medium w-full shadow-sm bg-slate-50/50 cursor-default justify-between">
                                        <span><?= Html::encode($departmentName) ?></span>
                                        <span class="material-symbols-outlined text-slate-400"></span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-slate-700 dark:text-slate-300 text-sm font-bold"><?= Html::encode($departmentHeadLabel) ?></span>
                                    <div class="flex items-center h-[52px] px-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 font-medium w-full shadow-sm bg-slate-50/50 cursor-default">
                                        <?= $isDepartmentHead ? 'Yes' : 'No' ?>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <span class="text-slate-700 dark:text-slate-300 text-sm font-bold"><?= Html::encode($managedDepartmentsLabel) ?></span>
                                    <div class="flex items-center min-h-[52px] px-4 py-3 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 font-medium w-full shadow-sm bg-slate-50/50 cursor-default">
                                        <?php if ($isDepartmentHead): ?>
                                            <div class="flex flex-wrap gap-2">
                                                <?php foreach ($managedDepartmentNames as $deptName): ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-[#eb652c]/10 text-[#eb652c] border border-[#eb652c]/20">
                                                        <?= Html::encode($deptName) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-slate-500">—</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-span-full flex flex-col gap-2">
                                    <span class="text-slate-700 dark:text-slate-300 text-sm font-bold">Work Location</span>
                                    <div class="flex items-center gap-10 mt-2">
                                        <label class="flex items-center gap-3 cursor-default">
                                            <div class="size-5 rounded-full border <?= Html::encode($locationUi['remote']['dotClass']) ?> bg-white"></div>
                                            <span class="text-sm <?= Html::encode($locationUi['remote']['textClass']) ?>">Remote</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-default">
                                            <div class="size-5 rounded-full border <?= Html::encode($locationUi['hybrid']['dotClass']) ?> bg-white"></div>
                                            <span class="text-sm <?= Html::encode($locationUi['hybrid']['textClass']) ?>">Hybrid</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-default">
                                            <div class="size-5 rounded-full border <?= Html::encode($locationUi['onsite']['dotClass']) ?> bg-white"></div>
                                            <span class="text-sm <?= Html::encode($locationUi['onsite']['textClass']) ?>">On-site</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!$portalLocked): ?>
                        <div class="bg-[#642754]/5 dark:bg-[#642754]/10 rounded-2xl p-8 border border-[#642754]/20 flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex flex-col gap-1.5">
                                <h4 class="text-slate-900 dark:text-white font-bold text-lg">Need to update info?</h4>
                                <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed max-w-xl">
                                    Personal information can only be edited through HR approval to maintain data integrity and regulatory compliance.
                                </p>
                            </div>
                            <a href="<?= Url::to(['/employee/employee/request']) ?>"
                               class="whitespace-nowrap px-8 py-3 bg-white dark:bg-slate-800 border-2 border-[#642754] rounded-xl text-[#642754] text-sm font-bold hover:bg-[#642754]/5 transition-colors shadow-sm inline-block text-center">
                                Open Request
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
<?= $this->render('../layouts/_shell-end') ?>

