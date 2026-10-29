<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudent[] $students
 * @var yii\data\Pagination $pagination
 * @var int $totalCount
 * @var string $search
 * @var int $gradeFilter
 * @var int $sectionFilter
 * @var string $statusFilter
 * @var common\modules\employee\models\SchoolGrade[] $gradeOptions
 * @var array<int, string> $sectionOptions
 * @var array<int, array<int, array{id:int,name:string,label:string}>> $sectionsByGrade
 * @var string $scopeLabel
 */

use common\modules\employee\models\SchoolStudent;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Student Registry | LMS';

$gradeDropdown = ArrayHelper::map($gradeOptions, 'id', 'name');
$hasActiveFilters = $search !== '' || $gradeFilter > 0 || $sectionFilter > 0 || $statusFilter !== '';
$from = $totalCount > 0 ? $pagination->offset + 1 : 0;
$to = min($pagination->offset + count($students), $totalCount);
$sectionsByGradeJson = Json::htmlEncode($sectionsByGrade ?? []);
$selectedGradeIdJson = Json::htmlEncode($gradeFilter ?: '');
$selectedSectionIdJson = Json::htmlEncode($sectionFilter ?: '');

?>
<?= $this->render('../layouts/_page-start') ?>
    <header class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Student Registry</h2>
            <p class="text-slate-500 mt-2 text-lg">
                View enrolled students<?= $scopeLabel !== '' ? ' (' . Html::encode($scopeLabel) . ').' : '.' ?>
            </p>
        </div>
    </header>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <?= Html::beginForm(['students'], 'get', ['class' => 'space-y-4']) ?>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Student Name</label>
                <?= Html::textInput('search', $search, [
                    'placeholder' => 'Search by name',
                    'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                ]) ?>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Grade</label>
                    <?= Html::dropDownList('grade_id', $gradeFilter ?: '', ['' => 'All Grades'] + $gradeDropdown, [
                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                        'id' => 'lms-student-filter-grade-id',
                    ]) ?>
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Section</label>
                    <?= Html::dropDownList('section_id', $sectionFilter ?: '', ['' => 'All Sections'] + $sectionOptions, [
                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                        'id' => 'lms-student-filter-section-id',
                    ]) ?>
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Status</label>
                    <?= Html::dropDownList('status', $statusFilter, ['' => 'All Statuses'] + SchoolStudent::statusOptions(), [
                        'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm',
                    ]) ?>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                <button type="submit" class="inline-flex items-center gap-2 h-11 px-4 rounded-lg bg-[#642754] text-white text-sm font-bold border-0 cursor-pointer">
                    Apply Filters
                </button>
                <?php if ($hasActiveFilters): ?>
                    <a href="<?= Html::encode(Url::to(['students'])) ?>"
                       class="inline-flex items-center gap-2 h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-sm font-semibold no-underline">
                        Reset Filters
                    </a>
                <?php endif; ?>
            </div>
        <?= Html::endForm() ?>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50/80">
                        <th class="px-5 py-3.5">Reg #</th>
                        <th class="px-5 py-3.5">Student</th>
                        <th class="px-5 py-3.5">Class / Year</th>
                        <th class="px-5 py-3.5">Contact</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($students === []): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">No students found in your scope.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($students as $student): ?>
                        <?php $contactPhone = trim((string)($student->guardian_phone ?: $student->phone)); ?>
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-4 font-mono text-xs text-slate-600 whitespace-nowrap"><?= Html::encode($student->getRegistrationLabel()) ?></td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900"><?= Html::encode($student->getDisplayFullName()) ?></div>
                            </td>
                            <td class="px-5 py-4 text-slate-700"><?= Html::encode($student->getClassYearLabel()) ?></td>
                            <td class="px-5 py-4 text-slate-700 whitespace-nowrap">
                                <?= $contactPhone !== '' ? Html::encode($contactPhone) : '—' ?>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex text-[10px] font-black uppercase tracking-wide px-2.5 py-1 rounded-full <?= Html::encode($student->getStatusBadgeClass()) ?>">
                                    <?= Html::encode(strtoupper($student->getStatusLabel())) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end">
                                    <a href="<?= Html::encode(Url::to(['view', 'id' => (int)$student->id])) ?>"
                                       class="inline-flex size-8 items-center justify-center rounded-md hover:bg-slate-100 text-slate-500 no-underline"
                                       title="View profile">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500">
                Showing <?= (int)$from ?> to <?= (int)$to ?> of <?= (int)$totalCount ?> students
            </p>
            <?= LinkPager::widget([
                'pagination' => $pagination,
                'options' => ['class' => 'flex flex-wrap gap-1'],
                'linkContainerOptions' => ['class' => 'inline-flex'],
                'linkOptions' => ['class' => 'inline-flex items-center justify-center min-w-9 h-9 px-2 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 no-underline hover:bg-slate-50'],
                'disabledListItemSubTagOptions' => ['class' => 'inline-flex items-center justify-center min-w-9 h-9 px-2 rounded-lg border border-slate-100 bg-slate-50 text-sm text-slate-400'],
            ]) ?>
        </div>
    </section>
<?= $this->render('../layouts/_page-end') ?>

<?php
$this->registerJs(<<<JS
(function() {
  var sectionsByGrade = {$sectionsByGradeJson} || {};
  var gradeSelect = document.getElementById('lms-student-filter-grade-id');
  var sectionSelect = document.getElementById('lms-student-filter-section-id');

  function rebuildSections(gradeId, selectedSectionId) {
    if (!sectionSelect) return;
    gradeId = String(gradeId || '');
    selectedSectionId = String(selectedSectionId || '');
    sectionSelect.innerHTML = '<option value="">All Sections</option>';
    if (!gradeId) {
      sectionSelect.disabled = true;
      sectionSelect.value = '';
      return;
    }
    sectionSelect.disabled = false;
    var items = sectionsByGrade[gradeId] || sectionsByGrade[parseInt(gradeId, 10)] || [];
    items.forEach(function(item) {
      var option = document.createElement('option');
      option.value = String(item.id);
      option.textContent = String(item.label || ('Section ' + item.name));
      if (selectedSectionId && String(item.id) === selectedSectionId) {
        option.selected = true;
      }
      sectionSelect.appendChild(option);
    });
  }

  if (gradeSelect && sectionSelect) {
    rebuildSections({$selectedGradeIdJson}, {$selectedSectionIdJson});
    gradeSelect.addEventListener('change', function() {
      rebuildSections(this.value, '');
    });
  }
})();
JS
);
?>
