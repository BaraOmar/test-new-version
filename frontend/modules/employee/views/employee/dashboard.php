<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\Employee $employee
 * @var string $pageTitle
 * @var string $navLinksHtml
 * @var string $helloText
 * @var string $departmentName
 * @var string $jobTitle
 * @var string $statusText
 * @var string $statusClasses
 * @var string $positionLevelText
 * @var string $employmentMetaText
 * @var int $pendingSurveys
 * @var int $pendingEvaluations
 * @var int $pendingTasks
 * @var string $pendingTasksLabel
 * @var string $surveysCtaText
 * @var int $lmsContentCount
 * @var string $lmsResourcesLabel
 * @var string|null $lmsLatestTitle
 * @var string $surveyRingClass
 * @var string $actionNeededClass
 * @var string $lmsLatestVisibleClass
 * @var string $lmsBrowseVisibleClass
 * @var string $lmsEmptyVisibleClass
 * @var bool $portalLocked
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $pageTitle ?? 'Dashboard';

$this->registerCss(<<<'CSS'
.employee-dashboard-page {
    width: 100%;
    max-width: 90rem;
    margin-left: auto;
    margin-right: auto;
    padding: 2.5rem 1.5rem 3rem;
}
@media (min-width: 1024px) {
    .employee-dashboard-page {
        padding-left: 2rem;
        padding-right: 2rem;
    }
}
.employee-dashboard-hero {
    margin-bottom: 2.5rem;
}
.employee-dashboard-hero__eyebrow {
    color: #eb652c;
    font-size: 0.8125rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
}
.employee-dashboard-hero__title {
    font-size: clamp(1.875rem, 4vw, 3rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.15;
    color: #0f172a;
}
.dark .employee-dashboard-hero__title {
    color: #f8fafc;
}
.employee-dashboard-hero__subtitle {
    margin-top: 0.625rem;
    font-size: 1.0625rem;
    line-height: 1.5;
    color: #64748b;
    max-width: 36rem;
}
.employee-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(1, minmax(0, 1fr));
    gap: 1.25rem;
    align-items: stretch;
}
@media (min-width: 640px) {
    .employee-dashboard-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.5rem;
    }
}
@media (min-width: 1024px) {
    .employee-dashboard-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
@media (min-width: 1280px) {
    .employee-dashboard-grid {
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 1.25rem;
    }
}
.employee-dash-card {
    display: flex;
    flex-direction: column;
    min-height: 22.5rem;
    padding: 1.375rem;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    box-shadow: 0 4px 24px rgba(100, 39, 84, 0.06);
    transition: box-shadow 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
}
.dark .employee-dash-card {
    background: rgba(15, 23, 42, 0.9);
    border-color: rgba(51, 65, 85, 0.8);
}
.employee-dash-card:hover {
    box-shadow: 0 12px 32px rgba(100, 39, 84, 0.12);
    transform: translateY(-2px);
}
.employee-dash-card--highlight {
    border-color: rgba(251, 191, 36, 0.55);
    box-shadow: 0 0 0 2px rgba(251, 191, 36, 0.2), 0 4px 24px rgba(100, 39, 84, 0.08);
}
.employee-dash-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 1rem;
    min-height: 2.75rem;
}
.employee-dash-card__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.75rem;
    flex-shrink: 0;
}
.employee-dash-card__icon .material-symbols-outlined {
    font-size: 1.5rem;
}
.employee-dash-card__icon--slate {
    background: linear-gradient(135deg, #f1f5f9, #f8fafc);
    color: #475569;
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.35);
}
.employee-dash-card__icon--amber {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    color: #d97706;
    box-shadow: inset 0 0 0 1px rgba(251, 191, 36, 0.4);
}
.employee-dash-card__icon--blue {
    background: linear-gradient(135deg, #eff6ff, #e0e7ff);
    color: #2563eb;
    box-shadow: inset 0 0 0 1px rgba(147, 197, 253, 0.45);
}
.employee-dash-card__icon--orange {
    background: linear-gradient(135deg, rgba(235, 101, 44, 0.12), rgba(235, 101, 44, 0.05));
    color: #eb652c;
    box-shadow: inset 0 0 0 1px rgba(235, 101, 44, 0.25);
}
.employee-dash-card__icon--teal {
    background: linear-gradient(135deg, #f0fdfa, #ecfdf5);
    color: #0d9488;
    box-shadow: inset 0 0 0 1px rgba(45, 212, 191, 0.35);
}
.employee-dash-card__badge {
    font-size: 0.625rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    background: #ffe4e6;
    color: #be123c;
    flex-shrink: 0;
    line-height: 1.2;
}
.employee-dash-card__title {
    font-size: 1.0625rem;
    font-weight: 700;
    line-height: 1.3;
    color: #0f172a;
    margin: 0 0 0.375rem;
}
.dark .employee-dash-card__title {
    color: #f8fafc;
}
.employee-dash-card__desc {
    font-size: 0.8125rem;
    line-height: 1.5;
    color: #64748b;
    margin: 0 0 1rem;
    min-height: 2.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.employee-dash-card__metrics {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    min-height: 6.75rem;
    margin-bottom: 1rem;
}
.employee-dash-card__stat-row {
    display: flex;
    align-items: baseline;
    gap: 0.375rem;
    flex-wrap: wrap;
}
.employee-dash-card__stat-value {
    font-size: 1.875rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    line-height: 1;
    color: #0f172a;
}
.dark .employee-dash-card__stat-value {
    color: #f8fafc;
}
.employee-dash-card__stat-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
}
.employee-dash-card__stat-meta {
    font-size: 0.6875rem;
    color: #64748b;
    margin-top: 0.5rem;
    line-height: 1.4;
}
.employee-dash-card__profile-role {
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.dark .employee-dash-card__profile-role {
    color: #f1f5f9;
}
.employee-dash-card__profile-dept {
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
    display: block;
    margin-top: 0.125rem;
}
.employee-dash-card__status {
    display: inline-block;
    margin-top: 0.5rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.625rem;
    font-weight: 700;
    border-radius: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.employee-dash-card__profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem 0.75rem;
    margin-top: 0.375rem;
    font-size: 0.6875rem;
    color: #64748b;
}
.employee-dash-card__progress-track {
    width: 100%;
    height: 0.5rem;
    margin-top: 0.75rem;
    border-radius: 9999px;
    background: #f1f5f9;
    overflow: hidden;
}
.dark .employee-dash-card__progress-track {
    background: #1e293b;
}
.employee-dash-card__progress-fill {
    height: 100%;
    border-radius: 9999px;
    background: linear-gradient(90deg, #3b82f6, #6366f1);
    transition: width 0.5s ease;
}
.employee-dash-card__cta {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 2.75rem;
    margin-top: auto;
    flex-shrink: 0;
    border-radius: 0.75rem;
    background: #eb652c;
    color: #fff;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(235, 101, 44, 0.25);
    transition: background 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
}
.employee-dash-card__cta:hover {
    background: #d85a24;
    color: #fff;
    box-shadow: 0 6px 18px rgba(235, 101, 44, 0.35);
}
.employee-dash-card__cta:focus-visible {
    outline: 2px solid #642754;
    outline-offset: 2px;
}
.employee-dashboard-footer {
    margin-top: 2.5rem;
    padding-top: 2rem;
    text-align: center;
    border-top: 1px solid rgba(226, 232, 240, 0.8);
    font-size: 0.75rem;
    color: #94a3b8;
}
CSS);

$profileMetrics = ob_start();
?>
<div>
    <span class="employee-dash-card__profile-role"><?= Html::encode($jobTitle) ?></span>
    <span class="employee-dash-card__profile-dept"><?= Html::encode($departmentName) ?></span>
    <span class="employee-dash-card__status <?= Html::encode($statusClasses) ?>"><?= Html::encode($statusText) ?></span>
    <div class="employee-dash-card__profile-meta">
        <span><?= Html::encode($positionLevelText) ?></span>
        <span><?= Html::encode($employmentMetaText) ?></span>
    </div>
</div>
<?php
$profileMetrics = ob_get_clean();

$surveyBadge = $pendingTasks > 0
    ? '<span class="employee-dash-card__badge">Action needed</span>'
    : '';
$surveyMetrics = '<div class="employee-dash-card__stat-row">'
    . '<span class="employee-dash-card__stat-value">' . Html::encode((string)$pendingTasks) . '</span>'
    . '<span class="employee-dash-card__stat-label">' . Html::encode($pendingTasksLabel) . '</span>'
    . '</div>'
    . '<p class="employee-dash-card__stat-meta">' . Html::encode((string)$pendingSurveys) . ' surveys · '
    . Html::encode((string)$pendingEvaluations) . ' evaluations</p>';

$lmsMetrics = '<p class="employee-dash-card__stat-meta">Access your teacher portal for classes, attendance, and schedules.</p>';

$surveyCardClass = 'employee-dash-card--highlight ' . trim($surveyRingClass);

$portalLocked = !empty($portalLocked);

?>
<?= $this->render('../layouts/_shell-start', [
    'pageTitle' => $this->title,
    'currentPage' => 'dashboard',
    'userName' => $employee->full_name,
    'userRole' => $employee->job_title ?: 'Employee',
    'portalLocked' => $portalLocked,
]) ?>
    <div class="employee-dashboard-page">
        <header class="employee-dashboard-hero">
            <p class="employee-dashboard-hero__eyebrow">Your Dashboard</p>
            <h1 class="employee-dashboard-hero__title"><?= Html::encode($helloText) ?></h1>
            <p class="employee-dashboard-hero__subtitle">
                <?= $portalLocked
                    ? 'Your employee account is read-only.'
                    : 'A quick overview of your employee journey and priorities.' ?>
            </p>
        </header>

        <?php if ($portalLocked): ?>
            <?= $this->render('../layouts/_portal-terminated-banner') ?>
        <?php endif; ?>

        <div class="employee-dashboard-grid">
            <?= $this->render('_dashboard-card', [
                'icon' => 'badge',
                'tone' => 'slate',
                'title' => 'My Profile',
                'description' => 'Personal data, professional details, and contact information.',
                'metricsHtml' => $profileMetrics,
                'ctaUrl' => Url::to(['/employee/employee/profile']),
                'ctaLabel' => 'View Profile',
            ]) ?>

            <?php if (!$portalLocked): ?>
            <?= $this->render('_dashboard-card', [
                'icon' => 'fact_check',
                'tone' => 'amber',
                'title' => 'Surveys & Evaluations',
                'description' => 'Submit engagement surveys and performance feedback.',
                'metricsHtml' => $surveyMetrics,
                'ctaUrl' => Url::to(['/employee/employee/survey-display']),
                'ctaLabel' => $surveysCtaText,
                'cardClass' => $surveyCardClass,
                'badgeHtml' => $surveyBadge,
            ]) ?>

            <?php if ($employee->canAccessTeacherLms()): ?>
            <?= $this->render('_dashboard-card', [
                'icon' => 'school',
                'tone' => 'orange',
                'title' => 'Learning (LMS)',
                'description' => 'Training courses and learning modules.',
                'metricsHtml' => $lmsMetrics,
                'ctaUrl' => Url::to(['/employee/employee/lms']),
                'ctaLabel' => 'Open LMS',
            ]) ?>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <footer class="employee-dashboard-footer">
            © <?= Html::encode((string)date('Y')) ?> CultureHR
        </footer>
    </div>
<?= $this->render('../layouts/_shell-end') ?>
