<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudent[] $students
 * @var yii\data\Pagination $pagination
 * @var int $totalCount
 * @var string $search
 * @var int $departmentFilter
 * @var int $gradeFilter
 * @var int $sectionFilter
 * @var string $statusFilter
 * @var common\modules\employee\models\SchoolDepartment[] $departments
 * @var common\modules\employee\models\SchoolGrade[] $grades
 * @var array<int, string> $sections
 * @var array<int, array<int, array{id:int,name:string}>> $gradesByDepartment
 * @var array<int, array<int, array{id:int,name:string}>> $sectionsByGrade
 * @var string $userName
 * @var string $currentPage
 */

use common\modules\employee\models\SchoolStudent;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Student Affairs | CultureHR';

$departmentOptions = ArrayHelper::map($departments, 'id', 'name');
$gradeOptions = ArrayHelper::map($grades, 'id', 'name');
$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');
$hasActiveFilters = $search !== ''
    || $departmentFilter > 0
    || $gradeFilter > 0
    || $sectionFilter > 0
    || $statusFilter !== '';
$resetFiltersUrl = Url::to(['/hr/student-affairs/students']);
$studentViewReturnUrl = Yii::$app->request->url;
$from = $totalCount > 0 ? $pagination->offset + 1 : 0;
$to = min($pagination->offset + count($students), $totalCount);
$gradesByDepartmentJson = Json::htmlEncode($gradesByDepartment);
$sectionsByGradeJson = Json::htmlEncode($sectionsByGrade);
$selectedDepartmentIdJson = Json::htmlEncode($departmentFilter ?: '');
$selectedGradeIdJson = Json::htmlEncode($gradeFilter ?: '');
$selectedSectionIdJson = Json::htmlEncode($sectionFilter ?: '');

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'student_affairs']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Student Registry', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 space-y-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Student Registry</h1>
                        <p class="text-slate-500 mt-2">Manage enrollment, student profiles, and administrative records.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button"
                                onclick="alert('Excel import will be available in a future update.');"
                                class="inline-flex items-center gap-2 h-10 px-4 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-sm font-semibold text-slate-700 cursor-pointer">
                            <span class="material-symbols-outlined text-base">upload_file</span>
                            Import Excel
                        </button>
                        <a href="<?= Html::encode(Url::to(['/hr/student-affairs/export-excel'] + Yii::$app->request->queryParams)) ?>"
                           class="inline-flex items-center gap-2 h-10 px-4 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-sm font-semibold text-slate-700 no-underline">
                            <span class="material-symbols-outlined text-base">download</span>
                            Export Excel
                        </a>
                        <a href="<?= Html::encode(Url::to(['/hr/student-affairs/export-pdf'])) ?>"
                           target="_blank"
                           class="inline-flex items-center gap-2 h-10 px-4 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-sm font-semibold text-slate-700 no-underline">
                            <span class="material-symbols-outlined text-base">picture_as_pdf</span>
                            Export PDF
                        </a>
                        <a href="<?= Html::encode(Url::to(['/hr/student-affairs/create'])) ?>"
                           class="inline-flex items-center gap-2 h-10 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold shadow-md no-underline">
                            <span class="material-symbols-outlined text-base">person_add</span>
                            Add New Student
                        </a>
                    </div>
                </div>

                <?php if ($successMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm"><?= Html::encode($successMessage) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm"><?= Html::encode($errorMessage) ?></div>
                <?php endif; ?>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <?= Html::beginForm(['students'], 'get', ['class' => 'space-y-4']) ?>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Student Name</label>
                            <?= Html::textInput('search', $search, [
                                'placeholder' => 'e.g. Ahmad Ali',
                                'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                            ]) ?>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 items-end">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Department</label>
                                <?= Html::dropDownList('department_id', $departmentFilter ?: '', ['' => 'All Departments'] + $departmentOptions, [
                                    'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                                    'id' => 'student-filter-department-id',
                                ]) ?>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Grade</label>
                                <?= Html::dropDownList('grade_id', $departmentFilter > 0 ? ($gradeFilter ?: '') : '', ['' => 'All Grades'] + ($departmentFilter > 0 ? $gradeOptions : []), [
                                    'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                                    'id' => 'student-filter-grade-id',
                                    'disabled' => $departmentFilter <= 0,
                                ]) ?>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Section</label>
                                <?= Html::dropDownList('section_id', $sectionFilter ?: '', ['' => 'All Sections'] + $sections, [
                                    'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                                    'id' => 'student-filter-section-id',
                                ]) ?>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Status</label>
                                <?= Html::dropDownList('status', $statusFilter, ['' => 'All Statuses'] + SchoolStudent::statusOptions(), [
                                    'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                                ]) ?>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                            <button type="submit" class="inline-flex items-center gap-2 h-11 px-4 rounded-lg bg-[#1e3a8a] text-white text-sm font-bold border-0 cursor-pointer">
                                Apply Filters
                            </button>
                            <?php if ($hasActiveFilters): ?>
                                <a href="<?= Html::encode($resetFiltersUrl) ?>"
                                   class="inline-flex items-center gap-2 h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-sm font-semibold no-underline">
                                    Reset Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    <?= Html::endForm() ?>
                </section>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50/80">
                                    <th class="px-5 py-3.5">Reg #</th>
                                    <th class="px-5 py-3.5">Student</th>
                                    <th class="px-5 py-3.5">Class / Year</th>
                                    <th class="px-5 py-3.5">Department</th>
                                    <th class="px-5 py-3.5">Status</th>
                                    <th class="px-5 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if ($students === []): ?>
                                    <tr>
                                        <td colspan="6" class="px-5 py-12 text-center text-slate-400">No students found.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($students as $student): ?>
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-5 py-4 font-mono text-xs text-slate-600 whitespace-nowrap"><?= Html::encode($student->getRegistrationLabel()) ?></td>
                                        <td class="px-5 py-4">
                                            <div class="font-semibold text-slate-900"><?= Html::encode($student->getDisplayFullName()) ?></div>
                                        </td>
                                        <td class="px-5 py-4 text-slate-700"><?= Html::encode($student->getClassYearLabel()) ?></td>
                                        <td class="px-5 py-4 text-slate-700 whitespace-nowrap">
                                            <?= Html::encode($student->getDepartmentLabel()) ?>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex text-[10px] font-black uppercase tracking-wide px-2.5 py-1 rounded-full <?= Html::encode($student->getStatusBadgeClass()) ?>">
                                                <?= Html::encode(strtoupper($student->getStatusLabel())) ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="<?= Html::encode(Url::to(['/hr/student-affairs/view', 'id' => (int)$student->id, 'return' => $studentViewReturnUrl, 'from' => 'students'])) ?>"
                                                   class="inline-flex size-8 items-center justify-center rounded-md hover:bg-slate-100 text-slate-500 no-underline"
                                                   title="View">
                                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                                </a>
                                                <a href="<?= Html::encode(Url::to(['/hr/student-affairs/update', 'id' => (int)$student->id])) ?>"
                                                   class="inline-flex size-8 items-center justify-center rounded-md hover:bg-slate-100 text-slate-500 no-underline"
                                                   title="Edit">
                                                    <span class="material-symbols-outlined text-lg">edit</span>
                                                </a>
                                                <button type="button"
                                                        class="student-delete-btn inline-flex size-8 items-center justify-center rounded-md hover:bg-red-50 border-0 bg-transparent cursor-pointer text-red-500"
                                                        data-student-id="<?= (int)$student->id ?>"
                                                        data-student-name="<?= Html::encode($student->getDisplayFullName()) ?>"
                                                        title="Delete">
                                                    <span class="material-symbols-outlined text-lg">delete</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-slate-500">
                            Showing <?= (int)$from ?> to <?= (int)$to ?> of <?= (int)$totalCount ?> students
                        </p>
                        <?= LinkPager::widget([
                            'pagination' => $pagination,
                            'options' => ['class' => 'flex flex-wrap gap-1'],
                            'linkContainerOptions' => ['class' => 'inline-flex'],
                            'linkOptions' => ['class' => 'inline-flex items-center justify-center min-w-9 h-9 px-2 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 no-underline hover:bg-slate-50'],
                            'disabledListItemSubTagOptions' => ['class' => 'inline-flex items-center justify-center min-w-9 h-9 px-2 rounded-lg border border-slate-100 bg-slate-50 text-sm text-slate-400'],
                            'activePageCssClass' => 'active',
                        ]) ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<div id="student-delete-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/50" data-student-delete-close></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-xl p-6" role="dialog" aria-modal="true" aria-labelledby="student-delete-title">
            <div class="flex items-start gap-3 mb-4">
                <div class="size-10 rounded-full bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined">delete</span>
                </div>
                <div>
                    <h2 id="student-delete-title" class="text-lg font-black text-slate-900">Delete student?</h2>
                    <p class="text-sm text-slate-500 mt-1">
                        This will permanently remove
                        <strong id="student-delete-name" class="text-slate-800"></strong>
                        from the student registry.
                    </p>
                </div>
            </div>

            <p class="text-sm text-slate-600 mb-2">
                Type <span class="font-bold text-red-700">delete</span> to confirm.
            </p>
            <?= Html::beginForm(['delete', 'id' => 0], 'post', ['id' => 'student-delete-form']) ?>
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                <input type="text"
                       name="confirmation"
                       id="student-delete-confirmation"
                       autocomplete="off"
                       placeholder="delete"
                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm mb-5">
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="button"
                            class="inline-flex items-center h-11 px-5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-bold cursor-pointer"
                            data-student-delete-close>
                        Cancel
                    </button>
                    <button type="submit"
                            id="student-delete-submit"
                            class="hidden inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-red-600 text-white text-sm font-bold border-0 cursor-pointer"
                            disabled>
                        <span class="material-symbols-outlined text-lg">delete</span>
                        Delete Student
                    </button>
                </div>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>

<?php
$deleteUrlTemplateJs = Json::encode(Url::to(['delete', 'id' => '__ID__']));
$this->registerJs(<<<JS
(function () {
    var modal = document.getElementById('student-delete-modal');
    var form = document.getElementById('student-delete-form');
    var nameEl = document.getElementById('student-delete-name');
    var confirmInput = document.getElementById('student-delete-confirmation');
    var submitBtn = document.getElementById('student-delete-submit');
    var deleteUrlTemplate = {$deleteUrlTemplateJs};

    if (!modal || !form || !nameEl || !confirmInput || !submitBtn) {
        return;
    }

    function openModal(studentId, studentName) {
        form.action = deleteUrlTemplate.replace('__ID__', String(studentId));
        nameEl.textContent = studentName || 'this student';
        confirmInput.value = '';
        syncSubmitState();
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        confirmInput.focus();
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        confirmInput.value = '';
        syncSubmitState();
    }

    function syncSubmitState() {
        var confirmed = confirmInput.value.trim().toLowerCase() === 'delete';
        submitBtn.disabled = !confirmed;
        submitBtn.classList.toggle('hidden', !confirmed);
    }

    document.querySelectorAll('.student-delete-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(btn.getAttribute('data-student-id'), btn.getAttribute('data-student-name'));
        });
    });

    modal.querySelectorAll('[data-student-delete-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    confirmInput.addEventListener('input', syncSubmitState);

    form.addEventListener('submit', function (event) {
        if (confirmInput.value.trim().toLowerCase() !== 'delete') {
            event.preventDefault();
            syncSubmitState();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
})();
JS);
?>

<?php
$this->registerJs(<<<JS
(function() {
  var gradesByDepartment = {$gradesByDepartmentJson} || {};
  var sectionsByGrade = {$sectionsByGradeJson} || {};
  var departmentSelect = document.getElementById('student-filter-department-id');
  var gradeSelect = document.getElementById('student-filter-grade-id');
  var sectionSelect = document.getElementById('student-filter-section-id');

  function rebuildGrades(departmentId, selectedGradeId) {
    if (!gradeSelect) return;
    departmentId = String(departmentId || '');
    selectedGradeId = String(selectedGradeId || '');
    gradeSelect.innerHTML = '<option value="">All Grades</option>';
    if (!departmentId) {
      gradeSelect.disabled = true;
      gradeSelect.value = '';
      return;
    }
    gradeSelect.disabled = false;
    var items = gradesByDepartment[departmentId] || gradesByDepartment[parseInt(departmentId, 10)] || [];
    items.forEach(function(item) {
      var option = document.createElement('option');
      option.value = String(item.id);
      option.textContent = String(item.name);
      if (selectedGradeId && String(item.id) === selectedGradeId) {
        option.selected = true;
      }
      gradeSelect.appendChild(option);
    });
  }

  function rebuildSections(gradeId, selectedSectionId) {
    if (!sectionSelect) return;
    gradeId = String(gradeId || '');
    selectedSectionId = String(selectedSectionId || '');
    sectionSelect.innerHTML = '<option value="">All Sections</option>';
    if (!gradeId) {
      sectionSelect.disabled = true;
      return;
    }
    sectionSelect.disabled = false;
    var items = sectionsByGrade[gradeId] || sectionsByGrade[parseInt(gradeId, 10)] || [];
    items.forEach(function(item) {
      var option = document.createElement('option');
      option.value = String(item.id);
      option.textContent = 'Section ' + String(item.name);
      if (selectedSectionId && String(item.id) === selectedSectionId) {
        option.selected = true;
      }
      sectionSelect.appendChild(option);
    });
  }

  if (departmentSelect && gradeSelect && sectionSelect) {
    rebuildGrades({$selectedDepartmentIdJson}, {$selectedGradeIdJson});
    rebuildSections({$selectedGradeIdJson}, {$selectedSectionIdJson});
    departmentSelect.addEventListener('change', function() {
      rebuildGrades(this.value, '');
      rebuildSections('', '');
    });
    gradeSelect.addEventListener('change', function() {
      rebuildSections(this.value, '');
    });
  }
})();
JS
);
?>
