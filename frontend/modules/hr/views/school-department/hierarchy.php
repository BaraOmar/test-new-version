<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolDepartment[] $departments
 * @var string $search
 * @var string $userName
 * @var string $currentPage
 */

use common\modules\employee\models\SchoolDepartment;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Infrastructure Hierarchy | CultureHR';

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');

$this->registerCss(<<<'CSS'
.infra-dept-card { border: 1px solid #e2e8f0; border-radius: 1rem; overflow: hidden; background: #fff; box-shadow: 0 4px 24px rgba(15,23,42,.04); }
.infra-dept-head { background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); color: #fff; }
.infra-grade-row { border-top: 1px solid #e2e8f0; }
.infra-section-card { border: 1px solid #e2e8f0; border-radius: .875rem; background: #f8fafc; min-width: 12rem; }
.infra-modal { display: none; position: fixed; inset: 0; z-index: 50; background: rgba(15,23,42,.45); align-items: center; justify-content: center; padding: 1rem; }
.infra-modal.open { display: flex; }
.infra-modal-panel { width: 100%; max-width: 28rem; background: #fff; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 20px 50px rgba(15,23,42,.2); }
.infra-delete-modal-panel { max-width: 32rem; }
.infra-delete-phrase { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
CSS
);

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'school_department_hierarchy']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Infrastructure Hierarchy', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 py-8 space-y-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Infrastructure Hierarchy</h1>
                        <p class="text-slate-500 mt-2">Manage departments, grades, and their constituent sections.</p>
                    </div>
                    <a href="<?= Url::to(['/hr/school-department/create']) ?>"
                       class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold no-underline shadow-md">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Add Department
                    </a>
                </div>

                <?= Html::beginForm(['hierarchy'], 'get', ['id' => 'infra-search-form', 'class' => 'max-w-xl']) ?>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">search</span>
                        <?= Html::textInput('search', $search, [
                            'id' => 'infra-search-input',
                            'placeholder' => 'Search infrastructure...',
                            'autocomplete' => 'off',
                            'class' => 'w-full h-11 pl-10 pr-10 rounded-lg border border-slate-200 bg-white text-sm focus:border-[#1e3a8a] focus:ring-[#1e3a8a]',
                        ]) ?>
                        <button type="button"
                                id="infra-search-clear"
                                class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex size-7 items-center justify-center rounded-md text-slate-400 transition-opacity hover:bg-slate-100 hover:text-slate-700 <?= trim($search) === '' ? 'opacity-0 pointer-events-none' : '' ?>"
                                aria-label="Clear search"
                                title="Clear search">
                            <span class="material-symbols-outlined text-[1.15rem]">close</span>
                        </button>
                    </div>
                <?= Html::endForm() ?>

                <?php if ($successMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-medium"><?= Html::encode($successMessage) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium"><?= Html::encode($errorMessage) ?></div>
                <?php endif; ?>

                <?php if ($departments === []): ?>
                    <div class="p-10 text-center bg-white rounded-xl border border-slate-200 text-slate-500">
                        No departments found.
                        <a href="<?= Url::to(['/hr/school-department/create']) ?>" class="text-[#1e3a8a] font-semibold no-underline">Create one</a>.
                    </div>
                <?php endif; ?>

                <?php foreach ($departments as $department): ?>
                    <?php $deanName = $department->head?->full_name ?? ''; ?>
                    <article id="dept-<?= (int)$department->id ?>" class="infra-dept-card">
                        <div class="infra-dept-head px-5 py-4 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="size-12 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-3xl">account_balance</span>
                                </div>
                                <div class="min-w-0">
                                    <h2 class="text-xl font-black truncate"><?= Html::encode($department->name) ?></h2>
                                    <?php if ($deanName !== ''): ?>
                                        <p class="text-xs font-bold tracking-[0.12em] uppercase text-blue-100 mt-1">
                                            Dean: <?= Html::encode(strtoupper($deanName)) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" class="infra-open-modal h-9 px-3 rounded-lg bg-white/15 hover:bg-white/25 text-white text-xs font-bold border-0 cursor-pointer"
                                        data-modal="grade-<?= (int)$department->id ?>">+ Add Grade</button>
                                <a href="<?= Url::to(['/hr/school-department/update', 'id' => (int)$department->id]) ?>"
                                   class="inline-flex items-center justify-center size-9 rounded-lg bg-white/15 hover:bg-white/25 text-white no-underline" title="Edit">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </a>
                                <?= Html::beginForm(['delete', 'id' => (int)$department->id], 'post', [
                                    'class' => 'm-0 infra-delete-form',
                                    'data-delete-message' => Html::encode('This will permanently delete the department "' . $department->name . '" and all of its grades and sections.'),
                                ]) ?>
                                    <button type="submit" class="inline-flex items-center justify-center size-9 rounded-lg bg-white/15 hover:bg-white/25 text-white border-0 cursor-pointer"
                                            title="Delete">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                <?= Html::endForm() ?>
                            </div>
                        </div>

                        <?php if ($department->grades === []): ?>
                            <div class="px-5 py-8 text-center text-sm text-slate-500">No grades yet. Use "+ Add Grade" to create one.</div>
                        <?php endif; ?>

                        <?php foreach ($department->grades as $grade): ?>
                            <?php
                            $sectionCount = count($grade->sections);
                            $studentCount = 0;
                            foreach ($grade->sections as $section) {
                                $studentCount += (int)$section->current_enrollment;
                            }
                            ?>
                            <div class="infra-grade-row px-5 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-900"><?= Html::encode($grade->name) ?></h3>
                                        <p class="text-sm text-slate-500"><?= (int)$sectionCount ?> Sections | <?= (int)$studentCount ?> Students</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="<?= Url::to(['/hr/school-department/create-section', 'grade_id' => (int)$grade->id]) ?>"
                                           class="inline-flex items-center h-9 px-3 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold no-underline">
                                            + Add Section
                                        </a>
                                        <?= Html::beginForm(['delete-grade', 'id' => (int)$grade->id], 'post', [
                                            'class' => 'm-0 infra-delete-form',
                                            'data-delete-message' => Html::encode('This will permanently delete "' . $grade->name . '" and all of its sections.'),
                                        ]) ?>
                                            <button type="submit" class="h-9 px-3 rounded-lg text-red-600 text-xs font-bold bg-transparent border border-red-200 cursor-pointer">Delete grade</button>
                                        <?= Html::endForm() ?>
                                    </div>
                                </div>

                                <div class="flex gap-3 overflow-x-auto pb-1">
                                    <?php if ($grade->sections === []): ?>
                                        <p class="text-sm text-slate-400 py-4">No sections in this grade.</p>
                                    <?php endif; ?>
                                    <?php foreach ($grade->sections as $section): ?>
                                    <?php
                                    $atCapacity = $section->isAtCapacity();
                                    ?>
                                        <div class="infra-section-card p-4 flex flex-col gap-3 shrink-0">
                                            <div class="flex items-start justify-between gap-2">
                                                <h4 class="font-bold text-slate-900"><?= Html::encode($section->name) ?></h4>
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?= $atCapacity ? 'bg-red-100 text-red-700' : 'bg-slate-200 text-slate-700' ?>">
                                                    <?= (int)$section->current_enrollment ?>/<?= (int)$section->capacity_max ?>
                                                </span>
                                            </div>
                                            <?php if ($atCapacity): ?>
                                                <span class="self-start text-[10px] font-black tracking-wide uppercase px-2 py-0.5 rounded bg-amber-500 text-white">Full</span>
                                            <?php endif; ?>
                                            <div class="space-y-1 text-sm text-slate-600">
                                                <p class="flex items-center gap-1.5">
                                                    <span class="material-symbols-outlined text-base text-slate-400">person</span>
                                                    <?= Html::encode($section->leadTeacher?->full_name ?: 'Unassigned') ?>
                                                </p>
                                                <p class="flex items-center gap-1.5">
                                                    <span class="material-symbols-outlined text-base text-slate-400">meeting_room</span>
                                                    <?= Html::encode($section->room_number ?: '—') ?>
                                                </p>
                                            </div>
                                            <div class="mt-auto flex flex-col gap-2">
                                                <a href="<?= Url::to(['/hr/school-department/section', 'id' => (int)$section->id]) ?>"
                                                   class="inline-flex items-center justify-center h-9 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 no-underline hover:bg-slate-50">
                                                    View Section
                                                </a>
                                                <?= Html::beginForm(['delete-section', 'id' => (int)$section->id], 'post', [
                                                    'class' => 'm-0 infra-delete-form',
                                                    'data-delete-message' => Html::encode('This will permanently delete section "' . $section->name . '".'),
                                                ]) ?>
                                                    <button type="submit"
                                                            class="inline-flex w-full items-center justify-center h-9 rounded-lg text-red-600 text-xs font-bold bg-transparent border border-red-200 cursor-pointer hover:bg-red-50">
                                                        Delete section
                                                    </button>
                                                <?= Html::endForm() ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </article>

                    <div id="grade-<?= (int)$department->id ?>" class="infra-modal">
                        <div class="infra-modal-panel">
                            <h3 class="text-lg font-bold text-slate-900 mb-4">Add Grade</h3>
                            <?= Html::beginForm(['add-grade'], 'post') ?>
                                <?= Html::hiddenInput('search', $search) ?>
                                <?= Html::hiddenInput('SchoolGrade[school_department_id]', (int)$department->id) ?>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Grade name</label>
                                <?= Html::textInput('SchoolGrade[name]', '', [
                                    'required' => true,
                                    'placeholder' => 'e.g. Grade 9',
                                    'class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 mb-4',
                                ]) ?>
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="infra-close-modal h-10 px-4 rounded-lg border border-slate-200 bg-white text-sm font-semibold cursor-pointer">Cancel</button>
                                    <button type="submit" class="h-10 px-4 rounded-lg bg-[#1e3a8a] text-white text-sm font-bold border-0 cursor-pointer">Save</button>
                                </div>
                            <?= Html::endForm() ?>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

<div id="infra-delete-modal" class="infra-modal" aria-hidden="true">
    <div class="infra-modal-panel infra-delete-modal-panel">
        <h3 class="text-lg font-bold text-slate-900 mb-2">Confirm deletion</h3>
        <p id="infra-delete-message" class="text-sm text-slate-600 mb-4"></p>
        <label for="infra-delete-confirm-input" class="block text-sm font-semibold text-slate-700 mb-2">
            Type <span class="infra-delete-phrase text-red-700">delete</span> to continue
        </label>
        <input type="text"
               id="infra-delete-confirm-input"
               autocomplete="off"
               spellcheck="false"
               placeholder="delete"
               class="w-full h-11 px-3 rounded-lg border border-slate-200 mb-2 text-sm focus:border-red-500 focus:ring-red-500">
        <p id="infra-delete-error" class="hidden text-sm text-red-600 mb-4"></p>
        <div class="flex justify-end gap-2">
            <button type="button" id="infra-delete-cancel" class="h-10 px-4 rounded-lg border border-slate-200 bg-white text-sm font-semibold cursor-pointer">Cancel</button>
            <button type="button" id="infra-delete-confirm-btn" disabled class="h-10 px-4 rounded-lg bg-red-600 text-white text-sm font-bold border-0 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">Delete</button>
        </div>
    </div>
</div>

<?php
$clearSearchUrl = json_encode(Url::to(['/hr/school-department/hierarchy']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$deletePhrase = json_encode('delete', JSON_UNESCAPED_UNICODE);
$this->registerJs(<<<JS
(function() {
  var deletePhrase = {$deletePhrase};
  var deleteModal = document.getElementById('infra-delete-modal');
  var deleteMessage = document.getElementById('infra-delete-message');
  var deleteInput = document.getElementById('infra-delete-confirm-input');
  var deleteError = document.getElementById('infra-delete-error');
  var deleteConfirmBtn = document.getElementById('infra-delete-confirm-btn');
  var deleteCancelBtn = document.getElementById('infra-delete-cancel');
  var pendingDeleteForm = null;

  function closeModals() {
    document.querySelectorAll('.infra-modal.open').forEach(function(el) { el.classList.remove('open'); });
    if (deleteModal) deleteModal.setAttribute('aria-hidden', 'true');
  }

  function resetDeleteModal() {
    if (deleteInput) deleteInput.value = '';
    if (deleteError) {
      deleteError.textContent = '';
      deleteError.classList.add('hidden');
    }
    if (deleteConfirmBtn) deleteConfirmBtn.disabled = true;
    pendingDeleteForm = null;
  }

  function openDeleteModal(form) {
    if (!deleteModal || !form) return;
    pendingDeleteForm = form;
    if (deleteMessage) {
      deleteMessage.textContent = form.getAttribute('data-delete-message') || 'This action cannot be undone.';
    }
    if (deleteInput) deleteInput.value = '';
    if (deleteError) {
      deleteError.textContent = '';
      deleteError.classList.add('hidden');
    }
    if (deleteConfirmBtn) deleteConfirmBtn.disabled = true;
    deleteModal.classList.add('open');
    deleteModal.setAttribute('aria-hidden', 'false');
    if (deleteInput) deleteInput.focus();
  }

  function closeDeleteModal() {
    if (deleteModal) deleteModal.classList.remove('open');
    resetDeleteModal();
  }

  function phraseMatches(value) {
    return value.trim() === deletePhrase;
  }

  document.querySelectorAll('.infra-delete-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
      event.preventDefault();
      openDeleteModal(form);
    });
  });

  deleteInput?.addEventListener('input', function() {
    if (deleteConfirmBtn) deleteConfirmBtn.disabled = !phraseMatches(deleteInput.value);
    if (deleteError && phraseMatches(deleteInput.value)) {
      deleteError.textContent = '';
      deleteError.classList.add('hidden');
    }
  });

  deleteConfirmBtn?.addEventListener('click', function() {
    if (!pendingDeleteForm || !deleteInput) return;
    if (!phraseMatches(deleteInput.value)) {
      if (deleteError) {
        deleteError.textContent = 'Type delete exactly to confirm.';
        deleteError.classList.remove('hidden');
      }
      return;
    }
    var formToSubmit = pendingDeleteForm;
    closeDeleteModal();
    formToSubmit.submit();
  });

  deleteCancelBtn?.addEventListener('click', closeDeleteModal);

  deleteModal?.addEventListener('click', function(event) {
    if (event.target === deleteModal) closeDeleteModal();
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && deleteModal?.classList.contains('open')) {
      closeDeleteModal();
    }
  });

  document.querySelectorAll('.infra-open-modal').forEach(function(btn) {
    btn.addEventListener('click', function() {
      closeModals();
      var modal = document.getElementById(btn.getAttribute('data-modal'));
      if (modal) modal.classList.add('open');
    });
  });
  document.querySelectorAll('.infra-close-modal').forEach(function(btn) {
    btn.addEventListener('click', closeModals);
  });
  document.querySelectorAll('.infra-modal').forEach(function(modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === modal) closeModals();
    });
  });

  var searchInput = document.getElementById('infra-search-input');
  var clearSearch = document.getElementById('infra-search-clear');
  var clearSearchUrl = {$clearSearchUrl};

  function syncSearchClearButton() {
    if (!clearSearch || !searchInput) return;
    var hasValue = searchInput.value.trim() !== '';
    clearSearch.classList.toggle('opacity-0', !hasValue);
    clearSearch.classList.toggle('pointer-events-none', !hasValue);
  }

  if (searchInput) {
    syncSearchClearButton();
    searchInput.addEventListener('input', syncSearchClearButton);
  }

  if (clearSearch && searchInput) {
    clearSearch.addEventListener('click', function(event) {
      event.preventDefault();
      window.location.assign(clearSearchUrl);
    });
  }
})();
JS
);
?>
