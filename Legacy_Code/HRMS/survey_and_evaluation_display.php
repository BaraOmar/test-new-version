<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/validation.php";

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("DB connection \$pdo not found. Check config/db.php");
}

require_once __DIR__ . "/config/auth.php";
requireLogin();

$userId = (int)$_SESSION['user_id'];

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* Ensure direct-assignment tables exist */
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS survey_employee_targets (survey_id INT NOT NULL, employee_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (survey_id, employee_id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS evaluation_employee_targets (evaluation_id INT NOT NULL, employee_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (evaluation_id, employee_id))");
} catch (PDOException $e) { /* ignore */ }

/* =========================
   CONFIG
========================= */
// Change these if your target pages use different names
$surveyOpenPage = "survey_form.php";
$evaluationOpenPage = "evaluation_response.php";

/* =========================
   HELPERS (e() from config/validation.php)
========================= */
function formatDateTime(?string $date): string {
    if (empty($date)) return "Not set";
    $ts = strtotime($date);
    if (!$ts) return "Not set";
    return date("M d, Y h:i A", $ts);
}

function getTimeLeftLabel(?string $startAt, ?string $endAt, string $status = "active"): array {
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

function getStatusBadge(string $status): string {
    return match ($status) {
        'active' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
        'scheduled' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
        'closed' => 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400',
        default => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300',
    };
}

/* =========================
   FILTERS
========================= */
$search = validate_search($_GET['search'] ?? '');
$typeFilter = validate_whitelist($_GET['type'] ?? '', ['all', 'survey', 'evaluation'], 'all');
$view = validate_whitelist($_GET['view'] ?? '', ['grid', 'list'], 'grid');

// Current employee department (from session or fetch from employees table)
$currentDeptId = isset($_SESSION['department_id']) ? (int)$_SESSION['department_id'] : null;
if ($currentDeptId === null && $userId) {
    $empStmt = $pdo->prepare("SELECT department_id FROM employees WHERE id = ?");
    $empStmt->execute([$userId]);
    $empRow = $empStmt->fetch(PDO::FETCH_ASSOC);
    $currentDeptId = isset($empRow['department_id']) && $empRow['department_id'] !== null ? (int)$empRow['department_id'] : null;
}

/* =========================
   COUNTS FOR TABS
========================= */
$surveyCountStmt = $pdo->query("SELECT COUNT(*) FROM surveys");
$surveyCount = (int)$surveyCountStmt->fetchColumn();

$evaluationCountStmt = $pdo->query("SELECT COUNT(*) FROM evaluations");
$evaluationCount = (int)$evaluationCountStmt->fetchColumn();

/* =========================
   BUILD QUERY
========================= */
// We keep parameters for the inner surveys SELECT and the outer WHERE separately
// to ensure they match the correct placeholder order in the SQL.
$innerParams = [];
$outerParams = [];
$whereParts = [];

if ($search !== '') {
    $whereParts[] = "item_title LIKE ?";
    $outerParams[] = "%{$search}%";
}

if ($typeFilter === 'survey') {
    $whereParts[] = "item_type = 'survey'";
} elseif ($typeFilter === 'evaluation') {
    $whereParts[] = "item_type = 'evaluation'";
}

$whereSql = '';
if (!empty($whereParts)) {
    $whereSql = "WHERE " . implode(" AND ", $whereParts);
}

// Survey visibility: show if (a) user is in survey_employee_targets, OR (b) no employee targets and (org-wide or dept match)
$surveyWhereParts = [];
if ($userId) {
    $deptParam = ($currentDeptId !== null && $currentDeptId > 0) ? $currentDeptId : 0;
    $surveyWhereParts[] = "(
        EXISTS (SELECT 1 FROM survey_employee_targets se WHERE se.survey_id = s.id AND se.employee_id = ?)
        OR
        (NOT EXISTS (SELECT 1 FROM survey_employee_targets se2 WHERE se2.survey_id = s.id)
         AND (
            NOT EXISTS (SELECT 1 FROM survey_targets st WHERE st.survey_id = s.id)
            OR EXISTS (SELECT 1 FROM survey_targets st2 WHERE st2.survey_id = s.id AND st2.department_id = ?)
         ))
    )";
    $innerParams[] = $userId;
    $innerParams[] = $deptParam;
}
$surveyFilterSql = !empty($surveyWhereParts) ? " WHERE " . $surveyWhereParts[0] : "";

// Evaluation visibility: same logic as surveys
$evalWhereParts = [];
$deptParamEval = 0;
if ($userId) {
    $deptParamEval = ($currentDeptId !== null && $currentDeptId > 0) ? $currentDeptId : 0;
    $evalWhereParts[] = "(
        EXISTS (SELECT 1 FROM evaluation_employee_targets eet WHERE eet.evaluation_id = e.id AND eet.employee_id = ?)
        OR
        (NOT EXISTS (SELECT 1 FROM evaluation_employee_targets eet2 WHERE eet2.evaluation_id = e.id)
         AND (
            NOT EXISTS (SELECT 1 FROM evaluation_targets et WHERE et.evaluation_id = e.id)
            OR EXISTS (SELECT 1 FROM evaluation_targets et2 WHERE et2.evaluation_id = e.id AND et2.department_id = ?)
         ))
    )";
    $innerParams[] = $userId;
    $innerParams[] = $deptParamEval;
}
$evalFilterSql = !empty($evalWhereParts) ? " WHERE " . $evalWhereParts[0] : "";

