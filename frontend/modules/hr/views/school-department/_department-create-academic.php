<?php

/**
 * @var yii\web\View $this
 * @var string[] $departmentGrades
 * @var array<int, array{name:string,grade_ids:int[],grade_credits:array<int|string,string>,grade_max_scores:array<int|string,string>}> $subjectsDraft
 */

use yii\helpers\Html;

$departmentGrades = $departmentGrades ?? [''];
$subjectsDraft = $subjectsDraft ?? [['name' => '', 'grade_ids' => [], 'grade_credits' => [], 'grade_max_scores' => []]];

?>
<section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100">
        <h2 class="text-lg font-bold text-slate-900">Academic Grades</h2>
        <p class="text-sm text-slate-500 mt-1">Add at least one grade for this department. Subjects are assigned to these grades.</p>
    </div>
    <div class="p-6 sm:p-8 space-y-3" id="department-grades-list">
        <?php foreach ($departmentGrades as $index => $gradeName): ?>
            <div class="department-grade-row flex items-center gap-3" data-grade-index="<?= (int)$index ?>">
                <div class="flex-1">
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">
                        Grade <?= (int)$index + 1 ?> <span class="text-red-500">*</span>
                    </label>
                    <?= Html::textInput('department_grades[]', $gradeName, [
                        'required' => true,
                        'placeholder' => 'e.g. Grade 9',
                        'class' => 'department-grade-name w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm',
                    ]) ?>
                </div>
                <button type="button"
                        class="remove-grade-row mt-6 h-11 px-3 rounded-lg border border-red-200 text-red-600 text-sm font-semibold bg-white cursor-pointer disabled:opacity-40"
                        <?= count($departmentGrades) <= 1 ? 'disabled' : '' ?>>
                    Remove
                </button>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="px-6 sm:px-8 pb-6">
        <button type="button" id="add-department-grade"
                class="inline-flex items-center gap-2 h-10 px-4 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-sm font-semibold cursor-pointer">
            <span class="material-symbols-outlined text-base">add</span>
            Add Grade
        </button>
    </div>
</section>

<section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Subjects</h2>
            <p class="text-sm text-slate-500 mt-1">Add at least one subject and assign maximum marks per grade, same as the Subjects catalog workflow.</p>
        </div>
        <button type="button" id="add-department-subject"
                class="inline-flex items-center gap-2 h-10 px-4 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold cursor-pointer">
            <span class="material-symbols-outlined text-base">add_circle</span>
            Add Subject
        </button>
    </div>
    <div class="p-6 sm:p-8 space-y-6" id="department-subjects-list">
        <?php foreach ($subjectsDraft as $subjectIndex => $subject): ?>
            <?= $this->render('_department-create-subject-row', [
                'subjectIndex' => (int)$subjectIndex,
                'subject' => $subject,
                'departmentGrades' => $departmentGrades,
            ]) ?>
        <?php endforeach; ?>
    </div>
</section>

<template id="department-grade-row-template">
    <div class="department-grade-row flex items-center gap-3" data-grade-index="__INDEX__">
        <div class="flex-1">
            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">
                Grade __NUMBER__ <span class="text-red-500">*</span>
            </label>
            <input type="text" name="department_grades[]" required placeholder="e.g. Grade 9"
                   class="department-grade-name w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
        </div>
        <button type="button" class="remove-grade-row mt-6 h-11 px-3 rounded-lg border border-red-200 text-red-600 text-sm font-semibold bg-white cursor-pointer">
            Remove
        </button>
    </div>
</template>

<template id="department-subject-row-template">
    <?= $this->render('_department-create-subject-row', [
        'subjectIndex' => '__INDEX__',
        'subject' => ['name' => '', 'grade_ids' => [], 'grade_credits' => [], 'grade_max_scores' => []],
        'departmentGrades' => [''],
        'isTemplate' => true,
    ]) ?>
</template>

