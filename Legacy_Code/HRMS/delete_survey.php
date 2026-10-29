<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: survey_list.php");
    exit;
}

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    require_once __DIR__ . "/config/security_log.php";
    security_log('csrf_failure', ['context' => 'delete_survey']);
    http_response_code(403);
    die('Invalid request.');
}

$survey_id = (int)$_POST['id'];
if ($survey_id <= 0) {
    header("Location: survey_list.php");
    exit;
}

try {

    // delete answers first
    $stmt = $pdo->prepare("
        DELETE sa
        FROM survey_answers sa
        JOIN survey_questions sq ON sa.question_id = sq.id
        WHERE sq.survey_id = ?
    ");
    $stmt->execute([$survey_id]);

    // delete questions
    $stmt = $pdo->prepare("
        DELETE FROM survey_questions
        WHERE survey_id = ?
    ");
    $stmt->execute([$survey_id]);

    // delete responses
    $stmt = $pdo->prepare("
        DELETE FROM survey_responses
        WHERE survey_id = ?
    ");
    $stmt->execute([$survey_id]);

    // delete survey itself
    $stmt = $pdo->prepare("
        DELETE FROM surveys
        WHERE id = ?
    ");
    $stmt->execute([$survey_id]);

    require_once __DIR__ . "/config/security_log.php";
    security_log('admin_action', ['action' => 'survey_delete', 'survey_id' => (string)$survey_id]);

} catch (PDOException $e) {
    error_log("delete_survey: " . $e->getMessage());
    $_SESSION['flash_error'] = "Delete failed. Please try again.";
}

header("Location: survey_list.php");
exit;