<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolSection $section
 * @var common\modules\employee\models\SchoolSectionMaterial $material
 * @var array<string, mixed> $card
 * @var common\modules\employee\models\SchoolStudent[] $students
 * @var array<int, array<string, mixed>> $markRows
 * @var array<int, string> $materialOptions
 * @var int $materialId
 * @var float $subjectMaxScore
 * @var float $passThreshold
 * @var array{max_first:float,max_second:float,max_third:float,max_final:float} $termMaxima
 * @var bool $canEditMarks
 * @var string $academicSessionLabel
 * @var string $subjectLabel
 */

use common\modules\employee\models\SchoolStudentSectionMark;
use common\modules\employee\services\SchoolSectionMarksService;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Grades & Marks | My Classes';

$gradeName = trim((string)($section->grade?->name ?? ''));
$sectionLabel = $gradeName !== '' ? $gradeName . ' · ' . $section->name : (string)$section->name;
$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');
$maxScoreLabel = rtrim(rtrim(number_format($subjectMaxScore, 1, '.', ''), '0'), '.');
$passThresholdLabel = rtrim(rtrim(number_format($passThreshold, 1, '.', ''), '0'), '.');
$termLabels = [
    'first' => 'First',
    'second' => 'Second',
    'third' => 'Third',
    'final' => 'Final',
];
$termMaxKeys = [
    'first' => 'max_first',
    'second' => 'max_second',
    'third' => 'max_third',
    'final' => 'max_final',
];
$hasMaterialPicker = count($materialOptions) > 1;
$markInputMaxLength = SchoolSectionMarksService::MARK_INPUT_MAX_LENGTH;
$canEditMarks = $canEditMarks ?? true;

