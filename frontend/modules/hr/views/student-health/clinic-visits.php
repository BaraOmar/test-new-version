<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudentClinicVisit $model
 * @var common\modules\employee\models\SchoolStudent[] $students
 * @var common\modules\employee\models\SchoolStudent|null $selectedStudent
 * @var array<string, mixed>|null $studentSummary
 * @var string $summaryUrl
 * @var string $notifyParentsUrl
 * @var string[] $parentNotifyActions
 * @var string $userName
 * @var string $currentPage
 */

use common\modules\employee\models\SchoolStudentClinicVisit;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Clinic Visits | CultureHR';

$summaryJson = Json::encode($studentSummary);
$summaryUrlJson = Json::encode($summaryUrl);
$notifyParentsUrl = $notifyParentsUrl ?? Url::to(['notify-parents']);
$parentNotifyActions = $parentNotifyActions ?? SchoolStudentClinicVisit::parentNotificationActions();
$notifyParentsUrlJson = Json::encode($notifyParentsUrl);
$parentNotifyActionsJson = Json::encode($parentNotifyActions);
$showParentNotifyInitially = SchoolStudentClinicVisit::requiresParentNotification($model->action_taken);
$parentNotifyOnChange = "var b=document.getElementById('parent-notify-btn');if(b){b.classList.toggle('hidden',['parent_contacted','sent_home','referred_hospital'].indexOf(this.value)===-1);}";

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage]) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Clinic Visits', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 py-8 space-y-6">
                <div class="space-y-4">
                    <div>
                        <a href="<?= Html::encode(Url::to(['index'])) ?>" class="text-sm font-semibold text-[#1e3a8a] no-underline inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">arrow_back</span>
                            Back to Student Health
                        </a>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight mt-3">Clinic Visits</h1>
                        <p class="text-slate-500 mt-2 max-w-3xl">Document student clinic visits, medicine given, and emergency cases with clinical precision.</p>
                    </div>
                    <?= $this->render('_clinic-tabs', ['activeTab' => 'record']) ?>
                </div>

                <?php if (Yii::$app->session->hasFlash('success')): ?>
                    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm"><?= Html::encode((string)Yii::$app->session->getFlash('success')) ?></div>
                <?php endif; ?>

                <?php $form = ActiveForm::begin([
                    'id' => 'clinic-visit-form',
                    'options' => ['class' => 'space-y-6'],
                    'fieldConfig' => [
                        'template' => "{label}\n{input}\n{error}",
                        'labelOptions' => ['class' => 'block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2'],
                        'inputOptions' => ['class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm'],
                        'errorOptions' => ['class' => 'text-xs text-red-600 mt-1'],
                    ],
                ]); ?>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
                        <h2 class="text-sm font-bold text-slate-900">Student Selection</h2>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Student</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">person</span>
                                <?= Html::dropDownList('SchoolStudentClinicVisit[school_student_id]', $model->school_student_id, ['' => 'Select student'] + ArrayHelper::map($students, 'id', static fn($s) => $s->getDisplayFullName()), [
                                    'id' => 'clinic-student-select',
                                    'class' => 'w-full h-11 pl-10 pr-3 rounded-lg border border-slate-200 bg-white text-sm',
                                    'required' => true,
                                ]) ?>
                            </div>
                        </div>

                        <div id="clinic-student-summary" class="<?= $studentSummary ? '' : 'hidden' ?>">
                            <?php if ($studentSummary): ?>
                                <?= $this->render('_student-summary-panel', ['summary' => $studentSummary]) ?>
                            <?php endif; ?>
                        </div>
                        <div id="clinic-student-summary-empty" class="<?= $studentSummary ? 'hidden' : '' ?> rounded-lg border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400">
                            Select a student to view allergies, chronic conditions, and emergency contact.
                        </div>
                    </section>

                    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
                        <h2 class="text-sm font-bold text-slate-900">Visit Information</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?= $form->field($model, 'visit_date')->input('date', ['class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm']) ?>
                            <?= $form->field($model, 'time_in')->input('time', ['class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm']) ?>
                        </div>
                        <?= $form->field($model, 'primary_reason')->textInput(['placeholder' => 'e.g. Difficulty breathing, Head injury...']) ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?= $form->field($model, 'visit_type')->dropDownList(SchoolStudentClinicVisit::visitTypeOptions()) ?>
                            <?= $form->field($model, 'status')->dropDownList(SchoolStudentClinicVisit::statusOptions()) ?>
                        </div>
                    </section>

                    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-sm font-bold text-slate-900">Medicine Administration</h2>
                            <div class="inline-flex items-center gap-2.5 select-none">
                                <span id="medicine-toggle-no" class="text-xs font-bold uppercase tracking-wide transition-colors duration-200 <?= $model->medicine_given ? 'text-slate-400' : 'text-slate-800' ?>">No</span>
                                <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer" for="medicine-given-toggle" aria-label="Medicine given">
                                    <input type="checkbox"
                                           id="medicine-given-toggle"
                                           name="medicine_given"
                                           value="1"
                                           role="switch"
                                           class="sr-only peer"
                                           <?= $model->medicine_given ? 'checked' : '' ?>>
                                    <span class="pointer-events-none absolute inset-0 rounded-full bg-slate-300 transition-colors duration-200 ease-in-out peer-checked:bg-[#1e3a8a] peer-focus-visible:ring-2 peer-focus-visible:ring-[#1e3a8a]/30 peer-focus-visible:ring-offset-2"></span>
                                    <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-md transition-transform duration-200 ease-in-out peer-checked:translate-x-5"></span>
                                </label>
                                <span id="medicine-toggle-yes" class="text-xs font-bold uppercase tracking-wide transition-colors duration-200 <?= $model->medicine_given ? 'text-[#1e3a8a]' : 'text-slate-400' ?>">Yes</span>
                            </div>
                        </div>
                        <div id="medicine-fields" class="space-y-4 <?= $model->medicine_given ? '' : 'opacity-50 pointer-events-none' ?>">
                            <?= $form->field($model, 'medicine_name')->textInput(['placeholder' => 'e.g. Salbutamol Inhaler']) ?>
                            <?= $form->field($model, 'dosage')->textInput(['placeholder' => '2 puffs']) ?>
                            <?= $form->field($model, 'administration_time')->input('time', ['class' => 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm']) ?>
                            <?= $form->field($model, 'pharmaceutical_notes')->textarea([
                                'rows' => 3,
                                'class' => 'w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm',
                                'placeholder' => 'Student felt immediate relief after 5 minutes...',
                            ]) ?>
                        </div>
                    </section>

                    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
                        <h2 class="text-sm font-bold text-slate-900">Outcome &amp; Care Plan</h2>
                        <?= $form->field($model, 'action_taken')->dropDownList(
                            SchoolStudentClinicVisit::actionTakenOptions(),
                            [
                                'id' => 'clinic-action-taken',
                                'onchange' => $parentNotifyOnChange,
                            ]
                        ) ?>
                        <button type="button"
                                id="parent-notify-btn"
                                class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-bold border-0 cursor-pointer <?= $showParentNotifyInitially ? '' : 'hidden' ?>">
                            <span class="material-symbols-outlined text-base">notifications_active</span>
                            Notify Parents
                        </button>
                        <?= $form->field($model, 'clinical_notes')->textarea([
                            'rows' => 6,
                            'class' => 'w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm',
                            'placeholder' => 'Document thorough observations here. Include vital signs if measured...',
                            'id' => 'clinic-clinical-notes',
                        ]) ?>
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
                    <a href="<?= Html::encode(Url::to(['clinic-visit-records'])) ?>" class="h-10 px-4 inline-flex items-center rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 no-underline">Discard Draft</a>
                    <button type="submit" class="h-10 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold border-0 cursor-pointer">Save and File Record</button>
                </div>

                <?php ActiveForm::end(); ?>

                <div id="parent-notify-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="parent-notify-modal-title">
                    <button type="button" id="parent-notify-backdrop" class="absolute inset-0 bg-slate-900/50 border-0 cursor-pointer" aria-label="Close"></button>
                    <div class="relative w-full max-w-lg bg-white rounded-xl border border-slate-200 shadow-xl p-6 space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 id="parent-notify-modal-title" class="text-lg font-bold text-slate-900">Notify Parents</h3>
                                <p class="text-sm text-slate-500 mt-1">Send an update to the student's guardian about this clinic visit.</p>
                            </div>
                            <button type="button" id="parent-notify-close" class="h-9 w-9 inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 border-solid cursor-pointer" aria-label="Close">
                                <span class="material-symbols-outlined text-lg">close</span>
                            </button>
                        </div>
                        <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 space-y-2 text-sm">
                            <p><span class="text-slate-400 font-bold uppercase text-[10px] tracking-wide">Guardian</span><br><span id="parent-notify-guardian-name" class="font-semibold text-slate-900">—</span></p>
                            <p><span class="text-slate-400 font-bold uppercase text-[10px] tracking-wide">Phone</span><br><span id="parent-notify-guardian-phone" class="text-slate-800">—</span></p>
                            <p><span class="text-slate-400 font-bold uppercase text-[10px] tracking-wide">Email</span><br><span id="parent-notify-guardian-email" class="text-slate-800">—</span></p>
                        </div>
                        <div>
                            <label for="parent-notify-message" class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Message</label>
                            <textarea id="parent-notify-message" rows="8" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"></textarea>
                        </div>
                        <div id="parent-notify-status" class="hidden text-sm rounded-lg px-3 py-2"></div>
                        <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                            <a id="parent-notify-call" href="#" class="hidden h-10 px-4 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 no-underline">
                                <span class="material-symbols-outlined text-base">call</span>
                                Call
                            </a>
                            <a id="parent-notify-sms" href="#" class="hidden h-10 px-4 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 no-underline">
                                <span class="material-symbols-outlined text-base">sms</span>
                                SMS
                            </a>
                            <button type="button" id="parent-notify-send" class="h-10 px-4 inline-flex items-center gap-2 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold border-0 cursor-pointer">
                                <span class="material-symbols-outlined text-base">mail</span>
                                Send Email
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php
$this->registerJs(<<<JS
(function() {
  var studentSelect = document.getElementById('clinic-student-select');
  var summaryBox = document.getElementById('clinic-student-summary');
  var summaryEmpty = document.getElementById('clinic-student-summary-empty');
  var summaryUrl = {$summaryUrlJson};
  var medicineToggle = document.getElementById('medicine-given-toggle');
  var medicineFields = document.getElementById('medicine-fields');
  var medicineToggleNo = document.getElementById('medicine-toggle-no');
  var medicineToggleYes = document.getElementById('medicine-toggle-yes');

  function syncMedicineToggle() {
    if (!medicineToggle || !medicineFields) return;
    var on = medicineToggle.checked;
    medicineFields.classList.toggle('opacity-50', !on);
    medicineFields.classList.toggle('pointer-events-none', !on);
    if (medicineToggleNo) {
      medicineToggleNo.classList.toggle('text-slate-800', !on);
      medicineToggleNo.classList.toggle('text-slate-400', on);
    }
    if (medicineToggleYes) {
      medicineToggleYes.classList.toggle('text-[#1e3a8a]', on);
      medicineToggleYes.classList.toggle('text-slate-400', !on);
    }
    medicineToggle.setAttribute('aria-checked', on ? 'true' : 'false');
  }

  var initialSummary = {$summaryJson};
  var currentStudentSummary = initialSummary || null;

  function renderSummary(summary) {
    currentStudentSummary = summary || null;
    if (!summaryBox) return;
    var chronicHtml = (summary.chronic_tags || []).map(function(tag) {
      return '<span class="inline-flex px-2 py-1 rounded text-[10px] font-bold tracking-wide uppercase bg-sky-100 text-sky-700">Chronic: ' + escapeHtml(tag) + '</span>';
    }).join('');
    summaryBox.innerHTML =
      '<div class="rounded-lg border border-slate-200 bg-slate-50 p-4 border-l-4 ' + escapeHtml(summary.alert_border || 'border-l-sky-500') + '">' +
        '<p class="font-bold text-slate-900">' + escapeHtml(summary.name) + '</p>' +
        '<p class="text-xs text-slate-500 mt-1">ID: ' + escapeHtml(summary.student_code) + ' · ' + escapeHtml(summary.class_label) + '</p>' +
        '<div class="mt-3 space-y-2">' +
          '<div><p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Allergies</p><span class="inline-flex px-2 py-1 rounded text-[10px] font-bold tracking-wide uppercase ' + escapeHtml(summary.allergy_class) + '">' + escapeHtml(summary.allergy_label) + '</span></div>' +
          '<div><p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Chronic</p><div class="flex flex-wrap gap-2">' + (chronicHtml || '<span class="text-xs text-slate-400">—</span>') + '</div></div>' +
          '<div><p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Emergency Contact</p><p class="text-sm text-slate-700">' + escapeHtml(summary.emergency_contact) + '</p></div>' +
        '</div>' +
      '</div>';
    summaryBox.classList.remove('hidden');
    if (summaryEmpty) summaryEmpty.classList.add('hidden');
  }

  function escapeHtml(value) {
    return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function loadSummary(studentId) {
    if (!studentId) {
      currentStudentSummary = null;
      if (summaryBox) {
        summaryBox.innerHTML = '';
        summaryBox.classList.add('hidden');
      }
      if (summaryEmpty) summaryEmpty.classList.remove('hidden');
      return;
    }
    fetch(summaryUrl + (summaryUrl.indexOf('?') === -1 ? '?' : '&') + 'student_id=' + encodeURIComponent(studentId), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function(response) { return response.json(); })
      .then(function(data) {
        if (data.success && data.summary) renderSummary(data.summary);
      })
      .catch(function() {});
  }

  if (studentSelect) {
    studentSelect.addEventListener('change', function() {
      loadSummary(studentSelect.value);
    });
    if (studentSelect.value) {
      loadSummary(studentSelect.value);
    } else if (initialSummary) {
      renderSummary(initialSummary);
    }
  }

  var parentNotifyActions = {$parentNotifyActionsJson} || [];
  var notifyParentsUrl = {$notifyParentsUrlJson};
  var actionTakenSelect = document.getElementById('clinic-action-taken')
    || document.querySelector('[name="SchoolStudentClinicVisit[action_taken]"]');
  var parentNotifyBtn = document.getElementById('parent-notify-btn');
  var parentNotifyModal = document.getElementById('parent-notify-modal');
  var parentNotifyBackdrop = document.getElementById('parent-notify-backdrop');
  var parentNotifyClose = document.getElementById('parent-notify-close');
  var parentNotifyMessage = document.getElementById('parent-notify-message');
  var parentNotifyStatus = document.getElementById('parent-notify-status');
  var parentNotifySend = document.getElementById('parent-notify-send');
  var parentNotifyCall = document.getElementById('parent-notify-call');
  var parentNotifySms = document.getElementById('parent-notify-sms');
  var parentNotifyGuardianName = document.getElementById('parent-notify-guardian-name');
  var parentNotifyGuardianPhone = document.getElementById('parent-notify-guardian-phone');
  var parentNotifyGuardianEmail = document.getElementById('parent-notify-guardian-email');
  var clinicVisitForm = document.getElementById('clinic-visit-form');

  function fieldValue(name) {
    var el = document.querySelector('[name="SchoolStudentClinicVisit[' + name + ']"]');
    return el ? String(el.value || '').trim() : '';
  }

  function sanitizePhone(value) {
    return String(value || '').replace(/[^\d+]/g, '');
  }

  function requiresParentNotification(action) {
    var actions = (parentNotifyActions && parentNotifyActions.length)
      ? parentNotifyActions
      : ['parent_contacted', 'sent_home', 'referred_hospital'];
    return actions.indexOf(String(action || '')) !== -1;
  }

  function syncParentNotifyPanel() {
    if (!parentNotifyBtn || !actionTakenSelect) return;
    var show = requiresParentNotification(actionTakenSelect.value);
    parentNotifyBtn.classList.toggle('hidden', !show);
  }

  function buildDefaultParentMessage(summary) {
    var guardianName = summary && summary.guardian_name ? summary.guardian_name : 'Parent/Guardian';
    var studentName = summary && summary.name ? summary.name : 'the student';
    var studentCode = summary && summary.student_code ? summary.student_code : '';
    var actionSelect = actionTakenSelect;
    var actionLabel = actionSelect && actionSelect.options[actionSelect.selectedIndex]
      ? actionSelect.options[actionSelect.selectedIndex].text
      : 'Clinic visit update';
    var lines = [
      'Dear ' + guardianName + ',',
      '',
      'This is a clinic visit update for ' + studentName + (studentCode ? ' (' + studentCode + ')' : '') + '.',
      '',
      'Visit date: ' + (fieldValue('visit_date') || '—'),
      'Primary reason: ' + (fieldValue('primary_reason') || '—'),
      'Action taken: ' + actionLabel
    ];
    var notes = fieldValue('clinical_notes');
    if (notes) {
      lines.push('', 'Clinical notes:', notes);
    }
    lines.push('', 'Please contact the school clinic if you have any questions.');
    return lines.join('\n');
  }

  function setParentNotifyStatus(message, type) {
    if (!parentNotifyStatus) return;
    parentNotifyStatus.textContent = message;
    parentNotifyStatus.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-800', 'border', 'border-emerald-200', 'bg-red-50', 'text-red-800', 'border-red-200');
    if (type === 'success') {
      parentNotifyStatus.classList.add('bg-emerald-50', 'text-emerald-800', 'border', 'border-emerald-200');
    } else if (type === 'error') {
      parentNotifyStatus.classList.add('bg-red-50', 'text-red-800', 'border', 'border-red-200');
    }
  }

  function openParentNotifyModal() {
    if (!studentSelect || !studentSelect.value) {
      window.alert('Please select a student first.');
      return;
    }
    if (!requiresParentNotification(actionTakenSelect ? actionTakenSelect.value : '')) {
      return;
    }
    var summary = currentStudentSummary || {};
    if (parentNotifyGuardianName) {
      parentNotifyGuardianName.textContent = summary.guardian_name || '—';
    }
    if (parentNotifyGuardianPhone) {
      parentNotifyGuardianPhone.textContent = summary.guardian_phone || '—';
    }
    if (parentNotifyGuardianEmail) {
      parentNotifyGuardianEmail.textContent = summary.notify_email || '—';
    }
    if (parentNotifyMessage) {
      parentNotifyMessage.value = buildDefaultParentMessage(summary);
    }
    var phone = sanitizePhone(summary.guardian_phone || '');
    if (parentNotifyCall) {
      if (phone) {
        parentNotifyCall.href = 'tel:' + phone;
        parentNotifyCall.classList.remove('hidden');
      } else {
        parentNotifyCall.classList.add('hidden');
      }
    }
    if (parentNotifySms) {
      if (phone) {
        parentNotifySms.href = 'sms:' + phone + '?body=' + encodeURIComponent(parentNotifyMessage ? parentNotifyMessage.value : '');
        parentNotifySms.classList.remove('hidden');
      } else {
        parentNotifySms.classList.add('hidden');
      }
    }
    if (parentNotifySend) {
      parentNotifySend.disabled = !(summary.notify_email || '').trim();
      parentNotifySend.classList.toggle('opacity-50', parentNotifySend.disabled);
      parentNotifySend.classList.toggle('cursor-not-allowed', parentNotifySend.disabled);
    }
    setParentNotifyStatus('', '');
    if (parentNotifyStatus) parentNotifyStatus.classList.add('hidden');
    if (parentNotifyModal) {
      parentNotifyModal.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
    }
  }

  function closeParentNotifyModal() {
    if (parentNotifyModal) parentNotifyModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  function getCsrfPair() {
    if (!clinicVisitForm) return { param: '', token: '' };
    var input = clinicVisitForm.querySelector('input[type="hidden"][name*="csrf"]');
    return input ? { param: input.name, token: input.value } : { param: '', token: '' };
  }

  function sendParentNotification() {
    if (!parentNotifySend || parentNotifySend.disabled) return;
    var csrf = getCsrfPair();
    var payload = new URLSearchParams();
    payload.set('student_id', studentSelect ? studentSelect.value : '');
    payload.set('action_taken', actionTakenSelect ? actionTakenSelect.value : '');
    payload.set('visit_date', fieldValue('visit_date'));
    payload.set('primary_reason', fieldValue('primary_reason'));
    payload.set('clinical_notes', fieldValue('clinical_notes'));
    payload.set('message', parentNotifyMessage ? parentNotifyMessage.value : '');
    if (csrf.param && csrf.token) {
      payload.set(csrf.param, csrf.token);
    }
    parentNotifySend.disabled = true;
    fetch(notifyParentsUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: payload.toString()
    })
      .then(function(response) { return response.json(); })
      .then(function(data) {
        setParentNotifyStatus(data.message || (data.success ? 'Notification sent.' : 'Unable to send notification.'), data.success ? 'success' : 'error');
        if (parentNotifyStatus) parentNotifyStatus.classList.remove('hidden');
      })
      .catch(function() {
        setParentNotifyStatus('Unable to send notification. Please try calling the guardian directly.', 'error');
        if (parentNotifyStatus) parentNotifyStatus.classList.remove('hidden');
      })
      .finally(function() {
        parentNotifySend.disabled = !(currentStudentSummary && currentStudentSummary.notify_email);
        parentNotifySend.classList.toggle('opacity-50', parentNotifySend.disabled);
        parentNotifySend.classList.toggle('cursor-not-allowed', parentNotifySend.disabled);
      });
  }

  if (actionTakenSelect) {
    actionTakenSelect.addEventListener('change', syncParentNotifyPanel);
    syncParentNotifyPanel();
  }
  if (parentNotifyBtn) parentNotifyBtn.addEventListener('click', openParentNotifyModal);
  if (parentNotifyBackdrop) parentNotifyBackdrop.addEventListener('click', closeParentNotifyModal);
  if (parentNotifyClose) parentNotifyClose.addEventListener('click', closeParentNotifyModal);
  if (parentNotifySend) parentNotifySend.addEventListener('click', sendParentNotification);
  if (parentNotifyMessage && parentNotifySms) {
    parentNotifyMessage.addEventListener('input', function() {
      var phone = sanitizePhone(currentStudentSummary && currentStudentSummary.guardian_phone ? currentStudentSummary.guardian_phone : '');
      if (phone) {
        parentNotifySms.href = 'sms:' + phone + '?body=' + encodeURIComponent(parentNotifyMessage.value);
      }
    });
  }

  if (medicineToggle && medicineFields) {
    medicineToggle.addEventListener('change', syncMedicineToggle);
    syncMedicineToggle();
  }
})();
JS
, \yii\web\View::POS_END
);
?>
