<?php
/**
 * Run validation tests: open http://localhost/HRMS/test_validation.php
 * Disabled in production (APP_ENV=production).
 */
require_once __DIR__ . "/config/load_env.php";
require_once __DIR__ . "/config/app.php";

if (defined('APP_ENV') && APP_ENV === 'production') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . "/config/validation.php";

header('Content-Type: text/plain; charset=utf-8');

$pass = 0;
$fail = 0;

function test(string $name, $expected, $actual): void {
    global $pass, $fail;
    $ok = $expected === $actual;
    echo ($ok ? 'PASS' : 'FAIL') . " $name\n";
    if (!$ok) {
        echo "  Expected: " . var_export($expected, true) . "\n";
        echo "  Got:      " . var_export($actual, true) . "\n";
        $fail++;
    } else {
        $pass++;
    }
}

echo "=== Input Validation Tests ===\n\n";

// validate_whitelist
test('whitelist: valid value', 'Normal', validate_whitelist('Normal', ['Normal', 'Urgent'], ''));
test('whitelist: invalid value', '', validate_whitelist('Critical', ['Normal', 'Urgent'], ''));
test('whitelist: default', 'default', validate_whitelist('x', ['a'], 'default'));

// validate_string
test('string: length limit', 'abc', validate_string('abcdef', 3));
test('string: trim', 'x', validate_string('  x  ', 10));
test('string: empty', '', validate_string('', 10));

// validate_email
test('email: valid', 'a@b.co', validate_email('a@b.co'));
test('email: invalid', '', validate_email('not-an-email'));

// validate_id
test('id: valid', 5, validate_id(5));
test('id: zero', 0, validate_id(0));
test('id: negative', 0, validate_id(-1));
test('id: string', 42, validate_id('42'));

// validate_date
test('date: valid', '2025-03-11', validate_date('2025-03-11'));
test('date: invalid', '', validate_date('03/11/2025'));
test('date: empty', '', validate_date(''));

// validate_search (@ allowed for email search)
test('search: sanitize', 'test@123', validate_search('test!@#123'));
test('search: length', str_repeat('a', 200), validate_search(str_repeat('a', 300), 200));

echo "\n=== Results: $pass passed, $fail failed ===\n";
