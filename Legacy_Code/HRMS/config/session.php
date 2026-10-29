<?php
/**
 * Secure session configuration - OWASP Session Management
 * Include before any output; starts session with secure defaults.
 *
 * - HttpOnly: prevents XSS access to session cookie
 * - Secure: cookie sent only over HTTPS (when available)
 * - SameSite=Strict: mitigates CSRF
 * - use_strict_mode: rejects uninitialized session IDs (fixation)
 * - Inactivity timeout: 30 minutes
 * - Periodic regeneration: reduces fixation risk on long sessions
 */

require_once __DIR__ . '/security_headers.php';

define('SESSION_TIMEOUT', 1800);
define('SESSION_REGENERATE_INTERVAL', 900);

if (session_status() === PHP_SESSION_NONE) {

    /* Cookie security */
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);

    /* Session lifetime (server-side GC) */
    ini_set('session.gc_maxlifetime', 1800);
    ini_set('session.cookie_lifetime', 0);

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }

    session_start();

    if (isset($_SESSION['user_id'])) {
        $now = time();

        if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            if (php_sapi_name() !== 'cli') {
                header('Location: Login_page.php?timeout=1');
                exit;
            }
        }

        $_SESSION['last_activity'] = $now;

        $lastRegen = $_SESSION['_last_regenerate'] ?? 0;
        if (($now - $lastRegen) >= SESSION_REGENERATE_INTERVAL) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerate'] = $now;
        }
    }
}
