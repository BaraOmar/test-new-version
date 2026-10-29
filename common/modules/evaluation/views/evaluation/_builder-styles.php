<?php

/**
 * Legacy CulturePulse evaluation builder & respond styles (matches Legacy_Code/HRMS).
 *
 * @var yii\web\View $this
 */

use yii\web\View;

$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');
$this->registerCss(<<<'CSS'
:root {
    --cp-primary: #642754;
    --cp-accent: #eb652c;
    --cp-accent-hover: #d85a24;
    --cp-bg: #f8f4f8;
    --cp-card-border: #e2e8f0;
    --cp-card-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}

.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* —— Evaluation builder (gforms JS + legacy chrome) —— */
.evaluation-builder-page {
    font-family: 'Inter', sans-serif;
    background: var(--cp-bg);
    color: #0f172a;
}

.sb-builder-header {
    flex-shrink: 0;
    z-index: 20;
    position: relative;
}

.evaluation-builder-page .gf-canvas-wrap {
    background: var(--cp-bg);
    flex: 1;
    overflow-y: auto;
}

.evaluation-builder-page #gf-canvas {
    max-width: 48rem;
    width: 100%;
    margin: 0 auto;
    padding: 2rem 4.5rem 8rem 2rem;
    position: relative;
    min-height: 100%;
}

.evaluation-builder-page .gf-card {
    background: #fff;
    border-radius: 0.75rem;
    box-shadow: var(--cp-card-shadow);
    margin-bottom: 1rem;
    border: 1px solid var(--cp-card-border);
    border-left: 6px solid transparent;
    transition: border-color 0.15s, box-shadow 0.15s;
    position: relative;
    overflow: visible;
}

.evaluation-builder-page .gf-card.gf-block-active {
    border-left-color: var(--cp-primary);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}

.evaluation-builder-page #gf-title-card {
    overflow: hidden;
    border-left: none;
    padding: 0;
}

.evaluation-builder-page #gf-title-card .gf-title-bar {
    height: 0.625rem;
    width: 100%;
    background: var(--cp-primary);
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
}

.evaluation-builder-page #gf-title-card .gf-title-inner {
    padding: 2.5rem 2rem 1.5rem;
    position: relative;
}

.evaluation-builder-page .gf-title-input {
    width: 100%;
    border: none;
    border-bottom: 1px solid transparent;
    font-size: 1.875rem;
    font-weight: 800;
    letter-spacing: -0.025em;
    color: #0f172a;
    padding: 0.5rem 0;
    outline: none;
    background: transparent;
}

.evaluation-builder-page .gf-title-input:hover {
    border-bottom-color: #e2e8f0;
}

.evaluation-builder-page .gf-title-input:focus {
    border-bottom-color: var(--cp-primary);
}

.evaluation-builder-page .gf-title-input::placeholder { color: #94a3b8; }

.evaluation-builder-page .gf-desc-editable {
    min-height: 2rem;
    font-size: 1rem;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.5;
    outline: none;
    padding: 0.25rem 0;
    border-bottom: 1px solid transparent;
}

.evaluation-builder-page .gf-desc-editable:not(:focus):not(:hover) {
    border-bottom-color: #e2e8f0;
}

.evaluation-builder-page .gf-desc-editable:hover {
    border-bottom-color: #e2e8f0;
}

.evaluation-builder-page .gf-desc-editable:focus {
    border-bottom-color: var(--cp-primary);
}

.evaluation-builder-page .gf-desc-editable:empty::before {
    content: attr(data-placeholder);
    color: #94a3b8;
    font-weight: 500;
}

.evaluation-builder-page .gf-desc-editable,
.evaluation-builder-page .gf-desc-editable * {
    font-family: inherit;
    font-size: 1rem;
    font-weight: 600;
    color: #0f172a;
    letter-spacing: normal;
    background: transparent !important;
}

.evaluation-builder-page .gf-card-inner { padding: 1.5rem 1.5rem 0.5rem; }

.evaluation-builder-page .gf-q-text {
    width: 100%;
    border: none;
    border-bottom: 1px solid #cbd5e1;
    font-size: 1rem;
    font-weight: 500;
    padding: 0.5rem 0 1rem;
    outline: none;
    color: #0f172a;
    background: transparent;
}

.evaluation-builder-page .gf-q-text:focus { border-bottom-color: var(--cp-primary); }

.evaluation-builder-page .gf-preview { margin-bottom: 0.5rem; padding: 0 0.25rem; }

.evaluation-builder-page .gf-option-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
}

