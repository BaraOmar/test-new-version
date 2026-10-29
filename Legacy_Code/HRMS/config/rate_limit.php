<?php
/**
 * Simple file-based rate limiting. Use for unauthenticated endpoints.
 *
 * @param string $key     Unique key (e.g. 'check_hr', 'login')
 * @param int    $limit   Max requests per window
 * @param int    $window  Window in seconds
 * @return bool  True if allowed, false if rate limited
 */
function rate_limit_check(string $key, int $limit = 20, int $window = 60): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $key) . '_' . md5($ip);

    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    $file = $dir . DIRECTORY_SEPARATOR . 'ratelimit_' . $id . '.json';

    $now = time();
    $cutoff = $now - $window;

    $data = ['ts' => []];
    if (is_file($file) && is_readable($file)) {
        $raw = @file_get_contents($file);
        if ($raw !== false) {
            $dec = json_decode($raw, true);
            if (is_array($dec) && isset($dec['ts']) && is_array($dec['ts'])) {
                $data['ts'] = array_filter($dec['ts'], fn($t) => $t > $cutoff);
            }
        }
    }

    if (count($data['ts']) >= $limit) {
        return false;
    }

    $data['ts'][] = $now;
    $data['ts'] = array_slice($data['ts'], -$limit);
    @file_put_contents($file, json_encode($data), LOCK_EX);

    return true;
}
