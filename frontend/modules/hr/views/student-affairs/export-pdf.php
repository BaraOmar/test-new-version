<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudent[] $students
 */

use yii\helpers\Html;

$this->title = 'Student Registry Export';

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
        h1 { font-size: 26px; margin: 0 0 8px; color: #111827; }
        p { color: #4b5563; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; page-break-inside: auto; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        th, td { border: 1px solid #d1d5db; padding: 7px 9px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; color: #111827; font-weight: 800; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="report-header">
        <div>
            <h1>Student Registry</h1>
            <p>Generated on <?= Html::encode(date('M j, Y g:i A')) ?></p>
        </div>
        <div class="report-brand">
            CultureHR<br>
            <span><?= Html::encode(date('Y-m-d H:i')) ?></span>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Reg #</th>
                <th>Student</th>
                <th>Class / Year</th>
                <th>Contact</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= Html::encode($student->getRegistrationLabel()) ?></td>
                    <td>
                        <?= Html::encode($student->getDisplayFullName()) ?><br>
                        <small><?= Html::encode($student->getGenderLabel()) ?><?= $student->nationality ? ' · ' . Html::encode($student->nationality) : '' ?></small>
                    </td>
                    <td><?= Html::encode($student->getClassYearLabel()) ?></td>
                    <td><?= Html::encode($student->phone ?: ($student->email ?: '—')) ?></td>
                    <td><?= Html::encode($student->getStatusLabel()) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
