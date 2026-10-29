<?php
/**
 * Load environment variables from .env file (project root).
 * Only loads if file exists. Does not overwrite existing env vars.
 * Add .env to .gitignore - never commit credentials.
 */
$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (!is_file($envFile) || !is_readable($envFile)) {
    return;
}
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false) {
    return;
}
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') {
        continue;
    }
    if (strpos($line, '=') === false) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
        $value = substr($value, 1, -1);
    }
    if ($key !== '' && getenv($key) === false) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}
