<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolSection $section
 * @var common\modules\employee\models\SchoolDepartment|null $department
 * @var common\modules\employee\models\Employee[] $teachers
 * @var common\modules\employee\models\Employee[] $instructorTeachers
 * @var common\modules\employee\models\Employee[] $classTeachers
 * @var array<int, array{id:int,name:string,credits:float|null}> $subjects
 * @var array<int, array<int, array{id:int,label:string}>> $instructorsBySubject
 * @var common\modules\employee\models\SchoolSectionMaterial[] $materials
 * @var common\modules\employee\models\SchoolStudent[] $students
 * @var common\modules\employee\models\SchoolStudent[] $eligibleStudents
 * @var yii\data\Pagination $studentPagination
 * @var string $rosterSearch
 * @var string $userName
 * @var string $currentPage
 * @var bool $isNewSection
 */

use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolSectionMaterial;
use common\modules\employee\models\SchoolStudent;
use common\modules\employee\models\SchoolSubjectGrade;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Section Details | CultureHR';

$isNewSection = !empty($isNewSection);
$grade = $section->grade;
$leadTeacher = $section->leadTeacher;
$teacherInitials = $leadTeacher ? strtoupper(substr($leadTeacher->full_name, 0, 1) . substr(strstr($leadTeacher->full_name, ' ') ?: '', 1, 1)) : '?';
$assignedSince = $section->lead_teacher_assigned_at
    ? Yii::$app->formatter->asDate($section->lead_teacher_assigned_at, 'php:M j, Y')
    : null;
$teacherOptions = ArrayHelper::map($teachers, 'id', static function ($teacher) {
    $title = trim((string)($teacher->job_title ?? ''));
    return $title !== '' ? $teacher->full_name . ' (' . $title . ')' : $teacher->full_name;
});
$editInstructorOptions = ArrayHelper::map($instructorTeachers ?? $teachers, 'id', static function ($teacher) {
    $title = trim((string)($teacher->job_title ?? ''));
    return $title !== '' ? $teacher->full_name . ' (' . $title . ')' : $teacher->full_name;
});
$classTeacherOptions = ArrayHelper::map($classTeachers ?? $teachers, 'id', static function ($teacher) {
    $title = trim((string)($teacher->job_title ?? ''));
    return $title !== '' ? $teacher->full_name . ' (' . $title . ')' : $teacher->full_name;
});
$formatCredits = static function (?float $value): ?string {
    if ($value === null) {
        return null;
    }

    return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
};
$subjectOptions = [];
$subjectNameMap = [];
foreach ($subjects as $item) {
    $id = (int)$item['id'];
    $subjectOptions[$id] = (string)$item['name'];
    $subjectNameMap[$id] = (string)$item['name'];
}
$assignedSubjectIds = [];
foreach ($materials as $material) {
    $assignedSubjectId = (int)($material->school_subject_id ?? 0);
    if ($assignedSubjectId > 0) {
        $assignedSubjectIds[$assignedSubjectId] = true;
    }
}
$availableSubjectOptions = array_diff_key($subjectOptions, $assignedSubjectIds);
$canAssignMoreMaterials = $subjectOptions !== [] && $availableSubjectOptions !== [];
$requiresCatalogSubject = SchoolSubjectGrade::isEnabled();
$gradeName = (string)($grade?->name ?? 'this grade');
$departmentName = (string)($department?->name ?? 'this department');
$teacherNameMap = ArrayHelper::map($instructorTeachers ?? $teachers, 'id', static function ($teacher) {
    $title = trim((string)($teacher->job_title ?? ''));
    return $title !== '' ? $teacher->full_name . ' (' . $title . ')' : $teacher->full_name;
});
$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');
$studentViewReturnParams = ['/hr/school-department/section', 'id' => (int)$section->id];
if ($rosterSearch !== '') {
    $studentViewReturnParams['roster'] = $rosterSearch;
}
$page = Yii::$app->request->get('page');
if ($page !== null && $page !== '') {
    $studentViewReturnParams['page'] = $page;
}
$studentViewReturnUrl = Url::to($studentViewReturnParams);
$eligibleStudentOptions = [];
$eligibleStudentMap = [];
foreach ($eligibleStudents ?? [] as $eligibleStudent) {
    $eligibleStudentOptions[(int)$eligibleStudent->id] = $eligibleStudent->getDisplayFullName()
        . ' · ' . $eligibleStudent->getRegistrationLabel();
    $eligibleStudentMap[(int)$eligibleStudent->id] = [
        'student_id' => (int)$eligibleStudent->id,
        'full_name' => $eligibleStudent->getDisplayFullName(),
        'email' => (string)($eligibleStudent->email ?? ''),
        'student_code' => (string)($eligibleStudent->student_code ?? ''),
        'attendance_percent' => (string)$eligibleStudent->attendance_percent,
        'grade_average' => (string)($eligibleStudent->grade_average ?? ''),
    ];
}
$canAssignStudents = $eligibleStudentOptions !== [];

