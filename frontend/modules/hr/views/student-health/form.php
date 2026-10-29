<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudentHealthFile $model
 * @var common\modules\employee\models\SchoolStudent|null $student
 * @var common\modules\employee\models\SchoolStudent[] $studentsWithoutFile
 * @var bool $isCreate
 * @var string $userName
 * @var string $currentPage
 */

use common\modules\employee\models\SchoolStudentHealthFile;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = ($isCreate ? 'Create' : 'Edit') . ' Health File | CultureHR';

$textareaClass = 'w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm';
$hasExtended = SchoolStudentHealthFile::hasColumn('primary_risk');

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage]) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => $isCreate ? 'Create Health File' : 'Edit Health File', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-5xl mx-auto px-6 lg:px-8 py-8 space-y-6">
                <div>
                    <a href="<?= Html::encode(\yii\helpers\Url::to(['files'])) ?>" class="text-sm font-semibold text-[#1e3a8a] no-underline inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-base">arrow_back</span>
                        Back to Health Files
                    </a>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight mt-3"><?= $isCreate ? 'Create Health File' : 'Edit Health File' ?></h1>
                    <p class="text-slate-500 mt-2">Record clinical background, allergies, chronic conditions, and risk information.</p>
                </div>

                <?php $form = ActiveForm::begin([
                    'options' => ['class' => 'space-y-6'],
                    'fieldConfig' => [
                        'template' => "{label}\n{input}\n{error}",
                        'labelOptions' => ['class' => 'block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2'],
                        'inputOptions' => ['class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm'],
                        'errorOptions' => ['class' => 'text-xs text-red-600 mt-1'],
                    ],
                ]); ?>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
                    <h2 class="text-sm font-bold text-slate-900">Student Identification</h2>
                    <?php if ($isCreate && $student === null): ?>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Student</label>
                            <?= Html::dropDownList('school_student_id', '', ['' => 'Select Student'] + ArrayHelper::map($studentsWithoutFile, 'id', static fn($s) => $s->getDisplayFullName() . ' (' . ($s->student_code ?: 'STU-' . $s->id) . ')'), [
                                'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm',
                                'required' => true,
                            ]) ?>
                        </div>
                    <?php elseif ($student !== null): ?>
                        <div class="rounded-lg bg-slate-50 border border-slate-200 p-4">
                            <p class="text-sm font-bold text-slate-900"><?= Html::encode($student->getDisplayFullName()) ?></p>
                            <p class="text-xs text-slate-500 mt-1">ID: <?= Html::encode($student->student_code ?: ('STU-' . $student->id)) ?> · Class: <?= Html::encode($student->getClassYearLabel()) ?></p>
                        </div>
                        <?= $form->field($model, 'school_student_id')->hiddenInput(['value' => (int)$student->id])->label(false) ?>
                    <?php endif; ?>
                </section>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
                    <h2 class="text-sm font-bold text-slate-900">Summary Status</h2>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="rounded-lg border border-amber-200 bg-amber-50/40 p-4 space-y-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-amber-700">Allergy Status</p>
                            <?= $form->field($model, 'allergy_status')->dropDownList(SchoolStudentHealthFile::allergyStatusOptions())->label('Status') ?>
                            <?= $form->field($model, 'allergy_detail')->textInput(['placeholder' => 'e.g. Peanut & Tree Nuts'])->label('Allergen') ?>
                            <?php if ($hasExtended): ?>
                                <?= $form->field($model, 'allergy_criticality')->dropDownList(SchoolStudentHealthFile::allergyCriticalityOptions())->label('Criticality') ?>
                            <?php endif; ?>
                        </div>
                        <div class="rounded-lg border border-sky-200 bg-sky-50/40 p-4 space-y-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-sky-700">Chronic Disease</p>
                            <?php if ($hasExtended): ?>
                                <?= $form->field($model, 'chronic_condition_name')->textInput(['placeholder' => 'e.g. Asthma'])->label('Condition') ?>
                                <?= $form->field($model, 'chronic_status')->dropDownList(SchoolStudentHealthFile::chronicStatusOptions())->label('Status') ?>
                                <?= $form->field($model, 'chronic_management')->textarea(['rows' => 2, 'class' => $textareaClass, 'placeholder' => 'e.g. Asthma Management'])->label('Management') ?>
                            <?php else: ?>
                                <?= $form->field($model, 'chronic_conditions')->textarea(['rows' => 3, 'class' => $textareaClass, 'placeholder' => 'e.g. Asthma'])->label('Conditions') ?>
                            <?php endif; ?>
                        </div>
                        <div class="rounded-lg border border-red-200 bg-red-50/40 p-4 space-y-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-red-700">Primary Risk</p>
                            <?php if ($hasExtended): ?>
                                <?= $form->field($model, 'primary_risk')->textInput(['placeholder' => 'e.g. Anaphylaxis'])->label('Risk') ?>
                            <?php endif; ?>
                            <?= $form->field($model, 'health_risk')->dropDownList(SchoolStudentHealthFile::healthRiskOptions())->label('Risk Level') ?>
                            <?php if ($hasExtended): ?>
                                <?= $form->field($model, 'risk_requirements')->textarea(['rows' => 2, 'class' => $textareaClass, 'placeholder' => 'e.g. Requires EpiPen access'])->label('Requirements') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
                    <h2 class="text-sm font-bold text-slate-900">Clinical Background</h2>
                    <?php if ($hasExtended): ?>
                        <?= $form->field($model, 'prior_illnesses')->textarea(['rows' => 3, 'class' => $textareaClass, 'placeholder' => "e.g. Chickenpox (Age 6)\nSeasonal Flu (2022)"])->label('Prior Illnesses') ?>
                        <?= $form->field($model, 'surgical_history')->textarea(['rows' => 2, 'class' => $textareaClass, 'placeholder' => 'e.g. Appendectomy (2019) or None reported'])->label('Surgical History') ?>
                        <?= $form->field($model, 'significant_injuries')->textarea(['rows' => 3, 'class' => $textareaClass, 'placeholder' => "e.g. Fractured Left Radius (Jan 2021)"])->label('Significant Injuries') ?>
                    <?php else: ?>
                        <?= $form->field($model, 'medical_history')->textarea(['rows' => 4, 'class' => $textareaClass])->label('Medical History') ?>
                    <?php endif; ?>
                    <?= $form->field($model, 'notes')->textarea(['rows' => 3, 'class' => $textareaClass, 'placeholder' => 'Patient is generally healthy. No recent hospitalizations.'])->label('General Clinic Notes') ?>
                </section>

                <?php if ($hasExtended): ?>
                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
                    <h2 class="text-sm font-bold text-slate-900">Allergies Detail</h2>
                    <?= $form->field($model, 'allergy_reaction')->textarea(['rows' => 3, 'class' => $textareaClass, 'placeholder' => 'e.g. Difficulty breathing, hives, swelling of the throat...'])->label('Reaction Description') ?>
                    <?= $form->field($model, 'emergency_precautions')->textarea(['rows' => 3, 'class' => $textareaClass, 'placeholder' => 'e.g. Avoid all nut products. EpiPen kept in school office...'])->label('Emergency Precautions') ?>
                </section>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
                    <h2 class="text-sm font-bold text-slate-900">Additional Chronic Conditions</h2>
                    <?= $form->field($model, 'chronic_conditions')->textarea(['rows' => 2, 'class' => $textareaClass, 'placeholder' => 'Optional: list any additional conditions separated by commas'])->label(false) ?>
                </section>
                <?php endif; ?>

                <?php if (!$isCreate): ?>
                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
                    <h2 class="text-sm font-bold text-slate-900">Visit Tracking</h2>
                    <?= $form->field($model, 'last_visit_date')->input('date', ['class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm'])->hint('Updated automatically when clinic visits are filed.') ?>
                </section>
                <?php endif; ?>

                <div class="flex items-center gap-3">
                    <button type="submit" class="h-10 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold border-0 cursor-pointer">Save Health File</button>
                    <a href="<?= Html::encode(\yii\helpers\Url::to(['files'])) ?>" class="h-10 px-4 inline-flex items-center rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 no-underline">Cancel</a>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </main>
    </div>
</div>
