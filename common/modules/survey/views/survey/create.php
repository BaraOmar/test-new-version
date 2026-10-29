<?php

/**
 * Survey builder — legacy CulturePulse appearance, Google Forms feature set in JS.
 *
 * @var yii\web\View $this
 * @var common\modules\survey\models\Survey $model
 * @var common\modules\employee\models\Department[] $departments
 * @var common\modules\employee\models\Employee[] $employees
 */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;
use yii\widgets\ActiveForm;

$this->title = 'Survey Builder';
echo $this->render('_builder-styles');

$initialBlocks = json_decode($model->questions_json ?: '[]', true) ?: [];
$descPlain = $model->getDescriptionPlain();
$formAction = ['create'];

?>

<?php $form = ActiveForm::begin([
    'id' => 'survey-form',
    'action' => $formAction,
    'options' => ['class' => 'survey-builder-page flex flex-col flex-1 min-h-0 h-full'],
    'fieldConfig' => ['template' => "{input}\n{error}", 'options' => ['tag' => false]],
]); ?>

<header class="sb-builder-header flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6 shrink-0">
    <div class="flex items-center gap-4">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#eb652c] text-white">
            <span class="material-symbols-outlined">poll</span>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Draft</span>
                <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                <span id="lastSavedLabel" class="text-xs text-slate-500">All changes saved in browser</span>
            </div>
            <h1 class="text-lg font-bold leading-tight text-slate-900">Survey Builder</h1>
        </div>
    </div>
    <div class="flex items-center gap-3 shrink-0">
        <?= Html::a('Cancel', ['index'], ['class' => 'text-sm font-medium text-slate-600 hover:text-slate-900 px-3 py-2']) ?>
        <button type="submit" class="flex items-center justify-center rounded-lg bg-[#eb652c] px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-[#d85a24] transition-all">
            Launch Survey
        </button>
    </div>
</header>

