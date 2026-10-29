<?php

/** @var yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;

$bodyClass = $this->params['bodyClass'] ?? 'bg-gray-50 text-slate-900 font-display';

?>

<?php $this->beginPage(); ?>
<!doctype html>
<html lang="<?= Html::encode(Yii::$app->language) ?>">
<head>
    <meta charset="<?= Html::encode(Yii::$app->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>

    <?= $this->render('@frontend/modules/hr/views/layouts/_header.php') ?>

    <?php $this->head(); ?>
</head>
<body class="<?= Html::encode($bodyClass) ?>">
<?php $this->beginBody(); ?>

<?= $content ?>

<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(); ?>

