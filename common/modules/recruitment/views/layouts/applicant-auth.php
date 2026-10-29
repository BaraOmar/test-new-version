<?php

/** @var yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;

$showHeader = !empty($this->params['applicantAuthHeader']);

$this->registerCssFile('@web/css/tailwind.css');
$this->registerLinkTag([
    'rel' => 'stylesheet',
    'href' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
]);
$this->registerLinkTag([
    'rel' => 'stylesheet',
    'href' => 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400&display=swap',
]);
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
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .applicant-auth-bg {
            background-color: #f1f5f9;
            background-image:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(100, 39, 84, 0.12), transparent),
                radial-gradient(ellipse 60% 40% at 100% 100%, rgba(235, 101, 44, 0.08), transparent),
                radial-gradient(ellipse 50% 30% at 0% 80%, rgba(100, 39, 84, 0.06), transparent);
        }
        .applicant-auth-card {
            width: 100%;
            max-width: 28rem;
        }
        @media (min-width: 640px) {
            .applicant-auth-card {
                max-width: 30rem;
            }
        }
    </style>
</head>
<body class="applicant-auth-bg min-h-[100dvh] flex flex-col text-slate-900">
<?php $this->beginBody() ?>

<?php if ($showHeader): ?>
    <header class="relative z-10 bg-white/80 backdrop-blur-md border-b border-slate-200/80 shrink-0">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center gap-3">
            <div class="size-10 rounded-xl bg-[#642754] text-white flex items-center justify-center font-bold text-lg shadow-sm shadow-[#642754]/20">C</div>
            <div class="min-w-0">
                <span class="text-[#642754] font-bold text-lg sm:text-xl tracking-tight block truncate">CultureHR Careers</span>
                <span class="text-xs text-slate-500 hidden sm:block">Applicant portal</span>
            </div>
        </div>
    </header>
<?php endif; ?>

<main class="relative z-10 flex-1 flex items-center justify-center px-4 py-8 sm:px-6 sm:py-12">
    <?= $content ?>
</main>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
