<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\EmployeeChangeRequest[] $requests
 * @var common\modules\employee\models\EmployeeChangeRequest|null $selectedRequest
 * @var int $selectedId
 * @var string $search
 * @var string $status
 * @var string $urgency
 * @var int $resultCount
 * @var bool $hasActiveFilters
 * @var string $userName
 */

use common\modules\employee\models\EmployeeChangeRequest;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Change Requests | CultureHR';

$buildListUrl = static function (?int $requestId = null) use ($search, $status, $urgency): string {
    $params = ['/hr/hr/requests'];
    if ($search !== '') {
        $params['search'] = $search;
    }
    if ($status !== '') {
        $params['status'] = $status;
    }
    if ($urgency !== '') {
        $params['urgency'] = $urgency;
    }
    if ($requestId !== null && $requestId > 0) {
        $params['request_id'] = $requestId;
    }

    return Url::to($params);
};

$chipClass = static function (bool $active, string $tone = 'brand'): string {
    $base = 'cr-filter-chip w-full px-3 py-2 rounded-lg text-xs font-bold transition-all duration-150 border ';
    if (!$active) {
        return $base . 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50';
    }

    return match ($tone) {
        'urgent' => $base . 'border-red-300 bg-red-500 text-white shadow-sm',
        'pending' => $base . 'border-amber-300 bg-amber-500 text-white shadow-sm',
        'approved' => $base . 'border-emerald-300 bg-emerald-600 text-white shadow-sm',
        'rejected' => $base . 'border-rose-300 bg-rose-500 text-white shadow-sm',
        'normal' => $base . 'border-[#eb652c]/40 bg-[#eb652c] text-white shadow-sm',
        default => $base . 'border-[#642754]/30 bg-[#642754] text-white shadow-sm',
    };
};

$urgencyActive = static fn(string $value): bool => $urgency === $value;
$statusActive = static fn(string $value): bool => $status === $value;
$urgencyAllActive = $urgency === '';
$statusAllActive = $status === '';

$filterPageUrl = Url::to(['/hr/hr/requests']);

$this->registerJs(
    'window.CR_FILTER_PAGE = ' . json_encode($filterPageUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';',
    \yii\web\View::POS_HEAD
);

$this->registerCss(<<<'CSS'
#cr-filter-search::-webkit-search-decoration,
#cr-filter-search::-webkit-search-cancel-button,
#cr-filter-search::-webkit-search-results-button {
    display: none;
}
#cr-filter-search::-ms-clear {
    display: none;
    width: 0;
    height: 0;
}
CSS);

?>

