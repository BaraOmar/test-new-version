<?php

/** @var yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;

$bodyClass = $this->params['bodyClass'] ?? '';

?>

<?php $this->beginPage(); ?>
<!doctype html>
<html lang="<?= Html::encode(Yii::$app->language) ?>">
<head>
    <meta charset="<?= Html::encode(Yii::$app->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>

    <?php // Registers Tailwind/fonts/theme CSS exactly like legacy ?>
    <?= $this->render('@frontend/modules/employee/views/layouts/_header.php') ?>

    <?php $this->head(); ?>
</head>
<body class="<?= Html::encode($bodyClass) ?>">
<?php $this->beginBody(); ?>

<?= $content ?>

<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(); ?>

