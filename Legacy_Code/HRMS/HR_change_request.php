<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/validation.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

/* ================================
   FILTER VALUES (whitelisted)
================================ */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$search  = validate_search($_GET['search'] ?? '');
$status  = validate_whitelist($_GET['status'] ?? '', ['Pending', 'Approved', 'Rejected', 'All'], '');
$urgency = validate_whitelist($_GET['urgency'] ?? '', ['Normal', 'Urgent', 'All'], '');

$where = [];
$params = [];

/* SEARCH */
if ($search !== '') {
    $where[] = "(e.full_name LIKE ? OR r.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

/* STATUS */
if ($status !== '' && $status !== 'All') {
    $where[] = "r.status = ?";
    $params[] = $status;
}

/* URGENCY */
if ($urgency !== '' && $urgency !== 'All') {
    $where[] = "r.urgency = ?";
    $params[] = $urgency;
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

/* ================================
   LOAD REQUESTS
================================ */

$sql = "
SELECT r.*, e.full_name, e.job_title
FROM employee_change_requests r
JOIN employees e ON e.id = r.employee_id
$whereSQL
ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* ================================
   SELECTED REQUEST
================================ */

$selected_id = validate_id($_GET['request_id'] ?? 0);
$selected_request = null;

if ($selected_id > 0) {
    $stmt = $pdo->prepare("
        SELECT r.*, e.full_name, e.job_title
        FROM employee_change_requests r
        JOIN employees e ON e.id = r.employee_id
        WHERE r.id = ?
    ");
    $stmt->execute([$selected_id]);
    $selected_request = $stmt->fetch(PDO::FETCH_ASSOC);
}


/* ================================
   APPROVE / REJECT
================================ */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        require_once __DIR__ . "/config/security_log.php";
        security_log('csrf_failure', ['context' => 'HR_change_request']);
        http_response_code(403);
        die('Invalid request');
    }
    $request_id = validate_id($_POST['request_id'] ?? 0);
    $action = validate_whitelist($_POST['action'] ?? '', ['approve', 'reject'], '');

    if ($request_id > 0 && $action !== '') {
        require_once __DIR__ . "/config/security_log.php";
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE employee_change_requests SET status='Approved' WHERE id=?");
            $stmt->execute([$request_id]);
            security_log('admin_action', ['action' => 'change_request_approve', 'request_id' => (string)$request_id]);
        }
        if ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE employee_change_requests SET status='Rejected' WHERE id=?");
            $stmt->execute([$request_id]);
            security_log('admin_action', ['action' => 'change_request_reject', 'request_id' => (string)$request_id]);
        }
    }

    $redirect = "HR_change_request.php?request_id=" . $request_id;
    if ($search !== '') $redirect .= "&search=" . urlencode($search);
    if ($status !== '') $redirect .= "&status=" . urlencode($status);
    if ($urgency !== '') $redirect .= "&urgency=" . urlencode($urgency);
    header("Location: " . $redirect);
    exit;
}

