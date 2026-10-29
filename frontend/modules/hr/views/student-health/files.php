<?php

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array<string, mixed> $filters
 * @var string $viewMode
 * @var common\modules\employee\models\SchoolGrade[] $grades
 * @var common\modules\employee\models\SchoolDepartment[] $departments
 * @var array<int, string> $sections
 * @var array<int, array<int, array{id:int,name:string}>> $gradesByDepartment
 * @var array<int, array<int, array{id:int,name:string}>> $sectionsByGrade
 * @var string $userName
 * @var string $currentPage
 */

use common\modules\employee\models\SchoolStudentHealthFile;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Student Health Files | CultureHR';

$departments = $departments ?? [];
$gradesByDepartment = $gradesByDepartment ?? [];
$gradeOptions = ['' => 'All Classes'] + ArrayHelper::map($grades, 'id', 'name');
$departmentOptions = ['' => 'All Departments'] + ArrayHelper::map($departments, 'id', 'name');
$sectionOptions = ['' => 'All Sections'] + $sections;
$allergyOptions = ['' => 'All'] + SchoolStudentHealthFile::allergyStatusOptions();
$riskOptions = ['' => 'All Risks'] + SchoolStudentHealthFile::healthRiskOptions();

$queryParams = Yii::$app->request->queryParams;
$cardsUrl = Url::to(array_merge(['files'], $queryParams, ['view' => 'cards']));
$listUrl = Url::to(array_merge(['files'], $queryParams, ['view' => 'list']));
$resetUrl = Url::to(['files', 'view' => $viewMode]);
$sectionsByGradeJson = Json::htmlEncode($sectionsByGrade);
$gradesByDepartmentJson = Json::htmlEncode($gradesByDepartment);
$selectedDepartmentJson = Json::htmlEncode($filters['department_id'] ?: '');
$selectedGradeJson = Json::htmlEncode($filters['grade_id'] ?: '');
$selectedSectionJson = Json::htmlEncode($filters['section_id'] ?: '');

$filterInputClass = 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#1e3a8a]/20 focus:border-[#1e3a8a]';
$filterSearchClass = 'w-full h-11 pl-10 pr-3 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#1e3a8a]/20 focus:border-[#1e3a8a]';

$students = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
$totalCount = (int)$dataProvider->getTotalCount();
$from = $totalCount > 0 ? $pagination->offset + 1 : 0;
$to = min($pagination->offset + count($students), $totalCount);

$riskBadgeClass = static function (?SchoolStudentHealthFile $file): string {
    $risk = $file?->health_risk ?? SchoolStudentHealthFile::RISK_NORMAL;

    return match ($risk) {
        SchoolStudentHealthFile::RISK_EMERGENCY => 'bg-red-100 text-red-700',
        SchoolStudentHealthFile::RISK_ALLERGY_WATCH => 'bg-amber-100 text-amber-800',
        default => 'bg-sky-100 text-sky-700',
    };
};

$riskLabel = static function (?SchoolStudentHealthFile $file): string {
    if ($file === null) {
        return 'Normal Risk';
    }

    return strtoupper(str_replace('_', ' ', $file->getHealthRiskLabel()));
};

$hasActiveFilters = trim((string)$filters['search']) !== ''
    || (int)($filters['department_id'] ?? 0) > 0
    || (int)($filters['grade_id'] ?? 0) > 0
    || (int)($filters['section_id'] ?? 0) > 0
    || trim((string)($filters['allergy_status'] ?? '')) !== ''
    || trim((string)($filters['health_risk'] ?? '')) !== ''
    || trim((string)($filters['last_visit_from'] ?? '')) !== ''
    || trim((string)($filters['last_visit_to'] ?? '')) !== '';

$hasActiveFiltersJson = $hasActiveFilters ? 'true' : 'false';

