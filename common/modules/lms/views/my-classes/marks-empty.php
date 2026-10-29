<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolSection $section
 * @var array<string, mixed> $card
 * @var bool $hasCourses
 * @var bool $canManageCourses
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Grades & Marks | My Classes';

$gradeName = trim((string)($section->grade?->name ?? ''));
$sectionLabel = $gradeName !== '' ? $gradeName . ' · ' . $section->name : (string)$section->name;

?>
<?= $this->render('../layouts/_page-start') ?>
    <a href="<?= Url::to(['index']) ?>" class="inline-flex items-center gap-1 text-sm font-semibold text-[#642754] no-underline">
        <span class="material-symbols-outlined text-base">arrow_back</span>
        Back to My Classes
    </a>

    <header class="mt-4">
        <h2 class="text-3xl font-black text-slate-900 tracking-tight">No Marks Available</h2>
        <p class="text-slate-500 mt-2 text-lg"><?= Html::encode($sectionLabel) ?></p>
    </header>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center max-w-2xl">
        <div class="size-16 rounded-2xl bg-amber-50 text-amber-600 inline-flex items-center justify-center mb-5">
            <span class="material-symbols-outlined text-4xl">info</span>
        </div>
        <?php if ($hasCourses): ?>
            <h3 class="text-xl font-bold text-slate-900">No courses you can access for marks</h3>
            <p class="text-slate-500 mt-3 leading-relaxed">
                This section has courses assigned, but none are available for marks with your current role.
                Subject teachers can only open courses they instruct. Class mentors and department leaders can view all courses once instructors are assigned.
            </p>
        <?php else: ?>
            <h3 class="text-xl font-bold text-slate-900">No courses assigned yet</h3>
            <p class="text-slate-500 mt-3 leading-relaxed">
                Assign subjects to this section before recording marks.
                <?php if ($canManageCourses): ?>
                    Open section operations to add courses and assign instructors.
                <?php else: ?>
                    Ask your department leader or class mentor to assign courses to this section.
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <div class="flex flex-wrap items-center justify-center gap-3 mt-8">
            <a href="<?= Url::to(['index']) ?>"
               class="inline-flex items-center h-11 px-5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-bold no-underline">
                Back to My Classes
            </a>
            <?php if ($canManageCourses): ?>
                <a href="<?= Url::to(['operations', 'section_id' => (int)$section->id]) ?>"
                   class="inline-flex items-center h-11 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold no-underline">
                    Manage Section
                </a>
            <?php endif; ?>
        </div>
    </section>
<?= $this->render('../layouts/_page-end') ?>
