<?php

use common\modules\recruitment\models\Application;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Applications & Interviews';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="text-muted mb-0">Review applicants, move pipeline stages, schedule interviews, and convert hires.</p>
    </div>
    <?= Html::a('Recruiter Dashboard', ['dashboard'], ['class' => 'btn btn-outline-secondary']) ?>
</div>

<div class="row g-2 mb-4">
    <?php foreach ($statusCounts as $label => $count): ?>
        <div class="col-auto">
            <?= Html::a(Html::encode($label) . ' ' . Html::tag('span', $count, ['class' => 'badge bg-secondary']), ['applications', 'status' => $label, 'search' => $search], ['class' => 'btn btn-sm btn-outline-primary']) ?>
        </div>
    <?php endforeach; ?>
</div>

<?= Html::beginForm(['applications'], 'get', ['class' => 'row g-2 mb-4']) ?>
<div class="col-md-7">
    <?= Html::textInput('search', $search, ['class' => 'form-control', 'placeholder' => 'Search applicant, email, or job']) ?>
</div>
<div class="col-md-3">
    <?= Html::dropDownList('status', $status, ['' => 'All statuses'] + Application::statusOptions(), ['class' => 'form-select']) ?>
</div>
<div class="col-md-2 d-grid">
    <?= Html::submitButton('Filter', ['class' => 'btn btn-outline-primary']) ?>
</div>
<?= Html::endForm() ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        [
            'label' => 'Applicant',
            'format' => 'raw',
            'value' => static fn(Application $model) => $model->applicant
                ? Html::tag('strong', Html::encode($model->applicant->full_name)) . Html::tag('div', Html::encode($model->applicant->email), ['class' => 'text-muted small'])
                : 'Removed applicant',
        ],
        [
            'label' => 'Job',
            'value' => static fn(Application $model) => $model->job ? $model->job->job_title : 'Removed job',
        ],
        'application_status',
        'interview_at:datetime',
        'interview_type',
        'feedback_rating',
        [
            'label' => 'Actions',
            'format' => 'raw',
            'value' => static function (Application $model) {
                $statusForm = Html::beginForm(['applications'], 'post', ['class' => 'd-inline-flex gap-1 align-items-center mb-1'])
                    . Html::hiddenInput('application_id', $model->application_id)
                    . Html::hiddenInput('action', 'update_status')
                    . Html::dropDownList('application_status', $model->application_status, Application::statusOptions(), ['class' => 'form-select form-select-sm'])
                    . Html::submitButton('Update', ['class' => 'btn btn-sm btn-outline-primary'])
                    . Html::endForm();

                $interviewForm = Html::beginForm(['applications'], 'post', ['class' => 'd-flex gap-1 align-items-center mb-1'])
                    . Html::hiddenInput('application_id', $model->application_id)
                    . Html::hiddenInput('action', 'schedule_interview')
                    . Html::input('date', 'interview_date', $model->interview_at ? date('Y-m-d', strtotime($model->interview_at)) : '', ['class' => 'form-control form-control-sm'])
                    . Html::input('time', 'interview_time', $model->interview_at ? date('H:i', strtotime($model->interview_at)) : '', ['class' => 'form-control form-control-sm'])
                    . Html::dropDownList('interview_type', $model->interview_type, ['Google Meet' => 'Google Meet', 'Zoom' => 'Zoom', 'In-Person Office' => 'In-Person Office'], ['class' => 'form-select form-select-sm'])
                    . Html::submitButton('Schedule', ['class' => 'btn btn-sm btn-outline-secondary'])
                    . Html::endForm();

                $feedbackForm = Html::beginForm(['applications'], 'post', ['class' => 'd-flex gap-1 align-items-center mb-1'])
                    . Html::hiddenInput('application_id', $model->application_id)
                    . Html::hiddenInput('action', 'save_feedback')
                    . Html::dropDownList('feedback_rating', $model->feedback_rating, ['' => 'Rating', 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5], ['class' => 'form-select form-select-sm'])
                    . Html::textInput('feedback_notes', $model->feedback_notes, ['class' => 'form-control form-control-sm', 'placeholder' => 'Feedback'])
                    . Html::submitButton('Save', ['class' => 'btn btn-sm btn-outline-secondary'])
                    . Html::endForm();

                $convertButton = Html::a('Convert to Employee', ['convert-applicant', 'id' => $model->application_id], [
                    'class' => 'btn btn-sm btn-success',
                    'data' => ['method' => 'post', 'confirm' => 'Convert this applicant into an employee record?'],
                ]);

                return $statusForm . $interviewForm . $feedbackForm . $convertButton;
            },
        ],
    ],
]) ?>