.evaluation-builder-page .gf-opt-icon { color: #94a3b8; font-size: 1.25rem; }

.evaluation-builder-page .gf-option-input {
    flex: 1;
    border: none;
    border-bottom: 1px solid #e2e8f0;
    padding: 0.25rem 0;
    font-size: 0.875rem;
    outline: none;
    background: transparent;
}

.evaluation-builder-page .gf-option-input:focus { border-bottom-color: var(--cp-primary); }

.evaluation-builder-page .gf-option-remove {
    opacity: 0;
    border: none;
    background: none;
    cursor: pointer;
    color: #64748b;
    padding: 4px;
    border-radius: 9999px;
}

.evaluation-builder-page .gf-option-row:hover .gf-option-remove { opacity: 1; }

.evaluation-builder-page .gf-option-remove:hover {
    background: #f1f5f9;
    color: #ef4444;
}

.evaluation-builder-page .gf-add-option,
.evaluation-builder-page .gf-add-other {
    border: none;
    background: none;
    color: var(--cp-primary);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    padding: 0.75rem 0;
    margin-right: 1rem;
}

.evaluation-builder-page .gf-add-option:hover,
.evaluation-builder-page .gf-add-other:hover { text-decoration: underline; }

.evaluation-builder-page .gf-line-short,
.evaluation-builder-page .gf-line-long {
    display: block;
    color: #94a3b8;
    font-size: 0.875rem;
    border-bottom: 1px solid #cbd5e1;
    padding-bottom: 0.25rem;
}

.evaluation-builder-page .gf-line-long { width: 100%; }
.evaluation-builder-page .gf-line-short { width: 40%; }

.evaluation-builder-page .gf-preview-rating .gf-star {
    color: #cbd5e1;
    font-size: 2rem;
    margin-right: 4px;
}

.evaluation-builder-page .gf-linear-row {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin: 12px 0;
}

.evaluation-builder-page .gf-linear-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    color: #64748b;
}

.evaluation-builder-page .gf-linear-dot {
    width: 20px;
    height: 20px;
    border: 2px solid #cbd5e1;
    border-radius: 50%;
}

.evaluation-builder-page .gf-linear-labels {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 8px;
}

.evaluation-builder-page .gf-linear-labels input,
.evaluation-builder-page .gf-scale-config input {
    border: none;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.75rem;
    padding: 4px;
    width: 100%;
    max-width: 140px;
    outline: none;
}

.evaluation-builder-page .gf-scale-config {
    margin-top: 12px;
    font-size: 0.75rem;
    color: #64748b;
}

.evaluation-builder-page .gf-scale-config input { width: 48px; max-width: none; display: inline-block; }

.evaluation-builder-page .gf-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-top: 1px solid #f1f5f9;
    margin-top: 1rem;
    padding: 0.75rem 1.5rem;
    flex-wrap: wrap;
    gap: 12px;
}

.evaluation-builder-page .gf-type-wrap {
    position: relative;
    display: inline-block;
}

.evaluation-builder-page .gf-type-wrap::after {
    content: '';
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 6px solid #000;
}

.evaluation-builder-page .gf-type-select {
    border: 1px solid var(--cp-card-border);
    border-radius: 0.5rem;
    padding: 0.75rem 2.25rem 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #0f172a;
    min-width: 200px;
    background: #fff;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
}

.evaluation-builder-page .gf-type-select:focus {
    border-color: var(--cp-primary);
    outline: none;
    box-shadow: 0 0 0 1px var(--cp-primary);
}

.evaluation-builder-page .gf-footer-actions {
    display: flex;
    align-items: center;
    gap: 4px;
}

.evaluation-builder-page .gf-icon-btn {
    border: none;
    background: transparent;
    width: 40px;
    height: 40px;
    border-radius: 9999px;
    cursor: pointer;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
}

.evaluation-builder-page .gf-icon-btn:hover { background: #f1f5f9; }

.evaluation-builder-page .gf-required-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    font-weight: 500;
    color: #475569;
    margin-left: 8px;
    padding-left: 8px;
    border-left: 1px solid #e2e8f0;
}

.evaluation-builder-page .gf-required-toggle input[type="checkbox"] {
    accent-color: var(--cp-primary);
    width: 1rem;
    height: 1rem;
}

