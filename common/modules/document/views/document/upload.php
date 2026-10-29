<?php

use common\modules\document\models\Document;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var Document $model */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Secure Document Upload';
?>

<div class="document-upload py-4">
    <div class="mb-4">
        <h1 class="mb-1"><?= Html::encode($this->title) ?></h1>
        <p class="text-muted mb-0">Upload documents into protected server storage and serve them through Yii actions.</p>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'uploadFile')->fileInput([
                        'accept' => '.mp4,.webm,.ogg,.mov,.avi,.mkv,.pdf,.pptx,.ppt,.docx,.doc,.jpg,.jpeg,.png,.gif,.webp,.bmp',
                    ]) ?>
                </div>
                <div class="col-12">
                    <?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <?= Html::submitButton('Upload', ['class' => 'btn btn-primary']) ?>
                <?= Html::resetButton('Clear', ['class' => 'btn btn-outline-secondary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <strong>Uploaded Documents</strong>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
                'columns' => [
                    'title',
                    'original_name',
                    'extension',
                    [
                        'label' => 'Size',
                        'value' => static fn(Document $document) => $document->getFormattedSize(),
                    ],
                    'created_at:datetime',
                    [
                        'format' => 'raw',
                        'value' => static fn(Document $document) => Html::a('View', ['view', 'id' => $document->id], ['class' => 'btn btn-sm btn-outline-primary']),
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
