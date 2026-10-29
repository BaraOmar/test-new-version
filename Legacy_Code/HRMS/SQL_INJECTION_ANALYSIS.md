# SQL Injection Analysis

## Summary

The HRMS codebase **uses prepared statements correctly** for all user-supplied input. Dynamic values are passed as parameters (`?` placeholders), not concatenated into SQL strings.

## Findings

### ✅ Safe – Prepared Statements Used

| File | Query Type | User Input | Protection |
|------|------------|------------|------------|
| Login_page.php | SELECT | email, password | `prepare()` + `execute([$email])` |
| check_hr.php | SELECT | email | `prepare()` + `execute([$email])` |
| employee_request_change.php | INSERT | urgency, old_value, new_value | `prepare()` + `execute()` |
| HR_change_request.php | SELECT/UPDATE | search, status, urgency, request_id | `prepare()` + `execute($params)` |
| survey_list.php | SELECT | search, dates, filters | `prepare()` + `execute($params)` |
| Employee_directory.php | SELECT/UPDATE/DELETE | search, dept, pos, employeeId, status | `prepare()` + `execute($params)` |
| survey_form.php | INSERT | answers | `prepare()` + `execute()` with bound params |
| evaluation_response.php | INSERT | answers | `prepare()` + `execute()` |
| LMS_upload.php | SELECT/INSERT/DELETE | filters, title, description | `prepare()` + `execute($params)` |
| send_survey.php, send_evaluation.php | SELECT | employeeId, surveyId | `prepare()` + `execute()` |
| All delete_*.php | DELETE | id | `prepare()` + `execute([$id])` |

### ✅ Safe – No User Input in Query

- `$pdo->query("SELECT ...")` with static SQL (e.g. departments, counts)
- Column/table names from `SHOW COLUMNS` / `INFORMATION_SCHEMA` called with hardcoded values

### ⚠️ Identifier Usage (Defense in Depth)

**Employee_directory.php** – `columnExists()`:

```php
$stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
```

- **Current use:** Only called with `'employees'` and `'status'`/`'is_manager'` (hardcoded).
- **Risk:** If `$table` or `$column` were ever supplied by user input, they could be exploited because identifiers cannot be parameterized in MySQL.
- **Mitigation:** Whitelist table and column names (allowed pattern: `[a-zA-Z0-9_]+`).

### Why Prepared Statements Prevent SQL Injection

1. The SQL structure (keywords, table/column names) is fixed at `prepare()` time.
2. User values are sent separately and escaped by the driver.
3. The database treats parameters as data, not executable SQL.

Example of vulnerable code (do **not** use):

```php
$sql = "SELECT * FROM users WHERE id = " . $_GET['id'];  // VULNERABLE
$pdo->query($sql);
```

Secure equivalent:

```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);
```

## Recommendations

1. Keep using prepared statements for all dynamic values.
2. Add identifier validation in `columnExists()` for defense in depth.
3. Keep validation (e.g. `validate_id`, `validate_search`) as an extra layer before queries.
