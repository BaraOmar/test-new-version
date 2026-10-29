<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudent $model
 * @var string $userName
 * @var string $currentPage
 * @var string $backUrl
 * @var string $backLabel
 */

use common\modules\employee\models\SchoolStudent;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $model->getDisplayFullName() . ' | Student Affairs';

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
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'student_affairs']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Student Profile', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 space-y-6">
                <nav aria-label="Breadcrumb" class="text-sm font-medium text-slate-500">
                    <ol class="flex items-center flex-wrap gap-x-2 gap-y-1">
                        <li><a href="<?= Html::encode(Url::to(['/hr/student-affairs/students'])) ?>" class="hover:text-[#1e3a8a] no-underline">Student Directory</a></li>
                        <li class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-base text-slate-300">chevron_right</span>
                            <span class="text-slate-900"><?= Html::encode($model->getDisplayFullName()) ?></span>
                        </li>
                    </ol>
                </nav>

                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 lg:p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div class="flex items-center gap-5 min-w-0">
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
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <a href="<?= Html::encode(Url::to(['/hr/student-affairs/update', 'id' => (int)$model->id])) ?>"
                               class="inline-flex items-center gap-2 h-11 px-5 rounded-xl bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold no-underline shadow-sm">
                                <span class="material-symbols-outlined text-lg">edit</span>
                                Edit
                            </a>
                            <a href="<?= Html::encode($backUrl) ?>"
                               class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold no-underline">
                                <span class="material-symbols-outlined text-lg">arrow_back</span>
                                <?= Html::encode($backLabel) ?>
                            </a>
                        </div>
                    </div>
                </section>

                <div class="space-y-6">
                    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[#1e3a8a]">person</span>
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
                            <span class="material-symbols-outlined text-[#1e3a8a]">family_restroom</span>
                            <h2 class="text-lg font-bold text-slate-900">Guardian &amp; Parent Information</h2>
                        </div>
                        <?= $this->render('@common/modules/lms/views/student-affairs/_guardian-parent-info', [
                            'model' => $model,
                            'display' => $display,
                            'accentTextClass' => 'text-[#1e3a8a]',
                            'accentBgClass' => 'bg-[#1e3a8a]/10',
                        ]) ?>
                    </section>

                    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-500">location_on</span>
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

                    <?php if (SchoolStudent::hasColumn('user_id')): ?>
                        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[#1e3a8a]">lock</span>
                                <h2 class="text-lg font-bold text-slate-900">Portal Credentials</h2>
                            </div>
                            <div class="p-6 lg:p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Login Email</p>
                                    <p class="text-sm font-semibold text-slate-900 break-all"><?= Html::encode($display($model->user?->email)) ?></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Login Password</p>
                                    <?php
                                    $storedPassword = SchoolStudent::hasColumn('login_password_plain')
                                        ? trim((string)($model->login_password_plain ?? ''))
                                        : '';
                                    ?>
                                    <?php if ($storedPassword !== ''): ?>
                                        <div class="flex items-center gap-2 w-full max-w-md">
                                            <input type="password" id="student-view-password" readonly
                                                   value="<?= Html::encode($storedPassword) ?>"
                                                   class="flex-1 min-w-0 h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm font-mono">
                                            <button type="button" data-password-toggle="student-view-password"
                                                    class="shrink-0 inline-flex items-center justify-center p-1 text-slate-500 hover:text-[#1e3a8a] bg-transparent border-0 cursor-pointer"
                                                    aria-label="Show password">
                                                <span class="material-symbols-outlined text-2xl leading-none">visibility</span>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-sm font-semibold text-slate-900">—</p>
                                        <p class="text-xs text-slate-500 mt-1">Changed by the student or not available. Use Edit to set a new portal password.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<?php
$this->registerJs(<<<'JS'
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.getAttribute('data-password-toggle'));
        if (!input) return;
        const icon = button.querySelector('.material-symbols-outlined');
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        if (icon) icon.textContent = showing ? 'visibility' : 'visibility_off';
        button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
});
JS);
?>
