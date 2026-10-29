<?php
/**
 * Security audit logging - OWASP Logging Cheat Sheet
 *
 * Logs: login attempts, failed auth, admin actions, permission violations,
 * suspicious API calls. Log file should be outside web root in production
 * or protected via .htaccess (logs/ directory).
 */

/**
 * Sanitize string for log output (prevent log injection - newlines, control chars)
 */
function _sec_log_sanitize(string $s, int $maxLen = 500): string {
    $s = preg_replace('/[\x00-\x1f\x7f]/', '', $s);
    $s = str_replace(["\n", "\r"], ' ', $s);
    return substr($s, 0, $maxLen);
}

/**
 * Get client IP (consider X-Forwarded-For only from trusted proxies)
 */
function _sec_log_ip(): string {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $h) {
        $v = $_SERVER[$h] ?? '';
        if ($v !== '' && is_string($v)) {
            $ip = trim(explode(',', $v)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Write a security log entry.
 *
 * @param string $event     Event type: login_success, login_failure, login_lockout,
 *                          csrf_failure, logout, permission_denied, admin_action, api_call
 * @param array  $details   Key-value details (keys/values sanitized)
 */
function security_log(string $event, array $details = []): void {
    static $logPath = null;
    if ($logPath === null) {
        $base = dirname(__DIR__);
        $dir = $base . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $logPath = $dir . DIRECTORY_SEPARATOR . 'security.log';
    }

    $allowedEvents = [
        'login_success', 'login_failure', 'login_lockout',
        'csrf_failure', 'logout', 'permission_denied',
        'admin_action', 'api_call', 'suspicious_request'
    ];
    if (!in_array($event, $allowedEvents, true)) {
        $event = 'suspicious_request';
    }

    $ts = date('Y-m-d\TH:i:s\Z');
    $ip = _sec_log_ip();
    $ua = _sec_log_sanitize($_SERVER['HTTP_USER_AGENT'] ?? '', 200);
    $uri = _sec_log_sanitize($_SERVER['REQUEST_URI'] ?? '', 300);
    $uid = (isset($_SESSION) && !empty($_SESSION['user_id'])) ? (int)$_SESSION['user_id'] : null;
    $userStr = $uid !== null ? (string)$uid : '-';

    $payload = [
        'ts' => $ts,
        'event' => $event,
        'ip' => $ip,
        'user_id' => $userStr,
        'uri' => $uri,
        'ua' => $ua,
    ];

    foreach ($details as $k => $v) {
        if (is_scalar($v)) {
            $payload[_sec_log_sanitize((string)$k, 50)] = _sec_log_sanitize((string)$v, 200);
        }
    }

    $line = json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";

    if (is_writable($logPath) || (is_dir(dirname($logPath)) && is_writable(dirname($logPath)))) {
        @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }
}
