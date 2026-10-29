<?php

/** @var yii\web\View $this */
/** @var string $content */

use common\modules\recruitment\models\Applicant;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

$applicantName = (string)Yii::$app->session->get('rms_applicant_name', 'Applicant');
$applicantPhone = '';
$applicantId = (int)Yii::$app->session->get('rms_applicant_id', 0);
if ($applicantId > 0) {
    $applicantRecord = Applicant::findOne($applicantId);
    if ($applicantRecord !== null && trim((string)$applicantRecord->phone) !== '') {
        $applicantPhone = trim((string)$applicantRecord->phone);
    }
}
$current = $this->params['applicantNav'] ?? 'dashboard';

$this->registerCssFile('@web/css/tailwind.css');
$this->registerLinkTag([
    'rel' => 'stylesheet',
    'href' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
]);
$this->registerLinkTag([
    'rel' => 'stylesheet',
    'href' => 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400&display=swap',
]);

$navClass = static function (string $key) use ($current): string {
    return $key === $current
        ? 'text-[#642754] border-b-2 border-[#642754] text-sm font-bold py-1'
        : 'text-slate-600 hover:text-[#642754] text-sm font-medium py-1 border-b-2 border-transparent';
};
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
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
<?php $this->beginBody() ?>

<header class="bg-white border-b border-slate-200">
    <div class="max-w-[1400px] mx-auto px-6 lg:px-10 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-6">
            <a href="<?= Url::to(['/recruitment/applicant/dashboard']) ?>" class="text-lg font-extrabold text-slate-900 no-underline">
                CultureHR <span class="text-[#642754]">Careers</span>
            </a>
            <nav class="hidden md:flex items-center gap-6">
                <a href="<?= Url::to(['/recruitment/applicant/dashboard']) ?>" class="<?= $navClass('dashboard') ?> no-underline">Job Openings</a>
                <a href="<?= Url::to(['/recruitment/applicant/profile']) ?>" class="<?= $navClass('profile') ?> no-underline">Applicant Profile</a>
                <a href="<?= Url::to(['/recruitment/applicant/status']) ?>" class="<?= $navClass('status') ?> no-underline">Application Status</a>
            </nav>
        </div>
        <div class="flex items-center gap-3">
            <div class="hidden sm:block text-right">
                <p class="text-sm font-semibold text-slate-900"><?= Html::encode($applicantName) ?></p>
                <?php if ($applicantPhone !== ''): ?>
                    <p class="text-xs text-slate-500 flex items-center justify-end gap-1 mt-0.5">
                        <span class="material-symbols-outlined text-sm">phone</span>
                        <?= Html::encode($applicantPhone) ?>
                    </p>
                <?php endif; ?>
            </div>
            <?= Html::beginForm(['/recruitment/applicant/logout'], 'post', ['class' => 'm-0']) ?>
                <button type="submit" class="text-sm font-semibold text-slate-600 hover:text-[#642754] bg-transparent border-0 cursor-pointer">
                    Sign out
                </button>
            <?= Html::endForm() ?>
        </div>
    </div>
</header>

<?php
$success = (string)Yii::$app->session->getFlash('success', '');
$error = (string)Yii::$app->session->getFlash('error', '');
$info = (string)Yii::$app->session->getFlash('info', '');
?>
<?php if ($success !== '' || $error !== '' || $info !== ''): ?>
    <div class="max-w-[1400px] mx-auto px-6 lg:px-10 pt-4">
        <?php if ($success !== ''): ?>
            <div class="mb-3 p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-medium"><?= Html::encode($success) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="mb-3 p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium"><?= Html::encode($error) ?></div>
        <?php endif; ?>
        <?php if ($info !== ''): ?>
            <div class="mb-3 p-4 rounded-xl bg-blue-50 text-blue-800 border border-blue-200 text-sm font-medium"><?= Html::encode($info) ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?= $content ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
