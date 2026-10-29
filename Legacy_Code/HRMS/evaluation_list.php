<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/validation.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("DB connection \$pdo not found. Check config/db.php");
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// =========================
//  HELPERS (e() from config/validation.php)
// =========================
function formatDateTime(?string $date): string {
    if (empty($date)) return "Not set";
    $ts = strtotime($date);
    if (!$ts) return "Not set";
    return date("M d, Y", $ts);
}

function humanTimeDiff(int $seconds): string {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);

    if ($days > 0) {
        return $days . " day" . ($days > 1 ? "s" : "");
    }

    if ($hours > 0) {
        return $hours . " hour" . ($hours > 1 ? "s" : "");
    }

    return max(1, $minutes) . " minute" . (max(1, $minutes) > 1 ? "s" : "");
}

function getTimeLeftLabel(?string $startAt, ?string $endAt, ?string $status = null): array {
    $now = time();

    if (!empty($startAt)) {
        $startTs = strtotime($startAt);
        if ($startTs && $now < $startTs) {
            $diff = $startTs - $now;
            return [
                'text' => "Starts in " . humanTimeDiff($diff),
                'color' => "text-amber-600 dark:text-amber-400"
            ];
        }
    }

    if (!empty($endAt)) {
        $endTs = strtotime($endAt);
        if ($endTs) {
            if ($now > $endTs) {
                return [
                    'text' => "Closed",
                    'color' => "text-rose-600 dark:text-rose-400"
                ];
            }

            $diff = $endTs - $now;
            return [
                'text' => "Ends in " . humanTimeDiff($diff),
                'color' => "text-rose-600 dark:text-rose-400"
            ];
        }
    }

    if ($status === "draft") {
        return [
            'text' => "Draft",
            'color' => "text-slate-500 dark:text-slate-400"
        ];
    }

    if ($status === "scheduled") {
        return [
            'text' => "Scheduled",
            'color' => "text-amber-600 dark:text-amber-400"
        ];
    }

    return [
        'text' => "No deadline",
        'color' => "text-slate-500 dark:text-slate-400"
    ];
}

function getStatusPill(string $status): array {
    $status = strtolower($status);

    return match ($status) {
        'active' => [
            'label' => 'Active',
            'class' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
        ],
        'in_progress' => [
            'label' => 'In Progress',
            'class' => 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
        ],
        'closing_soon' => [
            'label' => 'Closing Soon',
            'class' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
        ],
        'closed' => [
            'label' => 'Closed',
            'class' => 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400',
        ],
        default => [
            'label' => ucfirst($status),
            'class' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        ],
    };
}

// =========================
//  FILTERS
// =========================
$search = validate_search($_GET['search'] ?? '');
$viewMode = validate_whitelist($_GET['view'] ?? '', ['list', 'grid'], 'grid');

// =========================
//  QUERY EVALUATIONS
// =========================
$params = [];
$where = [];

