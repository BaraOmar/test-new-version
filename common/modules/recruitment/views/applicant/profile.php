<?php

/**
 * @var yii\web\View $this
 * @var common\modules\recruitment\models\Applicant $model
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Applicant Profile | CultureHR Careers';

?>

<div class="max-w-[900px] mx-auto p-6 lg:p-10">
    <h2 class="text-3xl font-extrabold text-slate-900 mb-2">Applicant Profile</h2>
    <p class="text-slate-500 mb-4">Keep your profile and resume ready for applications.</p>

    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-8 flex flex-wrap gap-4 text-sm">
        <div>
            <span class="text-slate-500 block text-xs font-bold uppercase">Name</span>
            <span class="text-slate-900 font-semibold"><?= Html::encode($model->full_name) ?></span>
        </div>
        <div>
            <span class="text-slate-500 block text-xs font-bold uppercase">Email</span>
            <span class="text-slate-900"><?= Html::encode($model->email) ?></span>
        </div>
        <div>
            <span class="text-slate-500 block text-xs font-bold uppercase">Phone</span>
            <span class="text-slate-900"><?= Html::encode($model->phone ?: '—') ?></span>
        </div>
    </div>

    <?php if ($model->hasErrors()): ?>
        <div class="mb-4 p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm">
            <?= Html::encode(implode(' ', $model->getFirstErrors())) ?>
        </div>
    <?php endif; ?>

    <?= Html::beginForm(['profile'], 'post', ['enctype' => 'multipart/form-data', 'class' => 'bg-white rounded-xl border border-slate-200 p-6 lg:p-8 space-y-5']) ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="text-sm font-bold text-slate-700">Full Name</label>
                <?= Html::activeTextInput($model, 'full_name', ['class' => 'w-full mt-1 h-11 px-3 border border-slate-200 rounded-lg']) ?>
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700">Email</label>
                <?= Html::activeTextInput($model, 'email', ['class' => 'w-full mt-1 h-11 px-3 border border-slate-200 rounded-lg']) ?>
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700">Phone</label>
                <?= Html::activeTextInput($model, 'phone', ['class' => 'w-full mt-1 h-11 px-3 border border-slate-200 rounded-lg']) ?>
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700">Headline</label>
                <?= Html::activeTextInput($model, 'headline', ['class' => 'w-full mt-1 h-11 px-3 border border-slate-200 rounded-lg', 'placeholder' => 'e.g. Software Engineer']) ?>
            </div>
        </div>
        <div>
            <label class="text-sm font-bold text-slate-700">Bio</label>
            <?= Html::activeTextarea($model, 'bio', ['rows' => 5, 'class' => 'w-full mt-1 px-3 py-2 border border-slate-200 rounded-lg']) ?>
        </div>
        <div>
            <label class="text-sm font-bold text-slate-700">Resume (PDF, DOC, DOCX, PNG)</label>
            <?= Html::activeFileInput($model, 'resumeFile', [
                'class' => 'w-full mt-1 text-sm',
                'accept' => '.pdf,.doc,.docx,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/png',
            ]) ?>
            <?php if ($model->resume_path): ?>
                <p class="text-sm text-slate-500 mt-2">
                    Current:
                    <a href="<?= Url::to(['download-resume']) ?>" class="text-[#642754] font-semibold"><?= Html::encode($model->resume_original_name ?: 'Download resume') ?></a>
                </p>
            <?php endif; ?>
        </div>
        <button type="submit" class="h-11 px-6 bg-[#642754] hover:bg-[#58264a] text-white font-bold rounded-lg">Save Profile</button>
    <?= Html::endForm() ?>
</div>
