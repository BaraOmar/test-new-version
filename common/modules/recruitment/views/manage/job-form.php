<?php

/**
 * @var yii\web\View $this
 * @var common\modules\recruitment\models\Job $model
 * @var string[] $jobsRoute
 * @var string[] $indexRoute
 * @var string[] $departmentOptions
 */

use common\modules\recruitment\helpers\RecruitmentUi;
use common\modules\recruitment\models\Job;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = $model->isNewRecord ? 'Create New Job' : 'Edit Job';
$locations = RecruitmentUi::locationOptions();

?>

<div class="max-w-6xl mx-auto">
    <?= $this->render('_breadcrumb', [
        'indexRoute' => $indexRoute,
        'crumbs' => [
            ['label' => $this->title],
        ],
    ]) ?>

    <header class="mb-8">
        <h1 class="text-3xl font-black text-slate-900 tracking-tight"><?= Html::encode($this->title) ?></h1>
        <p class="text-slate-500 mt-1">Define the position details to attract top talent.</p>
    </header>

    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'grid grid-cols-1 lg:grid-cols-12 gap-8 items-start'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'labelOptions' => ['class' => 'block text-sm font-bold text-slate-700 mb-2'],
            'inputOptions' => ['class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-[#642754]/30 focus:border-[#642754]'],
            'errorOptions' => ['class' => 'text-red-600 text-xs mt-1'],
        ],
    ]) ?>

    <div class="lg:col-span-7 space-y-6">
        <div class="flex flex-wrap gap-3 justify-end">
            <button type="submit" name="action" value="cancel" class="h-11 px-5 border-2 border-slate-200 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
            <button type="submit" name="action" value="draft" class="h-11 px-5 border-2 border-[#642754] text-[#642754] rounded-lg text-sm font-semibold hover:bg-[#642754]/5">Save as Draft</button>
            <?php if (!$model->isNewRecord): ?>
                <button type="submit" name="action" value="save" class="h-11 px-5 bg-[#eb652c] hover:bg-[#d85a24] text-white rounded-lg text-sm font-semibold shadow-md">Save Changes</button>
            <?php endif; ?>
            <?php if ($model->isNewRecord || (string)$model->status === Job::STATUS_DRAFT): ?>
                <button type="submit" name="action" value="publish" class="h-11 px-5 bg-[#eb652c] hover:bg-[#d85a24] text-white rounded-lg text-sm font-semibold shadow-md">Publish Job</button>
            <?php endif; ?>
        </div>

        <section class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center gap-3 mb-6">
                <span class="material-symbols-outlined text-[#642754] p-2 bg-[#642754]/10 rounded-lg">info</span>
                <h2 class="text-xl font-bold text-slate-900">Basic Information</h2>
            </div>
            <div class="space-y-4">
                <?= $form->field($model, 'job_title')->textInput(['id' => 'job_title', 'placeholder' => 'e.g. Senior Research Assistant']) ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2" for="department">Department</label>
                        <?= Html::dropDownList('Job[department]', $model->department, array_combine($departmentOptions, $departmentOptions), [
                            'id' => 'department',
                            'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 text-sm',
                        ]) ?>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2" for="location">Location</label>
                        <?= Html::dropDownList('Job[location]', $model->location ?: $locations[0], array_combine($locations, $locations), [
                            'id' => 'location',
                            'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 text-sm',
                        ]) ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center gap-3 mb-6">
                <span class="material-symbols-outlined text-[#642754] p-2 bg-[#642754]/10 rounded-lg">description</span>
                <h2 class="text-xl font-bold text-slate-900">Job Description</h2>
            </div>
            <?= $form->field($model, 'description')->textarea([
                'id' => 'description',
                'rows' => 10,
                'class' => 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[240px] focus:ring-2 focus:ring-[#642754]/30 focus:border-[#642754]',
                'placeholder' => 'Write the job description...',
            ]) ?>
        </section>
    </div>

    <aside class="lg:col-span-5 lg:sticky lg:top-6 self-start">
        <div class="bg-white rounded-xl border border-slate-200 shadow-lg overflow-hidden">
            <div class="bg-[#642754]/5 px-6 py-4 border-b border-slate-200 flex items-center gap-2">
                <span class="material-symbols-outlined text-[#642754]">visibility</span>
                <span class="text-sm font-bold text-[#642754] uppercase tracking-wider">Applicant View Preview</span>
            </div>
            <div class="p-6 max-h-[560px] overflow-y-auto">
                <h3 id="pv_title" class="text-xl font-extrabold text-slate-900 mb-1"><?= Html::encode($model->job_title ?: 'Job Title Preview') ?></h3>
                <p id="pv_dept" class="text-slate-500 font-medium mb-4"><?= Html::encode($model->department ?: 'Department') ?></p>
                <div class="flex flex-wrap gap-2 mb-6">
                    <span class="inline-flex items-center gap-1 text-xs font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-600">
                        <span class="material-symbols-outlined text-sm">location_on</span>
                        <span id="pv_loc"><?= Html::encode($model->location ?: 'Location') ?></span>
                    </span>
                    <span class="inline-flex items-center gap-1 text-xs font-bold px-3 py-1 rounded-full bg-[#642754]/10 text-[#642754]">
                        <span id="pv_status"><?= Html::encode($model->status) ?></span>
                    </span>
                </div>
                <div id="pv_desc" class="text-sm text-slate-600 whitespace-pre-wrap leading-relaxed"><?= Html::encode($model->description ?: 'Write a description to see preview...') ?></div>
            </div>
        </div>
        <div class="mt-4 p-4 rounded-xl border border-[#eb652c]/20 bg-[#eb652c]/5 flex gap-3">
            <span class="material-symbols-outlined text-[#eb652c]">lightbulb</span>
            <p class="text-xs text-slate-600 leading-relaxed"><strong class="text-[#eb652c]">Recruiter tip:</strong> Use clear role titles and specific requirements so candidates understand expectations quickly.</p>
        </div>
    </aside>

    <?php ActiveForm::end() ?>
</div>

<?php
$js = <<<'JS'
(function () {
    const title = document.getElementById('job_title');
    const dept = document.getElementById('department');
    const loc = document.getElementById('location');
    const desc = document.getElementById('description');
    const sync = () => {
        document.getElementById('pv_title').textContent = (title && title.value.trim()) || 'Job Title Preview';
        document.getElementById('pv_dept').textContent = (dept && dept.value) || 'Department';
        document.getElementById('pv_loc').textContent = (loc && loc.value) || 'Location';
        document.getElementById('pv_desc').textContent = (desc && desc.value.trim()) || 'Write a description to see preview...';
    };
    [title, dept, loc, desc].forEach((el) => el && el.addEventListener('input', sync));
    [dept, loc].forEach((el) => el && el.addEventListener('change', sync));
})();
JS;
$this->registerJs($js);
?>