function statusBadge($status) {
    $s = strtolower(trim($status ?? ''));
    return match ($s) {
        'approved' => ['class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', 'label' => 'Approved'],
        'rejected' => ['class' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', 'label' => 'Rejected'],
        default   => ['class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'label' => 'Pending']
    };
}

$queryBase = "?search=" . urlencode($search) . "&status=" . urlencode($status) . "&urgency=" . urlencode($urgency);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Change Requests | CultureHR</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#642754",
                        "background-light": "#f7f6f8",
                        "background-dark": "#191022"
                    },
                    fontFamily: { display: ["Inter", "sans-serif"] },
                    borderRadius: { DEFAULT: "0.25rem", lg: "0.5rem", xl: "0.75rem", "2xl": "1rem", full: "9999px" }
                }
            }
        };
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="bg-[#f8f4f8] text-slate-900 font-display min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <?php $currentPage = 'change_requests'; include __DIR__ . '/navbar.php'; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <?php $pageTitle = 'Change Requests'; include __DIR__ . '/admin_header.php'; ?>

            <div class="flex-1 flex min-h-0">
                <!-- LEFT PANEL -->
                <div class="w-[420px] min-w-[360px] max-w-[480px] flex flex-col border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 shrink-0">
                        <form method="GET" class="space-y-4">
                            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>" />
                            <input type="hidden" name="urgency" value="<?= htmlspecialchars($urgency) ?>" />
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl">search</span>
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                    placeholder="Search by name or request ID..."
                                    class="w-full h-11 pl-10 pr-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all" />
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&urgency="
                                    class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= ($urgency === '' || $urgency === 'All') ? 'bg-[#eb652c] text-white shadow-md' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
                                    All
                                </a>
                                <a href="?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&urgency=Urgent"
                                    class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= $urgency === 'Urgent' ? 'bg-red-500 text-white shadow-lg' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
                                    Urgent
                                </a>
                                <a href="?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&urgency=Normal"
                                    class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= $urgency === 'Normal' ? 'bg-[#eb652c] text-white shadow-md' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
                                    Normal
                                </a>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="?search=<?= urlencode($search) ?>&status=All&urgency=<?= urlencode($urgency) ?>"
                                    class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= ($status === '' || $status === 'All') ? 'bg-[#eb652c] text-white shadow-md' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
                                    All Status
                                </a>
                                <a href="?search=<?= urlencode($search) ?>&status=Pending&urgency=<?= urlencode($urgency) ?>"
                                    class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= $status === 'Pending' ? 'bg-amber-500 text-white shadow-lg' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
                                    Pending
                                </a>
                                <a href="?search=<?= urlencode($search) ?>&status=Approved&urgency=<?= urlencode($urgency) ?>"
                                    class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= $status === 'Approved' ? 'bg-emerald-500 text-white shadow-lg' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
                                    Approved
                                </a>
                                <a href="?search=<?= urlencode($search) ?>&status=Rejected&urgency=<?= urlencode($urgency) ?>"
                                    class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= $status === 'Rejected' ? 'bg-red-500 text-white shadow-lg' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
                                    Rejected
                                </a>
                            </div>
                            <button type="submit" class="w-full h-11 px-4 rounded-xl bg-[#eb652c] text-white font-bold text-sm hover:bg-[#d85a24] transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-lg">search</span>
                                Search
                            </button>
                        </form>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 space-y-3">
                        <?php if (empty($requests)): ?>
                        <div class="flex flex-col items-center justify-center py-16 text-center">
                            <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-600 mb-3">inbox</span>
                            <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">No requests found</p>
                            <p class="text-xs text-slate-500 dark:text-slate-500 mt-1">Try adjusting your filters</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($requests as $r):
                            $reqStatus = statusBadge($r['status'] ?? 'Pending');
                            $preview = $r['new_value'] ?? '';
                            if (strlen($preview) > 100) $preview = substr($preview, 0, 100) . '…';
                        ?>
                        <a href="?request_id=<?= (int)$r['id'] ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&urgency=<?= urlencode($urgency) ?>"
                            class="block group">
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 hover:border-primary/40 hover:shadow-md hover:shadow-primary/5 transition-all <?= $selected_id && (int)$selected_id === (int)$r['id'] ? 'ring-2 ring-primary border-primary' : '' ?>">
                                <div class="flex justify-between items-start gap-2 mb-2">
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-slate-900 dark:text-white truncate group-hover:text-primary transition-colors">
                                            <?= htmlspecialchars($r['full_name']) ?>
                                        </h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                            <?= htmlspecialchars($r['job_title']) ?>
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 shrink-0">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded <?= ($r['urgency'] ?? '') === 'Urgent' ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400' ?>">
                                            <?= htmlspecialchars($r['urgency'] ?? 'Normal') ?>
                                        </span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded <?= $reqStatus['class'] ?>">
                                            <?= htmlspecialchars($reqStatus['label']) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if ($preview): ?>
                                <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 mt-1">
                                    <?= htmlspecialchars($preview) ?>
                                </p>
                                <?php endif; ?>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-2 font-medium">
                                    <?= date("M j, Y", strtotime($r['created_at'] ?? 'now')) ?>
                                </p>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT PANEL -->
                <div class="flex-1 flex flex-col min-w-0 bg-slate-50/50 dark:bg-slate-900/50">
                    <div class="flex-1 overflow-y-auto p-8">
                        <?php if ($selected_request): ?>
                        <div class="max-w-4xl mx-auto space-y-8">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div>
                                    <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                        Request #<?= (int)$selected_request['id'] ?>
                                    </h2>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                        Submitted by <span class="font-semibold text-slate-700 dark:text-slate-300"><?= htmlspecialchars($selected_request['full_name']) ?></span>
                                        · <?= htmlspecialchars($selected_request['job_title']) ?>
                                        · <?= date("M j, Y \a\t g:i A", strtotime($selected_request['created_at'])) ?>
                                    </p>
                                </div>
                                <a href="On_board.php?id=<?= (int)$selected_request['employee_id'] ?>"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shrink-0">
                                    <span class="material-symbols-outlined text-lg">person</span>
                                    View Employee Profile
                                </a>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-sm">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-base">history_edu</span>
                                        Current Value
                                    </h3>
                                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700 text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">
                                        <?= nl2br(htmlspecialchars($selected_request['old_value'] ?? '—')) ?>
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-sm">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-primary mb-3 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-base">edit_note</span>
                                        Proposed Change
                                    </h3>
                                    <div class="p-4 rounded-xl bg-primary/5 dark:bg-primary/10 border border-primary/20 dark:border-primary/30 text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">
                                        <?= nl2br(htmlspecialchars($selected_request['new_value'] ?? '—')) ?>
                                    </div>
                                </div>
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

                    <?php if ($selected_request && in_array(strtolower($selected_request['status'] ?? ''), ['pending', ''])): ?>
                    <div class="p-6 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shrink-0">
                        <form method="POST" class="flex items-center justify-end gap-3 max-w-4xl mx-auto">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                            <input type="hidden" name="request_id" value="<?= (int)$selected_request['id'] ?>" />
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
                        </form>
                    </div>
                    <?php elseif ($selected_request): ?>
                    <div class="p-6 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shrink-0">
                        <div class="flex items-center justify-center gap-2 max-w-4xl mx-auto">
                            <span class="material-symbols-outlined text-emerald-500">check_circle</span>
                            <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">This request has been <?= e(strtolower($selected_request['status'] ?? '')) ?>.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