.evaluation-builder-page #gf-float-toolbar {
    position: absolute;
    right: -3.25rem;
    top: 7.5rem;
    width: 3rem;
    background: #fff;
    border-radius: 0.75rem;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    border: 1px solid var(--cp-card-border);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem 0;
    z-index: 40;
}

.evaluation-builder-page #gf-float-toolbar button {
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    border-radius: 9999px;
    cursor: pointer;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
}

.evaluation-builder-page #gf-float-toolbar button:hover {
    background: #f8fafc;
    color: var(--cp-primary);
}

.evaluation-builder-page .gf-add-question-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: auto;
    margin: 1rem auto 0;
    padding: 0.75rem 1.5rem;
    border: 1px solid var(--cp-card-border);
    border-radius: 9999px;
    background: #fff;
    box-shadow: var(--cp-card-shadow);
    color: var(--cp-primary);
    font-size: 0.875rem;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.evaluation-builder-page .gf-add-question-btn .material-symbols-outlined {
    font-size: 1.25rem;
}

.evaluation-builder-page .gf-add-question-btn:hover {
    border-color: var(--cp-primary);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

.evaluation-builder-page .gf-layout-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
}

.evaluation-builder-page .gf-media-url {
    width: 100%;
    border: 1px solid var(--cp-card-border);
    border-radius: 0.5rem;
    padding: 10px 12px;
    font-size: 0.875rem;
    margin-bottom: 12px;
}

.evaluation-builder-page .gf-media-url:focus {
    border-color: var(--cp-primary);
    outline: none;
}

.evaluation-builder-page .gf-media-preview {
    max-width: 100%;
    max-height: 200px;
    border-radius: 0.5rem;
}

.evaluation-builder-page .gf-media-placeholder {
    height: 120px;
    background: #f8fafc;
    border-radius: 0.5rem;
    border: 1px dashed #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 0.875rem;
}

.evaluation-builder-page .gf-section-hr {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 16px 0 8px;
}

.evaluation-builder-page .gf-section-title,
.evaluation-builder-page .gf-section-desc {
    width: 100%;
    border: none;
    border-bottom: 1px solid #e2e8f0;
    padding: 8px 0;
    outline: none;
    background: transparent;
}

.evaluation-builder-page .gf-section-title {
    font-size: 1.125rem;
    font-weight: 700;
}

.evaluation-builder-page .gf-title-block {
    font-size: 1.25rem;
    font-weight: 700;
}

.evaluation-builder-page #gf-import-modal {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.evaluation-builder-page #gf-import-modal.hidden { display: none; }

.evaluation-builder-page .gf-modal-box {
    background: #fff;
    border-radius: 0.75rem;
    padding: 24px;
    max-width: 520px;
    width: 100%;
    border: 1px solid var(--cp-card-border);
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
}

.evaluation-builder-page .gf-modal-box textarea {
    width: 100%;
    min-height: 160px;
    border: 1px solid var(--cp-card-border);
    border-radius: 0.5rem;
    padding: 12px;
    font-family: ui-monospace, monospace;
    font-size: 0.8125rem;
}

.evaluation-builder-page .sb-config-aside {
    width: 360px;
    min-width: 360px;
    max-width: 360px;
    border-left: 1px solid #e2e8f0;
    background: #fff;
}

.evaluation-builder-page .target-radio:checked + .target-card {
    border-color: var(--cp-primary);
    background-color: rgba(100, 39, 84, 0.05);
}

.evaluation-builder-page .target-radio:checked + .target-card .target-indicator {
    background-color: var(--cp-primary);
    border-color: var(--cp-primary);
}

.evaluation-builder-page .target-radio:checked + .target-card .target-indicator-inner {
    display: block;
}

.evaluation-builder-page .target-check-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    flex-shrink: 0;
}

.evaluation-builder-page .target-check-on {
    display: none;
    color: var(--cp-primary);
    font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
}

.evaluation-builder-page .dept-checkbox:checked + .dept-label,
.evaluation-builder-page .employee-checkbox:checked + .employee-label {
    background-color: rgba(100, 39, 84, 0.08);
    border-color: var(--cp-primary);
}

.evaluation-builder-page .dept-checkbox:checked + .dept-label .target-check-off,
.evaluation-builder-page .employee-checkbox:checked + .employee-label .target-check-off {
    display: none;
}

