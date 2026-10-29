<?php

/**
 * @var yii\web\View $this
 * @var string $content
 * @var string $pageTitle
 * @var string $currentPage
 * @var string $userName
 * @var string $userRole
 */

use yii\helpers\Html;

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');

?>

<?= $this->render('../layouts/_shell-start', [
    'pageTitle' => $pageTitle ?? $this->title,
    'currentPage' => $currentPage ?? 'survey_evaluation',
    'userName' => $userName ?? 'Employee',
    'userRole' => $userRole ?? 'Employee',
    'legacyRespond' => !empty($legacyRespond),
]) ?>
    <?php if (!empty($legacyRespond)): ?>
        <?= $content ?>
    <?php else: ?>
    <div class="max-w-3xl mx-auto w-full px-6 lg:px-8 py-8">
        <?php if ($successMessage !== ''): ?>
            <div class="mb-4 p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-medium">
                <?= Html::encode($successMessage) ?>
            </div>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <div class="mb-4 p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium">
                <?= Html::encode($errorMessage) ?>
            </div>
        <?php endif; ?>
        <?= $content ?>
    </div>
    <?php endif; ?>
<?= $this->render('../layouts/_shell-end') ?>
