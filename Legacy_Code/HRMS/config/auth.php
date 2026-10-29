<?php
/**
 * Authentication helpers - OWASP best practices
 * - Secure password hashing (bcrypt) and verification
 * - Password validation (complexity, length)
 * - Login rate limiting and account lockout
 * - Access control (requireLogin, requireHRAdmin)
 */
require_once __DIR__ . '/security_log.php';

/**
 * Require authenticated user. Redirects to login if not logged in.
 * Call after config/session.php.
 */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        if (function_exists('security_log')) {
            security_log('permission_denied', ['reason' => 'not_logged_in', 'required' => 'login']);
        }
        header('Location: Login_page.php');
        exit;
    }
}

/**
 * Require HR admin. Redirects to login if not logged in or not HR admin.
 * Call after config/session.php.
 */
function requireHRAdmin(): void {
    if (empty($_SESSION['user_id'])) {
        if (function_exists('security_log')) {
            security_log('permission_denied', ['reason' => 'not_logged_in', 'required' => 'hr_admin']);
        }
        header('Location: Login_page.php');
        exit;
    }
    if (empty($_SESSION['is_hr_admin'])) {
        if (function_exists('security_log')) {
            security_log('permission_denied', ['reason' => 'insufficient_privilege', 'user_id' => (string)$_SESSION['user_id'], 'required' => 'hr_admin']);
        }
        header('Location: Login_page.php');
        exit;
    }
}

/** Bcrypt cost factor (10-12 recommended; higher = stronger but slower) */
const PASSWORD_BCRYPT_COST = 12;

/**
 * Hash a password securely using bcrypt.
 * Never store plaintext passwords.
 */
function hashPassword(string $password): string {
    $options = ['cost' => PASSWORD_BCRYPT_COST];
    $hash = password_hash($password, PASSWORD_BCRYPT, $options);
    if ($hash === false) {
        throw new RuntimeException('Password hashing failed');
    }
    return $hash;
}

/**
 * Verify a password against a stored hash.
 * Uses timing-safe comparison internally.
 */
function verifyPassword(string $password, string $hash): bool {
    if ($hash === '' || strlen($hash) < 60) {
        return false;
    }
    return password_verify($password, $hash);
}

/**
 * Check if a stored hash should be rehashed (e.g. cost increased).
 * Call after successful login to upgrade weak/old hashes.
 */
function passwordNeedsRehash(string $hash): bool {
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => PASSWORD_BCRYPT_COST]);
}

/**
 * Validate password strength (OWASP: enforce complexity)
 * Returns [] if valid, or array of error messages
 */
function validatePassword(string $password, bool $required = true): array {
    $errors = [];
    if ($required && strlen($password) === 0) {
        $errors[] = 'Password is required.';
        return $errors;
    }
    if (!$required && strlen($password) === 0) {
        return [];
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }
    if (strlen($password) > 128) {
        $errors[] = 'Password must not exceed 128 characters.';
    }
    return $errors;
}

/**
 * Ensure login_attempts table exists
 */
function ensureLoginAttemptsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        identifier VARCHAR(255) PRIMARY KEY,
        attempts INT NOT NULL DEFAULT 0,
        locked_until DATETIME NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
}

/**
 * Check if account is locked (too many failed attempts)
 * Returns lockout message or null if OK to proceed
 */
function checkLoginLockout(PDO $pdo, string $identifier, int $maxAttempts = 5, int $lockoutMinutes = 15): ?string {
    ensureLoginAttemptsTable($pdo);
    $stmt = $pdo->prepare("SELECT attempts, locked_until FROM login_attempts WHERE identifier = ?");
    $stmt->execute([$identifier]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $lockedUntil = $row['locked_until'];
    if ($lockedUntil && strtotime($lockedUntil) > time()) {
        $mins = (int)ceil((strtotime($lockedUntil) - time()) / 60);
        return "Account temporarily locked. Try again in {$mins} minutes.";
    }
    if ($lockedUntil && strtotime($lockedUntil) <= time()) {
        $pdo->prepare("UPDATE login_attempts SET attempts = 0, locked_until = NULL WHERE identifier = ?")->execute([$identifier]);
    }
    return null;
}

/**
 * Record failed login attempt; lock account if threshold exceeded
 * Resets attempts when lock expires (fresh start after lockout)
 */
function recordFailedLogin(PDO $pdo, string $identifier, int $maxAttempts = 5, int $lockoutMinutes = 15): void {
    ensureLoginAttemptsTable($pdo);
    $pdo->prepare("INSERT INTO login_attempts (identifier, attempts, locked_until) VALUES (?, 1, NULL)
        ON DUPLICATE KEY UPDATE
        attempts = IF(locked_until IS NULL OR locked_until <= NOW(), attempts + 1, attempts),
        locked_until = IF(locked_until IS NOT NULL AND locked_until > NOW(), locked_until,
            IF((locked_until IS NULL OR locked_until <= NOW()) AND attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NULL))
        ")->execute([$identifier, $maxAttempts, $lockoutMinutes]);
}

/**
 * Clear failed attempts on successful login
 */
function clearLoginAttempts(PDO $pdo, string $identifier): void {
    ensureLoginAttemptsTable($pdo);
    $pdo->prepare("DELETE FROM login_attempts WHERE identifier = ?")->execute([$identifier]);
}