$userRespondedSurvey = $userId ? "EXISTS (SELECT 1 FROM survey_responses sr2 WHERE sr2.survey_id = s.id AND sr2.employee_id = ?)" : "0";
$userRespondedEval   = $userId ? "EXISTS (SELECT 1 FROM evaluation_responses er2 WHERE er2.evaluation_id = e.id AND er2.employee_id = ?)" : "0";

// Params must match placeholder order in SQL: userRespondedSurvey, survey WHERE (emp, dept), userRespondedEval, eval WHERE (emp, dept)
$allParamsOrdered = $innerParams;
if ($userId) {
    $allParamsOrdered = [
        $userId,                    // userRespondedSurvey
        $innerParams[0], $innerParams[1],  // survey WHERE employee_id, department_id
        $userId,                    // userRespondedEval
        $innerParams[2], $innerParams[3],  // eval WHERE employee_id, department_id
    ];
}

$sql = "
    SELECT *
    FROM (
        SELECT
            s.id,
            s.title AS item_title,
            s.description AS item_description,
            'survey' AS item_type,
            s.created_at,
            s.start_at,
            s.end_at,
            s.status,
            s.is_anonymous,
            COUNT(DISTINCT sr.id) AS response_count,
            ({$userRespondedSurvey}) AS current_user_responded
        FROM surveys s
        LEFT JOIN survey_responses sr ON sr.survey_id = s.id
        {$surveyFilterSql}
        GROUP BY
            s.id, s.title, s.description, s.created_at,
            s.start_at, s.end_at, s.status, s.is_anonymous

        UNION ALL

        SELECT
            e.id,
            e.title AS item_title,
            NULL AS item_description,
            'evaluation' AS item_type,
            e.created_at,
            NULL AS start_at,
            NULL AS end_at,
            'active' AS status,
            NULL AS is_anonymous,
            COUNT(DISTINCT er.id) AS response_count,
            ({$userRespondedEval}) AS current_user_responded
        FROM evaluations e
        LEFT JOIN evaluation_responses er ON er.evaluation_id = e.id
        {$evalFilterSql}
        GROUP BY e.id, e.title, e.created_at
    ) AS combined_items
    {$whereSql}
    ORDER BY created_at DESC, id DESC
";

