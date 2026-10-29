<?php
/**
 * Application environment - control error display and debug behavior.
 * Set APP_ENV=production in production. Include before other config.
 */
$appEnv = getenv('APP_ENV') ?: 'development';
define('APP_ENV', $appEnv);

if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}
