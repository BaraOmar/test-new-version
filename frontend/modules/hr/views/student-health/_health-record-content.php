<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudentHealthFile $model
 * @var common\modules\employee\models\SchoolStudent $student
 * @var common\modules\employee\models\SchoolStudentClinicVisit[] $clinicVisits
 * @var bool $showStatusCards
 * @var bool $showStaffActions
 * @var bool $showDeleteButton
 * @var string|null $fullHistoryUrl
 * @var string $accentColor
 */

use common\modules\employee\models\SchoolStudentClinicVisit;
use common\modules\employee\models\SchoolStudentHealthFile;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$showStatusCards = $showStatusCards ?? true;
$showStaffActions = $showStaffActions ?? true;
$showDeleteButton = $showDeleteButton ?? true;
$fullHistoryUrl = $fullHistoryUrl ?? null;
$accentColor = $accentColor ?? '#1e3a8a';
$clinicVisits = $clinicVisits ?? [];

$display = static fn(?string $value): string => trim((string)$value) !== '' ? trim((string)$value) : '—';
$hasExtended = SchoolStudentHealthFile::hasColumn('primary_risk');

$lines = static function (?string $value): array {
    $raw = trim((string)$value);
    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: [])));
};

$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= strtoupper(substr($part, 0, 1));
    }

    return $letters !== '' ? $letters : '?';
};

$allergySeverity = match ($model->allergy_status) {
    SchoolStudentHealthFile::ALLERGY_SEVERE => 'Severe',
    SchoolStudentHealthFile::ALLERGY_MILD => 'Mild',
    SchoolStudentHealthFile::ALLERGY_SPECIFIC => 'Specific',
    default => 'None',
};

$chronicName = $model->getChronicConditionLabel();
$hasChronic = $chronicName !== '—';
$lastVisit = $clinicVisits[0] ?? null;
$lastVisitDateLabel = $model->last_visit_date
    ? Yii::$app->formatter->asDate($model->last_visit_date, 'php:M j, Y')
    : '—';
$lastVisitReason = $lastVisit ? trim((string)$lastVisit->primary_reason) : '—';

$visitTypeBadge = static function (string $type): string {
    return match ($type) {
        SchoolStudentClinicVisit::TYPE_ROUTINE => 'bg-slate-100 text-slate-600',
        SchoolStudentClinicVisit::TYPE_ILLNESS => 'bg-sky-100 text-sky-700',
        SchoolStudentClinicVisit::TYPE_EMERGENCY => 'bg-red-100 text-red-700',
        SchoolStudentClinicVisit::TYPE_INJURY => 'bg-amber-100 text-amber-800',
        SchoolStudentClinicVisit::TYPE_FOLLOW_UP => 'bg-purple-100 text-purple-700',
        default => 'bg-slate-100 text-slate-600',
    };
};

$visitTypeShortLabel = static function (string $type): string {
    return match ($type) {
        SchoolStudentClinicVisit::TYPE_ROUTINE => 'Wellness',
        SchoolStudentClinicVisit::TYPE_ILLNESS => 'Sick Visit',
        SchoolStudentClinicVisit::TYPE_EMERGENCY => 'Urgent',
        SchoolStudentClinicVisit::TYPE_INJURY => 'Injury',
        SchoolStudentClinicVisit::TYPE_FOLLOW_UP => 'Follow-up',
        default => 'Visit',
    };
};

$sectionCard = 'bg-white rounded-xl border border-slate-200 shadow-sm p-5 h-full flex flex-col';
$sectionTitle = 'flex items-center gap-2 text-sm font-bold text-slate-900';
$avatarClass = 'w-14 h-14 rounded-full flex items-center justify-center text-lg font-black shrink-0';
$timelineDotClass = 'w-2 h-2 rounded-full shrink-0';

