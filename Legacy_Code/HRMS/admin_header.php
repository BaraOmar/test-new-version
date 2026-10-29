<?php
/**
 * Admin Header Bar - Solid purple, no gradient
 * Include and set $pageTitle before including.
 * Requires session_start() to be called earlier in the page (before any output).
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$userName = $_SESSION['employee_name'] ?? 'Admin User';
?>
<header class="relative flex items-center justify-between whitespace-nowrap bg-[#6B225E] px-8 py-4 shadow-sm">
    <div class="flex-1 min-w-0"></div>
    <h2 class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-white text-lg font-bold leading-tight tracking-tight pointer-events-none"><?= htmlspecialchars($pageTitle) ?></h2>
    <div class="flex-1 flex items-center justify-end gap-6">
        <div class="flex items-center gap-3 border-l border-white/20 pl-6">
            <div class="text-right">
                <p class="text-sm font-bold text-white"><?= htmlspecialchars($userName) ?></p>
                <p class="text-[11px] text-white/80 font-medium">Org Admin</p>
            </div>
            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 border-2 border-white/30"
                style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDRMqKbnF41oqwIpCUFqCKfDKRzyAXwxSRdShEZU12F4eoIT9Raf38wIWDWuFRMCtxRVE1JJH_6Iz463-YohRafk3gKACYsL2EHChtgKIqI8_5WNHpM9RQIqOi0_aealrQi88lNfWO9g-R6PYSOa1NgYUbYAVjexZY_z-vr_5W_cO2Mn36a4gaufbTIn3xCnkibV3thE8pPbFTGuvTy9WqOyGO74_q58a8qs5qGOTltFn8JZAWRyEN2zGYJ0Suhws2O0WPZFLYyk34O");'>
            </div>
        </div>
    </div>
</header>
