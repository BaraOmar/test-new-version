<?php
/**
 * Security HTTP headers - OWASP recommendations
 * Include early, before any output.
 * Call applySecurityHeaders() once per request.
 */
function applySecurityHeaders(): void {
    if (php_sapi_name() === 'cli' || headers_sent()) {
        return;
    }

    /* X-Frame-Options: Prevent clickjacking - deny embedding in iframes */
    header('X-Frame-Options: SAMEORIGIN');

    /* X-Content-Type-Options: Prevent MIME sniffing (e.g. XSS via disguised scripts) */
    header('X-Content-Type-Options: nosniff');

    /* Referrer-Policy: Control referrer information sent on navigation */
    header('Referrer-Policy: strict-origin-when-cross-origin');

    /* Content-Security-Policy: Mitigate XSS and data injection
     * Allows: self, Tailwind CDN, Google Fonts, inline scripts (Tailwind config), data URIs for avatars */
    $csp = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com",
        "font-src 'self' https://fonts.gstatic.com https://fonts.googleapis.com",
        "img-src 'self' data: https:",
        "connect-src 'self'",
        "frame-ancestors 'self'",
    ];
    header('Content-Security-Policy: ' . implode('; ', $csp));

    /* Strict-Transport-Security: Force HTTPS (only when already on HTTPS) */
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

applySecurityHeaders();
