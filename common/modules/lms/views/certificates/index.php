<?php

/**
 * @var yii\web\View $this
 * @var bool $studentMode
 * @var array<string, mixed> $options
 * @var array<string, mixed>|null $certificate
 */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$studentMode = (bool)$studentMode;
$pageTitle = (string)($options['page_title'] ?? ($studentMode ? 'My Certificate | LMS' : 'Student Certificates | LMS'));
$displayTitle = trim((string)preg_replace('/\s*\|\s*LMS\s*$/i', '', $pageTitle));
$this->title = $displayTitle !== '' ? $displayTitle : ($studentMode ? 'My Certificate' : 'Student Certificates');

$eyebrow = (string)($options['portal_eyebrow'] ?? '');
$subtitle = (string)($options['subtitle'] ?? '');
$scopes = is_array($options['scopes'] ?? null) ? $options['scopes'] : [];
$initialScope = (string)($certificate['scope'] ?? 'all');
$canSelectDepartment = (bool)($options['can_select_department'] ?? false);
$defaultDepartmentId = $options['default_department_id'] ?? false;

$this->registerCssFile('@web/css/lms-certificates.css');

$config = [
    'studentMode' => $studentMode,
    'options' => $options,
    'certificate' => $certificate,
    'rosterUrl' => Url::to(['roster']),
    'dataUrl' => Url::to(['data']),
    'printUrl' => Url::to(['print']),
];
$configJson = Json::htmlEncode($config);