<?php
$subjectsDraftJson = json_encode($subjectsDraft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$this->registerJs(<<<JS
(function() {
  var gradesList = document.getElementById('department-grades-list');
  var subjectsList = document.getElementById('department-subjects-list');
  var gradeTemplate = document.getElementById('department-grade-row-template');
  var subjectTemplate = document.getElementById('department-subject-row-template');
  var addGradeBtn = document.getElementById('add-department-grade');
  var addSubjectBtn = document.getElementById('add-department-subject');
  var createForm = gradesList ? gradesList.closest('form') : null;

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function getGradeNames() {
    if (!gradesList) return [];
    return Array.from(gradesList.querySelectorAll('.department-grade-name')).map(function(input) {
      return input.value.trim();
    });
  }

  function syncGradeRowState() {
    if (!gradesList) return;
    var rows = gradesList.querySelectorAll('.department-grade-row');
    rows.forEach(function(row, index) {
      row.setAttribute('data-grade-index', String(index));
      var label = row.querySelector('label');
      if (label) {
        label.innerHTML = 'Grade ' + (index + 1) + ' <span class="text-red-500">*</span>';
      }
      var removeBtn = row.querySelector('.remove-grade-row');
      if (removeBtn) removeBtn.disabled = rows.length <= 1;
    });
    refreshAllSubjectGradeFields();
  }

  function buildSubjectGradeFields(subjectIndex, selectedIds, maxScores) {
    var grades = getGradeNames();
    if (grades.length === 0) {
      return '<p class="text-sm text-slate-400 italic md:col-span-2">Add at least one grade above before assigning subjects.</p>';
    }

    return grades.map(function(gradeName, gradeIndex) {
      var displayName = gradeName !== '' ? gradeName : ('Grade ' + (gradeIndex + 1));
      var checked = selectedIds.indexOf(gradeIndex) !== -1 ? ' checked' : '';
      var maxScoreValue = maxScores[gradeIndex] !== undefined ? maxScores[gradeIndex] : (maxScores[String(gradeIndex)] || '100');
      var disabledAttr = checked ? '' : ' disabled';
      var requiredAttr = checked ? ' required' : '';

      return '<div data-grade-row class="rounded-xl border border-slate-200 bg-slate-50 p-4">' +
        '<label class="flex items-center gap-3 cursor-pointer mb-3">' +
          '<input type="checkbox" name="subjects[' + subjectIndex + '][grade_ids][]" value="' + gradeIndex + '" class="grade-select-checkbox rounded border-slate-300"' + checked + '>' +
          '<span class="font-semibold text-slate-800">' + escapeHtml(displayName) + '</span>' +
        '</label>' +
        '<label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Maximum Mark</label>' +
        '<input type="number" name="subjects[' + subjectIndex + '][grade_max_scores][' + gradeIndex + ']" min="0.01" step="0.01" placeholder="e.g. 100" value="' + escapeHtml(maxScoreValue) + '" ' +
        'class="grade-max-score-input w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm disabled:bg-slate-100 disabled:text-slate-400"' + disabledAttr + requiredAttr + '>' +
      '</div>';
    }).join('');
  }

  function collectSubjectSelections(card) {
    var selectedIds = [];
    var maxScores = {};
    card.querySelectorAll('.grade-select-checkbox:checked').forEach(function(checkbox) {
      var gradeIndex = parseInt(checkbox.value, 10);
      selectedIds.push(gradeIndex);
      var row = checkbox.closest('[data-grade-row]');
      var maxScoreInput = row ? row.querySelector('.grade-max-score-input') : null;
      if (maxScoreInput) maxScores[gradeIndex] = maxScoreInput.value;
    });
    return { selectedIds: selectedIds, maxScores: maxScores };
  }

  function bindGradeCheckbox(checkbox) {
    checkbox.addEventListener('change', function() {
      var row = checkbox.closest('[data-grade-row]');
      var maxScoreInput = row ? row.querySelector('.grade-max-score-input') : null;
      if (!maxScoreInput) return;
      maxScoreInput.disabled = !checkbox.checked;
      maxScoreInput.required = checkbox.checked;
      if (!checkbox.checked) {
        maxScoreInput.value = '';
      } else if (maxScoreInput.value === '') {
        maxScoreInput.value = '100';
      }
      updateSubjectSelectedCount(checkbox.closest('.department-subject-card'));
    });
  }

  function updateSubjectSelectedCount(card) {
    if (!card) return;
    var countEl = card.querySelector('.selected-grade-count');
    if (!countEl) return;
    var count = card.querySelectorAll('.grade-select-checkbox:checked').length;
    countEl.textContent = count + ' selected';
  }

  function refreshSubjectGradeFields(card) {
    if (!card) return;
    var subjectIndex = card.getAttribute('data-subject-index');
    var fields = card.querySelector('.subject-grade-fields');
    if (!fields || subjectIndex === null) return;

    var selections = collectSubjectSelections(card);
    fields.innerHTML = buildSubjectGradeFields(subjectIndex, selections.selectedIds, selections.maxScores);
    fields.querySelectorAll('.grade-select-checkbox').forEach(bindGradeCheckbox);
    updateSubjectSelectedCount(card);

    var selectAllBtn = card.querySelector('.select-all-grades');
    var clearAllBtn = card.querySelector('.clear-all-grades');
    var hasGrades = getGradeNames().length > 0;
    if (selectAllBtn) selectAllBtn.disabled = !hasGrades;
    if (clearAllBtn) clearAllBtn.disabled = !hasGrades;
  }

  function refreshAllSubjectGradeFields() {
    document.querySelectorAll('.department-subject-card').forEach(refreshSubjectGradeFields);
  }

  function bindSubjectCard(card) {
    var removeBtn = card.querySelector('.remove-subject-row');
    if (removeBtn) {
      removeBtn.addEventListener('click', function() {
        var cards = subjectsList.querySelectorAll('.department-subject-card');
        if (cards.length <= 1) return;
        card.remove();
        syncSubjectRowState();
      });
    }

    var selectAllBtn = card.querySelector('.select-all-grades');
    var clearAllBtn = card.querySelector('.clear-all-grades');
    selectAllBtn?.addEventListener('click', function() {
      card.querySelectorAll('.grade-select-checkbox').forEach(function(checkbox) {
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('change'));
      });
    });
    clearAllBtn?.addEventListener('click', function() {
      card.querySelectorAll('.grade-select-checkbox').forEach(function(checkbox) {
        checkbox.checked = false;
        checkbox.dispatchEvent(new Event('change'));
      });
    });

    refreshSubjectGradeFields(card);
  }

  function syncSubjectRowState() {
    if (!subjectsList) return;
    var cards = subjectsList.querySelectorAll('.department-subject-card');
    cards.forEach(function(card, index) {
      card.setAttribute('data-subject-index', String(index));
      var title = card.querySelector('.subject-card-title');
      if (title) title.textContent = 'Subject ' + (index + 1);
      var nameInput = card.querySelector('.subject-name-input');
      if (nameInput) nameInput.name = 'subjects[' + index + '][name]';
      var removeBtn = card.querySelector('.remove-subject-row');
      if (removeBtn) removeBtn.disabled = cards.length <= 1;
    });
    refreshAllSubjectGradeFields();
  }

  addGradeBtn?.addEventListener('click', function() {
    if (!gradeTemplate || !gradesList) return;
    var index = gradesList.querySelectorAll('.department-grade-row').length;
    var html = gradeTemplate.innerHTML
      .replace(/__INDEX__/g, String(index))
      .replace(/__NUMBER__/g, String(index + 1));
    gradesList.insertAdjacentHTML('beforeend', html);
    syncGradeRowState();
  });

  gradesList?.addEventListener('click', function(event) {
    var button = event.target.closest('.remove-grade-row');
    if (!button || button.disabled) return;
    button.closest('.department-grade-row')?.remove();
    syncGradeRowState();
  });

  gradesList?.addEventListener('input', function(event) {
    if (event.target.classList.contains('department-grade-name')) {
      refreshAllSubjectGradeFields();
    }
  });

  addSubjectBtn?.addEventListener('click', function() {
    if (!subjectTemplate || !subjectsList) return;
    var index = subjectsList.querySelectorAll('.department-subject-card').length;
    var html = subjectTemplate.innerHTML
      .replace(/__INDEX__/g, String(index))
      .replace(/__NUMBER__/g, String(index + 1));
    subjectsList.insertAdjacentHTML('beforeend', html);
    var card = subjectsList.lastElementChild;
    if (card) bindSubjectCard(card);
    syncSubjectRowState();
  });

  subjectsList?.querySelectorAll('.department-subject-card').forEach(bindSubjectCard);
  syncGradeRowState();
  syncSubjectRowState();

  createForm?.addEventListener('submit', function(event) {
    var gradeNames = getGradeNames().filter(function(name) { return name !== ''; });
    if (gradeNames.length === 0) {
      event.preventDefault();
      alert('Add at least one grade for this department.');
      return;
    }

    var subjectCards = subjectsList ? subjectsList.querySelectorAll('.department-subject-card') : [];
    if (subjectCards.length === 0) {
      event.preventDefault();
      alert('Add at least one subject for this department.');
      return;
    }

    for (var i = 0; i < subjectCards.length; i++) {
      var card = subjectCards[i];
      var nameInput = card.querySelector('.subject-name-input');
      var subjectName = nameInput ? nameInput.value.trim() : '';
      if (subjectName === '') {
        event.preventDefault();
        alert('Enter a name for each subject.');
        return;
      }
      var checked = card.querySelectorAll('.grade-select-checkbox:checked').length;
      if (checked === 0) {
        event.preventDefault();
        alert('Select at least one grade for each subject.');
        return;
      }
    }
  });
})();
JS
);
