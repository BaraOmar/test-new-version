# HRMS Security Audit Report

**Date:** March 2025  
**Scope:** Full PHP application security review  
**Standards:** OWASP Top 10, OWASP Secure Coding Practices

---

## Executive Summary

The audit identified **Critical** and **High** severity vulnerabilities across authentication, authorization, CSRF, error handling, and credential management. Immediate remediation is recommended for unauthenticated admin endpoints and hardcoded secrets.

---

## 1. SQL Injection

| Severity | Status |
|----------|--------|
| **Low** | Most queries use prepared statements correctly. |

**Findings:** The application uses PDO prepared statements for most database operations. `Employee_directory.php` uses `SHOW COLUMNS FROM employees` with a hardcoded table name, so no injection risk.

**Recommendation:** Continue using parameterized queries for all user input. Never concatenate user input into SQL.

---

## 2. Cross-Site Scripting (XSS)

| File | Line | Severity | Issue |
|------|------|----------|-------|
| survey_form.php | 119 | Medium | `echo "Error: " . $e->getMessage()` – raw exception output |
| delete_survey.php | 44 | Medium | `die("Delete failed: " . $e->getMessage())` – exposes internals |
| delete_evaluation.php | 45 | Medium | Exception message in session flash – potential XSS if rendered unescaped |

**Remediation:** Always use `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` for output. Log errors server-side; show generic messages to users.

---

## 3. Cross-Site Request Forgery (CSRF)

| File | Severity | Issue |
|------|----------|-------|
| Login_page.php | Medium | Login form has no CSRF token (login CSRF) |
| employee_request_change.php | High | POST form has no CSRF token |
| HR_change_request.php | High | Approve/Reject actions without CSRF |
| LMS_upload.php | High | Upload and delete without CSRF |
| On_board.php | High | Create/update/delete employee without CSRF |
| save_survey.php | High | No CSRF; anyone can create surveys |
| survey_form.php | High | Survey submission without CSRF |
| evaluation_response.php | High | Evaluation submission without CSRF |
| survey_builder_interface.php | High | Survey creation without CSRF |
| evaluation_bulder_interface.php | High | Evaluation creation without CSRF |

**Reference implementation:** `Employee_directory.php` uses CSRF correctly.

```php
// At page start
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// In form
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
// On POST
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Invalid request');
}
```

---

## 4. Authentication Flaws

| File | Severity | Issue |
|------|----------|-------|
| Login_page.php | Medium | No `session_regenerate_id(true)` after login – session fixation |
| check_hr.php | **High** | No auth; unauthenticated user enumeration (reveals if email is HR) |

**Remediation (Login_page.php):** After successful `password_verify`, call `session_regenerate_id(true)` before setting session vars.

**Remediation (check_hr.php):** Require authenticated session, or remove/rate-limit this endpoint.

---

## 5. Authorization Flaws

| File | Severity | Issue |
|------|----------|-------|
| delete_employee.php | **Critical** | No `session_start()` or auth – anyone can delete employees via `?id=N` |
| change_status.php | **Critical** | No auth – anyone can change employee status |
| set_manager.php | **Critical** | No auth – anyone can promote users to manager |
| save_survey.php | **Critical** | No auth – anyone can create surveys via POST |
| delete_survey.php | High | No auth – anyone can delete surveys |
| delete_evaluation.php | High | No `is_hr_admin` check – any logged-in user can delete |
| HR_Dashboard.php | High | No `is_hr_admin` check |
| LMS_upload.php | High | No HR admin check |
| On_board.php | High | No `is_hr_admin` check |
| Employee_directory.php | High | No `is_hr_admin` check |

**Remediation for admin-only pages:**

```php
session_start();
require_once __DIR__ . "/config/db.php";
if (empty($_SESSION['user_id']) || empty($_SESSION['is_hr_admin'])) {
    header("Location: Login_page.php");
    exit;
}
```

---

## 6. Insecure File Uploads

| File | Issue |
|------|-------|
| LMS_upload.php | Extension whitelist and size limit present; no MIME/content validation. `file_path` from DB used in links – potential path traversal if DB compromised. |

**Remediation:** Validate MIME type; store files outside web root or serve via a script that validates path and authorization.

---

## 7. Session Handling

| Issue | Severity |
|-------|----------|
| No `session_regenerate_id(true)` after login | Medium |
| No central `session.cookie_httponly`, `session.cookie_secure`, `session.use_strict_mode` | Medium |

**Remediation:** Add `config/session.php` or configure before `session_start()`:

```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);  // if using HTTPS
ini_set('session.use_strict_mode', 1);
```

---

## 8. Hardcoded Secrets

| File | Line | Severity | Issue |
|------|------|----------|-------|
| config/db.php | 4–5 | **Critical** | `$username = "root";` and `$password = "root123";` in source |

**Remediation:** Use environment variables:

```php
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'HRMS';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
```

---

## 9. Unsafe Error Handling

| File | Issue |
|------|-------|
| config/db.php | `die("Database connection failed: " . $e->getMessage())` – exposes DB details |
| survey_form.php | Echoes exception message to user |
| delete_survey.php | `die("Delete failed: " . $e->getMessage())` |
| delete_evaluation.php | Exception message in session |

**Remediation:** Log full errors with `error_log()`. Return generic messages to users (e.g., "Service temporarily unavailable").

---

## 10. Open Redirect

| File | Issue |
|------|-------|
| Employee_directory.php | `$redirectBack = $_POST['redirect_back']` used in `header("Location: ...")` – attacker-controlled redirect |

**Remediation:** Whitelist allowed redirect targets or use basename and validate.

---

## Priority Remediation Order

1. ~~**Critical:** Add authentication to `delete_employee.php`, `change_status.php`, `set_manager.php`, `save_survey.php`~~ ✅
2. ~~**Critical:** Move DB credentials to environment variables~~ ✅
3. ~~**High:** Add CSRF to `employee_request_change.php`~~ ✅
4. **High:** Add CSRF to remaining forms (LMS_upload, On_board, HR_change_request, survey/eval builders)
5. **High:** Add `is_hr_admin` checks to HR_Dashboard, LMS_upload, On_board, etc.
6. ~~**High:** Fix info disclosure in config/db.php, delete_survey, delete_evaluation~~ ✅
7. ~~**Medium:** Session hardening (config/session.php) and `session_regenerate_id` after login~~ ✅
8. ~~**Medium:** Open redirect fix in Employee_directory.php~~ ✅

## Migration Note (DB Credentials)

For local XAMPP with password `root123`, add to your Apache vhost or .htaccess:

```
SetEnv DB_PASS root123
```

Or use PHP's `putenv()` in a bootstrap file. Do not commit credentials to version control.
