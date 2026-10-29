<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolSection $section
 * @var common\modules\employee\models\SchoolSectionMaterial $material
 * @var array<string, mixed> $card
 * @var string $subjectLabel
 * @var common\modules\employee\models\SchoolLmsCourseTopic[] $topics
 * @var bool $canManage
 * @var bool $storageReady
 * @var string $acceptAttribute
 * @var string[] $allowedExtensions
 * @var string $maxSizeLabel
 */

use common\modules\employee\services\SchoolLmsCourseMaterialsService;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Learning Materials | My Classes';

$gradeName = trim((string)($section->grade?->name ?? ''));
$sectionName = trim((string)$section->name);
$sectionLabel = $gradeName !== '' ? $gradeName . ' · ' . $sectionName : $sectionName;
$courseLabel = trim($subjectLabel) !== '' ? $subjectLabel : 'Course';
$successMessage = (string)Yii::$app->session->getFlash('success', '');
$errorMessage = (string)Yii::$app->session->getFlash('error', '');
$topics = $topics ?? [];
$canManage = (bool)($canManage ?? false);
$storageReady = (bool)($storageReady ?? false);
$acceptAttribute = (string)($acceptAttribute ?? '');
$allowedLabel = implode(', ', array_map('strtoupper', $allowedExtensions ?? []));
$maxSizeLabel = (string)($maxSizeLabel ?? '500 MB');
$maxUploadBytes = (int)SchoolLmsCourseMaterialsService::MAX_UPLOAD_SIZE;
$courseStorageKey = 'lms-topic-open-' . (int)$material->id;

