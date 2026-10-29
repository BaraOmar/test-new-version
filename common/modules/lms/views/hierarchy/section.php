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
 * @var string $accessMode student|department_leader|class_teacher
 * @var bool $isNewSection
 * @var common\modules\employee\models\SchoolStudent|null $toggleStudent
 */

use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolSectionMaterial;
use common\modules\employee\models\SchoolSubjectGrade;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Section Details | LMS';

$isNewSection = !empty($isNewSection);
$isReadOnly = $accessMode === 'student';
$canManage = in_array($accessMode, ['department_leader', 'class_teacher'], true);
$canEditClassTeacher = $accessMode === 'department_leader' && !$isNewSection;
$canDeleteSection = $accessMode === 'department_leader' && !$isNewSection;

$grade = $section->grade;
$leadTeacher = $section->leadTeacher;
$teacherInitials = $leadTeacher
    ? strtoupper(substr($leadTeacher->full_name, 0, 1) . substr(strstr($leadTeacher->full_name, ' ') ?: '', 1, 1))
    : '?';
$assignedSince = $section->lead_teacher_assigned_at
    ? Yii::$app->formatter->asDate($section->lead_teacher_assigned_at, 'php:M j, Y')
    : null;

$formatTeacherLabel = static function ($teacher): string {
    $title = trim((string)($teacher->job_title ?? ''));
    return $title !== '' ? $teacher->full_name . ' (' . $title . ')' : $teacher->full_name;
};
$classTeacherOptions = ArrayHelper::map($classTeachers ?? $teachers, 'id', $formatTeacherLabel);
$instructorOptions = ArrayHelper::map($instructorTeachers ?? $teachers, 'id', $formatTeacherLabel);
$teacherNameMap = $instructorOptions;

$instructorsForSubject = static function (?int $subjectId, ?int $keepInstructorId = null) use ($instructorsBySubject, $teacherNameMap): array {
    if ($subjectId === null || $subjectId <= 0) {
        return [];
    }

    $options = [];
    foreach ($instructorsBySubject[$subjectId] ?? [] as $item) {
        $options[(int)$item['id']] = (string)$item['label'];
    }

    if ($keepInstructorId !== null && $keepInstructorId > 0 && !isset($options[$keepInstructorId])) {
        $fallback = $teacherNameMap[$keepInstructorId] ?? null;
        if ($fallback !== null) {
            $options[$keepInstructorId] = $fallback;
        }
    }

    return $options;
};

$subjectOptions = [];
foreach ($subjects as $item) {
    $credits = isset($item['credits']) ? (float)$item['credits'] : null;
    $creditsLabel = $credits !== null ? rtrim(rtrim(number_format($credits, 2, '.', ''), '0'), '.') : null;
    $subjectOptions[(int)$item['id']] = $creditsLabel !== null
        ? $item['name'] . ' (' . $creditsLabel . ' credits)'
        : (string)$item['name'];
}
$assignedSubjectIds = [];
foreach ($materials as $material) {
    $subjectId = (int)($material->school_subject_id ?? 0);
    if ($subjectId > 0) {
        $assignedSubjectIds[$subjectId] = true;
    }
}
$availableSubjectOptions = array_diff_key($subjectOptions, $assignedSubjectIds);

$eligibleStudentOptions = ArrayHelper::map(
    $eligibleStudents ?? [],
    'id',
    static fn($student) => $student->getDisplayFullName() . ' · ' . $student->getRegistrationLabel()
);

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');

$backUrl = $isReadOnly
    ? Url::to(['/lms/student-affairs/profile'])
    : Url::to(['/lms/hierarchy/index']);

$sectionDisplayName = 'Section ' . trim((string)$section->name);
$gradeName = trim((string)($grade?->name ?? ''));
$materialCount = count($materials);
$departmentName = trim((string)($department?->name ?? 'this department'));
$toggleStudent = $toggleStudent ?? null;

