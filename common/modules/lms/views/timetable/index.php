<?php

/**
 * @var yii\web\View $this
 * @var string $accessType
 * @var common\modules\employee\models\SchoolDepartment[] $departments
 * @var array<int, array<int, array{id:int,name:string}>> $gradesByDepartment
 * @var array<int, array<int, array{id:int,name:string,label:string}>> $sectionsByGrade
 * @var bool $canManage
 * @var bool $canEditLessonBrief
 * @var int $currentEmployeeId
 * @var array<int, string> $dayNames
 * @var int $initialDepartmentId
 * @var int $initialGradeId
 * @var int $initialSectionId
 * @var bool $isStudentView
 * @var string $studentScheduleLabel
 */

use common\components\LmsPortal;
use common\modules\employee\models\SchoolStudent;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$isStudentView = $isStudentView ?? false;
$studentScheduleLabel = $studentScheduleLabel ?? '';
$canEditLessonBrief = $canEditLessonBrief ?? false;
$currentEmployeeId = (int)($currentEmployeeId ?? 0);

$this->title = $isStudentView ? 'My Schedule | LMS' : 'Timetable Management | LMS';

$departmentOptions = ArrayHelper::map($departments, 'id', 'name');
$gradesUrl = Url::to(['/lms/timetable/grades-by-department']);
$sectionsUrl = Url::to(['/lms/timetable/sections-by-grade']);
$loadUrl = Url::to(['/lms/timetable/load']);
$saveGridUrl = Url::to(['/lms/timetable/save-grid']);
$saveSlotUrl = Url::to(['/lms/timetable/save-slot']);
$clearSlotsUrl = Url::to(['/lms/timetable/clear-slots']);
$courseOptionsUrl = Url::to(['/lms/timetable/course-options']);
$saveLessonBriefUrl = Url::to(['/lms/timetable/save-lesson-brief']);
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
$dayNamesJson = Json::htmlEncode($dayNames);
$academicYear = SchoolStudent::defaultAcademicYear();

