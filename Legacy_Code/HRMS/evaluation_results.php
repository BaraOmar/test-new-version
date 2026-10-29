<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("DB connection \$pdo not found. Check config/db.php");
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// =========================
//  INPUT
// =========================
$evaluation_id = (int)($_GET['id'] ?? 0);
if ($evaluation_id <= 0) {
    die("Evaluation not found.");
}

// =========================
//  LOAD EVALUATION
// =========================
$stmt = $pdo->prepare("SELECT * FROM evaluations WHERE id = ?");
$stmt->execute([$evaluation_id]);
$evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evaluation) {
    die("Evaluation not found.");
}

// Basic meta
$title       = $evaluation['title'] ?? 'Evaluation';
$description = $evaluation['description'] ?? '';
$status      = $evaluation['status'] ?? 'active';
$start_at    = $evaluation['start_at'] ?? null;
$end_at      = $evaluation['end_at'] ?? null;
$target_participants = isset($evaluation['target_participants']) ? (int)$evaluation['target_participants'] : null;

// =========================
//  LOAD QUESTIONS
// =========================
$stmt = $pdo->prepare("
    SELECT *
    FROM evaluation_questions
    WHERE evaluation_id = ?
    ORDER BY position ASC, id ASC
");
$stmt->execute([$evaluation_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================
//  LOAD RESPONSE COUNTS
// =========================
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM evaluation_responses
    WHERE evaluation_id = ?
");
$stmt->execute([$evaluation_id]);
$responseCount = (int)$stmt->fetchColumn();

// =========================
//  LOAD ANSWERS PER QUESTION
// =========================
$answersByQuestion = [];

if (!empty($questions)) {
    $qIds = array_column($questions, 'id');
    $placeholders = implode(',', array_fill(0, count($qIds), '?'));

    $sql = "
        SELECT ea.*, e.full_name
        FROM evaluation_answers ea
        LEFT JOIN employees e ON e.id = ea.employee_id
        WHERE ea.evaluation_id = ?
          AND ea.question_id IN ($placeholders)
        ORDER BY ea.question_id, ea.id
    ";
    $params = array_merge([$evaluation_id], $qIds);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $qid = (int)$row['question_id'];
        if (!isset($answersByQuestion[$qid])) {
            $answersByQuestion[$qid] = [];
        }
        $answersByQuestion[$qid][] = $row;
    }
}

// =========================
//  HELPERS FOR STATS
// =========================
function formatDateShort(?string $dt): string {
    if (empty($dt)) {
        return "Not set";
    }
    $ts = strtotime($dt);
    if (!$ts) {
        return "Not set";
    }
    return date("M d, Y", $ts);
}

function computeRatingStats(array $answers): array {
    $nums = [];
    foreach ($answers as $a) {
        if ($a['answer_number'] !== null && $a['answer_number'] !== '') {
            $nums[] = (float)$a['answer_number'];
        }
    }
    if (empty($nums)) {
        return [
            'count' => 0,
            'avg'   => null,
            'min'   => null,
            'max'   => null,
        ];
    }
    return [
        'count' => count($nums),
        'avg'   => array_sum($nums) / count($nums),
        'min'   => min($nums),
        'max'   => max($nums),
    ];
}

function computeOptionCounts(array $answers): array {
    $counts = [];
    foreach ($answers as $a) {
        $text = trim((string)($a['answer_text'] ?? ''));
        if ($text === '') {
            continue;
        }
        // Checkbox answers may be comma separated
        $parts = array_map('trim', explode(',', $text));
        foreach ($parts as $p) {
            if ($p === '') continue;
            $counts[$p] = ($counts[$p] ?? 0) + 1;
        }
    }
    arsort($counts);
    return $counts;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Evaluation Results - PerformCenter</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&amp;display=swap"
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
                        "sidebar-active": "#f5f0ff",
                        "logout-purple": "#8b5cf6",
                        "background-light": "#f6f6f8",
                        "background-dark": "#131022",
                    },
                    fontFamily: {
                        "display": ["Manrope"]
                    },
                    borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                },
            },
        }
    </script>
    <style type="text/tailwindcss">
        body { font-family: 'Manrope', sans-serif; }
        .sidebar-item {
            @apply flex items-center gap-3 px-6 py-3 text-slate-600 font-medium transition-colors;
        }
        .sidebar-item:hover {
            @apply text-primary;
        }
        .sidebar-item.active {
            @apply bg-sidebar-active text-primary border-r-4 border-primary;
        }
    </style>
</head>

