<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\modules\employee\models\Employee $employee */
/** @var array $profile */

$this->title = $employee->full_name;
?>

<div class="employee-view py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-1"><?= Html::encode($employee->full_name) ?></h1>
            <p class="text-muted mb-2"><?= Html::encode($employee->job_title) ?></p>
            <span class="badge bg-secondary"><?= Html::encode($employee->getDisplayStatus()) ?></span>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a('Request Change', ['request-change'], ['class' => 'btn btn-outline-primary']) ?>
            <?= Html::a('Back to Dashboard', ['dashboard'], ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-bold">Personal Information</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Full Name</dt>
                        <dd class="col-sm-8"><?= Html::encode($employee->full_name) ?></dd>
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8"><?= Html::encode($employee->email) ?></dd>
                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8"><?= Html::encode($profile['phone']) ?></dd>
                        <dt class="col-sm-4">Marital Status</dt>
                        <dd class="col-sm-8"><?= Html::encode($profile['maritalStatus']) ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-bold">Professional Details</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Job Title</dt>
                        <dd class="col-sm-8"><?= Html::encode($employee->job_title) ?></dd>
                        <dt class="col-sm-4">Position Level</dt>
                        <dd class="col-sm-8"><?= Html::encode($profile['positionLevel']) ?></dd>
                        <dt class="col-sm-4">Joining Date</dt>
                        <dd class="col-sm-8"><?= Html::encode($profile['joiningDate']) ?></dd>
                        <dt class="col-sm-4">Employment Type</dt>
                        <dd class="col-sm-8"><?= Html::encode($profile['employmentType']) ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header fw-bold">Reporting Structure</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-2">Manager</dt>
                        <dd class="col-sm-10"><?= Html::encode($employee->manager->full_name ?? 'No Manager Assigned') ?></dd>
                        <dt class="col-sm-2">Department</dt>
                        <dd class="col-sm-10"><?= Html::encode($employee->department->name ?? '—') ?></dd>
                        <dt class="col-sm-2">Work Location</dt>
                        <dd class="col-sm-10"><?= Html::encode($profile['location']) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
