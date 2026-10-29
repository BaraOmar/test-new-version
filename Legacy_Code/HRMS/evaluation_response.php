<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("DB connection \$pdo not found. Check config/db.php");
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// =========================
//  INPUTS & CURRENT EMPLOYEE
// =========================
$evaluation_id = (int)($_GET['id'] ?? 0);
if ($evaluation_id <= 0) {
    die("Evaluation not found.");
}

// Prefer explicit employee_id, fall back to user_id for employees
$employee_id = null;
if (isset($_SESSION['employee_id'])) {
    $employee_id = (int)$_SESSION['employee_id'];
} elseif (isset($_SESSION['user_id'])) {
    $employee_id = (int)$_SESSION['user_id'];
}

if (!$employee_id) {
    die("Employee not authenticated.");
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =========================
//  LOAD EVALUATION & QUESTIONS
// =========================
$stmt = $pdo->prepare("SELECT * FROM evaluations WHERE id = ?");
$stmt->execute([$evaluation_id]);
$evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evaluation) {
    die("Evaluation not found.");
}

$stmt = $pdo->prepare("
    SELECT *
    FROM evaluation_questions
    WHERE evaluation_id = ?
    ORDER BY position ASC, id ASC
");
$stmt->execute([$evaluation_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================
//  PREVENT MULTIPLE SUBMISSIONS (GET)
// =========================
$alreadyStmt = $pdo->prepare("
    SELECT id
    FROM evaluation_responses
    WHERE evaluation_id = ? AND employee_id = ?
    LIMIT 1
");
$alreadyStmt->execute([$evaluation_id, $employee_id]);
$hasSubmitted = (bool)$alreadyStmt->fetchColumn();

// =========================
//  HANDLE SUBMISSION (POST)
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Invalid request');
    }
    if ($hasSubmitted) {
        die("You have already submitted this evaluation.");
    }

    try {
        $pdo->beginTransaction();

        // Block again inside transaction to be extra safe
        $checkStmt = $pdo->prepare("
            SELECT id
            FROM evaluation_responses
            WHERE evaluation_id = ? AND employee_id = ?
            LIMIT 1
        ");
        $checkStmt->execute([$evaluation_id, $employee_id]);
        if ($checkStmt->fetch()) {
            $pdo->rollBack();
            die("You have already submitted this evaluation.");
        }

        // Create response header row
        $stmt = $pdo->prepare("
            INSERT INTO evaluation_responses (evaluation_id, employee_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$evaluation_id, $employee_id]);
        $response_id = (int)$pdo->lastInsertId();

        // Save each answer
        foreach ($questions as $q) {
            $qid = (int)$q['id'];
            $field = "q_{$qid}";
            if (!isset($_POST[$field])) {
                continue;
            }

            $raw = $_POST[$field];

            // Handle multiple selections (checkbox)
            if (is_array($raw)) {
                $value = implode(', ', array_map('trim', $raw));
            } else {
                $value = trim((string)$raw);
            }

            if ($value === '') {
                continue;
            }

            $answer_text = null;
            $answer_number = null;

            if (is_numeric($value)) {
                $answer_number = $value;
            } else {
                $answer_text = $value;
            }

            $stmt = $pdo->prepare("
                INSERT INTO evaluation_answers
                    (response_id, evaluation_id, question_id, employee_id, answer_text, answer_number)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $response_id,
                $evaluation_id,
                $qid,
                $employee_id,
                $answer_text,
                $answer_number
            ]);
        }

        $pdo->commit();

        header("Location: survey_and_evaluation_display.php");
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Error saving evaluation responses: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&amp;display=swap" rel="stylesheet" />

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
                        "accent": "#eb652c",
                        "bg-lavender": "#f8f4f8",
                        "card-border": "#e8e0e8"
                    },
                    fontFamily: {
                        "sans": ["Roboto", "sans-serif"]
                    },
                    borderRadius: {
                        "google": "8px"
                    }
                }
            }
        }
    </script>

    <title>Evaluation Response Interface</title>

    <style type="text/tailwindcss">
        @layer base {
            body {
                @apply bg-bg-lavender font-sans text-[#202124];
            }
        }

        .google-card {
            @apply bg-white rounded-google shadow-[0_2px_1px_-1px_rgba(0,0,0,0.2),0_1px_1px_0_rgba(0,0,0,0.14),0_1px_3px_0_rgba(0,0,0,0.12)] border border-card-border overflow-hidden;
        }

        .header-top-border {
            @apply border-t-[10px] border-[#eb652c];
        }
    </style>
</head>

<body class="min-h-screen bg-[#f8f4f8]">

<?php if ($hasSubmitted && $_SERVER["REQUEST_METHOD"] !== "POST"): ?>
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="google-card p-8 max-w-md w-full text-center">
            <span class="material-symbols-outlined text-5xl text-green-500 mb-4">check_circle</span>
            <h1 class="text-xl font-medium mb-2">You have already submitted this evaluation.</h1>
            <p class="text-sm text-gray-600 mb-6">
                Thank you for completing "<?= htmlspecialchars($evaluation['title']) ?>".
                You can return to your task list to review other surveys or evaluations.
            </p>
            <a href="survey_and_evaluation_display.php"
               class="inline-flex items-center justify-center px-6 py-2.5 rounded-google bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-medium transition-all">
                Back to my tasks
            </a>
        </div>
    </div>
<?php else: ?>

<form method="POST">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />

    <div class="relative flex min-h-screen w-full flex-col">

        <header class="flex items-center justify-between bg-[#642754] border-b border-white/10 px-6 py-2 sticky top-0 z-50">

            <div class="flex items-center gap-3">
                <div class="size-10 flex items-center justify-center rounded-lg bg-[#eb652c] text-white">
                    <span class="material-symbols-outlined text-2xl">assignment_turned_in</span>
                </div>

                <h2 class="text-white text-xl font-normal">Performance Evaluation</h2>
            </div>

        </header>

        <main class="flex flex-1 justify-center py-6 px-4">

            <div class="flex flex-col max-w-[770px] w-full gap-3">

                <div class="google-card header-top-border p-6 pt-5">

                    <h1 class="text-3xl font-normal mb-2">
                        <?= htmlspecialchars($evaluation['title']) ?>
                    </h1>

                    <?php if (!empty($evaluation['description'])): ?>
                        <p class="text-sm text-[#202124] mb-4">
                            <?= htmlspecialchars($evaluation['description']) ?>
                        </p>
                    <?php endif; ?>

                </div>

                <?php if (empty($questions)): ?>
                    <div class="google-card p-6 text-center text-red-600">
                        <span class="material-symbols-outlined text-4xl mb-2">error</span>
                        <p class="font-medium">This evaluation has no questions configured.</p>
                        <p class="text-sm text-gray-500 mt-1">Please contact your HR administrator.</p>
                    </div>
                <?php else: ?>

                    <?php foreach ($questions as $index => $q): ?>
                        <div class="google-card p-6">

                            <div class="flex items-start gap-1 mb-6">

                                <span class="text-base font-medium">
                                    <?= ($index + 1) ?>. <?= htmlspecialchars($q['question_text']) ?>
                                </span>

                                <?php if (!empty($q['is_required'])): ?>
                                    <span class="text-red-500">*</span>
                                <?php endif; ?>

                            </div>

                            <?php
                                $qType = $q['question_type'];
                            ?>

                            <?php if ($qType === "rating"): ?>
                                <?php
                                    $min = isset($q['scale_min']) && $q['scale_min'] !== null ? (int)$q['scale_min'] : 1;
                                    $max = isset($q['scale_max']) && $q['scale_max'] !== null ? (int)$q['scale_max'] : 5;
                                    if ($min >= $max) {
                                        $min = 1;
                                        $max = 5;
                                    }
                                ?>
                                <div class="flex flex-col gap-4">
                                    <div class="flex items-center gap-2">
                                        <?php for ($i = $min; $i <= $max; $i++): ?>
                                            <label>
                                                <input
                                                    type="radio"
                                                    name="q_<?= (int)$q['id'] ?>"
                                                    value="<?= $i ?>"
                                                    class="hidden peer"
                                                >
                                                <span class="material-symbols-outlined text-3xl text-gray-300 peer-checked:text-primary cursor-pointer">
                                                    star
                                                </span>
                                            </label>
                                        <?php endfor; ?>
                                        <span class="ml-4 text-xs text-gray-500 uppercase tracking-wide">
                                            Scale <?= $min ?>-<?= $max ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($qType === "multiple_choice" || $qType === "checkbox" || $qType === "dropdown"): ?>
                                <?php
                                    $optStmt = $pdo->prepare("SELECT * FROM evaluation_question_options WHERE question_id = ? ORDER BY id ASC");
                                    $optStmt->execute([(int)$q['id']]);
                                    $options = $optStmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <?php if ($qType === "multiple_choice" || $qType === "checkbox"): ?>
                                    <div class="space-y-4">
                                        <?php foreach ($options as $op): ?>
                                            <?php
                                                $label = '';
                                                if (isset($op['option_text']) && $op['option_text'] !== '') {
                                                    $label = $op['option_text'];
                                                } elseif (isset($op['text'])) {
                                                    $label = $op['text'];
                                                } elseif (isset($op['label'])) {
                                                    $label = $op['label'];
                                                }
                                                $label = (string)$label;
                                            ?>
                                            <label class="flex items-center gap-3 group cursor-pointer">
                                                <input
                                                    class="w-5 h-5 text-primary border-gray-300 focus:ring-primary focus:ring-offset-0"
                                                    name="q_<?= (int)$q['id'] ?><?= $qType === 'checkbox' ? '[]' : '' ?>"
                                                    value="<?= htmlspecialchars($label) ?>"
                                                    type="<?= $qType === 'checkbox' ? 'checkbox' : 'radio' ?>"
                                                />
                                                <span class="text-sm text-[#202124]">
                                                    <?= htmlspecialchars($label) ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($qType === "dropdown"): ?>
                                    <div class="mt-2">
                                        <select
                                            name="q_<?= (int)$q['id'] ?>"
                                            class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-primary focus:border-primary bg-white"
                                        >
                                            <option value="">Select an option</option>
                                            <?php foreach ($options as $op): ?>
                                                <?php
                                                    $label = '';
                                                    if (isset($op['option_text']) && $op['option_text'] !== '') {
                                                        $label = $op['option_text'];
                                                    } elseif (isset($op['text'])) {
                                                        $label = $op['text'];
                                                    } elseif (isset($op['label'])) {
                                                        $label = $op['label'];
                                                    }
                                                    $label = (string)$label;
                                                ?>
                                                <option value="<?= htmlspecialchars($label) ?>">
                                                    <?= htmlspecialchars($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($qType === "short_text"): ?>
                                <div class="relative group">
                                    <input
                                        name="q_<?= (int)$q['id'] ?>"
                                        class="w-full border-0 border-b border-gray-300 bg-transparent py-2 px-0 focus:ring-0 focus:border-primary transition-colors text-sm placeholder:text-gray-400"
                                        placeholder="Your answer"
                                    >
                                </div>
                            <?php endif; ?>

                            <?php if ($qType === "long_text"): ?>
                                <div class="relative group">
                                    <textarea
                                        name="q_<?= (int)$q['id'] ?>"
                                        class="w-full border-0 border-b border-gray-300 bg-transparent py-2 px-0 focus:ring-0 focus:border-primary transition-colors text-sm placeholder:text-gray-400"
                                        placeholder="Your answer"
                                        rows="3"
                                    ></textarea>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>

                    <div class="flex items-center justify-between py-4">

                        <button type="reset"
                                class="text-primary hover:bg-primary/5 px-6 py-2 rounded-google text-sm font-medium transition-colors">
                            Clear form
                        </button>

                        <div class="flex items-center gap-3">
                            <button
                                type="submit"
                                class="bg-[#eb652c] hover:bg-[#d85a24] text-white px-8 py-2 rounded-google text-sm font-medium shadow-sm transition-all">
                                Submit Evaluation
                            </button>
                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </main>

    </div>

</form>

<?php endif; ?>

</body>

</html>