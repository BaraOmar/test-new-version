<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolDepartment[] $departments
 * @var array<int, array<int, array{id:int,name:string}>> $gradesByDepartment
 */

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Create Subject | LMS';

$departmentOptions = ArrayHelper::map($departments, 'id', 'name');
$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');
$gradesByDepartmentUrl = Url::to(['grades-by-department']);
$gradesByDepartmentJson = json_encode($gradesByDepartment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
<?= $this->render('../layouts/_page-start') ?>
    <nav class="text-sm text-slate-500">
        <a href="<?= Html::encode(Url::to(['index'])) ?>" class="text-slate-500 hover:text-slate-700 no-underline">Subjects</a>
        <span class="mx-2">/</span>
        <span class="text-slate-900 font-semibold">Create</span>
    </nav>

    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Create Subject</h2>
            <p class="text-slate-500 mt-2">Add a subject to your department and assign maximum marks for one or more grades.</p>
        </div>
        <a href="<?= Html::encode(Url::to(['index'])) ?>"
           class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold no-underline shadow-sm">
            <span class="material-symbols-outlined text-base">list</span>
            Back to Subjects
        </a>
    </header>

    <?php if ($successMessage !== ''): ?>
        <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm"><?= Html::encode($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm"><?= Html::encode($errorMessage) ?></div>
    <?php endif; ?>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <?= Html::beginForm(['create'], 'post', ['id' => 'create-subject-form', 'class' => 'space-y-5']) ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Department</label>
                    <?= Html::dropDownList('school_department_id', count($departments) === 1 ? (int)$departments[0]->id : '', ['' => 'Select Department'] + $departmentOptions, [
                        'required' => true,
                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm',
                        'id' => 'create-department-id',
                    ]) ?>
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Subject Name</label>
                    <?= Html::textInput('name', '', [
                        'required' => true,
                        'placeholder' => 'e.g. Advanced Physics',
                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm',
                    ]) ?>
                </div>
            </div>

            <div>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Assign Grades</label>
                        <p class="text-sm text-slate-500">Select one or more grades for this subject. Each selected grade gets its own maximum mark field.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="selected-grade-count" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">0 selected</span>
                        <button type="button" id="select-all-grades" class="h-9 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold cursor-pointer disabled:opacity-40" disabled>Select all</button>
                        <button type="button" id="clear-all-grades" class="h-9 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold cursor-pointer disabled:opacity-40" disabled>Clear all</button>
                    </div>
                </div>
                <div id="grade-credit-fields" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <p class="text-sm text-slate-400 italic md:col-span-2">Select a department to choose grades.</p>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3 pt-2">
                <a href="<?= Html::encode(Url::to(['index'])) ?>"
                   class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold no-underline shadow-sm">
                    <span class="material-symbols-outlined text-base">close</span>
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 h-11 px-5 rounded-xl bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold border-0 cursor-pointer shadow-md">
                    <span class="material-symbols-outlined text-base">add_circle</span>
                    Add Subject
                </button>
            </div>
        <?= Html::endForm() ?>
    </section>
<?= $this->render('../layouts/_page-end') ?>

<?php
$gradesByDepartmentUrlJson = json_encode($gradesByDepartmentUrl, JSON_UNESCAPED_SLASHES);
$singleDepartmentId = count($departments) === 1 ? (int)$departments[0]->id : 0;
$this->registerJs(<<<JS
(function() {
  var gradesByDepartment = {$gradesByDepartmentJson} || {};
  var gradesUrl = {$gradesByDepartmentUrlJson};
  var createDepartment = document.getElementById('create-department-id');
  var gradeFields = document.getElementById('grade-credit-fields');
  var selectedCount = document.getElementById('selected-grade-count');
  var selectAllBtn = document.getElementById('select-all-grades');
  var clearAllBtn = document.getElementById('clear-all-grades');
  var createForm = document.getElementById('create-subject-form');
  var initialDepartmentId = {$singleDepartmentId};

  function updateSelectedCount() {
    if (!selectedCount || !gradeFields) return;
    var count = gradeFields.querySelectorAll('.grade-select-checkbox:checked').length;
    selectedCount.textContent = count + ' selected';
  }

  function bindGradeRow(checkbox) {
    checkbox.addEventListener('change', function() {
      var row = checkbox.closest('[data-grade-row]');
      var maxScoreInput = row?.querySelector('.grade-max-score-input');
      if (!maxScoreInput) return;
      maxScoreInput.disabled = !checkbox.checked;
      maxScoreInput.required = checkbox.checked;
      if (!checkbox.checked) {
        maxScoreInput.value = '';
      } else if (maxScoreInput.value === '') {
        maxScoreInput.value = '100';
      }
      updateSelectedCount();
    });
  }

  function renderGradeFields(grades) {
    if (!gradeFields) return;

    var hasGrades = Array.isArray(grades) && grades.length > 0;
    if (selectAllBtn) selectAllBtn.disabled = !hasGrades;
    if (clearAllBtn) clearAllBtn.disabled = !hasGrades;

    if (!hasGrades) {
      gradeFields.innerHTML = '<p class="text-sm text-slate-400 italic md:col-span-2">No grades found for this department.</p>';
      updateSelectedCount();
      return;
    }

    gradeFields.innerHTML = grades.map(function(grade) {
      return '<div data-grade-row class="rounded-xl border border-slate-200 bg-slate-50 p-4">' +
        '<label class="flex items-center gap-3 cursor-pointer mb-3">' +
          '<input type="checkbox" name="grade_ids[]" value="' + grade.id + '" class="grade-select-checkbox rounded border-slate-300">' +
          '<span class="font-semibold text-slate-800">' + grade.name + '</span>' +
        '</label>' +
        '<label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Maximum Mark</label>' +
        '<input type="number" name="grade_max_scores[' + grade.id + ']" min="0.01" step="0.01" placeholder="e.g. 100" value="100" ' +
        'class="grade-max-score-input w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm disabled:bg-slate-100 disabled:text-slate-400" disabled>' +
      '</div>';
    }).join('');

    gradeFields.querySelectorAll('.grade-select-checkbox').forEach(bindGradeRow);
    updateSelectedCount();
  }

  function loadGrades(departmentId) {
    if (!departmentId) {
      if (gradeFields) {
        gradeFields.innerHTML = '<p class="text-sm text-slate-400 italic md:col-span-2">Select a department to choose grades.</p>';
      }
      if (selectAllBtn) selectAllBtn.disabled = true;
      if (clearAllBtn) clearAllBtn.disabled = true;
      updateSelectedCount();
      return;
    }

    var embedded = gradesByDepartment[String(departmentId)] || gradesByDepartment[departmentId] || [];
    if (embedded.length > 0) {
      renderGradeFields(embedded);
      return;
    }

    fetch(gradesUrl + '?department_id=' + encodeURIComponent(departmentId), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function(response) { return response.json(); })
      .then(function(data) { renderGradeFields(Array.isArray(data) ? data : []); })
      .catch(function() {
        if (gradeFields) gradeFields.innerHTML = '<p class="text-sm text-red-600 md:col-span-2">Unable to load grades.</p>';
      });
  }

  createDepartment?.addEventListener('change', function() {
    loadGrades(this.value);
  });

  selectAllBtn?.addEventListener('click', function() {
    gradeFields?.querySelectorAll('.grade-select-checkbox').forEach(function(checkbox) {
      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change'));
    });
  });

  clearAllBtn?.addEventListener('click', function() {
    gradeFields?.querySelectorAll('.grade-select-checkbox').forEach(function(checkbox) {
      checkbox.checked = false;
      checkbox.dispatchEvent(new Event('change'));
    });
  });

  createForm?.addEventListener('submit', function(event) {
    var checked = gradeFields?.querySelectorAll('.grade-select-checkbox:checked').length || 0;
    if (checked === 0) {
      event.preventDefault();
      alert('Select at least one grade for this subject.');
    }
  });

  if (initialDepartmentId > 0) {
    loadGrades(initialDepartmentId);
  }
})();
JS
);
?>
