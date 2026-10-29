<?php

/**
 * @var yii\web\View $this
 * @var common\models\User $user
 * @var common\modules\employee\models\SchoolStudent|null $student
 * @var string $userName
 * @var string $userRole
 * @var string $currentPage
 */

use common\components\LmsPortal;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Student Portal | LMS';

$gradeLabel = trim((string)($student?->grade?->name ?? ''));
$sectionLabel = trim((string)($student?->section?->name ?? ''));
$classLabel = $gradeLabel !== '' && $sectionLabel !== ''
    ? $gradeLabel . ' · Section ' . $sectionLabel
    : ($gradeLabel !== '' ? $gradeLabel : '—');

?>
<?= $this->render('../layouts/_page-start') ?>
    <header>
        <h2 class="text-3xl font-black text-slate-900 tracking-tight">Welcome, <?= Html::encode($userName) ?></h2>
        <p class="text-slate-500 mt-2 text-lg">Your student learning portal.</p>
    </header>

    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Overview</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between min-h-[140px]">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">Class</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1"><?= Html::encode($classLabel) ?></p>
                    </div>
                    <div class="w-12 h-12 shrink-0 rounded-xl flex items-center justify-center bg-[#642754]/10 text-[#642754]">
                        <span class="material-symbols-outlined">class</span>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between min-h-[140px]">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">Registration</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1"><?= Html::encode($student?->getRegistrationLabel() ?? '—') ?></p>
                    </div>
                    <div class="w-12 h-12 shrink-0 rounded-xl flex items-center justify-center bg-[#eb652c]/10 text-[#eb652c]">
                        <span class="material-symbols-outlined">badge</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Modules</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php if (LmsPortal::canAccessOwnStudentProfile($user)): ?>
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-shadow flex flex-col items-center text-center h-full min-h-[360px]">
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shrink-0 bg-blue-50 text-blue-700">
                        <span class="material-symbols-outlined text-4xl">person</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">My Profile</h3>
                    <p class="text-slate-500 mb-8 max-w-sm leading-relaxed flex-1">View your personal, academic, and contact information.</p>
                    <a href="<?= Html::encode(Url::to(['/lms/student-affairs/profile'])) ?>"
                       class="w-full bg-[#642754] hover:bg-[#4a2a51] text-white font-bold py-3.5 px-6 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 mt-auto no-underline">
                        View My Profile
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (LmsPortal::canAccessOwnStudentHealthRecord($user)): ?>
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-shadow flex flex-col items-center text-center h-full min-h-[360px]">
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shrink-0 bg-rose-50 text-rose-700">
                        <span class="material-symbols-outlined text-4xl">medical_information</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">My Health Record</h3>
                    <p class="text-slate-500 mb-8 max-w-sm leading-relaxed flex-1">Review allergies, chronic conditions, clinic notes, and visit history.</p>
                    <a href="<?= Html::encode(Url::to(['/lms/student-affairs/health-record'])) ?>"
                       class="w-full bg-[#642754] hover:bg-[#4a2a51] text-white font-bold py-3.5 px-6 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 mt-auto no-underline">
                        View Health Record
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (LmsPortal::canAccessLmsSubjects($user)): ?>
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-shadow flex flex-col items-center text-center h-full min-h-[360px]">
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shrink-0 bg-violet-50 text-violet-700">
                        <span class="material-symbols-outlined text-4xl">menu_book</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Subjects</h3>
                    <p class="text-slate-500 mb-8 max-w-sm leading-relaxed flex-1">View subjects in your section and the teachers assigned to each one.</p>
                    <a href="<?= Html::encode(Url::to(['/lms/subjects/index'])) ?>"
                       class="w-full bg-[#642754] hover:bg-[#4a2a51] text-white font-bold py-3.5 px-6 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 mt-auto no-underline">
                        View Subjects
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (LmsPortal::canAccessOwnStudentAttendanceLog($user)): ?>
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-shadow flex flex-col items-center text-center h-full min-h-[360px]">
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shrink-0 bg-indigo-50 text-indigo-700">
                        <span class="material-symbols-outlined text-4xl">fact_check</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">My Attendance Log</h3>
                    <p class="text-slate-500 mb-8 max-w-sm leading-relaxed flex-1">View your attendance history and percentage.</p>
                    <a href="<?= Html::encode(Url::to(['/lms/student-affairs/attendance-log'])) ?>"
                       class="w-full bg-[#642754] hover:bg-[#4a2a51] text-white font-bold py-3.5 px-6 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 mt-auto no-underline">
                        View Attendance Log
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (LmsPortal::canAccessOwnStudentMarks($user)): ?>
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-shadow flex flex-col items-center text-center h-full min-h-[360px]">
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shrink-0 bg-amber-50 text-amber-700">
                        <span class="material-symbols-outlined text-4xl">grade</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">My Marks</h3>
                    <p class="text-slate-500 mb-8 max-w-sm leading-relaxed flex-1">Review your marks across all subjects, including monthly assessments and pass status.</p>
                    <a href="<?= Html::encode(Url::to(['/lms/student-affairs/marks-log'])) ?>"
                       class="w-full bg-[#642754] hover:bg-[#4a2a51] text-white font-bold py-3.5 px-6 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 mt-auto no-underline">
                        View My Marks
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (LmsPortal::canAccessOwnStudentCertificate($user)): ?>
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-shadow flex flex-col items-center text-center h-full min-h-[360px]">
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shrink-0 bg-emerald-50 text-emerald-700">
                        <span class="material-symbols-outlined text-4xl">workspace_premium</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">My Certificate</h3>
                    <p class="text-slate-500 mb-8 max-w-sm leading-relaxed flex-1">View your academic report card.</p>
                    <a href="<?= Html::encode(Url::to(['/lms/certificates/my'])) ?>"
                       class="w-full bg-[#642754] hover:bg-[#4a2a51] text-white font-bold py-3.5 px-6 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 mt-auto no-underline">
                        Open My Certificate
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (LmsPortal::canAccessOwnStudentSchedule($user)): ?>
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-shadow flex flex-col items-center text-center h-full min-h-[360px]">
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shrink-0 bg-sky-50 text-sky-700">
                        <span class="material-symbols-outlined text-4xl">calendar_month</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">My Schedule</h3>
                    <p class="text-slate-500 mb-8 max-w-sm leading-relaxed flex-1">View your section timetable with subjects and assigned teachers. No filters needed — your class schedule loads automatically.</p>
                    <a href="<?= Html::encode(Url::to(['/lms/timetable/index'])) ?>"
                       class="w-full bg-[#642754] hover:bg-[#4a2a51] text-white font-bold py-3.5 px-6 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 mt-auto no-underline">
                        View My Schedule
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            <?php endif; ?>

            <div class="bg-gradient-to-br from-[#642754] to-[#4a2a51] rounded-2xl p-8 text-white shadow-md flex flex-col justify-center min-h-[360px]">
                <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-3xl">mail</span>
                </div>
                <p class="text-xs font-bold uppercase tracking-wide text-white/70">Portal Email</p>
                <p class="text-lg font-bold mt-3 break-all"><?= Html::encode($user->email) ?></p>
                <p class="text-sm text-white/80 mt-4 leading-relaxed">Use this email whenever you sign in to the LMS portal.</p>
            </div>
        </div>
    </section>
<?= $this->render('../layouts/_page-end') ?>