<body class="bg-[#f8f4f8] text-slate-900 antialiased">
<div class="flex h-screen w-full overflow-hidden">
    <?php $currentPage = 'evaluations'; include __DIR__ . '/navbar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php $pageTitle = 'Evaluation Results'; include __DIR__ . '/admin_header.php'; ?>
        <main class="flex-1 overflow-y-auto py-8 px-8">
            <div class="layout-content-container flex flex-col max-w-[1200px] mx-auto gap-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-slate-900 dark:text-white text-3xl font-extrabold tracking-tight">
                                <?= htmlspecialchars($title) ?>
                            </h1>
                            <span
                                    class="bg-emerald-100 text-emerald-700 text-xs font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">
                                <?= htmlspecialchars(ucfirst($status)) ?>
                            </span>
                        </div>
                        <?php if ($description): ?>
                            <p class="text-slate-500 dark:text-slate-400 text-base">
                                <?= htmlspecialchars($description) ?>
                            </p>
                        <?php endif; ?>
                        <p class="text-xs text-slate-400 mt-1">
                            From <?= formatDateShort($start_at) ?> to <?= formatDateShort($end_at) ?>
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <a href="evaluation_list.php"
                           class="flex items-center justify-center gap-2 rounded-lg px-4 py-2 border border-slate-200 dark:border-slate-700 font-bold text-sm bg-white dark:bg-slate-800 hover:bg-slate-50">
                            <span class="material-symbols-outlined text-lg">arrow_back</span>
                            Back to list
                        </a>
                        <button
                                class="flex items-center justify-center gap-2 rounded-lg px-4 py-2 bg-[#eb652c] text-white font-bold text-sm hover:bg-[#d85a24] transition-all"
                                onclick="window.print();return false;">
                            <span class="material-symbols-outlined text-lg">print</span>
                            Print
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                            class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold uppercase tracking-wide">
                                Total Responses</p>
                            <span class="material-symbols-outlined text-primary">task_alt</span>
                        </div>
                        <div class="flex items-baseline gap-2 mt-1">
                            <p class="text-slate-900 dark:text-white text-3xl font-extrabold">
                                <?= $responseCount ?>
                            </p>
                            <?php if ($target_participants): ?>
                                <p class="text-slate-500 dark:text-slate-400 text-sm">
                                    of <?= $target_participants ?> target participants
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php if ($target_participants && $target_participants > 0): ?>
                            <?php $completionPercent = (int)round(min(100, ($responseCount / $target_participants) * 100)); ?>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full mt-4 overflow-hidden">
                                <div class="bg-[#eb652c] h-2 rounded-full" style="width: <?= $completionPercent ?>%;"></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                Completion rate: <?= $completionPercent ?>%
                            </p>
                        <?php endif; ?>
                    </div>
                    <div
                            class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold uppercase tracking-wide">
                                Questions</p>
                            <span class="material-symbols-outlined text-slate-400">quiz</span>
                        </div>
                        <p class="text-slate-900 dark:text-white text-3xl font-extrabold mt-1">
                            <?= count($questions) ?>
                        </p>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                            Included in this evaluation
                        </p>
                    </div>
                    <div
                            class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold uppercase tracking-wide">
                                Mode</p>
                            <span class="material-symbols-outlined text-amber-500">visibility</span>
                        </div>
                        <p class="text-slate-900 dark:text-white text-3xl font-extrabold mt-1">Non-Anonymous</p>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1 italic">
                            Responses are linked to employees.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-4 mt-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-slate-900 dark:text-white text-lg font-bold">Question-level results</h3>
                    </div>

                    <?php if (empty($questions)): ?>
                        <div class="bg-white dark:bg-slate-900 border border-dashed border-slate-300 dark:border-slate-700 rounded-xl p-8 text-center">
                            <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">quiz</span>
                            <p class="font-medium text-slate-700 dark:text-slate-200">No questions configured for this evaluation.</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Return to the builder to add questions.</p>
                        </div>
                    <?php else: ?>

                        <div class="space-y-6">
                            <?php foreach ($questions as $index => $q): ?>
                                <?php
                                $qid       = (int)$q['id'];
                                $qType     = $q['question_type'];
                                $qAnswers  = $answersByQuestion[$qid] ?? [];
                                $isRequired = !empty($q['is_required']);
                                ?>
                                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                                                    Question <?= $index + 1 ?>
                                                </span>
                                                <?php if ($isRequired): ?>
                                                    <span class="text-[10px] font-bold text-red-500 uppercase tracking-widest">
                                                        Required
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <h4 class="text-base md:text-lg font-semibold text-slate-900 dark:text-white mt-1">
                                                <?= htmlspecialchars($q['question_text']) ?>
                                            </h4>
                                            <p class="text-xs text-slate-500 mt-1">
                                                Type: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $qType))) ?>
                                            </p>
                                        </div>
                                        <div class="text-right text-xs text-slate-500">
                                            <span class="font-semibold">
                                                <?= count($qAnswers) ?> response<?= count($qAnswers) === 1 ? '' : 's' ?>
                                            </span>
                                        </div>
                                    </div>

                                    <?php if ($qType === 'rating'): ?>
                                        <?php $stats = computeRatingStats($qAnswers); ?>
                                        <?php if ($stats['count'] === 0): ?>
                                            <p class="text-sm text-slate-500 italic">No rating responses yet.</p>
                                        <?php else: ?>
                                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4">
                                                <div>
                                                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wide">Average</p>
                                                    <p class="text-xl font-extrabold text-slate-900 dark:text-white">
                                                        <?= number_format($stats['avg'], 2) ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wide">Min</p>
                                                    <p class="text-lg font-semibold text-slate-900 dark:text-white">
                                                        <?= number_format($stats['min'], 1) ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wide">Max</p>
                                                    <p class="text-lg font-semibold text-slate-900 dark:text-white">
                                                        <?= number_format($stats['max'], 1) ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wide">Responses</p>
                                                    <p class="text-lg font-semibold text-slate-900 dark:text-white">
                                                        <?= $stats['count'] ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                    <?php elseif (in_array($qType, ['multiple_choice', 'checkbox', 'dropdown'], true)): ?>
                                        <?php $counts = computeOptionCounts($qAnswers); ?>
                                        <?php if (empty($counts)): ?>
                                            <p class="text-sm text-slate-500 italic">No responses yet.</p>
                                        <?php else: ?>
                                            <div class="space-y-3">
                                                <?php foreach ($counts as $label => $cnt): ?>
                                                    <?php
                                                    $percent = $responseCount > 0 ? round(($cnt / $responseCount) * 100) : 0;
                                                    ?>
                                                    <div>
                                                        <div class="flex justify-between text-xs font-medium text-slate-600 dark:text-slate-300">
                                                            <span><?= htmlspecialchars($label) ?></span>
                                                            <span><?= $cnt ?> (<?= $percent ?>%)</span>
                                                        </div>
                                                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full mt-1 overflow-hidden">
                                                            <div class="bg-[#eb652c] h-2 rounded-full" style="width: <?= $percent ?>%;"></div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                    <?php else: ?>
                                        <?php if (empty($qAnswers)): ?>
                                            <p class="text-sm text-slate-500 italic">No responses yet.</p>
                                        <?php else: ?>
                                            <div class="mt-2 border border-slate-100 dark:border-slate-800 rounded-lg divide-y divide-slate-100 dark:divide-slate-800 bg-slate-50/70 dark:bg-slate-900/40">
                                                <?php foreach ($qAnswers as $a): ?>
                                                    <div class="p-3 text-sm">
                                                        <div class="flex items-center justify-between gap-3 mb-1">
                                                            <span class="font-semibold text-slate-800 dark:text-slate-100">
                                                                <?= htmlspecialchars($a['full_name'] ?? 'Employee #'.$a['employee_id']) ?>
                                                            </span>
                                                            <span class="text-[10px] uppercase tracking-wider text-slate-400">
                                                                <?= htmlspecialchars(date('M d, Y', strtotime($a['created_at'] ?? 'now'))) ?>
                                                            </span>
                                                        </div>
                                                        <p class="text-slate-700 dark:text-slate-200">
                                                            <?= nl2br(htmlspecialchars($a['answer_text'] ?? '')) ?>
                                                        </p>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <footer class="mt-12 py-8 border-t border-slate-200 dark:border-slate-800">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-sm text-slate-500">© 2024 PerformCenter. All rights reserved.</p>
                    <div class="flex gap-6">
                        <a class="text-xs font-bold text-slate-400 uppercase tracking-widest hover:text-primary"
                           href="#">Support</a>
                        <a class="text-xs font-bold text-slate-400 uppercase tracking-widest hover:text-primary"
                           href="#">Privacy</a>
                        <a class="text-xs font-bold text-slate-400 uppercase tracking-widest hover:text-primary"
                           href="#">Terms</a>
                    </div>
                </div>
            </footer>
        </main>
    </div>
</div>

</body>

</html>

