<?php

use common\modules\document\models\Document;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var Document $model */
/** @var bool $canPreview */

$this->title = $model->title;
?>

<div class="document-view py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-1"><?= Html::encode($model->title) ?></h1>
            <p class="text-muted mb-0"><?= Html::encode($model->original_name) ?></p>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a('Back to Uploads', ['upload'], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('Download', ['view', 'id' => $model->id, 'download' => 1], ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <dl class="mb-0">
                        <dt>Description</dt>
                        <dd><?= Html::encode($model->description ?: 'No description') ?></dd>
                        <dt>MIME Type</dt>
                        <dd><?= Html::encode($model->mime_type) ?></dd>
                        <dt>Size</dt>
                        <dd><?= Html::encode($model->getFormattedSize()) ?></dd>
                        <dt>SHA-256</dt>
                        <dd class="small text-break"><?= Html::encode($model->sha256_hash) ?></dd>
                        <dt>Uploaded</dt>
                        <dd><?= Yii::$app->formatter->asDatetime($model->created_at) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <?php if ($canPreview && $model->extension === 'pdf'): ?>
                        <embed src="<?= Html::encode(\yii\helpers\Url::to(['view', 'id' => $model->id, 'file' => 1])) ?>" type="application/pdf" style="width: 100%; min-height: 640px;">
                    <?php elseif ($canPreview && in_array($model->extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)): ?>
                        <img src="<?= Html::encode(\yii\helpers\Url::to(['view', 'id' => $model->id, 'file' => 1])) ?>" class="img-fluid" alt="<?= Html::encode($model->title) ?>">
                    <?php else: ?>
                        <p class="text-muted mb-0">Preview is not available for this file type. Use Download to access the file.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
