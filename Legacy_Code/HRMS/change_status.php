<?php
/**
 * Legacy change employee status - requires HR admin auth
 */
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = trim((string)($_GET['status'] ?? ''));
$allowed = ['Active', 'Onboarding', 'Leave', 'Terminated'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    header("Location: Employee_directory.php");
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE employees SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    require_once __DIR__ . "/config/security_log.php";
    security_log('admin_action', ['action' => 'change_status_legacy', 'target_employee_id' => (string)$id, 'new_status' => $status]);
} catch (PDOException $e) {
    error_log("change_status: " . $e->getMessage());
}

header("Location: Employee_directory.php");
exit;
