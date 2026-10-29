<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolDepartment[] $departments
 * @var common\modules\employee\models\SchoolGrade[] $grades
 * @var array<int, array<int, array{id:int,name:string}>> $gradesByDepartment
 * @var common\modules\employee\models\SchoolSubjectGrade[] $rows
 * @var yii\data\Pagination $pagination
 * @var int $totalCount
 * @var int $departmentFilter
 * @var int $gradeFilter
 * @var string $userName
 * @var string $currentPage
 */

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Subject List | CultureHR';

$departmentOptions = ArrayHelper::map($departments, 'id', 'name');
$gradeOptions = ArrayHelper::map($grades, 'id', 'name');
$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');
$hasActiveFilters = $departmentFilter > 0 || $gradeFilter > 0;
$resetFiltersUrl = Url::to(['/hr/school-department/subjects']);

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'subjects']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Subject List', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 py-8 space-y-6">
                <nav class="text-sm text-slate-500">
                    <span>Academic</span>
                    <span class="mx-2">/</span>
                    <a href="<?= Html::encode(Url::to(['/hr/school-department/subjects'])) ?>" class="text-slate-500 hover:text-slate-700 no-underline">Subjects</a>
                    <span class="mx-2">/</span>
                    <span class="text-slate-900 font-semibold">List</span>
                </nav>

                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Subject List</h1>
                        <p class="text-slate-500 mt-2">Browse subjects by department and grade with assigned maximum marks.</p>
                    </div>
                    <a href="<?= Html::encode(Url::to(['/hr/school-department/create-subject'])) ?>"
                       class="inline-flex items-center gap-2 h-11 px-5 rounded-xl bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold shadow-md transition-colors no-underline">
                        <span class="material-symbols-outlined text-lg">add_circle</span>
                        Create Subject
                    </a>
                </div>

                <?php if ($successMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm"><?= Html::encode($successMessage) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm"><?= Html::encode($errorMessage) ?></div>
                <?php endif; ?>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <?= Html::beginForm(['subjects'], 'get', ['class' => 'flex flex-wrap items-end gap-4']) ?>
                        <div class="min-w-[14rem] flex-1 sm:flex-none">
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Department</label>
                            <?= Html::dropDownList('department_id', $departmentFilter ?: '', ['' => 'All Departments'] + $departmentOptions, [
                                'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm focus:border-[#642754] focus:ring-1 focus:ring-[#642754]/20',
                                'id' => 'filter-department-id',
                            ]) ?>
                        </div>
                        <div class="min-w-[14rem] flex-1 sm:flex-none">
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Grade</label>
                            <?= Html::dropDownList('grade_id', $gradeFilter ?: '', ['' => 'All Grades'] + $gradeOptions, [
                                'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm focus:border-[#642754] focus:ring-1 focus:ring-[#642754]/20',
                                'id' => 'filter-grade-id',
                            ]) ?>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 h-11 px-4 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-sm font-semibold cursor-pointer shadow-sm">
                                <span class="material-symbols-outlined text-base text-slate-500">filter_alt</span>
                                Apply Filters
                            </button>
                            <?php if ($hasActiveFilters): ?>
                                <a href="<?= Html::encode($resetFiltersUrl) ?>"
                                   class="inline-flex items-center gap-2 h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-sm font-semibold no-underline shadow-sm transition-colors">
                                    <span class="material-symbols-outlined text-base text-slate-500">filter_alt_off</span>
                                    Reset Filters
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="ml-auto flex items-center gap-2 self-center">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Results</span>
                            <span class="inline-flex items-center h-8 px-3 rounded-full bg-[#642754]/10 text-[#642754] text-sm font-bold">
                                <?= (int)$totalCount ?> <?= (int)$totalCount === 1 ? 'Subject' : 'Subjects' ?>
                            </span>
                        </div>
                    <?= Html::endForm() ?>
                </section>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                        <h2 class="text-sm font-bold text-slate-900">All Subjects</h2>
                        <span class="text-xs text-slate-500">Manage grade assignments and maximum marks</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50/80">
                                    <th class="px-5 py-3.5">Department</th>
                                    <th class="px-5 py-3.5">Grade</th>
                                    <th class="px-5 py-3.5">Name</th>
                                    <th class="px-5 py-3.5">Maximum Mark</th>
                                    <th class="px-5 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if ($rows === []): ?>
                                    <tr>
                                        <td colspan="5" class="px-5 py-16 text-center">
                                            <div class="flex flex-col items-center gap-3 text-slate-400">
                                                <span class="material-symbols-outlined text-4xl text-slate-300">menu_book</span>
                                                <p class="text-sm font-medium">No subjects found.</p>
                                                <a href="<?= Html::encode(Url::to(['/hr/school-department/create-subject'])) ?>"
                                                   class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-[#eb652c] hover:bg-[#d85a24] text-white text-xs font-bold no-underline">
                                                    <span class="material-symbols-outlined text-base">add</span>
                                                    Create Subject
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($rows as $row): ?>
                                    <?php
                                    $subjectId = (int)($row->subject?->id ?? 0);
                                    $maxScoreLabel = rtrim(rtrim(number_format((float)($row->max_score ?? 100), 2, '.', ''), '0'), '.');
                                    ?>
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="px-5 py-4 text-slate-600"><?= Html::encode($row->subject?->department?->name ?? '—') ?></td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                                                <span class="material-symbols-outlined text-sm text-slate-400">school</span>
                                                <?= Html::encode($row->grade?->name ?? '—') ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex items-center gap-2 font-semibold text-slate-900">
                                                <span class="inline-flex size-8 items-center justify-center rounded-lg bg-[#642754]/10 text-[#642754]">
                                                    <span class="material-symbols-outlined text-base">menu_book</span>
                                                </span>
                                                <?= Html::encode($row->subject?->name ?? '—') ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex items-center h-8 px-3 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100">
                                                <?= Html::encode($maxScoreLabel) ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="flex flex-wrap items-center justify-end gap-2">
                                                <?php if ($subjectId > 0): ?>
                                                    <a href="<?= Html::encode(Url::to(['/hr/school-department/view-subject', 'id' => $subjectId])) ?>"
                                                       class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold no-underline shadow-sm transition-colors"
                                                       title="View subject">
                                                        <span class="material-symbols-outlined text-base text-[#1e3a8a]">visibility</span>
                                                        View
                                                    </a>
                                                    <a href="<?= Html::encode(Url::to(['/hr/school-department/update-subject', 'id' => $subjectId])) ?>"
                                                       class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg border border-slate-200 bg-white hover:bg-amber-50 hover:border-amber-200 text-slate-700 hover:text-amber-800 text-xs font-semibold no-underline shadow-sm transition-colors"
                                                       title="Update subject">
                                                        <span class="material-symbols-outlined text-base text-amber-600">edit</span>
                                                        Update
                                                    </a>
                                                <?php endif; ?>
                                                <?= Html::beginForm(['delete-subject-grade', 'id' => (int)$row->id], 'post', ['class' => 'm-0 inline']) ?>
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold cursor-pointer shadow-sm transition-colors"
                                                            onclick="return confirm('Remove this subject grade entry?');"
                                                            title="Remove grade assignment">
                                                        <span class="material-symbols-outlined text-base">delete</span>
                                                        Remove
                                                    </button>
                                                <?= Html::endForm() ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-t border-slate-100">
                        <p class="text-sm text-slate-500">
                            Page <?= $pagination->page + 1 ?> of <?= max(1, (int)$pagination->pageCount) ?>
                        </p>
                        <?= LinkPager::widget([
                            'pagination' => $pagination,
                            'options' => ['class' => 'flex gap-2 list-none m-0 p-0'],
                            'linkOptions' => ['class' => 'inline-flex h-9 px-3 items-center rounded-lg border border-slate-200 text-sm no-underline text-slate-700 hover:bg-slate-50'],
                            'disabledPageCssClass' => 'opacity-40 pointer-events-none',
                            'activePageCssClass' => 'bg-[#1e3a8a] text-white border-[#1e3a8a]',
                        ]) ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<?php
$gradesByDepartmentJson = Json::htmlEncode($gradesByDepartment ?? []);
$selectedDepartmentIdJson = (int)$departmentFilter;
$selectedGradeIdJson = (int)$gradeFilter;

$this->registerJs(<<<JS
(function() {
  var gradesByDepartment = {$gradesByDepartmentJson} || {};
  var departmentSelect = document.getElementById('filter-department-id');
  var gradeSelect = document.getElementById('filter-grade-id');

  function rebuildGrades(departmentId, selectedGradeId) {
    if (!gradeSelect) {
      return;
    }

    departmentId = String(departmentId || '');
    selectedGradeId = String(selectedGradeId || '');
    gradeSelect.innerHTML = '<option value="">All Grades</option>';

    var items = [];
    if (!departmentId) {
      Object.keys(gradesByDepartment).forEach(function(deptId) {
        (gradesByDepartment[deptId] || []).forEach(function(item) {
          items.push(item);
        });
      });
    } else {
      items = gradesByDepartment[departmentId] || gradesByDepartment[parseInt(departmentId, 10)] || [];
    }

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

  if (departmentSelect && gradeSelect) {
    rebuildGrades({$selectedDepartmentIdJson}, {$selectedGradeIdJson});
    departmentSelect.addEventListener('change', function() {
      rebuildGrades(this.value, '');
    });
  }
})();
JS
);
?>
