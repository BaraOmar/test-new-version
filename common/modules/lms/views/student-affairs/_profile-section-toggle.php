<?php

/**
 * @var yii\web\View $this
 * @var string $activeTab profile|health|attendance|marks|section
 * @var array $profileUrl
 * @var array|null $healthUrl
 * @var array|null $attendanceUrl
 * @var array|null $marksUrl
 * @var array $sectionUrl
 */

use yii\helpers\Html;
use yii\helpers\Url;

$healthUrl = $healthUrl ?? null;
$attendanceUrl = $attendanceUrl ?? null;
$marksUrl = $marksUrl ?? null;
$hasHealthTab = $healthUrl !== null;
$hasAttendanceTab = $attendanceUrl !== null;
$hasMarksTab = $marksUrl !== null;
$user = Yii::$app->user->identity;
$isStudentUser = $user instanceof \common\models\User && $user->isStudent();
$attendanceTabLabel = $isStudentUser ? 'My Attendance Log' : 'Attendance Log';
$marksTabLabel = $isStudentUser ? 'My Marks' : 'Marks';

$buttonClass = static function (bool $active): string {
    $class = 'inline-flex items-center justify-center h-11 px-6 rounded-lg border-2 text-sm font-bold transition-all no-underline ';
    return $class . ($active
        ? 'border-[#642754] bg-[#642754] text-white'
        : 'border-slate-200 text-slate-700 hover:border-[#642754]/40 bg-white');
};

?>
<section class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100">
        <h2 class="text-lg font-bold text-slate-900">View as</h2>
        <p class="text-sm text-slate-500 mt-1">Switch between profile, attendance, marks, health record, and section details.</p>
    </div>
    <div class="p-6 sm:p-8">
        <div class="flex flex-wrap gap-3" role="tablist" aria-label="Student profile views">
            <a href="<?= Html::encode(Url::to($profileUrl)) ?>"
               class="<?= Html::encode($buttonClass($activeTab === 'profile')) ?>"
               role="tab"
               aria-selected="<?= $activeTab === 'profile' ? 'true' : 'false' ?>">
                Student Profile
            </a>
            <?php if ($hasAttendanceTab): ?>
                <a href="<?= Html::encode(Url::to($attendanceUrl)) ?>"
                   class="<?= Html::encode($buttonClass($activeTab === 'attendance')) ?>"
                   role="tab"
                   aria-selected="<?= $activeTab === 'attendance' ? 'true' : 'false' ?>">
                    <?= Html::encode($attendanceTabLabel) ?>
                </a>
            <?php endif; ?>
            <?php if ($hasMarksTab): ?>
                <a href="<?= Html::encode(Url::to($marksUrl)) ?>"
                   class="<?= Html::encode($buttonClass($activeTab === 'marks')) ?>"
                   role="tab"
                   aria-selected="<?= $activeTab === 'marks' ? 'true' : 'false' ?>">
                    <?= Html::encode($marksTabLabel) ?>
                </a>
            <?php endif; ?>
            <?php if ($hasHealthTab): ?>
                <a href="<?= Html::encode(Url::to($healthUrl)) ?>"
                   class="<?= Html::encode($buttonClass($activeTab === 'health')) ?>"
                   role="tab"
                   aria-selected="<?= $activeTab === 'health' ? 'true' : 'false' ?>">
                    My Health Record
                </a>
            <?php endif; ?>
            <a href="<?= Html::encode(Url::to($sectionUrl)) ?>"
               class="<?= Html::encode($buttonClass($activeTab === 'section')) ?>"
               role="tab"
               aria-selected="<?= $activeTab === 'section' ? 'true' : 'false' ?>">
                Section Details
            </a>
        </div>
    </div>
</section>
