<?php

use yii\helpers\Html;

$this->title = 'Recruiter Dashboard';
?>
<div class="mb-4">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">Recruitment workspace for postings, applications, interviews, and hiring handoff.</p>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        'Published Jobs' => $openJobs,
        'Draft Jobs' => $draftJobs,
        'Applications' => $applications,
        'New Applications' => $newApplications,
    ] as $label => $value): ?>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted"><?= Html::encode($label) ?></div>
                    <div class="display-6"><?= Html::encode($value) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5>Post New Job</h5>
                <p class="text-muted">Create a draft or publish a new role.</p>
                <?= Html::a('Create Posting', ['/recruitment/job/create'], ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5>Job Management</h5>
                <p class="text-muted">Edit, close, or remove current job postings.</p>
                <?= Html::a('View Jobs', ['/recruitment/job/index'], ['class' => 'btn btn-outline-primary']) ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5>Applications</h5>
                <p class="text-muted">Screen candidates, schedule interviews, save feedback, and hire.</p>
                <?= Html::a('Review Applicants', ['applications'], ['class' => 'btn btn-outline-primary']) ?>
            </div>
        </div>
    </div>
</div>
