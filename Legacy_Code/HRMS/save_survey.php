<?php
/**
 * Save survey - requires HR admin auth and CSRF
 */
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/validation.php";

if (empty($_SESSION['user_id']) || empty($_SESSION['is_hr_admin'])) {
    header("Location: Login_page.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: survey_list.php");
    exit;
}

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Invalid request');
}

$title = validate_string($_POST['title'] ?? '', 255);
$description = validate_string($_POST['description'] ?? '', 2000);
$target_type = isset($_POST['departments']) ? 'department' : 'organization';

if ($title === '') {
    header("Location: survey_builder.php");
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO surveys (title, description, target_type, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$title, $description, $target_type]);
    $survey_id = (int)$pdo->lastInsertId();

    if (isset($_POST['departments']) && is_array($_POST['departments'])) {
        $ins = $pdo->prepare("INSERT INTO survey_targets (survey_id, department_id) VALUES (?, ?)");
        foreach ($_POST['departments'] as $dept) {
            $deptId = validate_id($dept);
            if ($deptId > 0) {
                $ins->execute([$survey_id, $deptId]);
            }
        }
    }

    header("Location: survey_builder.php");
    exit;
} catch (PDOException $e) {
    error_log("save_survey: " . $e->getMessage());
    header("Location: survey_list.php");
    exit;
}
