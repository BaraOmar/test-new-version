<?php

/**
 * @var yii\web\View $this
 * @var array<string, mixed> $schedule
 * @var array<int, string> $dayNames
 */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'My Schedule | LMS';

$entries = $schedule['entries'] ?? [];
$rows = $schedule['rows'] ?? [];
$columns = $schedule['columns'] ?? [];
$cells = $schedule['cells'] ?? [];
$teacherName = trim((string)($schedule['teacher_name'] ?? ''));
$departmentName = trim((string)($schedule['department_name'] ?? ''));

$sessionError = (string)Yii::$app->session->getFlash('error', '');
$saveLessonBriefUrl = Url::to(['/lms/timetable/save-lesson-brief']);
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;

$this->registerCss(<<<'CSS'
.tt-instructor-slot { border-left: 4px solid #1e3a8a; }
.tt-instructor-slot--editable { cursor: pointer; }
.tt-instructor-slot--editable:hover { border-color: #cbd5e1; background: #fafafa; }
.tt-instructor-grid-cell { min-width: 10rem; min-height: 5.5rem; vertical-align: top; }
.tt-slot-note { font-size: 11px; color: #475569; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #cbd5e1; line-height: 1.35; word-break: break-word; }
.tt-slot-note--empty { color: #94a3b8; font-style: italic; }
.tt-lesson-modal { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
.tt-lesson-modal.hidden { display: none; }
.tt-lesson-modal-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.45); }
.tt-lesson-modal-panel { position: relative; width: min(28rem, 100%); background: #fff; border-radius: 0.75rem; border: 1px solid #e2e8f0; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18); padding: 1.25rem; }
CSS
);

?>
<?= $this->render('../layouts/_page-start') ?>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">My Schedule</h2>
            <p class="text-slate-500 mt-2 text-lg">
                <?php if ($departmentName !== ''): ?>
                    Teaching periods assigned to you in <span class="font-semibold text-slate-700"><?= Html::encode($departmentName) ?></span>.
                <?php else: ?>
                    View the periods assigned to you across your department.
                <?php endif; ?>
            </p>
        </div>
        <?php if ($teacherName !== ''): ?>
            <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-[#642754]/10 text-[#642754] text-xs font-bold uppercase tracking-wide">
                <?= Html::encode($teacherName) ?>
            </span>
        <?php endif; ?>
    </header>

    <div id="tt-flash" class="hidden p-4 rounded-xl text-sm font-medium mb-4"></div>

    <?php if ($sessionError !== ''): ?>
        <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium"><?= Html::encode($sessionError) ?></div>
    <?php endif; ?>

    <?php if ($entries === []): ?>
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
            <span class="material-symbols-outlined text-5xl text-slate-300">event_busy</span>
            <h3 class="text-lg font-bold text-slate-900 mt-4">No assigned periods yet</h3>
            <p class="text-sm text-slate-500 mt-2 max-w-lg mx-auto">
                When your department head or deputy assigns you to timetable periods, they will appear here across all grades and sections in your department.
            </p>
        </section>
    <?php else: ?>
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">Weekly Timetable</h3>
                <p class="text-xs text-slate-500 mt-1">Click a period to add or edit your lesson note. Students can see these notes on their schedule.</p>
            </div>
            <div class="overflow-x-auto p-5">
                <?php if ($rows === [] || $columns === []): ?>
                    <p class="py-12 text-center text-slate-400 text-sm">Unable to build timetable grid from assigned periods.</p>
                <?php else: ?>
                    <table class="w-full text-sm border-collapse min-w-[48rem]">
                        <thead>
                            <tr>
                                <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50/80 w-36">Day</th>
                                <?php foreach ($columns as $column): ?>
                                    <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50/80">
                                        <div><?= Html::encode((string)($column['period_label'] ?? '')) ?></div>
                                        <div class="font-normal normal-case tracking-normal text-slate-500 mt-1"><?= Html::encode((string)($column['time_range'] ?? '')) ?></div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <?php $dayOfWeek = (int)($row['day_of_week'] ?? 0); ?>
                                <tr>
                                    <td class="px-3 py-3 align-top border-b border-slate-100 font-semibold text-slate-800 bg-slate-50/50">
                                        <?= Html::encode((string)($row['day_label'] ?? ($dayNames[$dayOfWeek] ?? 'Day'))) ?>
                                    </td>
                                    <?php foreach ($columns as $column): ?>
                                        <?php
                                        $columnKey = (string)($column['key'] ?? '');
                                        $cell = $cells[$dayOfWeek . ':' . $columnKey] ?? null;
                                        $lessonBrief = trim((string)($cell['lesson_brief'] ?? ''));
                                        $slotId = (int)($cell['id'] ?? 0);
                                        ?>
                                        <td class="px-3 py-3 align-top border-b border-slate-100 tt-instructor-grid-cell">
                                            <?php if ($cell !== null && $slotId > 0): ?>
                                                <button type="button"
                                                        class="tt-instructor-slot tt-instructor-slot--editable rounded-lg bg-white border border-slate-200 p-3 shadow-sm h-full w-full text-left"
                                                        data-slot-id="<?= $slotId ?>"
                                                        data-subject-name="<?= Html::encode((string)($cell['subject_name'] ?? '')) ?>"
                                                        data-class-label="<?= Html::encode((string)($cell['class_label'] ?? '')) ?>"
                                                        data-lesson-brief="<?= Html::encode($lessonBrief) ?>">
                                                    <p class="font-bold text-slate-900"><?= Html::encode((string)($cell['subject_name'] ?? '')) ?></p>
                                                    <p class="text-xs text-slate-600 mt-1"><?= Html::encode((string)($cell['class_label'] ?? '')) ?></p>
                                                    <?php if ($lessonBrief !== ''): ?>
                                                        <p class="tt-slot-note"><?= Html::encode($lessonBrief) ?></p>
                                                    <?php else: ?>
                                                        <p class="tt-slot-note tt-slot-note--empty">Add lesson note…</p>
                                                    <?php endif; ?>
                                                </button>
                                            <?php else: ?>
                                                <div class="rounded-lg border border-dashed border-slate-200 min-h-[5.5rem] flex items-center justify-center text-slate-300">
                                                    <span class="text-xs text-slate-400">—</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

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
            <p class="text-xs text-slate-500 mb-3">Add a short note about what you will teach in this period (max 15 words / 100 characters). Students can see it on their schedule.</p>
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
<?= $this->render('../layouts/_page-end') ?>

<?php
$configJson = Json::encode([
    'saveLessonBriefUrl' => $saveLessonBriefUrl,
    'csrfParam' => $csrfParam,
    'csrfToken' => $csrfToken,
]);

$this->registerJs(<<<JS
(function() {
  var config = {$configJson};
  var modal = document.getElementById('tt-lesson-modal');
  var backdrop = document.getElementById('tt-lesson-modal-backdrop');
  var closeBtn = document.getElementById('tt-lesson-modal-close');
  var cancelBtn = document.getElementById('tt-lesson-modal-cancel');
  var saveBtn = document.getElementById('tt-lesson-modal-save');
  var input = document.getElementById('tt-lesson-brief-input');
  var subtitle = document.getElementById('tt-lesson-modal-subtitle');
  var wordCountEl = document.getElementById('tt-lesson-word-count');
  var charCountEl = document.getElementById('tt-lesson-char-count');
  var flashEl = document.getElementById('tt-flash');
  var activeSlotId = 0;
  var activeTrigger = null;
  var maxWords = 15;
  var maxChars = 100;

  function showFlash(message, type) {
    if (!flashEl) return;
    flashEl.textContent = message;
    flashEl.className = 'p-4 rounded-xl text-sm font-medium mb-4 ' + (type === 'error'
      ? 'bg-red-50 text-red-800 border border-red-200'
      : 'bg-emerald-50 text-emerald-800 border border-emerald-200');
    flashEl.classList.remove('hidden');
    window.setTimeout(function() { flashEl.classList.add('hidden'); }, type === 'error' ? 10000 : 5000);
  }

  function countWords(text) {
    return String(text || '').trim().split(/\\s+/).filter(Boolean).length;
  }

  function updateCounters() {
    var text = input ? input.value : '';
    if (wordCountEl) wordCountEl.textContent = countWords(text) + ' / ' + maxWords + ' words';
    if (charCountEl) charCountEl.textContent = String(text).length + ' / ' + maxChars;
  }

  function enforceLimits() {
    if (!input) return;
    var value = input.value || '';
    if (value.length > maxChars) {
      value = value.slice(0, maxChars);
    }
    var words = value.trim().split(/\\s+/).filter(Boolean);
    if (words.length > maxWords) {
      value = words.slice(0, maxWords).join(' ');
      if (value.length > maxChars) {
        value = value.slice(0, maxChars);
      }
    }
    input.value = value;
    updateCounters();
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    activeSlotId = 0;
    activeTrigger = null;
    if (input) input.value = '';
    updateCounters();
  }

  function openModal(trigger) {
    if (!modal || !input || !trigger) return;
    activeTrigger = trigger;
    activeSlotId = parseInt(trigger.getAttribute('data-slot-id') || '0', 10);
    var subject = trigger.getAttribute('data-subject-name') || '';
    var classLabel = trigger.getAttribute('data-class-label') || '';
    if (subtitle) {
      subtitle.textContent = [subject, classLabel].filter(Boolean).join(' · ');
    }
    input.value = trigger.getAttribute('data-lesson-brief') || '';
    updateCounters();
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    input.focus();
  }

  function updateTriggerNote(text) {
    if (!activeTrigger) return;
    activeTrigger.setAttribute('data-lesson-brief', text);
    var noteEl = activeTrigger.querySelector('.tt-slot-note');
    if (!noteEl) return;
    if (text) {
      noteEl.textContent = text;
      noteEl.classList.remove('tt-slot-note--empty');
    } else {
      noteEl.textContent = 'Add lesson note…';
      noteEl.classList.add('tt-slot-note--empty');
    }
  }

  function saveLessonBrief() {
    if (!activeSlotId || !saveBtn) return;
    var text = (input ? input.value : '').trim();
    if (text.length > maxChars) {
      showFlash('Lesson note is too long (max 100 characters).', 'error');
      return;
    }
    if (countWords(text) > maxWords) {
      showFlash('Lesson note is too long (max 15 words).', 'error');
      return;
    }
    saveBtn.disabled = true;
    var body = new URLSearchParams();
    body.append(config.csrfParam, config.csrfToken);
    body.append('slot_id', String(activeSlotId));
    body.append('lesson_brief', text);
    fetch(config.saveLessonBriefUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: body.toString()
    })
      .then(function(response) { return response.json(); })
      .then(function(data) {
        if (!data.success) {
          showFlash((data.errors || []).join(' '), 'error');
          return;
        }
        var savedText = data.slot && data.slot.lesson_brief ? data.slot.lesson_brief : '';
        updateTriggerNote(savedText);
        showFlash(data.message || 'Lesson note saved.', 'success');
        closeModal();
      })
      .catch(function() {
        showFlash('Unable to save lesson note.', 'error');
      })
      .finally(function() {
        saveBtn.disabled = false;
      });
  }

  document.querySelectorAll('.tt-instructor-slot--editable').forEach(function(trigger) {
    trigger.addEventListener('click', function() {
      openModal(trigger);
    });
  });

  input?.addEventListener('input', enforceLimits);
  backdrop?.addEventListener('click', closeModal);
  closeBtn?.addEventListener('click', closeModal);
  cancelBtn?.addEventListener('click', closeModal);
  saveBtn?.addEventListener('click', saveLessonBrief);
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
      closeModal();
    }
  });
})();
JS
);
