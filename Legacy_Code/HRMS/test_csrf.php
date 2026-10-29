<?php
/**
 * CSRF test - POST to save_survey.php without valid token. Disabled in production.
 */
require_once __DIR__ . "/config/load_env.php";
require_once __DIR__ . "/config/app.php";

if (defined('APP_ENV') && APP_ENV === 'production') {
    http_response_code(404);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>CSRF Test</title></head>
<body>
<h1>POST to save_survey.php without valid CSRF token</h1>
<form method="POST" action="save_survey.php">
  <input type="hidden" name="title" value="Test">
  <input type="hidden" name="description" value="Test">
  <!-- No csrf_token field -->
  <button type="submit">Submit (should get 403)</button>
</form>
</body>
</html>
