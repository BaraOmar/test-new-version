<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudent $model
 */

$this->title = 'My Profile | LMS';

?>
<?= $this->render('../layouts/_page-start') ?>
    <header>
        <h2 class="text-3xl font-black text-slate-900 tracking-tight">My Profile</h2>
        <p class="text-slate-500 mt-2 text-lg">Your student information and contact details.</p>
    </header>

    <?= $this->render('_profile-section-toggle-wrapper', ['model' => $model, 'activeTab' => 'profile']) ?>
    <?= $this->render('_profile-content', ['model' => $model]) ?>
<?= $this->render('../layouts/_page-end') ?>
