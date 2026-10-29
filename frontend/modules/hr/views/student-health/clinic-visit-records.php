<?php



/**

 * @var yii\web\View $this

 * @var yii\data\ActiveDataProvider $dataProvider

 * @var array<string, mixed> $filters

 * @var common\modules\employee\models\SchoolStudent[] $students

 * @var string $userName

 * @var string $currentPage

 */



use common\modules\employee\models\SchoolStudentClinicVisit;

use yii\helpers\ArrayHelper;

use yii\helpers\Html;

use yii\helpers\Url;

use yii\widgets\LinkPager;



$this->title = 'Clinic Visit Records | CultureHR';



$visits = $dataProvider->getModels();

$pagination = $dataProvider->getPagination();

$totalCount = (int)$dataProvider->getTotalCount();



$filterInputClass = 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#1e3a8a]/20 focus:border-[#1e3a8a]';

$filterSearchClass = 'w-full h-11 pl-10 pr-3 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#1e3a8a]/20 focus:border-[#1e3a8a]';

$today = date('Y-m-d');



?>

<div class="flex h-screen overflow-hidden">

    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage]) ?>

    <div class="flex-1 flex flex-col overflow-hidden">

        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Clinic Visits', 'userName' => $userName]) ?>



        <main class="flex-1 overflow-y-auto bg-slate-50">

            <div class="max-w-6xl mx-auto px-6 lg:px-8 py-8 space-y-6">

                <div class="space-y-4">

                    <div>
                        <a href="<?= Html::encode(Url::to(['index'])) ?>" class="text-sm font-semibold text-[#1e3a8a] no-underline inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">arrow_back</span>
                            Back to Student Health
                        </a>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight mt-3">Clinic Visits</h1>

                        <p class="text-slate-500 mt-2">Review filed clinic visits and follow-up outcomes.</p>

                    </div>

                    <?= $this->render('_clinic-tabs', ['activeTab' => 'records']) ?>

                </div>



                <?php if (Yii::$app->session->hasFlash('success')): ?>

                    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm"><?= Html::encode((string)Yii::$app->session->getFlash('success')) ?></div>

                <?php endif; ?>



                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">

                    <?= Html::beginForm(['clinic-visit-records'], 'get', ['class' => 'space-y-4']) ?>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

                            <div class="sm:col-span-2">

                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Search</label>

                                <div class="relative">

                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>

                                    <?= Html::textInput('search', $filters['search'], [

                                        'placeholder' => 'Student name, ID, or reason...',

                                        'class' => $filterSearchClass,

                                    ]) ?>

                                </div>

                            </div>

                            <div>

                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Student</label>

                                <?= Html::dropDownList('student_id', $filters['student_id'] ?: '', ['' => 'All Students'] + ArrayHelper::map($students, 'id', static fn($s) => $s->getDisplayFullName()), [

                                    'class' => $filterInputClass,

                                ]) ?>

                            </div>

                            <div>

                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Status</label>

                                <?= Html::dropDownList('status', $filters['status'], ['' => 'All Statuses'] + SchoolStudentClinicVisit::statusOptions(), [

                                    'class' => $filterInputClass,

                                ]) ?>

                            </div>

                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 items-end">

                            <div>

                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Visit Date From</label>

                                <?= Html::input('date', 'date_from', $filters['date_from'], [

                                    'class' => $filterInputClass,

                                    'max' => $today,

                                ]) ?>

                            </div>

                            <div>

                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Visit Date To</label>

                                <?= Html::input('date', 'date_to', $filters['date_to'], [

                                    'class' => $filterInputClass,

                                    'max' => $today,

                                ]) ?>

                            </div>

                            <div class="sm:col-span-2 flex flex-wrap items-center gap-2">

                                <button type="submit" class="h-10 px-4 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-semibold border-0 cursor-pointer">Apply Filters</button>

                                <a href="<?= Html::encode(Url::to(['clinic-visit-records'])) ?>" class="h-10 px-4 inline-flex items-center rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 no-underline">Reset</a>

                            </div>

                        </div>

                    <?= Html::endForm() ?>

                </section>



                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

                    <div class="overflow-x-auto">

                        <table class="w-full text-sm">

                            <thead class="bg-slate-50 border-b border-slate-200">

                                <tr>

                                    <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">Date / Time</th>

                                    <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">Student</th>

                                    <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">Reason</th>

                                    <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">Type</th>

                                    <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">Medicine</th>

                                    <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">Outcome</th>

                                    <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">Status</th>

                                    <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wide text-slate-400">Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($visits as $visit): ?>

                                    <?php $student = $visit->student; ?>

                                    <tr class="border-b border-slate-100 hover:bg-slate-50/60">

                                        <td class="px-4 py-3 align-top whitespace-nowrap">

                                            <?= Html::encode(Yii::$app->formatter->asDate($visit->visit_date, 'php:M j, Y')) ?><br>

                                            <span class="text-xs text-slate-500"><?= Html::encode(substr((string)$visit->time_in, 0, 5)) ?></span>

                                        </td>

                                        <td class="px-4 py-3 align-top">

                                            <p class="font-semibold text-slate-900"><?= Html::encode($student?->getDisplayFullName() ?? '—') ?></p>

                                            <p class="text-xs text-slate-500"><?= Html::encode($student?->student_code ?: '') ?></p>

                                        </td>

                                        <td class="px-4 py-3 align-top text-slate-700"><?= Html::encode($visit->primary_reason) ?></td>

                                        <td class="px-4 py-3 align-top text-slate-600"><?= Html::encode($visit->getVisitTypeLabel()) ?></td>

                                        <td class="px-4 py-3 align-top text-slate-600">

                                            <?= $visit->medicine_given

                                                ? Html::encode(trim($visit->medicine_name . ($visit->dosage ? ' · ' . $visit->dosage : '')))

                                                : '—' ?>

                                        </td>

                                        <td class="px-4 py-3 align-top text-slate-600"><?= Html::encode($visit->getActionTakenLabel()) ?></td>

                                        <td class="px-4 py-3 align-top">

                                            <span class="inline-flex px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide bg-slate-100 text-slate-600"><?= Html::encode($visit->getStatusLabel()) ?></span>

                                        </td>

                                        <td class="px-4 py-3 align-top text-right whitespace-nowrap">

                                            <a href="<?= Html::encode(Url::to(['clinic-visits', 'student_id' => $visit->school_student_id])) ?>" class="text-xs font-semibold text-[#1e3a8a] no-underline mr-3">New Visit</a>

                                            <?php if ($student && $student->healthFile): ?>

                                                <a href="<?= Html::encode(Url::to(['view', 'id' => $student->healthFile->id])) ?>" class="text-xs font-semibold text-[#1e3a8a] no-underline">Health File</a>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                    <?php if ($totalCount === 0): ?>

                        <div class="py-16 text-center text-slate-400">No clinic visits match your filters.</div>

                    <?php endif; ?>

                </section>



                <div class="flex justify-center">

                    <?= LinkPager::widget([

                        'pagination' => $pagination,

                        'options' => ['class' => 'flex flex-wrap gap-1'],

                        'linkOptions' => ['class' => 'inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 no-underline'],

                    ]) ?>

                </div>

            </div>

        </main>

    </div>

</div>


