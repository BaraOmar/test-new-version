<?php
/**
 * Legacy set manager - requires HR admin auth
 */
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: Employee_directory.php");
    exit;
}

try {
    $pdo->prepare("UPDATE employees SET is_manager = 1 WHERE id = ?")->execute([$id]);
    require_once __DIR__ . "/config/security_log.php";
    security_log('admin_action', ['action' => 'set_manager_legacy', 'target_employee_id' => (string)$id]);
} catch (PDOException $e) {
    error_log("set_manager: " . $e->getMessage());
}

header("Location: Employee_directory.php");
exit;
