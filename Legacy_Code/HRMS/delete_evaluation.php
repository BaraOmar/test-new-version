<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: evaluation_list.php");
    exit;
}

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    require_once __DIR__ . "/config/security_log.php";
    security_log('csrf_failure', ['context' => 'delete_evaluation']);
    http_response_code(403);
    die('Invalid request.');
}

$evaluation_id = (int)$_POST['id'];
if ($evaluation_id <= 0) {
    header("Location: evaluation_list.php");
    exit;
}

try {
    // Delete evaluation_answers (references evaluation_responses and evaluation_id)
    $pdo->prepare("DELETE FROM evaluation_answers WHERE evaluation_id = ?")->execute([$evaluation_id]);

    // Delete evaluation_responses
    $pdo->prepare("DELETE FROM evaluation_responses WHERE evaluation_id = ?")->execute([$evaluation_id]);

    // Delete evaluation_question_options (via questions that belong to this evaluation)
    $pdo->prepare("
        DELETE eqo FROM evaluation_question_options eqo
        INNER JOIN evaluation_questions eq ON eqo.question_id = eq.id
        WHERE eq.evaluation_id = ?
    ")->execute([$evaluation_id]);

    // Delete evaluation_questions
    $pdo->prepare("DELETE FROM evaluation_questions WHERE evaluation_id = ?")->execute([$evaluation_id]);

    // Delete evaluation_employee_targets (if table exists)
    try {
        $pdo->prepare("DELETE FROM evaluation_employee_targets WHERE evaluation_id = ?")->execute([$evaluation_id]);
    } catch (PDOException $e) { /* table may not exist */ }

    // Delete evaluation_targets
    $pdo->prepare("DELETE FROM evaluation_targets WHERE evaluation_id = ?")->execute([$evaluation_id]);

    // Delete evaluation itself
    $pdo->prepare("DELETE FROM evaluations WHERE id = ?")->execute([$evaluation_id]);

    require_once __DIR__ . "/config/security_log.php";
    security_log('admin_action', ['action' => 'evaluation_delete', 'evaluation_id' => (string)$evaluation_id]);

} catch (PDOException $e) {
    error_log("delete_evaluation: " . $e->getMessage());
    $_SESSION['flash_error'] = "Delete failed. Please try again.";
}

header("Location: evaluation_list.php");
exit;
