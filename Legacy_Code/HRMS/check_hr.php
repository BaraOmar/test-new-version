<?php
/**
 * Check if email belongs to HR admin (used on login page for UX).
 * SECURITY: Unauthenticated - rate limited to mitigate enumeration.
 */
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/security_log.php";
require_once __DIR__ . "/config/rate_limit.php";

header('Content-Type: application/json');

if (!rate_limit_check('check_hr', 30, 60)) {
    http_response_code(429);
    echo json_encode(["is_hr" => false, "error" => "Too many requests"]);
    exit;
}

$email = trim((string)($_GET['email'] ?? ''));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["is_hr" => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT is_hr_admin FROM employees WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$isHr = $user && (int)$user['is_hr_admin'] === 1;

security_log('api_call', ['endpoint' => 'check_hr', 'email_provided' => 'yes', 'is_hr' => $isHr ? '1' : '0']);

echo json_encode([
    "is_hr" => $isHr
]);