$this->registerCss(<<<'CSS'
.marks-input { width: 5.25rem; }
.marks-row-hidden { display: none; }
.marks-field-wrap { display: inline-flex; align-items: center; gap: 0.25rem; }
.marks-clear-field {
    width: 1.5rem;
    height: 1.5rem;
    border: 0;
    border-radius: 0.375rem;
    background: transparent;
    color: #94a3b8;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}
.marks-clear-field:hover { background: #f1f5f9; color: #64748b; }
.marks-total-over { color: #dc2626; }
CSS
);

?>
<?= $this->render('../layouts/_page-start') ?>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="<?= Url::to(['view', 'section_id' => (int)$section->id]) ?>" class="inline-flex items-center gap-1 text-sm font-semibold text-[#642754] no-underline mb-3">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                Back to View Actions
            </a>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight"><?= $canEditMarks ? 'Grades & Marks' : 'View Marks' ?></h2>
            <p class="text-slate-500 mt-2 text-lg">
                <?= Html::encode($academicSessionLabel) ?> · <?= Html::encode($subjectLabel) ?>
            </p>
            <p class="text-sm font-semibold text-[#1e3a8a] mt-2"><?= Html::encode($sectionLabel) ?></p>
            <div class="flex flex-wrap items-center gap-2 mt-3">
                <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-[#1e3a8a]/10 text-[#1e3a8a] text-xs font-bold uppercase tracking-wide">
                    Maximum mark: <?= Html::encode($maxScoreLabel) ?>
                </span>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold uppercase tracking-wide">
                    Minimum pass mark: <?= Html::encode($passThresholdLabel) ?>
                </span>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <?php if ($canEditMarks): ?>
                <button type="submit" form="marks-form" class="inline-flex items-center h-11 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold border-0 cursor-pointer">
                    Save Marks
                </button>
                <button type="button" id="clear-all-marks" class="inline-flex items-center h-11 px-5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-bold cursor-pointer">
                    Clear All Fields
                </button>
            <?php else: ?>
                <span class="inline-flex items-center h-11 px-5 rounded-lg bg-slate-100 text-slate-600 text-sm font-bold">
                    View only
                </span>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($successMessage !== ''): ?>
        <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-medium"><?= Html::encode($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium"><?= Html::encode($errorMessage) ?></div>
    <?php endif; ?>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Term maxima</h3>
                <p class="text-xs text-slate-500 mt-1">
                    First + Second + Third + Final must equal <strong><?= Html::encode($maxScoreLabel) ?></strong>.
                </p>
            </div>
        </div>
        <?php if ($canEditMarks): ?>
            <?= Html::beginForm(['save-term-maxima'], 'post', ['class' => 'space-y-4']) ?>
                <?= Html::hiddenInput('section_id', (int)$section->id) ?>
                <?= Html::hiddenInput('material_id', (int)$material->id) ?>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
                    <?php foreach ($termMaxKeys as $field => $maxKey): ?>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2"><?= Html::encode($termLabels[$field]) ?></label>
                            <input type="text"
                                   name="<?= Html::encode($maxKey) ?>"
                                   value="<?= Html::encode(rtrim(rtrim(number_format((float)$termMaxima[$maxKey], 1, '.', ''), '0'), '.')) ?>"
                                   maxlength="<?= (int)$markInputMaxLength ?>"
                                   inputmode="decimal"
                                   class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm term-max-field"
                                   data-term-key="<?= Html::encode($maxKey) ?>">
                        </div>
                    <?php endforeach; ?>
                    <div>
                        <button type="submit" class="w-full h-10 px-4 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-bold cursor-pointer">
                            Save Term Maxima
                        </button>
                    </div>
                </div>
            <?= Html::endForm() ?>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php foreach ($termMaxKeys as $field => $maxKey): ?>
                    <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400"><?= Html::encode($termLabels[$field]) ?></p>
                        <p class="text-lg font-black text-slate-900 mt-1"><?= Html::encode(rtrim(rtrim(number_format((float)$termMaxima[$maxKey], 1, '.', ''), '0'), '.')) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <?php if ($hasMaterialPicker): ?>
                    <?= Html::beginForm(['marks'], 'get', ['class' => 'flex items-center gap-2']) ?>
                        <?= Html::hiddenInput('section_id', (int)$section->id) ?>
                        <?= Html::dropDownList('material_id', $materialId, $materialOptions, [
                            'class' => 'h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm min-w-[12rem]',
                            'onchange' => 'this.form.submit()',
                        ]) ?>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>
            <div class="relative w-full max-w-sm">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">search</span>
                <input type="search"
                       id="marks-search"
                       placeholder="Search students..."
                       autocomplete="off"
                       class="w-full h-10 pl-10 pr-4 rounded-lg border border-slate-200 bg-slate-50 text-sm">
            </div>
        </div>

        <?php if ($students === []): ?>
            <div class="p-10 text-center text-slate-500 text-sm">No active students in this section.</div>
        <?php else: ?>
            <?php if ($canEditMarks): ?>
                <?= Html::beginForm(['save-marks'], 'post', ['id' => 'marks-form']) ?>
                    <?= Html::hiddenInput('section_id', (int)$section->id) ?>
                    <?= Html::hiddenInput('material_id', (int)$material->id) ?>
            <?php endif; ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50/80">
                                <th class="px-5 py-3.5 min-w-[16rem]">Student Name</th>
                                <?php foreach ($termLabels as $field => $label): ?>
                                    <?php $termMaxLabel = rtrim(rtrim(number_format((float)$termMaxima[$termMaxKeys[$field]], 1, '.', ''), '0'), '.'); ?>
                                    <th class="px-3 py-3.5 text-center">
                                        <?= Html::encode($label) ?>
                                        <span class="block text-[10px] font-semibold normal-case text-slate-400">/ <?= Html::encode($termMaxLabel) ?></span>
                                    </th>
                                <?php endforeach; ?>
                                <th class="px-3 py-3.5 text-center">Total <span class="block text-[10px] font-semibold normal-case text-slate-400">/ <?= Html::encode($maxScoreLabel) ?></span></th>
                                <th class="px-5 py-3.5 text-center">Status</th>
                                <?php if ($canEditMarks): ?>
                                    <th class="px-3 py-3.5 text-center">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($markRows as $studentId => $row): ?>
                                <?php
                                /** @var common\modules\employee\models\SchoolStudent $student */
                                $student = $row['student'];
                                $portraitUrl = $student->getPortraitUrl();
                                $status = (string)($row['status'] ?? '');
                                $overMax = !empty($row['over_max']);
                                ?>
                                <tr class="marks-row hover:bg-slate-50/70 transition-colors"
                                    data-student-id="<?= (int)$studentId ?>"
                                    data-search="<?= Html::encode(mb_strtolower($student->full_name . ' ' . $student->student_code)) ?>">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <?php if ($portraitUrl !== null): ?>
                                                <img src="<?= Html::encode($portraitUrl) ?>" alt="" class="size-10 rounded-full object-cover shrink-0">
                                            <?php else: ?>
                                                <span class="size-10 rounded-full bg-[#1e3a8a]/10 text-[#1e3a8a] text-xs font-bold inline-flex items-center justify-center shrink-0">
                                                    <?= Html::encode($student->getInitials()) ?>
                                                </span>
                                            <?php endif; ?>
                                            <div class="min-w-0">
                                                <p class="font-bold text-slate-900 truncate"><?= Html::encode((string)$student->full_name) ?></p>
                                                <p class="text-xs text-slate-400 mt-0.5">#<?= Html::encode((string)$student->student_code) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <?php foreach (['first', 'second', 'third', 'final'] as $field): ?>
                                        <td class="px-3 py-3 text-center">
                                            <?php if ($canEditMarks): ?>
                                                <div class="marks-field-wrap">
                                                    <input type="text"
                                                           name="marks[<?= (int)$studentId ?>][<?= Html::encode($field) ?>]"
                                                           value="<?= $row[$field] !== null ? Html::encode((string)$row[$field]) : '' ?>"
                                                           maxlength="<?= (int)$markInputMaxLength ?>"
                                                           inputmode="decimal"
                                                           autocomplete="off"
                                                           class="marks-input marks-field h-9 px-2 rounded-lg border border-slate-200 text-sm text-center"
                                                           data-field="<?= Html::encode($field) ?>"
                                                           data-term-max="<?= Html::encode(rtrim(rtrim(number_format((float)$termMaxima[$termMaxKeys[$field]], 1, '.', ''), '0'), '.')) ?>">
                                                    <button type="button"
                                                            class="marks-clear-field"
                                                            title="Clear this field"
                                                            aria-label="Clear <?= Html::encode($field) ?> mark">
                                                        <span class="material-symbols-outlined text-base leading-none">close</span>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="inline-flex min-w-[3rem] justify-center font-semibold text-slate-800">
                                                    <?= $row[$field] !== null ? Html::encode(number_format((float)$row[$field], 1, '.', '')) : '—' ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="px-3 py-3 text-center">
                                        <span class="marks-total inline-flex min-w-[3rem] justify-center font-black <?= $overMax ? 'marks-total-over' : 'text-slate-900' ?>">
                                            <?= $row['total'] !== null ? Html::encode(number_format((float)$row['total'], 1, '.', '')) : '—' ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="marks-status inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide
                                            <?= $overMax ? 'bg-red-50 text-red-700' : ($status === SchoolStudentSectionMark::STATUS_PASS ? 'bg-emerald-50 text-emerald-700' : ($status === SchoolStudentSectionMark::STATUS_FAILED ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700')) ?>">
                                            <?= $overMax ? 'Over max' : (SchoolStudentSectionMark::statusOptions()[$status] ?? 'Incomplete') ?>
                                        </span>
                                    </td>
                                    <?php if ($canEditMarks): ?>
                                        <td class="px-3 py-3 text-center">
                                            <button type="button"
                                                    class="marks-clear-row inline-flex items-center gap-1 h-8 px-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-xs font-bold cursor-pointer"
                                                    title="Clear all marks for this student">
                                                <span class="material-symbols-outlined text-sm leading-none">mop</span>
                                                Clear
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php if ($canEditMarks): ?>
                <?= Html::endForm() ?>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <p class="text-xs text-slate-500">
        <?php if ($canEditMarks): ?>
            Set First, Second, Third, and Final marks for each student (up to <?= (int)$markInputMaxLength ?> characters per field).
            Each term cannot exceed its own maximum shown in the table headers.
            Those four maxima must total <?= Html::encode($maxScoreLabel) ?>.
            The student total is shown only when every field is filled and cannot exceed <?= Html::encode($maxScoreLabel) ?>.
            Students pass when the total is <?= Html::encode($passThresholdLabel) ?> or higher (50% of the maximum mark).
            Clearing all fields for a student removes their saved mark record.
        <?php else: ?>
            You are viewing marks in read-only mode. Only the assigned subject instructor can edit marks for this course.
        <?php endif; ?>
    </p>
<?= $this->render('../layouts/_page-end') ?>

<?php
if (!$canEditMarks) {
    return;
}

$passThresholdJs = json_encode($passThreshold);
$subjectMaxScoreJs = json_encode($subjectMaxScore);
$termMaximaJs = json_encode($termMaxima);
$markInputMaxLengthJs = json_encode($markInputMaxLength);
$statusIncompleteJs = json_encode(SchoolStudentSectionMark::STATUS_INCOMPLETE);
$this->registerJs(<<<JS
(function () {
    const passThreshold = {$passThresholdJs};
    const subjectMaxScore = {$subjectMaxScoreJs};
    const termMaxima = {$termMaximaJs};
    const markInputMaxLength = {$markInputMaxLengthJs};
    const statusIncomplete = {$statusIncompleteJs};
    const termFieldMax = {
        first: termMaxima.max_first,
        second: termMaxima.max_second,
        third: termMaxima.max_third,
        final: termMaxima.max_final,
    };
    const requiredFieldCount = 4;

    function parseMark(value) {
        if (value === '' || value === null || value === undefined) {
            return null;
        }
        const normalized = String(value).trim();
        if (normalized === '' || normalized.length > markInputMaxLength || !/^-?\d*(\.\d*)?$/.test(normalized)) {
            return null;
        }
        const number = Number(normalized);
        return Number.isFinite(number) ? number : null;
    }

    function clampMarkInput(field) {
        if (field.value.length > markInputMaxLength) {
            field.value = field.value.slice(0, markInputMaxLength);
        }
    }

    function termMaxForField(field) {
        const key = field.getAttribute('data-field');
        const raw = field.getAttribute('data-term-max');
        const fromAttr = raw !== null && raw !== '' ? Number(raw) : NaN;
        if (Number.isFinite(fromAttr)) {
            return fromAttr;
        }
        return termFieldMax[key] || subjectMaxScore;
    }

    function formatMarkValue(value) {
        const rounded = Math.round(value * 10) / 10;
        return Number.isInteger(rounded) ? String(rounded) : rounded.toFixed(1);
    }

    function enforceTermMax(field) {
        const termMax = termMaxForField(field);
        const value = parseMark(field.value);
        if (value === null) {
            return;
        }
        if (value < 0) {
            field.value = '0';
            return;
        }
        if (value > termMax) {
            field.value = formatMarkValue(termMax);
        }
    }

    function collectMarks(row) {
        const marks = [];
        row.querySelectorAll('.marks-field').forEach((field) => {
            marks.push(parseMark(field.value));
        });
        return marks;
    }

    function calculateTotal(row) {
        const marks = collectMarks(row);
        if (marks.length !== requiredFieldCount || marks.some((mark) => mark === null)) {
            return null;
        }
        const total = marks.reduce((sum, value) => sum + value, 0);
        return Math.round(total * 10) / 10;
    }

    function isOverMax(total) {
        return total !== null && total > subjectMaxScore;
    }

    function rowHasTermOverMax(row) {
        let invalid = false;
        row.querySelectorAll('.marks-field').forEach((field) => {
            const value = parseMark(field.value);
            const termMax = termMaxForField(field);
            if (value !== null && (value < 0 || value > termMax)) {
                invalid = true;
            }
        });
        return invalid;
    }

    function renderStatus(totalEl, statusEl, total, hasInvalidMarks) {
        const overSubjectMax = isOverMax(total);
        const showOverMax = hasInvalidMarks || overSubjectMax;
        totalEl.classList.toggle('marks-total-over', overSubjectMax);
        totalEl.classList.toggle('text-slate-900', !overSubjectMax);

        if (total === null) {
            totalEl.textContent = '—';
            statusEl.textContent = 'Incomplete';
            statusEl.className = 'marks-status inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide bg-amber-50 text-amber-700';
            return;
        }

        totalEl.textContent = total.toFixed(1);

        if (showOverMax) {
            statusEl.textContent = 'Over max';
            statusEl.className = 'marks-status inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide bg-red-50 text-red-700';
            return;
        }

        const passed = total >= passThreshold;
        statusEl.textContent = passed ? 'Pass' : 'Failed';
        statusEl.className = 'marks-status inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide ' + (passed ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700');
    }

    function clearRow(row) {
        row.querySelectorAll('.marks-field').forEach((field) => {
            field.value = '';
            field.classList.remove('border-red-400');
        });
        renderStatus(row.querySelector('.marks-total'), row.querySelector('.marks-status'), null, false);
    }

    function refreshRow(row) {
        const total = calculateTotal(row);
        const overSubjectMax = isOverMax(total);
        const hasTermOverMax = rowHasTermOverMax(row);
        row.querySelectorAll('.marks-field').forEach((field) => {
            const value = parseMark(field.value);
            const termMax = termMaxForField(field);
            if (value !== null && (value < 0 || value > termMax || overSubjectMax)) {
                field.classList.add('border-red-400');
            } else {
                field.classList.remove('border-red-400');
            }
        });
        renderStatus(
            row.querySelector('.marks-total'),
            row.querySelector('.marks-status'),
            total,
            hasTermOverMax || overSubjectMax
        );
    }

    function bindRow(row) {
        row.querySelectorAll('.marks-field').forEach((field) => {
            enforceTermMax(field);
            field.addEventListener('input', () => {
                clampMarkInput(field);
                enforceTermMax(field);
                refreshRow(row);
            });
            field.addEventListener('blur', () => {
                enforceTermMax(field);
                refreshRow(row);
            });
        });
        row.querySelectorAll('.marks-clear-field').forEach((button) => {
            button.addEventListener('click', () => {
                const field = button.closest('.marks-field-wrap')?.querySelector('.marks-field');
                if (!field) return;
                field.value = '';
                refreshRow(row);
            });
        });
        row.querySelector('.marks-clear-row')?.addEventListener('click', () => clearRow(row));
        refreshRow(row);
    }

    document.querySelectorAll('.marks-row').forEach(bindRow);

    document.getElementById('clear-all-marks')?.addEventListener('click', () => {
        document.querySelectorAll('.marks-row').forEach(clearRow);
    });

    document.getElementById('marks-form')?.addEventListener('submit', (event) => {
        let hasInvalid = false;
        document.querySelectorAll('.marks-row').forEach((row) => {
            if (isOverMax(calculateTotal(row))) {
                hasInvalid = true;
            }
            row.querySelectorAll('.marks-field').forEach((field) => {
                const key = field.getAttribute('data-field');
                const value = parseMark(field.value);
                const termMax = termFieldMax[key] || subjectMaxScore;
                if (value !== null && (value < 0 || value > termMax)) {
                    hasInvalid = true;
                }
            });
        });
        if (hasInvalid) {
            event.preventDefault();
            alert('One or more marks exceed their term maximum or the subject total maximum.');
        }
    });

    const searchInput = document.getElementById('marks-search');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const needle = searchInput.value.trim().toLowerCase();
            document.querySelectorAll('.marks-row').forEach((row) => {
                const haystack = row.getAttribute('data-search') || '';
                row.classList.toggle('marks-row-hidden', needle !== '' && !haystack.includes(needle));
            });
        });
    }
})();
JS);
?>
