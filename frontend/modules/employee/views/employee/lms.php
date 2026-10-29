<?php

/**
 * @var yii\web\View $this
 * @var string $pageTitle
 * @var common\modules\employee\models\Employee|null $employee
 */

use yii\helpers\Html;

$this->title = $pageTitle ?? 'LMS';
$employee = $employee ?? null;

?>

<?= $this->render('../layouts/_shell-start', [
    'pageTitle' => $this->title,
    'currentPage' => 'lms',
    'userName' => $employee->full_name ?? 'Employee',
    'userRole' => ($employee && $employee->job_title) ? $employee->job_title : 'Employee',
]) ?>
    <div class="max-w-4xl mx-auto w-full px-6 lg:px-8 py-10">
        <div class="bg-white border border-slate-200 rounded-2xl p-10 shadow-sm">
            <p class="text-slate-600 mt-2">This page is wired for the legacy UI migration. Next step is porting the full UI from `LMS_course_review.php`.</p>
        </div>
    </div>
<?= $this->render('../layouts/_shell-end') ?>
