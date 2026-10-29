<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\Employee $employee
 * @var common\modules\employee\models\EmployeeSchoolSubject[] $assignedSubjects
 * @var common\modules\employee\models\SchoolSectionMaterial[] $sectionMaterials
 * @var bool $showAssignedBlock
 */

use yii\helpers\Html;

?>
<?php if ($showAssignedBlock): ?>
<section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-sm font-bold text-slate-900">My Assigned Subjects</h3>
            <p class="text-xs text-slate-500 mt-1">Subjects linked to your employee profile when your account was created.</p>
        </div>
        <span class="inline-flex items-center h-8 px-3 rounded-full bg-[#642754]/10 text-[#642754] text-sm font-bold">
            <?= count($assignedSubjects) ?> <?= count($assignedSubjects) === 1 ? 'Subject' : 'Subjects' ?>
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50/80">
                    <th class="px-5 py-3.5">Department</th>
                    <th class="px-5 py-3.5">Subject</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($assignedSubjects === []): ?>
                    <tr>
                        <td colspan="2" class="px-5 py-12 text-center text-slate-400 text-sm">No subjects assigned to your profile.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($assignedSubjects as $assignment): ?>
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-5 py-4 text-slate-600"><?= Html::encode($assignment->subject?->department?->name ?? '—') ?></td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-2 font-semibold text-slate-900">
                                <span class="inline-flex size-8 items-center justify-center rounded-lg bg-[#642754]/10 text-[#642754]">
                                    <span class="material-symbols-outlined text-base">menu_book</span>
                                </span>
                                <?= Html::encode($assignment->subject?->name ?? '—') ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-sm font-bold text-slate-900">Sections I Teach</h3>
            <p class="text-xs text-slate-500 mt-1">Sections where you are assigned as instructor for a subject.</p>
        </div>
        <span class="inline-flex items-center h-8 px-3 rounded-full bg-emerald-50 text-emerald-700 text-sm font-bold border border-emerald-100">
            <?= count($sectionMaterials) ?> <?= count($sectionMaterials) === 1 ? 'Assignment' : 'Assignments' ?>
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50/80">
                    <th class="px-5 py-3.5">Department</th>
                    <th class="px-5 py-3.5">Grade</th>
                    <th class="px-5 py-3.5">Section</th>
                    <th class="px-5 py-3.5">Subject</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($sectionMaterials === []): ?>
                    <tr>
                        <td colspan="4" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-slate-400">
                                <span class="material-symbols-outlined text-4xl text-slate-300">school</span>
                                <p class="text-sm font-medium">You are not assigned as instructor in any section yet.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($sectionMaterials as $material): ?>
                    <?php
                    $section = $material->section;
                    $grade = $section?->grade;
                    $department = $grade?->department;
                    $subjectName = trim((string)($material->subject?->name ?? $material->name ?? ''));
                    ?>
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-5 py-4 text-slate-600"><?= Html::encode($department?->name ?? '—') ?></td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                                <?= Html::encode($grade?->name ?? '—') ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-slate-700 font-medium"><?= Html::encode($section?->name ?? '—') ?></td>
                        <td class="px-5 py-4 font-semibold text-slate-900"><?= Html::encode($subjectName !== '' ? $subjectName : '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
