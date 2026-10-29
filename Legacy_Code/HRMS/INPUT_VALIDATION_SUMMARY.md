# Input Validation Summary

All user inputs have been validated using whitelisting and type/length checks per OWASP guidelines.

## Validation Helper: `config/validation.php`

| Function | Purpose |
|----------|---------|
| `validate_whitelist($value, $allowed, $default)` | Return value only if in allowed list |
| `validate_string($value, $maxLen, $strict)` | Trim, limit length, optional charset |
| `validate_email($value)` | `filter_var(FILTER_VALIDATE_EMAIL)` |
| `validate_id($value, $max)` | Positive integer, bounded |
| `validate_date($value)` | Y-m-d format |
| `validate_search($value, $maxLen)` | Safe search string, alphanumeric + basic punct |

## Files Updated

| File | Inputs Validated |
|------|------------------|
| **HR_change_request.php** | `search`, `status`, `urgency`, `request_id`, `action` – whitelist + ID |
| **survey_list.php** | `search`, `date_from`, `date_to`, `anonymous_filter`, `department_filter` |
| **employee_request_change.php** | `urgency`, `current_text`, `new_text` – length 5000 |
| **Login_page.php** | `email` (validate_email), `password` (length ≤255) |
| **LMS_upload.php** | `type`, `search`, `sort` (GET); `title` (255), `description` (2000) |
| **save_survey.php** | `title` (255), `description` (2000), `departments[]` (validate_id) |
| **Employee_directory.php** | `search`, `dept`, `pos` |
| **evaluation_list.php** | `search`, `view` |
| **LMS_course_review.php** | `id`, `type`, `search`, `sort` |
| **survey_response.php** | `id`, `search` |
| **survey_and_evaluation_display.php** | `search`, `type`, `view` |

## Whitelists Used

- **Status:** Pending, Approved, Rejected, All
- **Urgency:** Normal, Urgent, All
- **Action:** approve, reject
- **View:** list, grid
- **Type filter:** video, pdf, pptx, docx, image, all, survey, evaluation
- **Sort:** newest, oldest, az
- **Anonymous:** anonymous, named

## Length Limits

- Search strings: 200 chars (configurable)
- Title: 255 chars
- Description: 2000 chars
- Change request text: 5000 chars
- Password: 255 chars
