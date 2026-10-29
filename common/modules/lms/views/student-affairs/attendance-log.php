<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudent $student
 * @var bool $isStaffView
 * @var array{present:int,absent:int,late:int,excused:int,total_days:int,attended_days:int,attendance_percentage:float} $summary
 * @var array<int, array<string, mixed>> $rows
 * @var array|string|null $backUrl
 * @var string|null $backLabel
 * @var string $eyebrow
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'My Attendance Log';
echo $this->render('../attendance/_assets');

?>
<div class="lms-attendance-root lms-courses-shell">
    <div class="lms-courses-page">
        <div class="lms-teacher-ops-header">
            <p class="lms-portal-eyebrow"><?= Html::encode($eyebrow) ?></p>
            <h1 class="lms-teacher-ops-title">My Attendance Log</h1>
            <p class="lms-teacher-ops-subtitle">Monitor your presence and track your academic consistency.</p>
        </div>

        <?= $this->render('_profile-section-toggle-wrapper', ['model' => $student, 'activeTab' => 'attendance']) ?>

        <div class="lms-attendance-summary-grid">
            <div class="lms-summary-banner lms-summary-banner--blue">
                <div><div class="lms-summary-label">TOTAL DAYS</div><div class="lms-summary-value"><?= (int)$summary['total_days'] ?></div></div>
            </div>
            <div class="lms-summary-banner lms-summary-banner--blue">
                <div><div class="lms-summary-label">ATTENDANCE %</div><div class="lms-summary-value"><?= Html::encode((string)$summary['attendance_percentage']) ?>%</div></div>
            </div>
            <div class="lms-summary-banner lms-summary-banner--blue">
                <div><div class="lms-summary-label">PRESENT</div><div class="lms-summary-value"><?= (int)$summary['present'] ?></div></div>
            </div>
            <div class="lms-summary-banner lms-summary-banner--blue">
                <div><div class="lms-summary-label">ABSENT</div><div class="lms-summary-value"><?= (int)$summary['absent'] ?></div></div>
            </div>
        </div>

        <div class="lms-teacher-card">
            <div class="lms-timetable-table-wrap">
                <table class="lms-timetable-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Section</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rows === []): ?>
                            <tr>
                                <td colspan="4" class="lms-attendance-empty">No attendance records.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= Html::encode((string)($row['attendance_date'] ?? '')) ?></td>
                                    <td><?= Html::encode((string)($row['status'] ?? '')) ?></td>
                                    <td><?= Html::encode((string)($row['section_name'] ?? '')) ?></td>
                                    <td><?= Html::encode((string)($row['notes'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
