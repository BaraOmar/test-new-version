<?php

/**
 * Odoo-matching CultureHR LMS login shell.
 *
 * @var yii\web\View $this
 * @var string $content
 */

use yii\helpers\Html;

$this->registerCssFile('@web/css/culturehr-login.css');
$this->registerCsrfMetaTags();
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->title ?: 'LMS') ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<?= $content ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
