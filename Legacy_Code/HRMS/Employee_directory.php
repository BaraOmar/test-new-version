<?php
declare(strict_types=1);

require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/validation.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

/* ==========================
   Basic CSRF (for POST actions)
========================== */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* ==========================
   Helpers (e() from config/validation.php)
========================== */
function columnExists(PDO $pdo, string $table, string $column): bool {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

/* ==========================
   Detect optional columns
========================== */
$hasStatus = columnExists($pdo, 'employees', 'status');       // if you added it
$hasIsManager = columnExists($pdo, 'employees', 'is_manager'); // if you added it

/* ==========================
   Handle POST Actions (Delete / Set Manager / Change Status)
   - Kept inside this file so features "come to life"
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $postedToken)) {
        require_once __DIR__ . "/config/security_log.php";
        security_log('csrf_failure', ['context' => 'Employee_directory', 'action' => $_POST['action'] ?? '']);
        http_response_code(403);
        die("Forbidden (CSRF)");
    }

    $action = $_POST['action'] ?? '';
    $employeeId = (int)($_POST['employee_id'] ?? 0);

    $redirectBack = $_POST['redirect_back'] ?? 'Employee_directory.php';
    if (strpos($redirectBack, '//') !== false || preg_match('#^[a-z]+:#i', $redirectBack)) {
        $redirectBack = 'Employee_directory.php';
    }
    $redirectBack = basename($redirectBack) ?: 'Employee_directory.php';

    try {
        if ($employeeId > 0) {
            if ($action === 'delete') {
                // If other employees reference this as manager_id, set them to NULL first
                $pdo->prepare("UPDATE employees SET manager_id = NULL WHERE manager_id = ?")->execute([$employeeId]);
                $pdo->prepare("DELETE FROM employees WHERE id = ?")->execute([$employeeId]);
                require_once __DIR__ . "/config/security_log.php";
                security_log('admin_action', ['action' => 'employee_delete', 'target_employee_id' => (string)$employeeId]);
            }

            if ($action === 'set_manager') {
                if ($hasIsManager) {
                    $pdo->prepare("UPDATE employees SET is_manager = 1 WHERE id = ?")->execute([$employeeId]);
                } else {
                    // If you don't have is_manager column yet, we still make it "work" without breaking:
                    // We keep manager dropdown in On_board.php usually filtered by job_title containing 'manager' OR is_manager=1.
                    // So we set job_title to include "Manager" only if it doesn't already.
                    $stmt = $pdo->prepare("SELECT job_title FROM employees WHERE id = ?");
                    $stmt->execute([$employeeId]);
                    $jt = (string)($stmt->fetchColumn() ?? '');
                    if (stripos($jt, 'manager') === false) {
                        $pdo->prepare("UPDATE employees SET job_title = CONCAT(job_title, ' (manager)') WHERE id = ?")->execute([$employeeId]);
                    }
                }
                require_once __DIR__ . "/config/security_log.php";
                security_log('admin_action', ['action' => 'set_manager', 'target_employee_id' => (string)$employeeId]);
            }

            if ($action === 'change_status' && $hasStatus) {
                $newStatus = (string)($_POST['status'] ?? '');
                $allowed = ['Active', 'Onboarding', 'Leave', 'Terminated'];
                if (in_array($newStatus, $allowed, true)) {
                    $pdo->prepare("UPDATE employees SET status = ? WHERE id = ?")->execute([$newStatus, $employeeId]);
                    require_once __DIR__ . "/config/security_log.php";
                    security_log('admin_action', ['action' => 'change_status', 'target_employee_id' => (string)$employeeId, 'new_status' => $newStatus]);
                }
            }
        }
    } catch (Throwable $ex) {
        // You can log $ex->getMessage() if you want
        // We keep it quiet for clean UI
    }

    header("Location: " . $redirectBack);
    exit;
}

/* ==========================
   GET Filters (Search + Department + Position)
========================== */
$search = validate_search($_GET['search'] ?? '');
$dept = validate_id($_GET['dept'] ?? 0);
$pos  = validate_search($_GET['pos'] ?? '', 100);

/* ==========================
   Load Departments for filter dropdown
========================== */
$departments = [];
try {
    $departments = $pdo->query("SELECT id, name FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $ex) {
    $departments = [];
}

/* ==========================
   Build Employee List Query
========================== */
$selectStatus = $hasStatus ? "e.status" : "'Active' AS status";

$sql = "
    SELECT
        e.id,
        e.full_name,
        e.email,
        e.job_title,
        e.position_level,
        e.joining_date,
        e.department_id,
        e.manager_id,
        $selectStatus,
        d.name AS department_name,
        m.full_name AS manager_name
    FROM employees e
    LEFT JOIN departments d ON d.id = e.department_id
    LEFT JOIN employees m ON m.id = e.manager_id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (e.full_name LIKE ? OR e.email LIKE ? OR e.job_title LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($dept > 0) {
    $sql .= " AND e.department_id = ?";
    $params[] = $dept;
}

if ($pos !== '' && $pos !== 'all') {
    // your DB column is enum('l1','l2','l3','l4','l5') according to your screenshot
    $sql .= " AND e.position_level = ?";
    $params[] = $pos;
}

$sql .= " ORDER BY e.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
   For footer "Showing x to y of z"
========================== */
$totalRows = count($employees);
$showingText = $totalRows > 0 ? "Showing 1 to {$totalRows} of {$totalRows} employees" : "Showing 0 employees";

/* ==========================
   Badge styles for status
========================== */
function statusBadge(string $status): array {
    $s = strtolower(trim($status));
    if ($s === 'active') {
        return ['bg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', 'label' => 'Active'];
    }
    if ($s === 'onboarding') {
        return ['bg' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'label' => 'Onboarding'];
    }
    if ($s === 'leave') {
        return ['bg' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400', 'label' => 'Leave'];
    }
    if ($s === 'terminated') {
        return ['bg' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', 'label' => 'Terminated'];
    }
    return ['bg' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400', 'label' => $status ?: 'Active'];
}

/* ==========================
   Build "redirect back" URL (keep filters/search)
========================== */
$query = $_GET;
$redirectBack = basename(__FILE__) . (count($query) ? ('?' . http_build_query($query)) : '');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>HR Employee Directory | CultureHR</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#642754",
                        "background-light": "#f8f4f8",
                        "background-dark": "#191022",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style type="text/tailwindcss">
        body {
            font-family: 'Inter', sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .table-fixed-cols th, .table-fixed-cols td {
            white-space: nowrap;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <?php $currentPage = 'employees'; include __DIR__ . '/navbar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php $pageTitle = 'Directory'; include __DIR__ . '/admin_header.php'; ?>

            <main class="flex-1 overflow-y-auto bg-[#f8f4f8]">
                <div class="layout-content-container flex flex-col w-full max-w-[1400px] mx-auto px-8 py-8">
                    <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
                        <div class="flex flex-col gap-1">
                            <h1
                                class="text-slate-900 dark:text-slate-100 text-4xl font-black leading-tight tracking-tight">
                                Employee Directory</h1>
                            <p class="text-slate-500 dark:text-slate-400 text-base font-normal">Manage your
                                organization's talent and team structure.</p>
                        </div>

                        <div class="flex gap-3">
                            <!-- Add Employee (redirect to On_board.php) -->
                            <a href="On_board.php"
                                class="flex items-center gap-2 rounded-lg h-10 px-4 bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold shadow-md transition-all">
                                <span class="material-symbols-outlined text-lg">person_add</span>
                                <span>Add Employee</span>
                            </a>
                        </div>
                    </div>

                    <!-- Search + Filters (GET) -->
                    <form method="get"
                        class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row gap-4 mb-6">
                        <div class="flex-1 relative">
                            <span
                                class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                            <input name="search" value="<?= e($search) ?>"
                                class="w-full h-11 pl-10 pr-4 rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:ring-primary focus:border-primary border text-sm"
                                placeholder="Search by name, email, or role..." type="text" />
                        </div>

                        <div class="flex gap-3">
                            <!-- Department filter -->
                            <div class="relative min-w-[160px]">
                                <select name="dept" onchange="this.form.submit()"
                                    class="appearance-none w-full h-11 px-4 pr-10 rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm font-medium focus:ring-primary focus:border-primary border">
                                    <option value="0">Department: All</option>
                                    <?php foreach ($departments as $d): ?>
                                        <option value="<?= (int)$d['id'] ?>" <?= ((int)$d['id'] === $dept) ? 'selected' : '' ?>>
                                            <?= e((string)$d['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span
                                    class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></span>
                            </div>

                            <!-- Position filter -->
                            <div class="relative min-w-[160px]">
                                <select name="pos" onchange="this.form.submit()"
                                    class="appearance-none w-full h-11 px-4 pr-10 rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm font-medium focus:ring-primary focus:border-primary border">
                                    <option value="all">Position: All</option>
                                    <option value="l1" <?= ($pos === 'l1') ? 'selected' : '' ?>>l1</option>
                                    <option value="l2" <?= ($pos === 'l2') ? 'selected' : '' ?>>l2</option>
                                    <option value="l3" <?= ($pos === 'l3') ? 'selected' : '' ?>>l3</option>
                                    <option value="l4" <?= ($pos === 'l4') ? 'selected' : '' ?>>l4</option>
                                    <option value="l5" <?= ($pos === 'l5') ? 'selected' : '' ?>>l5</option>
                                </select>
                                <span
                                    class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></span>
                            </div>

                            <!-- Filter button keeps design, acts as submit -->
                            <button type="submit"
                                class="h-11 px-4 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition-colors flex items-center justify-center">
                                <span class="material-symbols-outlined">filter_list</span>
                            </button>
                        </div>
                    </form>

                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse table-auto table-fixed-cols">
                                <thead>
                                    <tr
                                        class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 min-w-[200px]">
                                            Name</th>
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 min-w-[180px]">
                                            Job Title</th>
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 min-w-[220px]">
                                            Email</th>
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 min-w-[140px]">
                                            Department</th>
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 min-w-[160px]">
                                            Manager</th>
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 min-w-[140px]">
                                            Joining Date</th>
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 min-w-[110px]">
                                            Status</th>
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-right w-16">
                                            Actions</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <?php if (!$employees): ?>
                                        <tr>
                                            <td colspan="8" class="px-6 py-8 text-sm text-slate-500 dark:text-slate-400">
                                                No employees found.
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($employees as $row): ?>
                                        <?php
                                            $badge = statusBadge((string)$row['status']);
                                            $deptName = (string)($row['department_name'] ?? '—');
                                            $mgrName  = (string)($row['manager_name'] ?? '—');
                                            $joinDate = (string)($row['joining_date'] ?? '');
                                        ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="size-9 rounded-full bg-primary/10 flex items-center justify-center overflow-hidden border border-slate-100 dark:border-slate-700 shrink-0">
                                                        <!-- keep same style (static avatar) -->
                                                        <img alt="<?= e((string)$row['full_name']) ?>" class="size-full object-cover"
                                                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuBDG_KedDc2ywyTakKSuRxPtvjiOYqr0bp6znUw8Gz3Gt4xVw4Ca3kQ8p9RO7Jufab0VWmdwR-5x2Lik49qdyA2Y_NUDnExNXcx-c2d3wqZfkUxNk74TFkxh8JTFCJf07VVOZP-Zvnb5fpxYAOfeOCDYSPD1kL0CiKNWjC2v71om7N0dj7tLKr2xo-_IQlgeXfJ72hXLIl1q0xoVEZgog290shr2Seo4teWm7qQ7PoYtsGQHLWpK-TD2-Aig7PSZu0fJXndKz8RfZhA" />
                                                    </div>
                                                    <span class="text-sm font-bold text-slate-900 dark:text-slate-100 truncate">
                                                        <?= e((string)$row['full_name']) ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                                <?= e((string)$row['job_title']) ?>
                                            </td>

                                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                                <?= e((string)$row['email']) ?>
                                            </td>

                                            <td class="px-6 py-4">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                                    <?= e($deptName) ?>
                                                </span>
                                            </td>

                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <div class="size-6 rounded-full bg-slate-200 bg-cover bg-center"
                                                        style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuDdd8yju85UayjdA2MWQS3pAqavQsrg01Ok4AeWdJPD23T_f0WtIrr4TFi8DzT9Mm5m0G6GwqDBIxrwL9xpWcsS0oirj5yy3vQkkJ0ogqmWvso3QYQ1wVRv3_4mljY_bn5EN3dI7CUHFJDkHOsBR2Y9mufkZYfwt_yfkY1fgi5cfFsoi7Pzt05edYp1RFtZg_UJkSH_H6E-lAiR1jx_s9R4_pGieoxPYalMgAN6urEI1vss2kOEQEfRzhqQM1fhsXDiamvTvNBgQ0hi');">
                                                    </div>
                                                    <span class="text-sm text-slate-600 dark:text-slate-400">
                                                        <?= e($mgrName) ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                                <?= $joinDate ? e($joinDate) : '—' ?>
                                            </td>

                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= e($badge['bg']) ?>">
                                                    <?= e($badge['label']) ?>
                                                </span>
                                            </td>

                                            <!-- ACTIONS (3 dots dropdown) -->
                                            <td class="px-6 py-4 text-right">
                                                <div class="relative inline-block text-left">
                                                   <button type="button" onclick="toggleMenu(<?= (int)$row['id'] ?>, this)"
                                                        class="text-slate-400 hover:text-primary transition-colors">
                                                        <span class="material-symbols-outlined">more_vert</span>
                                                    </button>

                                                   <div id="menu-<?= (int)$row['id'] ?>"
    class="hidden fixed w-56 bg-white dark:bg-slate-800
           border border-slate-200 dark:border-slate-700
           rounded-xl shadow-xl z-[9999] overflow-hidden">

                                                        <!-- Edit -->
                                                        <a href="On_board.php?id=<?= (int)$row['id'] ?>"
                                                            class="block px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-700">
                                                            Edit Employee
                                                        </a>

                                                        <!-- Delete (POST) -->
                                                        <form method="post" class="m-0">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="employee_id" value="<?= (int)$row['id'] ?>">
                                                            <input type="hidden" name="redirect_back" value="<?= e($redirectBack) ?>">
                                                            <button type="submit"
                                                                onclick="return confirm('Are you sure you want to delete this employee?')"
                                                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                                Delete Employee
                                                            </button>
                                                        </form>

                                                        <!-- Send Survey (opens Survey Builder with employee pre-selected) -->
                                                        <a href="survey_builder_interface.php?employee_id=<?= (int)$row['id'] ?>"
                                                            class="block px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-700">
                                                            Send Survey
                                                        </a>

                                                        <!-- Send Evaluation (opens Evaluation Builder with employee pre-selected) -->
                                                        <a href="evaluation_bulder_interface.php?employee_id=<?= (int)$row['id'] ?>"
                                                            class="block px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-700">
                                                            Send Evaluation
                                                        </a>

                                                        <!-- Set as Manager (POST) -->
                                                        <form method="post" class="m-0">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                            <input type="hidden" name="action" value="set_manager">
                                                            <input type="hidden" name="employee_id" value="<?= (int)$row['id'] ?>">
                                                            <input type="hidden" name="redirect_back" value="<?= e($redirectBack) ?>">
                                                            <button type="submit"
                                                                class="w-full text-left px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-700">
                                                                Set as Manager
                                                            </button>
                                                        </form>

                                                        <div class="border-t border-slate-200 dark:border-slate-700 my-1"></div>

                                                        <!-- Change Status (POST) -->
                                                        <?php if ($hasStatus): ?>
                                                            <?php foreach (['Active','Onboarding','Leave','Terminated'] as $st): ?>
                                                                <form method="post" class="m-0">
                                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                                    <input type="hidden" name="action" value="change_status">
                                                                    <input type="hidden" name="employee_id" value="<?= (int)$row['id'] ?>">
                                                                    <input type="hidden" name="status" value="<?= e($st) ?>">
                                                                    <input type="hidden" name="redirect_back" value="<?= e($redirectBack) ?>">
                                                                    <button type="submit"
                                                                        class="w-full text-left px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-700">
                                                                        Set <?= e($st) ?>
                                                                    </button>
                                                                </form>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <div class="px-4 py-2 text-xs text-slate-500 dark:text-slate-400">
                                                                Status feature needs a <b>status</b> column in employees.
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div
                            class="px-6 py-4 flex items-center justify-between border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                            <span class="text-sm text-slate-500 dark:text-slate-400"><?= e($showingText) ?></span>

                            
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
let activeMenu = null;

function toggleMenu(id, button) {
    const menu = document.getElementById('menu-' + id);

    // close all first
    document.querySelectorAll('[id^="menu-"]').forEach(m => m.classList.add('hidden'));

    if (activeMenu === menu) {
        activeMenu = null;
        return;
    }

    activeMenu = menu;

    const rect = button.getBoundingClientRect();
    const menuHeight = 320; // estimated height

    let top = rect.bottom + 6;
    let left = rect.right - 220;

    // if near bottom -> open upward
    if (window.innerHeight - rect.bottom < menuHeight) {
        top = rect.top - menuHeight - 6;
    }

    menu.style.top = top + "px";
    menu.style.left = left + "px";

    menu.classList.remove('hidden');
}

// close on outside click
document.addEventListener('click', function (e) {
    if (!e.target.closest('[onclick^="toggleMenu"]') &&
        !e.target.closest('[id^="menu-"]')) {

        document.querySelectorAll('[id^="menu-"]').forEach(m => m.classList.add('hidden'));
        activeMenu = null;
    }
});
</script>
</body>
</html>