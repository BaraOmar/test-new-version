<?php
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id ? true : false;
$employee = null;

/* =========================
   Fetch departments & managers
========================= */
$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$managers = $pdo->query("SELECT id, full_name, job_title FROM employees")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Load employee if editing
========================= */
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        die("Employee not found.");
    }
}

/* =========================
   Handle DELETE
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid request. Please try again.";
    } elseif (!$isEdit) {
        header("Location: Employee_directory.php");
        exit;
    } else {
        $deleteId = (int)$_POST['delete_id'];
        $check = $pdo->prepare("SELECT id, is_hr_admin FROM employees WHERE id = ?");
        $check->execute([$deleteId]);
        $emp = $check->fetch(PDO::FETCH_ASSOC);

        if ($emp) {
            if ($emp['is_hr_admin']) {
                $errors[] = "Cannot delete HR Administrator.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
                $stmt->execute([$deleteId]);
                header("Location: Employee_directory.php?success=1");
                exit;
            }
        }
    }
}

/* =========================
   Handle CREATE / UPDATE
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['delete_id'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid request. Please try again.";
    }

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $job_title = trim($_POST['job_title'] ?? '');

    /* =========================
   NAME & JOB TITLE VALIDATION
========================= */

// Allow letters and spaces only
if ($full_name && !preg_match("/^[a-zA-Z\s]+$/", $full_name)) {
    $errors[] = "Full name must contain letters only (no numbers).";
}

if ($job_title && !preg_match("/^[a-zA-Z\s]+$/", $job_title)) {
    $errors[] = "Job title must contain letters only (no numbers).";
}
  $joining_date = trim($_POST['joining_date'] ?? '');
    $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : null;
$manager_id = isset($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
  
    $phone = trim($_POST['phone'] ?? '');
$marital_status = trim($_POST['marital_status'] ?? '');
$position_level = trim($_POST['position_level'] ?? '');
$employment_type = trim($_POST['employment_type'] ?? '');
$location = trim($_POST['location'] ?? '');

    if (!$full_name) $errors[] = "Full name required.";
    if (!$email) $errors[] = "Email required.";
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}
    if (!$job_title) $errors[] = "Job title required.";
    if (!$joining_date) $errors[] = "Joining date required.";
    if ($joining_date) {
    $dateCheck = DateTime::createFromFormat('Y-m-d', $joining_date);
    if (!$dateCheck || $dateCheck->format('Y-m-d') !== $joining_date) {
        $errors[] = "Invalid joining date format.";
    }
}
    if (!$department_id) $errors[] = "Department required.";
    /* ✅ ADD THIS AFTER DEPARTMENT VALIDATION */
if (!empty($manager_id)) {

    // Prevent employee being their own manager (edit mode only)
    if ($isEdit && $manager_id === (int)$id) {
        $errors[] = "Employee cannot be their own manager.";
    } else {
        $checkManager = $pdo->prepare("SELECT id FROM employees WHERE id = ?");
        $checkManager->execute([$manager_id]);
        if (!$checkManager->fetch()) {
            $errors[] = "Invalid manager selected.";
        }
    }
}

