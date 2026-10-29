<?php

/**
 * @var yii\web\View $this
 * @var int|string $subjectIndex
 * @var array{name:string,grade_ids:int[],grade_credits:array<int|string,string>,grade_max_scores:array<int|string,string>} $subject
 * @var string[] $departmentGrades
 * @var bool $isTemplate
 */

use yii\helpers\Html;

$isTemplate = !empty($isTemplate);
$subjectIndex = $isTemplate ? '__INDEX__' : (int)$subjectIndex;
$subjectName = (string)($subject['name'] ?? '');
$selectedGradeIds = array_map('intval', (array)($subject['grade_ids'] ?? []));
$gradeMaxScores = (array)($subject['grade_max_scores'] ?? []);
$departmentGrades = $departmentGrades ?? [''];

?>
<div class="department-subject-card rounded-xl border border-slate-200 bg-slate-50/60 p-5 space-y-4" data-subject-index="<?= Html::encode((string)$subjectIndex) ?>">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="subject-card-title text-base font-bold text-slate-900">
            Subject <?= $isTemplate ? '__NUMBER__' : ((int)$subjectIndex + 1) ?>
        </h3>
        <button type="button"
                class="remove-subject-row h-9 px-3 rounded-lg border border-red-200 text-red-600 text-xs font-semibold bg-white cursor-pointer disabled:opacity-40">
            Remove
        </button>
    </div>

    <div>
        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">
            Subject Name <span class="text-red-500">*</span>
        </label>
        <?= Html::textInput('subjects[' . $subjectIndex . '][name]', $subjectName, [
            'required' => true,
            'placeholder' => 'e.g. Advanced Physics',
            'class' => 'subject-name-input w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm',
        ]) ?>
    </div>

    <div>
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Assign Grades</label>
                <p class="text-sm text-slate-500">Select one or more grades for this subject. Each selected grade gets its own maximum mark field.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="selected-grade-count text-xs font-semibold px-2.5 py-1 rounded-full bg-white text-slate-600 border border-slate-200">0 selected</span>
                <button type="button" class="select-all-grades h-9 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold cursor-pointer disabled:opacity-40">Select all</button>
                <button type="button" class="clear-all-grades h-9 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold cursor-pointer disabled:opacity-40">Clear all</button>
            </div>
        </div>
        <div class="subject-grade-fields grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php if ($departmentGrades === []): ?>
                <p class="text-sm text-slate-400 italic md:col-span-2">Add at least one grade above before assigning subjects.</p>
            <?php else: ?>
                <?php foreach ($departmentGrades as $gradeIndex => $gradeName): ?>
                    <?php
                    $isChecked = in_array((int)$gradeIndex, $selectedGradeIds, true);
                    $maxScoreValue = (string)($gradeMaxScores[$gradeIndex] ?? $gradeMaxScores[(string)$gradeIndex] ?? '100');
                    $displayName = trim((string)$gradeName) !== '' ? (string)$gradeName : ('Grade ' . ((int)$gradeIndex + 1));
                    ?>
                    <div data-grade-row class="rounded-xl border border-slate-200 bg-white p-4">
                        <label class="flex items-center gap-3 cursor-pointer mb-3">
                            <?= Html::checkbox(
                                'subjects[' . $subjectIndex . '][grade_ids][]',
                                $isChecked,
                                [
                                    'value' => (int)$gradeIndex,
                                    'class' => 'grade-select-checkbox rounded border-slate-300',
                                ]
                            ) ?>
                            <span class="font-semibold text-slate-800"><?= Html::encode($displayName) ?></span>
                        </label>
                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Maximum Mark</label>
                        <?= Html::textInput('subjects[' . $subjectIndex . '][grade_max_scores][' . (int)$gradeIndex . ']', $maxScoreValue, [
                            'type' => 'number',
                            'min' => '0.01',
                            'step' => '0.01',
                            'placeholder' => 'e.g. 100',
                            'class' => 'grade-max-score-input w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm disabled:bg-slate-100 disabled:text-slate-400',
                            'disabled' => !$isChecked,
                            'required' => $isChecked,
                        ]) ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