.evaluation-builder-page .dept-checkbox:checked + .dept-label .target-check-on,
.evaluation-builder-page .employee-checkbox:checked + .employee-label .target-check-on {
    display: inline-flex;
}

@media (max-width: 1100px) {
    .evaluation-builder-page #gf-float-toolbar {
        right: 8px;
        top: auto;
        bottom: 24px;
        flex-direction: row;
        width: auto;
        padding: 4px 8px;
    }
    .evaluation-builder-page #gf-canvas { padding-right: 2rem; }
}

/* —— Employee evaluation respond (legacy evaluation_form.php) —— */
.cp-respond-root {
    font-family: 'Roboto', sans-serif;
    background: var(--cp-bg);
    color: #202124;
    min-height: 100%;
}

.cp-respond-root .google-card {
    background: #fff;
    border-radius: 8px;
    border: 1px solid #e8e0e8;
    box-shadow: 0 2px 1px -1px rgba(0,0,0,0.2), 0 1px 1px 0 rgba(0,0,0,0.14), 0 1px 3px 0 rgba(0,0,0,0.12);
    overflow: hidden;
}

.cp-respond-root .header-top-border {
    border-top: 10px solid var(--cp-accent);
}

.cp-respond-root .cp-respond-header {
    background: var(--cp-primary);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.cp-respond-root .cp-respond-main {
    max-width: 770px;
    margin: 0 auto;
    padding: 1.5rem 1rem 2rem;
}

.cp-respond-root .cp-q-card { padding: 1.5rem; margin-bottom: 0.75rem; }

.cp-respond-root .cp-q-number {
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 1.5rem;
}

.cp-respond-root .cp-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    cursor: pointer;
    font-size: 0.875rem;
}

.cp-respond-root .cp-option input {
    width: 1.25rem;
    height: 1.25rem;
    accent-color: var(--cp-primary);
}

.cp-respond-root .cp-text-input,
.cp-respond-root .cp-textarea {
    width: 100%;
    border: 0;
    border-bottom: 1px solid #d1d5db;
    background: transparent;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    outline: none;
}

.cp-respond-root .cp-text-input:focus,
.cp-respond-root .cp-textarea:focus {
    border-bottom-color: var(--cp-primary);
}

.cp-respond-root .cp-textarea { min-height: 4.5rem; resize: vertical; }

.cp-respond-root .cp-star-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

.cp-respond-root .cp-star-row label { cursor: pointer; }

.cp-respond-root .cp-star-row input { display: none; }

.cp-respond-root .cp-star-row input:checked + .material-symbols-outlined {
    color: var(--cp-primary);
    font-variation-settings: 'FILL' 1;
}

.cp-respond-root .cp-star-row .material-symbols-outlined {
    font-size: 1.875rem;
    color: #d1d5db;
}

.cp-respond-root .cp-linear-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.cp-respond-root .cp-linear-row {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.cp-respond-root .cp-linear-row label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    cursor: pointer;
    flex: 1;
    min-width: 48px;
}

.cp-respond-root .cp-linear-row input { accent-color: var(--cp-primary); }

.cp-respond-root .cp-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 0;
}

.cp-respond-root .cp-btn-clear {
    color: var(--cp-primary);
    background: transparent;
    border: none;
    padding: 0.5rem 1.5rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
}

.cp-respond-root .cp-btn-clear:hover { background: rgba(100, 39, 84, 0.05); }

.cp-respond-root .cp-btn-submit {
    background: var(--cp-accent);
    color: #fff;
    border: none;
    padding: 0.5rem 2rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.cp-respond-root .cp-btn-submit:hover { background: var(--cp-accent-hover); }

.cp-respond-root .cp-alert {
    padding: 1.5rem;
    margin-bottom: 0.75rem;
    border-radius: 8px;
    font-size: 0.875rem;
}

.cp-respond-root .cp-alert-success {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.cp-respond-root .cp-alert-warning {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}

.cp-respond-root .cp-section-break {
    background: transparent;
    border: none;
    box-shadow: none;
    padding: 0.5rem 0;
}

.cp-respond-root .cp-section-break hr {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin-top: 0.5rem;
}

.cp-respond-root select.cp-select {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 0.625rem;
    font-size: 0.875rem;
}

.cp-respond-root .cp-media-img { max-width: 100%; border-radius: 4px; }

.cp-respond-root .cp-video-wrap {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    border-radius: 4px;
}

.cp-respond-root .cp-video-wrap iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
}
CSS);
