<?php

/**
 * @var yii\web\View $this
 * @var string $accessType
 * @var common\modules\employee\models\SchoolStudent|null $student
 * @var common\modules\employee\models\SchoolSectionMaterial[] $materials
 * @var common\modules\employee\models\Employee|null $employee
 * @var common\modules\employee\models\EmployeeSchoolSubject[] $assignedSubjects
 * @var common\modules\employee\models\SchoolSectionMaterial[] $sectionMaterials
 * @var common\modules\employee\models\SchoolSubject[] $departmentSubjects
 * @var common\modules\employee\models\SchoolDepartment[] $departments
 */

use common\components\LmsPortal;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Subjects | LMS';

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');
$canCreate = LmsPortal::canCreateLmsSubjects(Yii::$app->user->identity);

?>
<?= $this->render('../layouts/_page-start') ?>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Subjects</h2>
            <?php if ($accessType === LmsPortal::SUBJECTS_ACCESS_STUDENT): ?>
                <p class="text-slate-500 mt-2 text-lg">Subjects in your section and the teachers assigned to each one.</p>
            <?php elseif ($accessType === LmsPortal::SUBJECTS_ACCESS_DEPARTMENT_LEADER): ?>
                <p class="text-slate-500 mt-2 text-lg">Manage department subjects and review your teaching assignments.</p>
            <?php else: ?>
                <p class="text-slate-500 mt-2 text-lg">Your assigned subjects and the sections you teach.</p>
            <?php endif; ?>
        </div>
        <?php if ($canCreate): ?>
            <a href="<?= Html::encode(Url::to(['create'])) ?>"
               class="inline-flex items-center gap-2 h-11 px-5 rounded-xl bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold shadow-md transition-colors no-underline">
                <span class="material-symbols-outlined text-lg">add_circle</span>
                Create Subject
            </a>
        <?php endif; ?>
    </header>

    <?php if ($successMessage !== ''): ?>
        <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-medium"><?= Html::encode($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium"><?= Html::encode($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($accessType === LmsPortal::SUBJECTS_ACCESS_STUDENT): ?>
        <?= $this->render('_student', ['student' => $student, 'materials' => $materials]) ?>
    <?php else: ?>
        <?php if ($accessType === LmsPortal::SUBJECTS_ACCESS_DEPARTMENT_LEADER): ?>
            <?= $this->render('_leader', ['departmentSubjects' => $departmentSubjects, 'departments' => $departments]) ?>
        <?php endif; ?>
        <?= $this->render('_teacher', [
            'employee' => $employee,
            'assignedSubjects' => $assignedSubjects,
            'sectionMaterials' => $sectionMaterials,
            'showAssignedBlock' => $accessType === LmsPortal::SUBJECTS_ACCESS_TEACHER || $assignedSubjects !== [],
        ]) ?>
    <?php endif; ?>
<?= $this->render('../layouts/_page-end') ?>
