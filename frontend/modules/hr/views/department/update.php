<?php

/**
 * Update Department (HR module).
 *
 * @var yii\web\View $this
 * @var common\modules\employee\models\Department $model
 * @var common\modules\employee\models\Employee[] $employees
 * @var bool $hasManager
 * @var bool $hasStatus
 * @var string $userName
 * @var string $successMessage
 * @var string $successVisibleClass
 */

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Update Department';

$errorSummary = $model->hasErrors() ? implode("\n", $model->getFirstErrors()) : '';
$errorVisibleClass = $errorSummary !== '' ? '' : 'hidden';

?>

<div class="flex h-screen overflow-hidden">
    <?= $this->render('@frontend/modules/hr/views/layouts/_navbar.php', ['currentPage' => 'employees']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('@frontend/modules/hr/views/layouts/_topbar.php', ['pageTitle' => 'Update Department', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="layout-content-container flex flex-col w-full max-w-[900px] mx-auto px-8 py-8 gap-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-slate-900 text-4xl font-black leading-tight tracking-tight">Update Department</h1>
                        <p class="text-slate-500 text-base">Edit department information.</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="<?= Url::to(['/hr/department/index']) ?>"
                           class="flex items-center gap-2 rounded-lg h-10 px-4 bg-white hover:bg-slate-50 text-slate-900 text-sm font-bold shadow-md border border-slate-200 transition-all">
                            <span class="material-symbols-outlined text-lg">arrow_back</span>
                            <span>Back</span>
                        </a>
                    </div>
                </div>

                <div class="<?= Html::encode($successVisibleClass) ?> p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200">
                    <?= Html::encode($successMessage) ?>
                </div>
                <div class="<?= Html::encode($errorVisibleClass) ?> p-4 bg-red-50 text-red-700 rounded-xl border border-red-200 whitespace-pre-line">
                    <?= Html::encode($errorSummary) ?>
                </div>

                <section class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <?= Html::beginForm(['/hr/department/update', 'id' => (int)$model->id], 'post', ['class' => 'space-y-6']) ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col gap-2 md:col-span-2">
                                <label class="text-sm font-semibold text-slate-700">
                                    Department Name <span class="text-red-500">*</span>
                                </label>
                                <?= Html::activeTextInput($model, 'name', [
                                    'required' => true,
                                    'placeholder' => 'Department Name',
                                    'class' => 'w-full rounded-lg border-slate-200 bg-slate-50 focus:border-primary focus:ring-primary h-11',
                                ]) ?>
                            </div>

                            <?php if ($hasManager): ?>
                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label class="text-sm font-semibold text-slate-700">
                                        Head of Department <span class="text-slate-400 font-normal">(Optional)</span>
                                    </label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                                        <input id="dept-manager-combobox" type="text"
                                               autocomplete="off"
                                               placeholder="Search and select head of department..."
                                               class="w-full h-11 pl-10 pr-10 rounded-lg border border-slate-200 bg-slate-50 text-slate-900 text-sm focus:ring-primary focus:border-primary" />
                                        <button type="button" id="dept-manager-clear"
                                                class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                                aria-label="Clear selection">
                                            <span class="material-symbols-outlined text-xl">close</span>
                                        </button>
                                        <div id="dept-manager-menu"
                                             class="hidden absolute mt-2 w-full max-h-60 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg z-50"></div>
                                    </div>

                                    <?= Html::activeDropDownList(
                                        $model,
                                        'manager_id',
                                        ArrayHelper::map($employees, 'id', 'full_name'),
                                        [
                                            'prompt' => 'Select Head of Department',
                                            'id' => 'dept-manager-select',
                                            'class' => 'hidden',
                                        ]
                                    ) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($hasStatus): ?>
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-slate-700">
                                        Status <span class="text-red-500">*</span>
                                    </label>
                                    <?= Html::activeDropDownList($model, 'status', [
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                    ], [
                                        'class' => 'w-full rounded-lg border-slate-200 bg-slate-50 focus:border-primary focus:ring-primary h-11',
                                    ]) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pt-6 border-t border-slate-200 flex justify-end">
                            <button type="submit"
                                    class="flex items-center gap-2 rounded-lg h-11 px-5 bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold shadow-md transition-all">
                                <span class="material-symbols-outlined text-lg">save</span>
                                Save Changes
                            </button>
                        </div>

                        <?= Html::endForm() ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<?php
$this->registerJs(<<<'JS'
(function() {
  var input = document.getElementById('dept-manager-combobox');
  var select = document.getElementById('dept-manager-select');
  var menu = document.getElementById('dept-manager-menu');
  var clearBtn = document.getElementById('dept-manager-clear');
  if (!input || !select || !menu || !clearBtn) return;

  var items = Array.prototype.slice.call(select.options)
    .filter(function(o) { return o.value !== ''; })
    .map(function(o) { return { value: o.value, label: o.text }; });

  function openMenu() { menu.classList.remove('hidden'); }
  function closeMenu() { menu.classList.add('hidden'); }
  function setValue(value, label) {
    select.value = String(value || '');
    input.value = label || '';
    clearBtn.classList.toggle('hidden', !select.value);
    closeMenu();
  }
  function clearValue() { setValue('', ''); }

  function render(query) {
    var q = String(query || '').toLowerCase().trim();
    var filtered = q ? items.filter(function(it){ return it.label.toLowerCase().startsWith(q); }) : items.slice(0, 20);
    menu.innerHTML = '';

    if (!filtered.length) {
      var empty = document.createElement('div');
      empty.className = 'px-4 py-3 text-sm text-slate-500';
      empty.textContent = 'No matches';
      menu.appendChild(empty);
      return;
    }

    for (var i = 0; i < filtered.length; i++) {
      (function(it) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'w-full text-left px-4 py-2 text-sm hover:bg-slate-50 transition-colors';
        btn.textContent = it.label;
        btn.addEventListener('click', function() { setValue(it.value, it.label); });
        menu.appendChild(btn);
      })(filtered[i]);
    }
  }

  // initialize from current selected value
  if (select.value) {
    var selected = items.find(function(it){ return String(it.value) === String(select.value); });
    if (selected) setValue(selected.value, selected.label);
  }

  input.addEventListener('focus', function() {
    render(input.value);
    openMenu();
  });
  input.addEventListener('input', function() {
    render(input.value);
    openMenu();
  });
  input.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeMenu();
  });
  clearBtn.addEventListener('click', function() {
    clearValue();
    input.focus();
    render('');
    openMenu();
  });

  document.addEventListener('click', function(e) {
    if (!e.target.closest('#dept-manager-menu') && !e.target.closest('#dept-manager-combobox') && !e.target.closest('#dept-manager-clear')) {
      closeMenu();
    }
  });
})();
JS);
?>

