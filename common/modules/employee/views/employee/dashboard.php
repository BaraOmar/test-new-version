<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\modules\employee\models\Employee $employee */
/** @var int $pendingSurveys */
/** @var int $pendingEvaluations */
/** @var int $pendingTasks */

$this->title = 'Employee Dashboard';
?>

<div class="employee-dashboard py-4">
    <div class="mb-4">
        <h1>Hello<?= $employee->full_name ? ', ' . Html::encode($employee->full_name) : '' ?></h1>
        <p class="text-muted mb-0">A quick overview of your employee journey and priorities.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h5">My Profile</h2>
                    <p class="text-muted small">Personal data, professional details, and contact information.</p>
                    <p class="mb-1"><strong><?= Html::encode($employee->job_title ?: '—') ?></strong></p>
                    <p class="text-muted small mb-2"><?= Html::encode($employee->department->name ?? '—') ?></p>
                    <span class="badge bg-secondary"><?= Html::encode($employee->getDisplayStatus()) ?></span>
                </div>
                <div class="card-footer bg-transparent">
                    <?= Html::a('View Profile', ['view'], ['class' => 'btn btn-primary w-100']) ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100 <?= $pendingTasks > 0 ? 'border-warning' : '' ?>">
                <div class="card-body">
                    <h2 class="h5">Surveys & Evaluations</h2>
                    <p class="text-muted small">Submit engagement surveys and performance feedback.</p>
                    <div class="display-6"><?= (int)$pendingTasks ?></div>
                    <p class="text-muted small mb-0"><?= (int)$pendingSurveys ?> surveys · <?= (int)$pendingEvaluations ?> evaluations</p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h5">LMS Portal</h2>
                    <p class="text-muted small mb-3">Open the teacher learning management portal.</p>
                    <a href="<?= Html::encode(Url::to(['/employee/employee/lms'])) ?>" class="btn btn-outline-primary btn-sm">Open LMS</a>
                </div>
            </div>
        </div>
    </div>
</div>
