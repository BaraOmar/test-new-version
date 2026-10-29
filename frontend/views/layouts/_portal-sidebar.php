<?php

/**
 * Shared light sidebar (default site chrome).
 *
 * @var yii\web\View $this
 * @var array<string, array{url: array|string, icon: string, label: string, children?: array<string, array{url: array|string, label: string}>}> $navItems
 * @var string $currentPage
 * @var string $brandName
 * @var string|array $brandUrl
 * @var string $userName
 * @var string $userRole
 * @var array<int, array{url: array|string, icon: string, label: string}>|null $footerLinks
 * @var bool $showLogout
 * @var string|array $logoutUrl
 */

use yii\helpers\Html;
use yii\helpers\Url;

$currentPage = $currentPage ?? '';
$brandName = $brandName ?? 'CultureHR';
$brandUrl = $brandUrl ?? ['/site/index'];
$userName = $userName ?? 'User';
$userRole = $userRole ?? '';
$footerLinks = $footerLinks ?? [];
$showLogout = $showLogout ?? true;
$logoutUrl = $logoutUrl ?? ['/site/logout'];

$navDescendantKeys = static function (array $children) use (&$navDescendantKeys): array {
    $keys = [];
    foreach ($children as $childKey => $child) {
        $keys[] = (string)$childKey;
        $nested = $child['children'] ?? [];
        if ($nested !== []) {
            $keys = array_merge($keys, $navDescendantKeys($nested));
        }
    }

    return $keys;
};

?>

<aside id="app-sidebar" class="app-sidebar flex flex-col relative">
    <div class="px-4 pt-6 pb-4 border-b border-gray-100 shrink-0">
        <a href="<?= Url::to($brandUrl) ?>" class="flex items-center gap-3 min-w-0" title="<?= Html::encode($brandName) ?>">
            <div class="size-9 shrink-0 flex items-center justify-center rounded-md bg-[#4a2a51] text-white font-bold text-lg">
                <?= Html::encode(mb_strtoupper(mb_substr($brandName, 0, 1))) ?>
            </div>
            <span class="brand-text font-bold text-[#4a2a51] text-base truncate"><?= Html::encode($brandName) ?></span>
        </a>
    </div>

    <nav id="app-sidebar-nav" class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">
        <?php foreach ($navItems as $key => $item): ?>
            <?php
            $children = $item['children'] ?? [];
            if ($children !== []):
                $descendantKeys = $navDescendantKeys($children);
                $childActive = in_array($currentPage, $descendantKeys, true);
                $parentActive = $currentPage === $key;
                $isOpen = $parentActive || $childActive;
            ?>
                <div class="nav-group<?= $isOpen ? ' open' : '' ?>" data-nav-group="<?= Html::encode($key) ?>">
                    <div class="nav-group-header">
                        <a class="nav-link nav-group-parent <?= $parentActive ? 'active' : ($childActive ? 'has-active-child' : '') ?>"
                           href="<?= Url::to($item['url']) ?>"
                           title="<?= Html::encode($item['label']) ?>">
                            <span class="material-symbols-outlined shrink-0"><?= Html::encode($item['icon']) ?></span>
                            <span class="nav-label min-w-0 truncate"><?= Html::encode($item['label']) ?></span>
                        </a>
                        <button type="button"
                                class="nav-group-toggle"
                                aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
                                aria-label="Toggle <?= Html::encode($item['label']) ?> submenu">
                            <span class="material-symbols-outlined nav-group-chevron">expand_more</span>
                        </button>
                    </div>
                    <div class="nav-submenu" <?= $isOpen ? '' : 'hidden' ?>>
                        <?php foreach ($children as $childKey => $child): ?>
                            <?php
                            $grandchildren = $child['children'] ?? [];
                            if ($grandchildren !== []):
                                $nestedKeys = array_keys($grandchildren);
                                $nestedChildActive = in_array($currentPage, $nestedKeys, true);
                                $gradeActive = $currentPage === $childKey;
                                $gradeOpen = $gradeActive || $nestedChildActive;
                            ?>
                                <div class="nav-group nav-group--nested<?= $gradeOpen ? ' open' : '' ?>" data-nav-group="<?= Html::encode($childKey) ?>">
                                    <div class="nav-group-header">
                                        <button type="button"
                                                class="nav-grade-toggle nav-group-parent nav-group-toggle <?= $gradeActive ? 'active' : ($nestedChildActive ? 'has-active-child' : '') ?>"
                                                aria-expanded="<?= $gradeOpen ? 'true' : 'false' ?>">
                                            <span class="nav-grade-icon material-symbols-outlined">school</span>
                                            <span class="nav-grade-label"><?= Html::encode($child['label']) ?></span>
                                            <span class="nav-grade-count"><?= count($grandchildren) ?></span>
                                            <span class="material-symbols-outlined nav-group-chevron">expand_more</span>
                                        </button>
                                    </div>
                                    <div class="nav-submenu nav-section-list" <?= $gradeOpen ? '' : 'hidden' ?>>
                                        <?php foreach ($grandchildren as $leafKey => $leaf): ?>
                                            <a class="nav-section-link <?= $currentPage === $leafKey ? 'active' : '' ?>"
                                               href="<?= Url::to($leaf['url']) ?>">
                                                <span class="nav-section-mark"></span>
                                                <span class="nav-section-label"><?= Html::encode($leaf['label']) ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <a class="nav-sublink <?= $currentPage === $childKey ? 'active' : '' ?>"
                                   href="<?= Url::to($child['url']) ?>"
                                   title="<?= Html::encode($child['label']) ?>">
                                    <span class="nav-sublink-label"><?= Html::encode($child['label']) ?></span>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <a class="nav-link <?= $currentPage === $key ? 'active' : '' ?>"
                   href="<?= Url::to($item['url']) ?>"
                   title="<?= Html::encode($item['label']) ?>">
                    <span class="material-symbols-outlined shrink-0"><?= Html::encode($item['icon']) ?></span>
                    <span class="nav-label min-w-0 truncate"><?= Html::encode($item['label']) ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <div class="p-2 pt-0 mt-auto flex flex-col border-t border-gray-100">
        <div class="user-profile-card">
            <?= $this->render('@frontend/modules/employee/views/layouts/_initials-avatar', [
                'name' => $userName,
                'sizeClass' => 'size-10 shrink-0',
            ]) ?>
            <div class="user-meta min-w-0">
                <p class="text-sm font-bold text-gray-900 truncate"><?= Html::encode($userName) ?></p>
                <?php if ($userRole !== ''): ?>
                    <p class="text-xs text-gray-500 truncate"><?= Html::encode($userRole) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php foreach ($footerLinks as $link): ?>
            <a class="footer-link" href="<?= Url::to($link['url']) ?>" title="<?= Html::encode($link['label']) ?>">
                <span class="material-symbols-outlined shrink-0 text-[1.25rem]"><?= Html::encode($link['icon']) ?></span>
                <span class="footer-link-label min-w-0 truncate"><?= Html::encode($link['label']) ?></span>
            </a>
        <?php endforeach; ?>

        <?php if ($showLogout): ?>
            <?= Html::beginForm($logoutUrl, 'post', ['class' => 'w-full m-0']) ?>
                <button type="submit" class="logout-btn" title="Logout">
                    <span class="material-symbols-outlined shrink-0 text-[1.25rem]">logout</span>
                    <span class="logout-text min-w-0 truncate">Logout</span>
                </button>
            <?= Html::endForm() ?>
        <?php endif; ?>

        <button type="button" id="sidebar-toggle"
                class="sidebar-toggle flex items-center justify-center w-9 h-9 rounded-full bg-[#f27a44] hover:bg-[#eb652c] text-white shadow-sm transition-all focus:outline-none mx-auto mt-2 mb-2"
                aria-label="Toggle sidebar">
            <span class="material-symbols-outlined text-lg">chevron_left</span>
        </button>
    </div>
