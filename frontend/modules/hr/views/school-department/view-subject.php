<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolSubject $subject
 * @var string $userName
 * @var string $currentPage
 */

use common\modules\employee\models\SchoolSubjectGrade;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'View Subject | CultureHR';

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');

$formatMaxScore = static function (float $value): string {
    return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
};

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'subjects']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'View Subject', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-4xl mx-auto px-6 lg:px-8 py-8 space-y-6">
                <nav class="text-sm text-slate-500">
                    <span>Academic</span>
                    <span class="mx-2">/</span>
                    <a href="<?= Html::encode(Url::to(['/hr/school-department/subjects'])) ?>" class="text-slate-500 hover:text-slate-700 no-underline">Subjects</a>
                    <span class="mx-2">/</span>
                    <span class="text-slate-900 font-semibold"><?= Html::encode($subject->name) ?></span>
                </nav>

                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight"><?= Html::encode($subject->name) ?></h1>
                        <p class="text-slate-500 mt-2"><?= Html::encode($subject->department?->name ?? '—') ?></p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="<?= Html::encode(Url::to(['/hr/school-department/subjects'])) ?>"
                           class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold no-underline shadow-sm">
                            <span class="material-symbols-outlined text-base">arrow_back</span>
                            Back to List
                        </a>
                        <a href="<?= Html::encode(Url::to(['/hr/school-department/update-subject', 'id' => (int)$subject->id])) ?>"
                           class="inline-flex items-center gap-2 h-11 px-5 rounded-xl bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold no-underline shadow-md">
                            <span class="material-symbols-outlined text-base">edit</span>
                            Update Subject
                        </a>
                    </div>
                </div>

                <?php if ($successMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm"><?= Html::encode($successMessage) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm"><?= Html::encode($errorMessage) ?></div>
                <?php endif; ?>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-lg font-bold text-slate-900">Subject Details</h2>
                    </div>
                    <dl class="p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">Department</dt>
                            <dd class="text-sm font-semibold text-slate-900"><?= Html::encode($subject->department?->name ?? '—') ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">Subject Name</dt>
                            <dd class="text-sm font-semibold text-slate-900"><?= Html::encode($subject->name) ?></dd>
                        </div>
                    </dl>
                </section>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-lg font-bold text-slate-900">Assigned Grades & Maximum Marks</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                                    <th class="px-5 py-3">Grade</th>
                                    <th class="px-5 py-3">Maximum Mark</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if ($subject->subjectGrades === []): ?>
                                    <tr>
                                        <td colspan="2" class="px-5 py-10 text-center text-slate-400">No grades assigned yet.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($subject->subjectGrades as $subjectGrade): ?>
                                    <?php /** @var SchoolSubjectGrade $subjectGrade */ ?>
                                    <tr>
                                        <td class="px-5 py-4">
                                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                                                <?= Html::encode($subjectGrade->grade?->name ?? '—') ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-slate-700"><?= Html::encode($formatMaxScore((float)($subjectGrade->max_score ?? 100))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