<div class="flex flex-1 min-h-0 overflow-hidden relative">
    <div class="gf-canvas-wrap no-scrollbar flex-1 flex justify-center">
        <div id="gf-canvas" class="w-full max-w-3xl">
            <div id="gf-title-card" class="gf-card gf-block-active">
                <div class="gf-title-bar" aria-hidden="true"></div>
                <div class="gf-title-inner">
                    <?= $form->field($model, 'title')->textInput([
                        'id' => 'surveyTitleInput',
                        'class' => 'gf-title-input',
                        'placeholder' => 'Survey Title',
                    ]) ?>
                    <div id="survey-description-editable" class="gf-desc-editable" contenteditable="true" data-placeholder="Form description"><?= Html::encode($descPlain) ?></div>
                    <?= Html::activeHiddenInput($model, 'description', ['id' => 'survey-description-hidden']) ?>
                </div>
            </div>

            <div id="gf-blocks"></div>

            <div class="flex justify-center py-4">
                <button type="button" id="gf-add-question-btn" class="gf-add-question-btn" data-gf-action="add-question">
                    <span class="material-symbols-outlined">add</span>
                    Add Question
                </button>
            </div>
        </div>

        <nav id="gf-float-toolbar" aria-label="Add form elements">
            <button type="button" data-gf-action="add-question" title="Add question"><span class="material-symbols-outlined">add_circle</span></button>
            <button type="button" data-gf-action="import" title="Import questions"><span class="material-symbols-outlined">upload_file</span></button>
            <button type="button" data-gf-action="add-title" title="Add title and description"><span class="material-symbols-outlined">title</span></button>
            <button type="button" data-gf-action="add-image" title="Add image"><span class="material-symbols-outlined">image</span></button>
            <button type="button" data-gf-action="add-video" title="Add video"><span class="material-symbols-outlined">videocam</span></button>
            <button type="button" data-gf-action="add-section" title="Add section"><span class="material-symbols-outlined">view_agenda</span></button>
        </nav>
    </div>

    <aside class="sb-config-aside shrink-0 overflow-y-auto no-scrollbar sidebar-scroll p-6">
        <div class="space-y-10 max-w-full">
            <section>
                <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 mb-4">
                    <span class="material-symbols-outlined text-[#642754]">groups</span>
                    Target Audience <span class="text-red-500">*</span>
                </h3>
                <div class="space-y-3">
                    <?php
                    foreach ([
                        'organization' => ['Org-wide', 'All employees will receive this survey', 'corporate_fare'],
                        'department' => ['Specific Departments', 'Select which departments receive this survey', 'account_tree'],
                        'employee' => ['Individual users', 'Only selected employees will receive this survey', 'person'],
                    ] as $value => [$label, $hint, $icon]):
                        $checked = $model->target_type === $value;
                    ?>
                        <label class="cursor-pointer block">
                            <input type="radio" name="Survey[target_type]" value="<?= $value ?>" class="sr-only target-radio" <?= $checked ? 'checked' : '' ?> onchange="typeof toggleTargetSelection==='function'&&toggleTargetSelection()">
                            <div class="target-card rounded-lg border-2 border-slate-200 p-3 transition-all hover:border-[#642754]/50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="target-indicator h-5 w-5 rounded-full border-2 border-slate-300 flex items-center justify-center">
                                            <div class="target-indicator-inner hidden h-2.5 w-2.5 rounded-full bg-white"></div>
                                        </div>
                                        <div>
                                            <span class="text-sm font-bold block"><?= Html::encode($label) ?></span>
                                            <span class="text-xs text-slate-500"><?= Html::encode($hint) ?></span>
                                        </div>
                                    </div>
                                    <span class="material-symbols-outlined text-slate-400"><?= $icon ?></span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>

                    <div id="department-targets" class="space-y-2 pl-8 <?= $model->target_type === 'department' ? '' : 'hidden' ?>">
                        <p class="text-xs text-slate-500 mb-2">Select departments:</p>
                        <div class="max-h-40 overflow-y-auto space-y-1 border border-slate-200 rounded-lg p-2">
                            <?php foreach ($departments as $department): ?>
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <input type="checkbox" name="Survey[departments][]" value="<?= (int)$department->id ?>" class="dept-checkbox sr-only" <?= in_array($department->id, (array)$model->departments, true) ? 'checked' : '' ?>>
                                    <span class="dept-label flex-1 flex items-center gap-2 p-2 rounded border border-transparent transition-all">
                                        <span class="target-check-icon" aria-hidden="true">
                                            <span class="material-symbols-outlined target-check-off text-slate-400 text-lg">check_box_outline_blank</span>
                                            <span class="material-symbols-outlined target-check-on text-lg">check_box</span>
                                        </span>
                                        <span><?= Html::encode($department->name) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div id="employee-targets" class="space-y-2 pl-8 <?= $model->target_type === 'employee' ? '' : 'hidden' ?>">
                        <p class="text-xs text-slate-500 mb-2">Select employees:</p>
                        <div class="max-h-40 overflow-y-auto space-y-1 border border-slate-200 rounded-lg p-2">
                            <?php foreach ($employees as $employee): ?>
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <input type="checkbox" name="Survey[employees][]" value="<?= (int)$employee->id ?>" class="employee-checkbox sr-only" <?= in_array($employee->id, (array)$model->employees, true) ? 'checked' : '' ?>>
                                    <span class="employee-label flex-1 flex items-center gap-2 p-2 rounded border border-transparent transition-all">
                                        <span class="target-check-icon" aria-hidden="true">
                                            <span class="material-symbols-outlined target-check-off text-slate-400 text-lg">check_box_outline_blank</span>
                                            <span class="material-symbols-outlined target-check-on text-lg">check_box</span>
                                        </span>
                                        <span><?= Html::encode($employee->full_name) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl bg-slate-50 p-4 border border-slate-200 w-full">
                <div class="flex items-center justify-between mb-4 w-full">
                    <h3 class="text-sm font-bold text-slate-900">Anonymity</h3>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="hidden" name="Survey[anonymous]" value="0">
                        <input type="checkbox" name="Survey[anonymous]" value="1" id="anonymousToggle" class="sr-only peer" <?= $model->anonymous ? 'checked' : '' ?> onchange="typeof updateAnonymityUI==='function'&&updateAnonymityUI()">
                        <div class="w-11 h-6 bg-slate-300 rounded-full peer peer-checked:bg-[#642754] transition"></div>
                        <div class="absolute left-[2px] top-[2px] bg-white w-5 h-5 rounded-full transition peer-checked:translate-x-5"></div>
                    </label>
                </div>
                <div id="anonOnState" class="<?= $model->anonymous ? '' : 'hidden' ?>">
                    <div class="flex items-center gap-2 text-xs font-medium text-emerald-600 mb-2">
                        <span class="material-symbols-outlined text-sm">lock</span>
                        Anonymous Responses Enabled
                    </div>
                    <p class="text-[11px] leading-relaxed text-slate-500 italic">
                        Respondent names will be hidden. You will NOT be able to see who responded to this survey.
                    </p>
                </div>
                <div id="anonOffState" class="<?= $model->anonymous ? 'hidden' : '' ?>">
                    <div class="flex items-center gap-2 text-xs font-medium text-amber-600 mb-2">
                        <span class="material-symbols-outlined text-sm">visibility</span>
                        Non-Anonymous Responses
                    </div>
                    <p class="text-[11px] leading-relaxed text-slate-500 italic">
                        Respondent names will be visible. You WILL be able to see who responded to this survey.
                    </p>
                </div>
            </section>

            <section>
                <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 mb-4">
                    <span class="material-symbols-outlined text-[#642754]">calendar_today</span>
                    Scheduling
                </h3>
                <?= $this->render('_schedule-fields', ['model' => $model, 'endDateId' => 'endDate']) ?>
            </section>
        </div>
    </aside>