if ($search !== '') {
    $where[] = "e.title LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$sql = "
    SELECT 
        e.id,
        e.title,
        e.description,
        e.status,
        e.start_at,
        e.end_at,
        e.created_at,
        CASE 
            WHEN EXISTS (SELECT 1 FROM evaluation_employee_targets eet WHERE eet.evaluation_id = e.id) THEN 'employee'
            WHEN EXISTS (SELECT 1 FROM evaluation_targets et WHERE et.evaluation_id = e.id) THEN 'department'
            ELSE 'organization'
        END AS audience_type,
        (
            SELECT COUNT(*) 
            FROM evaluation_responses er2 
            WHERE er2.evaluation_id = e.id
        ) AS response_count,
        CASE 
            WHEN EXISTS (SELECT 1 FROM evaluation_employee_targets eet2 WHERE eet2.evaluation_id = e.id) THEN (
                SELECT COUNT(*) FROM evaluation_employee_targets eet3 WHERE eet3.evaluation_id = e.id
            )
            WHEN EXISTS (SELECT 1 FROM evaluation_targets et2 WHERE et2.evaluation_id = e.id) THEN (
                SELECT COUNT(*) 
                FROM employees emp
                WHERE emp.department_id IN (
                    SELECT et3.department_id 
                    FROM evaluation_targets et3 
                    WHERE et3.evaluation_id = e.id
                )
            )
            ELSE (SELECT COUNT(*) FROM employees emp_all)
        END AS target_count
    FROM evaluations e
    {$whereSql}
    ORDER BY 
        e.created_at DESC, e.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalEvaluations = count($evaluations);

$evaluationResponsePage = "evaluation_results.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Published Evaluations List Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <link href="assets/hrms-theme.css" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#642754",
                        "primary-dark": "#58264a",
                        "accent": "#eb652c",
                        "accent-dark": "#d85a24",
                        "background-light": "#f8f4f8",
                        "background-dark": "#2d1f2d",
                    },
                    fontFamily: { "display": ["Inter", "sans-serif"] },
                    borderRadius: {
                        "DEFAULT": "0.5rem",
                        "lg": "0.75rem",
                        "xl": "1rem",
                        "2xl": "1.5rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style type="text/tailwindcss">
        body { font-family: 'Inter', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @layer components {
            .nav-item-active {
                @apply bg-[#eb652c]/15 text-[#eb652c] font-bold rounded-lg;
            }
            .nav-item {
                @apply flex items-center gap-3 px-4 py-3 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer rounded-lg font-medium;
            }
        }
    </style>
</head>

<body
    class="bg-[#f8f4f8] text-slate-900 font-display transition-colors duration-200">
    <div class="flex h-screen overflow-hidden">
        <?php $currentPage = 'evaluations'; include __DIR__ . '/navbar.php'; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php $pageTitle = 'Evaluations'; include __DIR__ . '/admin_header.php'; ?>
            <div class="flex-1 overflow-auto">
            <main class="h-full overflow-y-auto bg-[#f8f4f8] p-8 no-scrollbar">
                <form method="GET" class="mb-6 max-w-2xl relative group">
                    <input type="hidden" name="view" value="<?= e($viewMode) ?>" />
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#eb652c] transition-colors">search</span>
                    <input
                        class="w-full h-11 pl-12 pr-4 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-[#eb652c]/50 focus:border-[#eb652c] outline-none"
                        placeholder="Search evaluations..." type="text" name="search"
                        value="<?= e($search) ?>" />
                </form>
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Published Evaluations</h2>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                            Showing <?= $totalEvaluations ?> published evaluation<?= $totalEvaluations === 1 ? '' : 's' ?>
                            for your organization
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="evaluation_bulder_interface.php"
                           class="flex items-center gap-2 bg-[#eb652c] hover:bg-[#d85a24] text-white font-semibold py-2.5 px-5 rounded-xl shadow-md transition-all text-sm group">
                            <span
                                class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform">add</span>
                            Create Evaluation
                        </a>
                        <div
                            class="flex items-center gap-3 bg-white dark:bg-slate-900 p-1 rounded-xl border border-slate-200 dark:border-slate-800">
                            <a href="?search=<?= urlencode($search) ?>&view=grid"
                                class="p-2 rounded-lg transition-colors <?= $viewMode === 'grid' ? 'bg-[#eb652c]/20 text-[#eb652c]' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300' ?>">
                                <span class="material-symbols-outlined text-[20px]">grid_view</span>
                            </a>
                            <a href="?search=<?= urlencode($search) ?>&view=list"
                                class="p-2 rounded-lg transition-colors <?= $viewMode === 'list' ? 'bg-[#eb652c]/20 text-[#eb652c]' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300' ?>">
                                <span class="material-symbols-outlined text-[20px]">format_list_bulleted</span>
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (empty($evaluations)): ?>
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl p-10 border border-dashed border-slate-200 dark:border-slate-800 flex flex-col items-center justify-center text-center gap-3">
                            <div
                                class="h-12 w-12 rounded-full bg-[#eb652c]/10 flex items-center justify-center mb-1">
                            <span class="material-symbols-outlined text-[#eb652c]">playlist_add</span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">No evaluations found</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md">
                            Create your first evaluation cycle to start collecting structured performance feedback from your teams.
                        </p>
                        <a href="evaluation_bulder_interface.php"
                           class="mt-2 inline-flex items-center gap-2 bg-[#eb652c] hover:bg-[#d85a24] text-white font-semibold py-2.5 px-4 rounded-xl text-sm shadow-md transition-all">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            New Evaluation
                        </a>
                    </div>
                <?php else: ?>
                    <?php if ($viewMode === 'list'): ?>
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Evaluation</th>
                                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Due Date</th>
                                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Completion</th>
                                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <?php foreach ($evaluations as $evaluation): ?>
                                    <?php
                                        $statusRaw = $evaluation['status'] ?? 'active';
                                        $statusInfo = getStatusPill($statusRaw);
                                        $timeMeta = getTimeLeftLabel($evaluation['start_at'] ?? null, $evaluation['end_at'] ?? null, $statusRaw);
                                        $responses = (int)($evaluation['response_count'] ?? 0);
                                        $target = (int)($evaluation['target_count'] ?? 0);
                                        $completionPercent = $target > 0 ? (int)round(min(100, ($responses / $target) * 100)) : 0;
                                        $dueLabel = !empty($evaluation['end_at']) ? formatDateTime($evaluation['end_at']) : "No due date";
                                        $respondentLabel = $target > 0 ? "{$responses}/{$target}" : (string)$responses;
                                        $openLink = $evaluationResponsePage . '?id=' . (int)$evaluation['id'];
                                    ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <h3 class="font-bold text-slate-900 dark:text-white"><?= e($evaluation['title']) ?></h3>
                                            <?php if (!empty($evaluation['description'])): ?>
                                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1"><?= e($evaluation['description']) ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= e($statusInfo['class']) ?>"><?= e($statusInfo['label']) ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400"><?= e($dueLabel) ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="h-2 w-24 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-[#eb652c] rounded-full" style="width: <?= $completionPercent ?>%"></div>
                                                </div>
                                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300"><?= $completionPercent ?>%</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="<?= e($openLink) ?>"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-[#eb652c]/10 hover:text-[#eb652c] border border-slate-200 dark:border-slate-700 hover:border-[#eb652c]/40 transition-all">
                                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                                    View Results
                                                </a>
                                                <form method="POST" action="delete_evaluation.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this evaluation?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                    <input type="hidden" name="id" value="<?= (int)$evaluation['id'] ?>">
                                                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-900/20 border border-rose-200 dark:border-rose-800 hover:border-rose-400 transition-all">
                                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="grid grid-cols-1 xl:grid-cols-2 2xl:grid-cols-3 gap-6">
                        <?php foreach ($evaluations as $evaluation): ?>
                            <?php
                                $statusRaw   = $evaluation['status'] ?? 'active';
                                $statusInfo  = getStatusPill($statusRaw);
                                $timeMeta    = getTimeLeftLabel($evaluation['start_at'] ?? null, $evaluation['end_at'] ?? null, $statusRaw);
                                $responses   = (int)($evaluation['response_count'] ?? 0);
                                $target      = (int)($evaluation['target_count'] ?? 0);
                                $audience    = $evaluation['audience_type'] ?? 'organization';

                                // Completion logic:
                                // 1) Organization-wide: 100% when all employees responded.
                                // 2) Department-wide: 100% when all targeted department employees responded.
                                $completionPercent = 0;
                                if ($target > 0) {
                                    $completionPercent = (int)round(min(100, ($responses / $target) * 100));
                                }

                                $dueLabel = !empty($evaluation['end_at']) ? formatDateTime($evaluation['end_at']) : "No due date";
                                $respondentLabel = $target > 0
                                    ? "{$responses}/{$target} Respondents"
                                    : "{$responses} Response" . ($responses === 1 ? '' : 's');
                                $openLink = $evaluationResponsePage . '?id=' . (int)$evaluation['id'];
                            ?>
                            <div
                                class="group bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-lg hover:border-[#eb652c]/30 transition-all flex flex-col justify-between h-full">
                                <div>
                                    <div class="flex justify-between items-start mb-4">
                                        <span
                                            class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= e($statusInfo['class']) ?>">
                                            <?= e($statusInfo['label']) ?>
                                        </span>
                                        <div class="flex gap-1">
                                            <form method="POST" action="delete_evaluation.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this evaluation?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="id" value="<?= (int)$evaluation['id'] ?>">
                                                <button type="submit" class="p-1.5 text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg transition-colors" title="Delete">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <h3
                                        class="font-bold text-lg text-slate-900 dark:text-white group-hover:text-[#eb652c] transition-colors leading-tight mb-2 line-clamp-2">
                                        <?= e($evaluation['title']) ?>
                                    </h3>
                                    <?php if (!empty($evaluation['description'])): ?>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 line-clamp-2">
                                            <?= e($evaluation['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500 mb-6">
                                        <div class="flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-sm">event_note</span>
                                            Due: <?= e($dueLabel) ?>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-sm">group</span>
                                            <?= e($respondentLabel) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between text-xs font-bold">
                                            <span class="text-slate-600 dark:text-slate-400">Completion Rate</span>
                                            <span class="text-[#eb652c] bg-[#eb652c]/10 px-2 py-0.5 rounded-full font-bold">
                                                <?= $completionPercent ?>%
                                            </span>
                                        </div>
                                        <div class="h-2.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                            <div class="h-full bg-[#eb652c] rounded-full <?= $completionPercent >= 80 ? 'shadow-[0_0_8px_rgba(235,101,44,0.5)]' : '' ?>"
                                                 style="width: <?= $completionPercent ?>%"></div>
                                        </div>
                                    </div>
                                    <div
                                        class="pt-4 border-t border-slate-50 dark:border-slate-800 mt-2 flex items-center justify-between gap-4">
                                        <div class="flex -space-x-2 shrink-0">
                                            <div
                                                class="h-8 w-8 rounded-full border-2 border-white dark:border-slate-900 bg-slate-200 overflow-hidden shadow-sm">
                                                <img class="object-cover h-full w-full"
                                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuD5-cCAHzpxjuVsK4m11R28Bq4f4FRtZ7QS5kW8eP8WdBlDDQ8-xO7dXIq-rvmnaRrNti4FZNVc02lyCVI6WEvfTCgf8lIx70VPdDnD3A-O_W0MUe21XfjHgfYMxxuw0KrE6q0JJwFBOn9Iow0Bu8QlONgkPNrWUCZLTXzVH9r44lcOye3ipf03Pz-c5UQvXuBP2wSBNUt8rkDfmQZUoYdma7-6fQNxp0uitklp4pGIVYnkKHlqBCe1vwrEB6ejA00XdO7inFSy9OdG" />
                                            </div>
                                            <div
                                                class="h-8 w-8 rounded-full border-2 border-white dark:border-slate-900 bg-slate-200 flex items-center justify-center text-[10px] font-bold text-slate-600 shadow-sm">
                                                +<?= max(1, min(99, $responses)) ?>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <a href="<?= e($openLink) ?>"
                                                class="flex items-center justify-center gap-1.5 px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-[#eb652c]/10 hover:text-[#eb652c] hover:border-[#eb652c]/40 transition-all whitespace-nowrap bg-white dark:bg-slate-900">
                                                <span class="material-symbols-outlined text-[16px]">visibility</span>
                                                View Results
                                            </a>
                                            <form method="POST" action="delete_evaluation.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this evaluation?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="id" value="<?= (int)$evaluation['id'] ?>">
                                                <button type="submit" class="flex items-center justify-center gap-1.5 px-3 py-2 border border-rose-200 dark:border-rose-800 rounded-lg text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-all">
                                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="text-[11px] font-semibold text-right <?= e($timeMeta['color']) ?>">
                                        <?= e($timeMeta['text']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </main>

        </div>
    </div>
</body>

</html>