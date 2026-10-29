<?php

/**
 * Shared portal shell assets (Tailwind, fonts, sidebar + header styles).
 *
 * @var yii\web\View $this
 */

$this->registerCssFile('@web/css/hrms-theme.css');
echo $this->render('_tailwind-assets');
$this->registerLinkTag([
    'rel' => 'stylesheet',
    'href' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap',
]);
$this->registerLinkTag([
    'rel' => 'stylesheet',
    'href' => 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
]);
$this->registerCss(<<<'CSS'
body { font-family: 'Inter', sans-serif; }
.material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }

/* App shell — light sidebar + white header (default site chrome) */
.app-sidebar {
  width: 16rem;
  min-height: 100%;
  height: 100%;
  transition: width 0.25s ease;
  background: #fafafa;
  border-right: 1px solid #e5e7eb;
  flex-shrink: 0;
}
.app-sidebar.collapsed { width: 5rem; }
.app-sidebar.collapsed .nav-label,
.app-sidebar.collapsed .brand-text,
.app-sidebar.collapsed .user-meta,
.app-sidebar.collapsed .logout-text,
.app-sidebar.collapsed .footer-link-label { opacity: 0; max-width: 0; overflow: hidden; white-space: nowrap; pointer-events: none; }
.app-sidebar.collapsed .nav-link,
.app-sidebar.collapsed .footer-link,
.app-sidebar.collapsed .logout-btn { justify-content: center; padding-left: 0.75rem; padding-right: 0.75rem; }
.app-sidebar.collapsed .user-profile-card { padding: 0.5rem; justify-content: center; }
.app-sidebar.collapsed .sidebar-toggle { transform: rotate(180deg); }