$stmt = $pdo->prepare($sql);
$allParams = $userId ? array_merge($allParamsOrdered, $outerParams) : array_merge($innerParams, $outerParams);
$stmt->execute($allParams);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalItems = count($items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Employee Survey &amp; Evaluation List</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="assets/hrms-theme.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#642754",
                        "background-light": "#f4f6f9",
                        "background-dark": "#191919",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.5rem",
                        "lg": "1rem",
                        "xl": "1.5rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>

<body class="bg-[#f8f4f8] text-slate-900 font-display">
<div class="relative flex min-h-screen w-full flex-col overflow-x-hidden">
    <div class="layout-container flex h-full grow flex-col">
        <?php $currentPage = 'survey_evaluation'; $pageTitle = 'Surveys & Evaluations'; include __DIR__ . '/employee_navbar.php'; ?>
        <main class="flex-1 flex flex-col max-w-6xl mx-auto w-full px-6 py-10 gap-8">
            <div class="flex flex-col gap-2">
                <h1 class="text-slate-900 dark:text-white text-4xl font-extrabold tracking-tight">My Surveys &amp; Evaluations</h1>
                <p class="text-slate-600 dark:text-slate-400 text-lg max-w-2xl">
                    Search, filter, and open your current surveys and evaluations in one place.
                </p>
            </div>

            <div class="flex flex-col gap-6">
                <div class="flex border-b border-slate-200 dark:border-slate-800 gap-8">
                    <a href="?type=survey&search=<?= urlencode($search) ?>&view=<?= e($view) ?>"
                       class="flex items-center gap-2 pb-4 pt-2 text-sm <?= $typeFilter === 'survey' ? 'border-b-2 border-[#eb652c] text-[#eb652c] font-bold' : 'border-b-2 border-transparent text-slate-500 dark:text-slate-400 font-semibold hover:text-slate-700' ?>">
                        Surveys
                        <span class="<?= $typeFilter === 'survey' ? 'bg-[#eb652c]/10 text-[#eb652c]' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' ?> text-xs px-2 py-0.5 rounded-full">
                            <?= $surveyCount ?>
                        </span>
                    </a>

                    <a href="?type=evaluation&search=<?= urlencode($search) ?>&view=<?= e($view) ?>"
                       class="flex items-center gap-2 pb-4 pt-2 text-sm <?= $typeFilter === 'evaluation' ? 'border-b-2 border-[#eb652c] text-[#eb652c] font-bold' : 'border-b-2 border-transparent text-slate-500 dark:text-slate-400 font-semibold hover:text-slate-700' ?>">
                        Evaluations
                        <span class="<?= $typeFilter === 'evaluation' ? 'bg-[#eb652c]/10 text-[#eb652c]' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' ?> text-xs px-2 py-0.5 rounded-full">
                            <?= $evaluationCount ?>
                        </span>
                    </a>

                    <a href="?type=all&search=<?= urlencode($search) ?>&view=<?= e($view) ?>"
                       class="flex items-center gap-2 pb-4 pt-2 text-sm <?= $typeFilter === 'all' ? 'border-b-2 border-[#eb652c] text-[#eb652c] font-bold' : 'border-b-2 border-transparent text-slate-500 dark:text-slate-400 font-semibold hover:text-slate-700' ?>">
                        All
                        <span class="<?= $typeFilter === 'all' ? 'bg-[#eb652c]/10 text-[#eb652c]' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' ?> text-xs px-2 py-0.5 rounded-full">
                            <?= $surveyCount + $evaluationCount ?>
                        </span>
                    </a>
                </div>

                <form method="GET" class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex flex-col sm:flex-row gap-3 flex-1">
                        <input type="hidden" name="view" value="<?= e($view) ?>">

                        <label class="flex flex-col min-w-0 flex-1 max-w-xl relative">
                            <div class="flex w-full items-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-3 h-12 shadow-sm">
                                <span class="material-symbols-outlined text-slate-500 text-lg">search</span>
                                <input
                                    name="search"
                                    value="<?= e($search) ?>"
                                    class="form-input w-full border-none bg-transparent focus:ring-0 text-sm placeholder:text-slate-500"
                                    placeholder="Search survey or evaluation by name..." />
                            </div>
                        </label>

                        <select name="type"
                                class="h-12 min-w-[180px] rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 text-sm font-semibold shadow-sm focus:border-primary focus:ring-primary">
                            <option value="all" <?= $typeFilter === 'all' ? 'selected' : '' ?>>All Tasks</option>
                            <option value="survey" <?= $typeFilter === 'survey' ? 'selected' : '' ?>>Surveys Only</option>
                            <option value="evaluation" <?= $typeFilter === 'evaluation' ? 'selected' : '' ?>>Evaluations Only</option>
                        </select>

                        <button type="submit"
                                class="h-12 px-5 rounded-lg bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold transition-all shadow-sm">
                            Filter
                        </button>

                        <a href="?type=all&view=<?= e($view) ?>"
                           class="h-12 px-5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center justify-center">
                            Reset
                        </a>
                    </div>

                    <div class="hidden sm:flex items-center p-1 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">
                        <a href="?type=<?= e($typeFilter) ?>&search=<?= urlencode($search) ?>&view=grid"
                           class="p-1.5 rounded <?= $view === 'grid' ? 'bg-slate-100 dark:bg-slate-700 text-[#eb652c] shadow-sm' : 'text-slate-400' ?>">
                            <span class="material-symbols-outlined text-lg">grid</span>
                        </a>
                        <a href="?type=<?= e($typeFilter) ?>&search=<?= urlencode($search) ?>&view=list"
                           class="p-1.5 rounded <?= $view === 'list' ? 'bg-slate-100 dark:bg-slate-700 text-[#eb652c] shadow-sm' : 'text-slate-400' ?>">
                            <span class="material-symbols-outlined text-lg">list</span>
                        </a>
                    </div>
                </form>

                <div class="flex items-center justify-between">
                    <div class="flex flex-wrap gap-2">
                        <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#eb652c]/10 text-[#eb652c] text-xs font-bold">
                            Showing <?= $totalItems ?> item<?= $totalItems !== 1 ? 's' : '' ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (empty($items)): ?>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-12 text-center shadow-sm">
                    <div class="mx-auto mb-4 h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                        <span class="material-symbols-outlined text-3xl text-slate-400">inbox</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No matching items found</h3>
                    <p class="text-slate-500 dark:text-slate-400">Try a different search or filter.</p>
                </div>
            <?php else: ?>

                <div class="<?= $view === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6' : 'flex flex-col gap-4' ?>">
                    <?php foreach ($items as $item): ?>
                        <?php
                            $isSurvey = $item['item_type'] === 'survey';
                            $isAnonymousLabel = $isSurvey
                                ? ((int)$item['is_anonymous'] === 1 ? 'Anonymous' : 'Non-Anonymous')
                                : 'Not configured';

                            $timeMeta = getTimeLeftLabel($item['start_at'], $item['end_at'], $item['status']);
                            $openLink = $isSurvey
                                ? $surveyOpenPage . '?id=' . (int)$item['id']
                                : $evaluationOpenPage . '?id=' . (int)$item['id'];

                            $buttonText = $isSurvey ? 'Open Survey' : 'Open Evaluation';

                            $typeBadgeClass = $isSurvey
                                ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
                                : 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400';

                            $hasResponded = !empty($item['current_user_responded']);
                        ?>

                        <div class="group bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col <?= $hasResponded ? 'ring-2 ring-emerald-500/30' : '' ?>">
                            <div class="p-5 flex-1">
                                <div class="flex justify-between items-start mb-4 gap-3">
                                    <div class="flex flex-wrap gap-2">
                                        <?php if ($hasResponded): ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1"></span>
                                            Answered
                                        </span>
                                        <?php endif; ?>
                                        <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded <?= getStatusBadge($item['status']) ?>">
                                            <?= e($item['status']) ?>
                                        </span>

                                        <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded <?= $typeBadgeClass ?>">
                                            <?= $isSurvey ? 'Survey' : 'Evaluation' ?>
                                        </span>

                                        <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                            <?= e($isAnonymousLabel) ?>
                                        </span>
                                    </div>
                                </div>

                                <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-tight mb-4 group-hover:text-primary transition-colors">
                                    <?= e($item['item_title']) ?>
                                </h3>

                                <?php if (!empty($item['item_description'])): ?>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 line-clamp-2">
                                        <?= e($item['item_description']) ?>
                                    </p>
                                <?php endif; ?>

                                <div class="flex flex-col gap-3 text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center gap-2">
                                        
                                        <span class="text-xs font-medium">Created: <?= formatDateTime($item['created_at']) ?></span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        
                                        <span class="text-xs font-medium">Start: <?= formatDateTime($item['start_at']) ?></span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                       
                                        <span class="text-xs font-medium">End: <?= formatDateTime($item['end_at']) ?></span>
                                    </div>

                                   
                                </div>
                            </div>

                            <div class="p-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 flex flex-col gap-4">
                                <a href="<?= e($openLink) ?>"
                                   class="w-full <?= $hasResponded ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-[#eb652c] hover:bg-[#d85a24]' ?> text-white py-2.5 rounded-lg text-sm font-bold transition-all text-center flex items-center justify-center gap-2">
                                    <?php if ($hasResponded): ?>
                                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1"></span>
                                    View Response
                                    <?php else: ?>
                                    <?= e($buttonText) ?>
                                    <?php endif; ?>
                                </a>

                                <div class="flex justify-center">
                                    <span class="text-[11px] font-semibold uppercase tracking-wider <?= e($timeMeta['color']) ?>">
                                        <?= e($timeMeta['text']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>

        <footer class="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 py-8 px-6 lg:px-20 mt-10">
            <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-2 opacity-60">
                    <span class="material-symbols-outlined text-primary text-xl">auto_graph</span>
                    <span class="text-xs font-semibold uppercase tracking-widest text-slate-900 dark:text-white">PulsePoint © 2024</span>
                </div>
                <div class="flex gap-6">
                    <a class="text-xs text-slate-500 hover:text-primary" href="#">Privacy Policy</a>
                    <a class="text-xs text-slate-500 hover:text-primary" href="#">Terms of Service</a>
                    <a class="text-xs text-slate-500 hover:text-primary" href="#">Support</a>
                </div>
            </div>
        </footer>

    </div>
</div>
</body>
</html>