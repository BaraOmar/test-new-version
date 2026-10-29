<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudent $student
 * @var bool $isStaffView
 * @var array{
 *     academicSessionLabel:string,
 *     sectionLabel:string,
 *     rows:array<int, array<string, mixed>>
 * } $gradebook
 * @var array|string|null $backUrl
 * @var string|null $backLabel
 */

use common\modules\employee\models\SchoolStudentSectionMark;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = ($isStaffView ? $student->getDisplayFullName() . ' — Marks' : 'My Gradebook') . ' | LMS';

$rows = $gradebook['rows'] ?? [];
$academicSessionLabel = (string)($gradebook['academicSessionLabel'] ?? 'Academic Session');
$sectionLabel = (string)($gradebook['sectionLabel'] ?? '');
$passedCount = 0;
$recordedCount = 0;

foreach ($rows as $row) {
    if (($row['total'] ?? null) !== null) {
        $recordedCount++;
        if (($row['status'] ?? '') === SchoolStudentSectionMark::STATUS_PASS) {
            $passedCount++;
        }
    }
}

?>
<?= $this->render('../layouts/_page-start') ?>
    <?php if ($isStaffView && $backUrl !== null): ?>
        <nav aria-label="Breadcrumb" class="text-sm font-medium text-slate-500">
            <ol class="flex items-center flex-wrap gap-x-2 gap-y-1">
                <li><a href="<?= Html::encode(Url::to(['students'])) ?>" class="hover:text-[#642754] no-underline">Student Registry</a></li>
                <li class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-base text-slate-300">chevron_right</span>
                    <a href="<?= Html::encode(Url::to(['view', 'id' => (int)$student->id])) ?>" class="hover:text-[#642754] no-underline"><?= Html::encode($student->getDisplayFullName()) ?></a>
                </li>
                <li class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-base text-slate-300">chevron_right</span>
                    <span class="text-slate-900">Marks</span>
                </li>
            </ol>
        </nav>
    <?php endif; ?>

    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">
                <?= $isStaffView ? Html::encode($student->getDisplayFullName()) . ' — Marks' : 'My Gradebook' ?>
            </h2>
            <p class="text-slate-500 mt-2 text-lg flex flex-wrap items-center gap-2">
                <span class="material-symbols-outlined text-base">calendar_month</span>
                <?= Html::encode($academicSessionLabel) ?>
                <?php if ($sectionLabel !== ''): ?>
                    <span class="text-slate-300">·</span>
                    <?= Html::encode($sectionLabel) ?>
                <?php endif; ?>
            </p>
        </div>
        <?php if ($isStaffView && $backUrl !== null && $backLabel !== null): ?>
            <a href="<?= Html::encode(Url::to($backUrl)) ?>"
               class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold no-underline">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                <?= Html::encode($backLabel) ?>
            </a>
        <?php endif; ?>
    </header>

    <?= $this->render('_profile-section-toggle-wrapper', ['model' => $student, 'activeTab' => 'marks']) ?>

    <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Subjects</p>
            <p class="text-3xl font-black text-[#1e3a8a] mt-1"><?= count($rows) ?></p>
            <p class="text-sm text-slate-500 mt-1">Enrolled courses in your section</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Recorded Marks</p>
            <p class="text-3xl font-black text-slate-900 mt-1"><?= $recordedCount ?> / <?= count($rows) ?></p>
            <p class="text-sm text-slate-500 mt-1">Subjects with all four marks entered</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Passing Subjects</p>
            <p class="text-3xl font-black text-emerald-600 mt-1"><?= $passedCount ?></p>
            <p class="text-sm text-slate-500 mt-1">Based on 50% of each subject maximum</p>
        </div>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Subject Breakdown</h3>
                <p class="text-sm text-slate-500 mt-0.5">First, Second, Third, and Final month marks per subject.</p>
            </div>
        </div>

        <?php if ($rows === []): ?>
            <div class="p-10 text-center text-slate-500 text-sm">No subjects or marks are available yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50/80">
                            <th class="px-5 py-3.5 min-w-[14rem]">Subject</th>
                            <th class="px-3 py-3.5 text-center">First</th>
                            <th class="px-3 py-3.5 text-center">Second</th>
                            <th class="px-3 py-3.5 text-center">Third</th>
                            <th class="px-3 py-3.5 text-center">Final</th>
                            <th class="px-3 py-3.5 text-center">Total</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $status = (string)($row['status'] ?? '');
                            $overMax = !empty($row['over_max']);
                            $maxScoreLabel = rtrim(rtrim(number_format((float)($row['max_score'] ?? 100), 1, '.', ''), '0'), '.');
                            ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="size-10 rounded-xl bg-[#1e3a8a]/10 text-[#1e3a8a] inline-flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-lg">menu_book</span>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 truncate"><?= Html::encode((string)$row['subject_label']) ?></p>
                                            <?php if (trim((string)($row['subject_code'] ?? '')) !== ''): ?>
                                                <p class="text-xs text-slate-400 mt-0.5"><?= Html::encode((string)$row['subject_code']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <?php foreach (['first', 'second', 'third', 'final'] as $field): ?>
                                    <td class="px-3 py-3 text-center font-semibold text-slate-800">
                                        <?= ($row[$field] ?? null) !== null ? Html::encode(number_format((float)$row[$field], 1, '.', '')) : '—' ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="px-3 py-3 text-center">
                                    <span class="font-black <?= $overMax ? 'text-red-600' : 'text-slate-900' ?>">
                                        <?= ($row['total'] ?? null) !== null ? Html::encode(number_format((float)$row['total'], 1, '.', '')) : '—' ?>
                                    </span>
                                    <span class="block text-[10px] text-slate-400 font-semibold">/ <?= Html::encode($maxScoreLabel) ?></span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide
                                        <?= $overMax ? 'bg-red-50 text-red-700' : ($status === SchoolStudentSectionMark::STATUS_PASS ? 'bg-emerald-50 text-emerald-700' : ($status === SchoolStudentSectionMark::STATUS_FAILED ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700')) ?>">
                                        <?= $overMax ? 'Over max' : (SchoolStudentSectionMark::statusOptions()[$status] ?? 'Incomplete') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-slate-100 text-xs text-slate-500">
                Showing <?= count($rows) ?> subject<?= count($rows) === 1 ? '' : 's' ?> for this section.
            </div>
        <?php endif; ?>
    </section>
<?= $this->render('../layouts/_page-end') ?>
