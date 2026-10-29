<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolSection $section
 * @var array<string, mixed> $card
 * @var string $actionKey
 * @var string $title
 * @var string $description
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $title . ' | My Classes';

?>
<?= $this->render('../layouts/_page-start') ?>
    <a href="<?= Url::to(['index']) ?>" class="inline-flex items-center gap-1 text-sm font-semibold text-[#642754] no-underline">
        <span class="material-symbols-outlined text-base">arrow_back</span>
        Back to My Classes
    </a>

    <header class="mt-4">
        <h2 class="text-3xl font-black text-slate-900 tracking-tight"><?= Html::encode($title) ?></h2>
        <p class="text-slate-500 mt-2 text-lg"><?= Html::encode($description) ?></p>
        <p class="text-sm font-semibold text-[#1e3a8a] mt-2"><?= Html::encode((string)($card['title'] ?? '')) ?></p>
    </header>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
        <span class="material-symbols-outlined text-5xl text-slate-300">construction</span>
        <h3 class="text-lg font-bold text-slate-900 mt-4"><?= Html::encode($title) ?> coming soon</h3>
        <p class="text-sm text-slate-500 mt-2 max-w-lg mx-auto">
            This workflow for <span class="font-semibold text-slate-700"><?= Html::encode((string)($card['title'] ?? 'this section')) ?></span>
            will be available in a future update.
        </p>
        <div class="flex flex-wrap justify-center gap-3 mt-6">
            <a href="<?= Url::to(['index']) ?>"
               class="inline-flex items-center h-10 px-4 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-bold no-underline">
                Back to My Classes
            </a>
        </div>
    </section>
<?= $this->render('../layouts/_page-end') ?>
