<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolSection[] $sections
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'My Sections | LMS';

?>
<?= $this->render('../layouts/_page-start') ?>
    <header>
        <h2 class="text-3xl font-black text-slate-900 tracking-tight">My Sections</h2>
        <p class="text-slate-500 mt-2 text-lg">Select a section to manage materials and students.</p>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        <?php foreach ($sections as $section): ?>
            <article class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-col gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400"><?= Html::encode($section->grade?->department?->name ?? 'Department') ?></p>
                    <h3 class="text-xl font-bold text-slate-900 mt-1"><?= Html::encode($section->grade?->name ?? 'Grade') ?> · <?= Html::encode($section->name) ?></h3>
                </div>
                <p class="text-sm text-slate-500"><?= (int)$section->current_enrollment ?> students enrolled</p>
                <a href="<?= Url::to(['/lms/hierarchy/section', 'id' => (int)$section->id]) ?>"
                   class="mt-auto inline-flex items-center justify-center h-10 rounded-lg bg-[#642754] hover:bg-[#7c3a6b] text-white text-sm font-bold no-underline">
                    Open Section
                </a>
            </article>
        <?php endforeach; ?>
    </div>
<?= $this->render('../layouts/_page-end') ?>
