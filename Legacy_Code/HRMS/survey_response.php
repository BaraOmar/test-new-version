<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/validation.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

/**
 * View Survey Responses
 * 
 * Anonymity Handling:
 * - When is_anonymous = 1: Respondent names are HIDDEN
 * - When is_anonymous = 0: Respondent names are VISIBLE
 * 
 * Supports all question types:
 * - short_text, long_text
 * - multiple_choice, checkbox, dropdown
 * - rating
 */

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("DB connection \$pdo not found. Check config/db.php");
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get survey ID from URL
$survey_id = validate_id($_GET['id'] ?? 0);

if ($survey_id <= 0) {
    die("Invalid survey ID");
}

$search = validate_search($_GET['search'] ?? '');

// Fetch survey details
$stmt = $pdo->prepare("
    SELECT s.*, 
           COUNT(DISTINCT sr.id) as response_count,
           COUNT(DISTINCT sa.id) as answer_count
    FROM surveys s
    LEFT JOIN survey_responses sr ON s.id = sr.survey_id
    LEFT JOIN survey_answers sa ON s.id = sa.survey_id
    WHERE s.id = ?
    GROUP BY s.id
");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$survey) {
    die("Survey not found");
}

// Fetch questions with their options
$stmt = $pdo->prepare("
    SELECT sq.*, GROUP_CONCAT(sqo.option_text ORDER BY sqo.position SEPARATOR '|||') as options
    FROM survey_questions sq
    LEFT JOIN survey_question_options sqo ON sq.id = sqo.question_id
    WHERE sq.survey_id = ?
    GROUP BY sq.id
    ORDER BY sq.position ASC, sq.id ASC
");
$stmt->execute([$survey_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse options for each question
foreach ($questions as &$q) {
    $q['options'] = $q['options'] ? explode('|||', $q['options']) : [];
}
unset($q);

// Fetch responses with or without employee info based on anonymity
$isAnonymous = (int)($survey['is_anonymous'] ?? 1);

if ($isAnonymous) {
    $stmt = $pdo->prepare("
        SELECT 
            sa.*,
            sq.question_text,
            sq.question_type,
            NULL as employee_name,
            NULL as employee_email,
            sa.created_at as submitted_at
        FROM survey_answers sa
        JOIN survey_questions sq ON sa.question_id = sq.id
        WHERE sa.survey_id = ?
        ORDER BY sa.created_at DESC, sa.id DESC
    ");
    $stmt->execute([$survey_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT 
            sa.*,
            sq.question_text,
            sq.question_type,
            e.full_name as employee_name,
            e.email as employee_email,
            sa.created_at as submitted_at
        FROM survey_answers sa
        JOIN survey_questions sq ON sa.question_id = sq.id
        LEFT JOIN employees e ON sa.employee_id = e.id
        WHERE sa.survey_id = ?
        ORDER BY sa.created_at DESC, sa.id DESC
    ");
    $stmt->execute([$survey_id]);
}

$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group answers by employee/response
$groupedResponses = [];
foreach ($answers as $answer) {
 $key = $answer['employee_id'] ?? 'anonymous_' . $answer['id'];
    if (!isset($groupedResponses[$key])) {
        $groupedResponses[$key] = [
            'employee_name' => $answer['employee_name'],
            'employee_email' => $answer['employee_email'],
            'submitted_at' => $answer['submitted_at'],
            'answers' => []
        ];
    }
    $groupedResponses[$key]['answers'][$answer['question_id']] = $answer;

    // Filter by employee name
if (!$isAnonymous && $search !== '') {

    $filtered = [];

    foreach ($groupedResponses as $key => $resp) {

        if (
            isset($resp['employee_name']) &&
            stripos($resp['employee_name'], $search) !== false
        ) {
            $filtered[$key] = $resp;
        }

    }

    $groupedResponses = $filtered;
}
}
// Calculate statistics for each question
$questionStats = [];

foreach ($questions as $q) {

    $qId = $q['id'];
    $qType = $q['question_type'];

    // ======================
    // RATING QUESTIONS
    // ======================
    if ($qType === 'rating') {

        $distribution = [];
        $values = [];

        $min = $q['scale_min'] ?? 1;
        $max = $q['scale_max'] ?? 5;

        for ($i = $min; $i <= $max; $i++) {
            $distribution[$i] = 0;
        }

        foreach ($answers as $a) {
            if ($a['question_id'] == $qId && $a['answer_number'] !== null) {

                $val = (int)$a['answer_number'];
                $values[] = $val;

                if (isset($distribution[$val])) {
                    $distribution[$val]++;
                }
            }
        }

        if (count($values) > 0) {
            $questionStats[$qId] = [
                'type' => 'rating',
                'average' => round(array_sum($values) / count($values), 2),
                'total' => count($values),
                'distribution' => $distribution
            ];
        }

    }

    // ======================
    // CHOICE QUESTIONS
    // ======================
    elseif (in_array($qType, ['multiple_choice', 'checkbox', 'dropdown'])) {

        $optionCounts = [];

        foreach ($answers as $a) {

            if ($a['question_id'] == $qId && !empty($a['answer_text'])) {

                if (!isset($optionCounts[$a['answer_text']])) {
                    $optionCounts[$a['answer_text']] = 0;
                }

                $optionCounts[$a['answer_text']]++;
            }
        }

        if (!empty($optionCounts)) {

            arsort($optionCounts);

            $questionStats[$qId] = [
                'type' => 'choice',
                'counts' => $optionCounts,
                'total' => array_sum($optionCounts)
            ];
        }
    }

    // ======================
    // TEXT QUESTIONS
    // ======================
    elseif (in_array($qType, ['short_text', 'long_text'])) {

        $count = 0;

        foreach ($answers as $a) {
            if ($a['question_id'] == $qId && !empty($a['answer_text'])) {
                $count++;
            }
        }

        if ($count > 0) {
            $questionStats[$qId] = [
                'type' => 'text',
                'count' => $count
            ];
        }
    }

}
// Format dates
function formatDate($dateStr) {
    if (!$dateStr) return 'Not set';
    $date = new DateTime($dateStr);
    return $date->format('M j, Y g:i A');
}

// Check survey status
$status = $survey['status'] ?? 'draft';
$statusColor = [
    'draft' => 'bg-slate-100 text-slate-600',
    'scheduled' => 'bg-amber-100 text-amber-600',
    'active' => 'bg-emerald-100 text-emerald-600',
    'closed' => 'bg-rose-100 text-rose-600'
][$status] ?? 'bg-slate-100 text-slate-600';

// Get target departments (when department targeting)
$targetDepts = [];
if (($survey['audience_type'] ?? '') === 'department') {
    $stmt = $pdo->prepare("
        SELECT d.name 
        FROM survey_targets st
        JOIN departments d ON st.department_id = d.id
        WHERE st.survey_id = ?
    ");
    $stmt->execute([$survey_id]);
    $targetDepts = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Get target employees (when employee targeting) - also check survey_employee_targets as fallback
$targetEmployees = [];
$hasEmployeeTargets = false;
try {
    $stmt = $pdo->prepare("
        SELECT e.full_name 
        FROM survey_employee_targets se
        JOIN employees e ON se.employee_id = e.id
        WHERE se.survey_id = ?
    ");
    $stmt->execute([$survey_id]);
    $targetEmployees = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $hasEmployeeTargets = !empty($targetEmployees);
} catch (PDOException $e) { /* table may not exist */ }

// Get question type display name
function getQuestionTypeLabel($type) {
    $labels = [
        'short_text' => 'Short Answer',
        'long_text' => 'Paragraph',
        'multiple_choice' => 'Multiple Choice',
        'checkbox' => 'Checkboxes',
        'dropdown' => 'Dropdown',
        'rating' => 'Rating Scale'
    ];
    return $labels[$type] ?? $type;
}

function getQuestionTypeIcon($type) {
    $icons = [
        'short_text' => 'short_text',
        'long_text' => 'notes',
        'multiple_choice' => 'radio_button_checked',
        'checkbox' => 'check_box',
        'dropdown' => 'arrow_drop_down_circle',
        'rating' => 'star'
    ];
    return $icons[$type] ?? 'help';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Survey Responses - <?= e($survey['title']) ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#642754",
                        "background-light": "#f0ebf8",
                        "background-dark": "#191022",
                    },
                    fontFamily: {
                        "display": ["Inter"]
                    },
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Bar chart animation */
        @keyframes barGrow {
            from { width: 0; }
        }
        .bar-animate {
            animation: barGrow 0.5s ease-out;
        }
    </style>
</head>

<body class="bg-[#f8f4f8] text-slate-900 font-display transition-colors duration-200">
    <div class="flex h-screen overflow-hidden">
        <?php $currentPage = 'surveys'; include __DIR__ . '/navbar.php'; ?>

        <!-- Main Content -->
        <div class="flex flex-col flex-1 h-screen overflow-hidden">
            <!-- Header -->
            <header class="flex h-16 items-center justify-between border-b border-white/10 bg-[#642754] px-6 shrink-0 z-20">
                <div class="flex items-center gap-4">
                    <button onclick="history.back()" class="p-2 hover:bg-white/10 rounded-lg transition-colors text-white">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </button>
                    <div>
                        <h1 class="text-lg font-bold leading-tight text-white"><?= e($survey['title']) ?></h1>
                        <p class="text-xs text-white/80">Survey Responses</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="survey_list.php" class="flex items-center justify-center rounded-lg border-2 border-white/60 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition-colors">
                        Back to Surveys
                    </a>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto bg-[#f8f4f8] p-8">
                <div class="max-w-6xl mx-auto space-y-6">
                    
                    <!-- Survey Info Card -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?= $statusColor ?>">
                                        <?= e(ucfirst($status)) ?>
                                    </span>
                                    <?php if ($isAnonymous): ?>
                                        <span class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold uppercase bg-emerald-100 text-emerald-600">
                                            <span class="material-symbols-outlined text-sm">lock</span>
                                            Anonymous
                                        </span>
                                    <?php else: ?>
                                        <span class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold uppercase bg-amber-100 text-amber-600">
                                            <span class="material-symbols-outlined text-sm">visibility</span>
                                            Non-Anonymous
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-slate-600 dark:text-slate-400 text-sm"><?= e($survey['description'] ?? 'No description') ?></p>
                            </div>
                                <div class="flex flex-wrap gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-slate-400">calendar_today</span>
                                    <span class="text-slate-600 dark:text-slate-400">Opens: <?= formatDate($survey['start_at']) ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-slate-400">event_busy</span>
                                    <span class="text-slate-600 dark:text-slate-400">Closes: <?= formatDate($survey['end_at']) ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-slate-400">groups</span>
                                    <span class="text-slate-600 dark:text-slate-400">Target: <?php
                                        if ($hasEmployeeTargets || ($survey['audience_type'] ?? '') === 'employee') {
                                            echo 'Specific Employee(s)';
                                        } elseif (($survey['audience_type'] ?? '') === 'organization') {
                                            echo 'Organization-wide';
                                        } else {
                                            echo 'Specific Departments';
                                        }
                                    ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($targetEmployees)): ?>
                            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <span class="text-xs font-bold text-slate-500 uppercase">Target Employees:</span>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <?php foreach ($targetEmployees as $empName): ?>
                                        <span class="px-2 py-1 bg-primary/10 text-primary text-xs rounded-full"><?= e($empName) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($targetDepts)): ?>
                            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <span class="text-xs font-bold text-slate-500 uppercase">Target Departments:</span>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <?php foreach ($targetDepts as $dept): ?>
                                        <span class="px-2 py-1 bg-primary/10 text-primary text-xs rounded-full"><?= e($dept) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary">how_to_reg</span>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold"><?= (int)($survey['response_count'] ?? 0) ?></p>
                                    <p class="text-xs text-slate-500">Total Responses</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold"><?= count($groupedResponses) ?></p>
                                    <p class="text-xs text-slate-500">Unique Respondents</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-amber-600">schedule</span>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold"><?= (int)($survey['duration_days'] ?? 'N/A') ?></p>
                                    <p class="text-xs text-slate-500">Duration (Days)</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-blue-600">help</span>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold"><?= count($questions) ?></p>
                                    <p class="text-xs text-slate-500">Questions</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Question Statistics -->
                    <?php if (!empty($questionStats)): ?>
                        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6">
                            <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">analytics</span>
                                Response Statistics
                            </h2>
                            <div class="space-y-6">
                                <?php foreach ($questions as $q): ?>
                                    <?php if (isset($questionStats[$q['id']])): 
                                        $stats = $questionStats[$q['id']];
                                        $qType = $q['question_type'];
                                    ?>
                                        <div class="border-b border-slate-100 dark:border-slate-800 pb-6 last:border-0 last:pb-0">
                                            <div class="flex items-center gap-2 mb-3">
                                                <span class="material-symbols-outlined text-slate-400 text-lg"><?= getQuestionTypeIcon($qType) ?></span>
                                                <p class="text-sm font-medium"><?= e($q['question_text']) ?></p>
                                                <span class="text-xs text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded"><?= getQuestionTypeLabel($qType) ?></span>
                                            </div>
                                            
                                         
<?php if ($stats['type'] === 'choice'): ?>

<div class="space-y-3">

<?php foreach ($stats['counts'] as $option => $count):

$percent = $stats['total'] > 0
? round(($count / $stats['total']) * 100)
: 0;

?>

<div class="flex items-center gap-3">

<div class="w-32 text-sm text-slate-600">
<?= e($option) ?>
</div>

<div class="flex-1 h-6 bg-slate-200 rounded-lg overflow-hidden">

<div
class="h-full bg-primary bar-animate flex items-center justify-end pr-2 text-white text-xs"
style="width:<?= $percent ?>%">
<?= $percent ?>%
</div>

</div>

<div class="w-10 text-sm text-slate-500 text-right">
<?= $count ?>
</div>

</div>

<?php endforeach; ?>

</div>

<?php elseif ($stats['type'] === 'rating'): ?>

<div class="flex items-start justify-between mb-3">

<div>
<span class="px-2 py-1 text-xs font-bold rounded bg-primary/10 text-primary">
RATING SCALE
</span>

<p class="font-bold mt-2">
<?= e($q['question_text']) ?>
</p>

</div>

<div class="text-right">
<p class="text-3xl font-bold text-primary">
<?= $stats['average'] ?>
</p>
<p class="text-xs text-slate-500">AVG SCORE</p>
</div>

</div>

<div class="space-y-3">

<?php
$max = $q['scale_max'] ?? 5;

for($i = $max; $i >= 1; $i--):

$count = $stats['distribution'][$i] ?? 0;

$percent = $stats['total'] > 0
? round(($count / $stats['total']) * 100)
: 0;
?>

<div class="flex items-center gap-3">

<div class="w-20 text-sm text-slate-600">
<?= $i ?> Stars
</div>

<div class="flex-1 h-8 bg-slate-200 rounded-lg overflow-hidden">

<div
class="h-full bg-primary bar-animate flex items-center pl-3 text-white text-sm font-semibold"
style="width:<?= $percent ?>%">
<?= $percent ?>%
</div>

</div>

<div class="w-10 text-sm text-slate-500 text-right">
<?= $count ?>
</div>

</div>

<?php endfor; ?>

</div>

<?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">

<?php if (!$isAnonymous): ?>

<form method="GET" class="flex items-center gap-3 w-full md:w-auto">

<input type="hidden" name="id" value="<?= $survey_id ?>">

<div class="relative w-full md:w-80">

<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
search
</span>

<input
type="text"
name="search"
value="<?= e($search) ?>"
placeholder="Search respondent name..."
class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 focus:ring-2 focus:ring-primary focus:border-primary transition"
/>

</div>

<button class="bg-[#eb652c] hover:bg-[#d85a24] text-white px-4 py-2.5 text-sm font-semibold rounded-lg shadow-sm transition-all">
Search
</button>

<a
href="?id=<?= $survey_id ?>"
class="px-4 py-2.5 text-sm font-medium border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition">
Reset
</a>

</form>

<?php endif; ?>

<div class="text-sm text-slate-500 flex items-center gap-2">

<span class="material-symbols-outlined text-base">group</span>

Showing <span class="font-semibold"><?= count($groupedResponses) ?></span> respondents

</div>

</div>

                    <!-- Individual Responses -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6">
                        <h2 class="text-lg font-bold mb-4 flex items-center gap-2">

                        
                            <span class="material-symbols-outlined text-primary">format_list_bulleted</span>
                            Individual Responses

                            
                            <?php if ($isAnonymous): ?>
                                <span class="text-xs font-normal text-emerald-600 bg-emerald-100 px-2 py-1 rounded-full ml-2">
                                    Respondent identities hidden
                                </span>
                            <?php else: ?>
                                <span class="text-xs font-normal text-amber-600 bg-amber-100 px-2 py-1 rounded-full ml-2">
                                    Respondent identities visible
                                </span>
                            <?php endif; ?>
                        </h2>

                        <?php if (empty($groupedResponses)): ?>
                            <div class="text-center py-12">
                                <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">inbox</span>
                                <p class="text-slate-500">No responses yet</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php 
                                $responseNum = 1;
                                foreach ($groupedResponses as $key => $response): 
                                ?>
                                    <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                                            <div class="flex items-center gap-3">
                                                <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                                                    <?php if ($isAnonymous): ?>
                                                       
                                                    <?php else: ?>
                                                        <span class="material-symbols-outlined text-primary">person</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <?php if ($isAnonymous): ?>
                                                        <p class="font-bold">Anonymous Respondent #<?= $responseNum ?></p>
                                                        <p class="text-xs text-slate-500">Identity hidden due to anonymity settings</p>
                                                    <?php else: ?>
                                                        <p class="font-bold"><?= e($response['employee_name'] ?? 'Unknown') ?></p>
                                                        <p class="text-xs text-slate-500"><?= e($response['employee_email'] ?? 'No email') ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs text-slate-500">Submitted</p>
                                                <p class="text-sm font-medium"><?= formatDate($response['submitted_at']) ?></p>
                                            </div>
                                        </div>
                                        <div class="space-y-3">
                                            <?php foreach ($questions as $q): ?>
                                                <?php if (isset($response['answers'][$q['id']])): 
                                                    $ans = $response['answers'][$q['id']];
                                                    $qType = $q['question_type'];
                                                ?>
                                                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3">
                                                        <div class="flex items-center gap-2 mb-2">
                                                            <span class="material-symbols-outlined text-slate-400 text-sm"><?= getQuestionTypeIcon($qType) ?></span>
                                                            <p class="text-xs text-slate-500"><?= e($q['question_text']) ?></p>
                                                        </div>
                                                        <p class="font-medium pl-6">
                                                            <?php if ($qType === 'rating'): ?>
                                                                <span class="flex items-center gap-1">
                                                                    <?php for ($i = 1; $i <= ($q['scale_max'] ?? 5); $i++): ?>
                                                                        <span class="material-symbols-outlined text-lg <?= $i <= ($ans['answer_number'] ?? 0) ? 'text-primary fill-1' : 'text-slate-300' ?>">star</span>
                                                                    <?php endfor; ?>
                                                                    <span class="ml-2 text-sm text-slate-500">(<?= $ans['answer_number'] ?? 'N/A' ?>)</span>
                                                                </span>
                                                            <?php elseif ($qType === 'checkbox'): ?>
                                                                <span class="flex items-center gap-2">
                                                                    <span class="material-symbols-outlined text-primary">check_box</span>
                                                                    <span><?= e($ans['answer_text'] ?? 'N/A') ?></span>
                                                                </span>
                                                            <?php elseif ($qType === 'multiple_choice'): ?>
                                                                <span class="flex items-center gap-2">
                                                                    <span class="material-symbols-outlined text-primary">radio_button_checked</span>
                                                                    <span><?= e($ans['answer_text'] ?? 'N/A') ?></span>
                                                                </span>
                                                            <?php elseif ($qType === 'dropdown'): ?>
                                                                <span class="flex items-center gap-2">
                                                                    <span class="material-symbols-outlined text-primary">arrow_drop_down_circle</span>
                                                                    <span><?= e($ans['answer_text'] ?? 'N/A') ?></span>
                                                                </span>
                                                            <?php elseif ($qType === 'long_text'): ?>
                                                                <div class="bg-white dark:bg-slate-700 rounded p-3 text-sm whitespace-pre-wrap"><?= e($ans['answer_text'] ?? 'N/A') ?></div>
                                                            <?php else: ?>
                                                                <span><?= e($ans['answer_text'] ?? 'N/A') ?></span>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php 
                                    $responseNum++;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </main>
        </div>
    </div>
</body>
</html>