<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/security_log.php";

$uid = $_SESSION['user_id'] ?? null;
$_SESSION = [];
if ($uid !== null) {
    security_log('logout', ['user_id' => (string)$uid]);
}
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}
session_destroy();
header('Location: Login_page.php');
exit;
