<?php

use common\modules\recruitment\models\Job;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = $model->isNewRecord ? 'Post New Job' : 'Edit Job';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="text-muted mb-0">Draft the posting, then publish when ready.</p>
    </div>
    <?= Html::a('Back to Jobs', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
</div>

<?php $form = ActiveForm::begin(); ?>
<div class="row">
    <div class="col-md-6">
        <?= $form->field($model, 'job_title')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($model, 'department')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($model, 'location')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-12">
        <?= $form->field($model, 'description')->textarea(['rows' => 8]) ?>
    </div>
    <div class="col-md-4">
        <?= $form->field($model, 'status')->dropDownList(Job::statusOptions()) ?>
    </div>
</div>
<div class="d-flex gap-2">
    <?= Html::submitButton('Save Draft', ['class' => 'btn btn-outline-primary', 'name' => 'submit', 'value' => 'draft']) ?>
    <?= Html::submitButton('Publish', ['class' => 'btn btn-primary', 'name' => 'submit', 'value' => 'publish']) ?>
</div>
<?php ActiveForm::end(); ?>