<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => 'change_requests']) ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Change Requests', 'userName' => $userName]) ?>

        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="mx-6 mt-4 px-4 py-3 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-medium shrink-0">
                <?= Html::encode((string)Yii::$app->session->getFlash('success')) ?>
            </div>
        <?php endif; ?>

        <div class="flex-1 flex min-h-0">
            <div class="w-[420px] min-w-[360px] max-w-[480px] flex flex-col border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0">
                <div class="p-5 border-b border-slate-200 dark:border-slate-800 shrink-0 bg-slate-50/40">
                    <?= Html::beginForm(['/hr/hr/requests'], 'get', [
                        'id' => 'cr-filter-form',
                        'class' => 'space-y-5',
                    ]) ?>
                        <input type="hidden" name="status" id="cr-filter-status" value="<?= Html::encode($status) ?>" />
                        <input type="hidden" name="urgency" id="cr-filter-urgency" value="<?= Html::encode($urgency) ?>" />

                        <div class="w-full">
                            <label for="cr-filter-search" class="block text-[0.6875rem] font-bold uppercase tracking-[0.12em] text-slate-500 mb-2">
                                Search
                            </label>
                            <div class="group flex w-full items-stretch overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition-all duration-150 focus-within:border-[#642754] focus-within:ring-2 focus-within:ring-[#642754]/10">
                                <span class="inline-flex w-10 shrink-0 items-center justify-center border-r border-slate-100 bg-slate-50 text-slate-400 group-focus-within:text-[#642754]">
                                    <span class="material-symbols-outlined text-[1.2rem]" aria-hidden="true">search</span>
                                </span>
                                <input type="text"
                                       name="search"
                                       id="cr-filter-search"
                                       value="<?= Html::encode($search) ?>"
                                       placeholder="Title, employee name, or request ID"
                                       autocomplete="off"
                                       class="min-w-0 flex-1 h-10 border-0 bg-transparent px-3 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0" />
                                <button type="button"
                                        id="cr-search-clear"
                                        class="inline-flex w-9 shrink-0 items-center justify-center text-slate-400 transition-opacity hover:bg-slate-50 hover:text-slate-700 <?= $search === '' ? 'opacity-0 pointer-events-none' : '' ?>"
                                        aria-label="Clear search"
                                        title="Clear search">
                                    <span class="material-symbols-outlined text-[1.15rem]">close</span>
                                </button>
                                <button type="button"
                                        id="cr-search-submit"
                                        class="inline-flex h-10 shrink-0 items-center justify-center border-l border-slate-200 bg-[#642754] px-4 text-xs font-bold text-white transition-colors hover:bg-[#532244]"
                                        title="Run search">
                                    Search
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1.5">Press Enter or click Search to apply. Priority and status filters still apply instantly.</p>
                        </div>

                        <div>
                            <p class="text-[0.6875rem] font-bold uppercase tracking-[0.12em] text-slate-500 mb-2">Priority</p>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" class="<?= $chipClass($urgencyAllActive, 'brand') ?>" data-cr-field="urgency" data-cr-value="">All</button>
                                <button type="button" class="<?= $chipClass($urgencyActive('Urgent'), 'urgent') ?>" data-cr-field="urgency" data-cr-value="Urgent">Urgent</button>
                                <button type="button" class="<?= $chipClass($urgencyActive('Normal'), 'normal') ?>" data-cr-field="urgency" data-cr-value="Normal">Normal</button>
                            </div>
                        </div>

                        <div>
                            <p class="text-[0.6875rem] font-bold uppercase tracking-[0.12em] text-slate-500 mb-2">Status</p>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" class="<?= $chipClass($statusAllActive, 'brand') ?>" data-cr-field="status" data-cr-value="">All</button>
                                <button type="button" class="<?= $chipClass($statusActive('Pending'), 'pending') ?>" data-cr-field="status" data-cr-value="Pending">Pending</button>
                                <button type="button" class="<?= $chipClass($statusActive('Approved'), 'approved') ?>" data-cr-field="status" data-cr-value="Approved">Approved</button>
                                <button type="button" class="<?= $chipClass($statusActive('Rejected'), 'rejected') ?>" data-cr-field="status" data-cr-value="Rejected">Rejected</button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 pt-1 border-t border-slate-200">
                            <p class="text-xs font-semibold text-slate-600">
                                <span id="cr-result-count"><?= (int)$resultCount ?></span>
                                <?= (int)$resultCount === 1 ? 'request' : 'requests' ?>
                            </p>
                            <?php if ($hasActiveFilters): ?>
                                <button type="button" id="cr-clear-filters"
                                        class="text-xs font-bold text-[#642754] hover:text-[#eb652c] transition-colors">
                                    Clear filters
                                </button>
                            <?php endif; ?>
                        </div>
                    <?= Html::endForm() ?>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    <?php if (empty($requests)): ?>
                        <div class="flex flex-col items-center justify-center py-16 text-center">
                            <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-600 mb-3">inbox</span>
                            <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">No requests found</p>
                            <p class="text-xs text-slate-500 dark:text-slate-500 mt-1">Try adjusting your filters</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($requests as $r):
                        $reqStatus = $r->getStatusBadge();
                        $isSelected = $selectedId > 0 && (int)$r->id === $selectedId;
                        ?>
                        <a href="<?= $buildListUrl((int)$r->id) ?>" class="block group">
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 hover:border-primary/40 hover:shadow-md hover:shadow-primary/5 transition-all <?= $isSelected ? 'ring-2 ring-primary border-primary' : '' ?>">
                                <div class="flex justify-between items-start gap-2 mb-2">
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-slate-900 dark:text-white truncate group-hover:text-primary transition-colors">
                                            <?= Html::encode($r->employee->full_name ?? '—') ?>
                                        </h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                            <?= Html::encode($r->employee->job_title ?? '') ?>
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 shrink-0">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded <?= ($r->urgency ?? '') === 'Urgent' ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400' ?>">
                                            <?= Html::encode($r->urgency ?? 'Normal') ?>
                                        </span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded <?= $reqStatus['class'] ?>">
                                            <?= Html::encode($reqStatus['label']) ?>
                                        </span>
                                    </div>
                                </div>
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 line-clamp-2 mt-1">
                                    <?= Html::encode($r->getDisplayTitle()) ?>
                                </p>
                                <?php if (!empty($r->created_at)): ?>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-2 font-medium">
                                        <?= Html::encode(date('M j, Y', strtotime((string)$r->created_at))) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex-1 flex flex-col min-w-0 bg-slate-50/50 dark:bg-slate-900/50">
                <div class="flex-1 overflow-y-auto p-8">
                    <?php if ($selectedRequest !== null):
                        $employee = $selectedRequest->employee;
                        $submittedAt = !empty($selectedRequest->created_at)
                            ? date('M j, Y \a\t g:i A', strtotime((string)$selectedRequest->created_at))
                            : '—';
                        ?>
                        <div class="max-w-4xl mx-auto space-y-8">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div>
                                    <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                        <?= Html::encode($selectedRequest->getDisplayTitle()) ?>
                                    </h2>
                                    <p class="text-xs font-semibold text-slate-400 dark:text-slate-500 mt-1">
                                        Request #<?= (int)$selectedRequest->id ?>
                                    </p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                                        Submitted by
                                        <span class="font-semibold text-slate-700 dark:text-slate-300"><?= Html::encode($employee->full_name ?? '—') ?></span>
                                        · <?= Html::encode($employee->job_title ?? '—') ?>
                                        · <?= Html::encode($submittedAt) ?>
                                    </p>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <span class="text-xs font-bold px-2.5 py-1 rounded-full <?= ($selectedRequest->urgency ?? '') === 'Urgent' ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400' ?>">
                                            <?= Html::encode($selectedRequest->urgency ?? EmployeeChangeRequest::URGENCY_NORMAL) ?>
                                        </span>
                                        <?php $statusBadge = $selectedRequest->getStatusBadge(); ?>
                                        <span class="text-xs font-bold px-2.5 py-1 rounded-full <?= $statusBadge['class'] ?>">
                                            <?= Html::encode($statusBadge['label']) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if ($employee !== null): ?>
                                    <a href="<?= Url::to(['/hr/hr/onboarding', 'id' => (int)$employee->id]) ?>"
                                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shrink-0">
                                        <span class="material-symbols-outlined text-lg">person</span>
                                        View Employee Profile
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php if ($selectedRequest->hasAttribute('title') && trim((string)$selectedRequest->title) !== ''): ?>
                                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-6 py-5 shadow-sm">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Request title</p>
                                    <p class="text-lg font-bold text-slate-900 dark:text-white leading-snug">
                                        <?= Html::encode((string)$selectedRequest->title) ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <?= $this->render('_request-comparison', ['request' => $selectedRequest]) ?>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <article class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden flex flex-col">
                                    <header class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/40">
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                            <span class="material-symbols-outlined text-slate-500 text-xl">history_edu</span>
                                            Current content
                                        </h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">What the employee says is on record today.</p>
                                    </header>
                                    <div class="p-6 flex-1 text-left">
                                        <?= $this->render('_request-content', [
                                            'content' => (string)($selectedRequest->old_value ?? ''),
                                            'variant' => 'current',
                                        ]) ?>
                                    </div>
                                </article>
                                <article class="bg-white dark:bg-slate-800 rounded-2xl border border-primary/30 dark:border-primary/40 shadow-sm overflow-hidden flex flex-col ring-1 ring-primary/10">
                                    <header class="px-6 py-4 border-b border-primary/20 dark:border-primary/30 bg-primary/5 dark:bg-primary/10">
                                        <h3 class="text-sm font-bold text-primary flex items-center gap-2">
                                            <span class="material-symbols-outlined text-xl">edit_note</span>
                                            Proposed change
                                        </h3>
                                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">What the employee is asking HR to update.</p>
                                    </header>
                                    <div class="p-6 flex-1 text-left">
                                        <?= $this->render('_request-content', [
                                            'content' => (string)($selectedRequest->new_value ?? ''),
                                            'variant' => 'proposed',
                                        ]) ?>
                                    </div>
                                </article>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center min-h-[400px] text-center">
                            <span class="material-symbols-outlined text-7xl text-slate-200 dark:text-slate-600 mb-4">touch_app</span>
                            <h3 class="text-xl font-bold text-slate-700 dark:text-slate-300">Select a request</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-sm">Choose a change request from the list on the left to review details and approve or reject.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($selectedRequest !== null): ?>
                    <?php if ($selectedRequest->isPending()): ?>
                        <div class="p-6 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shrink-0">
                            <?= Html::beginForm(['/hr/hr/requests'], 'post', ['class' => 'flex items-center justify-end gap-3 max-w-4xl mx-auto']) ?>
                                <?= Html::hiddenInput('request_id', (int)$selectedRequest->id) ?>
                                <?= Html::hiddenInput('search', $search) ?>
                                <?= Html::hiddenInput('status', $status) ?>
                                <?= Html::hiddenInput('urgency', $urgency) ?>
                                <button type="submit" name="action" value="reject"
                                        class="px-6 py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold text-sm hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-200 dark:hover:border-red-800 hover:text-red-600 dark:hover:text-red-400 transition-all flex items-center gap-2">
                                    <span class="material-symbols-outlined text-xl">close</span>
                                    Reject
                                </button>
                                <button type="submit" name="action" value="approve"
                                        class="px-8 py-3 rounded-xl bg-[#eb652c] text-white font-bold text-sm shadow-lg hover:bg-[#d85a24] transition-all flex items-center gap-2">
                                    <span class="material-symbols-outlined text-xl">check_circle</span>
                                    Approve
                                </button>
                            <?= Html::endForm() ?>
                        </div>
                    <?php else: ?>
                        <div class="p-6 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shrink-0">
                            <div class="flex items-center justify-center gap-2 max-w-4xl mx-auto">
                                <span class="material-symbols-outlined text-emerald-500">check_circle</span>
                                <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">
                                    This request has been <?= Html::encode(strtolower((string)$selectedRequest->status)) ?>.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php
