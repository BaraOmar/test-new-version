<?php

/**
 * @var yii\web\View $this
 * @var string $eyebrow
 * @var array<string, mixed>|null $header
 * @var array<int, array<string, mixed>> $lines
 * @var string $date
 * @var bool $missingSection
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Take Attendance';
echo $this->render('_assets');

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');
$warningMessage = (string)Yii::$app->session->getFlash('warning', '');

?>
<div class="lms-attendance-root lms-courses-shell">
    <div class="lms-courses-page">
        <?php if ($successMessage !== ''): ?>
            <div class="lms-attendance-flash lms-attendance-flash--success"><?= Html::encode($successMessage) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <div class="lms-attendance-flash lms-attendance-flash--error"><?= Html::encode($errorMessage) ?></div>
        <?php endif; ?>
        <?php if ($warningMessage !== ''): ?>
            <div class="lms-attendance-flash lms-attendance-flash--warning"><?= Html::encode($warningMessage) ?></div>
        <?php endif; ?>

        <?php if ($header === null): ?>
            <div class="lms-teacher-ops-header">
                <p class="lms-portal-eyebrow"><?= Html::encode($eyebrow) ?></p>
                <h1 class="lms-teacher-ops-title">Take Attendance</h1>
                <p class="lms-teacher-ops-subtitle">No section selected. Go back and choose a section first.</p>
            </div>
            <a href="<?= Html::encode(Url::to(['index'])) ?>" class="lms-teacher-btn lms-teacher-btn--outline">Cancel</a>
        <?php else: ?>
            <div class="lms-teacher-ops-header">
                <p class="lms-portal-eyebrow"><?= Html::encode($eyebrow) ?></p>
                <h1 class="lms-teacher-ops-title">Take Attendance</h1>
                <p class="lms-teacher-ops-subtitle">
                    <?= Html::encode((string)$header['department_name']) ?> ·
                    <?= Html::encode((string)$header['grade_name']) ?> ·
                    Section <?= Html::encode((string)$header['section_name']) ?> ·
                    <?= Html::encode((string)$header['attendance_date']) ?>
                </p>
                <p class="lms-teacher-ops-subtitle">Class Teacher: <?= Html::encode((string)$header['class_teacher_name']) ?></p>
            </div>

            <div class="lms-teacher-card">
                <?= Html::beginForm(['save'], 'post', ['id' => 'attendance-form']) ?>
                    <?= Html::hiddenInput('section_id', (int)$header['section_id']) ?>
                    <?= Html::hiddenInput('date', $date) ?>

                    <div class="lms-attendance-quick-actions">
                        <button type="button" class="lms-btn lms-btn--outline" id="mark-all-present">Mark All Present</button>
                        <button type="button" class="lms-btn lms-btn--outline" id="mark-all-absent">Mark All Absent</button>
                        <button type="button" class="lms-btn lms-btn--outline" id="reset-attendance">Reset</button>
                    </div>

                    <div class="lms-timetable-table-wrap">
                        <table class="lms-timetable-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Reg. No.</th>
                                    <th>Student</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lines as $line): ?>
                                    <?php $studentId = (int)$line['student_id']; ?>
                                    <tr class="attendance-row">
                                        <td><?= Html::encode((string)$line['section_student_no']) ?></td>
                                        <td><?= Html::encode((string)$line['student_code']) ?></td>
                                        <td><?= Html::encode((string)$line['student_name']) ?></td>
                                        <td>
                                            <?= Html::hiddenInput("records[{$studentId}][student_id]", $studentId) ?>
                                            <select name="records[<?= $studentId ?>][status]" class="lms-teacher-select attendance-status">
                                                <option value="present" <?= $line['status'] === 'present' ? 'selected' : '' ?>>Present</option>
                                                <option value="absent" <?= $line['status'] === 'absent' ? 'selected' : '' ?>>Absent</option>
                                                <option value="late" <?= $line['status'] === 'late' ? 'selected' : '' ?>>Late</option>
                                                <option value="excused" <?= $line['status'] === 'excused' ? 'selected' : '' ?>>Excused</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="records[<?= $studentId ?>][notes]" class="lms-teacher-input attendance-notes" value="<?= Html::encode((string)$line['notes']) ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="lms-upload-form-actions">
                        <button type="submit" class="lms-teacher-btn lms-teacher-btn--orange" id="save-attendance">Save Attendance</button>
                        <a href="<?= Html::encode(Url::to(['index'])) ?>" class="lms-teacher-btn lms-teacher-btn--outline">Cancel</a>
                    </div>
                <?= Html::endForm() ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($header !== null): ?>
<?php
$this->registerJs(<<<JS
(function() {
  var form = document.getElementById('attendance-form');
  var saveBtn = document.getElementById('save-attendance');
  function setAll(status, clearNotes) {
    document.querySelectorAll('.attendance-status').forEach(function(select) {
      select.value = status;
    });
    if (clearNotes) {
      document.querySelectorAll('.attendance-notes').forEach(function(input) {
        input.value = '';
      });
    }
  }
  document.getElementById('mark-all-present').addEventListener('click', function() { setAll('present', false); });
  document.getElementById('mark-all-absent').addEventListener('click', function() { setAll('absent', false); });
  document.getElementById('reset-attendance').addEventListener('click', function() { setAll('present', true); });
  form.addEventListener('submit', function() {
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving…';
  });
})();
JS
);
?>
<?php endif; ?>
