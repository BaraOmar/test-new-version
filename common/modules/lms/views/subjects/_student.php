<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudent $student
 * @var common\modules\employee\models\SchoolSectionMaterial[] $materials
 */

use yii\helpers\Html;

$gradeLabel = trim((string)($student->grade?->name ?? ''));
$sectionLabel = trim((string)($student->section?->name ?? ''));
$classLabel = $gradeLabel !== '' && $sectionLabel !== ''
    ? $gradeLabel . ' · Section ' . $sectionLabel
    : ($gradeLabel !== '' ? $gradeLabel : 'Your section');

?>
<section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-sm font-bold text-slate-900">My Subjects</h3>
            <p class="text-xs text-slate-500 mt-1"><?= Html::encode($classLabel) ?></p>
        </div>
        <span class="inline-flex items-center h-8 px-3 rounded-full bg-[#642754]/10 text-[#642754] text-sm font-bold">
            <?= count($materials) ?> <?= count($materials) === 1 ? 'Subject' : 'Subjects' ?>
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50/80">
                    <th class="px-5 py-3.5">Subject</th>
                    <th class="px-5 py-3.5">Teacher</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($materials === []): ?>
                    <tr>
                        <td colspan="2" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-slate-400">
                                <span class="material-symbols-outlined text-4xl text-slate-300">menu_book</span>
                                <p class="text-sm font-medium">No subjects assigned to your section yet.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($materials as $material): ?>
                    <?php
                    $subjectName = trim((string)($material->subject?->name ?? $material->name ?? ''));
                    $teacherName = trim((string)($material->instructor?->full_name ?? ''));
                    ?>
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-2 font-semibold text-slate-900">
                                <span class="inline-flex size-8 items-center justify-center rounded-lg bg-[#642754]/10 text-[#642754]">
                                    <span class="material-symbols-outlined text-base">menu_book</span>
                                </span>
                                <?= Html::encode($subjectName !== '' ? $subjectName : '—') ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            <?= Html::encode($teacherName !== '' ? $teacherName : 'Not assigned') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
