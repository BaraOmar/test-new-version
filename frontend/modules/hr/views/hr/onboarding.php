<?php

/**
 * Legacy UI reference: `Legacy_Code/HRMS/On_board.php`
 *
 * @var yii\web\View $this
 * @var frontend\modules\hr\models\OnboardingEmployeeForm $model
 * @var bool $isEdit
 * @var int|null $employeeId
 * @var common\modules\employee\models\Department[] $departments
 * @var common\modules\employee\models\SchoolDepartment[] $schoolDepartments
 * @var array<int, object> $departmentOptions
 * @var common\modules\employee\models\Employee[] $managers
 * @var string $userName
 * @var array $errorList
 * @var array $managersByDepartment
 * @var bool $hasJoiningDate
 * @var bool $hasLinkedUser
 * @var bool $hasEmployeeCategory
 * @var bool $usesSchoolDepartments
 * @var bool $allowTeacherTypeSwitch
 * @var array<int, array<int, array{id:int,name:string}>> $subjectsByDepartment
 * @var bool $hasTeacherSubjects
 * @var string $portalEmailDomain
 * @var bool $hasPersonalEmail
 * @var bool $hasPortalCredentials
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $isEdit ? 'Edit Employee' : 'Onboarding';

$hasErrors = !empty($errorList);
$hasTeacherField = !empty($hasEmployeeCategory);
$teacherValue = (int)$model->teacher === 1 ? 1 : 0;

?>

<div class="flex h-screen overflow-hidden w-full">
    <?= $this->render('../layouts/_navbar', ['currentPage' => 'employees']) ?>
    <div class="flex-1 min-w-0 overflow-auto">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => $this->title, 'userName' => $userName]) ?>

        <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="flex mb-6 text-sm font-medium text-slate-500 dark:text-slate-400">
                <ol class="flex items-center space-x-2">
                    <li><a class="hover:text-primary" href="<?= Url::to(['/hr/hr/employees']) ?>">Employee Directory</a></li>
                    <li class="flex items-center space-x-2">
                        <span class="material-symbols-outlined text-base text-slate-300">chevron_right</span>
                        <span class="text-slate-900 dark:text-slate-100"><?= Html::encode($isEdit ? 'edit profile' : 'add employee') ?></span>
                    </li>
                </ol>
            </nav>

            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        <?= Html::encode($isEdit ? 'Edit Employee Profile' : 'Employee Onboarding') ?>
                    </h1>
                    <p class="mt-2 text-slate-600 dark:text-slate-400">
                        <?= Html::encode($isEdit
                            ? 'Update personal, professional, and reporting information for this employee.'
                            : 'Choose employee or teacher, then complete the onboarding details below.') ?>
                    </p>
                </div>
            </div>

            <div class="<?= $hasErrors ? '' : 'hidden' ?> mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                <ul class="list-disc pl-5 space-y-1">
                    <?php foreach ($errorList as $attrErrors): ?>
                        <?php foreach ($attrErrors as $msg): ?>
                            <li><?= Html::encode($msg) ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ($isEdit && !$hasLinkedUser && $hasPortalCredentials): ?>
                <div class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-amber-600">info</span>
                        <div class="text-sm leading-relaxed">
                            <div class="font-bold">This employee does not have a portal login yet.</div>
                            <div class="mt-1">
                                Portal credentials will be generated automatically when you save.
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?= Html::beginForm(['onboarding', 'id' => $employeeId], 'post') ?>

            <div class="space-y-8">
                <?php if (!$isEdit && $hasTeacherField): ?>
                    <section class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Create as</h2>
                            <p class="text-sm text-slate-500 mt-1">Choose what type of employee you are adding.</p>
                        </div>
                        <div class="p-6 sm:p-8">
                            <?= Html::activeHiddenInput($model, 'teacher', ['id' => 'onboarding-teacher-value', 'value' => $teacherValue]) ?>
                            <div class="flex flex-wrap gap-3">
                                <button type="button"
                                        id="onboarding-type-employee"
                                        data-teacher="0"
                                        class="onboarding-type-btn inline-flex items-center justify-center h-11 px-6 rounded-lg border-2 text-sm font-bold transition-all <?= $teacherValue === 0 ? 'border-[#642754] bg-[#642754] text-white' : 'border-slate-200 text-slate-700 hover:border-[#642754]/40' ?>">
                                    Employee
                                </button>
                                <button type="button"
                                        id="onboarding-type-teacher"
                                        data-teacher="1"
                                        class="onboarding-type-btn inline-flex items-center justify-center h-11 px-6 rounded-lg border-2 text-sm font-bold transition-all <?= $teacherValue === 1 ? 'border-[#642754] bg-[#642754] text-white' : 'border-slate-200 text-slate-700 hover:border-[#642754]/40' ?>">
                                    Teacher
                                </button>
                            </div>
                            <?= Html::error($model, 'teacher', ['class' => 'text-red-600 text-xs mt-3']) ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <?= Html::activeTextInput($model, 'full_name', [
                                    'required' => true,
                                    'class' => 'w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11',
                                ]) ?>
                                <?= Html::error($model, 'full_name', ['class' => 'text-red-600 text-xs mt-1']) ?>
                            </div>

                            <?php if ($hasPersonalEmail): ?>
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Personal Email</label>
                                    <?= Html::activeInput('email', $model, 'personal_email', [
                                        'class' => 'w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11',
                                        'placeholder' => 'name@example.com',
                                    ]) ?>
                                    <p class="text-xs text-slate-500">Used for communication only. Not used for portal login.</p>
                                    <?= Html::error($model, 'personal_email', ['class' => 'text-red-600 text-xs mt-1']) ?>
                                </div>
                            <?php endif; ?>

                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Phone Number</label>
                                <?= Html::activeTextInput($model, 'phone', [
                                    'class' => 'w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11',
                                    'data-digits-only' => true,
                                    'inputmode' => 'numeric',
                                    'pattern' => '[0-9]*',
                                ]) ?>
                                <?= Html::error($model, 'phone', ['class' => 'text-red-600 text-xs mt-1']) ?>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Marital Status</label>
                                <?= Html::activeDropDownList($model, 'marital_status', [
                                    '' => 'Select status',
                                    'single' => 'Single',
                                    'married' => 'Married',
                                    'divorced' => 'Divorced',
                                    'widowed' => 'Widowed',
                                ], [
                                    'class' => 'w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11',
                                ]) ?>
                            </div>
                        </div>
                    </div>
                </section>

                <?php if ($hasPortalCredentials): ?>
                    <?php
                    $portalEmailValue = $isEdit
                        ? trim((string)$model->portal_email)
                        : 'firstname.family.{numbers}@' . $portalEmailDomain;
                    $portalPasswordValue = (string)$model->portal_password;
                    ?>
                    <?= $this->render('_employee-portal-credentials', [
                        'mode' => $isEdit ? 'edit' : 'create',
                        'loginEmail' => $portalEmailValue,
                        'loginPassword' => $portalPasswordValue,
                        'passwordInputId' => 'employee-portal-password',
                        'passwordFieldName' => Html::getInputName($model, 'portal_password'),
                        'passwordEditable' => $isEdit,
                        'emailHint' => $isEdit
                            ? 'Auto-generated from the employee name. Not editable here.'
                            : 'Generated automatically when you create the employee.',
                        'passwordHint' => !$isEdit
                            ? 'Auto-generated. You can view it here; it will be saved when you create the employee.'
                            : (trim($portalPasswordValue) !== ''
                                ? 'You can view or change this password. Leave unchanged to keep the current one.'
                                : 'Password was changed by the employee or is unavailable. Enter a new password to reset it.'),
                    ]) ?>
                    <?= Html::error($model, 'portal_email', ['class' => 'text-red-600 text-xs mt-1']) ?>
                    <?= Html::error($model, 'portal_password', ['class' => 'text-red-600 text-xs mt-1']) ?>
                <?php endif; ?>

                <section class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Job Title <span class="text-red-500">*</span></label>
                                <?= Html::activeTextInput($model, 'job_title', [
                                    'required' => true,
                                    'class' => 'w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11',
                                ]) ?>
                                <?= Html::error($model, 'job_title', ['class' => 'text-red-600 text-xs mt-1']) ?>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Position Level</label>
                                <?= Html::activeDropDownList($model, 'position_level', [
                                    '' => 'Select level',
                                    'l1' => 'Entry (L1)',
                                    'l2' => 'Intermediate (L2)',
                                    'l3' => 'Senior (L3)',
                                    'l4' => 'Staff (L4)',
                                    'l5' => 'Director (L5)',
                                ], [
                                    'class' => 'w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11',
                                ]) ?>
                            </div>

                            <?php if ($hasJoiningDate): ?>
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Joining Date <span class="text-red-500">*</span></label>
                                    <?= Html::activeInput('date', $model, 'joining_date', [
                                        'required' => true,
                                        'max' => date('Y-m-d'),
                                        'class' => 'w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11',
                                    ]) ?>
                                    <?= Html::error($model, 'joining_date', ['class' => 'text-red-600 text-xs mt-1']) ?>
                                </div>
                            <?php endif; ?>

                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Employment Type</label>
                                <?= Html::activeDropDownList($model, 'employment_type', [
                                    '' => 'Select type',
                                    'full-time' => 'Full-time',
                                    'part-time' => 'Part-time',
                                    'contract' => 'Contract',
                                    'intern' => 'Intern',
                                ], [
                                    'class' => 'w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11',
                                ]) ?>
                                <?= Html::error($model, 'employment_type', ['class' => 'text-red-600 text-xs mt-1']) ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Department</label>
                                <select id="department-select" name="<?= Html::encode(Html::getInputName($model, 'department_id')) ?>"
                                        class="w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11">
                                    <option value="">Select department</option>
                                    <?php foreach ($departmentOptions as $dept): ?>
                                        <option value="<?= (int)$dept->id ?>" <?= ((int)$dept->id === (int)($model->department_id ?? 0)) ? 'selected' : '' ?>>
                                            <?= Html::encode($dept->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Manager</label>
                                <select id="manager-select" name="<?= Html::encode(Html::getInputName($model, 'manager_id')) ?>"
                                        class="w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11"
                                        <?= (int)($model->department_id ?? 0) <= 0 ? 'disabled' : '' ?>>
                                    <option value="">Select manager</option>
                                    <?php foreach ($managers as $mgr): ?>
                                        <option value="<?= (int)$mgr->id ?>" <?= ((int)$mgr->id === (int)($model->manager_id ?? 0)) ? 'selected' : '' ?>>
                                            <?= Html::encode($mgr->full_name) ?><?= $mgr->job_title ? ' (' . Html::encode($mgr->job_title) . ')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p id="manager-hint" class="text-xs text-slate-500 <?= (int)($model->department_id ?? 0) > 0 ? 'hidden' : '' ?>">Select a department first to choose a manager.</p>
                            </div>

                            <?php if (!empty($hasTeacherSubjects)): ?>
                                <div id="teacher-subjects-field" class="flex flex-col gap-2 md:col-span-2 hidden">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Subjects</label>
                                    <div id="teacher-subjects-list"
                                         class="grid grid-cols-1 sm:grid-cols-2 gap-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 p-4 min-h-[3rem]">
                                    </div>
                                    <p id="teacher-subjects-empty" class="text-xs text-slate-500 hidden">No subjects are configured for this department yet.</p>
                                    <p id="teacher-subjects-hint" class="text-xs text-slate-500">Select a department first to choose subjects.</p>
                                    <?= Html::error($model, 'subject_ids', ['class' => 'text-red-600 text-xs mt-1']) ?>
                                </div>
                            <?php endif; ?>

                            <?php $workLocation = $model->workLocationValue(); ?>
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Work Location</label>
                                <div class="flex gap-6">
                                    <label><input type="radio" name="<?= Html::encode(Html::getInputName($model, 'location')) ?>" value="remote" <?= $workLocation === 'remote' ? 'checked' : '' ?>> Remote</label>
                                    <label><input type="radio" name="<?= Html::encode(Html::getInputName($model, 'location')) ?>" value="hybrid" <?= $workLocation === 'hybrid' ? 'checked' : '' ?>> Hybrid</label>
                                    <label><input type="radio" name="<?= Html::encode(Html::getInputName($model, 'location')) ?>" value="onsite" <?= $workLocation === 'onsite' ? 'checked' : '' ?>> On-site</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-between pt-6">
                    <a href="<?= Url::to(['/hr/hr/employees']) ?>" class="text-slate-500 font-semibold">Cancel</a>
                    <button type="submit" id="saveBtn"
                            class="px-8 py-3 bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold rounded-lg shadow-md transition-all active:scale-95">
                        Save Changes
                    </button>
                </div>
            </div>

            <?= Html::endForm() ?>
        </main>
    </div>
</div>

<?php
$currentManagerId = (int)($model->manager_id ?? 0);
$mapJson = json_encode($hrManagersByDepartment ?? $managersByDepartment, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$schoolMapJson = json_encode($schoolManagersByDepartment ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$employeeDepartmentsJson = json_encode(array_map(static function ($dept) {
    return ['id' => (int)$dept->id, 'name' => (string)$dept->name];
}, $departments), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$schoolDepartmentsJson = json_encode(array_map(static function ($dept) {
    return ['id' => (int)$dept->id, 'name' => (string)$dept->name];
}, $schoolDepartments ?? []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$subjectsByDepartmentJson = json_encode($subjectsByDepartment ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$selectedSubjectIdsJson = json_encode(array_map('intval', $model->subject_ids ?? []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$subjectFieldName = Html::getInputName($model, 'subject_ids');
$subjectFieldNameJson = json_encode($subjectFieldName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$hasTeacherSubjectsJs = !empty($hasTeacherSubjects) ? 'true' : 'false';
$isTeacherFlow = !empty($usesSchoolDepartments);
$allowTeacherTypeSwitch = !empty($allowTeacherTypeSwitch);
$isTeacherFlowJs = $isTeacherFlow ? 'true' : 'false';
$allowTeacherTypeSwitchJs = $allowTeacherTypeSwitch ? 'true' : 'false';
$js = <<<JS
(function() {
  var dept = document.getElementById('department-select');
  var mgr = document.getElementById('manager-select');
  var employeeDepartments = {$employeeDepartmentsJson} || [];
  var schoolDepartments = {$schoolDepartmentsJson} || [];
  var subjectsByDepartment = {$subjectsByDepartmentJson} || {};
  var selectedSubjectIds = {$selectedSubjectIdsJson} || [];
  var subjectFieldName = {$subjectFieldNameJson};
  var hasTeacherSubjects = {$hasTeacherSubjectsJs};
  var employeeManagerMap = {$mapJson} || {};
  var schoolManagerMap = {$schoolMapJson} || {};
  var isTeacherFlow = {$isTeacherFlowJs};
  var allowTeacherTypeSwitch = {$allowTeacherTypeSwitchJs};

  function usesSchoolDepartments() {
    if (!allowTeacherTypeSwitch) {
      return isTeacherFlow;
    }
    var teacherInput = document.getElementById('onboarding-teacher-value');
    return teacherInput && String(teacherInput.value) === '1';
  }

  function getActiveManagerMap() {
    return usesSchoolDepartments() ? schoolManagerMap : employeeManagerMap;
  }

  function rebuildDepartments(items, selectedId) {
    if (!dept) return;
    var html = '<option value="">Select department</option>';
    for (var i = 0; i < items.length; i++) {
      var item = items[i];
      var selected = selectedId && String(item.id) === String(selectedId) ? ' selected' : '';
      html += '<option value="' + String(item.id) + '"' + selected + '>' + String(item.name) + '</option>';
    }
    dept.innerHTML = html;
  }

  var subjectsField = document.getElementById('teacher-subjects-field');
  var subjectsList = document.getElementById('teacher-subjects-list');
  var subjectsEmpty = document.getElementById('teacher-subjects-empty');
  var subjectsHint = document.getElementById('teacher-subjects-hint');

  function getSelectedSubjectIds() {
    if (!subjectsList) return [];
    return Array.prototype.slice.call(subjectsList.querySelectorAll('input[type="checkbox"]:checked'))
      .map(function(input) { return parseInt(input.value, 10); })
      .filter(function(id) { return !isNaN(id) && id > 0; });
  }

  function renderSubjectOptions(deptId, selectedIds) {
    if (!hasTeacherSubjects || !subjectsList) return;

    var selected = Array.isArray(selectedIds) ? selectedIds.slice() : [];
    var items = deptId ? (subjectsByDepartment[String(deptId)] || subjectsByDepartment[deptId] || []) : [];
    if (items.length === 0) {
      subjectsList.innerHTML = '';
      if (subjectsEmpty) subjectsEmpty.classList.toggle('hidden', !(usesSchoolDepartments() && deptId));
    } else {
      subjectsList.innerHTML = items.map(function(item) {
        var checked = selected.indexOf(parseInt(item.id, 10)) !== -1 ? ' checked' : '';
        return '<label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white dark:bg-slate-900 px-3 py-2 cursor-pointer">' +
          '<input type="checkbox" name="' + subjectFieldName + '[]" value="' + String(item.id) + '"' + checked +
          ' class="rounded border-slate-300">' +
          '<span class="text-sm font-medium text-slate-800 dark:text-slate-100">' + String(item.name) + '</span>' +
        '</label>';
      }).join('');
      if (subjectsEmpty) subjectsEmpty.classList.add('hidden');
    }
  }

  function syncTeacherSubjectsField() {
    if (!hasTeacherSubjects || !subjectsField) return;

    var isTeacher = usesSchoolDepartments();
    var deptId = dept ? dept.value : '';
    subjectsField.classList.toggle('hidden', !isTeacher || !deptId);

    if (!isTeacher) {
      if (subjectsList) subjectsList.innerHTML = '';
      if (subjectsHint) {
        subjectsHint.textContent = 'Select teacher and department to choose subjects.';
        subjectsHint.classList.remove('hidden');
      }
      if (subjectsEmpty) subjectsEmpty.classList.add('hidden');
      return;
    }

    if (!deptId) {
      if (subjectsList) subjectsList.innerHTML = '';
      if (subjectsHint) {
        subjectsHint.textContent = 'Select a department first to choose subjects.';
        subjectsHint.classList.remove('hidden');
      }
      if (subjectsEmpty) subjectsEmpty.classList.add('hidden');
      return;
    }

    if (subjectsHint) subjectsHint.classList.add('hidden');
    renderSubjectOptions(deptId, getSelectedSubjectIds().length ? getSelectedSubjectIds() : selectedSubjectIds);
  }

  if (dept && mgr) {
    var map = employeeManagerMap;
    var hint = document.getElementById('manager-hint');

    function setOptions(items, keepSelection) {
      var keep = keepSelection ? {$currentManagerId} : 0;
      var html = '<option value="">Select manager</option>';
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        var selected = (keep && String(it.id) === String(keep)) ? ' selected' : '';
        html += '<option value="' + String(it.id) + '"' + selected + '>' + String(it.label) + '</option>';
      }
      mgr.innerHTML = html;
    }

    function loadManagers() {
      var deptId = dept.value;
      if (!deptId) {
        mgr.innerHTML = '<option value="">Select manager</option>';
        mgr.value = '';
        mgr.disabled = true;
        if (hint) hint.classList.remove('hidden');
        return;
      }
      mgr.disabled = false;
      if (hint) hint.classList.add('hidden');
      var activeMap = getActiveManagerMap();
      var items = activeMap[String(deptId)] || activeMap[deptId] || [];
      setOptions(items, true);
      if (items.length === 1) {
        mgr.value = String(items[0].id);
      }
    }

    dept.addEventListener('change', function() {
      mgr.value = '';
      loadManagers();
      renderSubjectOptions(dept.value, []);
      syncTeacherSubjectsField();
    });
    loadManagers();
  }

  syncTeacherSubjectsField();

  var teacherInput = document.getElementById('onboarding-teacher-value');
  var typeButtons = document.querySelectorAll('.onboarding-type-btn');

  function setTeacherType(value) {
    if (!teacherInput) return;
    teacherInput.value = String(value);
    typeButtons.forEach(function(btn) {
      var active = String(btn.getAttribute('data-teacher')) === String(value);
      btn.classList.toggle('border-[#642754]', active);
      btn.classList.toggle('bg-[#642754]', active);
      btn.classList.toggle('text-white', active);
      btn.classList.toggle('border-slate-200', !active);
      btn.classList.toggle('text-slate-700', !active);
    });

    if (allowTeacherTypeSwitch && dept && mgr) {
      var nextDepartments = String(value) === '1' ? schoolDepartments : employeeDepartments;
      rebuildDepartments(nextDepartments, '');
      dept.value = '';
      mgr.value = '';
      mgr.disabled = true;
      var hint = document.getElementById('manager-hint');
      if (hint) hint.classList.remove('hidden');
      selectedSubjectIds = [];
      syncTeacherSubjectsField();
    }
  }

  if (typeButtons.length) {
    typeButtons.forEach(function(btn) {
      btn.addEventListener('click', function() {
        setTeacherType(btn.getAttribute('data-teacher'));
      });
    });
  }
})();
JS;
$this->registerJs($js);
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

