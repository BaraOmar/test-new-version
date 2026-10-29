<?php

use common\modules\recruitment\models\Job;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Job Management';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="text-muted mb-0">Create, publish, close, and review your recruitment postings.</p>
    </div>
    <?= Html::a('Post New Job', ['create'], ['class' => 'btn btn-primary']) ?>
</div>

<?= Html::beginForm(['index'], 'get', ['class' => 'row g-2 mb-4']) ?>
<div class="col-md-7">
    <?= Html::textInput('search', $search, ['class' => 'form-control', 'placeholder' => 'Search title, department, or location']) ?>
</div>
<div class="col-md-3">
    <?= Html::dropDownList('status', $status, ['' => 'All statuses'] + Job::statusOptions(), ['class' => 'form-select']) ?>
</div>
<div class="col-md-2 d-grid">
    <?= Html::submitButton('Filter', ['class' => 'btn btn-outline-primary']) ?>
</div>
<?= Html::endForm() ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'job_title',
        'department',
        'location',
        'status',
        [
            'label' => 'Applications',
            'value' => static fn(Job $model) => count($model->applications),
        ],
        'created_at:datetime',
        [
            'class' => yii\grid\ActionColumn::class,
            'template' => '{update} {close} {delete}',
            'buttons' => [
                'update' => static fn($url, Job $model) => Html::a('Edit', ['create', 'id' => $model->job_id], ['class' => 'btn btn-sm btn-outline-secondary']),
                'close' => static fn($url, Job $model) => Html::a('Close', ['close', 'id' => $model->job_id], [
                    'class' => 'btn btn-sm btn-outline-warning',
                    'data' => ['method' => 'post', 'confirm' => 'Close this job?'],
                ]),
                'delete' => static fn($url, Job $model) => Html::a('Delete', ['delete', 'id' => $model->job_id], [
                    'class' => 'btn btn-sm btn-outline-danger',
                    'data' => ['method' => 'post', 'confirm' => 'Delete this job?'],
                ]),
            ],
        ],
    ],
]) ?>
