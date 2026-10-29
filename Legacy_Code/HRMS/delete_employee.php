<?php
/**
 * Legacy delete employee - requires HR admin auth
 * Prefer Employee_directory.php POST handler which has CSRF
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
    $pdo->prepare("UPDATE employees SET manager_id = NULL WHERE manager_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM employees WHERE id = ?")->execute([$id]);
    require_once __DIR__ . "/config/security_log.php";
    security_log('admin_action', ['action' => 'employee_delete_legacy', 'target_employee_id' => (string)$id]);
} catch (PDOException $e) {
    error_log("delete_employee: " . $e->getMessage());
}

header("Location: Employee_directory.php");
exit;