?>
<div class="o_sch_cert lms-portal-page <?= $studentMode ? 'o_sch_cert--student' : 'o_sch_cert--manage' ?>"
     data-sch-cert-version="2026-08-06.1"
     id="lms-certificates-root">
    <div class="lms-portal-container">
        <header class="lms-portal-header o_sch_cert_header">
            <div>
                <p class="lms-portal-eyebrow"><?= Html::encode($eyebrow) ?></p>
                <h1><?= Html::encode($this->title) ?></h1>
                <p class="lms-portal-subtitle"><?= Html::encode($subtitle) ?></p>
            </div>
            <div class="o_sch_cert_header_actions" id="cert-print-wrap" <?= empty($certificate) ? 'hidden' : '' ?>>
                <button type="button" class="lms-btn lms-btn--orange" id="cert-print-btn">
                    <span class="material-symbols-outlined">print</span> Print Certificate
                </button>
            </div>
        </header>

        <?php if (!$studentMode): ?>
            <div class="o_sch_cert_card o_sch_cert_filters">
                <div class="o_sch_cert_filter_grid">
                    <div class="o_sch_cert_filter_field">
                        <label class="o_sch_cert_label" for="cert-department">Department</label>
                        <select class="lms-teacher-select" id="cert-department"
                            <?= (!$canSelectDepartment && $defaultDepartmentId) ? 'disabled' : '' ?>>
                            <option value="">Select department…</option>
                        </select>
                    </div>
                    <div class="o_sch_cert_filter_field">
                        <label class="o_sch_cert_label" for="cert-grade">Grade</label>
                        <select class="lms-teacher-select" id="cert-grade" disabled>
                            <option value="">Select grade…</option>
                        </select>
                    </div>
                    <div class="o_sch_cert_filter_field">
                        <label class="o_sch_cert_label" for="cert-section">Section</label>
                        <select class="lms-teacher-select" id="cert-section" disabled>
                            <option value="">Select section…</option>
                        </select>
                    </div>
                    <div class="o_sch_cert_filter_field">
                        <label class="o_sch_cert_label" for="cert-scope">Coverage Period</label>
                        <select class="lms-teacher-select" id="cert-scope">
                            <?php foreach ($scopes as $scope): ?>
                                <option value="<?= Html::encode((string)$scope['key']) ?>"
                                    <?= ((string)$scope['key'] === $initialScope) ? 'selected' : '' ?>>
                                    <?= Html::encode((string)$scope['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="o_sch_cert_layout" id="cert-layout" hidden>
                <div class="o_sch_cert_card o_sch_cert_rank">
                    <h2>Section Ranking</h2>
                    <p class="o_sch_cert_help" id="cert-rank-help">
                        Students ranked from highest to lowest average
                    </p>
                    <div class="o_sch_cert_empty" id="cert-rank-empty" hidden>No active students in this section.</div>
                    <ol class="o_sch_cert_rank_list" id="cert-rank-list"></ol>
                </div>

                <div class="o_sch_cert_card o_sch_cert_preview" id="cert-preview">
                    <div class="o_sch_cert_empty" id="cert-preview-empty">
                        Select a student from the ranking list to preview their certificate.
                    </div>
                    <?= $this->render('_card') ?>
                </div>
            </div>
        <?php else: ?>
            <div class="o_sch_cert_card o_sch_cert_filters o_sch_cert_filters--student">
                <div class="o_sch_cert_filter_field">
                    <label class="o_sch_cert_label" for="cert-scope">Coverage Period</label>
                    <select class="lms-teacher-select o_sch_cert_scope_only" id="cert-scope">
                        <?php foreach ($scopes as $scope): ?>
                            <option value="<?= Html::encode((string)$scope['key']) ?>"
                                <?= ((string)$scope['key'] === $initialScope) ? 'selected' : '' ?>>
                                <?= Html::encode((string)$scope['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="o_sch_cert_preview o_sch_cert_preview--student" id="cert-preview">
                <?= $this->render('_card') ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$this->registerJs('window.LMS_CERT_CONFIG = ' . $configJson . ';', \yii\web\View::POS_HEAD);
$this->registerJs(<<<'JS'
(function () {
  var cfg = window.LMS_CERT_CONFIG || {};
  var studentMode = !!cfg.studentMode;
  var options = cfg.options || {};
  var state = {
    departmentId: options.default_department_id ? String(options.default_department_id) : '',
    gradeId: '',
    sectionId: '',
    studentId: '',
    scope: (cfg.certificate && cfg.certificate.scope) || 'all',
    roster: [],
    sectionMeta: null,
    certificate: cfg.certificate || null,
    saving: false
  };

  var els = {
    department: document.getElementById('cert-department'),
    grade: document.getElementById('cert-grade'),
    section: document.getElementById('cert-section'),
    scope: document.getElementById('cert-scope'),
    layout: document.getElementById('cert-layout'),
    rankList: document.getElementById('cert-rank-list'),
    rankEmpty: document.getElementById('cert-rank-empty'),
    rankHelp: document.getElementById('cert-rank-help'),
    previewEmpty: document.getElementById('cert-preview-empty'),
    preview: document.getElementById('cert-preview'),
    printWrap: document.getElementById('cert-print-wrap'),
    printBtn: document.getElementById('cert-print-btn'),
    card: document.getElementById('cert-card')
  };

  function notify(message, type) {
    var existing = document.querySelector('.lms-cert-toast');
    if (existing) existing.remove();
    var toast = document.createElement('div');
    toast.className = 'lms-cert-toast lms-cert-toast--' + (type || 'danger');
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(function () {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 3500);
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function gradeClass(letter) {
    if (!letter || letter === '—') return '';
    if (String(letter).indexOf('A') === 0) return 'is-a';
    if (String(letter).indexOf('B') === 0) return 'is-b';
    return 'is-other';
  }

  function gradesForDepartment() {
    var deptId = Number(state.departmentId || 0);
    if (!deptId) return [];
    return (options.grades || []).filter(function (g) {
      return Number(g.department_id) === deptId;
    });
  }

  function sectionsForGrade() {
    var grade = (options.grades || []).find(function (g) {
      return Number(g.id) === Number(state.gradeId);
    });
    return (grade && grade.sections) || [];
  }

  function fillSelect(select, placeholder, rows, valueKey, labelKey, selected) {
    if (!select) return;
    var html = '<option value="">' + escapeHtml(placeholder) + '</option>';
    (rows || []).forEach(function (row) {
      var value = String(row[valueKey]);
      html += '<option value="' + escapeHtml(value) + '"' + (String(selected) === value ? ' selected' : '') + '>'
        + escapeHtml(row[labelKey]) + '</option>';
    });
    select.innerHTML = html;
  }

  function renderFilters() {
    if (studentMode) return;
    fillSelect(els.department, 'Select department…', options.departments || [], 'id', 'name', state.departmentId);
    if (els.department) {
      els.department.disabled = !options.can_select_department && !!state.departmentId;
    }
    var grades = gradesForDepartment();
    fillSelect(els.grade, 'Select grade…', grades, 'id', 'name', state.gradeId);
    if (els.grade) els.grade.disabled = !state.departmentId;
    var sections = sectionsForGrade();
    fillSelect(els.section, 'Select section…', sections, 'id', 'display_name', state.sectionId);
    if (els.section) els.section.disabled = !state.gradeId;
  }

  function renderRoster() {
    if (studentMode || !els.layout) return;
    if (!state.sectionId) {
      els.layout.hidden = true;
      return;
    }
    els.layout.hidden = false;
    var help = 'Students ranked from highest to lowest average';
    if (state.sectionMeta && state.sectionMeta.scope_label) {
      help += ' · ' + state.sectionMeta.scope_label;
    }
    if (els.rankHelp) els.rankHelp.textContent = help;
    if (!state.roster.length) {
      if (els.rankEmpty) els.rankEmpty.hidden = false;
      if (els.rankList) els.rankList.innerHTML = '';
      return;
    }
    if (els.rankEmpty) els.rankEmpty.hidden = true;
    if (!els.rankList) return;
    els.rankList.innerHTML = state.roster.map(function (row) {
      var active = String(state.studentId) === String(row.student_id) ? ' is-active' : '';
      return '<li class="' + active.trim() + '">'
        + '<button type="button" class="o_sch_cert_rank_btn" data-student-id="' + escapeHtml(row.student_id) + '">'
        + '<span class="o_sch_cert_rank_num">' + escapeHtml(row.rank || '—') + '</span>'
        + '<span class="o_sch_cert_rank_body"><strong>' + escapeHtml(row.student_name) + '</strong>'
        + '<span>' + escapeHtml(row.student_code || '') + '</span></span>'
        + '<span class="o_sch_cert_rank_avg">' + escapeHtml(row.average_label) + '</span>'
        + '</button></li>';
    }).join('');
  }

  function renderCertificate() {
    var cert = state.certificate;
    if (els.printWrap) els.printWrap.hidden = !cert;
    if (els.previewEmpty) els.previewEmpty.hidden = !!cert;
    if (!els.card) return;
    if (!cert) {
      els.card.hidden = true;
      return;
    }
    els.card.hidden = false;
    els.card.querySelector('[data-cert="school_name"]').textContent = cert.school_name || '';
    els.card.querySelector('[data-cert="school_tagline"]').textContent = cert.school_tagline || '';
    els.card.querySelector('[data-cert="title"]').textContent = cert.title || '';
    var meta = cert.scope_label || '';
    if (cert.academic_session_label) meta += ' · ' + cert.academic_session_label;
    els.card.querySelector('[data-cert="doc_meta"]').textContent = meta;
    var student = cert.student || {};
    els.card.querySelector('[data-cert="student_name"]').textContent = student.name || '';
    els.card.querySelector('[data-cert="class_label"]').textContent = student.class_label || '';
    var codeHtml = 'Reg. No. ' + escapeHtml(student.student_code || '—');
    if (cert.rank) {
      codeHtml += '<br>Rank ' + escapeHtml(cert.rank) + ' / ' + escapeHtml(cert.section_size);
    }
    els.card.querySelector('[data-cert="student_code_block"]').innerHTML = codeHtml;
    var subjects = cert.subjects || [];
    var body = els.card.querySelector('[data-cert="subjects"]');
    if (!subjects.length) {
      body.innerHTML = '<tr><td colspan="4" class="o_sch_cert_empty">No subjects assigned to this section yet.</td></tr>';
    } else {
      body.innerHTML = subjects.map(function (row) {
        return '<tr><td class="o_sch_cert_subject">' + escapeHtml(row.subject_name) + '</td>'
          + '<td>' + escapeHtml(row.marks_label) + '</td>'
          + '<td><span class="o_sch_cert_letter ' + gradeClass(row.grade) + '">' + escapeHtml(row.grade) + '</span></td>'
          + '<td class="o_sch_cert_remark">' + escapeHtml(row.remarks) + '</td></tr>';
      }).join('');
    }
    els.card.querySelector('[data-cert="aggregate_label"]').textContent = cert.aggregate_label || '—';
    els.card.querySelector('[data-cert="average_label"]').textContent = cert.average_label || '—';
    els.card.querySelector('[data-cert="attendance_label"]').textContent = cert.attendance_label || '—';
    var note = els.card.querySelector('[data-cert="readonly_note"]');
    if (note) note.hidden = !(cert.read_only || studentMode);
    if (els.preview) {
      if (state.saving) els.preview.classList.add('o_sch_cert_preview--busy');
      else els.preview.classList.remove('o_sch_cert_preview--busy');
    }
  }

  function fetchJson(url) {
    return fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (response) {
      return response.json().then(function (payload) {
        if (!response.ok || (payload && payload.ok === false)) {
          throw new Error((payload && payload.message) || 'Request failed.');
        }
        return payload;
      });
    });
  }

  function loadRoster() {
    if (!state.sectionId) return Promise.resolve();
    var url = cfg.rosterUrl + (cfg.rosterUrl.indexOf('?') >= 0 ? '&' : '?')
      + 'section_id=' + encodeURIComponent(state.sectionId)
      + '&scope=' + encodeURIComponent(state.scope);
    return fetchJson(url).then(function (data) {
      state.roster = data.students || [];
      state.sectionMeta = {
        grade_name: data.grade_name,
        section_name: data.section_name,
        scope_label: data.scope_label
      };
      renderRoster();
    }).catch(function (err) {
      notify(err.message || 'Could not load section ranking.', 'danger');
    });
  }

  function loadCertificate(studentId) {
    var url = cfg.dataUrl + (cfg.dataUrl.indexOf('?') >= 0 ? '&' : '?')
      + 'scope=' + encodeURIComponent(state.scope);
    if (studentId) url += '&student_id=' + encodeURIComponent(studentId);
    return fetchJson(url).then(function (data) {
      state.certificate = data;
      if (data.scope) state.scope = data.scope;
      renderCertificate();
    }).catch(function (err) {
      notify(err.message || 'Could not load certificate.', 'danger');
    });
  }

  function onDepartmentChange() {
    state.departmentId = els.department.value || '';
    state.gradeId = '';
    state.sectionId = '';
    state.studentId = '';
    state.roster = [];
    state.certificate = null;
    state.sectionMeta = null;
    renderFilters();
    renderRoster();
    renderCertificate();
  }

  function onGradeChange() {
    state.gradeId = els.grade.value || '';
    state.sectionId = '';
    state.studentId = '';
    state.roster = [];
    state.certificate = null;
    state.sectionMeta = null;
    renderFilters();
    renderRoster();
    renderCertificate();
  }

  function onSectionChange() {
    state.sectionId = els.section.value || '';
    state.studentId = '';
    state.certificate = null;
    if (state.sectionId) {
      loadRoster().then(renderCertificate);
    } else {
      state.roster = [];
      state.sectionMeta = null;
      renderRoster();
      renderCertificate();
    }
  }

  function onScopeChange() {
    state.scope = els.scope.value || 'all';
    if (studentMode) {
      state.saving = true;
      renderCertificate();
      if (els.scope) els.scope.disabled = true;
      loadCertificate(null).finally(function () {
        state.saving = false;
        if (els.scope) els.scope.disabled = false;
        renderCertificate();
      });
      return;
    }
    state.certificate = null;
    if (state.sectionId) {
      loadRoster().then(function () {
        if (state.studentId) return loadCertificate(state.studentId);
        renderCertificate();
      });
    } else {
      renderCertificate();
    }
  }

  function printCertificate() {
    var studentId = (state.certificate && state.certificate.student && state.certificate.student.id)
      || Number(state.studentId)
      || 0;
    if (!studentMode && !studentId) return;
    var url = cfg.printUrl + (cfg.printUrl.indexOf('?') >= 0 ? '&' : '?')
      + 'scope=' + encodeURIComponent(state.scope);
    if (studentId) url += '&student_id=' + encodeURIComponent(studentId);
    fetchJson(url).then(function (result) {
      var win = window.open('', '_blank');
      if (!win) {
        notify('Please allow pop-ups to print the certificate.', 'warning');
        return;
      }
      win.document.open();
      win.document.write(result.html || '');
      win.document.close();
      win.document.title = result.title || (state.certificate && state.certificate.title) || 'Academic Report Card';
      win.focus();
      var triggerPrint = function () {
        try { win.print(); } catch (err) {}
      };
      if (win.document.readyState === 'complete') {
        setTimeout(triggerPrint, 350);
      } else {
        win.addEventListener('load', function () { setTimeout(triggerPrint, 200); });
        setTimeout(triggerPrint, 800);
      }
    }).catch(function (err) {
      notify(err.message || 'Could not print certificate.', 'danger');
    });
  }

  if (els.department) els.department.addEventListener('change', onDepartmentChange);
  if (els.grade) els.grade.addEventListener('change', onGradeChange);
  if (els.section) els.section.addEventListener('change', onSectionChange);
  if (els.scope) els.scope.addEventListener('change', onScopeChange);
  if (els.printBtn) els.printBtn.addEventListener('click', printCertificate);
  if (els.rankList) {
    els.rankList.addEventListener('click', function (ev) {
      var btn = ev.target.closest('[data-student-id]');
      if (!btn) return;
      state.studentId = String(btn.getAttribute('data-student-id') || '');
      renderRoster();
      loadCertificate(state.studentId);
    });
  }

  renderFilters();
  renderCertificate();
})();
JS
, \yii\web\View::POS_END);
