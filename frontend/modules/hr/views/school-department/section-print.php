<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolSection $section
 * @var common\modules\employee\models\SchoolDepartment|null $department
 * @var common\modules\employee\models\SchoolSectionMaterial[] $materials
 * @var common\modules\employee\models\SchoolStudent[] $students
 */

use yii\helpers\Html;

$grade = $section->grade;
$this->title = 'Section Report — ' . $section->name;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= Html::encode($this->title) ?></title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-shadow: none !important; text-shadow: none !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; background: #fff; line-height: 1.45; }
        .report-header { display: flex; justify-content: space-between; gap: 16px; padding-bottom: 24px; margin-bottom: 24px; border-bottom: 2px solid #111827; }
        .report-brand { font-size: 14px; font-weight: 800; white-space: nowrap; }
        h1 { margin: 0 0 8px; font-size: 26px; color: #111827; }
        .meta { color: #4b5563; margin: 0; }
        section { margin-bottom: 28px; page-break-inside: avoid; break-inside: avoid; }
        h2 { font-size: 16px; border-bottom: 1px solid #d1d5db; padding-bottom: 8px; color: #111827; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; page-break-inside: auto; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        th, td { text-align: left; padding: 7px 9px; border: 1px solid #d1d5db; vertical-align: top; }
        th { color: #111827; background: #f3f4f6; font-size: 11px; text-transform: uppercase; font-weight: 800; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    <div class="report-header">
        <div>
            <h1><?= Html::encode($section->name) ?> — Section Report</h1>
            <p class="meta">
                <?= Html::encode($department?->name ?? '') ?> · <?= Html::encode($grade?->name ?? '') ?>
                · Room <?= Html::encode($section->room_number ?: '—') ?>
                · <?= (int)$section->current_enrollment ?>/<?= (int)$section->capacity_max ?> enrolled
            </p>
        </div>
        <div class="report-brand">
            CultureHR<br>
            <span class="meta"><?= Html::encode(date('Y-m-d H:i')) ?></span>
        </div>
    </div>

    <section>
        <h2>Class Teacher</h2>
        <?php if ($section->leadTeacher): ?>
            <p><strong><?= Html::encode($section->leadTeacher->full_name) ?></strong>
                — <?= Html::encode($section->leadTeacher->job_title ?: 'Teacher') ?></p>
        <?php else: ?>
            <p>Unassigned</p>
        <?php endif; ?>
    </section>

    <section>
        <h2>Material Allocation</h2>
        <table>
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Instructor</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($materials === []): ?>
                    <tr><td colspan="2">No materials assigned.</td></tr>
                <?php endif; ?>
                <?php foreach ($materials as $material): ?>
                    <tr>
                        <td><?= Html::encode($material->name) ?></td>
                        <td><?= Html::encode($material->instructor?->full_name ?: 'Unassigned') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Student List</h2>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>ID</th>
                    <th>Attendance</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($students === []): ?>
                    <tr><td colspan="4">No students enrolled.</td></tr>
                <?php endif; ?>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= Html::encode($student->full_name) ?></td>
                        <td><?= Html::encode($student->student_code) ?></td>
                        <td><?= number_format((float)$student->attendance_percent, 0) ?>%</td>
                        <td><?= Html::encode($student->grade_average ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