$this->registerCss(<<<'CSS'
.lms-materials { display: flex; flex-direction: column; gap: 1.75rem; }
.lms-materials-crumb {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8125rem;
  color: #64748b;
}
.lms-materials-crumb a { color: #4a2a51; font-weight: 600; text-decoration: none; }
.lms-materials-crumb a:hover { text-decoration: underline; }
.lms-materials-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1.25rem;
}
.lms-materials-kicker {
  margin: 0;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #eb652c;
}
.lms-materials-title {
  margin: 0.35rem 0 0;
  font-size: 1.85rem;
  line-height: 1.2;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: #0f172a;
}
.lms-materials-meta { margin: 0.4rem 0 0; font-size: 0.925rem; color: #64748b; }
.lms-add-topic {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.lms-add-topic input {
  height: 2.6rem;
  min-width: 16rem;
  padding: 0 0.9rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.7rem;
  background: #fff;
  font-size: 0.875rem;
}
.lms-add-topic input:focus {
  outline: none;
  border-color: #4a2a51;
  box-shadow: 0 0 0 3px rgba(74, 42, 81, 0.12);
}
.lms-add-topic button,
.lms-btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  height: 2.6rem;
  padding: 0 1rem;
  border: 0;
  border-radius: 0.7rem;
  background: #1e3a8a;
  color: #fff;
  font-size: 0.8125rem;
  font-weight: 700;
  cursor: pointer;
}
.lms-add-topic button:hover,
.lms-btn-primary:hover { background: #1e40af; }
.lms-flash {
  padding: 0.85rem 1rem;
  border-radius: 0.8rem;
  font-size: 0.875rem;
  font-weight: 600;
}
.lms-flash.is-ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.lms-flash.is-bad { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.lms-topics-shell {
  background: #fff;
  border: 1px solid #e8edf3;
  border-radius: 1rem;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
  overflow: hidden;
}
.lms-topic { border-bottom: 1px solid #eef2f6; }
.lms-topic:last-child { border-bottom: 0; }
.lms-topic-bar {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  min-height: 4.35rem;
  padding: 0.35rem 1.1rem 0.35rem 0.35rem;
}
.lms-topic-head {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  flex: 1;
  min-width: 0;
  min-height: 3.65rem;
  padding: 0.85rem 0.75rem 0.85rem 1.1rem;
  border: 0;
  background: transparent;
  cursor: pointer;
  text-align: left;
}
.lms-topic-bar:hover,
.lms-topic-bar:focus-within { background: #fbfcfe; }
.lms-topic-chevron {
  width: 1.85rem;
  height: 1.85rem;
  border-radius: 999px;
  border: 1px solid #dbe3ee;
  background: #fff;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 1.05rem !important;
  transition: transform 0.18s ease, background 0.18s ease, color 0.18s ease, border-color 0.18s ease;
}
.lms-topic.is-open .lms-topic-chevron {
  transform: rotate(90deg);
  background: #4a2a51;
  border-color: #4a2a51;
  color: #fff;
}
.lms-topic-title {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 1.02rem;
  font-weight: 600;
  color: #1e293b;
}
.lms-topic-count {
  flex-shrink: 0;
  margin-left: 0.35rem;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  background: #f1f5f9;
  color: #64748b;
  font-size: 0.7rem;
  font-weight: 700;
}
.lms-topic-actions {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  padding-right: 0.65rem;
  position: relative;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.15s ease;
}
.lms-topic-bar:hover .lms-topic-actions,
.lms-topic-bar:focus-within .lms-topic-actions,
.lms-topic.is-renaming .lms-topic-actions,
.lms-topic-actions:focus-within {
  opacity: 1;
  pointer-events: auto;
}
@media (hover: none) {
  .lms-topic-actions { opacity: 1; pointer-events: auto; }
}
.lms-icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.1rem;
  height: 2.1rem;
  padding: 0;
  border: 1px solid #e2e8f0;
  border-radius: 0.55rem;
  background: #fff;
  color: #64748b;
  cursor: pointer;
}
.lms-icon-btn:hover { background: #f8fafc; color: #0f172a; }
.lms-icon-btn.is-danger:hover { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
.lms-icon-btn .material-symbols-outlined { font-size: 1.15rem; }
.lms-rename-form { display: none; align-items: center; gap: 0.45rem; flex: 1; min-width: 0; }
.lms-topic.is-renaming .lms-rename-form { display: flex; }
.lms-topic.is-renaming .lms-topic-title,
.lms-topic.is-renaming .lms-topic-count { display: none; }
.lms-rename-form input {
  flex: 1;
  min-width: 0;
  height: 2.25rem;
  padding: 0 0.75rem;
  border: 1px solid #d6dee8;
  border-radius: 0.55rem;
  font-size: 0.9rem;
}
.lms-rename-form input:focus {
  outline: none;
  border-color: #4a2a51;
  box-shadow: 0 0 0 3px rgba(74, 42, 81, 0.12);
}
.lms-rename-save {
  height: 2.25rem;
  padding: 0 0.8rem;
  border: 0;
  border-radius: 0.55rem;
  background: #1e3a8a;
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
}
.lms-upload-form { position: absolute; width: 0; height: 0; overflow: hidden; }
.lms-topic-body { display: none; padding: 0 0 0.85rem; }
.lms-topic.is-open > .lms-topic-body { display: block; }
.lms-file-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-height: 3.35rem;
  margin: 0 1rem 0.35rem 3.55rem;
  padding: 0.7rem 0.85rem;
  border-radius: 0.8rem;
}
.lms-file-row:hover { background: #f8fafc; }
.lms-file-link {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  min-width: 0;
  flex: 1;
  text-decoration: none;
}
.lms-file-icon {
  width: 2.35rem;
  height: 2.35rem;
  border-radius: 0.7rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.lms-file-name { color: #1e3a8a; font-weight: 600; font-size: 0.95rem; }
.lms-file-link:hover .lms-file-name { text-decoration: underline; }
.lms-file-meta { display: block; margin-top: 0.15rem; font-size: 0.72rem; color: #94a3b8; }
.lms-topic-empty {
  margin: 0 1rem 0.2rem 3.55rem;
  padding: 0.85rem 0.2rem 1rem;
  color: #94a3b8;
  font-size: 0.875rem;
}
.lms-topics-empty { padding: 3.5rem 1.5rem; text-align: center; color: #94a3b8; }
.lms-topics-empty .material-symbols-outlined { font-size: 2.75rem; color: #cbd5e1; }
.lms-topics-empty h3 { margin: 0.85rem 0 0; color: #0f172a; font-size: 1.05rem; font-weight: 700; }
.lms-topics-empty p { margin: 0.4rem 0 0; font-size: 0.875rem; }
.lms-materials-hint { margin: 0; font-size: 0.75rem; color: #94a3b8; line-height: 1.55; }
.lms-topic-head:focus-visible,
.lms-icon-btn:focus-visible,
.lms-add-topic button:focus-visible,
.lms-rename-save:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(74, 42, 81, 0.18);
}
CSS
);

?>
<?= $this->render('../layouts/_page-start') ?>
<div class="lms-materials">
    <nav class="lms-materials-crumb" aria-label="Breadcrumb">
        <a href="<?= Url::to(['index']) ?>">My Classes</a>
        <span aria-hidden="true">›</span>
        <a href="<?= Url::to(['view', 'section_id' => (int)$section->id]) ?>"><?= Html::encode($sectionLabel) ?></a>
        <span aria-hidden="true">›</span>
        <span><?= Html::encode($courseLabel) ?></span>
    </nav>

    <header class="lms-materials-header">
        <div>
            <p class="lms-materials-kicker">Learning materials</p>
            <h1 class="lms-materials-title"><?= Html::encode($courseLabel) ?></h1>
            <p class="lms-materials-meta"><?= Html::encode($sectionLabel) ?></p>
        </div>
        <?php if ($canManage && $storageReady): ?>
            <?= Html::beginForm(['create-topic'], 'post', ['class' => 'lms-add-topic']) ?>
                <?= Html::hiddenInput('section_id', (int)$section->id) ?>
                <?= Html::hiddenInput('material_id', (int)$material->id) ?>
                <input type="text" name="name" required maxlength="255" placeholder="Topic name, e.g. Topic 2" aria-label="New topic name">
                <button type="submit">
                    <span class="material-symbols-outlined text-base">add</span>
                    Add topic
                </button>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </header>

    <?php if ($successMessage !== ''): ?>
        <div class="lms-flash is-ok"><?= Html::encode($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <div class="lms-flash is-bad"><?= Html::encode($errorMessage) ?></div>
    <?php endif; ?>

    <?php if (!$storageReady): ?>
        <section class="lms-topics-shell">
            <div class="lms-topics-empty">
                <span class="material-symbols-outlined">database</span>
                <h3>Materials storage is not ready</h3>
                <p>Run <code>php yii migrate/up</code> to enable course topics and file uploads.</p>
            </div>
        </section>
    <?php else: ?>
        <section class="lms-topics-shell" data-open-key="<?= Html::encode($courseStorageKey) ?>" data-max-bytes="<?= $maxUploadBytes ?>">
            <?php if ($topics === []): ?>
                <div class="lms-topics-empty">
                    <span class="material-symbols-outlined">folder_open</span>
                    <h3>No topics yet</h3>
                    <p><?= $canManage ? 'Add a topic above, then upload files into it.' : 'The subject teacher has not added topics yet.' ?></p>
                </div>
            <?php endif; ?>

            <?php foreach ($topics as $topic): ?>
                <?php $fileCount = count($topic->files); ?>
                <article class="lms-topic" id="topic-<?= (int)$topic->id ?>" data-topic-id="<?= (int)$topic->id ?>">
                    <div class="lms-topic-bar">
                        <button type="button"
                                class="lms-topic-head lms-topic-toggle"
                                aria-expanded="false"
                                aria-controls="topic-body-<?= (int)$topic->id ?>">
                            <span class="material-symbols-outlined lms-topic-chevron" aria-hidden="true">play_arrow</span>
                            <span class="lms-topic-title"><?= Html::encode((string)$topic->name) ?></span>
                            <?php if ($fileCount > 0): ?>
                                <span class="lms-topic-count"><?= $fileCount === 1 ? '1 file' : $fileCount . ' files' ?></span>
                            <?php endif; ?>
                        </button>
                        <?php if ($canManage): ?>
                            <?= Html::beginForm(['rename-topic'], 'post', ['class' => 'lms-rename-form']) ?>
                                <?= Html::hiddenInput('section_id', (int)$section->id) ?>
                                <?= Html::hiddenInput('material_id', (int)$material->id) ?>
                                <?= Html::hiddenInput('topic_id', (int)$topic->id) ?>
                                <input type="text" name="name" required maxlength="255" value="<?= Html::encode((string)$topic->name) ?>" aria-label="Topic name">
                                <button type="submit" class="lms-rename-save">Save</button>
                            <?= Html::endForm() ?>
                            <div class="lms-topic-actions">
                                <?= Html::beginForm(['upload-topic-file'], 'post', [
                                    'class' => 'lms-upload-form',
                                    'enctype' => 'multipart/form-data',
                                ]) ?>
                                    <?= Html::hiddenInput('section_id', (int)$section->id) ?>
                                    <?= Html::hiddenInput('material_id', (int)$material->id) ?>
                                    <?= Html::hiddenInput('topic_id', (int)$topic->id) ?>
                                    <input type="file" name="files[]" multiple required accept="<?= Html::encode($acceptAttribute) ?>"
                                           class="lms-file-input" aria-label="Upload files to <?= Html::encode((string)$topic->name) ?>">
                                <?= Html::endForm() ?>
                                <button type="button" class="lms-icon-btn lms-upload-toggle" title="Upload files" aria-label="Upload files">
                                    <span class="material-symbols-outlined">upload_file</span>
                                </button>
                                <button type="button" class="lms-icon-btn lms-rename-toggle" title="Rename topic" aria-label="Rename topic">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                <?= Html::beginForm(['delete-topic'], 'post', [
                                    'class' => 'm-0',
                                    'onsubmit' => 'return confirm("Delete this topic and all of its files?");',
                                ]) ?>
                                    <?= Html::hiddenInput('section_id', (int)$section->id) ?>
                                    <?= Html::hiddenInput('material_id', (int)$material->id) ?>
                                    <?= Html::hiddenInput('topic_id', (int)$topic->id) ?>
                                    <button type="submit" class="lms-icon-btn is-danger" title="Delete topic" aria-label="Delete topic">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                <?= Html::endForm() ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="lms-topic-body" id="topic-body-<?= (int)$topic->id ?>">
                        <?php if ($fileCount === 0): ?>
                            <p class="lms-topic-empty"><?= $canManage ? 'No files yet. Use the upload icon to add materials.' : 'No files in this topic yet.' ?></p>
                        <?php endif; ?>
                        <?php foreach ($topic->files as $file): ?>
                            <?php $meta = $file->displayMeta(); ?>
                            <div class="lms-file-row">
                                <a href="<?= Url::to(['download-topic-file', 'id' => (int)$file->id]) ?>" class="lms-file-link">
                                    <span class="lms-file-icon" style="background: <?= Html::encode($meta['bg']) ?>; color: <?= Html::encode($meta['color']) ?>;">
                                        <span class="material-symbols-outlined text-[1.2rem]"><?= Html::encode($meta['icon']) ?></span>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="lms-file-name block truncate"><?= Html::encode((string)$file->title) ?></span>
                                        <span class="lms-file-meta"><?= Html::encode($meta['label']) ?> · <?= Html::encode(strtoupper((string)$file->extension)) ?> · <?= Html::encode($file->sizeLabel()) ?></span>
                                    </span>
                                </a>
                                <?php if ($canManage): ?>
                                    <?= Html::beginForm(['delete-topic-file'], 'post', [
                                        'class' => 'm-0',
                                        'onsubmit' => 'return confirm("Delete this file?");',
                                    ]) ?>
                                        <?= Html::hiddenInput('section_id', (int)$section->id) ?>
                                        <?= Html::hiddenInput('material_id', (int)$material->id) ?>
                                        <?= Html::hiddenInput('file_id', (int)$file->id) ?>
                                        <button type="submit" class="lms-icon-btn is-danger" title="Delete file" aria-label="Delete <?= Html::encode((string)$file->title) ?>">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    <?= Html::endForm() ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if ($canManage): ?>
            <p class="lms-materials-hint">
                Click a topic to open it. Hover a topic to upload, rename, or delete.
                Allowed files: <?= Html::encode($allowedLabel) ?>. A single file cannot exceed <?= Html::encode($maxSizeLabel) ?>.
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?= $this->render('../layouts/_page-end') ?>

<?php
$this->registerJs(<<<'JS'
(function() {
  var shell = document.querySelector('.lms-topics-shell');
  var storageKey = shell ? shell.getAttribute('data-open-key') : '';
  var maxBytes = shell ? parseInt(shell.getAttribute('data-max-bytes') || '524288000', 10) : 524288000;
  var saved = {};
  try { saved = JSON.parse(localStorage.getItem(storageKey) || '{}') || {}; } catch (e) { saved = {}; }
  var hashId = (location.hash || '').replace('#', '');

  function persist() {
    if (!storageKey) return;
    var next = {};
    document.querySelectorAll('.lms-topic.is-open').forEach(function(topic) {
      next[topic.getAttribute('data-topic-id')] = 1;
    });
    try { localStorage.setItem(storageKey, JSON.stringify(next)); } catch (e) {}
  }

  function setOpen(topic, open) {
    topic.classList.toggle('is-open', open);
    var btn = topic.querySelector('.lms-topic-toggle');
    if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  document.querySelectorAll('.lms-topic').forEach(function(topic) {
    var id = topic.getAttribute('data-topic-id');
    var shouldOpen = hashId === ('topic-' + id) || saved[id] === 1;
    setOpen(topic, shouldOpen);

    var toggle = topic.querySelector('.lms-topic-toggle');
    if (toggle) {
      toggle.addEventListener('click', function(e) {
        if (e.target.closest('form') || e.target.closest('input')) return;
        setOpen(topic, !topic.classList.contains('is-open'));
        persist();
      });
    }

    var renameBtn = topic.querySelector('.lms-rename-toggle');
    if (renameBtn) {
      renameBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        document.querySelectorAll('.lms-topic.is-renaming').forEach(function(other) {
          if (other !== topic) other.classList.remove('is-renaming');
        });
        topic.classList.toggle('is-renaming');
        setOpen(topic, true);
        persist();
        var input = topic.querySelector('.lms-rename-form input[name="name"]');
        if (topic.classList.contains('is-renaming') && input) {
          input.focus();
          input.select();
        }
      });
    }

    var renameForm = topic.querySelector('.lms-rename-form');
    if (renameForm) {
      renameForm.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          e.preventDefault();
          topic.classList.remove('is-renaming');
        }
      });
    }

    var uploadBtn = topic.querySelector('.lms-upload-toggle');
    var uploadForm = topic.querySelector('.lms-upload-form');
    var fileInput = topic.querySelector('.lms-file-input');
    if (uploadBtn && fileInput && uploadForm) {
      uploadBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        setOpen(topic, true);
        persist();
        fileInput.click();
      });
      fileInput.addEventListener('change', function() {
        if (!fileInput.files || !fileInput.files.length) {
          return;
        }
        var oversized = [];
        Array.prototype.forEach.call(fileInput.files, function(file) {
          if (file.size > maxBytes) {
            oversized.push(file.name);
          }
        });
        if (oversized.length) {
          fileInput.value = '';
          window.alert('A file cannot exceed 500 MB.\n\n' + oversized.join('\n'));
          return;
        }
        uploadForm.submit();
      });
    }
  });

  if (hashId) {
    var hashed = document.getElementById(hashId);
    if (hashed) hashed.scrollIntoView({ block: 'center' });
  }
})();
JS
);
