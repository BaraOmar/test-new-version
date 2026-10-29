<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/auth.php";
requireLogin();

require_once __DIR__ . "/config/db.php";

$userId = (int)$_SESSION['user_id'];

/* Fetch employee + department */
$stmt = $pdo->prepare("
    SELECT 
        e.full_name,
        e.job_title,
        e.status,
        e.position_level,
        e.employment_type,
        e.location,
        e.department_id,
        d.name AS department_name
    FROM employees e
    LEFT JOIN departments d ON d.id = e.department_id
    WHERE e.id = ?
    LIMIT 1
");

$stmt->execute([$userId]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$emp) {
    header("Location: Login_page.php");
    exit;
}

$empDeptId = isset($emp['department_id']) ? (int)$emp['department_id'] : null;

/* Ensure direct-assignment tables exist */
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS survey_employee_targets (survey_id INT NOT NULL, employee_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (survey_id, employee_id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS evaluation_employee_targets (evaluation_id INT NOT NULL, employee_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (evaluation_id, employee_id))");
} catch (PDOException $e) { /* ignore */ }

/* =========================
   Pending Surveys (employee-targeted exclusive; else org-wide or dept)
========================= */
$pendingSurveySql = "
    SELECT COUNT(*) FROM surveys s
    WHERE (
        (EXISTS (SELECT 1 FROM survey_employee_targets se_check WHERE se_check.survey_id = s.id)
         AND EXISTS (SELECT 1 FROM survey_employee_targets se WHERE se.survey_id = s.id AND se.employee_id = ?))
        OR
        (NOT EXISTS (SELECT 1 FROM survey_employee_targets se_check WHERE se_check.survey_id = s.id)
         AND (
            NOT EXISTS (SELECT 1 FROM survey_targets st WHERE st.survey_id = s.id)
            OR EXISTS (SELECT 1 FROM survey_targets st WHERE st.survey_id = s.id AND st.department_id = ?)
         ))
    )
    AND NOT EXISTS (
        SELECT 1 FROM survey_responses sr
        WHERE sr.survey_id = s.id AND sr.employee_id = ?
    )
";
$pendingSurveyStmt = $pdo->prepare($pendingSurveySql);
$pendingSurveyStmt->execute([$userId, $empDeptId ?: 0, $userId]);
$pendingSurveys = (int)$pendingSurveyStmt->fetchColumn();

/* =========================
   Pending Evaluations (employee-targeted exclusive; else org-wide or dept)
========================= */
$pendingEvalSql = "
    SELECT COUNT(*) FROM evaluations e
    WHERE (
        (EXISTS (SELECT 1 FROM evaluation_employee_targets eet_check WHERE eet_check.evaluation_id = e.id)
         AND EXISTS (SELECT 1 FROM evaluation_employee_targets eet WHERE eet.evaluation_id = e.id AND eet.employee_id = ?))
        OR
        (NOT EXISTS (SELECT 1 FROM evaluation_employee_targets eet_check WHERE eet_check.evaluation_id = e.id)
         AND (
            NOT EXISTS (SELECT 1 FROM evaluation_targets et WHERE et.evaluation_id = e.id)
            OR EXISTS (SELECT 1 FROM evaluation_targets et WHERE et.evaluation_id = e.id AND et.department_id = ?)
         ))
    )
    AND NOT EXISTS (
        SELECT 1 FROM evaluation_responses er
        WHERE er.evaluation_id = e.id AND er.employee_id = ?
    )
";
$pendingEvalStmt = $pdo->prepare($pendingEvalSql);
$pendingEvalStmt->execute([$userId, $empDeptId ?: 0, $userId]);
$pendingEvaluations = (int)$pendingEvalStmt->fetchColumn();

/* =========================
   Completed Surveys & Evaluations (for progress %)
========================= */
$completedSurveysStmt = $pdo->prepare("SELECT COUNT(*) FROM survey_responses WHERE employee_id = ?");
$completedSurveysStmt->execute([$userId]);
$completedSurveys = (int)$completedSurveysStmt->fetchColumn();

$completedEvalsStmt = $pdo->prepare("SELECT COUNT(*) FROM evaluation_responses WHERE employee_id = ?");
$completedEvalsStmt->execute([$userId]);
$completedEvals = (int)$completedEvalsStmt->fetchColumn();

/* =========================
   Total Assigned (for career progress denominator)
========================= */
$totalAssignedSurveysSql = "
    SELECT COUNT(*) FROM surveys s
    WHERE (
        (EXISTS (SELECT 1 FROM survey_employee_targets se_check WHERE se_check.survey_id = s.id)
         AND EXISTS (SELECT 1 FROM survey_employee_targets se WHERE se.survey_id = s.id AND se.employee_id = ?))
        OR
        (NOT EXISTS (SELECT 1 FROM survey_employee_targets se_check WHERE se_check.survey_id = s.id)
         AND (
            NOT EXISTS (SELECT 1 FROM survey_targets st WHERE st.survey_id = s.id)
            OR EXISTS (SELECT 1 FROM survey_targets st WHERE st.survey_id = s.id AND st.department_id = ?)
         ))
    )
";
$totalAssignedSurveysStmt = $pdo->prepare($totalAssignedSurveysSql);
$totalAssignedSurveysStmt->execute([$userId, $empDeptId ?: 0]);
$totalAssignedSurveys = (int)$totalAssignedSurveysStmt->fetchColumn();

$totalAssignedEvalsSql = "
    SELECT COUNT(*) FROM evaluations e
    WHERE (
        (EXISTS (SELECT 1 FROM evaluation_employee_targets eet_check WHERE eet_check.evaluation_id = e.id)
         AND EXISTS (SELECT 1 FROM evaluation_employee_targets eet WHERE eet.evaluation_id = e.id AND eet.employee_id = ?))
        OR
        (NOT EXISTS (SELECT 1 FROM evaluation_employee_targets eet_check WHERE eet_check.evaluation_id = e.id)
         AND (
            NOT EXISTS (SELECT 1 FROM evaluation_targets et WHERE et.evaluation_id = e.id)
            OR EXISTS (SELECT 1 FROM evaluation_targets et WHERE et.evaluation_id = e.id AND et.department_id = ?)
         ))
    )
";
$totalAssignedEvalsStmt = $pdo->prepare($totalAssignedEvalsSql);
$totalAssignedEvalsStmt->execute([$userId, $empDeptId ?: 0]);
$totalAssignedEvals = (int)$totalAssignedEvalsStmt->fetchColumn();

$totalAssignedTasks = $totalAssignedSurveys + $totalAssignedEvals;
$totalCompletedTasks = $completedSurveys + $completedEvals;
$careerProgressPct = ($totalAssignedTasks > 0)
    ? (int)round(($totalCompletedTasks / $totalAssignedTasks) * 100)
    : null;

/* =========================
   LMS Content (shared library)
========================= */
$lmsContentCount = 0;
$lmsLatestTitle = null;
try {
    $lmsCountStmt = $pdo->query("SELECT COUNT(*) FROM lms_content");
    $lmsContentCount = $lmsCountStmt ? (int)$lmsCountStmt->fetchColumn() : 0;
    $lmsLatestStmt = $pdo->query("SELECT title FROM lms_content ORDER BY created_at DESC LIMIT 1");
    $lmsLatestTitle = $lmsLatestStmt ? $lmsLatestStmt->fetchColumn() : null;
} catch (PDOException $e) {
    /* lms_content table may not exist yet */
}

/* =========================
   Totals
========================= */
$pendingTasks = $pendingSurveys + $pendingEvaluations;

$employeeName   = $emp['full_name'] ?? '';
$jobTitle       = $emp['job_title'] ?? '';
$departmentName = $emp['department_name'] ?? '';
$status         = $emp['status'] ?? '';

/* Dynamic status color */
$statusKey = strtolower(trim($status));

$statusClasses = match ($statusKey) {
    'active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'onboarding' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    'inactive' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
};
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Updated Employee Hub Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap"
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
                        "primary-dark": "#58264a",
                        "accent": "#eb652c",
                        "accent-dark": "#d85a24",
                        "background-light": "#f8f4f8",
                        "background-dark": "#2d1f2d",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "system-ui", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.5rem",
                        "lg": "0.75rem",
                        "xl": "1rem",
                        "2xl": "1.25rem",
                        "3xl": "1.5rem",
                        "full": "9999px"
                    },
                    boxShadow: {
                        "soft": "0 2px 15px -3px rgba(0,0,0,0.07), 0 10px 20px -2px rgba(0,0,0,0.04)",
                        "card": "0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05)",
                        "card-hover": "0 20px 25px -5px rgba(0,0,0,0.08), 0 8px 10px -6px rgba(0,0,0,0.04)",
                    },
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .dashboard-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .dashboard-card:hover { transform: translateY(-2px); }
    </style>
</head>

<body class="bg-[#f8f4f8] text-slate-900 min-h-screen flex flex-col">
    <?php $currentPage = 'dashboard'; $pageTitle = 'Dashboard'; include __DIR__ . '/employee_navbar.php'; ?>
    <main class="flex-grow max-w-7xl mx-auto px-6 lg:px-8 py-12 w-full">
        <div class="mb-14">
            <p class="text-[#eb652c] text-sm font-semibold uppercase tracking-widest mb-2">Your Dashboard</p>
            <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                Hello<?= $employeeName ? ', ' . htmlspecialchars($employeeName) : '' ?>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-lg mt-2 max-w-xl">A quick overview of your employee journey and priorities.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
            <div class="dashboard-card bg-white dark:bg-slate-900/90 rounded-2xl p-6 border border-slate-100 dark:border-slate-800/80 shadow-card hover:shadow-card-hover flex flex-col h-full backdrop-blur-sm">
                <div class="flex items-start justify-between mb-5">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-800/50 ring-1 ring-slate-200/50 dark:ring-slate-700/50">
                        <span class="material-symbols-outlined text-slate-600 dark:text-slate-300 text-2xl">badge</span>
                    </div>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1.5">My Profile</h3>
                <p class="text-slate-500 dark:text-slate-400 text-sm mb-5 flex-grow leading-relaxed">Personal data, professional details, and contact information.</p>
                <div class="mb-5 space-y-2.5 text-sm">
                    <div>
                        <span class="font-semibold text-slate-900 dark:text-white truncate block"><?= htmlspecialchars($jobTitle ?: '—') ?></span>
                        <span class="text-xs text-slate-500 uppercase tracking-wide"><?= htmlspecialchars($departmentName ?: '—') ?></span>
                    </div>
                    <span class="inline-block px-2.5 py-1 text-[10px] font-bold rounded-lg uppercase <?= $statusClasses ?>"><?= htmlspecialchars($status ?: '—') ?></span>
                    <div class="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-slate-500 dark:text-slate-400">
                        <span><?= !empty($emp['position_level']) ? 'Level ' . htmlspecialchars($emp['position_level']) : '—' ?></span>
                        <span><?= htmlspecialchars($emp['employment_type'] ?? '') ?><?= (!empty($emp['employment_type']) && !empty($emp['location'])) ? ' • ' : '' ?><?= htmlspecialchars($emp['location'] ?? '') ?></span>
                    </div>
                </div>
                <a href="view_my_profile.php" class="w-full text-center bg-[#eb652c] hover:bg-[#d85a24] text-white py-3 px-4 rounded-xl font-semibold text-sm transition-all mt-auto block">
                    View Profile
                </a>
            </div>
            <div class="dashboard-card bg-white dark:bg-slate-900/90 rounded-2xl p-6 border border-slate-100 dark:border-slate-800/80 shadow-card hover:shadow-card-hover flex flex-col h-full backdrop-blur-sm <?= $pendingTasks > 0 ? 'ring-2 ring-amber-400/30' : '' ?>">
                <div class="flex items-start justify-between mb-5">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100/50 dark:from-amber-900/20 dark:to-amber-900/10 ring-1 ring-amber-200/50 dark:ring-amber-800/30">
                        <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-2xl">fact_check</span>
                    </div>
                    <?php if ($pendingTasks > 0): ?>
                    <span class="bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider shrink-0">Action Needed</span>
                    <?php endif; ?>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1.5">Surveys &amp; Evaluations</h3>
                <p class="text-slate-500 dark:text-slate-400 text-sm mb-5 flex-grow leading-relaxed">Submit engagement surveys and performance feedback.</p>
                <div class="mb-5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold tabular-nums text-slate-900 dark:text-white"><?= $pendingTasks ?></span>
                        <span class="text-sm font-medium text-slate-500"><?= $pendingTasks === 1 ? 'pending task' : 'pending tasks' ?></span>
                    </div>
                    <p class="text-xs text-slate-500 mt-2"><?= $pendingSurveys ?> surveys · <?= $pendingEvaluations ?> evaluations</p>
                </div>
                <a href="survey_and_evaluation_display.php" class="w-full text-center bg-[#eb652c] hover:bg-[#d85a24] text-white py-3 px-4 rounded-xl font-semibold text-sm transition-all mt-auto block">
                    <?= $pendingTasks > 0 ? 'Start Evaluations' : 'View Surveys & Evaluations' ?>
                </a>
            </div>
            <div class="dashboard-card bg-white dark:bg-slate-900/90 rounded-2xl p-6 border border-slate-100 dark:border-slate-800/80 shadow-card hover:shadow-card-hover flex flex-col h-full backdrop-blur-sm">
                <div class="flex items-start justify-between mb-5">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 ring-1 ring-blue-200/50 dark:ring-blue-800/30">
                        <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-2xl">query_stats</span>
                    </div>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1.5">Career &amp; Development</h3>
                <p class="text-slate-500 dark:text-slate-400 text-sm mb-5 flex-grow leading-relaxed">Track career paths, goals, and skill progression.</p>
                <div class="mb-5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold tabular-nums text-slate-900 dark:text-white"><?= $careerProgressPct !== null ? $careerProgressPct . '%' : '—' ?></span>
                        <span class="text-sm font-medium text-slate-500"><?= $careerProgressPct !== null ? 'engagement' : 'no tasks yet' ?></span>
                    </div>
                    <?php if ($careerProgressPct !== null): ?>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 mt-3 rounded-full overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-full rounded-full transition-all duration-500" style="width: <?= min(100, max(0, $careerProgressPct)) ?>%"></div>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 font-medium"><?= $totalCompletedTasks ?> of <?= $totalAssignedTasks ?> completed</p>
                    <?php else: ?>
                    <p class="text-[11px] text-slate-500 mt-2 font-medium">Complete surveys &amp; evaluations to track progress</p>
                    <?php endif; ?>
                </div>
                <a href="My_career_development_plan.php" class="w-full text-center bg-[#eb652c] hover:bg-[#d85a24] text-white py-3 px-4 rounded-xl font-semibold text-sm transition-all mt-auto block">
                    View Career Progress
                </a>
            </div>
            <div class="dashboard-card bg-white dark:bg-slate-900/90 rounded-2xl p-6 border border-slate-100 dark:border-slate-800/80 shadow-card hover:shadow-card-hover flex flex-col h-full backdrop-blur-sm">
                <div class="flex items-start justify-between mb-5">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-[#eb652c]/10 to-[#eb652c]/5 dark:from-[#eb652c]/20 dark:to-[#eb652c]/10 ring-1 ring-[#eb652c]/20 dark:ring-[#eb652c]/30">
                        <span class="material-symbols-outlined text-[#eb652c] text-2xl">school</span>
                    </div>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1.5">Learning (LMS)</h3>
                <p class="text-slate-500 dark:text-slate-400 text-sm mb-5 flex-grow leading-relaxed">Training courses and learning modules.</p>
                <div class="mb-5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold tabular-nums text-slate-900 dark:text-white"><?= $lmsContentCount ?></span>
                        <span class="text-sm font-medium text-slate-500"><?= $lmsContentCount === 1 ? 'resource' : 'resources' ?></span>
                    </div>
                    <?php if ($lmsLatestTitle): ?>
                    <p class="text-[11px] text-slate-500 mt-2 truncate" title="<?= htmlspecialchars($lmsLatestTitle) ?>">Latest: <?= htmlspecialchars($lmsLatestTitle) ?></p>
                    <?php elseif ($lmsContentCount > 0): ?>
                    <p class="text-[11px] text-slate-500 mt-2">Browse learning content</p>
                    <?php else: ?>
                    <p class="text-[11px] text-slate-500 mt-2">No content yet</p>
                    <?php endif; ?>
                </div>
                <a href="LMS_course_review.php" class="w-full text-center bg-[#eb652c] hover:bg-[#d85a24] text-white py-3 px-4 rounded-xl font-semibold text-sm transition-all mt-auto block">
                    Open LMS
                </a>
            </div>
        </div>
       
    </main>
    <footer class="max-w-7xl mx-auto px-6 lg:px-8 py-8 text-center border-t border-slate-200/60 dark:border-slate-800 w-full mt-auto">
        <p class="text-xs text-slate-400 dark:text-slate-500">© <?= date('Y') ?> GrowthPlatform</p>
    </footer>

</body>

</html>