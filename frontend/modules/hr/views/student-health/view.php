<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudentHealthFile $model
 * @var common\modules\employee\models\SchoolStudent $student
 * @var common\modules\employee\models\SchoolStudentClinicVisit[] $clinicVisits
 * @var string $userName
 * @var string $currentPage
 */

use common\modules\employee\models\SchoolStudentClinicVisit;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $student->getDisplayFullName() . ' | My Health Record';

$fullHistoryUrl = SchoolStudentClinicVisit::isEnabled()
    ? Url::to(['clinic-visit-records', 'student_id' => $student->id])
    : null;

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage]) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'My Health Record', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 py-8 space-y-6">
                <div>
                    <a href="<?= Html::encode(Url::to(['files'])) ?>" class="text-sm font-semibold text-[#1e3a8a] no-underline inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-base">arrow_back</span>
                        Back to Health Files
                    </a>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight mt-3">My Health Record</h1>
                </div>

                <?= $this->render('_health-record-content', [
                    'model' => $model,
                    'student' => $student,
                    'clinicVisits' => $clinicVisits ?? [],
                    'showStatusCards' => true,
                    'showStaffActions' => true,
                    'showDeleteButton' => true,
                    'fullHistoryUrl' => $fullHistoryUrl,
                    'accentColor' => '#1e3a8a',
                ]) ?>
            </div>
        </main>
    </div>
</div>
