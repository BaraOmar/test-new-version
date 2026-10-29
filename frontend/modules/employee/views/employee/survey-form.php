<?php

/**
 * UI-only (pixel reference from legacy `survey_form.php`).
 *
 * @var yii\web\View $this
 * @var string $pageTitle
 * @var string $navLinksHtml
 * @var string $surveyTitle
 * @var string $surveyDescription
 * @var string $questionsHtml
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $pageTitle ?? 'Survey Response Interface';

?>

<div class="relative flex min-h-screen w-full flex-col">

    <header class="flex items-center justify-between bg-[#642754] border-b border-white/10 px-6 py-2 sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <div class="size-10 flex items-center justify-center rounded-lg bg-[#eb652c] text-white">
                <span class="material-symbols-outlined text-2xl">assignment</span>
            </div>
            <h2 class="text-white text-xl font-normal">CulturePulse</h2>
        </div>
    </header>

    <main class="flex flex-1 justify-center py-6 px-4">
        <div class="flex flex-col max-w-[770px] w-full gap-3">
            <div class="bg-white rounded-[8px] shadow-[0_2px_1px_-1px_rgba(0,0,0,0.2),0_1px_1px_0_rgba(0,0,0,0.14),0_1px_3px_0_rgba(0,0,0,0.12)] border border-[#e8e0e8] overflow-hidden border-t-[10px] border-[#eb652c] p-6 pt-5">
                <h1 class="text-3xl font-normal mb-2"><?= Html::encode($surveyTitle) ?></h1>
                <p class="text-sm text-[#202124] mb-4"><?= Html::encode($surveyDescription) ?></p>
            </div>

            <?= $questionsHtml ?>

            <div class="flex items-center justify-between py-4">
                <button type="button" class="text-primary hover:bg-primary/5 px-6 py-2 rounded-[8px] text-sm font-medium transition-colors">
                    Clear form
                </button>
                <div class="flex items-center gap-3">
                    <a href="<?= Url::to(['/employee/employee/survey-display']) ?>" class="bg-[#eb652c] hover:bg-[#d85a24] text-white px-8 py-2 rounded-[8px] text-sm font-medium shadow-sm transition-all">
                        Back to my tasks
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

