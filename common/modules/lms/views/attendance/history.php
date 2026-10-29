<?php

/**
 * @var yii\web\View $this
 * @var string $eyebrow
 * @var string $subtitle
 * @var array<string, mixed> $filterOptions
 * @var array<string, mixed> $filters
 * @var array<int, array<string, mixed>> $rows
 * @var array{present:int,absent:int,late:int,excused:int,total?:int} $summary
 * @var bool $hasSearched
 */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Attendance History';
echo $this->render('_assets');

$warningMessage = (string)Yii::$app->session->getFlash('warning', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');
$successMessage = (string)Yii::$app->session->getFlash('success', '');

$departmentsJson = Json::htmlEncode($filterOptions['departments'] ?? []);
$gradesJson = Json::htmlEncode($filterOptions['grades'] ?? []);
$sectionsJson = Json::htmlEncode($filterOptions['sections'] ?? []);
$filtersJson = Json::htmlEncode([
    'department_id' => (string)($filters['department_id'] ?: ''),
    'grade_id' => (string)($filters['grade_id'] ?: ''),
    'section_id' => (string)($filters['section_id'] ?: ''),
    'status' => (string)($filters['status'] ?? 'all'),
    'date_from' => (string)($filters['date_from'] ?? ''),
    'date_to' => (string)($filters['date_to'] ?? ''),
]);
?>
<div class="lms-attendance-root lms-courses-shell">
    <div class="lms-courses-page">
        <div class="lms-teacher-ops-header">
            <p class="lms-portal-eyebrow"><?= Html::encode($eyebrow) ?></p>
            <h1 class="lms-teacher-ops-title">Attendance History</h1>
            <p class="lms-teacher-ops-subtitle"><?= Html::encode($subtitle) ?></p>
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

        <div class="lms-attendance-summary-grid">
            <div class="lms-summary-banner lms-summary-banner--blue">
                <div><div class="lms-summary-label">PRESENT</div><div class="lms-summary-value"><?= (int)($summary['present'] ?? 0) ?></div></div>
            </div>
            <div class="lms-summary-banner lms-summary-banner--blue">
                <div><div class="lms-summary-label">ABSENT</div><div class="lms-summary-value"><?= (int)($summary['absent'] ?? 0) ?></div></div>
            </div>
            <div class="lms-summary-banner lms-summary-banner--blue">
                <div><div class="lms-summary-label">LATE</div><div class="lms-summary-value"><?= (int)($summary['late'] ?? 0) ?></div></div>
            </div>
            <div class="lms-summary-banner lms-summary-banner--blue">
                <div><div class="lms-summary-label">EXCUSED</div><div class="lms-summary-value"><?= (int)($summary['excused'] ?? 0) ?></div></div>
            </div>
        </div>

        <div class="lms-teacher-card">
            <?= Html::beginForm(['history'], 'get', ['id' => 'attendance-history-form']) ?>
                <?= Html::hiddenInput('apply', '1') ?>
                <div class="lms-myclasses-filter-grid lms-attendance-history-filters">
                    <div>
                        <label class="lms-teacher-label" for="history-department">Department</label>
                        <select id="history-department" name="department_id" class="lms-teacher-select"></select>
                    </div>
                    <div>
                        <label class="lms-teacher-label" for="history-grade">Grade</label>
                        <select id="history-grade" name="grade_id" class="lms-teacher-select"></select>
                    </div>
                    <div>
                        <label class="lms-teacher-label" for="history-section">Section</label>
                        <select id="history-section" name="section_id" class="lms-teacher-select" disabled></select>
                    </div>
                    <div>
                        <label class="lms-teacher-label" for="history-status">Status</label>
                        <select id="history-status" name="status" class="lms-teacher-select">
                            <?php foreach (($filterOptions['statuses'] ?? []) as $statusOption): ?>
                                <option value="<?= Html::encode((string)$statusOption['key']) ?>" <?= ((string)$filters['status'] === (string)$statusOption['key']) ? 'selected' : '' ?>>
                                    <?= Html::encode((string)$statusOption['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="lms-teacher-label" for="history-from">From</label>
                        <input id="history-from" type="date" name="date_from" class="lms-teacher-input" placeholder="From" value="<?= Html::encode((string)$filters['date_from']) ?>">
                    </div>
                    <div>
                        <label class="lms-teacher-label" for="history-to">To</label>
                        <input id="history-to" type="date" name="date_to" class="lms-teacher-input" placeholder="To" value="<?= Html::encode((string)$filters['date_to']) ?>">
                    </div>
                </div>
                <div class="lms-filter-actions">
                    <a href="<?= Html::encode(Url::to(['history'])) ?>" class="lms-teacher-btn lms-teacher-btn--outline">Reset</a>
                    <button type="submit" class="lms-teacher-btn lms-teacher-btn--purple">Apply</button>
                </div>
            <?= Html::endForm() ?>

            <div class="lms-timetable-table-wrap">
                <table class="lms-timetable-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Reg. No.</th>
                            <th>Grade</th>
                            <th>Section</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Recorded By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$hasSearched): ?>
                            <tr>
                                <td colspan="10" class="lms-attendance-empty">Choose filters and click Apply to view attendance history.</td>
                            </tr>
                        <?php elseif ($rows === []): ?>
                            <tr>
                                <td colspan="10" class="lms-attendance-empty">No attendance records for this filter.</td>
                            </tr>
                        <?php else: ?>
                            <?php $previousDate = null; ?>
                            <?php foreach ($rows as $index => $row): ?>
                                <?php
                                $currentDate = (string)($row['attendance_date'] ?? '');
                                $isDayStart = $index > 0 && $previousDate !== $currentDate;
                                $previousDate = $currentDate;
                                ?>
                                <tr class="<?= $isDayStart ? 'lms-attendance-day-start' : '' ?>">
                                    <td><?= Html::encode((string)($row['section_student_no'] ?? '')) ?></td>
                                    <td><?= Html::encode($currentDate) ?></td>
                                    <td><?= Html::encode((string)($row['student_name'] ?? '')) ?></td>
                                    <td><?= Html::encode((string)($row['student_code'] ?? '')) ?></td>
                                    <td><?= Html::encode((string)($row['grade_name'] ?? '')) ?></td>
                                    <td><?= Html::encode((string)($row['section_name'] ?? '')) ?></td>
                                    <td><span class="lms-badge"><?= Html::encode((string)($row['status'] ?? '')) ?></span></td>
                                    <td><?= Html::encode((string)($row['notes'] ?? $row['note'] ?? '')) ?></td>
                                    <td><?= Html::encode((string)($row['recorded_by'] ?? '')) ?></td>
                                    <td>
                                        <a href="<?= Html::encode(Url::to(['take', 'section_id' => (int)($row['section_id'] ?? 0), 'date' => $currentDate])) ?>"
                                           class="lms-btn lms-btn--outline">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
  var deptSelect = document.getElementById('history-department');
  var gradeSelect = document.getElementById('history-grade');
  var sectionSelect = document.getElementById('history-section');
  var form = document.getElementById('attendance-history-form');

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
    fillOptions(gradeSelect, filteredGrades(), 'All Grades', selected || '');
  }

  function renderSections(selected) {
    var gradeId = gradeSelect.value;
    sectionSelect.disabled = !gradeId;
    fillOptions(
      sectionSelect,
      filteredSections(),
      gradeId ? 'All Sections' : 'Select a grade first',
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

  form.addEventListener('submit', function(event) {
    var hasFilter = Boolean(
      deptSelect.value ||
      gradeSelect.value ||
      sectionSelect.value ||
      (document.getElementById('history-from').value || '').trim() ||
      (document.getElementById('history-to').value || '').trim() ||
      (document.getElementById('history-status').value && document.getElementById('history-status').value !== 'all')
    );
    if (!hasFilter) {
      event.preventDefault();
      window.lmsAttendanceNotify('Choose at least one filter (department, grade, section, status, or date) before applying.', 'warning');
    }
  });
})();
JS
);
?>