</aside>

<?php
$this->registerJs(<<<'JS'
(function() {
  var sidebar = document.getElementById('app-sidebar');
  var toggle = document.getElementById('sidebar-toggle');
  if (sidebar && toggle) {
    var stored = localStorage.getItem('app-sidebar-collapsed');
    if (stored === '1') sidebar.classList.add('collapsed');
    toggle.addEventListener('click', function() {
      sidebar.classList.toggle('collapsed');
      localStorage.setItem('app-sidebar-collapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
    });
  }

  document.querySelectorAll('.nav-group-toggle').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      var group = btn.closest('.nav-group');
      if (!group || (sidebar && sidebar.classList.contains('collapsed'))) return;
      var header = btn.closest('.nav-group-header');
      var submenu = header ? header.nextElementSibling : null;
      if (!submenu || !submenu.classList.contains('nav-submenu')) {
        submenu = group.querySelector(':scope > .nav-submenu');
      }
      var open = group.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (submenu) submenu.hidden = !open;
    });
  });

  try {
    localStorage.removeItem('nav-group-recruitment');
  } catch (e) {}

  function scrollActiveNavIntoView() {
    if (!sidebar || sidebar.classList.contains('collapsed')) return;
    var nav = document.getElementById('app-sidebar-nav');
    if (!nav) return;
    var active = nav.querySelector('.nav-section-link.active')
      || nav.querySelector('.nav-sublink.active')
      || nav.querySelector('.nav-link.active');
    if (!active) return;
    var navRect = nav.getBoundingClientRect();
    var activeRect = active.getBoundingClientRect();
    var offset = activeRect.top - navRect.top + nav.scrollTop;
    var target = offset - (nav.clientHeight / 2) + (active.offsetHeight / 2);
    nav.scrollTop = Math.max(0, Math.min(target, nav.scrollHeight - nav.clientHeight));
  }

  if (sidebar) {
    requestAnimationFrame(scrollActiveNavIntoView);
  }
})();
JS);
?>
