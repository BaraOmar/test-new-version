<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudent $model
 */

use common\modules\employee\models\SchoolStudent;
use yii\helpers\Html;

$display = static function (?string $value): string {
    $value = trim((string)$value);

    return $value !== '' ? $value : '—';
};

$birthDateLabel = static function (?string $birthDate): string {
    if ($birthDate === null || trim($birthDate) === '') {
        return '—';
    }

    try {
        $dob = new DateTime($birthDate);
        $age = (new DateTime())->diff($dob)->y;
        $formatted = Yii::$app->formatter->asDate($birthDate, 'php:j M Y');

        return $formatted . ' (' . $age . ' Years)';
    } catch (\Exception) {
        return Yii::$app->formatter->asDate($birthDate, 'php:j M Y');
    }
};

$gradeLabel = trim((string)($model->grade?->name ?? $model->section?->grade?->name ?? ''));
$sectionLabel = trim((string)($model->section?->name ?? ''));
$classMeta = $gradeLabel !== '' && $sectionLabel !== ''
    ? $gradeLabel . '-' . $sectionLabel
    : ($gradeLabel !== '' ? $gradeLabel : ($sectionLabel !== '' ? 'Section ' . $sectionLabel : '—'));

$joinedLabel = $model->created_at
    ? Yii::$app->formatter->asDate($model->created_at, 'php:M Y')
    : '—';

?>
<section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 lg:p-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-black text-slate-900 tracking-tight"><?= Html::encode($model->getDisplayFullName()) ?></h1>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold uppercase tracking-wide">
                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                    <?= Html::encode($model->getStatusLabel()) ?>
                </span>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-500">
                <span class="inline-flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base text-slate-400">school</span>
                    <?= Html::encode($classMeta) ?>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base text-slate-400">badge</span>
                    <?= Html::encode($model->getRegistrationLabel()) ?>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base text-slate-400">calendar_month</span>
                    Joined: <?= Html::encode($joinedLabel) ?>
                </span>
            </div>
        </div>
    </div>
</section>

<div class="space-y-6">
    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-[#642754]">person</span>
            <h2 class="text-lg font-bold text-slate-900">Personal Details</h2>
        </div>
        <dl class="p-6 lg:p-8 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-x-8 gap-y-6">
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Full Name</dt>
                <dd class="mt-1.5 text-sm font-semibold text-slate-900"><?= Html::encode($display($model->getDisplayFullName())) ?></dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Date of Birth</dt>
                <dd class="mt-1.5 text-sm font-semibold text-slate-900"><?= Html::encode($birthDateLabel($model->birth_date)) ?></dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Gender</dt>
                <dd class="mt-1.5 text-sm font-semibold text-slate-900"><?= Html::encode($model->getGenderLabel()) ?></dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Nationality</dt>
                <dd class="mt-1.5 text-sm font-semibold text-slate-900"><?= Html::encode($display($model->nationality)) ?></dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Blood Group</dt>
                <dd class="mt-1.5 text-sm font-bold text-red-600"><?= Html::encode($display($model->blood_group)) ?></dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Religion</dt>
                <dd class="mt-1.5 text-sm font-semibold text-slate-900"><?= Html::encode($model->getReligionLabel()) ?></dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Grade</dt>
                <dd class="mt-1.5 text-sm font-semibold text-slate-900"><?= Html::encode($display($gradeLabel)) ?></dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Section</dt>
                <dd class="mt-1.5 text-sm font-semibold text-slate-900"><?= Html::encode($display($sectionLabel)) ?></dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Department</dt>
                <dd class="mt-1.5 text-sm font-semibold text-slate-900"><?= Html::encode($model->getDepartmentLabel()) ?></dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Academic Year</dt>
                <dd class="mt-1.5 text-sm font-semibold text-slate-900"><?= Html::encode($display($model->academic_year)) ?></dd>
            </div>
        </dl>
    </section>

    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-[#642754]">family_restroom</span>
            <h2 class="text-lg font-bold text-slate-900">Guardian &amp; Parent Information</h2>
        </div>
        <?= $this->render('_guardian-parent-info', [
            'model' => $model,
            'display' => $display,
            'accentTextClass' => 'text-[#642754]',
            'accentBgClass' => 'bg-[#642754]/10',
        ]) ?>
    </section>

    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-[#eb652c]">location_on</span>
            <h2 class="text-lg font-bold text-slate-900">Contact &amp; Residential Details</h2>
        </div>
        <div class="p-6 lg:p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Area of Residence</dt>
                <dd class="mt-1.5 text-sm font-semibold text-slate-900 leading-relaxed"><?= Html::encode($display($model->residenceArea?->name)) ?></dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Address Details</dt>
                <dd class="mt-1.5 text-sm font-semibold text-slate-900 leading-relaxed"><?= Html::encode($display($model->home_address)) ?></dd>
            </div>
        </div>
    </section>
</div>