?>
<div class="space-y-6">
    <?php if ($showStaffActions): ?>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <?php if (SchoolStudentClinicVisit::isEnabled()): ?>
                <a href="<?= Html::encode(Url::to(['clinic-visits', 'student_id' => $student->id])) ?>" class="h-10 px-4 inline-flex items-center gap-1.5 rounded-lg border text-sm font-semibold no-underline" style="border-color: <?= Html::encode($accentColor) ?>; color: <?= Html::encode($accentColor) ?>;">
                    <span class="material-symbols-outlined text-base">add</span>
                    Record Visit
                </a>
            <?php endif; ?>
            <a href="<?= Html::encode(Url::to(['update', 'id' => $model->id])) ?>" class="h-10 px-4 inline-flex items-center gap-1.5 rounded-lg text-white text-sm font-semibold no-underline" style="background-color: <?= Html::encode($accentColor) ?>;">
                <span class="material-symbols-outlined text-base">edit</span>
                Edit File
            </a>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4 min-w-0">
            <div class="<?= Html::encode($avatarClass) ?>" style="background-color: <?= Html::encode($accentColor) ?>1a; color: <?= Html::encode($accentColor) ?>;">
                <?= Html::encode($initials($student->getDisplayFullName())) ?>
            </div>
            <div class="min-w-0">
                <p class="text-lg font-black text-slate-900 truncate"><?= Html::encode($student->getDisplayFullName()) ?></p>
                <p class="text-sm text-slate-500 mt-0.5">
                    ID: <?= Html::encode($student->student_code ?: ('STU-' . $student->id)) ?>
                    · Class: <?= Html::encode($student->getClassYearLabel()) ?>
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if ($model->allergy_status !== SchoolStudentHealthFile::ALLERGY_NONE): ?>
                <span class="inline-flex px-3 py-1.5 rounded-full text-[10px] font-bold tracking-wide uppercase bg-amber-100 text-amber-800">
                    Allergy: <?= Html::encode($allergySeverity) ?>
                </span>
            <?php endif; ?>
            <?php if ($hasChronic): ?>
                <span class="inline-flex px-3 py-1.5 rounded-full text-[10px] font-bold tracking-wide uppercase bg-sky-100 text-sky-700">
                    Chronic: <?= Html::encode(strtoupper($chronicName)) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($showStatusCards): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5 border-l-4 border-l-red-500">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Allergy Status</p>
                <p class="text-xl font-black <?= $model->allergy_status !== SchoolStudentHealthFile::ALLERGY_NONE ? 'text-red-600' : 'text-slate-900' ?> mt-2">
                    <?= Html::encode($allergySeverity) ?>
                </p>
                <p class="text-sm text-slate-500 mt-1"><?= Html::encode($display($model->allergy_detail)) ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5 border-l-4 border-l-sky-500">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Chronic Disease</p>
                <p class="text-xl font-black text-sky-700 mt-2">
                    <?= Html::encode($hasExtended && $hasChronic ? $model->getChronicStatusLabel() : ($hasChronic ? 'Active' : 'None')) ?>
                </p>
                <p class="text-sm text-slate-500 mt-1">
                    <?= Html::encode($hasExtended && trim((string)($model->chronic_management ?? '')) !== '' ? $model->chronic_management : $chronicName) ?>
                </p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5 border-l-4 border-l-slate-300">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Last Visit</p>
                <p class="text-xl font-black text-slate-900 mt-2"><?= Html::encode($lastVisitDateLabel) ?></p>
                <p class="text-sm text-slate-500 mt-1 truncate"><?= Html::encode($lastVisitReason) ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5 border-l-4 border-l-red-500">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Primary Risk</p>
                <p class="text-xl font-black text-red-600 mt-2"><?= Html::encode($model->getPrimaryRiskLabel()) ?></p>
                <p class="text-sm text-slate-500 mt-1">
                    <?= Html::encode($hasExtended ? $display($model->risk_requirements) : $display($model->getHealthRiskLabel())) ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <section class="<?= $sectionCard ?>">
            <div class="<?= $sectionTitle ?> mb-4">
                <span class="material-symbols-outlined text-slate-400 text-xl">history</span>
                Clinical Background
            </div>
            <div class="space-y-4 flex-1">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Prior Illnesses</p>
                    <?php $illnesses = $hasExtended ? $lines($model->prior_illnesses) : []; ?>
                    <?php if ($illnesses !== []): ?>
                        <ul class="space-y-1.5 text-sm text-slate-700 list-disc pl-4">
                            <?php foreach ($illnesses as $item): ?>
                                <li><?= Html::encode($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-sm text-slate-500"><?= Html::encode($hasExtended ? '—' : $display($model->medical_history)) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Surgical History</p>
                    <p class="text-sm text-slate-700"><?= Html::encode($hasExtended ? $display($model->surgical_history) : '—') ?></p>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Significant Injuries</p>
                    <?php $injuries = $hasExtended ? $lines($model->significant_injuries) : []; ?>
                    <?php if ($injuries !== []): ?>
                        <ul class="space-y-1.5 text-sm text-slate-700 list-disc pl-4">
                            <?php foreach ($injuries as $item): ?>
                                <li><?= Html::encode($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-sm text-slate-500">—</p>
                    <?php endif; ?>
                </div>
                <?php if (trim((string)($model->notes ?? '')) !== ''): ?>
                    <div class="rounded-lg bg-slate-50 border border-slate-100 p-3 mt-auto">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Clinic Notes</p>
                        <p class="text-sm text-slate-700 whitespace-pre-line"><?= Html::encode($model->notes) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="<?= $sectionCard ?> <?= $model->allergy_status !== SchoolStudentHealthFile::ALLERGY_NONE ? 'border-amber-200' : '' ?>">
            <div class="flex items-center justify-between gap-2 mb-4">
                <div class="<?= $sectionTitle ?>">
                    <span class="material-symbols-outlined text-red-500 text-xl">warning</span>
                    Allergies
                </div>
                <?php if ($hasExtended && $model->allergy_criticality !== SchoolStudentHealthFile::CRITICALITY_NONE): ?>
                    <span class="inline-flex px-2 py-1 rounded text-[10px] font-bold tracking-wide uppercase <?= $model->allergy_criticality === SchoolStudentHealthFile::CRITICALITY_CRITICAL ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' ?>">
                        <?= Html::encode(strtoupper($model->getAllergyCriticalityLabel())) ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if ($model->allergy_status === SchoolStudentHealthFile::ALLERGY_NONE): ?>
                <p class="text-sm text-slate-500">No known allergies recorded.</p>
            <?php else: ?>
                <div class="space-y-4 flex-1">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Allergen</p>
                        <p class="text-base font-bold text-slate-900"><?= Html::encode($model->getAllergyStatusLabel()) ?></p>
                    </div>
                    <?php if ($hasExtended && trim((string)($model->allergy_reaction ?? '')) !== ''): ?>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Reaction</p>
                            <p class="text-sm text-slate-700 whitespace-pre-line"><?= Html::encode($model->allergy_reaction) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($hasExtended && trim((string)($model->emergency_precautions ?? '')) !== ''): ?>
                        <div class="rounded-lg bg-red-50 border border-red-100 p-3 mt-auto">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-red-600 mb-1">Emergency Precautions</p>
                            <p class="text-sm text-red-800 whitespace-pre-line"><?= Html::encode($model->emergency_precautions) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="<?= $sectionCard ?> <?= $hasChronic ? 'border-sky-200' : '' ?>">
            <div class="flex items-center justify-between gap-2 mb-4">
                <div class="<?= $sectionTitle ?>">
                    <span class="material-symbols-outlined text-sky-500 text-xl">stethoscope</span>
                    Chronic Conditions
                </div>
                <?php if ($hasExtended && $hasChronic): ?>
                    <span class="inline-flex px-2 py-1 rounded text-[10px] font-bold tracking-wide uppercase bg-sky-100 text-sky-700">
                        <?= Html::encode(strtoupper($model->getChronicStatusLabel())) ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if (!$hasChronic): ?>
                <p class="text-sm text-slate-500">No chronic conditions recorded.</p>
            <?php else: ?>
                <div class="space-y-3 flex-1">
                    <p class="text-base font-bold text-slate-900"><?= Html::encode($chronicName) ?></p>
                    <?php if ($hasExtended && trim((string)($model->chronic_management ?? '')) !== ''): ?>
                        <p class="text-sm text-slate-700 whitespace-pre-line"><?= Html::encode($model->chronic_management) ?></p>
                    <?php elseif (trim((string)($model->chronic_conditions ?? '')) !== ''): ?>
                        <p class="text-sm text-slate-700 whitespace-pre-line"><?= Html::encode($model->chronic_conditions) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="<?= $sectionCard ?>">
            <div class="flex items-center justify-between gap-2 mb-4">
                <div class="<?= $sectionTitle ?>">
                    <span class="material-symbols-outlined text-slate-400 text-xl">clinical_notes</span>
                    Clinic Visit History
                </div>
                <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Last 3 visits</span>
            </div>
            <?php if ($clinicVisits === []): ?>
                <p class="text-sm text-slate-500 flex-1">No clinic visits recorded yet.</p>
            <?php else: ?>
                <div class="space-y-4 flex-1">
                    <?php foreach ($clinicVisits as $index => $visit): ?>
                        <?php
                        $visitDate = Yii::$app->formatter->asDate($visit->visit_date, 'php:M j, Y');
                        $timeIn = $visit->time_in ? date('g:i A', strtotime($visit->time_in)) : '';
                        $medication = '';
                        if ($visit->medicine_given && trim((string)($visit->medicine_name ?? '')) !== '') {
                            $medication = trim((string)$visit->medicine_name);
                            if (trim((string)($visit->dosage ?? '')) !== '') {
                                $medication .= ' (' . trim((string)$visit->dosage) . ')';
                            }
                        }
                        $isLast = $index === count($clinicVisits) - 1;
                        ?>
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center pt-1">
                                <span class="<?= Html::encode($timelineDotClass) ?>" style="background-color: <?= Html::encode($accentColor) ?>;"></span>
                                <?php if (!$isLast): ?>
                                    <span class="w-px flex-1 bg-slate-200 mt-1 min-h-[2rem]"></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 <?= $isLast ? '' : 'pb-1' ?>">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-semibold text-slate-500">
                                        <?= Html::encode($visitDate) ?><?= $timeIn !== '' ? ' - ' . Html::encode($timeIn) : '' ?>
                                    </span>
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold tracking-wide uppercase <?= $visitTypeBadge($visit->visit_type) ?>">
                                        <?= Html::encode($visitTypeShortLabel($visit->visit_type)) ?>
                                    </span>
                                </div>
                                <p class="text-sm font-bold text-slate-900 mt-1"><?= Html::encode($visit->primary_reason) ?></p>
                                <p class="text-sm text-slate-600 mt-0.5"><?= Html::encode($visit->getActionTakenLabel()) ?><?= trim((string)($visit->clinical_notes ?? '')) !== '' ? '. ' . Html::encode($visit->clinical_notes) : '' ?></p>
                                <?php if ($medication !== ''): ?>
                                    <p class="text-xs text-slate-500 mt-1"><span class="font-semibold text-slate-600">Medication:</span> <?= Html::encode($medication) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($fullHistoryUrl !== null && $fullHistoryUrl !== ''): ?>
                <a href="<?= Html::encode($fullHistoryUrl) ?>" class="inline-flex items-center gap-1 text-sm font-semibold no-underline mt-4 pt-3 border-t border-slate-100" style="color: <?= Html::encode($accentColor) ?>;">
                    View Full History Log
                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>
            <?php endif; ?>
        </section>
    </div>

    <details class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <summary class="px-5 py-4 text-sm font-bold text-slate-700 cursor-pointer list-none flex items-center justify-between">
            Emergency Contact &amp; Demographics
            <span class="material-symbols-outlined text-slate-400 text-lg">expand_more</span>
        </summary>
        <div class="px-5 pb-5 border-t border-slate-100">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm pt-4">
                <div><dt class="text-slate-400 text-xs uppercase font-bold">Parent / Guardian</dt><dd class="text-slate-800 mt-1"><?= Html::encode($display($student->guardian_name)) ?></dd></div>
                <div><dt class="text-slate-400 text-xs uppercase font-bold">Phone</dt><dd class="text-slate-800 mt-1"><?= Html::encode($display($student->guardian_phone ?: $student->emergency_contact)) ?></dd></div>
                <div><dt class="text-slate-400 text-xs uppercase font-bold">Blood Group</dt><dd class="text-slate-800 mt-1"><?= Html::encode($display($student->blood_group)) ?></dd></div>
                <div><dt class="text-slate-400 text-xs uppercase font-bold">Age / Gender</dt><dd class="text-slate-800 mt-1"><?= Html::encode($student->getAgeGenderLabel()) ?><?php if ($student->getBirthDateObject() !== null): ?><span class="block text-xs text-slate-400 mt-0.5">DOB: <?= Html::encode(Yii::$app->formatter->asDate($student->birth_date, 'php:M j, Y')) ?></span><?php endif; ?></dd></div>
            </dl>
        </div>
    </details>

    <?php if ($showDeleteButton): ?>
        <?php $form = ActiveForm::begin(['action' => Url::to(['/hr/student-health/delete', 'id' => $model->id]), 'method' => 'post']); ?>
            <button type="submit"
                    class="h-9 px-3 rounded-lg border border-red-200 text-red-600 text-sm font-semibold bg-white cursor-pointer"
                    onclick="return confirm('Delete this health file?');">
                Delete Health File
            </button>
        <?php ActiveForm::end(); ?>
    <?php endif; ?>
</div>
