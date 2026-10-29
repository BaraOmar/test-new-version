<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

/**
 * Survey Builder Interface with Google Forms-like features:
 * - Organization-wide OR Department targeting (single choice)
 * - Scheduling (start date/time, end date/time)
 * - Anonymity toggle (on/off)
 * - Multiple Question Types: Short Answer, Long Answer, Multiple Choice, Rating Scale
 */

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("DB connection \$pdo not found. Check config/db.php");
}

// Helpful (won't change output unless there's an exception)
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function tableHasColumn(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

$stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$employees = $pdo->query("SELECT id, full_name, email, job_title FROM employees ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$preselectedEmployeeId = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =========================
   SAVE SURVEY (Launch Survey)
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Invalid request');
    }
    $title = trim((string)($_POST["title"] ?? ""));
    $description = trim((string)($_POST["description"] ?? ""));
    
    // Target type: "organization", "department", or "employee"
    $targetType = (string)($_POST["target_type"] ?? "organization");
    $selectedDepartments = $_POST["departments"] ?? [];
    $selectedEmployees = $_POST["employees"] ?? [];
    if (!is_array($selectedDepartments)) $selectedDepartments = [];
    if (!is_array($selectedEmployees)) $selectedEmployees = [];
    
    if ($targetType === "organization") {
        $selectedDepartments = [];
        $selectedEmployees = [];
    } elseif ($targetType === "department") {
        $selectedEmployees = [];
    } elseif ($targetType === "employee") {
        $selectedDepartments = [];
    }

    // Builder JSON (created by JS)
    $questionsJson = (string)($_POST["questions_json"] ?? "[]");
    $questions = json_decode($questionsJson, true);
    if (empty($questions)) {
    $_SESSION['flash_error'] = "No questions were received by the server.";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
    if (!is_array($questions)) $questions = [];
    $questions = array_values(array_filter($questions, function ($q) {
    return !empty(trim((string)($q['text'] ?? '')));
}));

    

  
    // Scheduling
    $startDate = trim((string)($_POST["start_date"] ?? ""));
    $startTime = trim((string)($_POST["start_time"] ?? ""));
    $endDate = trim((string)($_POST["end_date"] ?? ""));
    $endTime = trim((string)($_POST["end_time"] ?? ""));
    
    // Anonymity
    $isAnonymous = isset($_POST["anonymous"]) ? 1 : 0;

    // Basic validation
    if ($title === "") {
        $_SESSION['flash_error'] = "Survey title is required.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Validate target selection
    if ($targetType === "department" && empty($selectedDepartments)) {
        $_SESSION['flash_error'] = "Please select at least one department.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    if ($targetType === "employee" && empty($selectedEmployees)) {
        $_SESSION['flash_error'] = "Please select at least one employee.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Ensure survey_employee_targets exists BEFORE transaction (CREATE TABLE causes implicit COMMIT in MySQL)
    if ($targetType === "employee" && !empty($selectedEmployees)) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS survey_employee_targets (survey_id INT NOT NULL, employee_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (survey_id, employee_id))");
        } catch (PDOException $e) { /* ignore */ }
    }

    try {
        $pdo->beginTransaction();

        // Build surveys insert dynamically
        $cols = ["title", "description"];
        $vals = [$title, $description];

        // Add audience_type
        if (tableHasColumn($pdo, "surveys", "audience_type")) {
            $cols[] = "audience_type";
            $vals[] = $targetType;
        }

        // Add is_anonymous
        if (tableHasColumn($pdo, "surveys", "is_anonymous")) {
            $cols[] = "is_anonymous";
            $vals[] = $isAnonymous;
        } elseif (tableHasColumn($pdo, "surveys", "anonymous")) {
            $cols[] = "anonymous";
            $vals[] = $isAnonymous;
        }

        // Add start_at
        if (tableHasColumn($pdo, "surveys", "start_at") && !empty($startDate)) {
            $cols[] = "start_at";
            $startDateTime = $startDate . (!empty($startTime) ? ' ' . $startTime : ' 00:00:00');
            $vals[] = $startDateTime;
        }

        // Add end_at
        if (tableHasColumn($pdo, "surveys", "end_at") && !empty($endDate)) {
            $cols[] = "end_at";
            $endDateTime = $endDate . (!empty($endTime) ? ' ' . $endTime : ' 23:59:59');
            $vals[] = $endDateTime;
        }

        // Add duration_days (calculated from start and end)
        if (tableHasColumn($pdo, "surveys", "duration_days") && !empty($startDate) && !empty($endDate)) {
            $cols[] = "duration_days";
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $diff = $start->diff($end);
            $durationDays = $diff->days + 1;
            $vals[] = $durationDays;
        }

        // Add status
        if (tableHasColumn($pdo, "surveys", "status")) {
            $cols[] = "status";
            // If start date is in future, set as scheduled
            if (!empty($startDate) && strtotime($startDate) > time()) {
                $vals[] = "scheduled";
            } else {
                $vals[] = "active";
            }
        }

        // Add created_at
        if (tableHasColumn($pdo, "surveys", "created_at")) {
            $cols[] = "created_at";
        }

        $placeholders = [];
        foreach ($cols as $c) {
            if ($c === "created_at") {
                $placeholders[] = "NOW()";
            } else {
                $placeholders[] = "?";
            }
        }

        $sql = "INSERT INTO surveys (" . implode(",", $cols) . ") VALUES (" . implode(",", $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($vals);

        $survey_id = (int)$pdo->lastInsertId();

        // Save target departments (only if department targeting)
        if ($targetType === "department" && !empty($selectedDepartments)) {
            $insTarget = $pdo->prepare("
                INSERT INTO survey_targets (survey_id, department_id)
                VALUES (?, ?)
            ");
            foreach ($selectedDepartments as $deptId) {
                $deptId = (int)$deptId;
                if ($deptId > 0) {
                    $insTarget->execute([$survey_id, $deptId]);
                }
            }
        }

        // Save target employees (only if employee targeting)
        if ($targetType === "employee" && !empty($selectedEmployees)) {
            $insEmp = $pdo->prepare("INSERT INTO survey_employee_targets (survey_id, employee_id) VALUES (?, ?)");
            foreach ($selectedEmployees as $empId) {
                $empId = (int)$empId;
                if ($empId > 0) {
                    $insEmp->execute([$survey_id, $empId]);
                }
            }
        }

                // Save questions
        $hasSQCreated = tableHasColumn($pdo, "survey_questions", "created_at");
        $hasScaleMin  = tableHasColumn($pdo, "survey_questions", "scale_min");
        $hasScaleMax  = tableHasColumn($pdo, "survey_questions", "scale_max");
        $hasPosition  = tableHasColumn($pdo, "survey_questions", "position");

        // Insert question statement - using exact column names from database
        $qCols = ["survey_id", "question_text", "question_type", "is_required"];
        $qPH = ["?", "?", "?", "?"];
        
        // Add optional columns if they exist
        if ($hasScaleMin) {
            $qCols[] = "scale_min";
            $qPH[] = "?";
        }
        if ($hasScaleMax) {
            $qCols[] = "scale_max";
            $qPH[] = "?";
        }
        if ($hasPosition) {
            $qCols[] = "position";
            $qPH[] = "?";
        }
        if ($hasSQCreated) {
            $qCols[] = "created_at";
            $qPH[] = "NOW()";
        }

        $insQ = $pdo->prepare("
            INSERT INTO survey_questions (" . implode(",", $qCols) . ")
            VALUES (" . implode(",", $qPH) . ")
        ");

        // Options insert - check column names
        $optTextCol = tableHasColumn($pdo, "survey_question_options", "option_text") ? "option_text" :
                      (tableHasColumn($pdo, "survey_question_options", "text") ? "text" : "option_text");

        $optSortCol = tableHasColumn($pdo, "survey_question_options", "sort_order") ? "sort_order" :
                      (tableHasColumn($pdo, "survey_question_options", "position") ? "position" : "sort_order");

        $insOpt = $pdo->prepare("
            INSERT INTO survey_question_options (question_id, {$optTextCol}, {$optSortCol})
            VALUES (?, ?, ?)
        ");

        $position = 0;
        $savedQuestions = 0;
        
        foreach ($questions as $q) {
            $qText = trim((string)($q["text"] ?? ""));
            $qType = (string)($q["type"] ?? "rating");
            $qRequired = !empty($q["required"]) ? 1 : 0;

            if ($qText === "") {
                error_log("Skipping empty question at position {$position}");
                continue;
            }

            $scaleMin = ($qType === "rating" && isset($q["scaleMin"])) ? (int)$q["scaleMin"] : null;
            $scaleMax = ($qType === "rating" && isset($q["scaleMax"])) ? (int)$q["scaleMax"] : null;

            // Build bind array dynamically based on existing columns
            $bind = [$survey_id, $qText, $qType, $qRequired];
            if ($hasScaleMin) $bind[] = $scaleMin;
            if ($hasScaleMax) $bind[] = $scaleMax;
            if ($hasPosition) $bind[] = $position;

            try {
                $insQ->execute($bind);
                $question_id = (int)$pdo->lastInsertId();
                $savedQuestions++;

                // Save options for multiple choice questions
                if (($qType === "multiple_choice" || $qType === "checkbox" || $qType === "dropdown") && !empty($q["options"])) {
                    $opts = $q["options"];
                    if (!is_array($opts)) $opts = [];
                    $i = 1;
                    foreach ($opts as $optText) {
                        $optText = trim((string)$optText);
                        if ($optText === "") continue;
                        $insOpt->execute([$question_id, $optText, $i]);
                        $i++;
                    }
                }
            } catch (PDOException $e) {
                error_log("Error saving question: " . $e->getMessage());
                throw $e;
            }
            
            $position++;
        }
        
              error_log("Saved {$savedQuestions} questions for survey {$survey_id}");
        
        $pdo->commit();

        $anonStatus = $isAnonymous ? "Anonymous" : "Non-anonymous";
        $targetStatus = $targetType === "organization" ? "Organization-wide" : ($targetType === "employee" ? "Specific Employee(s)" : "Department-specific");
        $_SESSION['flash_success'] = "Survey launched successfully (ID: {$survey_id}). {$targetStatus} | {$anonStatus}";
        header("Location: survey_list.php"); // Redirect to survey list instead of self
        exit;

    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash_error'] = "Save failed: " . $ex->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Survey Builder Interface</title>
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
                    borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                    boxShadow: {
                        'card': '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
                        'floating': '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
                    }
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .active-card-border { border-left: 6px solid #642754; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 20px; }
        
        /* Radio button custom styling */
        .target-radio:checked + .target-card {
            border-color: #642754;
            background-color: rgba(140, 43, 238, 0.05);
        }
        .target-radio:checked + .target-card .target-indicator {
            background-color: #642754;
        }
        .target-radio:checked + .target-card .target-indicator-inner {
            display: block;
        }
        
        /* Department checkbox styling */
        .dept-checkbox:checked + .dept-label {
            background-color: rgba(140, 43, 238, 0.1);
            border-color: #642754;
        }
        
     .type-dropdown-menu{
    position:absolute;
    top:calc(100% + 6px);
    left:0;
    right:0;
    background:#ffffff;
    border:1px solid #e2e8f0;
    border-radius:10px;
    box-shadow:0 10px 20px rgba(0,0,0,0.08);
    z-index:9999;

    max-height:260px;
    overflow-y:auto;
}

.question-card{
    position:relative;
    z-index:1;
}

.question-card:hover{
    z-index:50;
}
.type-dropdown-menu::-webkit-scrollbar{
    width:6px;
}

.type-dropdown-menu::-webkit-scrollbar-thumb{
    background:#cbd5f5;
    border-radius:10px;
}

.dark .type-dropdown-menu{
    background:#1e293b;
    border-color:#334155;
}

.type-option-item{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 14px;
    font-size:14px;
    font-weight:500;
    cursor:pointer;
    transition:all .18s ease;
    border-bottom:1px solid #f1f5f9;
}

.dark .type-option-item{
    border-bottom:1px solid #334155;
}

.type-option-item:last-child{
    border-bottom:none;
}

.type-option-item span.material-symbols-outlined{
    font-size:20px;
    color:#642754;
}

.type-option-item:hover{
    background:#f8fafc;
}

.dark .type-option-item:hover{
    background:#334155;
}

.type-option-item.selected{
    background:rgba(140,43,238,0.12);
    color:#642754;
    font-weight:600;
}   
        
        /* Multiple choice options */
        .mc-option {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
        }
        .mc-option-input {
            flex: 1;
            border: none;
            border-bottom: 1px solid #e2e8f0;
            background: transparent;
            padding: 0.25rem 0;
            font-size: 0.875rem;
            outline: none;
        }
        .mc-option-input:focus {
            border-color: #642754;
        }
        .delete-option-btn {
            opacity: 0;
            transition: opacity 0.2s;
        }
        .mc-option:hover .delete-option-btn {
            opacity: 1;
        }
        
        /* Add question floating button */
        .add-question-float {
            position: fixed;
            right: 360px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 40;
        }
    </style>
</head>

<body class="bg-[#f8f4f8] text-slate-900 font-display transition-colors duration-200">
<form method="POST" action="" id="surveyForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
    <!-- Hidden inputs for JS data -->
    <input type="hidden" name="questions_json" id="questions_json" value="[]">

    <div class="flex h-screen overflow-hidden">
        <?php $currentPage = 'surveys'; include __DIR__ . '/navbar.php'; ?>

        <div class="flex flex-col flex-1 h-screen overflow-hidden">
            <?php $pageTitle = 'Survey Builder'; include __DIR__ . '/admin_header.php'; ?>
            <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6 shrink-0 z-20 relative">
                <div class="flex items-center gap-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#eb652c] text-white">
                        <span class="material-symbols-outlined">poll</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Draft</span>
                            <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                            <span class="text-xs text-slate-500">Last saved just now</span>
                        </div>
                        <h1 id="surveyHeaderTitle" class="text-lg font-bold leading-tight text-slate-900">
Untitled Survey
</h1>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    

                    <button type="submit" class="flex items-center justify-center rounded-lg bg-[#eb652c] px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-[#d85a24] transition-all">
                        Launch Survey
                    </button>

                    <div class="ml-2 h-8 w-8 rounded-full bg-primary/20 flex items-center justify-center overflow-hidden border border-primary/30">
                        <img alt="User Avatar" class="h-full w-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD5-cCAHzpxjuVsK4m11R28Bq4f4FRtZ7QS5kW8eP8WdBlDDQ8-xO7dXIq-rvmnaRrNti4FZNVc02lyCVI6WEvfTCgf8lIx70VPdDnD3A-O_W0MUe21XfjHgfYMxxuw0KrE6q0JJwFBOn9Iow0Bu8QlONgkPNrWUCZLTXzVH9r44lcOye3ipf03Pz-c5UQvXuBP2wSBNUt8rkDfmQZUoYdma7-6fQNxp0uitklp4pGIVYnkKHlqBCe1vwrEB6ejA00XdO7inFSy9OdG" />
                    </div>
                </div>
            </header>

            <div class="flex flex-1 overflow-hidden relative">
                <main class="flex-1 overflow-y-auto bg-[#f8f4f8] p-8 pb-32 no-scrollbar flex justify-center">
                    <div class="w-full max-w-3xl space-y-4 relative">

                        <?php if ($flashSuccess): ?>
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900 px-4 py-3 text-sm font-medium">
                                <?= e($flashSuccess) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($flashError): ?>
                            <div class="rounded-lg border border-red-200 bg-red-50 text-red-900 px-4 py-3 text-sm font-medium">
                                <?= e($flashError) ?>
                            </div>
                        <?php endif; ?>

                        <div class="rounded-xl bg-white dark:bg-slate-900 shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden relative">
                            <div class="h-2.5 bg-primary w-full absolute top-0 left-0"></div>
                            <div class="p-8 pt-10">
                                <div class="mb-4">
                                    <input
id="surveyTitleInput"
name="title"
class="w-full border-b border-transparent hover:border-slate-200 focus:border-primary bg-transparent py-2 text-3xl font-black tracking-tight focus:ring-0 placeholder-slate-400 transition-colors"
placeholder="Survey Title"
type="text"
value=""
/>
                                </div>
                                <textarea
                                    name="description"
                                    class="w-full resize-none border-b border-transparent hover:border-slate-200 focus:border-primary bg-transparent py-1 text-slate-500 dark:text-slate-400 focus:ring-0 placeholder-slate-400 text-sm transition-colors"
                                    placeholder="Form description"
                                    rows="1"
                                >Your feedback helps us build a better workplace. Please be as honest and thorough as possible.</textarea>
                            </div>
                        </div>

                        <!-- QUESTIONS CONTAINER -->
                        <div id="questionsContainer" class="space-y-4">
                            <!-- Questions will be added here -->
                        </div>

                        <!-- Add Question Button (Bottom) -->
                        <div class="flex justify-center py-4">
                            <button type="button" id="btnAddQuestionBottom" class="flex items-center gap-2 px-6 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-full shadow-sm hover:shadow-md hover:border-primary transition-all text-primary font-medium">
                                <span class="material-symbols-outlined">add</span>
                                Add Question
                            </button>
                        </div>

                    </div>
                </main>

                <!-- Floating Add Question Button -->
                <div class="add-question-float">
                    <button type="button" id="btnAddQuestionFloat" class="flex flex-col items-center gap-1 p-3 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 hover:border-primary hover:text-primary transition-all group">
                        <span class="material-symbols-outlined text-2xl">add_circle</span>
                        <span class="text-xs font-medium">Add</span>
                    </button>
                </div>

                <aside class="w-[360px] min-w-[360px] max-w-[360px] border-l border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 overflow-y-auto overflow-x-hidden no-scrollbar z-20">
                   <div class="space-y-10 max-w-full">
                        
                        <!-- TARGETING SECTION - Single Choice Only -->
                        <section>
                            <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-slate-100 mb-4">
                                <span class="material-symbols-outlined text-primary">groups</span>
                                Target Audience <span class="text-red-500">*</span>
                            </h3>
                            <div class="space-y-3">
                                <!-- Organization-wide Option -->
                                <label class="cursor-pointer block">
                                    <input type="radio" name="target_type" value="organization" class="sr-only target-radio" <?= !$preselectedEmployeeId ? 'checked' : '' ?> onchange="toggleTargetSelection()">
                                    <div class="target-card rounded-lg border-2 border-slate-200 dark:border-slate-700 p-3 transition-all hover:border-primary/50">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="target-indicator h-5 w-5 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center">
                                                    <div class="target-indicator-inner hidden h-2.5 w-2.5 rounded-full bg-white"></div>
                                                </div>
                                                <div>
                                                    <span class="text-sm font-bold block">Organization-wide</span>
                                                    <span class="text-xs text-slate-500">All employees will receive this survey</span>
                                                </div>
                                            </div>
                                            <span class="material-symbols-outlined text-slate-400">corporate_fare</span>
                                        </div>
                                    </div>
                                </label>

                                <!-- Department Option -->
                                <label class="cursor-pointer block">
                                    <input type="radio" name="target_type" value="department" class="sr-only target-radio" onchange="toggleTargetSelection()">
                                    <div class="target-card rounded-lg border-2 border-slate-200 dark:border-slate-700 p-3 transition-all hover:border-primary/50">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="target-indicator h-5 w-5 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center">
                                                    <div class="target-indicator-inner hidden h-2.5 w-2.5 rounded-full bg-white"></div>
                                                </div>
                                                <div>
                                                    <span class="text-sm font-bold block">Specific Departments</span>
                                                    <span class="text-xs text-slate-500">Select which departments receive this survey</span>
                                                </div>
                                            </div>
                                            <span class="material-symbols-outlined text-slate-400">account_tree</span>
                                        </div>
                                    </div>
                                </label>

                                <!-- Employee Option -->
                                <label class="cursor-pointer block">
                                    <input type="radio" name="target_type" value="employee" class="sr-only target-radio" onchange="toggleTargetSelection()" <?= $preselectedEmployeeId ? 'checked' : '' ?>>
                                    <div class="target-card rounded-lg border-2 border-slate-200 dark:border-slate-700 p-3 transition-all hover:border-primary/50">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="target-indicator h-5 w-5 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center">
                                                    <div class="target-indicator-inner hidden h-2.5 w-2.5 rounded-full bg-white"></div>
                                                </div>
                                                <div>
                                                    <span class="text-sm font-bold block">Specific Employee(s)</span>
                                                    <span class="text-xs text-slate-500">Only selected employees will receive this survey</span>
                                                </div>
                                            </div>
                                            <span class="material-symbols-outlined text-slate-400">person</span>
                                        </div>
                                    </div>
                                </label>

                                <!-- Department Selection (Hidden by default) -->
                                <div id="departmentSelection" class="hidden space-y-2 pl-8">
                                    <p class="text-xs text-slate-500 mb-2">Select departments:</p>
                                    <div class="max-h-40 overflow-y-auto space-y-1 border border-slate-200 dark:border-slate-700 rounded-lg p-2">
                                        <?php foreach ($departments as $dept): ?>
                                            <label class="flex items-center gap-2 text-sm p-2 rounded cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                                <input
                                                    type="checkbox"
                                                    name="departments[]"
                                                    value="<?= (int)$dept['id'] ?>"
                                                    class="dept-checkbox sr-only"
                                                    onchange="updateDeptLabel(this)"
                                                />
                                                <span class="dept-label flex-1 flex items-center gap-2 p-2 rounded border border-transparent transition-all">
                                                    <span class="material-symbols-outlined text-slate-400 text-lg">check_box_outline_blank</span>
                                                    <span><?= e($dept['name']) ?></span>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p id="deptError" class="text-xs text-red-500 hidden">Please select at least one department</p>
                                </div>

                                <!-- Employee Selection (Hidden by default) -->
                                <div id="employeeSelection" class="hidden space-y-2 pl-8">
                                    <p class="text-xs text-slate-500 mb-2">Select employees:</p>
                                    <div class="max-h-40 overflow-y-auto space-y-1 border border-slate-200 dark:border-slate-700 rounded-lg p-2">
                                        <?php foreach ($employees as $emp): ?>
                                            <label class="flex items-center gap-2 text-sm p-2 rounded cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                                <input
                                                    type="checkbox"
                                                    name="employees[]"
                                                    value="<?= (int)$emp['id'] ?>"
                                                    class="employee-checkbox sr-only"
                                                    onchange="updateEmployeeLabel(this)"
                                                    <?= ($preselectedEmployeeId && (int)$emp['id'] === $preselectedEmployeeId) ? 'checked' : '' ?>
                                                />
                                                <span class="employee-label flex-1 flex items-center gap-2 p-2 rounded border border-transparent transition-all">
                                                    <span class="material-symbols-outlined text-slate-400 text-lg">check_box_outline_blank</span>
                                                    <span><?= e($emp['full_name']) ?> <span class="text-slate-400"><?= e($emp['job_title'] ?? '') ?></span></span>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p id="employeeError" class="text-xs text-red-500 hidden">Please select at least one employee</p>
                                </div>
                            </div>
                        </section>

                        <!-- ANONYMITY SECTION -->
                        <section class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-4 border border-slate-200 dark:border-slate-700 w-full">
                           <div class="flex items-center justify-between mb-4 w-full">

<div class="flex items-center gap-2">

<h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">
Anonymity
</h3>
</div>

<label class="relative inline-flex items-center cursor-pointer shrink-0">

<input type="checkbox"
name="anonymous"
id="anonymousToggle"
class="sr-only peer"
checked
onchange="updateAnonymityUI()">

<div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:bg-primary transition"></div>

<div class="absolute left-[2px] top-[2px] bg-white w-5 h-5 rounded-full transition peer-checked:translate-x-full"></div>

</label>

</div>
                            <!-- Anonymous State -->
                            <div id="anonOnState" class="block">
                                <div class="flex items-center gap-2 text-xs font-medium text-emerald-600 dark:text-emerald-400 mb-2">
                                    <span class="material-symbols-outlined text-sm">lock</span>
                                    Anonymous Responses Enabled
                                </div>
                               <p class="text-[11px] leading-relaxed text-slate-500 dark:text-slate-400 italic break-words">
                                    Respondent names will be hidden. You will NOT be able to see who responded to this survey.
                                </p>
                            </div>
                            
                            <!-- Non-Anonymous State -->
                            <div id="anonOffState" class="hidden">
                                <div class="flex items-center gap-2 text-xs font-medium text-amber-600 dark:text-amber-400 mb-2">
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                    Non-Anonymous Responses
                                </div>
                               <p class="text-[11px] leading-relaxed text-slate-500 dark:text-slate-400 italic break-words">
                                    Respondent names will be visible. You WILL be able to see who responded to this survey.
                                </p>
                            </div>
                        </section>

                        <!-- SCHEDULING SECTION -->
                        <section>
                            <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-slate-100 mb-4">
                                <span class="material-symbols-outlined text-primary">calendar_today</span>
                                Scheduling
                            </h3>
                            <div class="space-y-4">
                                <!-- Start Date/Time -->
                                <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                                    <label class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                                        <span class="material-symbols-outlined text-primary text-sm">play_circle</span>
                                        Survey Opens
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <input 
                                                type="date" 
                                                name="start_date" 
                                                class="w-full rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-xs focus:border-primary focus:ring-1 focus:ring-primary"
                                                onchange="updateEndDateMin()"
                                            >
                                        </div>
                                        <div>
                                            <input 
                                                type="time" 
                                                name="start_time" 
                                                class="w-full rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-xs focus:border-primary focus:ring-1 focus:ring-primary"
                                            >
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-slate-500 mt-1">Leave empty to start immediately</p>
                                </div>

                                <!-- End Date/Time -->
                                <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                                    <label class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                                        <span class="material-symbols-outlined text-primary text-sm">stop_circle</span>
                                        Survey Closes
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <input 
                                                type="date" 
                                                name="end_date" 
                                                id="endDate"
                                                class="w-full rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-xs focus:border-primary focus:ring-1 focus:ring-primary"
                                            >
                                        </div>
                                        <div>
                                            <input 
                                                type="time" 
                                                name="end_time" 
                                                class="w-full rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-xs focus:border-primary focus:ring-1 focus:ring-primary"
                                            >
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-slate-500 mt-1">Leave empty for no end date</p>
                                </div>

                                <!-- Quick Duration Presets -->
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" onclick="setDuration(4)" class="px-3 py-1.5 text-xs font-medium rounded-full border border-slate-200 dark:border-slate-700 hover:border-primary hover:text-primary transition-colors">
                                        4 Days
                                    </button>
                                    <button type="button" onclick="setDuration(7)" class="px-3 py-1.5 text-xs font-medium rounded-full border border-slate-200 dark:border-slate-700 hover:border-primary hover:text-primary transition-colors">
                                        7 Days
                                    </button>
                                    <button type="button" onclick="setDuration(14)" class="px-3 py-1.5 text-xs font-medium rounded-full border border-slate-200 dark:border-slate-700 hover:border-primary hover:text-primary transition-colors">
                                        14 Days
                                    </button>
                                    <button type="button" onclick="setDuration(30)" class="px-3 py-1.5 text-xs font-medium rounded-full border border-slate-200 dark:border-slate-700 hover:border-primary hover:text-primary transition-colors">
                                        30 Days
                                    </button>
                                </div>
                            </div>
                        </section>

                       
                    </div>
                </aside>
            </div>
        </div>
    </div>
</form>

<script>
// Escape for safe HTML attribute/value output (prevents XSS)
function escapeHtml(s) {
    if (s == null) return '';
    const t = String(s);
    return t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// ====================
// QUESTION TYPE CONFIG
// ====================
const questionTypes = {
    short_text: { icon: 'short_text', label: 'Short Answer' },
    long_text: { icon: 'notes', label: 'Paragraph' },
    multiple_choice: { icon: 'radio_button_checked', label: 'Multiple Choice' },
    checkbox: { icon: 'check_box', label: 'Checkboxes' },
    dropdown: { icon: 'arrow_drop_down_circle', label: 'Dropdown' },
    rating: { icon: 'star', label: 'Rating Scale' }
};

let questionCounter = 0;
let activeDropdown = null;

// ====================
// CREATE QUESTION HTML
// ====================
function createQuestionHTML(type = 'short_text', questionText = '', isRequired = false) {
    const config = questionTypes[type];
    const qId = 'q_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
    questionCounter++;
    
    let contentHTML = '';
    
    switch(type) {
        case 'short_text':
            contentHTML = `
                <div class="short-text-content">
                    <input type="text" disabled placeholder="Short answer text" class="w-full border-b border-slate-300 dark:border-slate-600 bg-transparent text-sm text-slate-400 py-1" />
                </div>`;
            break;
        case 'long_text':
            contentHTML = `
                <div class="long-text-content">
                    <textarea disabled placeholder="Long answer text" rows="2" class="w-full border-b border-slate-300 dark:border-slate-600 bg-transparent text-sm text-slate-400 py-1 resize-none"></textarea>
                </div>`;
            break;
        case 'multiple_choice':
            contentHTML = `
                <div class="multiple-choice-content">
                    <div class="mc-options-container space-y-1" data-options>
                        <div class="mc-option">
                            <span class="material-symbols-outlined text-slate-400 text-lg">radio_button_unchecked</span>
                            <input type="text" placeholder="Option 1" class="mc-option-input" data-option />
                        </div>
                        <div class="mc-option">
                            <span class="material-symbols-outlined text-slate-400 text-lg">radio_button_unchecked</span>
                            <input type="text" placeholder="Option 2" class="mc-option-input" data-option />
                        </div>
                    </div>
                    <button type="button" class="add-option-btn flex items-center gap-2 mt-3 text-sm text-primary hover:underline" onclick="addOption(this)">
                        <span class="material-symbols-outlined text-lg">add_circle</span>
                        Add option
                    </button>
                </div>`;
            break;
        case 'checkbox':
            contentHTML = `
                <div class="checkbox-content">
                    <div class="mc-options-container space-y-1" data-options>
                        <div class="mc-option">
                            <span class="material-symbols-outlined text-slate-400 text-lg">check_box_outline_blank</span>
                            <input type="text" placeholder="Option 1" class="mc-option-input" data-option />
                        </div>
                        <div class="mc-option">
                            <span class="material-symbols-outlined text-slate-400 text-lg">check_box_outline_blank</span>
                            <input type="text" placeholder="Option 2" class="mc-option-input" data-option />
                        </div>
                    </div>
                    <button type="button" class="add-option-btn flex items-center gap-2 mt-3 text-sm text-primary hover:underline" onclick="addOption(this)">
                        <span class="material-symbols-outlined text-lg">add_circle</span>
                        Add option
                    </button>
                </div>`;
            break;
        case 'dropdown':
            contentHTML = `
                <div class="dropdown-content">
                    <div class="mc-options-container space-y-1" data-options>
                        <div class="mc-option">
                            <span class="material-symbols-outlined text-slate-400 text-lg">format_list_numbered</span>
                            <input type="text" placeholder="Option 1" class="mc-option-input" data-option />
                        </div>
                        <div class="mc-option">
                            <span class="material-symbols-outlined text-slate-400 text-lg">format_list_numbered</span>
                            <input type="text" placeholder="Option 2" class="mc-option-input" data-option />
                        </div>
                    </div>
                    <button type="button" class="add-option-btn flex items-center gap-2 mt-3 text-sm text-primary hover:underline" onclick="addOption(this)">
                        <span class="material-symbols-outlined text-lg">add_circle</span>
                        Add option
                    </button>
                </div>`;
            break;
        case 'rating':
            contentHTML = `
                <div class="rating-content">
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <div class="flex gap-1">
                                <span class="material-symbols-outlined text-primary fill-1 text-3xl">star</span>
                                <span class="material-symbols-outlined text-primary fill-1 text-3xl">star</span>
                                <span class="material-symbols-outlined text-primary fill-1 text-3xl">star</span>
                                <span class="material-symbols-outlined text-slate-200 dark:text-slate-700 text-3xl">star</span>
                                <span class="material-symbols-outlined text-slate-200 dark:text-slate-700 text-3xl">star</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-xs text-slate-500 pl-1">
                            <span>Scale: 1-5</span>
                        </div>
                    </div>
                </div>`;
            break;
    }
    
    return `
        <div class="group relative flex items-start question-card" data-question-card data-question-type="${type}" id="${qId}">
       <div class="flex-1 rounded-xl bg-white dark:bg-slate-900 shadow-md border border-slate-200 dark:border-slate-800 active-card-border transition-all relative overflow-visible">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity cursor-grab z-20 bg-white dark:bg-slate-800 rounded-full p-1 shadow-sm border border-slate-100 dark:border-slate-700">
                    <span class="material-symbols-outlined text-slate-400 text-lg rotate-90">drag_indicator</span>
                </div>

                <div class="p-6 pb-2">
                    <div class="flex flex-col md:flex-row gap-4 mb-4">
                        <div class="flex-1 bg-slate-50 dark:bg-slate-800/50 p-2 rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <input
                                class="w-full bg-transparent border-b border-slate-300 focus:border-primary border-x-0 border-t-0 p-2 text-base font-medium focus:ring-0 focus:outline-none"
                                placeholder="Question"
                                type="text"
                                value="${escapeHtml(questionText)}"
                                data-question-text
                            />
                        </div>
                      
                          <div class="w-full md:w-56 shrink-0 relative type-selector-wrapper">

<button 
type="button"
onclick="toggleTypeDropdown(this)"
class="type-selector-btn w-full flex items-center justify-between rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition">

<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-[20px]">${config.icon}</span>
<span class="type-label">${config.label}</span>
</div>

<span class="material-symbols-outlined text-slate-500 text-[20px]">expand_more</span>

</button>

</div>  
                    </div>

                    <div class="question-content mb-4 px-2">
                        ${contentHTML}
                    </div>
                </div>

               

                <div class="border-t border-slate-100 dark:border-slate-800 px-6 py-3 flex items-center justify-end gap-4">
                    <div class="flex items-center gap-2 border-r border-slate-200 dark:border-slate-700 pr-4">
                        <button type="button" class="p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors" title="Duplicate" onclick="duplicateQuestion(this)">
                            <span class="material-symbols-outlined text-[20px]">content_copy</span>
                        </button>
                        <button type="button" class="p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors hover:text-red-500" title="Delete" onclick="deleteQuestion(this)">
                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-3 pl-2">
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-300">Required</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input class="sr-only peer" type="checkbox" ${isRequired ? 'checked' : ''} data-required>
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 dark:peer-focus:ring-primary/30 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// ====================
// TYPE DROPDOWN FUNCTIONS
// ====================
function toggleTypeDropdown(btn) {
    const wrapper = btn.closest('.type-selector-wrapper');
    let dropdown = wrapper.querySelector('.type-dropdown-menu');
    
    // Close all other dropdowns first
    document.querySelectorAll('.type-dropdown-menu').forEach(d => {
        if (d !== dropdown) d.remove();
    });
    
    if (dropdown) {
        dropdown.remove();
        return;
    }
    
    const card = btn.closest('[data-question-card]');
    const currentType = card.getAttribute('data-question-type');
    
    dropdown = document.createElement('div');
    dropdown.className = 'type-dropdown-menu';
    
    Object.entries(questionTypes).forEach(([type, config]) => {
        const option = document.createElement('div');
        option.className = 'type-option-item' + (type === currentType ? ' selected' : '');
       option.innerHTML = `
<span class="material-symbols-outlined">${config.icon}</span>
<span class="flex-1">${config.label}</span>
`;
        option.onclick = () => changeQuestionType(card, type);
        dropdown.appendChild(option);
    });
    
    wrapper.appendChild(dropdown);
}

function changeQuestionType(card, newType) {
    const textInput = card.querySelector('[data-question-text]');
    const currentText = textInput ? textInput.value : '';
    const requiredCheckbox = card.querySelector('[data-required]');
    const isRequired = requiredCheckbox ? requiredCheckbox.checked : false;
    
    // Get current options if it's a choice type
    let currentOptions = [];
    const optionsContainer = card.querySelector('[data-options]');
    if (optionsContainer) {
        optionsContainer.querySelectorAll('[data-option]').forEach(input => {
            if (input.value.trim()) currentOptions.push(input.value.trim());
        });
    }
    
    // Create new question HTML
    const newHTML = createQuestionHTML(newType, currentText, isRequired);
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = newHTML;
    const newCard = tempDiv.firstElementChild;
    
    // Preserve options if switching between choice types
    if (currentOptions.length > 0 && ['multiple_choice', 'checkbox', 'dropdown'].includes(newType)) {
        const newOptionsContainer = newCard.querySelector('[data-options]');
        if (newOptionsContainer) {
            newOptionsContainer.innerHTML = '';
            const iconMap = {
                multiple_choice: 'radio_button_unchecked',
                checkbox: 'check_box_outline_blank',
                dropdown: 'format_list_numbered'
            };
            currentOptions.forEach((opt, idx) => {
                newOptionsContainer.innerHTML += `
                    <div class="mc-option">
                        <span class="material-symbols-outlined text-slate-400 text-lg">${iconMap[newType]}</span>
                        <input type="text" placeholder="Option ${idx + 1}" class="mc-option-input" data-option value="${escapeHtml(opt)}" />
                        <button type="button" class="delete-option-btn p-1 text-slate-400 hover:text-red-500" onclick="deleteOption(this)">
                            <span class="material-symbols-outlined text-lg">close</span>
                        </button>
                    </div>
                `;
            });
        }
    }
    
    // Replace card
    card.replaceWith(newCard);
    
    // Remove any open dropdowns
    document.querySelectorAll('.type-dropdown-menu').forEach(d => d.remove());
}

// ====================
// OPTION MANAGEMENT
// ====================
function addOption(btn) {
    const container = btn.previousElementSibling;
    const optionCount = container.querySelectorAll('.mc-option').length + 1;
    const card = btn.closest('[data-question-card]');
    const type = card.getAttribute('data-question-type');
    
    const iconMap = {
        multiple_choice: 'radio_button_unchecked',
        checkbox: 'check_box_outline_blank',
        dropdown: 'format_list_numbered'
    };
    
    const newOption = document.createElement('div');
    newOption.className = 'mc-option';
    newOption.innerHTML = `
        <span class="material-symbols-outlined text-slate-400 text-lg">${iconMap[type]}</span>
        <input type="text" placeholder="Option ${optionCount}" class="mc-option-input" data-option />
        <button type="button" class="delete-option-btn p-1 text-slate-400 hover:text-red-500" onclick="deleteOption(this)">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
    `;
    
    container.appendChild(newOption);
    newOption.querySelector('input').focus();
}

function deleteOption(btn) {
    const option = btn.closest('.mc-option');
    const container = option.parentElement;
    if (container.querySelectorAll('.mc-option').length > 1) {
        option.remove();
    }
}

// ====================
// QUESTION MANAGEMENT
// ====================
function addNewQuestion(type = 'short_text') {
    const container = document.getElementById('questionsContainer');
    const html = createQuestionHTML(type);
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;
    const newCard = tempDiv.firstElementChild;
    container.appendChild(newCard);
    
    // Focus the question text input
    const textInput = newCard.querySelector('[data-question-text]');
    if (textInput) {
        textInput.focus();
    }
    
    // Scroll to new question
    newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function deleteQuestion(btn) {
    const card = btn.closest('[data-question-card]');
    const container = document.getElementById('questionsContainer');
    const allCards = container.querySelectorAll('[data-question-card]');
    
    if (allCards.length <= 1) {
        alert('You must have at least one question.');
        return;
    }
    
    card.remove();
}

function duplicateQuestion(btn) {
    const card = btn.closest('[data-question-card]');
    const type = card.getAttribute('data-question-type');
    const text = card.querySelector('[data-question-text]')?.value || '';
    const isRequired = card.querySelector('[data-required]')?.checked || false;
    
    // Get options if choice type
    let options = [];
    const optionsContainer = card.querySelector('[data-options]');
    if (optionsContainer) {
        optionsContainer.querySelectorAll('[data-option]').forEach(input => {
            if (input.value.trim()) options.push(input.value.trim());
        });
    }
    
    const container = document.getElementById('questionsContainer');
    const html = createQuestionHTML(type, text + ' (Copy)', isRequired);
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;
    const newCard = tempDiv.firstElementChild;
    
    // Restore options for choice types
    if (options.length > 0 && ['multiple_choice', 'checkbox', 'dropdown'].includes(type)) {
        const newOptionsContainer = newCard.querySelector('[data-options]');
        if (newOptionsContainer) {
            newOptionsContainer.innerHTML = '';
            const iconMap = {
                multiple_choice: 'radio_button_unchecked',
                checkbox: 'check_box_outline_blank',
                dropdown: 'format_list_numbered'
            };
            options.forEach((opt, idx) => {
                newOptionsContainer.innerHTML += `
                    <div class="mc-option">
                        <span class="material-symbols-outlined text-slate-400 text-lg">${iconMap[type]}</span>
                        <input type="text" placeholder="Option ${idx + 1}" class="mc-option-input" data-option value="${escapeHtml(opt)}" />
                        <button type="button" class="delete-option-btn p-1 text-slate-400 hover:text-red-500" onclick="deleteOption(this)">
                            <span class="material-symbols-outlined text-lg">close</span>
                        </button>
                    </div>
                `;
            });
        }
    }
    
    card.after(newCard);
    newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// ====================
// TARGETING FUNCTIONS
// ====================
function toggleTargetSelection() {
    const targetType = document.querySelector('input[name="target_type"]:checked').value;
    const deptSelection = document.getElementById('departmentSelection');
    const empSelection = document.getElementById('employeeSelection');
    const deptError = document.getElementById('deptError');
    const empError = document.getElementById('employeeError');
    
    deptSelection.classList.add('hidden');
    empSelection.classList.add('hidden');
    deptError?.classList.add('hidden');
    empError?.classList.add('hidden');
    
    if (targetType === 'department') {
        deptSelection.classList.remove('hidden');
        document.querySelectorAll('.employee-checkbox').forEach(cb => { cb.checked = false; updateEmployeeLabel(cb); });
    } else if (targetType === 'employee') {
        empSelection.classList.remove('hidden');
        document.querySelectorAll('.dept-checkbox').forEach(cb => { cb.checked = false; updateDeptLabel(cb); });
    } else {
        document.querySelectorAll('.dept-checkbox').forEach(cb => { cb.checked = false; updateDeptLabel(cb); });
        document.querySelectorAll('.employee-checkbox').forEach(cb => { cb.checked = false; updateEmployeeLabel(cb); });
    }
}

function toggleDepartmentSelection() { toggleTargetSelection(); }

function updateDeptLabel(checkbox) {
    const label = checkbox.nextElementSibling;
    const icon = label.querySelector('.material-symbols-outlined');
    
    if (checkbox.checked) {
        label.classList.add('bg-primary/10', 'border-primary');
        icon.textContent = 'check_box';
        icon.classList.add('text-primary');
        icon.classList.remove('text-slate-400');
    } else {
        label.classList.remove('bg-primary/10', 'border-primary');
        icon.textContent = 'check_box_outline_blank';
        icon.classList.remove('text-primary');
        icon.classList.add('text-slate-400');
    }
    
    const anyChecked = document.querySelectorAll('.dept-checkbox:checked').length > 0;
    if (anyChecked) {
        document.getElementById('deptError').classList.add('hidden');
    }
}

function updateEmployeeLabel(checkbox) {
    const label = checkbox.nextElementSibling;
    const icon = label.querySelector('.material-symbols-outlined');
    if (checkbox.checked) {
        label.classList.add('bg-primary/10', 'border-primary');
        icon.textContent = 'check_box';
        icon.classList.add('text-primary');
        icon.classList.remove('text-slate-400');
    } else {
        label.classList.remove('bg-primary/10', 'border-primary');
        icon.textContent = 'check_box_outline_blank';
        icon.classList.remove('text-primary');
        icon.classList.add('text-slate-400');
    }
    const anyChecked = document.querySelectorAll('.employee-checkbox:checked').length > 0;
    if (anyChecked && document.getElementById('employeeError')) {
        document.getElementById('employeeError').classList.add('hidden');
    }
}

// ====================
// ANONYMITY FUNCTIONS
// ====================
function updateAnonymityUI() {
    const toggle = document.getElementById('anonymousToggle');
    const onState = document.getElementById('anonOnState');
    const offState = document.getElementById('anonOffState');

    // If elements are missing, safely exit to avoid breaking other JS
    if (!toggle || !onState || !offState) {
        return;
    }

    if (toggle.checked) {
        onState.classList.remove('hidden');
        offState.classList.add('hidden');
    } else {
        onState.classList.add('hidden');
        offState.classList.remove('hidden');
    }
}

// ====================
// SCHEDULING FUNCTIONS
// ====================
function updateEndDateMin() {
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDateInput = document.getElementById('endDate');
    
    if (startDate) {
        endDateInput.min = startDate;
    } else {
        endDateInput.removeAttribute('min');
    }
}

function setDuration(days) {
    const startDateInput = document.querySelector('input[name="start_date"]');
    const endDateInput = document.querySelector('input[name="end_date"]');
    
    let startDate = startDateInput.value ? new Date(startDateInput.value) : new Date();
    if (!startDateInput.value) {
        startDateInput.value = startDate.toISOString().split('T')[0];
    }
    
    const endDate = new Date(startDate);
    endDate.setDate(endDate.getDate() + days);
    endDateInput.value = endDate.toISOString().split('T')[0];
    
    if (!document.querySelector('input[name="start_time"]').value) {
        document.querySelector('input[name="start_time"]').value = '09:00';
    }
    if (!document.querySelector('input[name="end_time"]').value) {
        document.querySelector('input[name="end_time"]').value = '23:59';
    }
}
// ====================
// FORM VALIDATION & SUBMIT
// ====================
function validateForm() {
    const targetType = document.querySelector('input[name="target_type"]:checked').value;
    
    if (targetType === 'department') {
        const selectedDepts = document.querySelectorAll('.dept-checkbox:checked');
        if (selectedDepts.length === 0) {
            document.getElementById('deptError').classList.remove('hidden');
            document.getElementById('departmentSelection').scrollIntoView({ behavior: 'smooth' });
            return false;
        }
    }
    if (targetType === 'employee') {
        const selectedEmps = document.querySelectorAll('.employee-checkbox:checked');
        if (selectedEmps.length === 0) {
            document.getElementById('employeeError').classList.remove('hidden');
            document.getElementById('employeeSelection').scrollIntoView({ behavior: 'smooth' });
            return false;
        }
    }
    
    // Validate survey title
    const titleInput = document.getElementById('surveyTitleInput');
    if (!titleInput.value.trim()) {
        alert("Please enter a survey title.");
        titleInput.focus();
        return false;
    }
    
    return true;
}

function collectQuestions() {
    const cards = document.querySelectorAll('[data-question-card]');
    const out = [];

    cards.forEach((card, index) => {
        const type = card.getAttribute('data-question-type') || 'short_text';
        const textEl = card.querySelector('[data-question-text]');
        const requiredEl = card.querySelector('[data-required]');

        const text = textEl ? (textEl.value || '').trim() : '';
        const required = requiredEl ? requiredEl.checked : false;

        // Skip completely empty questions
        if (text === '') {
            console.log(`Skipping question ${index + 1}: empty text`);
            return;
        }

        const q = {
            type: type,
            text: text,
            required: required,
            scaleMin: 1,
            scaleMax: 5,
            options: []
        };

        // Handle rating scale
        if (type === 'rating') {
            // Get scale values from inputs if they exist, otherwise default to 1-5
            const scaleMinEl = card.querySelector('[data-scale-min]');
            const scaleMaxEl = card.querySelector('[data-scale-max]');
            if (scaleMinEl) q.scaleMin = parseInt(scaleMinEl.value) || 1;
            if (scaleMaxEl) q.scaleMax = parseInt(scaleMaxEl.value) || 5;
        }

        // Handle choice-based questions
        if (type === 'multiple_choice' || type === 'checkbox' || type === 'dropdown') {
            const optionInputs = card.querySelectorAll('[data-option]');
            optionInputs.forEach(input => {
                const val = input.value.trim();
                if (val !== '') {
                    q.options.push(val);
                }
            });
            
            // Ensure at least 2 options for choice questions
            if (q.options.length < 2) {
                console.warn(`Question "${text}" has fewer than 2 options`);
            }
        }

        out.push(q);
    });

    console.log(`Collected ${out.length} questions:`, out);
    return out;
}

// ====================
// INITIALIZATION
// ====================
document.addEventListener('DOMContentLoaded', function() {
    // Add initial question
    addNewQuestion('rating');
    
    // Wire up add question buttons
    document.getElementById('btnAddQuestionBottom').addEventListener('click', () => addNewQuestion('short_text'));
    document.getElementById('btnAddQuestionFloat').addEventListener('click', () => addNewQuestion('short_text'));
    
    // Initialize UI
    updateAnonymityUI();
    toggleTargetSelection();
    document.querySelectorAll('.employee-checkbox:checked').forEach(cb => updateEmployeeLabel(cb));
    
    // IMPORTANT: Handle form submission
    document.getElementById('surveyForm').addEventListener('submit', function(e) {
        // Prevent default submission immediately
        e.preventDefault();
        
        if (!validateForm()) {
            return false;
        }

        const qs = collectQuestions();

        if (qs.length === 0) {
            alert("Add at least one question before launching the survey.");
            return false;
        }

        const hidden = document.getElementById('questions_json');
        hidden.value = JSON.stringify(qs);

        console.log("Submitting with questions:", hidden.value);
        
        // Now manually submit the form
        this.submit();
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.type-selector-wrapper')) {
            document.querySelectorAll('.type-dropdown-menu').forEach(d => d.remove());
        }
    });
    
    // Title update handler
    const titleInput = document.getElementById("surveyTitleInput");
    const headerTitle = document.getElementById("surveyHeaderTitle");

    function updateHeaderTitle() {
        const val = titleInput.value.trim();
        headerTitle.textContent = val === "" ? "Untitled Survey" : val;
    }

    titleInput.addEventListener("input", updateHeaderTitle);
    updateHeaderTitle();
});
</script>
</body>
</html>