</div>

<div id="gf-import-modal" class="hidden">
    <div class="gf-modal-box">
        <h3 class="text-lg font-bold mb-2">Import questions</h3>
        <p class="text-sm text-slate-600 mb-3">Paste a JSON array of question objects (same format as exported).</p>
        <textarea id="gf-import-text" placeholder='[{"type":"multiple_choice","text":"Question?","options":["A","B"]}]'></textarea>
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" id="gf-import-cancel" class="px-4 py-2 text-sm border border-slate-200 rounded-lg hover:bg-slate-50">Cancel</button>
            <button type="button" id="gf-import-apply" class="px-4 py-2 text-sm bg-[#eb652c] text-white rounded-lg hover:bg-[#d85a24]">Import</button>
        </div>
    </div>
</div>

<?= $form->field($model, 'questions_json')->hiddenInput(['id' => 'questions-json'])->label(false) ?>

<?php ActiveForm::end(); ?>

<?php
$this->registerJsFile('@web/js/survey-builder-gforms.js', ['depends' => [], 'position' => View::POS_END]);
$initJs = 'window.markDraftSaved = function() { var el = document.getElementById("lastSavedLabel"); if (el) el.textContent = "Last saved just now"; };'
    . 'function updateAnonymityUI() {'
    . ' var on = document.getElementById("anonymousToggle")?.checked;'
    . ' document.getElementById("anonOnState")?.classList.toggle("hidden", !on);'
    . ' document.getElementById("anonOffState")?.classList.toggle("hidden", !!on);'
    . ' markDraftSaved();'
    . '}'
    . 'function toggleTargetSelection() {'
    . ' var t = document.querySelector(\'input[name="Survey[target_type]"]:checked\')?.value || "organization";'
    . ' document.getElementById("department-targets")?.classList.toggle("hidden", t !== "department");'
    . ' document.getElementById("employee-targets")?.classList.toggle("hidden", t !== "employee");'
    . ' markDraftSaved();'
    . '}'
    . 'SurveyGFormsBuilder.init({ initial: ' . Json::htmlEncode($initialBlocks) . ' });'
    . 'toggleTargetSelection();'
    . 'updateAnonymityUI();';
$this->registerJs($initJs, View::POS_END);
echo $this->render('_schedule-scripts', ['endDateId' => 'endDate']);
?>
