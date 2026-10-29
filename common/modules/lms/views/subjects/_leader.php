<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolSubject[] $departmentSubjects
 * @var common\modules\employee\models\SchoolDepartment[] $departments
 */

use yii\helpers\Html;

$departmentNames = [];
foreach ($departments as $department) {
    $departmentNames[(int)$department->id] = (string)$department->name;
}

?>
<section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-sm font-bold text-slate-900">Department Subjects</h3>
            <p class="text-xs text-slate-500 mt-1">Subjects in your department with grade credits.</p>
        </div>
        <span class="inline-flex items-center h-8 px-3 rounded-full bg-[#642754]/10 text-[#642754] text-sm font-bold">
            <?= count($departmentSubjects) ?> <?= count($departmentSubjects) === 1 ? 'Subject' : 'Subjects' ?>
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50/80">
                    <th class="px-5 py-3.5">Department</th>
                    <th class="px-5 py-3.5">Subject</th>
                    <th class="px-5 py-3.5">Grades &amp; Credits</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($departmentSubjects === []): ?>
                    <tr>
                        <td colspan="3" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-slate-400">
                                <span class="material-symbols-outlined text-4xl text-slate-300">menu_book</span>
                                <p class="text-sm font-medium">No subjects in your department yet.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($departmentSubjects as $subject): ?>
                    <?php
                    $gradeLabels = [];
                    foreach ($subject->subjectGrades as $subjectGrade) {
                        $gradeName = trim((string)($subjectGrade->grade?->name ?? ''));
                        $credits = rtrim(rtrim(number_format((float)$subjectGrade->credits, 2, '.', ''), '0'), '.');
                        if ($gradeName !== '') {
                            $gradeLabels[] = $gradeName . ' (' . $credits . ' cr)';
                        }
                    }
                    ?>
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-5 py-4 text-slate-600"><?= Html::encode($subject->department?->name ?? ($departmentNames[(int)$subject->school_department_id] ?? '—')) ?></td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-2 font-semibold text-slate-900">
                                <span class="inline-flex size-8 items-center justify-center rounded-lg bg-[#642754]/10 text-[#642754]">
                                    <span class="material-symbols-outlined text-base">menu_book</span>
                                </span>
                                <?= Html::encode($subject->name) ?>
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <?php if ($gradeLabels === []): ?>
                                <span class="text-slate-400">No grades assigned</span>
                            <?php else: ?>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($gradeLabels as $label): ?>
                                        <span class="inline-flex items-center h-8 px-3 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100">
                                            <?= Html::encode($label) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
