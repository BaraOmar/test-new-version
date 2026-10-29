<?php
/**
 * Employee Portal Horizontal Navbar (GrowthPlatform style)
 * Include and set $currentPage, $pageTitle before including.
 */
$currentPage = $currentPage ?? '';
$pageTitle = $pageTitle ?? 'Dashboard';
$navItems = [
    'dashboard'         => ['href' => 'Employee_dashboard.php',            'label' => 'Dashboard'],
    'lms'               => ['href' => 'LMS_course_review.php',             'label' => 'LMS'],
    'my_development'    => ['href' => 'My_career_development_plan.php',    'label' => 'My Development'],
    'my_profile'        => ['href' => 'view_my_profile.php',               'label' => 'My Profile'],
    'survey_evaluation' => ['href' => 'survey_and_evaluation_display.php', 'label' => 'Survey and Evaluations'],
];
?>
<header class="flex items-center justify-between border-b border-slate-200 bg-[#642754] px-6 lg:px-10 py-4 sticky top-0 z-50 shrink-0 shadow-sm">
    <div class="flex items-center gap-8">
        <a href="Employee_dashboard.php" class="flex items-center gap-3 text-white hover:text-white/90 transition-colors">
            <div class="size-8 flex items-center justify-center shrink-0 rounded-lg bg-[#eb652c]">
                <svg class="size-5 text-white" fill="currentColor" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M24 0.757L47.243 24 24 47.243 0.757 24 24 0.757ZM21 35.757V12.243L9.243 24 21 35.757Z" />
                </svg>
            </div>
            <h2 class="text-white text-xl font-extrabold leading-tight tracking-tight">GrowthPlatform</h2>
        </a>
        <nav class="hidden md:flex items-center gap-8">
            <?php foreach ($navItems as $key => $item): ?>
            <a href="<?= htmlspecialchars($item['href']) ?>"
                class="text-sm font-semibold transition-colors py-4 -mb-4 border-b-2 <?= $currentPage === $key ? 'text-[#eb652c] border-[#eb652c]' : 'text-white/90 border-transparent hover:text-white hover:border-white/50' ?>">
                <?= htmlspecialchars($item['label']) ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
    <div class="flex-1 flex justify-center">
        <h2 class="text-white text-lg font-bold leading-tight tracking-tight"><?= htmlspecialchars($pageTitle) ?></h2>
    </div>
    <div class="flex items-center gap-3">
        <button type="button" class="flex items-center justify-center rounded-xl size-10 bg-white/10 text-white hover:bg-white/20 transition-colors"
            aria-label="Notifications">
            <span class="material-symbols-outlined text-xl">notifications</span>
        </button>
        <button type="button" class="flex items-center justify-center rounded-xl size-10 bg-white/10 text-white hover:bg-white/20 transition-colors"
            aria-label="Settings">
            <span class="material-symbols-outlined text-xl">settings</span>
        </button>
        <a href="view_my_profile.php" class="flex items-center justify-center rounded-full size-10 overflow-hidden border-2 border-white/40 hover:border-[#eb652c] transition-colors shrink-0">
            <img alt="Profile" class="w-full h-full object-cover"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuBioVmyy8XwN8GUhbGWRfureh1gNuPd9DJKyO-9o1VKmeF-qyphpd4F139IX-3uv82XhkUsMWbtESSGDdYvu7HsmlKejZ4v6bXsMZrwfZXi2ctOJw1bYlMh_DjH3vAD0Gr2e_O7fD1UhQo78YhyrLuIIyoaF1KToRuL4Vforc6nLm2vRRZeGKP5-9n6NpmcWuhIbqHIGNz93c5NJ7OJ5zp6p4IcPlrW9jiUoAtdB6Dkwb06K2Xoiv4hLHYTit26C63cW_WfD99ntrgZ" />
        </a>
    </div>
</header>