$this->registerCss(<<<'CSS'
.tt-course-card { cursor: grab; user-select: none; }
.tt-course-card:active { cursor: grabbing; }
.tt-drop-zone { cursor: pointer; }
.tt-drop-zone:hover { border-color: #642754; color: #642754; background: #faf5ff; }
.tt-grid-cell { min-width: 10rem; min-height: 5.5rem; }
.tt-grid-cell.drag-over { outline: 2px dashed #642754; outline-offset: -2px; background: #faf5ff; }
.tt-slot-card { border-left: 4px solid #1e3a8a; }
.tt-slot-note { font-size: 11px; color: #475569; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #cbd5e1; line-height: 1.35; word-break: break-word; }
.tt-slot-note--empty { color: #94a3b8; font-style: italic; }
.tt-slot-note--editable:hover { color: #642754; }
.tt-lesson-modal { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
.tt-lesson-modal.hidden { display: none; }
.tt-lesson-modal-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.45); }
.tt-lesson-modal-panel { position: relative; width: min(28rem, 100%); background: #fff; border-radius: 0.75rem; border: 1px solid #e2e8f0; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18); padding: 1.25rem; }
.tt-break-cell { background: #f8fafc; color: #94a3b8; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
.tt-exam-cell { background: #fffbeb; color: #b45309; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
.tt-picker-modal { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
.tt-picker-modal.hidden { display: none; }
.tt-picker-modal-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.45); }
.tt-picker-modal-panel { position: relative; width: min(28rem, 100%); max-height: min(32rem, 90vh); overflow: hidden; display: flex; flex-direction: column; background: #fff; border-radius: 0.75rem; border: 1px solid #e2e8f0; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18); }
.tt-picker-option:not(:disabled):hover { background: #faf5ff; border-color: #642754; }
.tt-collapse-body { overflow: hidden; transition: max-height 0.25s ease, opacity 0.2s ease; max-height: 4000px; opacity: 1; }
.tt-collapse-body.is-collapsed { max-height: 0; opacity: 0; pointer-events: none; }
.tt-collapse-toggle .material-symbols-outlined { transition: transform 0.2s ease; }
.tt-collapse-toggle.is-collapsed .material-symbols-outlined { transform: rotate(-180deg); }
.tt-column-conflict { border-color: #fca5a5 !important; background: #fef2f2 !important; }
#tt-print-root { display: none; }
.tt-print-sheet { font-family: Arial, Helvetica, sans-serif; color: #0f172a; }
.tt-print-header { margin-bottom: 1.25rem; }
.tt-print-title { margin: 0; font-size: 1.75rem; font-weight: 700; line-height: 1.2; color: #0f172a; }
.tt-print-year { margin: 0.35rem 0 0; font-size: 1rem; color: #64748b; }
.tt-print-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 11px; }
.tt-print-table th,
.tt-print-table td { border: 1px solid #cbd5e1; vertical-align: top; padding: 0.65rem 0.55rem; }
.tt-print-day-head,
.tt-print-period-head { background: #dbeafe; text-align: center; font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #1e3a8a; }
.tt-print-period-time { margin-top: 0.25rem; font-size: 10px; font-weight: 600; letter-spacing: normal; text-transform: none; color: #334155; }
.tt-print-day-cell { width: 6.5rem; font-weight: 700; background: #f8fafc; }
.tt-print-class-cell { min-height: 5rem; }
.tt-print-subject { font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 0.2rem; }
.tt-print-teacher { font-size: 10px; color: #475569; line-height: 1.35; }
.tt-print-note { font-size: 10px; color: #475569; line-height: 1.35; margin-top: 0.35rem; padding-top: 0.35rem; border-top: 1px dashed #cbd5e1; word-break: break-word; }
.tt-print-break-head { background: #e2e8f0; color: #64748b; }
.tt-print-break-cell { background: #f1f5f9; color: #94a3b8; text-align: center; font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; vertical-align: middle; }
.tt-print-exam-head { background: #fef3c7; color: #b45309; }
.tt-print-exam-cell { background: #fffbeb; color: #b45309; text-align: center; font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; vertical-align: middle; }
.tt-print-empty-cell { background: #fff; }
@media print {
  @page { size: landscape; margin: 12mm; }
  body * { visibility: hidden !important; }
  #tt-print-root,
  #tt-print-root * { visibility: visible !important; }
  #tt-print-root {
    display: block !important;
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    padding: 0;
    background: #fff;
  }
}
CSS
);

?>
<?= $this->render('../layouts/_page-start') ?>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight"><?= $isStudentView ? 'My Schedule' : ('Timetable' . ($canManage ? ' Management' : '')) ?></h2>
            <?php if ($isStudentView): ?>
                <p class="text-slate-500 mt-2 text-lg">
                    <?php if ($studentScheduleLabel !== ''): ?>
                        Your weekly timetable for <span class="font-semibold text-slate-700"><?= Html::encode($studentScheduleLabel) ?></span>.
                    <?php else: ?>
                        View your weekly class timetable with subjects and assigned teachers.
                    <?php endif; ?>
                </p>
            <?php elseif ($canManage): ?>
                <p class="text-slate-500 mt-2 text-lg">Schedule classes for a section, customize days and hours, and prevent teacher conflicts.</p>
            <?php elseif ($accessType === LmsPortal::TIMETABLE_ACCESS_INSTRUCTOR): ?>
                <p class="text-slate-500 mt-2 text-lg">View your assigned teaching periods.</p>
            <?php else: ?>
                <p class="text-slate-500 mt-2 text-lg">View your section schedule with subjects and assigned teachers.</p>
            <?php endif; ?>
        </div>
    </header>

    <?php
    $sessionError = (string)Yii::$app->session->getFlash('error', '');
    if ($sessionError !== ''): ?>
        <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium"><?= Html::encode($sessionError) ?></div>
    <?php endif; ?>

    <?php if ($isStudentView && $initialSectionId <= 0): ?>
        <div class="p-4 rounded-xl bg-amber-50 text-amber-900 border border-amber-200 text-sm">
            Your class section is not assigned yet. Please ask your school administrator to assign you to a section so your schedule can be displayed.
        </div>
    <?php endif; ?>

    <div id="tt-flash" class="hidden p-4 rounded-xl text-sm font-medium"></div>

    <?php if (!$isStudentView): ?>
    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
        <h3 class="text-sm font-bold text-slate-900">Select Class</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Department</label>
                <?= Html::dropDownList('department_id', $initialDepartmentId ?: '', ['' => 'Select Department'] + $departmentOptions, [
                    'id' => 'tt-department',
                    'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm',
                ]) ?>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Grade</label>
                <select id="tt-grade" class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm" disabled>
                    <option value="">Select Grade</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Section</label>
                <select id="tt-section" class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm" disabled>
                    <option value="">Select Section</option>
                </select>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <div id="tt-workspace" class="<?= $isStudentView && $initialSectionId > 0 ? 'space-y-6' : 'hidden space-y-6' ?>">
        <?php if ($canManage): ?>
        <section id="tt-customize-grid" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <button type="button" id="tt-customize-grid-toggle" class="tt-collapse-toggle h-9 w-9 shrink-0 rounded-lg border border-slate-200 bg-white text-slate-600 cursor-pointer flex items-center justify-center" aria-expanded="true" aria-controls="tt-customize-grid-body" title="Collapse customize grid">
                        <span class="material-symbols-outlined text-xl">expand_less</span>
                    </button>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900">Customize Grid</h3>
                        <p class="text-xs text-slate-500 mt-1">Set which days appear as rows and which hours appear as columns.</p>
                    </div>
                </div>
                <button type="button" id="tt-save-grid" class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-[#642754] hover:bg-[#4a2a51] text-white text-sm font-bold border-0 cursor-pointer">
                    <span class="material-symbols-outlined text-base">save</span>
                    Save Layout
                </button>
            </div>

            <div id="tt-customize-grid-body" class="tt-collapse-body space-y-5">
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">Week Days (Rows)</h4>
                        <button type="button" id="tt-add-day" class="h-8 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold cursor-pointer">Add day</button>
                    </div>
                    <p class="text-xs text-slate-400">Day labels fill automatically from the dropdown. You can add up to 7 week days, each day only once.</p>
                    <div id="tt-day-editor" class="space-y-2"></div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">Hours (Columns)</h4>
                        <button type="button" id="tt-add-column" class="h-8 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold cursor-pointer">Add time slot</button>
                    </div>
                    <div id="tt-column-editor" class="space-y-2"></div>
                    <div id="tt-column-time-error" class="hidden p-3 rounded-lg bg-red-50 text-red-800 border border-red-200 text-sm font-medium" role="alert"></div>
                    <p class="text-xs text-slate-400">Time slots cannot overlap. Each period must end before the next one begins.</p>
                </div>
            </div>
            </div>
        </section>
        <?php endif; ?>

        <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Weekly Timetable</h3>
                        <p class="text-xs text-slate-500 mt-1" id="tt-grid-subtitle"><?= $isStudentView ? 'Your weekly class schedule.' : 'Select a section to view the schedule.' ?></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="tt-print"
                                class="hidden inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-slate-200 bg-white text-slate-700 text-xs font-semibold cursor-pointer hover:bg-slate-50">
                            <span class="material-symbols-outlined text-base">print</span>
                            Print
                        </button>
                        <span id="tt-section-label" class="text-xs font-semibold px-3 py-1.5 rounded-full bg-[#642754]/10 text-[#642754]"></span>
                    </div>
                </div>
            </div>

            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">Courses</h4>
                        <p class="text-xs text-slate-500" id="tt-courses-hint">
                            <?= $canManage ? 'Drag courses into the timetable, or click + on an empty slot to choose a course.' : 'Subjects assigned to this section.' ?>
                        </p>
                    </div>
                    <?php if ($canManage): ?>
                        <button type="button" id="tt-clear-courses"
                                class="hidden inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-red-200 bg-white text-red-600 text-xs font-semibold cursor-pointer hover:bg-red-50">
                            <span class="material-symbols-outlined text-base">delete_sweep</span>
                            Clear all courses
                        </button>
                    <?php endif; ?>
                </div>
                <div id="tt-courses" class="flex flex-wrap gap-3 min-h-[3rem]"></div>
            </div>

            <div class="overflow-x-auto p-5">
                <div id="tt-empty" class="py-16 text-center text-slate-400 text-sm"><?= $isStudentView ? 'Your schedule is not available yet.' : 'Select department, grade, and section to load the timetable.' ?></div>
                <table id="tt-grid" class="hidden w-full text-sm border-collapse min-w-[48rem]">
                    <thead id="tt-grid-head"></thead>
                    <tbody id="tt-grid-body"></tbody>
                </table>
            </div>
        </section>

        <div id="tt-course-modal" class="tt-picker-modal hidden" aria-hidden="true">
            <div id="tt-course-modal-backdrop" class="tt-picker-modal-backdrop"></div>
            <div class="tt-picker-modal-panel" role="dialog" aria-modal="true" aria-labelledby="tt-course-modal-title">
                <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
                    <div>
                        <h4 id="tt-course-modal-title" class="text-sm font-bold text-slate-900">Add course</h4>
                        <p class="text-xs text-slate-500 mt-1" id="tt-course-modal-subtitle">Choose a course for this period.</p>
                    </div>
                    <button type="button" id="tt-course-modal-close" class="h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-500 cursor-pointer flex items-center justify-center" aria-label="Close">
                        <span class="material-symbols-outlined text-base">close</span>
                    </button>
                </div>
                <div id="tt-course-list" class="overflow-y-auto p-3 space-y-2"></div>
            </div>
        </div>
    </div>

    <div id="tt-lesson-modal" class="tt-lesson-modal hidden" aria-hidden="true">
        <div id="tt-lesson-modal-backdrop" class="tt-lesson-modal-backdrop"></div>
        <div class="tt-lesson-modal-panel" role="dialog" aria-modal="true" aria-labelledby="tt-lesson-modal-title">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div>
                    <h4 id="tt-lesson-modal-title" class="text-sm font-bold text-slate-900">Lesson note</h4>
                    <p class="text-xs text-slate-500 mt-1" id="tt-lesson-modal-subtitle"></p>
                </div>
                <button type="button" id="tt-lesson-modal-close" class="h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-500 cursor-pointer flex items-center justify-center" aria-label="Close">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </div>
            <p class="text-xs text-slate-500 mb-3">Add a short note about what you will teach in this period (max 15 words / 100 characters).</p>
            <textarea id="tt-lesson-brief-input" class="w-full min-h-[6rem] rounded-lg border border-slate-200 px-3 py-2 text-sm" maxlength="100" placeholder="e.g. Intro to chemical reactions"></textarea>
            <div class="flex items-center justify-between text-xs text-slate-400 mt-2">
                <span id="tt-lesson-word-count">0 / 15 words</span>
                <span id="tt-lesson-char-count">0 / 100</span>
            </div>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button type="button" id="tt-lesson-modal-cancel" class="h-9 px-3 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-semibold cursor-pointer">Cancel</button>
                <button type="button" id="tt-lesson-modal-save" class="h-9 px-4 rounded-lg bg-[#642754] hover:bg-[#4a2a51] text-white text-sm font-bold border-0 cursor-pointer">Save note</button>
            </div>
        </div>
    </div>

    <div id="tt-print-root" aria-hidden="true"></div>
<?= $this->render('../layouts/_page-end') ?>

<?php
$configJson = Json::encode([
    'gradesUrl' => $gradesUrl,
    'sectionsUrl' => $sectionsUrl,
    'loadUrl' => $loadUrl,
    'saveGridUrl' => $saveGridUrl,
    'saveSlotUrl' => $saveSlotUrl,
    'clearSlotsUrl' => $clearSlotsUrl,
    'courseOptionsUrl' => $courseOptionsUrl,
    'saveLessonBriefUrl' => $saveLessonBriefUrl,
    'csrfParam' => $csrfParam,
    'csrfToken' => $csrfToken,
    'canManage' => $canManage,
    'canEditLessonBrief' => $canEditLessonBrief,
    'currentEmployeeId' => $currentEmployeeId,
    'dayNames' => $dayNames,
    'gradesByDepartment' => $gradesByDepartment ?? [],
    'sectionsByGrade' => $sectionsByGrade ?? [],
    'initialDepartmentId' => $initialDepartmentId,
    'initialGradeId' => $initialGradeId,
    'initialSectionId' => $initialSectionId,
    'isStudentView' => $isStudentView,
    'academicYear' => $academicYear,
]);

$this->registerJs(<<<JS
(function() {
  var config = {$configJson};
  var dayNames = config.dayNames || {};
  var gradesByDepartment = config.gradesByDepartment || {};
  var sectionsByGrade = config.sectionsByGrade || {};
  var state = {
    sectionId: 0,
    canManage: !!config.canManage,
    rows: [],
    columns: [],
    slots: {},
    materials: [],
    draggedMaterialId: null,
    sectionMeta: null
  };

  var departmentSelect = document.getElementById('tt-department');
  var gradeSelect = document.getElementById('tt-grade');
  var sectionSelect = document.getElementById('tt-section');
  var workspace = document.getElementById('tt-workspace');
  var coursesEl = document.getElementById('tt-courses');
  var grid = document.getElementById('tt-grid');
  var gridHead = document.getElementById('tt-grid-head');
  var gridBody = document.getElementById('tt-grid-body');
  var emptyEl = document.getElementById('tt-empty');
  var flashEl = document.getElementById('tt-flash');
  var sectionLabel = document.getElementById('tt-section-label');
  var dayEditor = document.getElementById('tt-day-editor');
  var columnEditor = document.getElementById('tt-column-editor');
  var columnTimeErrorEl = document.getElementById('tt-column-time-error');
  var customizeGridToggle = document.getElementById('tt-customize-grid-toggle');
  var customizeGridBody = document.getElementById('tt-customize-grid-body');
  var courseModal = document.getElementById('tt-course-modal');
  var courseModalBackdrop = document.getElementById('tt-course-modal-backdrop');
  var courseModalClose = document.getElementById('tt-course-modal-close');
  var courseModalSubtitle = document.getElementById('tt-course-modal-subtitle');
  var courseListEl = document.getElementById('tt-course-list');
  var clearCoursesBtn = document.getElementById('tt-clear-courses');
  var printBtn = document.getElementById('tt-print');
  var lessonModal = document.getElementById('tt-lesson-modal');
  var lessonModalBackdrop = document.getElementById('tt-lesson-modal-backdrop');
  var lessonModalClose = document.getElementById('tt-lesson-modal-close');
  var lessonModalCancel = document.getElementById('tt-lesson-modal-cancel');
  var lessonModalSave = document.getElementById('tt-lesson-modal-save');
  var lessonBriefInput = document.getElementById('tt-lesson-brief-input');
  var lessonModalSubtitle = document.getElementById('tt-lesson-modal-subtitle');
  var lessonWordCountEl = document.getElementById('tt-lesson-word-count');
  var lessonCharCountEl = document.getElementById('tt-lesson-char-count');
  var activeLessonSlotKey = '';
  var lessonBriefMaxWords = 15;
  var lessonBriefMaxChars = 100;
  var pendingSlotRowId = null;
  var pendingSlotColumnId = null;
  var MAX_WEEK_DAYS = 7;
  var WEEK_DAY_ORDER = [7, 1, 2, 3, 4, 5, 6];

  function getUsedWeekDays(excludeIndex) {
    var used = {};
    state.rows.forEach(function(row, index) {
      if (excludeIndex !== undefined && index === excludeIndex) {
        return;
      }
      used[String(row.day_of_week)] = true;
    });
    return used;
  }

  function hasUnusedWeekDay() {
    var used = getUsedWeekDays();
    for (var i = 0; i < WEEK_DAY_ORDER.length; i++) {
      if (!used[String(WEEK_DAY_ORDER[i])]) {
        return true;
      }
    }
    return false;
  }

  function findNextUnusedWeekDay() {
    var used = getUsedWeekDays();
    for (var i = 0; i < WEEK_DAY_ORDER.length; i++) {
      var day = WEEK_DAY_ORDER[i];
      if (!used[String(day)]) {
        return day;
      }
    }
    return null;
  }

  function parseTimeToMinutes(time) {
    if (!time) return null;
    var parts = String(time).split(':');
    if (parts.length < 2) return null;
    var hours = parseInt(parts[0], 10);
    var minutes = parseInt(parts[1], 10);
    if (isNaN(hours) || isNaN(minutes)) return null;
    return hours * 60 + minutes;
  }

  function formatMinutesToTime(totalMinutes) {
    var clamped = Math.max(0, Math.min(totalMinutes, (24 * 60) - 1));
    var hours = Math.floor(clamped / 60);
    var mins = clamped % 60;
    return String(hours).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
  }

  function defaultColumnDurationMinutes(columns) {
    var defaultDuration = 60;
    if (!columns || !columns.length) {
      return defaultDuration;
    }
    var last = columns[columns.length - 1];
    var start = parseTimeToMinutes(last.start_time);
    var end = parseTimeToMinutes(last.end_time);
    if (start !== null && end !== null && end > start) {
      return end - start;
    }
    return defaultDuration;
  }

  function nextColumnTimeRange(columns) {
    var duration = defaultColumnDurationMinutes(columns);
    var defaultStart = 8 * 60;
    if (!columns || !columns.length) {
      return {
        start_time: formatMinutesToTime(defaultStart),
        end_time: formatMinutesToTime(defaultStart + duration)
      };
    }
    var last = columns[columns.length - 1];
    var lastEnd = parseTimeToMinutes(last.end_time);
    if (lastEnd !== null) {
      var start = lastEnd;
      var end = Math.min(start + duration, 24 * 60);
      if (end <= start) {
        end = Math.min(start + 60, 24 * 60);
      }
      return {
        start_time: formatMinutesToTime(start),
        end_time: formatMinutesToTime(end)
      };
    }
    var lastStart = parseTimeToMinutes(last.start_time);
    if (lastStart !== null) {
      var fallbackStart = lastStart + duration;
      return {
        start_time: formatMinutesToTime(fallbackStart),
        end_time: formatMinutesToTime(fallbackStart + duration)
      };
    }
    return {
      start_time: formatMinutesToTime(defaultStart),
      end_time: formatMinutesToTime(defaultStart + duration)
    };
  }

  function formatTimeDisplay(time) {
    var totalMinutes = parseTimeToMinutes(time);
    if (totalMinutes === null) return time || '';
    var hours24 = Math.floor(totalMinutes / 60);
    var mins = totalMinutes % 60;
    var period = hours24 >= 12 ? 'PM' : 'AM';
    var hours12 = hours24 % 12;
    if (hours12 === 0) hours12 = 12;
    return hours12 + ':' + String(mins).padStart(2, '0') + ' ' + period;
  }

  function analyzeColumnTimeSlots(columns) {
    var intervals = [];
    for (var i = 0; i < columns.length; i++) {
      var column = columns[i];
      var start = parseTimeToMinutes(column.start_time);
      var end = parseTimeToMinutes(column.end_time);
      if (start === null || end === null || start >= end) {
        return {
          error: 'Each time slot needs a valid start and end time.',
          conflictIndices: [i]
        };
      }
      var label = column.label && String(column.label).trim() !== ''
        ? String(column.label).trim()
        : defaultColumnLabel(i);
      intervals.push({
        index: i,
        label: label,
        start: start,
        end: end,
        startTime: column.start_time,
        endTime: column.end_time
      });
    }

    var conflictIndices = {};
    var firstError = null;
    for (var leftIndex = 0; leftIndex < intervals.length; leftIndex++) {
      for (var rightIndex = leftIndex + 1; rightIndex < intervals.length; rightIndex++) {
        var left = intervals[leftIndex];
        var right = intervals[rightIndex];
        if (left.start < right.end && right.start < left.end) {
          conflictIndices[left.index] = true;
          conflictIndices[right.index] = true;
          if (!firstError) {
            firstError = left.label + ' (' + formatTimeDisplay(left.startTime) + '–' + formatTimeDisplay(left.endTime) + ') overlaps with ' +
              right.label + ' (' + formatTimeDisplay(right.startTime) + '–' + formatTimeDisplay(right.endTime) + '). A period must end before the next one starts.';
          }
        }
      }
    }

    return {
      error: firstError,
      conflictIndices: Object.keys(conflictIndices).map(function(index) { return parseInt(index, 10); })
    };
  }

  function validateColumnTimeSlots(columns) {
    return analyzeColumnTimeSlots(columns).error;
  }

  function updateColumnTimeValidation(options) {
    options = options || {};
    if (!columnEditor) {
      return null;
    }
    syncEditorStateFromDom();
    var analysis = analyzeColumnTimeSlots(state.columns);

    columnEditor.querySelectorAll('[data-column-index]').forEach(function(colEl) {
      colEl.classList.remove('tt-column-conflict');
    });

    if (columnTimeErrorEl) {
      if (analysis.error) {
        columnTimeErrorEl.textContent = analysis.error;
        columnTimeErrorEl.classList.remove('hidden');
        analysis.conflictIndices.forEach(function(index) {
          var colEl = columnEditor.querySelector('[data-column-index="' + index + '"]');
          if (colEl) {
            colEl.classList.add('tt-column-conflict');
          }
        });
        if (options.expandCustomizeGrid && customizeGridBody && customizeGridBody.classList.contains('is-collapsed')) {
          setCustomizeGridCollapsed(false);
        }
        if (options.scrollIntoView) {
          columnTimeErrorEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        if (options.showFlash) {
          showFlash(analysis.error, 'error');
        }
      } else {
        columnTimeErrorEl.textContent = '';
        columnTimeErrorEl.classList.add('hidden');
      }
    }

    return analysis.error;
  }

  function updateDayEditorControls() {
    var addBtn = document.getElementById('tt-add-day');
    var atMax = state.rows.length >= MAX_WEEK_DAYS || !hasUnusedWeekDay();
    if (addBtn) {
      addBtn.disabled = atMax;
      addBtn.classList.toggle('opacity-50', atMax);
      addBtn.classList.toggle('cursor-not-allowed', atMax);
      addBtn.title = atMax ? 'You can add up to 7 week days only' : '';
    }
  }

  function hasAssignedCourses() {
    return Object.keys(state.slots).length > 0;
  }

  function updatePrintButton() {
    if (!printBtn) return;
    var canPrint = state.sectionId > 0 && state.rows.length > 0 && state.columns.length > 0;
    printBtn.classList.toggle('hidden', !canPrint);
  }

  function getPrintTitle() {
    if (!state.sectionMeta) {
      return 'Timetable';
    }
    return [
      state.sectionMeta.department_name,
      state.sectionMeta.grade_name,
      state.sectionMeta.section_name
    ].filter(Boolean).join(' · ');
  }

  function columnPrintTimeRange(column) {
    if (column.time_range) {
      return column.time_range;
    }
    if (column.start_time && column.end_time) {
      return formatTimeDisplay(column.start_time) + ' – ' + formatTimeDisplay(column.end_time);
    }
    return '';
  }

  function columnPrintLabel(column) {
    if (column.is_break) return 'BREAK';
    if (column.is_exam) return 'EXAM';
    return String(column.label || '').toUpperCase();
  }

  function renderPrintSheet() {
    var printRoot = document.getElementById('tt-print-root');
    if (!printRoot) return;

    var title = getPrintTitle();
    var academicYear = config.academicYear || '';

    var headHtml = '<tr><th class="tt-print-day-head">Day</th>';
    state.columns.forEach(function(column) {
      var headClass = 'tt-print-period-head';
      if (column.is_break) headClass += ' tt-print-break-head';
      if (column.is_exam) headClass += ' tt-print-exam-head';
      headHtml += '<th class="' + headClass + '">' +
        '<div class="tt-print-period-label">' + escapeHtml(columnPrintLabel(column)) + '</div>' +
        '<div class="tt-print-period-time">' + escapeHtml(columnPrintTimeRange(column)) + '</div>' +
      '</th>';
    });
    headHtml += '</tr>';

    var bodyHtml = state.rows.map(function(row) {
      var rowHtml = '<tr><td class="tt-print-day-cell">' + escapeHtml(rowDisplayLabel(row)) + '</td>';
      state.columns.forEach(function(column) {
        if (column.is_break) {
          rowHtml += '<td class="tt-print-break-cell">BREAK</td>';
          return;
        }
        if (column.is_exam) {
          rowHtml += '<td class="tt-print-exam-cell">EXAM</td>';
          return;
        }
        var key = String(row.id) + ':' + String(column.id);
        var slot = state.slots[key];
        if (slot && slot.material_id) {
          rowHtml += '<td class="tt-print-class-cell">' +
            '<div class="tt-print-subject">' + escapeHtml(slot.subject_name || '') + '</div>' +
            (slot.teacher_name ? '<div class="tt-print-teacher">' + escapeHtml(slot.teacher_name) + '</div>' : '') +
            (slot.lesson_brief ? '<div class="tt-print-note">' + escapeHtml(slot.lesson_brief) + '</div>' : '') +
          '</td>';
        } else {
          rowHtml += '<td class="tt-print-empty-cell"></td>';
        }
      });
      rowHtml += '</tr>';
      return rowHtml;
    }).join('');

    printRoot.innerHTML =
      '<div class="tt-print-sheet">' +
        '<div class="tt-print-header">' +
          '<h1 class="tt-print-title">' + escapeHtml(title) + '</h1>' +
          (academicYear ? '<p class="tt-print-year">' + escapeHtml(academicYear) + '</p>' : '') +
        '</div>' +
        '<table class="tt-print-table">' +
          '<thead>' + headHtml + '</thead>' +
          '<tbody>' + bodyHtml + '</tbody>' +
        '</table>' +
      '</div>';
  }

  function printTimetable() {
    if (!state.rows.length || !state.columns.length) {
      showFlash('Load a timetable before printing.', 'error');
      return;
    }
    renderPrintSheet();
    window.print();
  }

  function updateClearCoursesButton() {
    if (clearCoursesBtn) {
      var visible = state.canManage && state.sectionId > 0;
      clearCoursesBtn.classList.toggle('hidden', !visible);
      clearCoursesBtn.disabled = !hasAssignedCourses();
      clearCoursesBtn.classList.toggle('opacity-50', !hasAssignedCourses());
      clearCoursesBtn.classList.toggle('cursor-not-allowed', !hasAssignedCourses());
    }
    updatePrintButton();
  }

  function setCustomizeGridCollapsed(collapsed) {
    if (!customizeGridBody || !customizeGridToggle) return;
    customizeGridBody.classList.toggle('is-collapsed', collapsed);
    customizeGridToggle.classList.toggle('is-collapsed', collapsed);
    customizeGridToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    customizeGridToggle.title = collapsed ? 'Expand customize grid' : 'Collapse customize grid';
    try {
      localStorage.setItem('tt-customize-grid-collapsed', collapsed ? '1' : '0');
    } catch (e) {}
  }

  function defaultDayLabel(day) {
    return dayNames[String(day)] || dayNames[day] || 'Day';
  }

  function rowDisplayLabel(row) {
    if (row.custom_label && String(row.custom_label).trim() !== '') {
      return String(row.custom_label).trim();
    }
    return defaultDayLabel(row.day_of_week);
  }

  function defaultColumnLabel(index) {
    return 'Period ' + (index + 1);
  }

  function isAutoColumnLabel(label) {
    var trimmed = String(label || '').trim();
    if (trimmed === '' || trimmed === 'New Period') {
      return true;
    }
    return /^Period \d+$/i.test(trimmed) || /^Time slot \d+$/i.test(trimmed);
  }

  function columnDisplayLabel(column, index) {
    if (column.custom_label && String(column.custom_label).trim() !== '') {
      return String(column.custom_label).trim();
    }
    return defaultColumnLabel(index);
  }

  function normalizeColumns(columns) {
    return (columns || []).map(function(column, index) {
      var customLabel = column.custom_label != null ? String(column.custom_label).trim() : '';
      if (!customLabel && column.label) {
        if (!isAutoColumnLabel(column.label)) {
          customLabel = String(column.label).trim();
        }
      }
      var normalized = {
        id: column.id,
        start_time: column.start_time,
        end_time: column.end_time,
        time_range: column.time_range,
        is_break: !!column.is_break,
        is_exam: !!column.is_exam,
        custom_label: customLabel
      };
      normalized.label = columnDisplayLabel(normalized, index);
      return normalized;
    });
  }

  function renumberAutoColumnLabels() {
    state.columns.forEach(function(column, index) {
      if (!column.custom_label || String(column.custom_label).trim() === '') {
        column.label = defaultColumnLabel(index);
      }
    });
  }

  function normalizeRows(rows) {
    return (rows || []).map(function(row) {
      var customLabel = row.custom_label != null ? String(row.custom_label).trim() : '';
      if (!customLabel && row.label) {
        var autoLabel = defaultDayLabel(row.day_of_week);
        if (String(row.label).trim() !== autoLabel) {
          customLabel = String(row.label).trim();
        }
      }
      var normalized = {
        id: row.id,
        day_of_week: row.day_of_week,
        custom_label: customLabel
      };
      normalized.label = rowDisplayLabel(normalized);
      return normalized;
    });
  }

  function syncEditorStateFromDom() {
    if (!state.canManage) return;
    if (dayEditor && dayEditor.querySelectorAll('[data-row-index]').length === state.rows.length) {
      dayEditor.querySelectorAll('[data-row-index]').forEach(function(rowEl, index) {
        if (!state.rows[index]) return;
        var select = rowEl.querySelector('.tt-day-select');
        var input = rowEl.querySelector('.tt-day-label');
        if (select) {
          state.rows[index].day_of_week = parseInt(select.value, 10);
        }
        if (input) {
          var autoLabel = defaultDayLabel(select ? select.value : state.rows[index].day_of_week);
          var isCustom = input.dataset.custom === '1' || input.value.trim() !== autoLabel;
          input.dataset.custom = isCustom ? '1' : '0';
          state.rows[index].custom_label = isCustom ? input.value.trim() : '';
          state.rows[index].label = rowDisplayLabel(state.rows[index]);
        }
      });
    }
    if (columnEditor && columnEditor.querySelectorAll('[data-column-index]').length === state.columns.length) {
      columnEditor.querySelectorAll('[data-column-index]').forEach(function(colEl, index) {
        if (!state.columns[index]) return;
        var start = colEl.querySelector('.tt-col-start');
        var end = colEl.querySelector('.tt-col-end');
        var label = colEl.querySelector('.tt-col-label');
        var breakCb = colEl.querySelector('.tt-col-break');
        var examCb = colEl.querySelector('.tt-col-exam');
        if (start) state.columns[index].start_time = start.value;
        if (end) state.columns[index].end_time = end.value;
        if (label) {
          var autoLabel = defaultColumnLabel(index);
          var isCustom = label.dataset.custom === '1' || label.value.trim() !== autoLabel;
          label.dataset.custom = isCustom ? '1' : '0';
          state.columns[index].custom_label = isCustom ? label.value.trim() : '';
          state.columns[index].label = columnDisplayLabel(state.columns[index], index);
        }
        if (breakCb) state.columns[index].is_break = breakCb.checked;
        if (examCb) state.columns[index].is_exam = examCb.checked;
        if (breakCb && examCb && breakCb.checked && examCb.checked) {
          state.columns[index].is_break = false;
          breakCb.checked = false;
        }
      });
    }
  }

  function buildDaySelectOptions(row, index) {
    var usedByOthers = getUsedWeekDays(index);
    return WEEK_DAY_ORDER.map(function(day) {
      var isSelected = String(row.day_of_week) === String(day);
      if (!isSelected && usedByOthers[day]) {
        return '';
      }
      return '<option value="' + day + '"' + (isSelected ? ' selected' : '') + '>' +
        escapeHtml(dayNames[day] || dayNames[String(day)] || 'Day') + '</option>';
    }).join('');
  }

  function refreshDaySelectOptions() {
    if (!dayEditor) return;
    syncEditorStateFromDom();
    dayEditor.querySelectorAll('[data-row-index]').forEach(function(rowEl) {
      var index = parseInt(rowEl.getAttribute('data-row-index'), 10);
      var select = rowEl.querySelector('.tt-day-select');
      if (!select || !state.rows[index]) return;
      var previousValue = String(state.rows[index].day_of_week);
      select.innerHTML = buildDaySelectOptions(state.rows[index], index);
      select.value = previousValue;
      select.dataset.previousValue = previousValue;
    });
    updateDayEditorControls();
  }

  function bindDayRows() {
    if (!dayEditor) return;
    dayEditor.querySelectorAll('[data-row-index]').forEach(function(rowEl) {
      var index = parseInt(rowEl.getAttribute('data-row-index'), 10);
      var select = rowEl.querySelector('.tt-day-select');
      var input = rowEl.querySelector('.tt-day-label');
      if (!select || !input) return;

      select.dataset.previousValue = String(state.rows[index] ? state.rows[index].day_of_week : select.value);

      select.addEventListener('change', function() {
        syncEditorStateFromDom();
        var day = parseInt(select.value, 10);
        var usedByOther = Object.prototype.hasOwnProperty.call(getUsedWeekDays(index), String(day));
        if (usedByOther) {
          showFlash('Each week day can only be added once.', 'error');
          select.value = select.dataset.previousValue || String(state.rows[index] ? state.rows[index].day_of_week : '1');
          return;
        }
        select.dataset.previousValue = String(day);
        if (state.rows[index]) {
          state.rows[index].day_of_week = day;
        }
        if (input.dataset.custom !== '1') {
          input.value = defaultDayLabel(day);
          if (state.rows[index]) {
            state.rows[index].custom_label = '';
            state.rows[index].label = input.value;
          }
        }
        refreshDaySelectOptions();
        renderGrid();
      });

      input.addEventListener('input', function() {
        var autoLabel = defaultDayLabel(select.value);
        var isCustom = input.value.trim() !== autoLabel;
        input.dataset.custom = isCustom ? '1' : '0';
        if (state.rows[index]) {
          state.rows[index].custom_label = isCustom ? input.value.trim() : '';
          state.rows[index].label = rowDisplayLabel(state.rows[index]);
        }
      });
    });
  }

  function appendQuery(url, params) {
    var parts = [];
    Object.keys(params).forEach(function(key) {
      if (params[key] !== undefined && params[key] !== null && params[key] !== '') {
        parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
      }
    });
    if (parts.length === 0) {
      return url;
    }
    return url + (url.indexOf('?') === -1 ? '?' : '&') + parts.join('&');
  }

  function showFlash(message, type) {
    if (!flashEl) return;
    flashEl.textContent = message;
    flashEl.className = 'p-4 rounded-xl text-sm font-medium ' + (type === 'error'
      ? 'bg-red-50 text-red-800 border border-red-200'
      : 'bg-emerald-50 text-emerald-800 border border-emerald-200');
    flashEl.classList.remove('hidden');
    if (type === 'error' && flashEl.scrollIntoView) {
      flashEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    window.setTimeout(function() { flashEl.classList.add('hidden'); }, type === 'error' ? 10000 : 5000);
  }

  function postForm(url, data) {
    var body = new URLSearchParams();
    body.append(config.csrfParam, config.csrfToken);
    Object.keys(data).forEach(function(key) {
      var value = data[key];
      if (Array.isArray(value)) {
        value.forEach(function(item, index) {
          if (typeof item === 'object' && item !== null) {
            Object.keys(item).forEach(function(subKey) {
              body.append(key + '[' + index + '][' + subKey + ']', item[subKey]);
            });
          } else {
            body.append(key + '[]', item);
          }
        });
      } else if (value !== undefined && value !== null) {
        body.append(key, value);
      }
    });
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: body.toString()
    }).then(function(response) { return response.json(); });
  }

  function setOptions(select, items, placeholder, selectedId, enabled) {
    if (!select) return;
    select.innerHTML = '<option value="">' + placeholder + '</option>';
    items.forEach(function(item) {
      var option = document.createElement('option');
      option.value = String(item.id);
      option.textContent = item.label || item.name;
      if (selectedId && String(item.id) === String(selectedId)) {
        option.selected = true;
      }
      select.appendChild(option);
    });
    select.disabled = enabled === false;
  }

  function applyGrades(departmentId, grades, selectedGradeId) {
    if (!selectedGradeId && grades.length === 1) {
      selectedGradeId = grades[0].id;
    }
    setOptions(gradeSelect, grades, 'Select Grade', selectedGradeId || '', true);
    if (selectedGradeId) {
      return loadSections(selectedGradeId, config.initialSectionId || '');
    }
    setOptions(sectionSelect, [], 'Select Section', '', false);
    if (grades.length === 0) {
      showFlash('No grades found for this department. Add grades in Sections & Hierarchy first.', 'error');
    }
    return Promise.resolve();
  }

  function loadGrades(departmentId, selectedGradeId) {
    if (!departmentId) {
      setOptions(gradeSelect, [], 'Select Grade', '', false);
      setOptions(sectionSelect, [], 'Select Section', '', false);
      return Promise.resolve();
    }

    var embedded = gradesByDepartment[String(departmentId)] || gradesByDepartment[departmentId] || [];
    if (embedded.length > 0) {
      return applyGrades(departmentId, embedded, selectedGradeId || '');
    }

    return fetch(appendQuery(config.gradesUrl, { department_id: departmentId }), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function(response) {
        if (!response.ok) {
          throw new Error('grades request failed');
        }
        return response.json();
      })
      .then(function(data) {
        var grades = Array.isArray(data) ? data : [];
        gradesByDepartment[String(departmentId)] = grades;
        return applyGrades(departmentId, grades, selectedGradeId || '');
      })
      .catch(function() {
        setOptions(gradeSelect, [], 'Select Grade', '', true);
        showFlash('Unable to load grades for this department.', 'error');
      });
  }

  function applySections(gradeId, sections, selectedSectionId) {
    if (!selectedSectionId && sections.length === 1) {
      selectedSectionId = sections[0].id;
    }
    setOptions(sectionSelect, sections, 'Select Section', selectedSectionId || '', true);
    if (selectedSectionId) {
      loadTimetable(selectedSectionId);
      return;
    }
    if (sections.length === 0) {
      showFlash('No sections found for this grade. Add sections in Sections & Hierarchy first.', 'error');
    }
  }

  function loadSections(gradeId, selectedSectionId) {
    if (!gradeId) {
      setOptions(sectionSelect, [], 'Select Section', '', false);
      return Promise.resolve();
    }

    var embedded = sectionsByGrade[String(gradeId)] || sectionsByGrade[gradeId] || [];
    if (embedded.length > 0) {
      applySections(gradeId, embedded, selectedSectionId || '');
      return Promise.resolve();
    }

    return fetch(appendQuery(config.sectionsUrl, { grade_id: gradeId }), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function(response) {
        if (!response.ok) {
          throw new Error('sections request failed');
        }
        return response.json();
      })
      .then(function(data) {
        var sections = Array.isArray(data) ? data : [];
        sectionsByGrade[String(gradeId)] = sections;
        applySections(gradeId, sections, selectedSectionId || '');
      })
      .catch(function() {
        setOptions(sectionSelect, [], 'Select Section', '', true);
        showFlash('Unable to load sections for this grade.', 'error');
      });
  }

  function renderCourses() {
    if (!coursesEl) return;
    if (!state.materials.length) {
      coursesEl.innerHTML = '<p class="text-sm text-slate-400 italic">No subjects assigned to this section yet.</p>';
      return;
    }
    coursesEl.innerHTML = state.materials.map(function(material) {
      var icon = state.canManage ? 'drag_indicator' : 'menu_book';
      var cardClass = 'rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 min-w-[12rem]' + (state.canManage ? ' tt-course-card' : '');
      return '<div class="' + cardClass + '" draggable="' + (state.canManage ? 'true' : 'false') + '" data-material-id="' + material.id + '">' +
        '<div class="flex items-start gap-3">' +
          '<span class="material-symbols-outlined text-slate-400">' + icon + '</span>' +
          '<div class="min-w-0 flex-1">' +
            '<p class="font-bold text-slate-900">' + escapeHtml(material.subject_name || 'Subject') + '</p>' +
            '<p class="text-xs text-slate-500 mt-1">' + escapeHtml(material.teacher_name || 'No teacher assigned') + '</p>' +
          '</div>' +
        '</div>' +
      '</div>';
    }).join('');

    coursesEl.querySelectorAll('.tt-course-card').forEach(function(card) {
      if (!state.canManage) {
        return;
      }
      card.addEventListener('dragstart', function(event) {
        state.draggedMaterialId = card.getAttribute('data-material-id');
        event.dataTransfer.setData('text/plain', state.draggedMaterialId || '');
      });
      card.addEventListener('dragend', function() {
        state.draggedMaterialId = null;
      });
    });
  }

  function getSlotPeriodLabel(rowId, columnId) {
    var row = state.rows.find(function(r) { return String(r.id) === String(rowId); });
    var column = state.columns.find(function(c) { return String(c.id) === String(columnId); });
    if (!row || !column) return 'this period';
    var periodLabel = column.label || column.time_range || 'Period';
    var timeRange = column.time_range || '';
    if (!timeRange && column.start_time && column.end_time) {
      timeRange = column.start_time + ' – ' + column.end_time;
    }
    return rowDisplayLabel(row) + ' · ' + periodLabel + (timeRange ? ' (' + timeRange + ')' : '');
  }

  function renderCoursePickerOptions(courses) {
    if (!courseListEl) return;
    if (!courses.length) {
      courseListEl.innerHTML = '<p class="px-2 py-6 text-sm text-slate-400 text-center">No subjects assigned to this section yet.</p>';
      return;
    }

    courseListEl.innerHTML = courses.map(function(course) {
      var unavailable = !course.available;
      var status = unavailable
        ? '<span class="text-xs text-red-600 font-semibold">' + escapeHtml(course.status_label || 'Unavailable') + '</span>'
        : '<span class="text-xs text-emerald-600 font-semibold">Available</span>';
      var reason = course.unavailable_reason ? ' title="' + escapeHtml(course.unavailable_reason) + '"' : '';
      return '<button type="button" class="tt-picker-option w-full text-left rounded-lg border border-slate-200 bg-white px-4 py-3 disabled:opacity-50 disabled:cursor-not-allowed"' +
        ' data-material-id="' + course.id + '"' +
        reason +
        (unavailable ? ' disabled' : '') + '>' +
        '<div class="flex items-start justify-between gap-3">' +
          '<div>' +
            '<p class="font-semibold text-slate-900">' + escapeHtml(course.subject_name || 'Subject') + '</p>' +
            '<p class="text-xs text-slate-500 mt-1">' + escapeHtml(course.teacher_name || 'No teacher assigned') + '</p>' +
            (unavailable && course.unavailable_reason
              ? '<p class="text-xs text-red-600 mt-2">' + escapeHtml(course.unavailable_reason) + '</p>'
              : '') +
          '</div>' +
          status +
        '</div>' +
      '</button>';
    }).join('');

    courseListEl.querySelectorAll('.tt-picker-option:not(:disabled)').forEach(function(button) {
      button.addEventListener('click', function() {
        if (!pendingSlotRowId || !pendingSlotColumnId) return;
        saveSlot(pendingSlotRowId, pendingSlotColumnId, button.getAttribute('data-material-id'))
          .then(function(success) {
            if (success) closeCoursePicker();
          });
      });
    });
  }

  function fetchCourseOptionsForCell(rowId, columnId) {
    return fetch(appendQuery(config.courseOptionsUrl, {
      section_id: state.sectionId,
      row_id: rowId,
      column_id: columnId
    }), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function(response) {
        if (!response.ok) {
          throw new Error('course options request failed');
        }
        return response.json();
      })
      .then(function(data) {
        if (!data.success) {
          throw new Error((data.errors || []).join(' ') || 'Unable to validate this period.');
        }
        return data.courses || [];
      });
  }

  function validateMaterialForCell(rowId, columnId, materialId) {
    if (!materialId) {
      return Promise.resolve(true);
    }
    return fetchCourseOptionsForCell(rowId, columnId)
      .then(function(courses) {
        var course = courses.find(function(item) {
          return String(item.id) === String(materialId);
        });
        if (!course) {
          showFlash('Selected subject is not part of this section.', 'error');
          return false;
        }
        if (!course.available) {
          showFlash(
            course.unavailable_reason || 'This teacher is already teaching another class at this time.',
            'error'
          );
          return false;
        }
        return true;
      })
      .catch(function(error) {
        showFlash(error && error.message ? error.message : 'Unable to validate teacher availability.', 'error');
        return false;
      });
  }

  function closeCoursePicker() {
    pendingSlotRowId = null;
    pendingSlotColumnId = null;
    if (!courseModal) return;
    courseModal.classList.add('hidden');
    courseModal.setAttribute('aria-hidden', 'true');
    if (courseListEl) {
      courseListEl.innerHTML = '';
    }
  }

  function openCoursePicker(rowId, columnId) {
    if (!state.canManage || !rowId || !columnId || !courseModal || !courseListEl) return;
    pendingSlotRowId = rowId;
    pendingSlotColumnId = columnId;
    if (courseModalSubtitle) {
      courseModalSubtitle.textContent = 'Choose a course for ' + getSlotPeriodLabel(rowId, columnId) + '.';
    }

    courseListEl.innerHTML = '<p class="px-2 py-6 text-sm text-slate-400 text-center">Loading courses...</p>';
    courseModal.classList.remove('hidden');
    courseModal.setAttribute('aria-hidden', 'false');

    fetch(appendQuery(config.courseOptionsUrl, {
      section_id: state.sectionId,
      row_id: rowId,
      column_id: columnId
    }), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function(response) {
        if (!response.ok) {
          throw new Error('course options request failed');
        }
        return response.json();
      })
      .then(function(data) {
        if (!data.success) {
          throw new Error((data.errors || []).join(' ') || 'Unable to load courses.');
        }
        renderCoursePickerOptions(data.courses || []);
      })
      .catch(function(error) {
        courseListEl.innerHTML = '<p class="px-2 py-6 text-sm text-red-600 text-center">' +
          escapeHtml(error && error.message ? error.message : 'Unable to load courses for this period.') +
        '</p>';
      });
  }

  function renderEditors() {
    if (!state.canManage || !dayEditor || !columnEditor) return;

    dayEditor.innerHTML = state.rows.map(function(row, index) {
      var options = buildDaySelectOptions(row, index);
      var displayLabel = rowDisplayLabel(row);
      var isCustom = row.custom_label && String(row.custom_label).trim() !== '';
      return '<div class="flex items-center gap-2 rounded-lg border border-slate-200 p-3 bg-slate-50" data-row-index="' + index + '">' +
        '<select class="tt-day-select h-9 px-2 rounded border border-slate-200 text-sm flex-1">' + options + '</select>' +
        '<input type="text" class="tt-day-label h-9 px-2 rounded border border-slate-200 text-sm flex-1" placeholder="Day label" value="' + escapeHtml(displayLabel) + '" data-custom="' + (isCustom ? '1' : '0') + '">' +
        '<button type="button" class="tt-remove-day h-9 px-2 rounded border border-red-200 text-red-600 text-xs font-semibold cursor-pointer">Remove</button>' +
      '</div>';
    }).join('');

    columnEditor.innerHTML = state.columns.map(function(column, index) {
      var displayLabel = columnDisplayLabel(column, index);
      var isCustom = column.custom_label && String(column.custom_label).trim() !== '';
      return '<div class="rounded-lg border border-slate-200 p-3 bg-slate-50 space-y-2" data-column-index="' + index + '">' +
        '<div class="grid grid-cols-2 gap-2">' +
          '<input type="time" class="tt-col-start h-9 px-2 rounded border border-slate-200 text-sm" value="' + escapeHtml(column.start_time || '') + '">' +
          '<input type="time" class="tt-col-end h-9 px-2 rounded border border-slate-200 text-sm" value="' + escapeHtml(column.end_time || '') + '">' +
        '</div>' +
        '<div class="flex items-center gap-2">' +
          '<input type="text" class="tt-col-label h-9 px-2 rounded border border-slate-200 text-sm flex-1" placeholder="Label" value="' + escapeHtml(displayLabel) + '" data-custom="' + (isCustom ? '1' : '0') + '">' +
          '<label class="inline-flex items-center gap-1 text-xs text-slate-600"><input type="checkbox" class="tt-col-break"' + (column.is_break ? ' checked' : '') + '> Break</label>' +
          '<label class="inline-flex items-center gap-1 text-xs text-slate-600"><input type="checkbox" class="tt-col-exam"' + (column.is_exam ? ' checked' : '') + '> Exam</label>' +
          '<button type="button" class="tt-remove-column h-9 px-2 rounded border border-red-200 text-red-600 text-xs font-semibold cursor-pointer">Remove</button>' +
        '</div>' +
      '</div>';
    }).join('');

    dayEditor.querySelectorAll('.tt-remove-day').forEach(function(button) {
      button.addEventListener('click', function() {
        syncEditorStateFromDom();
        var index = parseInt(button.closest('[data-row-index]').getAttribute('data-row-index'), 10);
        state.rows.splice(index, 1);
        renderEditors();
      });
    });

    columnEditor.querySelectorAll('.tt-remove-column').forEach(function(button) {
      button.addEventListener('click', function() {
        syncEditorStateFromDom();
        var index = parseInt(button.closest('[data-column-index]').getAttribute('data-column-index'), 10);
        state.columns.splice(index, 1);
        renumberAutoColumnLabels();
        renderEditors();
      });
    });

    bindColumnRows();
    bindDayRows();
    updateDayEditorControls();
    updateColumnTimeValidation();
  }

  function bindColumnRows() {
    if (!columnEditor) return;
    columnEditor.querySelectorAll('[data-column-index]').forEach(function(colEl, index) {
      var start = colEl.querySelector('.tt-col-start');
      var end = colEl.querySelector('.tt-col-end');
      var label = colEl.querySelector('.tt-col-label');
      var breakCb = colEl.querySelector('.tt-col-break');
      var examCb = colEl.querySelector('.tt-col-exam');
      [start, end].forEach(function(input) {
        if (!input) return;
        ['input', 'change'].forEach(function(eventName) {
          input.addEventListener(eventName, function() {
            updateColumnTimeValidation({ expandCustomizeGrid: true });
          });
        });
      });
      if (label) {
        ['input', 'change'].forEach(function(eventName) {
          label.addEventListener(eventName, function() {
            syncEditorStateFromDom();
            if (label.dataset.custom !== '1' && !isAutoColumnLabel(label.value)) {
              label.dataset.custom = '1';
              if (state.columns[index]) {
                state.columns[index].custom_label = label.value.trim();
              }
            }
          });
        });
      }
      if (breakCb) {
        breakCb.addEventListener('change', function() {
          if (breakCb.checked && examCb) {
            examCb.checked = false;
          }
          syncEditorStateFromDom();
          renderGrid();
        });
      }
      if (examCb) {
        examCb.addEventListener('change', function() {
          if (examCb.checked && breakCb) {
            breakCb.checked = false;
          }
          syncEditorStateFromDom();
          renderGrid();
        });
      }
    });
  }

  function canEditSlotLessonBrief(slot) {
    if (!config.canEditLessonBrief || !config.currentEmployeeId || !slot) {
      return false;
    }
    return Number(slot.instructor_id) === Number(config.currentEmployeeId);
  }

  function findSlotKeyById(slotId) {
    var targetId = Number(slotId);
    if (!targetId) {
      return '';
    }
    for (var key in state.slots) {
      if (Object.prototype.hasOwnProperty.call(state.slots, key) && Number(state.slots[key].id) === targetId) {
        return key;
      }
    }
    return '';
  }

  function countLessonWords(text) {
    return String(text || '').trim().split(/\s+/).filter(Boolean).length;
  }

  function updateLessonBriefCounters() {
    var text = lessonBriefInput ? lessonBriefInput.value : '';
    if (lessonWordCountEl) {
      lessonWordCountEl.textContent = countLessonWords(text) + ' / ' + lessonBriefMaxWords + ' words';
    }
    if (lessonCharCountEl) {
      lessonCharCountEl.textContent = String(text).length + ' / ' + lessonBriefMaxChars;
    }
  }

  function enforceLessonBriefLimits() {
    if (!lessonBriefInput) {
      return;
    }
    var value = lessonBriefInput.value || '';
    if (value.length > lessonBriefMaxChars) {
      value = value.slice(0, lessonBriefMaxChars);
    }
    var words = value.trim().split(/\s+/).filter(Boolean);
    if (words.length > lessonBriefMaxWords) {
      value = words.slice(0, lessonBriefMaxWords).join(' ');
      if (value.length > lessonBriefMaxChars) {
        value = value.slice(0, lessonBriefMaxChars);
      }
    }
    lessonBriefInput.value = value;
    updateLessonBriefCounters();
  }

  function closeLessonBriefModal() {
    if (!lessonModal) {
      return;
    }
    lessonModal.classList.add('hidden');
    lessonModal.setAttribute('aria-hidden', 'true');
    activeLessonSlotKey = '';
    if (lessonBriefInput) {
      lessonBriefInput.value = '';
    }
    updateLessonBriefCounters();
  }

  function openLessonBriefModal(slot) {
    if (!lessonModal || !lessonBriefInput || !slot) {
      return;
    }
    activeLessonSlotKey = findSlotKeyById(slot.id);
    if (lessonModalSubtitle) {
      lessonModalSubtitle.textContent = [slot.subject_name || '', slot.teacher_name || ''].filter(Boolean).join(' · ');
    }
    lessonBriefInput.value = slot.lesson_brief || '';
    updateLessonBriefCounters();
    lessonModal.classList.remove('hidden');
    lessonModal.setAttribute('aria-hidden', 'false');
    lessonBriefInput.focus();
  }

  function saveLessonBrief() {
    if (!activeLessonSlotKey || !lessonModalSave) {
      return;
    }
    var slot = state.slots[activeLessonSlotKey];
    if (!slot || !slot.id) {
      return;
    }
    var text = (lessonBriefInput ? lessonBriefInput.value : '').trim();
    if (text.length > lessonBriefMaxChars) {
      showFlash('Lesson note is too long (max 100 characters).', 'error');
      return;
    }
    if (countLessonWords(text) > lessonBriefMaxWords) {
      showFlash('Lesson note is too long (max 15 words).', 'error');
      return;
    }
    lessonModalSave.disabled = true;
    postForm(config.saveLessonBriefUrl, {
      slot_id: slot.id,
      lesson_brief: text
    }).then(function(response) {
      if (!response.success) {
        showFlash((response.errors || []).join(' '), 'error');
        return;
      }
      slot.lesson_brief = response.slot && response.slot.lesson_brief ? response.slot.lesson_brief : '';
      state.slots[activeLessonSlotKey] = slot;
      renderGrid();
      showFlash(response.message || 'Lesson note saved.', 'success');
      closeLessonBriefModal();
    }).catch(function() {
      showFlash('Unable to save lesson note.', 'error');
    }).finally(function() {
      lessonModalSave.disabled = false;
    });
  }

  function renderSlotNoteHtml(slot) {
    if (!slot) {
      return '';
    }
    var editable = canEditSlotLessonBrief(slot);
    var text = String(slot.lesson_brief || '').trim();
    if (!text && !editable) {
      return '';
    }
    var className = 'tt-slot-note';
    if (!text && editable) {
      className += ' tt-slot-note--empty';
    }
    if (editable) {
      className += ' tt-slot-note--editable cursor-pointer';
    }
    var label = text || (editable ? 'Add lesson note…' : '');
    return '<p class="' + className + '" data-slot-id="' + slot.id + '">' + escapeHtml(label) + '</p>';
  }

  function renderGrid() {
    if (!state.rows.length || !state.columns.length) {
      grid.classList.add('hidden');
      emptyEl.classList.remove('hidden');
      emptyEl.textContent = 'Add at least one day and one time slot.';
      updatePrintButton();
      return;
    }

    emptyEl.classList.add('hidden');
    grid.classList.remove('hidden');

    var headHtml = '<tr><th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50/80 w-36">Day</th>';
    state.columns.forEach(function(column) {
      headHtml += '<th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50/80">' +
        '<div>' + escapeHtml(column.label || column.time_range || '') + '</div>' +
        '<div class="font-normal normal-case tracking-normal text-slate-500 mt-1">' + escapeHtml(column.time_range || '') + '</div>' +
      '</th>';
    });
    headHtml += '</tr>';
    gridHead.innerHTML = headHtml;

    gridBody.innerHTML = state.rows.map(function(row) {
      var rowHtml = '<tr><td class="px-3 py-3 align-top border-b border-slate-100 font-semibold text-slate-800 bg-slate-50/50">' + escapeHtml(rowDisplayLabel(row)) + '</td>';
      state.columns.forEach(function(column) {
        var key = String(row.id) + ':' + String(column.id);
        var slot = state.slots[key];
        if (column.is_break) {
          rowHtml += '<td class="px-3 py-3 align-top border-b border-slate-100 tt-break-cell tt-grid-cell text-center">Break</td>';
          return;
        }
        if (column.is_exam) {
          rowHtml += '<td class="px-3 py-3 align-top border-b border-slate-100 tt-exam-cell tt-grid-cell text-center">Exam</td>';
          return;
        }
        if (slot && slot.material_id) {
          rowHtml += '<td class="px-3 py-3 align-top border-b border-slate-100 tt-grid-cell">' +
            '<div class="tt-slot-card rounded-lg bg-white border border-slate-200 p-3 shadow-sm">' +
              '<p class="font-bold text-slate-900">' + escapeHtml(slot.subject_name || '') + '</p>' +
              '<p class="text-xs text-slate-500 mt-1">' + escapeHtml(slot.teacher_name || '') + '</p>' +
              renderSlotNoteHtml(slot) +
              (state.canManage ? '<button type="button" class="tt-clear-slot mt-2 text-xs text-red-600 font-semibold cursor-pointer border-0 bg-transparent p-0" data-row-id="' + row.id + '" data-column-id="' + column.id + '">Remove</button>' : '') +
            '</div>' +
          '</td>';
        } else {
          rowHtml += '<td class="px-3 py-3 align-top border-b border-slate-100 tt-grid-cell">' +
            '<div class="rounded-lg border border-dashed border-slate-200 min-h-[5.5rem] flex items-center justify-center text-slate-300 ' + (state.canManage ? 'tt-drop-zone' : '') + '" data-row-id="' + row.id + '" data-column-id="' + column.id + '">' +
              (state.canManage ? '<span class="material-symbols-outlined">add</span>' : '<span class="text-xs text-slate-400">—</span>') +
            '</div>' +
          '</td>';
        }
      });
      rowHtml += '</tr>';
      return rowHtml;
    }).join('');

    gridBody.querySelectorAll('.tt-drop-zone').forEach(function(zone) {
      zone.addEventListener('click', function() {
        openCoursePicker(zone.getAttribute('data-row-id'), zone.getAttribute('data-column-id'));
      });
      zone.addEventListener('dragover', function(event) {
        event.preventDefault();
        zone.classList.add('drag-over');
      });
      zone.addEventListener('dragleave', function() {
        zone.classList.remove('drag-over');
      });
      zone.addEventListener('drop', function(event) {
        event.preventDefault();
        zone.classList.remove('drag-over');
        var materialId = state.draggedMaterialId || event.dataTransfer.getData('text/plain');
        if (!materialId) return;
        var rowId = zone.getAttribute('data-row-id');
        var columnId = zone.getAttribute('data-column-id');
        validateMaterialForCell(rowId, columnId, materialId).then(function(ok) {
          if (!ok) return;
          saveSlot(rowId, columnId, materialId);
        });
      });
    });

    gridBody.querySelectorAll('.tt-clear-slot').forEach(function(button) {
      button.addEventListener('click', function() {
        saveSlot(button.getAttribute('data-row-id'), button.getAttribute('data-column-id'), '');
      });
    });

    gridBody.querySelectorAll('.tt-slot-note--editable').forEach(function(noteEl) {
      noteEl.addEventListener('click', function() {
        var slotId = parseInt(noteEl.getAttribute('data-slot-id') || '0', 10);
        var slotKey = findSlotKeyById(slotId);
        if (!slotKey) {
          return;
        }
        openLessonBriefModal(state.slots[slotKey]);
      });
    });

    updatePrintButton();
  }

  function saveSlot(rowId, columnId, materialId) {
    return postForm(config.saveSlotUrl, {
      section_id: state.sectionId,
      row_id: rowId,
      column_id: columnId,
      material_id: materialId
    }).then(function(response) {
      if (!response.success) {
        showFlash((response.errors || []).join(' '), 'error');
        return false;
      }
      var key = String(rowId) + ':' + String(columnId);
      if (response.slot) {
        state.slots[key] = response.slot;
      } else {
        delete state.slots[key];
      }
      renderGrid();
      showFlash('Timetable updated.', 'success');
      updateClearCoursesButton();
      return true;
    }).catch(function() {
      showFlash('Unable to save timetable slot.', 'error');
      return false;
    });
  }

  function collectEditorPayload() {
    var rows = [];
    var columns = [];
    if (dayEditor) {
      dayEditor.querySelectorAll('[data-row-index]').forEach(function(rowEl) {
        var input = rowEl.querySelector('.tt-day-label');
        var label = '';
        if (input && input.dataset.custom === '1') {
          label = input.value.trim();
        }
        rows.push({
          day_of_week: rowEl.querySelector('.tt-day-select')?.value || '1',
          label: label
        });
      });
    }
    if (columnEditor) {
      columnEditor.querySelectorAll('[data-column-index]').forEach(function(colEl) {
        columns.push({
          start_time: colEl.querySelector('.tt-col-start')?.value || '',
          end_time: colEl.querySelector('.tt-col-end')?.value || '',
          label: colEl.querySelector('.tt-col-label')?.value || '',
          is_break: colEl.querySelector('.tt-col-break')?.checked ? '1' : '0',
          is_exam: colEl.querySelector('.tt-col-exam')?.checked ? '1' : '0'
        });
      });
    }
    return { rows: rows, columns: columns };
  }

  function loadTimetable(sectionId) {
    closeCoursePicker();
    if (!sectionId) {
      workspace.classList.add('hidden');
      return;
    }
    fetch(appendQuery(config.loadUrl, { section_id: sectionId }), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function(response) {
        if (!response.ok) {
          throw new Error('Request failed with status ' + response.status);
        }
        return response.json();
      })
      .then(function(data) {
        if (data.success === false) {
          showFlash(data.error || 'Unable to load timetable.', 'error');
          workspace.classList.add('hidden');
          return;
        }
        state.sectionId = sectionId;
        state.canManage = !!data.can_manage;
        state.rows = normalizeRows(data.rows || []);
        state.columns = normalizeColumns(data.columns || []);
        state.slots = data.slots || {};
        state.materials = data.materials || [];
        state.sectionMeta = data.section ? {
          department_name: data.section.department_name || '',
          grade_name: data.section.grade_name || '',
          section_name: data.section.name || '',
          room_number: data.section.room_number || ''
        } : null;
        workspace.classList.remove('hidden');
        if (sectionLabel && data.section) {
          sectionLabel.textContent = [data.section.department_name, data.section.grade_name, 'Section ' + data.section.name].filter(Boolean).join(' · ');
        }
        renderCourses();
        renderEditors();
        renderGrid();
        updateClearCoursesButton();
      })
      .catch(function(error) {
        showFlash(error && error.message ? error.message : 'Unable to load timetable.', 'error');
        workspace.classList.add('hidden');
      });
  }

  function escapeHtml(value) {
    return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  departmentSelect?.addEventListener('change', function() {
    config.initialSectionId = 0;
    config.initialGradeId = 0;
    loadGrades(this.value, '');
    workspace.classList.add('hidden');
  });

  gradeSelect?.addEventListener('change', function() {
    config.initialSectionId = 0;
    loadSections(this.value, '');
    workspace.classList.add('hidden');
  });

  sectionSelect?.addEventListener('change', function() {
    loadTimetable(this.value);
  });

  printBtn?.addEventListener('click', printTimetable);

  document.getElementById('tt-add-day')?.addEventListener('click', function() {
    if (state.rows.length >= MAX_WEEK_DAYS) {
      showFlash('You can add up to 7 week days only.', 'error');
      return;
    }
    syncEditorStateFromDom();
    var nextDay = findNextUnusedWeekDay();
    if (nextDay === null) {
      showFlash('All week days are already added.', 'error');
      return;
    }
    state.rows.push({ day_of_week: nextDay, custom_label: '', label: defaultDayLabel(nextDay) });
    renderEditors();
  });

  document.getElementById('tt-add-column')?.addEventListener('click', function() {
    syncEditorStateFromDom();
    var nextIndex = state.columns.length;
    var nextTimes = nextColumnTimeRange(state.columns);
    state.columns.push({
      start_time: nextTimes.start_time,
      end_time: nextTimes.end_time,
      custom_label: '',
      label: defaultColumnLabel(nextIndex),
      is_break: false,
      is_exam: false
    });
    renderEditors();
    updateColumnTimeValidation();
  });

  clearCoursesBtn?.addEventListener('click', function() {
    if (!state.canManage || !state.sectionId || !hasAssignedCourses()) {
      return;
    }
    if (!confirm('Remove all courses from this timetable? Days and time slots will stay the same.')) {
      return;
    }
    postForm(config.clearSlotsUrl, { section_id: state.sectionId })
      .then(function(response) {
        if (!response.success) {
          showFlash((response.errors || []).join(' '), 'error');
          return;
        }
        state.slots = response.slots || {};
        renderGrid();
        updateClearCoursesButton();
        showFlash('All courses cleared from the timetable.', 'success');
      })
      .catch(function() {
        showFlash('Unable to clear timetable courses.', 'error');
      });
  });

  courseModalClose?.addEventListener('click', closeCoursePicker);
  courseModalBackdrop?.addEventListener('click', closeCoursePicker);
  customizeGridToggle?.addEventListener('click', function() {
    var collapsed = !customizeGridBody.classList.contains('is-collapsed');
    setCustomizeGridCollapsed(collapsed);
  });
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && courseModal && !courseModal.classList.contains('hidden')) {
      closeCoursePicker();
    }
    if (event.key === 'Escape' && lessonModal && !lessonModal.classList.contains('hidden')) {
      closeLessonBriefModal();
    }
  });
  lessonBriefInput?.addEventListener('input', enforceLessonBriefLimits);
  lessonModalBackdrop?.addEventListener('click', closeLessonBriefModal);
  lessonModalClose?.addEventListener('click', closeLessonBriefModal);
  lessonModalCancel?.addEventListener('click', closeLessonBriefModal);
  lessonModalSave?.addEventListener('click', saveLessonBrief);

  document.getElementById('tt-save-grid')?.addEventListener('click', function() {
    var payload = collectEditorPayload();
    if (!payload.columns.length) {
      showFlash('Add at least one time slot.', 'error');
      return;
    }
    if (payload.rows.length > MAX_WEEK_DAYS) {
      showFlash('You can add up to 7 week days only.', 'error');
      return;
    }
    var seenDays = {};
    for (var i = 0; i < payload.rows.length; i++) {
      var dayKey = String(payload.rows[i].day_of_week);
      if (seenDays[dayKey]) {
        showFlash('Each week day can only be added once.', 'error');
        return;
      }
      seenDays[dayKey] = true;
    }
    var columnError = updateColumnTimeValidation({
      expandCustomizeGrid: true,
      scrollIntoView: true,
      showFlash: true
    });
    if (columnError) {
      return;
    }
    if (!confirm('Saving the layout will clear current slot assignments for this section. Continue?')) {
      return;
    }
    postForm(config.saveGridUrl, {
      section_id: state.sectionId,
      rows: payload.rows,
      columns: payload.columns
    }).then(function(response) {
      if (!response.success) {
        showFlash((response.errors || []).join(' '), 'error');
        return;
      }
      state.rows = normalizeRows(response.data.rows || []);
      state.columns = normalizeColumns(response.data.columns || []);
      state.slots = response.data.slots || {};
      renderEditors();
      renderGrid();
      updateClearCoursesButton();
      showFlash('Timetable layout saved.', 'success');
    }).catch(function() {
      showFlash('Unable to save timetable layout.', 'error');
    });
  });

  if (config.isStudentView && config.initialSectionId) {
    loadTimetable(config.initialSectionId);
  } else if (config.initialDepartmentId) {
    loadGrades(config.initialDepartmentId, config.initialGradeId || '');
  } else if (departmentSelect && departmentSelect.value) {
    loadGrades(departmentSelect.value, '');
  } else if (departmentSelect && departmentSelect.options.length === 2) {
    departmentSelect.selectedIndex = 1;
    loadGrades(departmentSelect.value, '');
  } else if (config.initialSectionId) {
    loadTimetable(config.initialSectionId);
  }

  try {
    setCustomizeGridCollapsed(localStorage.getItem('tt-customize-grid-collapsed') === '1');
  } catch (e) {}
})();
JS
);
?>
