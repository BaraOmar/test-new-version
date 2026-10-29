<?php
/**
 * Input validation helpers - OWASP whitelisting approach
 * Use these for all user input before storage or use in queries.
 */

/**
 * Safe HTML output - escapes for use in HTML context (prevents XSS)
 */
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Whitelist: return value only if in allowed list, else default
 */
function validate_whitelist(string $value, array $allowed, string $default = ''): string {
    $v = trim((string)$value);
    return in_array($v, $allowed, true) ? $v : $default;
}

/**
 * Safe string: trim, limit length, optionally restrict charset
 */
function validate_string(string $value, int $maxLen = 10000, bool $strict = false): string {
    $v = trim((string)$value);
    if ($maxLen > 0 && mb_strlen($v) > $maxLen) {
        $v = mb_substr($v, 0, $maxLen);
    }
    if ($strict && $v !== '' && !preg_match('/^[\p{L}\p{N}\p{P}\p{S}\s\-_.,;:!?\'"()\n\r\t]+$/u', $v)) {
        return '';
    }
    return $v;
}

/**
 * Validate email
 */
function validate_email(string $value): string {
    $v = trim((string)$value);
    return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : '';
}

/**
 * Validate positive integer ID
 */
function validate_id($value, int $max = 2147483647): int {
    $v = (int)$value;
    return ($v > 0 && $v <= $max) ? $v : 0;
}

/**
 * Validate date in Y-m-d format
 */
function validate_date(string $value): string {
    $v = trim((string)$value);
    if ($v === '') return '';
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return $d && $d->format('Y-m-d') === $v ? $v : '';
}

/**
 * Validate search/filter string: alphanumeric, spaces, basic punctuation, max length
 */
function validate_search(string $value, int $maxLen = 200): string {
    $v = trim((string)$value);
    if (mb_strlen($v) > $maxLen) $v = mb_substr($v, 0, $maxLen);
    return preg_replace('/[^\p{L}\p{N}\s\-_.,@]/u', '', $v);
}
