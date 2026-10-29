<?php
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/validation.php";
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/security_log.php";

$loginError = "";
if (isset($_GET['timeout']) && $_GET['timeout'] === '1') {
    $loginError = "Your session expired due to inactivity. Please sign in again.";
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        security_log('csrf_failure', ['context' => 'login', 'email' => substr(trim($_POST['email'] ?? ''), 0, 3) . '***']);
        $loginError = "Invalid request. Please try again.";
    } else {

        $email = validate_email(trim($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $access_employee = isset($_POST['access_employee']);

        if ($email === '' || $password === '') {
            security_log('login_failure', ['reason' => 'empty_credentials', 'email' => $email ?: '(empty)']);
            $loginError = "Email and password required.";
        } elseif (strlen($password) > 255) {
            security_log('login_failure', ['reason' => 'invalid_input', 'email' => $email]);
            $loginError = "Invalid credentials.";
        } else {

            $lockoutMsg = checkLoginLockout($pdo, $email);
            if ($lockoutMsg !== null) {
                security_log('login_lockout', ['email' => $email]);
                $loginError = $lockoutMsg;
            } else {

                $stmt = $pdo->prepare("SELECT * FROM employees WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user || !verifyPassword($password, $user['password'])) {
                    recordFailedLogin($pdo, $email);
                    security_log('login_failure', ['reason' => 'invalid_credentials', 'email' => $email]);
                    $loginError = "Invalid credentials.";
                } else {
                    clearLoginAttempts($pdo, $email);
                    if (passwordNeedsRehash($user['password'])) {
                        $pdo->prepare("UPDATE employees SET password = ? WHERE id = ?")
                            ->execute([hashPassword($password), $user['id']]);
                    }
                    session_regenerate_id(true);
                    $_SESSION['user_id']        = (int)$user['id'];
                    $_SESSION['employee_name']  = $user['full_name'];
                    $_SESSION['is_hr_admin']    = (int)$user['is_hr_admin'];
                    $_SESSION['department_id']  = (int)$user['department_id'];
                    $_SESSION['last_activity']  = time();
                    $_SESSION['_last_regenerate'] = time();

                    security_log('login_success', ['user_id' => (string)$user['id'], 'email' => $email, 'is_hr_admin' => (string)$user['is_hr_admin']]);

                    if ($user['is_hr_admin'] == 1) {
                        if ($access_employee) {
                            header("Location: Employee_dashboard.php");
                        } else {
                            header("Location: HR_dashboard.php");
                        }
                    } else {
                        header("Location: Employee_dashboard.php");
                    }
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Unified Login - CultureHR</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="assets/hrms-theme.css" rel="stylesheet">
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        primary: "#642754",
        "primary-dark": "#58264a",
        accent: "#eb652c",
        "accent-dark": "#d85a24",
      }
    }
  }
};
</script>
<style>
  body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
  .login-bg {
    background: #642754;
  }
  @keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }
  .login-card {
    box-shadow: 0 25px 50px -12px rgba(100, 39, 84, 0.25), 0 0 0 1px rgba(255,255,255,0.1);
  }
</style>
</head>

<body class="login-bg min-h-screen flex items-center justify-center p-6">

<div class="w-full max-w-md bg-white/95 backdrop-blur-sm p-10 rounded-2xl login-card border border-white/20">

<h2 class="text-2xl font-bold mb-2 text-center text-[#642754]">Welcome Back</h2>
<p class="text-slate-500 text-sm text-center mb-8">Sign in to CultureHR</p>

<form method="POST" class="space-y-5">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

<?php if (!empty($loginError)): ?>
<div class="p-4 bg-[#ff4954]/10 text-[#ff4954] rounded-xl text-sm border border-[#ff4954]/30">
    <?= htmlspecialchars($loginError) ?>
</div>
<?php endif; ?>

<div>
<label class="block text-sm font-semibold mb-1.5 text-slate-700">Work Email</label>
<input
    type="email"
    name="email"
    required
    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
    class="w-full p-3.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#eb652c]/30 focus:border-[#eb652c] transition-all"
    placeholder="name@company.com">
</div>

<div>
<label class="block text-sm font-semibold mb-1.5 text-slate-700">Password</label>
<input
    type="password"
    name="password"
    required
    class="w-full p-3.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#eb652c]/30 focus:border-[#eb652c] transition-all"
    placeholder="••••••••">
</div>

<div id="employeeAccessOption" class="hidden">
    <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="access_employee"
               class="rounded border-slate-300 text-[#eb652c] focus:ring-[#eb652c]">
        Access Employee Portal
    </label>
</div>

<button
    type="submit"
    class="w-full bg-[#eb652c] hover:bg-[#d85a24] text-white font-bold py-3.5 rounded-xl shadow-md transition-all">
    Log In
</button>

</form>
</div>

<script>
document.querySelector('input[name="email"]').addEventListener('blur', function() {
    const email = this.value;

    fetch("check_hr.php?email=" + encodeURIComponent(email))
        .then(response => response.json())
        .then(data => {
            if (data.is_hr === true) {
                document.getElementById('employeeAccessOption').classList.remove('hidden');
            } else {
                document.getElementById('employeeAccessOption').classList.add('hidden');
            }
        });
});
</script>

</body>
</html>
