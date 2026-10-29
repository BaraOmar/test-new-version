<?php
/**
 * Database configuration - credentials from environment only.
 *
 * Set via:
 *   - .env file (copy .env.example to .env, add .env to .gitignore)
 *   - Apache: SetEnv DB_HOST localhost (in httpd.conf or .htaccess)
 *   - PHP-FPM: env[DB_PASS]=secret (in pool config)
 *   - Server: export DB_PASS=secret
 *
 * Required: DB_HOST, DB_NAME, DB_USER, DB_PASS
 */
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/app.php';

$host     = getenv('DB_HOST') ?: 'localhost';
$dbname   = getenv('DB_NAME') ?: 'HRMS';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS');
$dbSocket = getenv('DB_SOCKET') ?: '';

if ($password === false || $password === '') {
    error_log('HRMS: DB_PASS not set. Configure DB_HOST, DB_NAME, DB_USER, DB_PASS in .env or environment.');
    http_response_code(500);
    die('Database not configured. Contact administrator.');
}

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

    if ($dbSocket !== '') {
        $dsn = "mysql:unix_socket=$dbSocket;dbname=$dbname;charset=utf8mb4";
    } elseif ($host === 'localhost') {
        foreach ([
            '/opt/lampp/var/mysql/mysql.sock',
            '/var/run/mysqld/mysqld.sock',
            '/tmp/mysql.sock',
        ] as $socketPath) {
            if (is_readable($socketPath)) {
                $dsn = "mysql:unix_socket=$socketPath;dbname=$dbname;charset=utf8mb4";
                break;
            }
        }
    }

    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log("DB connection failed: " . $e->getMessage());
    http_response_code(500);
    die("Service temporarily unavailable. Please try again later.");
}
