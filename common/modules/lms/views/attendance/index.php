<?php

/**
 * @var yii\web\View $this
 * @var string $eyebrow
 * @var string $subtitle
 * @var array<string, mixed> $filterOptions
 * @var array{department_id:string,grade_id:string,section_id:string} $filters
 * @var string $todayIso
 * @var string $todayChip
 * @var bool $autoSection
 */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Attendance';

echo $this->render('_assets');
$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');
$warningMessage = (string)Yii::$app->session->getFlash('warning', '');

$departmentsJson = Json::htmlEncode($filterOptions['departments'] ?? []);
$gradesJson = Json::htmlEncode($filterOptions['grades'] ?? []);
$sectionsJson = Json::htmlEncode($filterOptions['sections'] ?? []);
$filtersJson = Json::htmlEncode($filters);
$takeUrl = Json::htmlEncode(Url::to(['take']));
$todayIsoJson = Json::htmlEncode($todayIso);

?>
<div class="lms-attendance-root lms-courses-shell">
    <div class="lms-courses-page lms-attendance-gateway">
        <div class="lms-teacher-ops-header">
            <p class="lms-portal-eyebrow"><?= Html::encode($eyebrow) ?></p>
            <h1 class="lms-teacher-ops-title">Attendance</h1>
            <p class="lms-teacher-ops-subtitle"><?= Html::encode($subtitle) ?></p>
        </div>

        <div class="lms-attendance-gateway-toolbar">
            <a href="<?= Html::encode(Url::to(['history'])) ?>" class="lms-teacher-btn lms-teacher-btn--ghost">Attendance History</a>
        </div>

        <?php if ($successMessage !== ''): ?>
            <div class="lms-attendance-flash lms-attendance-flash--success"><?= Html::encode($successMessage) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <div class="lms-attendance-flash lms-attendance-flash--error"><?= Html::encode($errorMessage) ?></div>
        <?php endif; ?>
        <?php if ($warningMessage !== ''): ?>
            <div class="lms-attendance-flash lms-attendance-flash--warning"><?= Html::encode($warningMessage) ?></div>
        <?php endif; ?>

        <div class="lms-teacher-card lms-attendance-gateway-card">
            <div class="lms-attendance-gateway-card-head">
                <h2 class="lms-teacher-card-title">Select class</h2>
                <div class="lms-attendance-today-chip" title="Attendance can only be taken for today">
                    <span class="material-symbols-outlined" style="font-size:1rem;">calendar_today</span>
                    <span>Today · <?= Html::encode($todayChip) ?></span>
                </div>
            </div>

            <div class="lms-attendance-filter-grid">
                <div class="lms-attendance-field">
                    <label class="lms-teacher-label" for="attendance-department">Department</label>
                    <select id="attendance-department" class="lms-teacher-select">
                        <option value="">All Departments</option>
                    </select>
                </div>
                <div class="lms-attendance-field">
                    <label class="lms-teacher-label" for="attendance-grade">Grade</label>
                    <select id="attendance-grade" class="lms-teacher-select">
                        <option value="">Select Grade</option>
                    </select>
                </div>
                <div class="lms-attendance-field">
                    <label class="lms-teacher-label" for="attendance-section">Section</label>
                    <select id="attendance-section" class="lms-teacher-select" disabled>
                        <option value="">Select a grade first</option>
                    </select>
                </div>
            </div>

            <p class="lms-attendance-date-hint">Attendance can only be taken for today.</p>

            <div class="lms-attendance-gateway-actions">
                <button type="button" id="attendance-reset" class="lms-teacher-btn lms-teacher-btn--outline">Reset</button>
                <button type="button" id="attendance-take" class="lms-teacher-btn lms-teacher-btn--orange">Take Attendance</button>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<JS
(function() {
  var departments = {$departmentsJson} || [];
  var grades = {$gradesJson} || [];
  var sections = {$sectionsJson} || [];
  var filters = {$filtersJson} || {};
  var takeUrl = {$takeUrl};
  var todayIso = {$todayIsoJson};
  var deptSelect = document.getElementById('attendance-department');
  var gradeSelect = document.getElementById('attendance-grade');
  var sectionSelect = document.getElementById('attendance-section');

  function fillOptions(select, items, placeholder, selected) {
    select.innerHTML = '';
    var empty = document.createElement('option');
    empty.value = '';
    empty.textContent = placeholder;
    select.appendChild(empty);
    items.forEach(function(item) {
      var option = document.createElement('option');
      option.value = String(item.id);
      option.textContent = item.name;
      if (selected && String(item.id) === String(selected)) option.selected = true;
      select.appendChild(option);
    });
  }

  function filteredGrades() {
    var deptId = deptSelect.value ? Number(deptSelect.value) : 0;
    if (!deptId) return grades;
    return grades.filter(function(g) { return Number(g.department_id) === deptId; });
  }

  function filteredSections() {
    var gradeId = gradeSelect.value ? Number(gradeSelect.value) : 0;
    if (!gradeId) return [];
    return sections.filter(function(s) { return Number(s.grade_id) === gradeId; });
  }

  function renderGrades(selected) {
    fillOptions(gradeSelect, filteredGrades(), 'Select Grade', selected || '');
  }

  function renderSections(selected) {
    var gradeId = gradeSelect.value;
    sectionSelect.disabled = !gradeId;
    fillOptions(
      sectionSelect,
      filteredSections(),
      gradeId ? 'Select Section' : 'Select a grade first',
      selected || ''
    );
  }

  fillOptions(deptSelect, departments, 'All Departments', filters.department_id || '');
  renderGrades(filters.grade_id || '');
  renderSections(filters.section_id || '');

  deptSelect.addEventListener('change', function() {
    renderGrades('');
    renderSections('');
  });
  gradeSelect.addEventListener('change', function() {
    renderSections('');
  });

  document.getElementById('attendance-reset').addEventListener('click', function() {
    deptSelect.value = '';
    renderGrades('');
    renderSections('');
  });

  document.getElementById('attendance-take').addEventListener('click', function() {
    if (!sectionSelect.value) {
      window.lmsAttendanceNotify('Please select a section.', 'warning');
      return;
    }
    var url = takeUrl + (takeUrl.indexOf('?') === -1 ? '?' : '&') + 'section_id=' + encodeURIComponent(sectionSelect.value) + '&date=' + encodeURIComponent(todayIso);
    window.location.href = url;
  });
})();
JS
);
?>
