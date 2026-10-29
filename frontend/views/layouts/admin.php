<?php

/** @var \yii\web\View $this */
/** @var string $content */

use common\widgets\Alert;
use frontend\assets\AppAsset;
use yii\helpers\Html;

AppAsset::register($this);
$currentPage = $this->params['currentPage'] ?? '';
$pageTitle = $this->title;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<div class="d-flex min-vh-100">
    <?= $this->render('_adminSidebar', ['currentPage' => $currentPage]) ?>
    <div class="flex-grow-1">
        <?= $this->render('_adminHeader', ['pageTitle' => $pageTitle]) ?>
        <main class="container-fluid p-4">
            <?= Alert::widget() ?>
            <?= $content ?>
        </main>
    </div>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
