<?php
require_once __DIR__ . "/config/session.php";
require_once "config/db.php";

$survey_id = intval($_GET['id'] ?? 0);

// Prefer explicit employee_id, fall back to user_id
$employee_id = null;
if (isset($_SESSION['employee_id'])) {
    $employee_id = (int)$_SESSION['employee_id'];
} elseif (isset($_SESSION['user_id'])) {
    $employee_id = (int)$_SESSION['user_id'];
}

if (!$survey_id) {
    die("Survey not found.");
}

if (!$employee_id) {
    die("Employee not authenticated.");
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id=?");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$survey) {
    die("Survey not found.");
}

$stmt = $pdo->prepare("
SELECT *
FROM survey_questions
WHERE survey_id=?
ORDER BY position ASC
");
$stmt->execute([$survey_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if this employee already submitted this survey
$alreadyStmt = $pdo->prepare("
    SELECT id FROM survey_responses
    WHERE survey_id = ? AND employee_id = ?
    LIMIT 1
");
$alreadyStmt->execute([$survey_id, $employee_id]);
$hasSubmitted = (bool)$alreadyStmt->fetchColumn();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Invalid request');
    }
    if ($hasSubmitted) {
        die("You have already answered this survey.");
    }

    $pdo->beginTransaction();

    try {
        // Block again inside transaction
        $checkStmt = $pdo->prepare("
            SELECT id FROM survey_responses
            WHERE survey_id = ? AND employee_id = ?
            LIMIT 1
        ");
        $checkStmt->execute([$survey_id, $employee_id]);
        if ($checkStmt->fetch()) {
            $pdo->rollBack();
            die("You have already answered this survey.");
        }

        $stmt = $pdo->prepare("
INSERT INTO survey_responses
(survey_id,employee_id)
VALUES (?,?)
");
        $stmt->execute([$survey_id, $employee_id]);

        $response_id = $pdo->lastInsertId();

        foreach ($questions as $q) {

            $qid = $q['id'];

            if (!isset($_POST["q_$qid"])) continue;

            $value = $_POST["q_$qid"];

            $answer_text = null;
            $answer_number = null;

            if (is_numeric($value)) {
                $answer_number = $value;
            } else {
                $answer_text = $value;
            }
            $stmt = $pdo->prepare("
INSERT INTO survey_answers
(response_id,survey_id,question_id,employee_id,answer_text,answer_number)
VALUES (?,?,?,?,?,?)
");
            $stmt->execute([
                $response_id,
                $survey_id,
                $qid,
                $employee_id,
                $answer_text,
                $answer_number
            ]);
        }

        $pdo->commit();

        header("Location: survey_submitted.php");
        exit;

    } catch (Exception $e) {

        $pdo->rollBack();
        echo "Error saving answers: " . $e->getMessage();

    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>

<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&amp;display=swap" rel="stylesheet"/>

<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>

<script id="tailwind-config">
tailwind.config={
darkMode:"class",
theme:{
extend:{
colors:{
"primary":"#642754",
"accent":"#eb652c",
"bg-lavender":"#f8f4f8",
"card-border":"#e8e0e8"
},
fontFamily:{
"sans":["Roboto","sans-serif"]
},
borderRadius:{
"google":"8px"
}
}
}
}
</script>

<title>Survey Response Interface</title>

<style type="text/tailwindcss">
@layer base{
body{
@apply bg-bg-lavender font-sans text-[#202124];
}
}

.google-card{
@apply bg-white rounded-google shadow-[0_2px_1px_-1px_rgba(0,0,0,0.2),0_1px_1px_0_rgba(0,0,0,0.14),0_1px_3px_0_rgba(0,0,0,0.12)] border border-card-border overflow-hidden;
}

.header-top-border{
@apply border-t-[10px] border-[#eb652c];
}
</style>
</head>

<body class="min-h-screen bg-[#f8f4f8]">

<?php if ($hasSubmitted && $_SERVER["REQUEST_METHOD"] !== "POST"): ?>
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="google-card p-8 max-w-md w-full text-center">
        <span class="material-symbols-outlined text-5xl text-green-500 mb-4">check_circle</span>
        <h1 class="text-xl font-medium mb-2">You have already submitted this survey.</h1>
        <p class="text-sm text-gray-600 mb-6">
            Thank you for completing "<?= htmlspecialchars($survey['title']) ?>".
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
<span class="material-symbols-outlined text-2xl">assignment</span>
</div>

<h2 class="text-white text-xl font-normal">CulturePulse</h2>
</div>

</header>

<main class="flex flex-1 justify-center py-6 px-4">

<div class="flex flex-col max-w-[770px] w-full gap-3">

<div class="google-card header-top-border p-6 pt-5">

<h1 class="text-3xl font-normal mb-2">
<?= htmlspecialchars($survey['title']) ?>
</h1>

<p class="text-sm text-[#202124] mb-4">
<?= htmlspecialchars($survey['description']) ?>
</p>

</div>


<?php foreach($questions as $index=>$q): ?>

<div class="google-card p-6">

<div class="flex items-start gap-1 mb-6">

<span class="text-base font-medium">
<?= ($index+1) ?>. <?= htmlspecialchars($q['question_text']) ?>
</span>

<?php if($q['is_required']): ?>
<span class="text-red-500">*</span>
<?php endif; ?>

</div>


<?php if($q['question_type']=="rating"): ?>

<div class="flex flex-col gap-4">

<div class="flex items-center gap-2">

<?php for($i=$q['scale_min'];$i<=$q['scale_max'];$i++): ?>

<label>

<input type="radio"
name="q_<?= $q['id'] ?>"
value="<?= $i ?>"
class="hidden peer">

<span class="material-symbols-outlined text-3xl text-gray-300 peer-checked:text-primary cursor-pointer">
star
</span>

</label>

<?php endfor; ?>

<span class="ml-4 text-xs text-gray-500 uppercase tracking-wide">
Scale <?= $q['scale_min'] ?>-<?= $q['scale_max'] ?>
</span>

</div>

</div>

<?php endif; ?>


<?php if($q['question_type']=="multiple_choice"): ?>

<?php
$stmt=$pdo->prepare("SELECT * FROM survey_question_options WHERE question_id=?");
$stmt->execute([$q['id']]);
$options=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-4">

<?php foreach($options as $op): ?>

<label class="flex items-center gap-3 group cursor-pointer">

<input class="w-5 h-5 text-primary border-gray-300 focus:ring-primary focus:ring-offset-0"
name="q_<?= $q['id'] ?>"
value="<?= htmlspecialchars($op['option_text']) ?>"
type="radio"/>

<span class="text-sm text-[#202124]">
<?= htmlspecialchars($op['option_text']) ?>
</span>

</label>

<?php endforeach; ?>

</div>

<?php endif; ?>


<?php if($q['question_type']=="short_text"): ?>

<div class="relative group">

<input
name="q_<?= $q['id'] ?>"
class="w-full border-0 border-b border-gray-300 bg-transparent py-2 px-0 focus:ring-0 focus:border-primary transition-colors text-sm placeholder:text-gray-400"
placeholder="Your answer">

</div>

<?php endif; ?>


<?php if($q['question_type']=="long_text"): ?>

<div class="relative group">

<textarea
name="q_<?= $q['id'] ?>"
class="w-full border-0 border-b border-gray-300 bg-transparent py-2 px-0 focus:ring-0 focus:border-primary transition-colors text-sm placeholder:text-gray-400"
placeholder="Your answer"
rows="3"></textarea>

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
Submit Response
</button>

</div>

</div>

</div>

</main>

</div>

</form>

<?php endif; ?>

</body>
</html>