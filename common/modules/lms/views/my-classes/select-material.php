<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolSection $section
 * @var array<string, mixed> $card
 * @var common\modules\employee\models\SchoolSectionMaterial[] $materials
 * @var array<int, array{max:float,pass:float}> $materialScores
 * @var int[] $editableMaterialIds
 * @var string $title
 * @var string $description
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $title . ' | My Classes';

$editableMaterialIds = $editableMaterialIds ?? [];
$showAccessMode = $editableMaterialIds !== [];

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

    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php foreach ($materials as $material): ?>
            <?php
            $subjectName = trim((string)($material->subject?->name ?? ''));
            $label = $subjectName !== '' ? $subjectName : trim((string)$material->name);
            $scores = $materialScores[(int)$material->id] ?? ['max' => 100.0, 'pass' => 50.0];
            $maxLabel = rtrim(rtrim(number_format((float)$scores['max'], 1, '.', ''), '0'), '.');
            $passLabel = rtrim(rtrim(number_format((float)$scores['pass'], 1, '.', ''), '0'), '.');
            $canEdit = !$showAccessMode || in_array((int)$material->id, $editableMaterialIds, true);
            $materialId = (int)$material->id;
            $sectionId = (int)$section->id;
            ?>
            <article class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-col">
                <div class="flex items-start justify-between gap-3">
                    <div class="size-11 rounded-xl bg-[#1e3a8a]/10 text-[#1e3a8a] flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined">menu_book</span>
                    </div>
                    <?php if ($showAccessMode): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide <?= $canEdit ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>">
                            <?= $canEdit ? 'Can edit' : 'View only' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mt-4"><?= Html::encode($label !== '' ? $label : 'Course') ?></h3>
                <p class="text-sm text-slate-500 mt-1">Maximum mark: <?= Html::encode($maxLabel) ?> · Pass mark: <?= Html::encode($passLabel) ?></p>
                <div class="flex flex-wrap gap-2 mt-5">
                    <a href="<?= Url::to(['marks', 'section_id' => $sectionId, 'material_id' => $materialId]) ?>"
                       class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-xs font-bold no-underline">
                        Marks
                    </a>
                    <a href="<?= Url::to(['materials', 'section_id' => $sectionId, 'material_id' => $materialId]) ?>"
                       class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-sky-50 hover:bg-sky-100 text-sky-800 border border-sky-100 text-xs font-bold no-underline">
                        Materials
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?= $this->render('../layouts/_page-end') ?>