?>
<?= $this->render('../layouts/_page-start') ?>
    <?php if ($toggleStudent !== null): ?>
        <?= $this->render('../student-affairs/_profile-section-toggle-wrapper', [
            'model' => $toggleStudent,
            'activeTab' => 'section',
        ]) ?>
    <?php endif; ?>
    <header class="space-y-3">
        <?php if ($toggleStudent === null): ?>
        <nav class="text-sm text-slate-500" aria-label="Breadcrumb">
            <a href="<?= Html::encode($backUrl) ?>" class="text-slate-500 hover:text-[#642754] no-underline">
                <?= $isReadOnly ? 'My Profile' : 'Classes & Sections' ?>
            </a>
            <?php if (!$isNewSection && $gradeName !== ''): ?>
                <span class="mx-2 text-slate-400">&gt;</span>
                <span class="text-slate-500"><?= Html::encode($gradeName) ?></span>
            <?php endif; ?>
            <?php if (!$isNewSection): ?>
                <span class="mx-2 text-slate-400">&gt;</span>
                <span class="text-slate-900 font-medium"><?= Html::encode($sectionDisplayName) ?></span>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-3xl font-black text-[#642754] tracking-tight">
                    <?php if ($isNewSection): ?>
                        New Section
                    <?php else: ?>
                        Section Details - <?= Html::encode($section->name) ?><?= $gradeName !== '' ? ' · ' . Html::encode($gradeName) : '' ?>
                    <?php endif; ?>
                </h2>
                <p class="text-slate-500 mt-2 text-base max-w-3xl">
                    <?php if ($isReadOnly): ?>
                        Your grade, section, class teacher, and assigned materials.
                    <?php elseif ($isNewSection): ?>
                        Create a new section for <?= Html::encode($department?->name ?? 'your department') ?>.
                    <?php else: ?>
                        Manage teacher assignments, materials, and the student list for this section.
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <?php if ($canManage && !$isNewSection && $accessMode === 'department_leader'): ?>
                    <button type="button"
                            id="tt-edit-section-open"
                            class="inline-flex items-center gap-2 h-10 px-4 rounded-lg border border-slate-200 text-slate-700 text-sm font-semibold bg-white hover:bg-slate-50 cursor-pointer">
                        <span class="material-symbols-outlined text-lg">edit</span>
                        Edit Section
                    </button>
                <?php endif; ?>
                <?php if ($canDeleteSection): ?>
                    <?= Html::beginForm(['/lms/hierarchy/delete-section', 'id' => (int)$section->id], 'post', ['class' => 'm-0']) ?>
                        <button type="submit"
                                class="inline-flex items-center gap-2 h-10 px-4 rounded-lg border border-red-200 text-red-600 text-sm font-bold bg-white hover:bg-red-50 cursor-pointer"
                                onclick="return confirm('Delete this section?');">
                            <span class="material-symbols-outlined text-lg">delete</span>
                            Delete Section
                        </button>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if ($successMessage !== ''): ?>
        <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-medium"><?= Html::encode($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium"><?= Html::encode($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($isNewSection): ?>
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 lg:p-8">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Section Setup</h3>
            <?php if ($section->hasErrors()): ?>
                <div class="p-4 mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm"><?= Html::errorSummary($section) ?></div>
            <?php endif; ?>
            <?= Html::beginForm(['/lms/hierarchy/create-section', 'grade_id' => (int)$section->school_grade_id], 'post', ['class' => 'space-y-5']) ?>
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
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="h-11 px-6 rounded-lg bg-[#642754] hover:bg-[#7c3a6b] text-white text-sm font-bold border-0 cursor-pointer">
                        Create Section
                    </button>
                </div>
            <?= Html::endForm() ?>
        </section>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <section class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Class Teacher</h3>
                <?php if ($leadTeacher): ?>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 mb-5">
                        <div class="flex items-start gap-3">
                            <div class="size-11 rounded-full bg-[#642754]/10 text-[#642754] flex items-center justify-center text-sm font-black shrink-0">
                                <?= Html::encode($teacherInitials) ?>
                            </div>
                            <div class="min-w-0">
                                <p class="text-base font-bold text-slate-900"><?= Html::encode($leadTeacher->full_name) ?></p>
                                <p class="text-sm text-slate-500"><?= Html::encode(trim((string)($leadTeacher->job_title ?? 'Teacher')) ?: 'Teacher') ?></p>
                                <?php if ($assignedSince): ?>
                                    <p class="text-xs text-slate-400 mt-1">Assigned since <?= Html::encode($assignedSince) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 mb-5 text-sm text-slate-500">
                        No class teacher assigned yet.
                    </div>
                <?php endif; ?>

                <?php if ($canEditClassTeacher): ?>
                    <?= Html::beginForm(['/lms/hierarchy/assign-class-teacher', 'id' => (int)$section->id], 'post', ['class' => 'space-y-2', 'id' => 'class-teacher-form']) ?>
                        <label for="class-teacher-select" class="block text-[11px] font-bold uppercase tracking-wide text-slate-400">Assign Class Teacher</label>
                        <?= Html::dropDownList('lead_teacher_id', $section->lead_teacher_id, $classTeacherOptions, [
                            'prompt' => 'Select teacher...',
                            'id' => 'class-teacher-select',
                            'class' => 'w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm text-slate-900 outline-none transition-[border-color,box-shadow] focus:border-[#642754] focus:ring-2 focus:ring-[#642754]/20',
                        ]) ?>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Any teacher in <?= Html::encode($departmentName) ?> can be class teacher. Teachers from other departments cannot.
                        </p>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </section>

            <section class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Material Allocation</h3>
                        <p class="text-sm text-slate-500 mt-1">Currently <?= (int)$materialCount ?> subject<?= $materialCount === 1 ? '' : 's' ?> assigned</p>
                    </div>
                    <?php if ($canManage && $availableSubjectOptions !== []): ?>
                        <button type="button"
                                id="assign-material-open"
                                class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-700 bg-transparent border-0 cursor-pointer p-0">
                            <span class="material-symbols-outlined text-base">add</span>
                            Assign Material
                        </button>
                    <?php endif; ?>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100">
                                <th class="pb-3 pr-3">Subject / Material</th>
                                <th class="pb-3 pr-3">Assigned Instructor</th>
                                <?php if ($canManage): ?>
                                    <th class="pb-3 text-right">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if ($materials === []): ?>
                                <tr><td colspan="<?= $canManage ? 3 : 2 ?>" class="py-8 text-center text-slate-400">No materials assigned yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($materials as $material): ?>
                                <?php
                                $materialSubjectId = (int)($material->school_subject_id ?? 0);
                                $materialInstructorOptions = $instructorsForSubject(
                                    $materialSubjectId > 0 ? $materialSubjectId : null,
                                    (int)($material->instructor_id ?? 0) ?: null
                                );
                                $subjectLabel = trim((string)($material->subject?->name ?? $material->name ?? 'Subject'));
                                ?>
                                <tr>
                                    <td class="py-3 pr-3">
                                        <div class="flex items-center gap-2 font-semibold text-slate-800">
                                            <span class="material-symbols-outlined text-[#642754] text-lg">menu_book</span>
                                            <span><?= Html::encode($subjectLabel) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 pr-3 text-slate-600"><?= Html::encode($material->instructor?->full_name ?: 'Unassigned') ?></td>
                                    <?php if ($canManage): ?>
                                        <td class="py-3">
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button"
                                                        class="edit-material-open inline-flex size-8 items-center justify-center rounded-md hover:bg-slate-100 border-0 bg-transparent cursor-pointer text-blue-600"
                                                        title="Edit instructor"
                                                        data-material-id="<?= (int)$material->id ?>"
                                                        data-material-name="<?= Html::encode($subjectLabel) ?>"
                                                        data-subject-id="<?= $materialSubjectId ?>"
                                                        data-instructor-id="<?= (int)($material->instructor_id ?? 0) ?>"
                                                        data-instructor-options="<?= Html::encode(json_encode($materialInstructorOptions, JSON_UNESCAPED_UNICODE)) ?>">
                                                    <span class="material-symbols-outlined text-lg">edit</span>
                                                </button>
                                                <?= Html::beginForm(['/lms/hierarchy/delete-material', 'id' => (int)$section->id, 'materialId' => (int)$material->id], 'post', ['class' => 'inline m-0']) ?>
                                                    <button type="submit"
                                                            class="inline-flex size-8 items-center justify-center rounded-md hover:bg-red-50 border-0 bg-transparent cursor-pointer text-red-500"
                                                            title="Remove material"
                                                            onclick="return confirm('Remove this material?');">
                                                        <span class="material-symbols-outlined text-lg">delete</span>
                                                    </button>
                                                <?= Html::endForm() ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <?php if ($canManage && $accessMode === 'department_leader'): ?>
            <div id="edit-section-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" aria-hidden="true">
                <div id="edit-section-backdrop" class="absolute inset-0 bg-slate-900/45"></div>
                <div class="relative w-full max-w-lg bg-white rounded-2xl border border-slate-200 shadow-xl p-6" role="dialog" aria-modal="true">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <h4 class="text-lg font-bold text-slate-900">Edit Section</h4>
                        <button type="button" id="edit-section-close" class="size-8 rounded-lg border border-slate-200 bg-white text-slate-500 cursor-pointer flex items-center justify-center" aria-label="Close">
                            <span class="material-symbols-outlined text-base">close</span>
                        </button>
                    </div>
                    <?= Html::beginForm(['/lms/hierarchy/update-section', 'id' => (int)$section->id], 'post', ['class' => 'space-y-4']) ?>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Section name</label>
                            <?= Html::activeTextInput($section, 'name', ['class' => 'w-full h-10 px-3 rounded-lg border border-slate-200 text-sm']) ?>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Room number</label>
                            <?= Html::activeTextInput($section, 'room_number', ['class' => 'w-full h-10 px-3 rounded-lg border border-slate-200 text-sm']) ?>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Capacity</label>
                            <?= Html::activeInput('number', $section, 'capacity_max', ['min' => 1, 'class' => 'w-full h-10 px-3 rounded-lg border border-slate-200 text-sm']) ?>
                        </div>
                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" id="edit-section-cancel" class="h-10 px-4 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-semibold cursor-pointer">Cancel</button>
                            <button type="submit" class="h-10 px-4 rounded-lg bg-[#642754] text-white text-sm font-bold border-0 cursor-pointer">Save Section</button>
                        </div>
                    <?= Html::endForm() ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($canManage && $availableSubjectOptions !== []): ?>
            <div id="assign-material-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" aria-hidden="true">
                <div id="assign-material-backdrop" class="absolute inset-0 bg-slate-900/45"></div>
                <div class="relative w-full max-w-lg bg-white rounded-2xl border border-slate-200 shadow-xl p-6" role="dialog" aria-modal="true">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <h4 class="text-lg font-bold text-slate-900">Assign Material</h4>
                        <button type="button" id="assign-material-close" class="size-8 rounded-lg border border-slate-200 bg-white text-slate-500 cursor-pointer flex items-center justify-center" aria-label="Close">
                            <span class="material-symbols-outlined text-base">close</span>
                        </button>
                    </div>
                    <?= Html::beginForm(['/lms/hierarchy/assign-material', 'id' => (int)$section->id], 'post', ['class' => 'space-y-4']) ?>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Subject<?= SchoolSubjectGrade::isEnabled() ? ' *' : '' ?></label>
                            <?= Html::dropDownList('SchoolSectionMaterial[school_subject_id]', null, $availableSubjectOptions, [
                                'prompt' => 'Select subject...',
                                'id' => 'assign-material-subject',
                                'class' => 'w-full h-10 px-3 rounded-lg border border-slate-200 text-sm',
                                'required' => SchoolSubjectGrade::isEnabled(),
                            ]) ?>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Instructor</label>
                            <?= Html::dropDownList('SchoolSectionMaterial[instructor_id]', null, [], [
                                'prompt' => 'Select subject first...',
                                'id' => 'assign-material-instructor',
                                'class' => 'w-full h-10 px-3 rounded-lg border border-slate-200 text-sm',
                                'disabled' => true,
                            ]) ?>
                            <p id="assign-instructor-hint" class="text-xs text-slate-500 mt-1">Select a subject to see teachers assigned to it.</p>
                        </div>
                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" id="assign-material-cancel" class="h-10 px-4 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-semibold cursor-pointer">Cancel</button>
                            <button type="submit" class="h-10 px-4 rounded-lg bg-[#642754] text-white text-sm font-bold border-0 cursor-pointer">Assign Material</button>
                        </div>
                    <?= Html::endForm() ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($canManage): ?>
            <div id="edit-material-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" aria-hidden="true">
                <div id="edit-material-backdrop" class="absolute inset-0 bg-slate-900/45"></div>
                <div class="relative w-full max-w-lg bg-white rounded-2xl border border-slate-200 shadow-xl p-6" role="dialog" aria-modal="true">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">Edit Material</h4>
                            <p id="edit-material-subtitle" class="text-sm text-slate-500 mt-1"></p>
                        </div>
                        <button type="button" id="edit-material-close" class="size-8 rounded-lg border border-slate-200 bg-white text-slate-500 cursor-pointer flex items-center justify-center" aria-label="Close">
                            <span class="material-symbols-outlined text-base">close</span>
                        </button>
                    </div>
                    <?= Html::beginForm(['/lms/hierarchy/update-material', 'id' => (int)$section->id, 'materialId' => 0], 'post', ['id' => 'edit-material-form', 'class' => 'space-y-4']) ?>
                        <input type="hidden" name="SchoolSectionMaterial[school_subject_id]" id="edit-material-subject-id" value="">
                        <input type="hidden" name="SchoolSectionMaterial[name]" id="edit-material-name" value="">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Assigned Instructor</label>
                            <?= Html::dropDownList('SchoolSectionMaterial[instructor_id]', null, [], [
                                'prompt' => 'Select instructor...',
                                'id' => 'edit-material-instructor',
                                'class' => 'w-full h-10 px-3 rounded-lg border border-slate-200 text-sm',
                            ]) ?>
                        </div>
                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" id="edit-material-cancel" class="h-10 px-4 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-semibold cursor-pointer">Cancel</button>
                            <button type="submit" class="h-10 px-4 rounded-lg bg-[#642754] text-white text-sm font-bold border-0 cursor-pointer">Save Changes</button>
                        </div>
                    <?= Html::endForm() ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$isReadOnly): ?>
            <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Student Roster</h3>
                        <p class="text-sm text-slate-500 mt-1"><?= (int)$section->current_enrollment ?> students enrolled</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <?= Html::beginForm(['/lms/hierarchy/section', 'id' => (int)$section->id], 'get', ['class' => 'm-0']) ?>
                            <div class="relative group">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[20px] leading-none text-slate-400 pointer-events-none transition-colors group-focus-within:text-[#642754]">search</span>
                                <?= Html::textInput('roster', $rosterSearch, [
                                    'placeholder' => 'Filter students...',
                                    'autocomplete' => 'off',
                                    'aria-label' => 'Filter students',
                                    'class' => 'h-10 w-64 min-w-[14rem] pl-10 pr-3 rounded-lg border border-slate-200 bg-white text-sm text-slate-900 placeholder:text-slate-400 outline-none transition-[border-color,box-shadow] focus:border-[#642754] focus:ring-2 focus:ring-[#642754]/20',
                                ]) ?>
                            </div>
                        <?= Html::endForm() ?>
                        <?php if ($canManage && $eligibleStudentOptions !== []): ?>
                            <?= Html::beginForm(['/lms/hierarchy/assign-student', 'id' => (int)$section->id], 'post', ['class' => 'm-0 flex flex-wrap items-center gap-2']) ?>
                                <?= Html::dropDownList('student_id', null, $eligibleStudentOptions, [
                                    'prompt' => 'Select student...',
                                    'aria-label' => 'Select student to assign',
                                    'class' => 'h-10 min-w-[14rem] px-3 rounded-lg border border-slate-200 bg-white text-sm text-slate-900 outline-none transition-[border-color,box-shadow] focus:border-[#642754] focus:ring-2 focus:ring-[#642754]/20',
                                    'required' => true,
                                ]) ?>
                                <button type="submit" class="h-10 px-4 rounded-lg bg-[#642754] hover:bg-[#4a2a51] text-white text-sm font-bold border-0 cursor-pointer whitespace-nowrap">Assign</button>
                            <?= Html::endForm() ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100">
                                <th class="pb-3 pr-3">Student</th>
                                <th class="pb-3 pr-3">Student ID</th>
                                <th class="pb-3 pr-3">Attendance</th>
                                <?php if ($canManage): ?>
                                    <th class="pb-3">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if ($students === []): ?>
                                <tr><td colspan="<?= $canManage ? 4 : 3 ?>" class="py-8 text-center text-slate-400">No students in this section yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="py-3 pr-3">
                                        <p class="font-semibold text-slate-900"><?= Html::encode($student->getDisplayFullName()) ?></p>
                                    </td>
                                    <td class="py-3 pr-3 font-mono text-xs text-slate-600">#<?= Html::encode($student->student_code) ?></td>
                                    <td class="py-3 pr-3 text-slate-600"><?= number_format((float)$student->attendance_percent, 0) ?>%</td>
                                    <?php if ($canManage): ?>
                                        <td class="py-3">
                                            <a href="<?= Url::to(['/lms/student-affairs/view', 'id' => (int)$student->id]) ?>"
                                               class="inline-flex size-8 items-center justify-center rounded-md hover:bg-slate-100 text-slate-500 no-underline"
                                               title="View profile">
                                                <span class="material-symbols-outlined text-lg">visibility</span>
                                            </a>
                                            <?= Html::beginForm(['/lms/hierarchy/remove-student', 'id' => (int)$section->id, 'studentId' => (int)$student->id], 'post', ['class' => 'inline m-0']) ?>
                                                <button type="submit" class="inline-flex size-8 items-center justify-center rounded-md hover:bg-red-50 border-0 bg-transparent cursor-pointer text-red-500"
                                                        onclick="return confirm('Remove this student from the section?');" title="Remove">
                                                    <span class="material-symbols-outlined text-lg">person_remove</span>
                                                </button>
                                            <?= Html::endForm() ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?= LinkPager::widget([
                    'pagination' => $studentPagination,
                    'options' => ['class' => 'flex flex-wrap gap-1 mt-4'],
                    'linkOptions' => ['class' => 'px-3 py-1 rounded border border-slate-200 text-sm no-underline text-slate-700 hover:bg-slate-50'],
                    'disabledPageCssClass' => 'opacity-50 pointer-events-none',
                ]) ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>
<?php
if (!$isNewSection):
    $instructorsBySubjectJson = json_encode($instructorsBySubject ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $teacherNameMapJson = json_encode($teacherNameMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $updateMaterialUrlTemplate = Url::to(['/lms/hierarchy/update-material', 'id' => (int)$section->id, 'materialId' => '__MATERIAL_ID__']);
    $updateMaterialUrlTemplateJson = Json::encode($updateMaterialUrlTemplate);
    $this->registerJs(<<<JS
(function() {
  var instructorsBySubject = {$instructorsBySubjectJson} || {};
  var teacherNameMap = {$teacherNameMapJson} || {};
  var updateMaterialUrlTemplate = {$updateMaterialUrlTemplateJson};
  var subjectSelect = document.getElementById('assign-material-subject');
  var instructorSelect = document.getElementById('assign-material-instructor');
  var hintEl = document.getElementById('assign-instructor-hint');
  var editInstructorSelect = document.getElementById('edit-material-instructor');
  var editMaterialForm = document.getElementById('edit-material-form');
  var editMaterialSubtitle = document.getElementById('edit-material-subtitle');
  var classTeacherSelect = document.getElementById('class-teacher-select');
  var classTeacherForm = document.getElementById('class-teacher-form');

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function openModal(modal) {
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
  }

  function bindModal(modalId, openId, closeIds) {
    var modal = document.getElementById(modalId);
    var openBtn = openId ? document.getElementById(openId) : null;
    if (openBtn && modal) {
      openBtn.addEventListener('click', function() { openModal(modal); });
    }
    (closeIds || []).forEach(function(closeId) {
      var closeEl = document.getElementById(closeId);
      if (closeEl && modal) {
        closeEl.addEventListener('click', function() { closeModal(modal); });
      }
    });
    if (modal) {
      modal.querySelectorAll('[id$="-backdrop"]').forEach(function(backdrop) {
        backdrop.addEventListener('click', function() { closeModal(modal); });
      });
    }
  }

  function fillInstructorSelect(selectEl, optionsMap, keepInstructorId) {
    if (!selectEl) return;
    keepInstructorId = parseInt(keepInstructorId || 0, 10);
    var html = '<option value="">Select instructor...</option>';
    var foundKeep = false;
    Object.keys(optionsMap || {}).forEach(function(key) {
      var selected = keepInstructorId > 0 && parseInt(key, 10) === keepInstructorId ? ' selected' : '';
      if (selected) foundKeep = true;
      html += '<option value="' + escapeHtml(key) + '"' + selected + '>' + escapeHtml(optionsMap[key]) + '</option>';
    });
    if (keepInstructorId > 0 && !foundKeep) {
      var fallbackLabel = teacherNameMap[String(keepInstructorId)] || teacherNameMap[keepInstructorId] || '';
      if (fallbackLabel) {
        html += '<option value="' + String(keepInstructorId) + '" selected>' + escapeHtml(fallbackLabel) + '</option>';
        foundKeep = true;
      }
    }
    selectEl.innerHTML = html;
    selectEl.disabled = Object.keys(optionsMap || {}).length === 0 && !foundKeep;
  }

  function refreshInstructorOptions(subjectId, keepInstructorId) {
    if (!instructorSelect) return;
    subjectId = parseInt(subjectId || 0, 10);
    keepInstructorId = parseInt(keepInstructorId || 0, 10);

    if (!subjectId) {
      instructorSelect.innerHTML = '<option value="">Select subject first...</option>';
      instructorSelect.value = '';
      instructorSelect.disabled = true;
      if (hintEl) {
        hintEl.textContent = 'Select a subject to see teachers assigned to it.';
      }
      return;
    }

    var items = instructorsBySubject[String(subjectId)] || instructorsBySubject[subjectId] || [];
    var optionsMap = {};
    items.forEach(function(item) {
      optionsMap[String(item.id)] = item.label;
    });
    fillInstructorSelect(instructorSelect, optionsMap, keepInstructorId);
    if (hintEl) {
      hintEl.textContent = items.length === 0 && keepInstructorId <= 0
        ? 'No teachers are assigned to this subject yet.'
        : 'Only teachers assigned to this subject appear here.';
    }
  }

  if (subjectSelect) {
    subjectSelect.addEventListener('change', function() {
      refreshInstructorOptions(this.value, 0);
    });
    if (subjectSelect.value) {
      refreshInstructorOptions(subjectSelect.value, 0);
    }
  }

  bindModal('assign-material-modal', 'assign-material-open', ['assign-material-close', 'assign-material-cancel']);
  bindModal('edit-section-modal', 'tt-edit-section-open', ['edit-section-close', 'edit-section-cancel']);
  bindModal('edit-material-modal', null, ['edit-material-close', 'edit-material-cancel']);

  document.querySelectorAll('.edit-material-open').forEach(function(button) {
    button.addEventListener('click', function() {
      var materialId = parseInt(button.getAttribute('data-material-id') || '0', 10);
      var materialName = button.getAttribute('data-material-name') || '';
      var subjectId = button.getAttribute('data-subject-id') || '';
      var instructorId = parseInt(button.getAttribute('data-instructor-id') || '0', 10);
      var optionsMap = {};
      try {
        optionsMap = JSON.parse(button.getAttribute('data-instructor-options') || '{}') || {};
      } catch (e) {
        optionsMap = {};
      }

      if (editMaterialForm) {
        editMaterialForm.action = updateMaterialUrlTemplate.replace('__MATERIAL_ID__', String(materialId));
      }
      var subjectInput = document.getElementById('edit-material-subject-id');
      var nameInput = document.getElementById('edit-material-name');
      if (subjectInput) subjectInput.value = subjectId;
      if (nameInput) nameInput.value = materialName;
      if (editMaterialSubtitle) editMaterialSubtitle.textContent = materialName;
      fillInstructorSelect(editInstructorSelect, optionsMap, instructorId);
      openModal(document.getElementById('edit-material-modal'));
    });
  });

  if (classTeacherSelect && classTeacherForm) {
    var lastClassTeacherValue = classTeacherSelect.value;
    classTeacherSelect.addEventListener('change', function() {
      if (!this.value || this.value === lastClassTeacherValue) {
        return;
      }
      classTeacherForm.submit();
    });
  }

  document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') return;
    ['assign-material-modal', 'edit-section-modal', 'edit-material-modal'].forEach(function(modalId) {
      var modal = document.getElementById(modalId);
      if (modal && !modal.classList.contains('hidden')) {
        closeModal(modal);
      }
    });
  });
})();
JS
    );
endif;
?>
<?= $this->render('../layouts/_page-end') ?>