.app-sidebar .nav-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0 0.5rem;
  padding: 0.625rem 0.875rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #4b5563;
  transition: background 0.15s, color 0.15s;
}
.app-sidebar .nav-link .material-symbols-outlined { font-size: 1.25rem; color: #6b7280; }
.app-sidebar .nav-link:hover { background: #f3f4f6; color: #4a2a51; }
.app-sidebar .nav-link:hover .material-symbols-outlined { color: #4a2a51; }
.app-sidebar .nav-link.active {
  background: rgba(74, 42, 81, 0.08);
  color: #4a2a51;
  font-weight: 600;
}
.app-sidebar .nav-link.active .material-symbols-outlined { color: #4a2a51; }
.app-sidebar .nav-link.has-active-child {
  color: #4a2a51;
  font-weight: 600;
}
.app-sidebar .nav-link.has-active-child .material-symbols-outlined { color: #4a2a51; }

.app-sidebar .nav-group { margin-bottom: 0.125rem; }
.app-sidebar .nav-group-header {
  display: flex;
  align-items: stretch;
  margin: 0 0.5rem;
  gap: 0;
}
.app-sidebar .nav-group-parent {
  flex: 1;
  min-width: 0;
  margin: 0;
}
.app-sidebar .nav-group-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  flex-shrink: 0;
  border: none;
  background: transparent;
  border-radius: 0.5rem;
  color: #6b7280;
  cursor: pointer;
  transition: background 0.15s, color 0.15s, transform 0.2s;
}
.app-sidebar .nav-group-toggle:hover {
  background: #f3f4f6;
  color: #4a2a51;
}
.app-sidebar .nav-group-chevron {
  font-size: 1.25rem;
  transition: transform 0.2s;
}
.app-sidebar .nav-group.open > .nav-group-header > .nav-group-toggle .nav-group-chevron {
  transform: rotate(180deg);
}
.app-sidebar .nav-submenu {
  margin: 0.2rem 0.65rem 0.35rem 1.1rem;
  padding: 0.15rem 0 0.15rem 0.55rem;
  border-left: 2px solid #eee4f2;
}
.app-sidebar .nav-sublink {
  display: block;
  margin: 0.125rem 0 0.125rem 0.35rem;
  padding: 0.5rem 0.75rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #6b7280;
  text-decoration: none;
  transition: background 0.15s, color 0.15s;
}
.app-sidebar .nav-sublink:hover {
  background: #f3f4f6;
  color: #4a2a51;
}
.app-sidebar .nav-sublink.active {
  background: rgba(74, 42, 81, 0.08);
  color: #4a2a51;
  font-weight: 600;
}
.app-sidebar .nav-sublink.has-active-child {
  color: #4a2a51;
  font-weight: 600;
}
.app-sidebar .nav-group--nested {
  margin: 0.15rem 0 0.35rem;
}
.app-sidebar .nav-group--nested .nav-group-header {
  margin: 0;
  align-items: center;
}
.app-sidebar .nav-grade-toggle.nav-group-toggle {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 0.5rem;
  width: 100%;
  height: auto;
  margin: 0;
  padding: 0.45rem 0.45rem 0.45rem 0.5rem;
  border: none;
  border-radius: 0.7rem;
  background: transparent;
  color: #4b5563;
  font: inherit;
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.01em;
  text-align: left;
  cursor: pointer;
  transition: background 0.15s, color 0.15s, box-shadow 0.15s;
}
.app-sidebar .nav-grade-toggle:hover {
  background: #f6f1f8;
  color: #4a2a51;
}
.app-sidebar .nav-grade-toggle:focus {
  outline: none;
}
.app-sidebar .nav-grade-toggle:focus-visible {
  box-shadow: 0 0 0 2px rgba(74, 42, 81, 0.22);
}
.app-sidebar .nav-grade-toggle.has-active-child,
.app-sidebar .nav-group--nested.open > .nav-group-header > .nav-grade-toggle {
  background: #f6f1f8;
  color: #4a2a51;
}
.app-sidebar .nav-grade-toggle.active {
  background: rgba(74, 42, 81, 0.1);
  color: #4a2a51;
}
.app-sidebar .nav-grade-icon {
  width: 1.55rem;
  height: 1.55rem;
  border-radius: 0.45rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #efe7f2;
  color: #4a2a51;
  font-size: 1rem !important;
  flex-shrink: 0;
}
.app-sidebar .nav-group--nested.open .nav-grade-icon {
  background: #4a2a51;
  color: #fff;
}
.app-sidebar .nav-grade-label {
  min-width: 0;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.app-sidebar .nav-grade-count {
  min-width: 1.15rem;
  height: 1.15rem;
  padding: 0 0.3rem;
  border-radius: 999px;
  background: #efe7f2;
  color: #6b4a73;
  font-size: 0.65rem;
  font-weight: 700;
  line-height: 1.15rem;
  text-align: center;
  flex-shrink: 0;
}
.app-sidebar .nav-grade-toggle .nav-group-chevron {
  width: 1.35rem;
  height: 1.35rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  color: #9ca3af;
  font-size: 1.05rem !important;
  flex-shrink: 0;
}
.app-sidebar .nav-group--nested.open > .nav-group-header > .nav-grade-toggle .nav-group-chevron {
  background: #fff;
  color: #4a2a51;
}
.app-sidebar .nav-group--nested.open .nav-grade-count,
.app-sidebar .nav-grade-toggle.has-active-child .nav-grade-count {
  background: #4a2a51;
  color: #fff;
}
.app-sidebar .nav-submenu:has(.nav-group--nested) {
  margin-left: 0.85rem;
  padding-left: 0.45rem;
  border-left-color: #f0e8f3;
}
.app-sidebar .nav-section-list {
  margin: 0.15rem 0 0.2rem 1.15rem;
  padding: 0.1rem 0 0.15rem 0.55rem;
  border-left: 2px solid #efe7f2;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}
.app-sidebar .nav-section-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
  padding: 0.42rem 0.65rem;
  border-radius: 0.6rem;
  color: #6b7280;
  text-decoration: none;
  font-size: 0.78rem;
  font-weight: 500;
  transition: background 0.15s, color 0.15s, box-shadow 0.15s;
}
.app-sidebar .nav-section-link:hover {
  background: #f8f5fa;
  color: #4a2a51;
}
.app-sidebar .nav-section-link.active {
  background: #fff;
  color: #4a2a51;
  font-weight: 700;
  box-shadow: 0 0 0 1px rgba(74, 42, 81, 0.12), 0 4px 10px rgba(74, 42, 81, 0.06);
}
.app-sidebar .nav-section-mark {
  width: 0.42rem;
  height: 0.42rem;
  border-radius: 999px;
  background: #d8c6de;
  flex-shrink: 0;
}
.app-sidebar .nav-section-link.active .nav-section-mark {
  background: #eb652c;
  box-shadow: 0 0 0 3px rgba(235, 101, 44, 0.16);
}
.app-sidebar .nav-section-label {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.app-sidebar.collapsed .nav-group-toggle,
.app-sidebar.collapsed .nav-submenu { display: none; }
.app-sidebar.collapsed .nav-group-header { margin: 0 0.25rem; }
.app-sidebar.collapsed .nav-group-parent { margin: 0; }

.app-sidebar .footer-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0 0.5rem 0.25rem;
  padding: 0.625rem 0.875rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #4b5563;
  transition: background 0.15s, color 0.15s;
}
.app-sidebar .footer-link:hover { background: #f3f4f6; color: #4a2a51; }

.app-sidebar .logout-btn {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 0.75rem;
  width: calc(100% - 1rem);
  margin: 0 0.5rem;
  padding: 0.625rem 0.875rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #4b5563;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.app-sidebar .logout-btn:hover { background: #f3f4f6; color: #4a2a51; }

.app-sidebar .user-profile-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0 0.75rem 0.75rem;
  padding: 0.75rem;
  border-radius: 0.5rem;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
}

.app-topbar {
  background: #ffffff;
  border-bottom: 1px solid #e5e7eb;
  flex-shrink: 0;
}
CSS);