/* ✅ ADD THIS RIGHT HERE */
if ($department_id) {
    $checkDept = $pdo->prepare("SELECT id FROM departments WHERE id = ?");
    $checkDept->execute([(int)$department_id]);
    if (!$checkDept->fetch()) {
        $errors[] = "Invalid department selected.";
    }
}


    // Email uniqueness check
    if ($isEdit) {
       $stmt = $pdo->prepare("SELECT 1 FROM employees WHERE email = ? AND id != ? LIMIT 1");
$stmt->execute([$email, $id]);
        
    } else {
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ?");
        $stmt->execute([$email]);
    }

    if ($stmt->fetch()) {
        $errors[] = "Email already exists.";
    }

    $password = trim($_POST['password'] ?? '');
    if ($isEdit) {
        if (!empty($password)) {
            $pwErrors = validatePassword($password, false);
            $errors = array_merge($errors, $pwErrors);
        }
    } else {
        $pwErrors = validatePassword($password, true);
        $errors = array_merge($errors, $pwErrors);
    }

    if (empty($errors)) {
if ($isEdit) {

    if (!empty($password)) {

        $hashedPassword = hashPassword($password);

        $stmt = $pdo->prepare("
            UPDATE employees SET
                full_name = ?,
                email = ?,
                password = ?,
                phone = ?,
                marital_status = ?,
                job_title = ?,
                position_level = ?,
                joining_date = ?,
                employment_type = ?,
                manager_id = ?,
                department_id = ?,
                location = ?,
                is_hr_admin = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $full_name,
            $email,
            $hashedPassword,
            $phone ?: null,
            $marital_status ?: null,
            $job_title,
            $position_level ?: null,
            $joining_date,
            $employment_type ?: null,
            $manager_id ?: null,
            $department_id,
            $location ?: null,
            isset($_POST['grant_hr']) ? 1 : 0,
            $id
        ]);

    } else {

        $stmt = $pdo->prepare("
            UPDATE employees SET
                full_name = ?,
                email = ?,
                phone = ?,
                marital_status = ?,
                job_title = ?,
                position_level = ?,
                joining_date = ?,
                employment_type = ?,
                manager_id = ?,
                department_id = ?,
                location = ?,
                is_hr_admin = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $full_name,
            $email,
            $phone ?: null,
            $marital_status ?: null,
            $job_title,
            $position_level ?: null,
            $joining_date,
            $employment_type ?: null,
            $manager_id ?: null,
            $department_id,
            $location ?: null,
            isset($_POST['grant_hr']) ? 1 : 0,
            $id
        ]);
    }

   header("Location: Employee_directory.php?success=1");

    exit;
} else {

    $hashedPassword = hashPassword($password);

$stmt = $pdo->prepare("
    INSERT INTO employees (
        full_name,
        email,
        password,
        phone,
        marital_status,
        job_title,
        position_level,
        joining_date,
        employment_type,
        manager_id,
        department_id,
        location,
        is_hr_admin
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $full_name,
    $email,
    $hashedPassword,
    $phone ?: null,
    $marital_status ?: null,
    $job_title,
    $position_level ?: null,
    $joining_date,
    $employment_type ?: null,
    $manager_id ?: null,
    $department_id,
    $location ?: null,
    isset($_POST['grant_hr']) ? 1 : 0
]);

            header("Location: Employee_directory.php?success=1");
exit;
            
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Edit Employee Profile - SurveyCenter</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#8b5cf6",
                        "background-light": "#f6f6f8",
                        "background-dark": "#141121",
                        "nav-active": "#f3e8ff",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style type="text/tailwindcss">
        body {
            font-family: 'Inter', sans-serif;
        }
        .form-input:focus {
            border-color: #8b5cf6;
            ring-color: #8b5cf6;
        }
        #delete-modal:target {
            display: flex;
        }
        #hr-access-modal:target {
            display: flex;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>

<body
    class="bg-[#f8f4f8] font-display text-slate-900 min-h-screen flex">
    <div class="flex h-screen overflow-hidden w-full">
        <?php $currentPage = 'employees'; include __DIR__ . '/navbar.php'; ?>
    <div class="flex-1 min-w-0 overflow-auto">
        <?php $pageTitle = $isEdit ? 'Edit Employee' : 'Onboarding'; include __DIR__ . '/admin_header.php'; ?>

        <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="flex mb-6 text-sm font-medium text-slate-500 dark:text-slate-400">
                <ol class="flex items-center space-x-2">
                    <li class="" style=""><a class="hover:text-primary" href="#" style="">Directory</a></li>
                    <li class="flex items-center space-x-2" style="">
                        <span class="material-symbols-outlined text-base" style="">chevron_right</span>
                        <a class="hover:text-primary" href="#" style="">Employees</a>
                    </li>
                    <li class="flex items-center space-x-2" style="">
                        <span class="material-symbols-outlined text-base text-slate-300" style="">chevron_right</span>
                        <span class="text-slate-900 dark:text-slate-100" style="">Edit Profile</span>
                    </li>
                </ol>
            </nav>

            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight" style="">Edit
                        Employee Profile</h1>
                    <p class="mt-2 text-slate-600 dark:text-slate-400" style="">Update personal, professional, and
                        reporting information for this employee.</p>
                </div>
                <div class="flex items-center gap-3">
                    <?php if ($isEdit && $id): ?>
                    <a href="survey_builder_interface.php?employee_id=<?= (int)$id ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary/10 text-primary text-sm font-semibold hover:bg-primary/20 transition-colors">
                        <span class="material-symbols-outlined text-lg">poll</span>
                        Send Survey
                    </a>
                    <a href="evaluation_bulder_interface.php?employee_id=<?= (int)$id ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary/10 text-primary text-sm font-semibold hover:bg-primary/20 transition-colors">
                        <span class="material-symbols-outlined text-lg">rate_review</span>
                        Send Evaluation
                    </a>
                    <?php endif; ?>
                    <div class="flex -space-x-2 overflow-hidden mr-2">
                        <img alt="Profile"
                            class="inline-block h-10 w-10 rounded-full ring-2 ring-white dark:ring-slate-900"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuCKJWDk2zseRabf2hOlOkE-emLvSa6a6pZ-S-51qFzmUxl1z1dfDCwnhqD8JngOb5cI4WYDI4iJKlTQ3ZiHiPOlx4xfzCc8l--YwobsU0XFhMzwFDh9tA1uLpbxOamNqvbsAFNmvVDaUj_I4lIX0bNHdOZXAxK3Y3Gza5Zq2x6-KSrd3e-BJSGnDnHE8aRsHn5HAHSiGc8m7-f5qro9putKbfmUUwQVcVh0QWrWIvZyvfi6Cl7LqbeBm7krb5ZOrtrNLfLIFCLdbGwO"
                            style="" />
                    </div>
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- ✅ FIX 1: ONE MAIN FORM ONLY (no nested forms)         -->
            <!-- ===================================================== -->
             <?php if (!empty($errors)): ?>
