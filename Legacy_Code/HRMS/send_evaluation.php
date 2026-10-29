<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

$employeeId = (int)($_GET['id'] ?? 0);
if ($employeeId <= 0) {
    header("Location: Employee_directory.php");
    exit;
}

/* Fetch employee */
$stmt = $pdo->prepare("SELECT id, full_name, email, job_title FROM employees WHERE id = ?");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$employee) {
    header("Location: Employee_directory.php");
    exit;
}

/* Create table if not exists */
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS evaluation_employee_targets (
            evaluation_id INT NOT NULL,
            employee_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (evaluation_id, employee_id)
        )
    ");
} catch (PDOException $e) { /* table may already exist with different schema */ }

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* Handle POST: assign evaluation to employee */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Invalid request');
    }
    $evaluationId = (int)($_POST['evaluation_id'] ?? 0);
    if ($evaluationId <= 0) {
        $_SESSION['flash_error'] = "Please select an evaluation.";
        header("Location: send_evaluation.php?id=" . $employeeId);
        exit;
    }
    try {
        $ins = $pdo->prepare("INSERT IGNORE INTO evaluation_employee_targets (evaluation_id, employee_id) VALUES (?, ?)");
        $ins->execute([$evaluationId, $employeeId]);
        if ($ins->rowCount() > 0) {
            $_SESSION['flash_success'] = "Evaluation assigned to " . htmlspecialchars($employee['full_name']) . ". They will see it in their Surveys & Evaluations.";
        } else {
            $_SESSION['flash_error'] = "This evaluation was already assigned to this employee.";
        }
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = "Could not assign evaluation. It may not exist.";
    }
    header("Location: send_evaluation.php?id=" . $employeeId);
    exit;
}

/* Fetch evaluations */
$evaluations = [];
try {
    $cols = $pdo->query("SHOW COLUMNS FROM evaluations")->fetchAll(PDO::FETCH_COLUMN);
    $hasCreated = in_array('created_at', $cols, true);
    $sql = "SELECT id, title" . ($hasCreated ? ", created_at" : "") . " FROM evaluations ORDER BY id DESC";
    $evaluations = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($evaluations as &$ev) {
        if (empty($ev['created_at'])) $ev['created_at'] = date('Y-m-d H:i:s');
    }
} catch (Throwable $e) {
    $evaluations = [];
}

/* Already assigned to this employee */
$assignedIds = [];
try {
    $st = $pdo->prepare("SELECT evaluation_id FROM evaluation_employee_targets WHERE employee_id = ?");
    $st->execute([$employeeId]);
    $assignedIds = $st->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Send Evaluation to Employee | HRMS</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script>tailwind.config = { theme: { extend: { colors: { primary: '#642754' } } } }</script>
    <style>body { font-family: 'Inter', sans-serif; } .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }</style>
</head>
<body class="bg-[#f8f4f8] text-slate-900 min-h-screen flex">
    <?php $currentPage = 'evaluations'; include __DIR__ . '/navbar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php $pageTitle = 'Send Evaluation'; include __DIR__ . '/admin_header.php'; ?>
    <div class="flex-1 overflow-y-auto">
        <div class="max-w-2xl mx-auto px-6 py-10">
        <a href="Employee_directory.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary mb-6">
            <span class="material-symbols-outlined text-lg">arrow_back</span> Back to Directory
        </a>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 mb-6">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white mb-1">Send Direct Evaluation</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mb-4">Assign an evaluation to <strong><?= e($employee['full_name']) ?></strong>. Only this employee will receive it.</p>
            <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">person</span>
                </div>
                <div>
                    <p class="font-semibold text-slate-900 dark:text-white"><?= e($employee['full_name']) ?></p>
                    <p class="text-sm text-slate-500"><?= e($employee['email']) ?> · <?= e($employee['job_title'] ?? '—') ?></p>
                </div>
            </div>
        </div>

        <?php if ($flashSuccess): ?>
        <div class="mb-6 p-4 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200 rounded-lg text-sm"><?= e($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if ($flashError): ?>
        <div class="mb-6 p-4 bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-200 rounded-lg text-sm"><?= e($flashError) ?></div>
        <?php endif; ?>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <h2 class="px-6 py-4 font-semibold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700">Select an evaluation to send</h2>
            <?php if (empty($evaluations)): ?>
            <div class="p-8 text-center text-slate-500">
                <span class="material-symbols-outlined text-4xl mb-2 opacity-50">rate_review</span>
                <p>No evaluations available. Create one from the Evaluation Builder first.</p>
                <a href="evaluation_bulder_interface.php" class="inline-flex items-center gap-2 mt-4 text-primary font-semibold hover:underline">
                    <span class="material-symbols-outlined text-lg">add</span> Create Evaluation
                </a>
            </div>
            <?php else: ?>
            <form method="post" class="divide-y divide-slate-100 dark:divide-slate-700">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                <?php foreach ($evaluations as $ev): ?>
                <?php $alreadyAssigned = in_array((string)$ev['id'], $assignedIds, true); ?>
                <label class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 cursor-pointer <?= $alreadyAssigned ? 'opacity-60' : '' ?>">
                    <input type="radio" name="evaluation_id" value="<?= (int)$ev['id'] ?>" <?= $alreadyAssigned ? 'disabled' : '' ?> class="rounded-full border-slate-300 text-primary focus:ring-primary" />
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-900 dark:text-white truncate"><?= e($ev['title']) ?></p>
                        <p class="text-xs text-slate-500"><?= !empty($ev['created_at']) ? 'Created ' . date('M j, Y', strtotime($ev['created_at'])) : '' ?></p>
                    </div>
                    <?php if ($alreadyAssigned): ?>
                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 shrink-0">Already assigned</span>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50">
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-[#eb652c] text-white font-semibold rounded-lg hover:bg-[#d85a24] transition-all">
                        Send Evaluation to <?= e(explode(' ', $employee['full_name'])[0] ?? 'Employee') ?>
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
        </div>
    </div>
    </div>
</body>
</html>