$this->registerJs(<<<'JS'
(function () {
    const form = document.getElementById('cr-filter-form');
    if (!form || !window.CR_FILTER_PAGE) return;

    const searchInput = document.getElementById('cr-filter-search');
    const statusInput = document.getElementById('cr-filter-status');
    const urgencyInput = document.getElementById('cr-filter-urgency');
    const clearFilters = document.getElementById('cr-clear-filters');
    const clearSearch = document.getElementById('cr-search-clear');
    const searchSubmit = document.getElementById('cr-search-submit');

    const applyFilters = function () {
        const target = new URL(window.CR_FILTER_PAGE, window.location.href);
        target.searchParams.delete('request_id');

        const search = searchInput ? searchInput.value.trim() : '';
        if (search) {
            target.searchParams.set('search', search);
        } else {
            target.searchParams.delete('search');
        }

        const status = statusInput ? statusInput.value.trim() : '';
        if (status) {
            target.searchParams.set('status', status);
        } else {
            target.searchParams.delete('status');
        }

        const urgency = urgencyInput ? urgencyInput.value.trim() : '';
        if (urgency) {
            target.searchParams.set('urgency', urgency);
        } else {
            target.searchParams.delete('urgency');
        }

        window.location.assign(target.toString());
    };

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        applyFilters();
    });

    form.querySelectorAll('[data-cr-field]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const field = button.getAttribute('data-cr-field');
            const value = button.getAttribute('data-cr-value') || '';
            if (field === 'status' && statusInput) {
                statusInput.value = value;
            }
            if (field === 'urgency' && urgencyInput) {
                urgencyInput.value = value;
            }
            applyFilters();
        });
    });

    const syncSearchClearButton = function () {
        if (!clearSearch || !searchInput) return;
        const hasValue = searchInput.value.trim() !== '';
        clearSearch.classList.toggle('opacity-0', !hasValue);
        clearSearch.classList.toggle('pointer-events-none', !hasValue);
    };

    if (searchInput) {
        syncSearchClearButton();
        searchInput.addEventListener('input', syncSearchClearButton);
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                applyFilters();
            }
        });
    }

    if (searchSubmit) {
        searchSubmit.addEventListener('click', function (event) {
            event.preventDefault();
            applyFilters();
        });
    }

    if (clearFilters) {
        clearFilters.addEventListener('click', function (event) {
            event.preventDefault();
            if (searchInput) searchInput.value = '';
            if (statusInput) statusInput.value = '';
            if (urgencyInput) urgencyInput.value = '';
            applyFilters();
        });
    }

    if (clearSearch && searchInput) {
        clearSearch.addEventListener('click', function (event) {
            event.preventDefault();
            searchInput.value = '';
            syncSearchClearButton();
            applyFilters();
        });
    }
})();
JS);
?>
