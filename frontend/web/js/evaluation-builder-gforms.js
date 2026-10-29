/**
 * Google Forms-style survey builder.
 */
(function () {
    'use strict';

    const NON_ANSWERABLE = new Set(['section_break', 'section_title', 'image', 'video']);

    const TYPE_LABELS = {
        multiple_choice: 'Multiple choice',
        checkbox: 'Checkboxes',
        short_text: 'Short answer',
        long_text: 'Paragraph',
        rating: 'Rating',
        linear_scale: 'Linear scale',
        dropdown: 'Dropdown',
        section_break: 'Section',
        section_title: 'Title and description',
        image: 'Image',
        video: 'Video',
    };

    let blocks = [];
    let activeIndex = 0;
    let els = {};

    function init(config) {
        els = {
            form: document.getElementById('evaluation-form'),
            blocks: document.getElementById('gf-blocks'),
            json: document.getElementById('questions-json'),
            titleCard: document.getElementById('gf-title-card'),
            titleInput: document.getElementById('surveyTitleInput'),
            descEditable: document.getElementById('evaluation-description-editable'),
            descHidden: document.getElementById('evaluation-description-hidden'),
            toolbar: document.getElementById('gf-float-toolbar'),
            importModal: document.getElementById('gf-import-modal'),
            importText: document.getElementById('gf-import-text'),
        };

        blocks = Array.isArray(config.initial) && config.initial.length
            ? config.initial.map(normalizeBlock)
            : [defaultQuestion('multiple_choice')];

        bindToolbar();
        bindTitleCard();
        bindImport();
        bindFormSubmit();
        bindGlobalEvents();

        if (els.descHidden && els.descEditable && els.descHidden.value && !els.descEditable.innerHTML.trim()) {
            els.descEditable.innerHTML = els.descHidden.value;
        }

        render();
        setActive(blocks.length ? 0 : -1);
    }

    function defaultQuestion(type) {
        return normalizeBlock({
            type: type || 'multiple_choice',
            text: '',
            required: false,
            options: [''],
            scaleMin: 1,
            scaleMax: 5,
            scaleLabelMin: '',
            scaleLabelMax: '',
            mediaUrl: '',
            sectionTitle: '',
        });
    }

    function normalizeBlock(b) {
        const type = b.type || 'short_text';
        return {
            type,
            text: b.text || '',
            required: !!b.required,
            options: Array.isArray(b.options) && b.options.length ? b.options : (['multiple_choice', 'checkbox', 'dropdown'].includes(type) ? [''] : []),
            scaleMin: parseInt(b.scaleMin, 10) || 1,
            scaleMax: parseInt(b.scaleMax, 10) || 5,
            scaleLabelMin: b.scaleLabelMin || '',
            scaleLabelMax: b.scaleLabelMax || '',
            mediaUrl: b.mediaUrl || b.media_url || '',
            sectionTitle: b.sectionTitle || b.section_title || '',
        };
    }

    function escapeHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function syncJson() {
        if (els.json) {
            els.json.value = JSON.stringify(blocks);
        }
        if (typeof window.markDraftSaved === 'function') {
            window.markDraftSaved();
        }
    }

    function setActive(index) {
        activeIndex = index;
        document.querySelectorAll('.gf-block').forEach((el, i) => {
            el.classList.toggle('gf-block-active', i === index);
        });
        if (index === -1 && els.titleCard) {
            els.titleCard.classList.add('gf-block-active');
            document.querySelectorAll('.gf-block').forEach(el => el.classList.remove('gf-block-active'));
        } else if (els.titleCard) {
            els.titleCard.classList.remove('gf-block-active');
        }
    }

    function render() {
        if (!els.blocks) return;
        syncJson();
        els.blocks.innerHTML = '';

        blocks.forEach((block, index) => {
            const card = document.createElement('div');
            card.className = 'gf-block gf-card';
            card.dataset.index = String(index);

            if (NON_ANSWERABLE.has(block.type) && block.type !== 'section_title') {
                card.innerHTML = renderLayoutBlock(block, index);
            } else if (block.type === 'section_title') {
                card.innerHTML = renderSectionTitleBlock(block, index);
            } else {
                card.innerHTML = renderQuestionBlock(block, index);
            }

            card.addEventListener('click', (e) => {
                if (e.target.closest('.gf-card-footer')) return;
                setActive(index);
            });

            els.blocks.appendChild(card);
        });

        bindBlockInputs();
        positionToolbar();
    }

    function renderQuestionBlock(b, index) {
        const preview = renderPreview(b, index);
        return `
            <div class="gf-card-inner">
                <input type="text" class="gf-q-text" data-field="text" data-index="${index}" value="${escapeHtml(b.text)}" placeholder="Question">
                ${preview}
                <div class="gf-card-footer">
                    <div class="gf-type-wrap">
                        <select class="gf-type-select" data-index="${index}">
                            ${Object.keys(TYPE_LABELS).filter(t => !NON_ANSWERABLE.has(t) || t === 'section_title').filter(t => t !== 'section_title').map(t =>
                                `<option value="${t}" ${b.type === t ? 'selected' : ''}>${TYPE_LABELS[t]}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="gf-footer-actions">
                        <button type="button" class="gf-icon-btn" data-action="duplicate" data-index="${index}" title="Duplicate"><span class="material-symbols-outlined">content_copy</span></button>
                        <button type="button" class="gf-icon-btn" data-action="delete" data-index="${index}" title="Delete"><span class="material-symbols-outlined">delete</span></button>
                        <label class="gf-required-toggle">
                            <span>Required</span>
                            <input type="checkbox" class="gf-required" data-index="${index}" ${b.required ? 'checked' : ''}>
                        </label>
                    </div>
                </div>
            </div>`;
    }

    function renderPreview(b, index) {
        switch (b.type) {
            case 'multiple_choice':
            case 'dropdown':
                return renderChoicePreview(b, 'radio', index);
            case 'checkbox':
                return renderChoicePreview(b, 'checkbox', index);
            case 'short_text':
                return '<div class="gf-preview"><span class="gf-line-short">Short answer text</span></div>';
            case 'long_text':
                return '<div class="gf-preview"><span class="gf-line-long">Long answer text</span></div>';
            case 'rating': {
                const max = Math.min(10, Math.max(2, b.scaleMax || 5));
                let stars = '';
                for (let i = 1; i <= max; i++) {
                    stars += '<span class="material-symbols-outlined gf-star">star</span>';
                }
                return `<div class="gf-preview gf-preview-rating">${stars}
                    <div class="gf-scale-config">
                        <label>Scale <input type="number" class="gf-scale-min" data-index="${index}" placeholder="1" min="1" max="10" value="${b.scaleMin}"> to
                        <input type="number" class="gf-scale-max" data-index="${index}" placeholder="5" min="2" max="10" value="${b.scaleMax}"></label>
                    </div></div>`;
            }
            case 'linear_scale': {
                const min = b.scaleMin || 1;
                const max = b.scaleMax || 5;
                let row = '';
                for (let i = min; i <= max; i++) {
                    row += `<div class="gf-linear-item"><span>${i}</span><span class="gf-linear-dot"></span></div>`;
                }
                return `<div class="gf-preview gf-preview-linear">
                    <div class="gf-linear-labels">
                        <input type="text" class="gf-label-min" data-index="${index}" placeholder="Label (optional)" value="${escapeHtml(b.scaleLabelMin)}">
                        <input type="text" class="gf-label-max" data-index="${index}" placeholder="Label (optional)" value="${escapeHtml(b.scaleLabelMax)}">
                    </div>
                    <div class="gf-linear-row">${row}</div>
                    <div class="gf-scale-config">
                        <label>Scale <input type="number" class="gf-scale-min" data-index="${index}" min="1" max="10" value="${min}"> –
                        <input type="number" class="gf-scale-max" data-index="${index}" min="2" max="10" value="${max}"></label>
                    </div></div>`;
            }
            default:
                return '';
        }
    }

    function renderChoicePreview(b, kind, index) {
        const icon = kind === 'checkbox' ? 'check_box_outline_blank' : 'radio_button_unchecked';
        const opts = (b.options || []).map((opt, oi) => `
            <div class="gf-option-row" data-option-index="${oi}">
                <span class="material-symbols-outlined gf-opt-icon">${icon}</span>
                <input type="text" class="gf-option-input" data-index="${index}" data-option-index="${oi}" value="${escapeHtml(opt)}" placeholder="Option ${oi + 1}">
                <button type="button" class="gf-option-remove" data-index="${index}" data-option-index="${oi}" title="Remove option"><span class="material-symbols-outlined">close</span></button>
            </div>
        `).join('');
        return `<div class="gf-preview gf-preview-options">${opts}
            <button type="button" class="gf-add-option" data-index="${index}">Add option</button>
            ${kind === 'radio' ? `<button type="button" class="gf-add-other" data-index="${index}">Add "Other"</button>` : ''}
        </div>`;
    }

    function renderLayoutBlock(b, index) {
        if (b.type === 'image') {
            return `<div class="gf-card-inner">
                <p class="gf-layout-label">Image</p>
                <input type="url" class="gf-media-url" data-index="${index}" value="${escapeHtml(b.mediaUrl)}" placeholder="Paste image URL">
                ${b.mediaUrl ? `<img src="${escapeHtml(b.mediaUrl)}" class="gf-media-preview" alt="">` : '<div class="gf-media-placeholder">Image preview</div>'}
                <div class="gf-card-footer"><button type="button" class="gf-icon-btn" data-action="delete" data-index="${index}"><span class="material-symbols-outlined">delete</span></button></div>
            </div>`;
        }
        if (b.type === 'video') {
            return `<div class="gf-card-inner">
                <p class="gf-layout-label">Video (YouTube URL)</p>
                <input type="url" class="gf-media-url" data-index="${index}" value="${escapeHtml(b.mediaUrl)}" placeholder="https://www.youtube.com/watch?v=...">
                <div class="gf-card-footer"><button type="button" class="gf-icon-btn" data-action="delete" data-index="${index}"><span class="material-symbols-outlined">delete</span></button></div>
            </div>`;
        }
        if (b.type === 'section_break') {
            return `<div class="gf-card-inner gf-section-break">
                <input type="text" class="gf-section-title" data-index="${index}" value="${escapeHtml(b.sectionTitle || b.text)}" placeholder="Section title (optional)">
                <hr class="gf-section-hr">
                <div class="gf-card-footer"><button type="button" class="gf-icon-btn" data-action="delete" data-index="${index}"><span class="material-symbols-outlined">delete</span></button></div>
            </div>`;
        }
        return '';
    }

    function renderSectionTitleBlock(b, index) {
        return `<div class="gf-card-inner">
            <input type="text" class="gf-q-text gf-title-block" data-field="text" data-index="${index}" value="${escapeHtml(b.text)}" placeholder="Section title">
            <div class="gf-preview"><span class="gf-line-long">Description (optional)</span></div>
            <input type="text" class="gf-section-desc" data-index="${index}" value="${escapeHtml(b.sectionTitle)}" placeholder="Description">
            <div class="gf-card-footer"><button type="button" class="gf-icon-btn" data-action="delete" data-index="${index}"><span class="material-symbols-outlined">delete</span></button></div>
        </div>`;
    }

    function bindBlockInputs() {
        document.querySelectorAll('.gf-q-text').forEach(el => {
            el.oninput = () => {
                blocks[+el.dataset.index].text = el.value;
                syncJson();
            };
        });
        document.querySelectorAll('.gf-type-select').forEach(el => {
            el.onchange = () => {
                const i = +el.dataset.index;
                blocks[i].type = el.value;
                if (['multiple_choice', 'checkbox', 'dropdown'].includes(blocks[i].type) && !blocks[i].options.length) {
                    blocks[i].options = [''];
                }
                render();
                setActive(i);
            };
        });
        document.querySelectorAll('.gf-required').forEach(el => {
            el.onchange = () => {
                blocks[+el.dataset.index].required = el.checked;
                syncJson();
            };
        });
        document.querySelectorAll('.gf-option-input').forEach(el => {
            el.oninput = () => {
                const i = +el.dataset.index;
                const oi = +el.dataset.optionIndex;
                blocks[i].options[oi] = el.value;
                syncJson();
            };
        });
        document.querySelectorAll('.gf-scale-min').forEach(el => {
            el.oninput = () => {
                const card = el.closest('.gf-block');
                if (!card) return;
                const i = +card.dataset.index;
                blocks[i].scaleMin = parseInt(el.value, 10) || 1;
                if (blocks[i].type === 'rating' || blocks[i].type === 'linear_scale') {
                    render();
                    setActive(i);
                } else {
                    syncJson();
                }
            };
        });
        document.querySelectorAll('.gf-scale-max').forEach(el => {
            el.oninput = () => {
                const card = el.closest('.gf-block');
                if (!card) return;
                const i = +card.dataset.index;
                blocks[i].scaleMax = parseInt(el.value, 10) || 5;
                if (blocks[i].type === 'rating' || blocks[i].type === 'linear_scale') {
                    render();
                    setActive(i);
                } else {
                    syncJson();
                }
            };
        });
        document.querySelectorAll('.gf-label-min').forEach(el => {
            el.oninput = () => {
                blocks[+el.dataset.index].scaleLabelMin = el.value;
                syncJson();
            };
        });
        document.querySelectorAll('.gf-label-max').forEach(el => {
            el.oninput = () => {
                blocks[+el.dataset.index].scaleLabelMax = el.value;
                syncJson();
            };
        });
        document.querySelectorAll('.gf-media-url').forEach(el => {
            el.oninput = () => {
                blocks[+el.dataset.index].mediaUrl = el.value;
                render();
                setActive(+el.dataset.index);
            };
        });
        document.querySelectorAll('.gf-section-title').forEach(el => {
            el.oninput = () => {
                const i = +el.dataset.index;
                blocks[i].sectionTitle = el.value;
                blocks[i].text = el.value;
                syncJson();
            };
        });
        document.querySelectorAll('.gf-section-desc').forEach(el => {
            el.oninput = () => {
                blocks[+el.dataset.index].sectionTitle = el.value;
                syncJson();
            };
        });
    }

    function bindGlobalEvents() {
        document.addEventListener('click', (e) => {
            const addOpt = e.target.closest('.gf-add-option');
            if (addOpt) {
                const i = +addOpt.dataset.index;
                blocks[i].options.push('');
                render();
                setActive(i);
                return;
            }
            const addOther = e.target.closest('.gf-add-other');
            if (addOther) {
                const i = +addOther.dataset.index;
                if (!blocks[i].options.includes('Other')) {
                    blocks[i].options.push('Other');
                }
                render();
                setActive(i);
                return;
            }
            const rmOpt = e.target.closest('.gf-option-remove');
            if (rmOpt) {
                const i = +rmOpt.dataset.index;
                const oi = +rmOpt.dataset.optionIndex;
                if (blocks[i].options.length > 1) {
                    blocks[i].options.splice(oi, 1);
                    render();
                    setActive(i);
                }
                return;
            }
            const dup = e.target.closest('[data-action="duplicate"]');
            if (dup) {
                const i = +dup.dataset.index;
                blocks.splice(i + 1, 0, normalizeBlock(JSON.parse(JSON.stringify(blocks[i]))));
                render();
                setActive(i + 1);
                return;
            }
            const del = e.target.closest('[data-action="delete"]');
            if (del) {
                const i = +del.dataset.index;
                if (blocks.length <= 1 && !NON_ANSWERABLE.has(blocks[0].type)) {
                    alert('Keep at least one question.');
                    return;
                }
                blocks.splice(i, 1);
                render();
                setActive(Math.max(0, i - 1));
            }
        });

        if (els.titleCard) {
            els.titleCard.addEventListener('click', () => setActive(-1));
        }
    }

    function addBlock(type) {
        const block = defaultQuestion(type);
        if (type === 'section_break') {
            block.text = '';
            block.sectionTitle = '';
        } else if (type === 'section_title') {
            block.text = '';
        } else if (type === 'image' || type === 'video') {
            block.mediaUrl = '';
        }
        blocks.push(block);
        render();
        setActive(blocks.length - 1);
        const card = els.blocks.lastElementChild;
        card?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function handleToolbarAction(action) {
        switch (action) {
                    case 'add-question':
                        addBlock('multiple_choice');
                        break;
                    case 'import':
                        els.importModal?.classList.remove('hidden');
                        break;
                    case 'add-title':
                        addBlock('section_title');
                        break;
                    case 'add-image':
                        addBlock('image');
                        break;
                    case 'add-video':
                        addBlock('video');
                        break;
                    case 'add-section':
                        addBlock('section_break');
                        break;
        }
    }

    function bindToolbar() {
        document.querySelectorAll('[data-gf-action]').forEach(btn => {
            btn.addEventListener('click', () => {
                handleToolbarAction(btn.dataset.gfAction);
            });
        });
    }

    function bindImport() {
        document.getElementById('gf-import-apply')?.addEventListener('click', () => {
            try {
                const parsed = JSON.parse(els.importText.value || '[]');
                if (!Array.isArray(parsed)) throw new Error('Must be an array');
                blocks = parsed.map(normalizeBlock);
                els.importModal?.classList.add('hidden');
                render();
                setActive(0);
            } catch (err) {
                alert('Invalid JSON: ' + err.message);
            }
        });
        document.getElementById('gf-import-cancel')?.addEventListener('click', () => {
            els.importModal?.classList.add('hidden');
        });
    }

    function plainDescriptionText(el) {
        if (!el) {
            return '';
        }
        return (el.innerText || el.textContent || '')
            .replace(/\u00a0/g, ' ')
            .replace(/\r\n/g, '\n')
            .trim();
    }

    function applyPlainDescription(el) {
        if (!el) {
            return;
        }
        const plain = plainDescriptionText(el);
        if (el.textContent !== plain) {
            el.textContent = plain;
        }
    }

    function bindTitleCard() {
        if (!els.descEditable) {
            return;
        }

        els.descEditable.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text/plain') || '';
            if (document.queryCommandSupported('insertText')) {
                document.execCommand('insertText', false, text);
            } else {
                els.descEditable.textContent = plainDescriptionText(els.descEditable)
                    + text;
            }
            syncDescription();
        });

        els.descEditable.addEventListener('input', syncDescription);
        els.descEditable.addEventListener('blur', () => {
            applyPlainDescription(els.descEditable);
            syncDescription();
        });

        applyPlainDescription(els.descEditable);
        syncDescription();
    }

    function syncDescription() {
        if (els.descEditable && els.descHidden) {
            els.descHidden.value = plainDescriptionText(els.descEditable);
        }
        syncJson();
    }

    function bindFormSubmit() {
        els.form?.addEventListener('submit', (e) => {
            syncDescription();
            const answerable = blocks.filter(b => !NON_ANSWERABLE.has(b.type) && b.type !== 'section_title');
            const filled = answerable.filter(b => (b.text || '').trim() !== '');
            if (filled.length === 0) {
                e.preventDefault();
                alert('Add at least one question with text before launching.');
                return;
            }
            blocks = blocks.map(b => {
                if (b.type === 'section_break') {
                    b.text = b.sectionTitle || 'Section';
                }
                if (b.type === 'image' || b.type === 'video') {
                    b.text = b.mediaUrl || b.type;
                }
                if (b.type === 'section_title') {
                    b.text = b.text || 'Section';
                }
                return b;
            });
            els.json.value = JSON.stringify(blocks);
        });
    }

    function positionToolbar() {
        if (!els.toolbar || !els.blocks) return;
        const active = document.querySelector('.gf-block-active');
        const canvas = document.getElementById('gf-canvas');
        if (!canvas) return;
        const canvasRect = canvas.getBoundingClientRect();
        if (active && active !== els.titleCard) {
            const rect = active.getBoundingClientRect();
            els.toolbar.style.top = Math.max(80, rect.top - canvasRect.top + 20) + 'px';
        } else if (els.titleCard?.classList.contains('gf-block-active')) {
            els.toolbar.style.top = '120px';
        }
    }

    window.EvaluationGFormsBuilder = { init };
})();
