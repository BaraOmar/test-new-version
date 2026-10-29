<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudentHealthFile $model
 * @var common\modules\employee\models\SchoolStudent $student
 * @var common\modules\employee\models\SchoolStudentClinicVisit[] $clinicVisits
 */

$this->title = 'My Health Record | LMS';

?>
<?= $this->render('../layouts/_page-start') ?>
    <header>
        <h2 class="text-3xl font-black text-slate-900 tracking-tight">My Health Record</h2>
        <p class="text-slate-500 mt-2 text-lg">Your clinical background, allergies, chronic conditions, and clinic visits.</p>
    </header>

    <?= $this->render('_profile-section-toggle-wrapper', ['model' => $student, 'activeTab' => 'health']) ?>

    <?= $this->render('@frontend/modules/hr/views/student-health/_health-record-content', [
        'model' => $model,
        'student' => $student,
        'clinicVisits' => $clinicVisits,
        'showStatusCards' => false,
        'showStaffActions' => false,
        'showDeleteButton' => false,
        'fullHistoryUrl' => null,
        'accentColor' => '#642754',
    ]) ?>
<?= $this->render('../layouts/_page-end') ?>