$this->registerCss(<<<'CSS'
.section-modal { display:none; position:fixed; inset:0; z-index:50; background:rgba(15,23,42,.45); align-items:center; justify-content:center; padding:1rem; }
.section-modal.open { display:flex; }
.section-modal-panel { width:100%; max-width:32rem; background:#fff; border-radius:1rem; padding:1.5rem; box-shadow:0 20px 50px rgba(15,23,42,.2); max-height:90vh; overflow-y:auto; }
CSS
);

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'school_department_hierarchy']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Section Details', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 py-8 space-y-6">
                <header class="space-y-4">
                    <nav class="text-sm text-slate-500" aria-label="Breadcrumb">
                        <a href="<?= Url::to(['/hr/school-department/hierarchy']) ?>" class="text-slate-500 hover:text-[#1e3a8a] no-underline">Classes &amp; Sections</a>
                        <span class="mx-2 text-slate-400">&gt;</span>
                        <span><?= Html::encode($grade?->name ?? 'Grade') ?></span>
                        <span class="mx-2 text-slate-400">&gt;</span>
                        <span class="text-slate-900 font-semibold">Section Details</span>
                    </nav>

                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1 pr-2">
                            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">
                                Section Details -
                                <?= Html::encode($isNewSection ? 'New Section' : $section->name) ?>
                                <?php if ($grade?->name): ?>
                                    <span class="text-slate-500 font-semibold">· <?= Html::encode($grade->name) ?></span>
                                <?php endif; ?>
                            </h1>
                            <p class="text-slate-500 mt-2 text-base">
                                <?php if ($isNewSection): ?>
                                    Set up this section, then assign a class teacher, materials, and students.
                                <?php else: ?>
                                    Manage teacher assignments, materials, and the student list for this section.
                                <?php endif; ?>
                            </p>
                            <?php if ($isNewSection && $department): ?>
                                <p class="text-sm text-slate-400 mt-2"><?= Html::encode($department->name) ?> · <?= Html::encode($grade?->name ?? '') ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2 shrink-0">
                            <?php if (!$isNewSection): ?>
                                <a href="<?= Url::to(['section-print', 'id' => (int)$section->id]) ?>" target="_blank"
                                   class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 no-underline hover:bg-slate-50 whitespace-nowrap">
                                    <span class="material-symbols-outlined text-[1.125rem]">print</span>
                                    Print Report
                                </a>
                                <button type="button" id="open-edit-section"
                                        class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold border-0 cursor-pointer shadow-md whitespace-nowrap">
                                    <span class="material-symbols-outlined text-[1.125rem]">edit</span>
                                    Edit Section
                                </button>
                                <button type="submit"
                                        form="section-details-form"
                                        id="save-section-details-btn"
                                        class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-lg border border-[#1e3a8a] bg-white hover:bg-blue-50 text-[#1e3a8a] text-sm font-bold cursor-pointer whitespace-nowrap">
                                    <span class="material-symbols-outlined text-[1.125rem]">save</span>
                                    Save Changes
                                </button>
                            <?php else: ?>
                                <a href="<?= Url::to(['/hr/school-department/hierarchy']) ?>"
                                   class="inline-flex items-center gap-2 h-11 px-5 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 no-underline hover:bg-slate-50">
                                    Back to hierarchy
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </header>

                <?php if (!$isNewSection): ?>
                    <?= Html::beginForm(['save-section-details', 'id' => (int)$section->id], 'post', ['id' => 'section-details-form']) ?>
                        <div id="section-details-hidden-fields"></div>
                    <?= Html::endForm() ?>
                <?php endif; ?>

                <?php if ($isNewSection): ?>
                    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8">
                        <h2 class="text-lg font-bold text-slate-900 mb-4">Section Setup</h2>
                        <?php if ($section->hasErrors()): ?>
                            <div class="p-4 mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm"><?= Html::errorSummary($section) ?></div>
                        <?php endif; ?>
                        <?= Html::beginForm(['create-section', 'grade_id' => (int)$section->school_grade_id], 'post', ['class' => 'space-y-5']) ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Section name <span class="text-red-500">*</span></label>
                                    <?= Html::activeTextInput($section, 'name', [
                                        'required' => true,
                                        'placeholder' => 'e.g. Section A',
                                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200',
                                    ]) ?>
                                    <?= Html::error($section, 'name', ['class' => 'text-red-600 text-xs mt-1']) ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Room number</label>
                                    <?= Html::activeTextInput($section, 'room_number', [
                                        'placeholder' => 'e.g. 125',
                                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200',
                                    ]) ?>
                                    <?= Html::error($section, 'room_number', ['class' => 'text-red-600 text-xs mt-1']) ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Capacity</label>
                                    <?= Html::activeInput('number', $section, 'capacity_max', ['min' => 1, 'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200']) ?>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Class teacher</label>
                                    <?= Html::activeDropDownList($section, 'lead_teacher_id', $classTeacherOptions, [
                                        'prompt' => 'Assign Class Teacher...',
                                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200',
                                    ]) ?>
                                    <?= Html::error($section, 'lead_teacher_id', ['class' => 'text-red-600 text-xs mt-1']) ?>
                                </div>
                            </div>
                            <div class="flex justify-end pt-2">
                                <button type="submit" class="h-11 px-6 rounded-lg bg-[#1e3a8a] text-white text-sm font-bold border-0 cursor-pointer">
                                    Create Section
                                </button>
                            </div>
                        <?= Html::endForm() ?>
                    </section>
                <?php endif; ?>

                <?php if ($successMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm"><?= Html::encode($successMessage) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm"><?= Html::encode($errorMessage) ?></div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 <?= $isNewSection ? 'opacity-60 pointer-events-none select-none' : '' ?>">
                    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        <h2 class="text-lg font-bold text-slate-900 mb-4">Class Teacher</h2>
                        <?php if ($isNewSection): ?>
                            <p class="text-sm text-slate-500">Available after the section is created.</p>
                        <?php elseif ($leadTeacher): ?>
                            <div class="flex items-start gap-4 mb-5">
                                <div class="size-14 rounded-full bg-[#1e3a8a]/10 text-[#1e3a8a] flex items-center justify-center text-lg font-black shrink-0">
                                    <?= Html::encode($teacherInitials) ?>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-slate-900"><?= Html::encode($leadTeacher->full_name) ?></p>
                                    <p class="text-sm text-slate-500"><?= Html::encode(trim((string)($leadTeacher->job_title ?? 'Teacher'))) ?></p>
                                    <?php if ($assignedSince): ?>
                                        <p class="text-xs text-slate-400 mt-1">Assigned since <?= Html::encode($assignedSince) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-sm text-slate-500 mb-5">No class teacher assigned yet.</p>
                        <?php endif; ?>

                        <?php if (!$isNewSection): ?>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Assign Class Teacher</label>
                                <?= Html::dropDownList('lead_teacher_id', $section->lead_teacher_id, $classTeacherOptions, [
                                    'prompt' => 'Assign Class Teacher...',
                                    'form' => 'section-details-form',
                                    'id' => 'section-lead-teacher-id',
                                    'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                                ]) ?>
                                <p class="text-xs text-slate-500 mt-2">Only teachers assigned to <?= Html::encode($departmentName) ?> appear here.</p>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="text-lg font-bold text-slate-900">Material Allocation</h2>
                            <button type="button" id="open-assign-material"
                                    class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] bg-transparent border-0 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                    <?= !$canAssignMoreMaterials ? 'disabled title="All subjects are already assigned to this section"' : '' ?>>
                                <span class="material-symbols-outlined text-base">add</span>
                                Assign Material
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100">
                                        <th class="pb-3 pr-3">Subject / Material</th>
                                        <th class="pb-3 pr-3">Assigned Instructor</th>
                                        <th class="pb-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="materials-table-body" class="divide-y divide-slate-100">
                                    <?php if ($materials === []): ?>
                                        <tr id="materials-empty-row"><td colspan="3" class="py-6 text-center text-slate-400">No materials assigned yet.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($materials as $material): ?>
                                        <tr class="saved-material-row" data-material-id="<?= (int)$material->id ?>">
                                            <td class="py-3 pr-3">
                                                <?php
                                                $linkedSubjectId = (int)($material->school_subject_id ?? 0);
                                                ?>
                                                <div class="flex items-center gap-2 font-semibold text-slate-800">
                                                    <span class="material-symbols-outlined text-[#1e3a8a] text-lg"><?= Html::encode($material->icon) ?></span>
                                                    <div>
                                                        <div><?= Html::encode($material->name) ?></div>
                                                        <?php if ($linkedSubjectId > 0): ?>
                                                            <p class="text-xs font-normal text-slate-500 mt-0.5">
                                                                <?= Html::encode($subjectNameMap[$linkedSubjectId] ?? $material->subject?->name ?? 'Subject') ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 pr-3 text-slate-600"><?= Html::encode($material->instructor?->full_name ?: 'Unassigned') ?></td>
                                            <td class="py-3">
                                                <div class="flex items-center gap-1">
                                                    <button type="button"
                                                            class="edit-material-btn inline-flex size-8 items-center justify-center rounded-md hover:bg-slate-100 border-0 bg-transparent cursor-pointer text-slate-500"
                                                            data-id="<?= (int)$material->id ?>"
                                                            data-subject="<?= (int)($material->school_subject_id ?? 0) ?>"
                                                            data-name="<?= Html::encode($material->name) ?>"
                                                            data-instructor="<?= (int)($material->instructor_id ?? 0) ?>"
                                                            title="Edit material">
                                                        <span class="material-symbols-outlined text-lg">edit</span>
                                                    </button>
                                                    <?= Html::beginForm(['delete-material', 'id' => (int)$section->id, 'materialId' => (int)$material->id], 'post', ['class' => 'inline m-0']) ?>
                                                        <button type="submit" class="inline-flex size-8 items-center justify-center rounded-md hover:bg-red-50 border-0 bg-transparent cursor-pointer text-red-500"
                                                                onclick="return confirm('Remove this material?');" title="Remove material">
                                                            <span class="material-symbols-outlined text-lg">delete</span>
                                                        </button>
                                                    <?= Html::endForm() ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <?php if ($isNewSection): ?>
                    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                        Complete <strong>Section Setup</strong> above, then you can assign materials and students on this page.
                    </p>
                <?php endif; ?>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 <?= $isNewSection ? 'opacity-60 pointer-events-none select-none' : '' ?>">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Student List</h2>
                            <p class="text-sm text-slate-500 mt-1">Currently <?= (int)$section->current_enrollment ?> students enrolled</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <?= Html::beginForm(['section', 'id' => (int)$section->id], 'get', ['class' => 'm-0']) ?>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                                    <?= Html::textInput('roster', $rosterSearch, [
                                        'placeholder' => 'Filter list...',
                                        'class' => 'h-10 w-56 pl-9 pr-3 rounded-lg border border-slate-200 text-sm',
                                    ]) ?>
                                </div>
                            <?= Html::endForm() ?>
                            <button type="button" id="open-assign-student"
                                    class="inline-flex items-center gap-1 h-10 px-4 rounded-lg bg-[#1e3a8a] text-white text-sm font-bold border-0 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                    <?= $canAssignStudents ? '' : 'disabled title="No eligible students for this department and grade"' ?>>
                                <span class="material-symbols-outlined text-lg">person_add</span>
                                Assign Student
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100">
                                    <th class="pb-3 pr-3">Student Name</th>
                                    <th class="pb-3 pr-3">Student ID</th>
                                    <th class="pb-3 pr-3">Attendance %</th>
                                    <th class="pb-3 pr-3">Grade Average</th>
                                    <th class="pb-3">Profile</th>
                                </tr>
                            </thead>
                            <tbody id="students-table-body" class="divide-y divide-slate-100">
                                <?php if ($students === []): ?>
                                    <tr id="students-empty-row"><td colspan="5" class="py-8 text-center text-slate-400">No students in this section yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    $attendance = (float)$student->attendance_percent;
                                    $attendanceClass = $attendance < 75 ? 'bg-red-500' : 'bg-emerald-500';
                                    ?>
                                    <tr data-student-id="<?= (int)$student->id ?>">
                                        <td class="py-3 pr-3">
                                            <div>
                                                <p class="font-semibold text-slate-900"><?= Html::encode($student->getDisplayFullName()) ?></p>
                                                <p class="text-xs text-slate-500"><?= Html::encode($student->email ?: '—') ?></p>
                                            </div>
                                        </td>
                                        <td class="py-3 pr-3 text-slate-600 font-mono text-xs">#<?= Html::encode($student->student_code) ?></td>
                                        <td class="py-3 pr-3 min-w-[140px]">
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                                                    <div class="h-full rounded-full <?= $attendanceClass ?>" style="width:<?= min(100, max(0, $attendance)) ?>%"></div>
                                                </div>
                                                <span class="text-xs font-semibold text-slate-600 w-10"><?= number_format($attendance, 0) ?>%</span>
                                            </div>
                                        </td>
                                        <td class="py-3 pr-3">
                                            <span class="text-xs font-bold px-2.5 py-1 rounded-full <?= Html::encode($student->getGradeBadgeClass()) ?>">
                                                <?= Html::encode($student->grade_average ?: '—') ?>
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <div class="flex items-center gap-1">
                                                <a href="<?= Html::encode(Url::to(['/hr/student-affairs/view', 'id' => (int)$student->id, 'return' => $studentViewReturnUrl, 'from' => 'section', 'section_id' => (int)$section->id])) ?>"
                                                   class="inline-flex size-8 items-center justify-center rounded-md hover:bg-slate-100 text-slate-500 no-underline"
                                                   title="View student profile">
                                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                                </a>
                                                <?= Html::beginForm(['remove-student', 'id' => (int)$section->id, 'studentId' => (int)$student->id], 'post', ['class' => 'inline m-0']) ?>
                                                    <button type="submit" class="inline-flex size-8 items-center justify-center rounded-md hover:bg-red-50 border-0 bg-transparent cursor-pointer text-red-500"
                                                            onclick="return confirm('Remove this student from the section?');" title="Remove student">
                                                        <span class="material-symbols-outlined text-lg">person_remove</span>
                                                    </button>
                                                <?= Html::endForm() ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 mt-4 pt-4 border-t border-slate-100">
                        <p class="text-sm text-slate-500">
                            Showing <?= $studentPagination->totalCount > 0 ? ($studentPagination->offset + 1) : 0 ?>
                            to <?= min($studentPagination->offset + $studentPagination->limit, $studentPagination->totalCount) ?>
                            of <?= (int)$studentPagination->totalCount ?> students
                        </p>
                        <?= LinkPager::widget([
                            'pagination' => $studentPagination,
                            'options' => ['class' => 'flex gap-1 list-none m-0 p-0'],
                            'linkOptions' => ['class' => 'inline-flex size-9 items-center justify-center rounded-lg border border-slate-200 text-sm no-underline text-slate-700 hover:bg-slate-50'],
                            'disabledPageCssClass' => 'opacity-40 pointer-events-none',
                            'activePageCssClass' => 'bg-[#1e3a8a] text-white border-[#1e3a8a]',
                        ]) ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<?php if (!$isNewSection): ?>
<div id="edit-section-modal" class="section-modal">
    <div class="section-modal-panel">
        <h3 class="text-lg font-bold text-slate-900 mb-4">Edit Section</h3>
        <?= Html::beginForm(['update-section', 'id' => (int)$section->id], 'post') ?>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Section name</label>
                    <?= Html::activeTextInput($section, 'name', ['class' => 'w-full h-11 px-3 rounded-lg border border-slate-200']) ?>
                    <?= Html::error($section, 'name', ['class' => 'text-red-600 text-xs mt-1']) ?>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Room number</label>
                    <?= Html::activeTextInput($section, 'room_number', ['class' => 'w-full h-11 px-3 rounded-lg border border-slate-200']) ?>
                    <?= Html::error($section, 'room_number', ['class' => 'text-red-600 text-xs mt-1']) ?>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Capacity</label>
                    <?= Html::activeInput('number', $section, 'capacity_max', ['min' => 1, 'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200']) ?>
                </div>
            </div>
            <div class="flex justify-between items-center gap-2 mt-6">
                <button type="submit"
                        formaction="<?= Html::encode(Url::to(['delete-section', 'id' => (int)$section->id])) ?>"
                        formmethod="post"
                        class="h-10 px-4 rounded-lg text-red-600 border border-red-200 bg-white text-sm font-semibold cursor-pointer"
                        onclick="return confirm('Delete this section?');">Delete Section</button>
                <div class="flex gap-2">
                    <button type="button" class="section-close-modal h-10 px-4 rounded-lg border border-slate-200 bg-white text-sm font-semibold cursor-pointer">Cancel</button>
                    <button type="submit" class="h-10 px-4 rounded-lg bg-[#1e3a8a] text-white text-sm font-bold border-0 cursor-pointer">Save Changes</button>
                </div>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>

<div id="assign-material-modal" class="section-modal">
    <div class="section-modal-panel">
        <h3 class="text-lg font-bold text-slate-900 mb-1">Assign Material</h3>
        <p class="text-sm text-slate-500 mb-4">
            Link a catalog subject for <?= Html::encode($gradeName) ?> in <?= Html::encode($departmentName) ?>.
        </p>
        <?php if ($subjectOptions === []): ?>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 space-y-3">
                <p>No subjects are assigned to <?= Html::encode($gradeName) ?> yet. Create subjects and assign maximum marks first.</p>
                <a href="<?= Html::encode(Url::to(['/hr/school-department/subjects'])) ?>"
                   class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold no-underline">
                    <span class="material-symbols-outlined text-base">menu_book</span>
                    Manage Subjects
                </a>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" class="section-close-modal h-10 px-4 rounded-lg border border-slate-200 bg-white text-sm font-semibold cursor-pointer">Close</button>
            </div>
        <?php elseif ($availableSubjectOptions === []): ?>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                All available subjects for <?= Html::encode($gradeName) ?> are already assigned to this section.
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" class="section-close-modal h-10 px-4 rounded-lg border border-slate-200 bg-white text-sm font-semibold cursor-pointer">Close</button>
            </div>
        <?php else: ?>
        <?= Html::beginForm(['assign-material', 'id' => (int)$section->id], 'post', ['id' => 'assign-material-form']) ?>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Subject <?= $requiresCatalogSubject ? '<span class="text-red-500">*</span>' : '' ?>
                    </label>
                    <?= Html::dropDownList('SchoolSectionMaterial[school_subject_id]', '', $availableSubjectOptions, [
                        'prompt' => 'Select subject',
                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50',
                        'id' => 'assign-material-subject',
                        'required' => $requiresCatalogSubject,
                    ]) ?>
                    <p class="text-xs text-slate-500 mt-2">Only subjects assigned to <?= Html::encode($gradeName) ?> appear here.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Material title <span class="text-slate-400 font-normal">(optional)</span>
                    </label>
                    <?= Html::textInput('SchoolSectionMaterial[name]', '', [
                        'placeholder' => 'Defaults to the subject name',
                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200',
                        'id' => 'assign-material-name',
                    ]) ?>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Assigned instructor</label>
                    <?= Html::dropDownList('SchoolSectionMaterial[instructor_id]', '', [], [
                        'prompt' => 'Select instructor',
                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200',
                        'id' => 'assign-material-instructor',
                        'disabled' => true,
                    ]) ?>
                    <p id="assign-instructor-hint" class="text-xs text-slate-500 mt-2">Select a subject first to see instructors for that course.</p>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" class="section-close-modal h-10 px-4 rounded-lg border border-slate-200 bg-white text-sm font-semibold cursor-pointer">Cancel</button>
                <button type="button" id="stage-assign-material" class="h-10 px-4 rounded-lg bg-[#1e3a8a] text-white text-sm font-bold border-0 cursor-pointer">Add Material</button>
            </div>
        <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
</div>

<div id="edit-material-modal" class="section-modal">
    <div class="section-modal-panel">
        <h3 class="text-lg font-bold text-slate-900 mb-4">Edit Material</h3>
        <?= Html::beginForm(['update-material', 'id' => (int)$section->id, 'materialId' => 0], 'post', ['id' => 'edit-material-form']) ?>
            <div class="space-y-4">
                <?php if ($subjectOptions !== []): ?>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Subject <?= $requiresCatalogSubject ? '<span class="text-red-500">*</span>' : '' ?>
                        </label>
                        <?= Html::dropDownList('SchoolSectionMaterial[school_subject_id]', '', $subjectOptions, [
                            'prompt' => 'Select subject',
                            'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50',
                            'id' => 'edit-material-subject',
                        ]) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Material title <span class="text-slate-400 font-normal">(optional)</span>
                    </label>
                    <?= Html::textInput('SchoolSectionMaterial[name]', '', ['class' => 'w-full h-11 px-3 rounded-lg border border-slate-200', 'id' => 'edit-material-name']) ?>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Assigned instructor</label>
                    <?= Html::dropDownList('SchoolSectionMaterial[instructor_id]', '', [], [
                        'prompt' => 'Select instructor',
                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200',
                        'id' => 'edit-material-instructor',
                        'disabled' => true,
                    ]) ?>
                    <p id="edit-instructor-hint" class="text-xs text-slate-500 mt-2">Select a subject first to see instructors for that course.</p>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" class="section-close-modal h-10 px-4 rounded-lg border border-slate-200 bg-white text-sm font-semibold cursor-pointer">Cancel</button>
                <button type="button" id="stage-edit-material" class="h-10 px-4 rounded-lg bg-[#1e3a8a] text-white text-sm font-bold border-0 cursor-pointer">Apply Changes</button>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>

<div id="assign-student-modal" class="section-modal">
    <div class="section-modal-panel">
        <h3 class="text-lg font-bold text-slate-900 mb-4">Assign Student</h3>
        <?= Html::beginForm(['assign-student', 'id' => (int)$section->id], 'post', ['id' => 'assign-student-form']) ?>
            <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-600 mb-4">
                <span class="font-semibold text-slate-800">Department:</span> <?= Html::encode($departmentName) ?>
                <span class="mx-2 text-slate-300">·</span>
                <span class="font-semibold text-slate-800">Grade:</span> <?= Html::encode($gradeName) ?>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2" for="assign-student-select">Student</label>
                    <?= Html::dropDownList('student_id', '', ['' => 'Select student'] + $eligibleStudentOptions, [
                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm',
                        'id' => 'assign-student-select',
                    ]) ?>
                    <p class="mt-2 text-xs text-slate-500">
                        Only students enrolled in this department and grade without a section assignment are listed.
                    </p>
                    <?php if (!$canAssignStudents): ?>
                        <p class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            No eligible students found. Enroll students in Student Affairs with this department and grade first.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" class="section-close-modal h-10 px-4 rounded-lg border border-slate-200 bg-white text-sm font-semibold cursor-pointer">Cancel</button>
                <button type="button" id="stage-assign-student" class="h-10 px-4 rounded-lg bg-[#1e3a8a] text-white text-sm font-bold border-0 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed" <?= $canAssignStudents ? '' : 'disabled' ?>>Assign Student</button>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>

<?php
$subjectNameMapJson = json_encode($subjectNameMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$allSubjectOptionsJson = json_encode($subjectOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$requiresCatalogSubjectJson = $requiresCatalogSubject ? 'true' : 'false';
$instructorsBySubjectJson = json_encode($instructorsBySubject ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$teacherNameMapJson = json_encode($teacherNameMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$eligibleStudentMapJsonJs = json_encode($eligibleStudentMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$this->registerJs(<<<JS
(function() {
  function openModal(id) {
    document.querySelectorAll('.section-modal.open').forEach(function(el) { el.classList.remove('open'); });
    var modal = document.getElementById(id);
    if (modal) modal.classList.add('open');
  }
  function closeModals() {
    document.querySelectorAll('.section-modal.open').forEach(function(el) { el.classList.remove('open'); });
  }
  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
  function instructorName(id) {
    if (!id) return 'Unassigned';
    return teacherNameMap[String(id)] || teacherNameMap[id] || 'Unassigned';
  }
  function studentGradeClass(grade) {
    var value = String(grade || '').trim().toUpperCase();
    if (!value || value === '—') return 'bg-slate-100 text-slate-600';
    if (value.charAt(0) === 'A') return 'bg-blue-100 text-blue-700';
    if (value.charAt(0) === 'B') return 'bg-slate-200 text-slate-700';
    if (value.charAt(0) === 'C' || value.charAt(0) === 'D' || value.charAt(0) === 'F') return 'bg-red-100 text-red-700';
    return 'bg-slate-100 text-slate-600';
  }
  function studentInitials(name) {
    var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    return ((parts[0] ? parts[0][0] : '') + (parts[1] ? parts[1][0] : '')).toUpperCase() || '?';
  }

  var subjectMap = {$subjectNameMapJson} || {};
  var allSubjectOptions = {$allSubjectOptionsJson} || {};
  var requiresCatalogSubject = {$requiresCatalogSubjectJson};
  var instructorsBySubject = {$instructorsBySubjectJson} || {};
  var teacherNameMap = {$teacherNameMapJson} || {};
  var eligibleStudentMap = {$eligibleStudentMapJsonJs} || {};
  var assignStudentSelect = document.getElementById('assign-student-select');
  var editingDuplicateOpts = {};
  var pendingNewMaterials = [];
  var pendingMaterialUpdates = {};
  var pendingNewStudents = [];
  var nextPendingMaterialIndex = 0;
  var nextPendingStudentIndex = 0;
  var editingMaterialKey = null;
  var hiddenFields = document.getElementById('section-details-hidden-fields');
  var materialsBody = document.getElementById('materials-table-body');
  var studentsBody = document.getElementById('students-table-body');

  function syncHiddenFields() {
    if (!hiddenFields) return;
    hiddenFields.innerHTML = '';

    pendingNewMaterials.forEach(function(item) {
      Object.keys(item.fields).forEach(function(key) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'new_materials[' + item.index + '][' + key + ']';
        input.value = item.fields[key] ?? '';
        hiddenFields.appendChild(input);
      });
    });

    Object.keys(pendingMaterialUpdates).forEach(function(materialId) {
      var fields = pendingMaterialUpdates[materialId];
      Object.keys(fields).forEach(function(key) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'updated_materials[' + materialId + '][' + key + ']';
        input.value = fields[key] ?? '';
        hiddenFields.appendChild(input);
      });
    });

    pendingNewStudents.forEach(function(item) {
      Object.keys(item.fields).forEach(function(key) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'new_students[' + item.index + '][' + key + ']';
        input.value = item.fields[key] ?? '';
        hiddenFields.appendChild(input);
      });
    });
  }

  function ensureMaterialsEmptyState() {
    if (!materialsBody) return;
    var hasRows = materialsBody.querySelector('tr:not(#materials-empty-row)');
    var emptyRow = document.getElementById('materials-empty-row');
    if (!hasRows && !emptyRow) {
      materialsBody.insertAdjacentHTML('beforeend', '<tr id="materials-empty-row"><td colspan="3" class="py-6 text-center text-slate-400">No materials assigned yet.</td></tr>');
    } else if (hasRows && emptyRow) {
      emptyRow.remove();
    }
  }

  function ensureStudentsEmptyState() {
    if (!studentsBody) return;
    var hasRows = studentsBody.querySelector('tr:not(#students-empty-row)');
    var emptyRow = document.getElementById('students-empty-row');
    if (!hasRows && !emptyRow) {
      studentsBody.insertAdjacentHTML('beforeend', '<tr id="students-empty-row"><td colspan="5" class="py-8 text-center text-slate-400">No students in this section yet.</td></tr>');
    } else if (hasRows && emptyRow) {
      emptyRow.remove();
    }
  }

  function renderPendingMaterialRow(item) {
    return '<tr class="pending-material-row" data-pending-material-index="' + item.index + '">' +
      '<td class="py-3 pr-3"><div class="flex items-center gap-2 font-semibold text-slate-800">' +
      '<span class="material-symbols-outlined text-[#1e3a8a] text-lg">menu_book</span>' +
      escapeHtml(item.fields.name) +
      ' <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Unsaved</span></div></td>' +
      '<td class="py-3 pr-3 text-slate-600">' + escapeHtml(instructorName(item.fields.instructor_id)) + '</td>' +
      '<td class="py-3"><div class="flex items-center gap-1">' +
      '<button type="button" class="edit-pending-material-btn inline-flex size-8 items-center justify-center rounded-md hover:bg-slate-100 border-0 bg-transparent cursor-pointer text-slate-500" title="Edit material">' +
      '<span class="material-symbols-outlined text-lg">edit</span></button>' +
      '<button type="button" class="remove-pending-material-btn inline-flex size-8 items-center justify-center rounded-md hover:bg-red-50 border-0 bg-transparent cursor-pointer text-red-500" title="Remove material">' +
      '<span class="material-symbols-outlined text-lg">delete</span></button></div></td></tr>';
  }

  function renderPendingStudentRow(item) {
    var attendance = parseFloat(item.fields.attendance_percent || 0);
    if (isNaN(attendance)) attendance = 0;
    var attendanceClass = attendance < 75 ? 'bg-red-500' : 'bg-emerald-500';
    var grade = item.fields.grade_average || '—';
    return '<tr class="pending-student-row" data-pending-student-index="' + item.index + '" data-student-id="' + escapeHtml(item.fields.student_id || '') + '">' +
      '<td class="py-3 pr-3"><div><p class="font-semibold text-slate-900">' + escapeHtml(item.fields.full_name) + '</p>' +
      '<p class="text-xs text-slate-500">' + escapeHtml(item.fields.email || '—') + '</p>' +
      '<span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Unsaved</span></div></td>' +
      '<td class="py-3 pr-3 text-slate-600 font-mono text-xs">#' + escapeHtml(item.fields.student_code || 'Auto') + '</td>' +
      '<td class="py-3 pr-3 min-w-[140px]"><div class="flex items-center gap-2"><div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">' +
      '<div class="h-full rounded-full ' + attendanceClass + '" style="width:' + Math.min(100, Math.max(0, attendance)) + '%"></div></div>' +
      '<span class="text-xs font-semibold text-slate-600 w-10">' + Math.round(attendance) + '%</span></div></td>' +
      '<td class="py-3 pr-3"><span class="text-xs font-bold px-2.5 py-1 rounded-full ' + studentGradeClass(grade) + '">' + escapeHtml(grade) + '</span></td>' +
      '<td class="py-3"><button type="button" class="remove-pending-student-btn inline-flex size-8 items-center justify-center rounded-md hover:bg-red-50 border-0 bg-transparent cursor-pointer text-red-500" title="Remove student">' +
      '<span class="material-symbols-outlined text-lg">person_remove</span></button></td></tr>';
  }

  function updateSavedMaterialRow(materialId, fields) {
    var row = document.querySelector('.saved-material-row[data-material-id="' + materialId + '"]');
    if (!row) return;
    row.classList.add('ring-1', 'ring-blue-200', 'bg-blue-50/40');
    var cells = row.querySelectorAll('td');
    if (cells[0]) {
      cells[0].innerHTML = '<div class="flex items-center gap-2 font-semibold text-slate-800">' +
        '<span class="material-symbols-outlined text-[#1e3a8a] text-lg">menu_book</span>' +
        escapeHtml(fields.name) +
        ' <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Unsaved</span></div>';
    }
    if (cells[1]) cells[1].textContent = instructorName(fields.instructor_id);
  }

  function readAssignMaterialFields() {
    return {
      school_subject_id: document.querySelector('#assign-material-form [name="SchoolSectionMaterial[school_subject_id]"]')?.value || '',
      name: document.querySelector('#assign-material-form [name="SchoolSectionMaterial[name]"]')?.value || '',
      instructor_id: document.getElementById('assign-material-instructor')?.value || ''
    };
  }

  function readEditMaterialFields() {
    return {
      school_subject_id: document.getElementById('edit-material-subject')?.value || '',
      name: document.getElementById('edit-material-name')?.value || '',
      instructor_id: document.getElementById('edit-material-instructor')?.value || ''
    };
  }

  function collectAssignedStudentIds(opts) {
    opts = opts || {};
    var ids = [];
    document.querySelectorAll('#students-table-body tr[data-student-id]').forEach(function(row) {
      var studentId = parseInt(row.getAttribute('data-student-id') || 0, 10);
      if (opts.excludeStudentId && studentId === parseInt(opts.excludeStudentId, 10)) {
        return;
      }
      if (studentId > 0) {
        ids.push(studentId);
      }
    });
    pendingNewStudents.forEach(function(item) {
      if (opts.excludePendingIndex != null && item.index === opts.excludePendingIndex) {
        return;
      }
      var studentId = parseInt(item.fields.student_id || 0, 10);
      if (studentId > 0) {
        ids.push(studentId);
      }
    });
    return ids;
  }

  function refreshAssignStudentOptions() {
    if (!assignStudentSelect) return;
    var blocked = collectAssignedStudentIds({});
    var current = assignStudentSelect.value;
    assignStudentSelect.innerHTML = '<option value="">Select student</option>';
    Object.keys(eligibleStudentMap).forEach(function(id) {
      if (blocked.indexOf(parseInt(id, 10)) !== -1) {
        return;
      }
      var student = eligibleStudentMap[id];
      var option = document.createElement('option');
      option.value = id;
      option.textContent = student.full_name + ' · #' + String(student.student_code || '').replace(/^#/, '');
      assignStudentSelect.appendChild(option);
    });
    if (current && blocked.indexOf(parseInt(current, 10)) === -1) {
      assignStudentSelect.value = current;
    }
    var openBtn = document.getElementById('open-assign-student');
    var stageBtn = document.getElementById('stage-assign-student');
    var available = assignStudentSelect.options.length > 1;
    if (openBtn) {
      openBtn.disabled = !available;
      openBtn.title = available ? '' : 'No eligible students for this department and grade';
    }
    if (stageBtn) {
      stageBtn.disabled = !available;
    }
  }

  function readAssignStudentFields() {
    var studentId = assignStudentSelect ? assignStudentSelect.value : '';
    var student = eligibleStudentMap[String(studentId)] || eligibleStudentMap[parseInt(studentId, 10)] || {};
    return {
      student_id: studentId,
      full_name: student.full_name || '',
      email: student.email || '',
      student_code: student.student_code || '',
      attendance_percent: student.attendance_percent || '0',
      grade_average: student.grade_average || ''
    };
  }

  function resetAssignMaterialForm() {
    var form = document.getElementById('assign-material-form');
    if (form) form.reset();
    refreshInstructorOptions(assignInstructorSelect, assignInstructorHint, 0, 0);
  }

  function resetAssignStudentForm() {
    if (assignStudentSelect) {
      assignStudentSelect.value = '';
    }
  }

  document.getElementById('open-edit-section')?.addEventListener('click', function() { openModal('edit-section-modal'); });
  ['assign-material-form', 'assign-student-form', 'edit-material-form'].forEach(function(formId) {
    document.getElementById(formId)?.addEventListener('submit', function(e) { e.preventDefault(); });
  });
  document.querySelectorAll('.section-close-modal').forEach(function(btn) {
    btn.addEventListener('click', closeModals);
  });
  document.querySelectorAll('.section-modal').forEach(function(modal) {
    modal.addEventListener('click', function(e) { if (e.target === modal) closeModals(); });
  });

  function collectAssignedSubjectIds(opts) {
    opts = opts || {};
    var ids = [];
    document.querySelectorAll('.saved-material-row').forEach(function(row) {
      var materialId = row.getAttribute('data-material-id');
      if (opts.excludeMaterialId && String(materialId) === String(opts.excludeMaterialId)) {
        return;
      }
      var btn = row.querySelector('.edit-material-btn');
      var staged = pendingMaterialUpdates[materialId] || {};
      var subjectId = parseInt(staged.school_subject_id || btn?.getAttribute('data-subject') || 0, 10);
      if (subjectId > 0) {
        ids.push(subjectId);
      }
    });
    pendingNewMaterials.forEach(function(item) {
      if (opts.excludePendingIndex != null && item.index === opts.excludePendingIndex) {
        return;
      }
      var subjectId = parseInt(item.fields.school_subject_id || 0, 10);
      if (subjectId > 0) {
        ids.push(subjectId);
      }
    });
    return ids;
  }

  function isDuplicateSubject(subjectId, opts) {
    subjectId = parseInt(subjectId || 0, 10);
    if (!subjectId) {
      return false;
    }
    return collectAssignedSubjectIds(opts).indexOf(subjectId) !== -1;
  }

  function updateAssignMaterialButtonState() {
    var btn = document.getElementById('open-assign-material');
    if (!btn) return;
    var blocked = collectAssignedSubjectIds({});
    var availableCount = Object.keys(allSubjectOptions).filter(function(id) {
      return blocked.indexOf(parseInt(id, 10)) === -1;
    }).length;
    var available = availableCount > 0;
    btn.disabled = !available;
    btn.title = available ? '' : 'All subjects are already assigned to this section';
  }

  function refreshAssignSubjectOptions() {
    if (!subjectSelect) return;
    var blocked = collectAssignedSubjectIds({});
    var current = subjectSelect.value;
    subjectSelect.innerHTML = '<option value="">Select subject</option>';
    Object.keys(allSubjectOptions).forEach(function(id) {
      if (blocked.indexOf(parseInt(id, 10)) !== -1) return;
      var option = document.createElement('option');
      option.value = id;
      option.textContent = allSubjectOptions[id];
      subjectSelect.appendChild(option);
    });
    if (current && blocked.indexOf(parseInt(current, 10)) === -1) {
      subjectSelect.value = current;
    }
    updateAssignMaterialButtonState();
  }

  function refreshEditSubjectOptions(opts) {
    var editSubject = document.getElementById('edit-material-subject');
    if (!editSubject) return;
    var blocked = collectAssignedSubjectIds(opts || {});
    var current = editSubject.value;
    editSubject.innerHTML = '<option value="">Select subject</option>';
    Object.keys(allSubjectOptions).forEach(function(id) {
      if (blocked.indexOf(parseInt(id, 10)) !== -1) return;
      var option = document.createElement('option');
      option.value = id;
      option.textContent = allSubjectOptions[id];
      editSubject.appendChild(option);
    });
    if (current) editSubject.value = current;
  }

  function refreshInstructorOptions(selectEl, hintEl, subjectId, keepInstructorId) {
    if (!selectEl) return;
    subjectId = parseInt(subjectId || 0, 10);
    keepInstructorId = parseInt(keepInstructorId || 0, 10);

    if (!subjectId) {
      selectEl.innerHTML = '<option value="">Select instructor</option>';
      selectEl.value = '';
      selectEl.disabled = true;
      if (hintEl) {
        hintEl.textContent = 'Select a subject first to see instructors for that course.';
        hintEl.classList.remove('hidden');
      }
      return;
    }

    var items = instructorsBySubject[String(subjectId)] || instructorsBySubject[subjectId] || [];
    var html = '<option value="">Select instructor</option>';
    var foundKeep = false;
    items.forEach(function(item) {
      var selected = keepInstructorId > 0 && parseInt(item.id, 10) === keepInstructorId ? ' selected' : '';
      if (selected) foundKeep = true;
      html += '<option value="' + String(item.id) + '"' + selected + '>' + escapeHtml(item.label) + '</option>';
    });
    if (keepInstructorId > 0 && !foundKeep) {
      var fallbackLabel = teacherNameMap[String(keepInstructorId)] || teacherNameMap[keepInstructorId] || '';
      if (fallbackLabel) {
        html += '<option value="' + String(keepInstructorId) + '" selected>' + escapeHtml(fallbackLabel) + '</option>';
        foundKeep = true;
      }
    }

    selectEl.innerHTML = html;
    selectEl.disabled = items.length === 0 && !foundKeep;
    if (hintEl) {
      if (items.length === 0 && !foundKeep) {
        hintEl.textContent = 'No teachers are assigned to this subject yet.';
        hintEl.classList.remove('hidden');
      } else {
        hintEl.textContent = 'Only teachers assigned to this subject appear here.';
        hintEl.classList.remove('hidden');
      }
    }
  }

  var assignInstructorSelect = document.getElementById('assign-material-instructor');
  var editInstructorSelect = document.getElementById('edit-material-instructor');
  var assignInstructorHint = document.getElementById('assign-instructor-hint');
  var editInstructorHint = document.getElementById('edit-instructor-hint');

  var subjectSelect = document.getElementById('assign-material-subject');
  var nameInput = document.getElementById('assign-material-name');
  if (subjectSelect && nameInput) {
    subjectSelect.addEventListener('change', function() {
      var label = subjectMap[String(this.value)] || subjectMap[this.value] || '';
      if (label && !String(nameInput.value).trim()) {
        nameInput.value = label;
      }
      refreshInstructorOptions(assignInstructorSelect, assignInstructorHint, this.value, 0);
    });
  }

  var editSubjectSelect = document.getElementById('edit-material-subject');
  if (editSubjectSelect) {
    editSubjectSelect.addEventListener('change', function() {
      refreshInstructorOptions(editInstructorSelect, editInstructorHint, this.value, 0);
    });
  }

  function validateMaterialFields(fields, duplicateOpts) {
    if (requiresCatalogSubject && !String(fields.school_subject_id || '').trim()) {
      alert('Select a subject from the catalog.');
      return false;
    }
    if (!String(fields.school_subject_id || '').trim() && !String(fields.name || '').trim()) {
      alert('Select a subject or enter a material title.');
      return false;
    }
    if (isDuplicateSubject(fields.school_subject_id, duplicateOpts || {})) {
      alert('This subject is already assigned to this section.');
      return false;
    }
    if (fields.instructor_id) {
      var subjectId = parseInt(fields.school_subject_id || 0, 10);
      var instructorId = parseInt(fields.instructor_id || 0, 10);
      var allowed = instructorsBySubject[String(subjectId)] || instructorsBySubject[subjectId] || [];
      var allowedMatch = allowed.some(function(item) { return parseInt(item.id, 10) === instructorId; });
      if (subjectId > 0 && !allowedMatch) {
        alert('Selected instructor is not assigned to this subject.');
        return false;
      }
    }
    return true;
  }

  document.getElementById('open-assign-material')?.addEventListener('click', function() {
    refreshAssignSubjectOptions();
    refreshInstructorOptions(assignInstructorSelect, assignInstructorHint, subjectSelect ? subjectSelect.value : 0, 0);
    openModal('assign-material-modal');
  });
  document.getElementById('open-assign-student')?.addEventListener('click', function() {
    refreshAssignStudentOptions();
    openModal('assign-student-modal');
  });

  document.getElementById('stage-assign-material')?.addEventListener('click', function() {
    var fields = readAssignMaterialFields();
    if (!validateMaterialFields(fields, {})) {
      return;
    }
    if (String(fields.name || '').trim() === '' && fields.school_subject_id) {
      fields.name = subjectMap[String(fields.school_subject_id)] || subjectMap[fields.school_subject_id] || '';
    }
    var item = { index: nextPendingMaterialIndex++, fields: fields };
    pendingNewMaterials.push(item);
    if (materialsBody) {
      materialsBody.insertAdjacentHTML('beforeend', renderPendingMaterialRow(item));
    }
    ensureMaterialsEmptyState();
    syncHiddenFields();
    resetAssignMaterialForm();
    refreshAssignSubjectOptions();
    closeModals();
  });

  document.getElementById('stage-assign-student')?.addEventListener('click', function() {
    var fields = readAssignStudentFields();
    if (!String(fields.student_id).trim()) {
      alert('Select a student to assign.');
      return;
    }
    if (collectAssignedStudentIds({}).indexOf(parseInt(fields.student_id, 10)) !== -1) {
      alert('This student is already in the section list.');
      return;
    }
    var item = { index: nextPendingStudentIndex++, fields: fields };
    pendingNewStudents.push(item);
    if (studentsBody) {
      studentsBody.insertAdjacentHTML('beforeend', renderPendingStudentRow(item));
    }
    ensureStudentsEmptyState();
    syncHiddenFields();
    resetAssignStudentForm();
    refreshAssignStudentOptions();
    closeModals();
  });

  document.getElementById('stage-edit-material')?.addEventListener('click', function() {
    var fields = readEditMaterialFields();
    if (!validateMaterialFields(fields, editingDuplicateOpts)) {
      return;
    }
    if (String(fields.name || '').trim() === '' && fields.school_subject_id) {
      fields.name = subjectMap[String(fields.school_subject_id)] || subjectMap[fields.school_subject_id] || '';
    }
    if (editingMaterialKey && String(editingMaterialKey).indexOf('pending:') === 0) {
      var pendingIndex = parseInt(editingMaterialKey.split(':')[1], 10);
      var pendingItem = pendingNewMaterials.find(function(item) { return item.index === pendingIndex; });
      if (pendingItem) {
        pendingItem.fields = Object.assign({}, pendingItem.fields, fields);
        var pendingRow = document.querySelector('.pending-material-row[data-pending-material-index="' + pendingIndex + '"]');
        if (pendingRow) {
          pendingRow.outerHTML = renderPendingMaterialRow(pendingItem);
        }
      }
    } else if (editingMaterialKey) {
      pendingMaterialUpdates[editingMaterialKey] = Object.assign({}, pendingMaterialUpdates[editingMaterialKey] || {}, fields);
      updateSavedMaterialRow(editingMaterialKey, pendingMaterialUpdates[editingMaterialKey]);
    }
    syncHiddenFields();
    refreshAssignSubjectOptions();
    closeModals();
  });

  document.querySelectorAll('.edit-material-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      editingMaterialKey = btn.getAttribute('data-id');
      editingDuplicateOpts = { excludeMaterialId: editingMaterialKey };
      var staged = pendingMaterialUpdates[editingMaterialKey] || {};
      document.getElementById('edit-material-name').value = staged.name || btn.getAttribute('data-name') || '';
      refreshEditSubjectOptions(editingDuplicateOpts);
      var editSubject = document.getElementById('edit-material-subject');
      var instructorId = staged.instructor_id || btn.getAttribute('data-instructor') || '';
      if (editSubject) {
        editSubject.value = staged.school_subject_id || btn.getAttribute('data-subject') || '';
      }
      refreshInstructorOptions(editInstructorSelect, editInstructorHint, editSubject ? editSubject.value : 0, instructorId);
      openModal('edit-material-modal');
    });
  });

  document.addEventListener('click', function(e) {
    var editPendingBtn = e.target.closest('.edit-pending-material-btn');
    if (editPendingBtn) {
      var row = editPendingBtn.closest('.pending-material-row');
      var pendingIndex = parseInt(row.getAttribute('data-pending-material-index'), 10);
      var pendingItem = pendingNewMaterials.find(function(item) { return item.index === pendingIndex; });
      if (!pendingItem) return;
      editingMaterialKey = 'pending:' + pendingIndex;
      editingDuplicateOpts = { excludePendingIndex: pendingIndex };
      refreshEditSubjectOptions(editingDuplicateOpts);
      var editSubject = document.getElementById('edit-material-subject');
      if (editSubject) editSubject.value = pendingItem.fields.school_subject_id || '';
      document.getElementById('edit-material-name').value = pendingItem.fields.name || '';
      refreshInstructorOptions(
        editInstructorSelect,
        editInstructorHint,
        editSubject ? editSubject.value : 0,
        pendingItem.fields.instructor_id || ''
      );
      openModal('edit-material-modal');
      return;
    }

    var removePendingMaterialBtn = e.target.closest('.remove-pending-material-btn');
    if (removePendingMaterialBtn) {
      var materialRow = removePendingMaterialBtn.closest('.pending-material-row');
      var materialIndex = parseInt(materialRow.getAttribute('data-pending-material-index'), 10);
      pendingNewMaterials = pendingNewMaterials.filter(function(item) { return item.index !== materialIndex; });
      materialRow.remove();
      ensureMaterialsEmptyState();
      syncHiddenFields();
      refreshAssignSubjectOptions();
      return;
    }

    var removePendingStudentBtn = e.target.closest('.remove-pending-student-btn');
    if (removePendingStudentBtn) {
      var studentRow = removePendingStudentBtn.closest('.pending-student-row');
      var studentIndex = parseInt(studentRow.getAttribute('data-pending-student-index'), 10);
      pendingNewStudents = pendingNewStudents.filter(function(item) { return item.index !== studentIndex; });
      studentRow.remove();
      ensureStudentsEmptyState();
      syncHiddenFields();
      refreshAssignStudentOptions();
    }
  });

  updateAssignMaterialButtonState();
  refreshAssignStudentOptions();
})();
JS
);
?>
<?php endif; ?>
