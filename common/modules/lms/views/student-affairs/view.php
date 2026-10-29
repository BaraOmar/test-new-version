<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudent $model
 * @var string|array $backUrl
 * @var string $backLabel
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $model->getDisplayFullName() . ' | Student Profile';

?>
<?= $this->render('../layouts/_page-start') ?>
    <nav aria-label="Breadcrumb" class="text-sm font-medium text-slate-500">
        <ol class="flex items-center flex-wrap gap-x-2 gap-y-1">
            <li><a href="<?= Html::encode(Url::to(['students'])) ?>" class="hover:text-[#642754] no-underline">Student Registry</a></li>
            <li class="flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-slate-300">chevron_right</span>
                <span class="text-slate-900"><?= Html::encode($model->getDisplayFullName()) ?></span>
            </li>
        </ol>
    </nav>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="<?= Html::encode(Url::to($backUrl)) ?>"
           class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold no-underline">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            <?= Html::encode($backLabel) ?>
        </a>
    </div>

    <?= $this->render('_profile-section-toggle-wrapper', ['model' => $model, 'activeTab' => 'profile']) ?>
    <?= $this->render('_profile-content', ['model' => $model]) ?>
<?= $this->render('../layouts/_page-end') ?>