<div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
    <ul class="list-disc pl-5 space-y-1">
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
       <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <div class="mb-6">
    <label class="flex items-center gap-3 px-6 py-4 bg-slate-100 dark:bg-slate-800 rounded-xl cursor-pointer">
        
        <span class="material-symbols-outlined text-amber-500">security</span>

        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">
            Grant HR Administrator Access
        </span>

        <input
            type="checkbox"
            name="grant_hr"
            value="1"
            class="h-5 w-5 rounded border-slate-300 text-primary focus:ring-primary"
            <?= !empty($employee['is_hr_admin']) ? 'checked' : '' ?>
        >

    </label>
</div>
    

    <div class="space-y-8">
    


<!-- ================= PERSONAL ================= -->
<section class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
<div class="p-6 sm:p-8">
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

<div class="flex flex-col gap-2">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
Full Name <span class="text-red-500">*</span>
</label>
<input type="text" name="full_name" required
value="<?= htmlspecialchars($employee['full_name'] ?? '') ?>"
class="w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11">
</div>

<div class="flex flex-col gap-2">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
    Email <span class="text-red-500">*</span>
</label>
<input type="email" name="email" required
value="<?= htmlspecialchars($employee['email'] ?? '') ?>"
class="w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11">
</div>

<div>
    <label class="block text-sm font-semibold mb-1">Password <?= !$isEdit ? '<span class="text-red-500">*</span>' : '' ?></label>
    <input
        type="password"
        name="password"
        placeholder="<?= $isEdit ? 'Leave empty to keep current password' : 'Min 8 chars, upper, lower, number' ?>"
        class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-[#884f79]/30 focus:border-[#884f79]"
        <?= !$isEdit ? 'required' : '' ?>>
</div>

<div class="flex flex-col gap-2">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
Phone Number
</label>
<input type="tel" name="phone"
value="<?= htmlspecialchars($employee['phone'] ?? '') ?>"
class="w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11">
</div>

<div class="flex flex-col gap-2">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
Marital Status
</label>
<select name="marital_status"
class="w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11">
<option value="">Select status</option>
<option value="single" <?= ($employee['marital_status'] ?? '') === 'single' ? 'selected' : '' ?>>Single</option>
<option value="married" <?= ($employee['marital_status'] ?? '') === 'married' ? 'selected' : '' ?>>Married</option>
<option value="divorced" <?= ($employee['marital_status'] ?? '') === 'divorced' ? 'selected' : '' ?>>Divorced</option>
<option value="widowed" <?= ($employee['marital_status'] ?? '') === 'widowed' ? 'selected' : '' ?>>Widowed</option>
</select>
</div>

</div>
</div>
</section>

<!-- ================= PROFESSIONAL ================= -->
<section class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
<div class="p-6 sm:p-8">
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

<div class="flex flex-col gap-2">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
Job Title <span class="text-red-500">*</span>
</label>
<input type="text" name="job_title" required
value="<?= htmlspecialchars($employee['job_title'] ?? '') ?>"
class="w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11">
</div>

<div class="flex flex-col gap-2">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
Position Level
</label>
<select name="position_level"
class="w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11">
<option value="">Select level</option>
<option value="l1" <?= ($employee['position_level'] ?? '') === 'l1' ? 'selected' : '' ?>>Entry (L1)</option>
<option value="l2" <?= ($employee['position_level'] ?? '') === 'l2' ? 'selected' : '' ?>>Intermediate (L2)</option>
<option value="l3" <?= ($employee['position_level'] ?? '') === 'l3' ? 'selected' : '' ?>>Senior (L3)</option>
<option value="l4" <?= ($employee['position_level'] ?? '') === 'l4' ? 'selected' : '' ?>>Staff (L4)</option>
<option value="l5" <?= ($employee['position_level'] ?? '') === 'l5' ? 'selected' : '' ?>>Director (L5)</option>
</select>
</div>

<div class="flex flex-col gap-2">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
Joining Date <span class="text-red-500">*</span>
</label>
<input type="date" name="joining_date" required
value="<?= htmlspecialchars($employee['joining_date'] ?? '') ?>"
class="w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11">
</div>

<div class="flex flex-col gap-2">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
Employment Type
</label>
<select name="employment_type"
class="w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11">
<option value="full-time" <?= ($employee['employment_type'] ?? '') === 'full-time' ? 'selected' : '' ?>>Full-time</option>
<option value="part-time" <?= ($employee['employment_type'] ?? '') === 'part-time' ? 'selected' : '' ?>>Part-time</option>
<option value="contract" <?= ($employee['employment_type'] ?? '') === 'contract' ? 'selected' : '' ?>>Contract</option>
<option value="intern" <?= ($employee['employment_type'] ?? '') === 'intern' ? 'selected' : '' ?>>Intern</option>
</select>
</div>

</div>
</div>
</section>

<!-- ================= REPORTING ================= -->
<section class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
<div class="p-6 sm:p-8">
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

<div class="flex flex-col gap-2">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
Manager
</label>

<?php
$managers = $pdo->query("SELECT id, full_name, job_title FROM employees WHERE is_manager = 1")->fetchAll();
?>

<select name="manager_id"
class="w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11">
    <option value="">Select manager</option>

    <?php foreach ($managers as $mgr): ?>
        <?php
            // Prevent selecting self in edit mode
            if (!empty($isEdit) && $mgr['id'] == ($id ?? 0)) {
                continue;
            }
        ?>

        <option value="<?= $mgr['id'] ?>"
            <?= ($employee['manager_id'] ?? '') == $mgr['id'] ? 'selected' : '' ?>>

            <?= htmlspecialchars($mgr['full_name']) ?>
            <?php if (!empty($mgr['job_title'])): ?>
                (<?= htmlspecialchars($mgr['job_title']) ?>)
            <?php endif; ?>

        </option>
    <?php endforeach; ?>
</select>

</div>
<div class="flex flex-col gap-2">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
Department <span class="text-red-500">*</span>
</label>

<select name="department_id" required
class="w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-primary focus:ring-primary h-11">
<option value="">Select department</option>
<?php foreach ($departments as $dept): ?>
<option value="<?= $dept['id'] ?>"
<?= ($employee['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
<?= htmlspecialchars($dept['name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="flex flex-col gap-2">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
Work Location
</label>
<div class="flex gap-6">
<label><input type="radio" name="location" value="remote"
<?= ($employee['location'] ?? '') === 'remote' ? 'checked' : '' ?>> Remote</label>
<label><input type="radio" name="location" value="hybrid"
<?= ($employee['location'] ?? '') === 'hybrid' ? 'checked' : '' ?>> Hybrid</label>
<label><input type="radio" name="location" value="onsite"
<?= ($employee['location'] ?? '') === 'onsite' ? 'checked' : '' ?>> On-site</label>
</div>
</div>

</div>
</div>
</section>

</div>



<div class="flex items-center justify-between pt-6">
   <button 
    type="submit" 
    name="delete_id" 
    value="<?= $id ?>"
    onclick="return confirm('Are you sure you want to delete this employee?')"
    class="text-red-600 font-semibold"
>
    Delete Profile
</button> 

    <div class="flex items-center gap-3">
    <script>
const form = document.querySelector("form");
const btn = document.getElementById("saveBtn");

form.addEventListener("submit", function () {
    btn.disabled = true;
    btn.textContent = "Saving...";
});
</script>

        <!-- Save button (filled) -->
        <button
    type="submit"
    id="saveBtn"
    class="px-8 py-3 bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-bold rounded-lg shadow-md transition-all active:scale-95"
>
    Save Changes
</button>
    </div>
</div>

</form>

                    
                </div>
            </div>

            <a class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                href="#" style="">
                <span class="material-symbols-outlined text-xl" style="">close</span>
            </a>
        </div>
    </div>

    <div class="fixed inset-0 z-[100] hidden items-center justify-center px-4 sm:px-0" id="hr-access-modal">
        <a class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm cursor-default" href="#" style=""></a>
        <div
            class="relative bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
            <div class="p-8">
                <div
                    class="flex items-center justify-center w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 mb-6 mx-auto">
                    <span class="material-symbols-outlined text-2xl" style="">security</span>
                </div>
                <div class="text-center">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3" style="">Enable HR Dashboard
                        Access for Jane Doe?</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed" style="">
                        Are you sure you want to grant administrator privileges? This will allow the employee to view
                        and manage company-wide data, including sensitive personnel files and survey results.
                    </p>
                </div>
                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <a class="flex-1 px-4 py-3 text-center text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                        href="#" style="">
                        Cancel
                    </a>
                    <button
                        class="flex-1 px-4 py-3 text-center text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-200 dark:shadow-none transition-all active:scale-95"
                        style="">
                        Confirm and Grant Access
                    </button>
                </div>
            </div>
            <a class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                href="#" style="">
                <span class="material-symbols-outlined text-xl" style="">close</span>
            </a>
        </div>
    </div>
</body>

</html>