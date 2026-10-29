<?php
/**
 * Admin Console Sidebar Navbar
 * Include this file and set $currentPage before including to highlight the active nav item.
 * Valid $currentPage values: dashboard, employees, surveys, evaluations, change_requests, career_paths, lms
 */
$currentPage = $currentPage ?? '';
$navItems = [
    'dashboard'       => ['href' => 'HR_Dashboard.php',        'icon' => 'grid_view',      'label' => 'Dashboard'],
    'employees'       => ['href' => 'Employee_directory.php',  'icon' => 'group',          'label' => 'Employees'],
    'surveys'         => ['href' => 'survey_list.php',         'icon' => 'bar_chart',       'label' => 'Surveys'],
    'evaluations'     => ['href' => 'evaluation_list.php',     'icon' => 'star',           'label' => 'Evaluations'],
    'change_requests' => ['href' => 'HR_change_request.php',   'icon' => 'edit_note',      'label' => 'Change Requests'],
    'career_paths'    => ['href' => 'career_path_Skill_module.php', 'icon' => 'bolt',       'label' => 'Career Paths'],
    'lms'             => ['href' => 'LMS_upload.php',          'icon' => 'school',         'label' => 'LMS'],
];
?>
<style>
.admin-sidebar { width: 16rem; min-height: 100vh; height: 100%; transition: width 0.25s ease; background: #6B225E; box-shadow: 4px 0 20px rgba(0,0,0,0.15); }
.admin-sidebar.collapsed { width: 5rem; }
.admin-sidebar.collapsed .nav-label,
.admin-sidebar.collapsed .logout-text { opacity: 0; max-width: 0; min-width: 0; overflow: hidden; white-space: nowrap; pointer-events: none; }
.admin-sidebar.collapsed nav a,
.admin-sidebar.collapsed .logout-btn { justify-content: center; padding-left: 1rem; padding-right: 1rem; }
.admin-sidebar.collapsed .nav-link { margin: 0 0.25rem; }
.admin-sidebar.collapsed .nav-link.active { margin: 0 0.25rem; }
.admin-sidebar .sidebar-toggle { transition: transform 0.25s ease; }
.admin-sidebar.collapsed .sidebar-toggle { transform: rotate(180deg); }
.admin-sidebar .nav-link { margin-left: 0.5rem; margin-right: 0.5rem; }
.admin-sidebar .nav-link.active { background: #F47F37; color: white; margin-right: -10px; padding-right: 1.25rem; box-shadow: 0 4px 14px rgba(244, 127, 55, 0.5); }
.admin-sidebar .nav-link:not(.active) { color: rgba(255,255,255,0.9); }
.admin-sidebar .nav-link:not(.active):hover { background: rgba(255,255,255,0.1); color: white; }
.admin-sidebar .logout-btn { color: rgba(255,255,255,0.9); margin: 0 0.5rem; }
.admin-sidebar .logout-btn:hover { background: rgba(255,255,255,0.1); color: white; }
.admin-sidebar .sidebar-toggle { position: static; margin: 0.5rem auto 0.75rem; }
</style>
<aside id="admin-sidebar" class="admin-sidebar flex flex-col shrink-0 relative">
    <nav class="flex-1 px-2 pt-6 space-y-1 overflow-y-auto py-4 overflow-x-hidden">
        <?php foreach ($navItems as $key => $item): ?>
        <a class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all <?= $currentPage === $key ? 'active' : '' ?>"
            href="<?= htmlspecialchars($item['href']) ?>" title="<?= htmlspecialchars($item['label']) ?>">
            <span class="material-symbols-outlined shrink-0"><?= htmlspecialchars($item['icon']) ?></span>
            <span class="nav-label min-w-0 truncate"><?= htmlspecialchars($item['label']) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="p-2 pt-0 mt-auto flex flex-col items-center">
        <a href="logout.php" class="logout-btn flex items-center justify-center gap-2 w-full font-medium py-3 rounded-xl text-sm transition-all" title="Logout">
            <span class="material-symbols-outlined text-sm shrink-0">logout</span>
            <span class="logout-text min-w-0 truncate">Logout</span>
        </a>
        <button type="button" id="sidebar-toggle" class="sidebar-toggle flex items-center justify-center w-10 h-10 rounded-full bg-[#F47F37] hover:bg-[#e07530] text-white shadow-md border-2 border-[#6B225E] transition-colors focus:outline-none focus:ring-0 mt-2" aria-label="Toggle sidebar">
            <span class="material-symbols-outlined text-xl">chevron_right</span>
        </button>
    </div>
</aside>
<script>
(function() {
    var sidebar = document.getElementById('admin-sidebar');
    var toggle = document.getElementById('sidebar-toggle');
    if (!sidebar || !toggle) return;
    var stored = localStorage.getItem('admin-sidebar-collapsed');
    if (stored === '1') sidebar.classList.add('collapsed');
    toggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('admin-sidebar-collapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
    });
})();
</script>