$this->registerCss(<<<'CSS'
.health-filters-body { overflow: hidden; transition: max-height 0.25s ease, opacity 0.2s ease; max-height: 1200px; opacity: 1; }
.health-filters-body.is-collapsed { max-height: 0; opacity: 0; pointer-events: none; }
.health-filters-toggle .material-symbols-outlined { transition: transform 0.2s ease; }
.health-filters-toggle.is-collapsed .material-symbols-outlined { transform: rotate(-180deg); }
CSS
);

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage]) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Student Health Files', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 space-y-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <a href="<?= Html::encode(Url::to(['index'])) ?>" class="text-sm font-semibold text-[#1e3a8a] no-underline inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">arrow_back</span>
                            Back to Student Health
                        </a>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight mt-3">Student Health Files</h1>
                        <p class="text-slate-500 mt-2 max-w-2xl">Manage medical history, allergies, chronic diseases, and emergency contact information.</p>
                    </div>
                    <a href="<?= Html::encode(Url::to(['/hr/student-health/create'])) ?>"
                       class="inline-flex items-center gap-2 h-10 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold shadow-md no-underline">
                        <span class="material-symbols-outlined text-base">add</span>
                        Create Health File
                    </a>
                    <a href="<?= Html::encode(Url::to(['/hr/student-health/clinic-visits'])) ?>"
                       class="inline-flex items-center gap-2 h-10 px-4 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-sm font-semibold text-slate-700 no-underline">
                        <span class="material-symbols-outlined text-base">medical_services</span>
                        Clinic Visits
                    </a>
                </div>

                <?php if (Yii::$app->session->hasFlash('success')): ?>
                    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm"><?= Html::encode((string)Yii::$app->session->getFlash('success')) ?></div>
                <?php endif; ?>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
                        <button type="button"
                                id="health-filters-toggle"
                                class="health-filters-toggle inline-flex items-center gap-2 text-sm font-bold text-slate-800 bg-transparent border-0 cursor-pointer p-0"
                                aria-expanded="true"
                                aria-controls="health-filters-body"
                                title="Collapse filters">
                            <span class="material-symbols-outlined text-xl text-slate-500">expand_less</span>
                            <span>Filters</span>
                            <?php if ($hasActiveFilters): ?>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-[#1e3a8a]/10 text-[#1e3a8a]">Active</span>
                            <?php endif; ?>
                        </button>
                        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1">
                            <a href="<?= Html::encode($cardsUrl) ?>"
                               class="inline-flex items-center gap-1 h-8 px-3 rounded-md text-xs font-semibold no-underline <?= $viewMode === 'cards' ? 'bg-[#1e3a8a] text-white' : 'text-slate-600' ?>">
                                <span class="material-symbols-outlined text-sm">grid_view</span>
                                Cards
                            </a>
                            <a href="<?= Html::encode($listUrl) ?>"
                               class="inline-flex items-center gap-1 h-8 px-3 rounded-md text-xs font-semibold no-underline <?= $viewMode === 'list' ? 'bg-[#1e3a8a] text-white' : 'text-slate-600' ?>">
                                <span class="material-symbols-outlined text-sm">view_list</span>
                                List
                            </a>
                        </div>
                    </div>

                    <div id="health-filters-body" class="health-filters-body">
                        <div class="p-5 pt-4">
                            <?= Html::beginForm(['files'], 'get', ['class' => 'space-y-4']) ?>
                                <?= Html::hiddenInput('view', $viewMode) ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                                    <div class="sm:col-span-2 lg:col-span-4">
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Search Student</label>
                                        <div class="relative">
                                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                                            <?= Html::textInput('search', $filters['search'], [
                                                'placeholder' => 'Search by Name, ID...',
                                                'class' => $filterSearchClass,
                                            ]) ?>
                                        </div>
                                    </div>
                                    <?php if ($departments !== []): ?>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Department</label>
                                        <?= Html::dropDownList('department_id', $filters['department_id'] ?: '', $departmentOptions, [
                                            'class' => $filterInputClass,
                                            'id' => 'health-filter-department-id',
                                        ]) ?>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Class</label>
                                        <?= Html::dropDownList('grade_id', $filters['grade_id'] ?: '', $gradeOptions, [
                                            'class' => $filterInputClass,
                                            'id' => 'health-filter-grade-id',
                                        ]) ?>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Section</label>
                                        <?= Html::dropDownList('section_id', $filters['section_id'] ?: '', $sectionOptions, [
                                            'class' => $filterInputClass,
                                            'id' => 'health-filter-section-id',
                                        ]) ?>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Allergy Status</label>
                                        <?= Html::dropDownList('allergy_status', $filters['allergy_status'], $allergyOptions, [
                                            'class' => $filterInputClass,
                                        ]) ?>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Last Visit From</label>
                                        <?= Html::input('date', 'last_visit_from', $filters['last_visit_from'], [
                                            'class' => $filterInputClass,
                                            'max' => date('Y-m-d'),
                                        ]) ?>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Last Visit To</label>
                                        <?= Html::input('date', 'last_visit_to', $filters['last_visit_to'], [
                                            'class' => $filterInputClass,
                                            'max' => date('Y-m-d'),
                                        ]) ?>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Health Risk</label>
                                        <?= Html::dropDownList('health_risk', $filters['health_risk'], $riskOptions, [
                                            'class' => $filterInputClass,
                                        ]) ?>
                                    </div>
                                    <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap items-center gap-2 pt-1">
                                        <button type="submit" class="h-10 px-4 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-semibold border-0 cursor-pointer">Apply Filters</button>
                                        <a href="<?= Html::encode($resetUrl) ?>" class="h-10 px-4 inline-flex items-center rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 no-underline">Reset</a>
                                    </div>
                                </div>
                            <?= Html::endForm() ?>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-between text-sm text-slate-500">
                    <span><?= $totalCount > 0 ? 'Showing ' . $from . '–' . $to . ' of ' . $totalCount . ' students' : 'No students found' ?></span>
                </div>

                <?php if ($viewMode === 'list'): ?>
                    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">Student</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">Class</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">Risk</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">Allergies</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">Chronic</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">Last Visit</th>
                                        <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wide text-slate-400">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <?php $file = $student->healthFile; ?>
                                        <tr class="border-b border-slate-100 hover:bg-slate-50/60">
                                            <td class="px-4 py-3 align-top">
                                                <p class="font-semibold text-slate-900"><?= Html::encode($student->getDisplayFullName()) ?></p>
                                                <p class="text-xs text-slate-500 mt-1">ID: <?= Html::encode($student->student_code ?: ('STU-' . $student->id)) ?></p>
                                                <p class="text-xs text-slate-500"><?= Html::encode($student->getAgeGenderLabel()) ?></p>
                                            </td>
                                            <td class="px-4 py-3 align-top text-slate-700"><?= Html::encode($student->getClassYearLabel()) ?></td>
                                            <td class="px-4 py-3 align-top">
                                                <span class="inline-flex px-2 py-1 rounded text-[10px] font-bold tracking-wide <?= $riskBadgeClass($file) ?>"><?= Html::encode($riskLabel($file)) ?></span>
                                            </td>
                                            <td class="px-4 py-3 align-top text-xs text-slate-600"><?= Html::encode($file?->getAllergyStatusLabel() ?? 'No Known Allergies') ?></td>
                                            <td class="px-4 py-3 align-top text-xs text-slate-600">
                                                <?php
                                                $chronic = $file?->getChronicConditionTags() ?? [];
                                                echo $chronic !== [] ? Html::encode(implode(', ', $chronic)) : '—';
                                                ?>
                                            </td>
                                            <td class="px-4 py-3 align-top text-xs text-slate-600">
                                                <?= $file && $file->last_visit_date
                                                    ? Html::encode(Yii::$app->formatter->asDate($file->last_visit_date, 'php:M j, Y'))
                                                    : '—' ?>
                                            </td>
                                            <td class="px-4 py-3 align-top text-right whitespace-nowrap">
                                                <?php if ($file): ?>
                                                    <a href="<?= Html::encode(Url::to(['view', 'id' => $file->id])) ?>" class="text-xs font-semibold text-[#1e3a8a] no-underline mr-3">View</a>
                                                    <a href="<?= Html::encode(Url::to(['update', 'id' => $file->id])) ?>" class="text-xs font-semibold text-[#1e3a8a] no-underline">Edit</a>
                                                <?php else: ?>
                                                    <a href="<?= Html::encode(Url::to(['create', 'student_id' => $student->id])) ?>" class="text-xs font-semibold text-[#1e3a8a] no-underline">Create File</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        <?php foreach ($students as $student): ?>
                            <?php $file = $student->healthFile; ?>
                            <article class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-col gap-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h2 class="text-lg font-bold text-slate-900 truncate"><?= Html::encode($student->getDisplayFullName()) ?></h2>
                                        <p class="text-xs text-slate-500 mt-1">
                                            ID: <?= Html::encode($student->student_code ?: ('STU-' . $student->id)) ?>
                                            · CLASS: <?= Html::encode($student->getClassYearLabel()) ?>
                                        </p>
                                    </div>
                                    <span class="shrink-0 inline-flex px-2 py-1 rounded text-[10px] font-bold tracking-wide <?= $riskBadgeClass($file) ?>"><?= Html::encode($riskLabel($file)) ?></span>
                                </div>

                                <div class="space-y-2 text-sm text-slate-600">
                                    <p><?= Html::encode($student->getAgeGenderLabel()) ?></p>
                                    <p>Parent: <?= Html::encode(trim((string)($student->guardian_name ?? '')) ?: '—') ?></p>
                                    <div class="flex flex-wrap gap-2">
                                        <?php
                                        $allergyLabel = $file?->getAllergyStatusLabel() ?? 'No Known Allergies';
                                        $allergyClass = ($file === null || $file->allergy_status === SchoolStudentHealthFile::ALLERGY_NONE)
                                            ? 'bg-slate-100 text-slate-600'
                                            : 'bg-orange-100 text-orange-700';
                                        ?>
                                        <span class="inline-flex px-2 py-1 rounded text-[10px] font-bold tracking-wide uppercase <?= $allergyClass ?>"><?= Html::encode($allergyLabel) ?></span>
                                        <?php foreach ($file?->getChronicConditionTags() ?? [] as $condition): ?>
                                            <span class="inline-flex px-2 py-1 rounded text-[10px] font-bold tracking-wide uppercase bg-purple-100 text-purple-700">Chronic: <?= Html::encode($condition) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="inline-flex items-center gap-1 text-xs text-slate-500">
                                        <span class="material-symbols-outlined text-sm">calendar_month</span>
                                        Last Visit:
                                        <?= $file && $file->last_visit_date
                                            ? Html::encode(Yii::$app->formatter->asDate($file->last_visit_date, 'php:M j, Y'))
                                            : '—' ?>
                                    </p>
                                </div>

                                <div class="flex gap-2 mt-auto pt-2">
                                    <?php if ($file): ?>
                                        <a href="<?= Html::encode(Url::to(['view', 'id' => $file->id])) ?>"
                                           class="flex-1 h-10 inline-flex items-center justify-center rounded-lg border border-[#1e3a8a] text-[#1e3a8a] text-sm font-semibold no-underline">View Details</a>
                                        <a href="<?= Html::encode(Url::to(['update', 'id' => $file->id])) ?>"
                                           class="flex-1 h-10 inline-flex items-center justify-center rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-semibold no-underline">Edit File</a>
                                    <?php else: ?>
                                        <a href="<?= Html::encode(Url::to(['create', 'student_id' => $student->id])) ?>"
                                           class="w-full h-10 inline-flex items-center justify-center rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-semibold no-underline">Create Health File</a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($totalCount === 0): ?>
                    <div class="py-16 text-center text-slate-400 bg-white rounded-xl border border-slate-200">No students match your filters.</div>
                <?php endif; ?>

                <div class="flex justify-center">
                    <?= LinkPager::widget([
                        'pagination' => $pagination,
                        'options' => ['class' => 'flex flex-wrap gap-1'],
                        'linkOptions' => ['class' => 'inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 no-underline'],
                        'disabledListItemSubTagOptions' => ['tag' => 'span', 'class' => 'inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-slate-100 bg-slate-50 px-3 text-sm text-slate-400'],
                        'activePageCssClass' => 'active',
                    ]) ?>
                </div>
            </div>
        </main>
    </div>
</div>
<?php
$this->registerJs(<<<JS
(function() {
  var hasActiveFilters = {$hasActiveFiltersJson};
  var filtersBody = document.getElementById('health-filters-body');
  var filtersToggle = document.getElementById('health-filters-toggle');
  var storageKey = 'health-files-filters-collapsed';

  function setFiltersCollapsed(collapsed) {
    if (!filtersBody || !filtersToggle) return;
    filtersBody.classList.toggle('is-collapsed', collapsed);
    filtersToggle.classList.toggle('is-collapsed', collapsed);
    filtersToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    filtersToggle.title = collapsed ? 'Expand filters' : 'Collapse filters';
    try {
      localStorage.setItem(storageKey, collapsed ? '1' : '0');
    } catch (e) {}
  }

  if (filtersToggle && filtersBody) {
    filtersToggle.addEventListener('click', function() {
      setFiltersCollapsed(!filtersBody.classList.contains('is-collapsed'));
    });

    var collapsed = false;
    if (!hasActiveFilters) {
      try {
        collapsed = localStorage.getItem(storageKey) === '1';
      } catch (e) {}
    }
    setFiltersCollapsed(collapsed);
  }

  var gradesByDepartment = {$gradesByDepartmentJson} || {};
  var sectionsByGrade = {$sectionsByGradeJson} || {};
  var departmentSelect = document.getElementById('health-filter-department-id');
  var gradeSelect = document.getElementById('health-filter-grade-id');
  var sectionSelect = document.getElementById('health-filter-section-id');

  function rebuildGrades(departmentId, selectedGradeId) {
    if (!gradeSelect) return;
    departmentId = String(departmentId || '');
    selectedGradeId = String(selectedGradeId || '');
    var items = [];
    if (departmentId) {
      items = gradesByDepartment[departmentId] || gradesByDepartment[parseInt(departmentId, 10)] || [];
    } else {
      Object.keys(gradesByDepartment).forEach(function(key) {
        (gradesByDepartment[key] || []).forEach(function(item) {
          items.push(item);
        });
      });
    }
    gradeSelect.innerHTML = '<option value="">All Classes</option>';
    items.forEach(function(item) {
      var option = document.createElement('option');
      option.value = String(item.id);
      option.textContent = String(item.name);
      if (selectedGradeId && String(item.id) === selectedGradeId) {
        option.selected = true;
      }
      gradeSelect.appendChild(option);
    });
  }

  function rebuildSections(gradeId, selectedSectionId) {
    if (!sectionSelect) return;
    gradeId = String(gradeId || '');
    selectedSectionId = String(selectedSectionId || '');
    sectionSelect.innerHTML = '<option value="">All Sections</option>';
    if (!gradeId) {
      sectionSelect.disabled = true;
      return;
    }
    sectionSelect.disabled = false;
    var items = sectionsByGrade[gradeId] || sectionsByGrade[parseInt(gradeId, 10)] || [];
    items.forEach(function(item) {
      var option = document.createElement('option');
      option.value = String(item.id);
      option.textContent = String(item.name);
      if (selectedSectionId && String(item.id) === selectedSectionId) {
        option.selected = true;
      }
      sectionSelect.appendChild(option);
    });
  }

  if (departmentSelect && gradeSelect && sectionSelect) {
    rebuildGrades({$selectedDepartmentJson}, {$selectedGradeJson});
    rebuildSections({$selectedGradeJson}, {$selectedSectionJson});

    departmentSelect.addEventListener('change', function() {
      rebuildGrades(departmentSelect.value, '');
      rebuildSections('', '');
    });
    gradeSelect.addEventListener('change', function() {
      rebuildSections(gradeSelect.value, '');
    });
  } else if (gradeSelect && sectionSelect) {
    gradeSelect.addEventListener('change', function() {
      rebuildSections(gradeSelect.value, '');
    });
    rebuildSections({$selectedGradeJson}, {$selectedSectionJson});
  }
})();
JS
);
?>
