<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/auth.php";
requireLogin();

require_once __DIR__ . "/config/db.php";

$userId = (int) $_SESSION['user_id'];

/* ============================
   Fetch Employee Full Profile
============================ */

$stmt = $pdo->prepare("
    SELECT 
        e.full_name,
        e.email,
        e.phone,
        e.marital_status,
        e.job_title,
        e.position_level,
        e.joining_date,
        e.employment_type,
        e.location,
        e.status,
        e.department_id,
        e.manager_id,
        d.name AS department_name,
        m.full_name AS manager_name
    FROM employees e
    LEFT JOIN departments d ON d.id = e.department_id
    LEFT JOIN employees m ON m.id = e.manager_id
    WHERE e.id = ?
    LIMIT 1
");

$stmt->execute([$userId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

$statusKey = strtolower(trim($employee['status'] ?? ''));

$statusClasses = match ($statusKey) {
    'active' => 'bg-green-100 text-green-700',
    'onboarding' => 'bg-amber-100 text-amber-700',
    'inactive' => 'bg-red-100 text-red-700',
    default => 'bg-slate-100 text-slate-700',
};

if (!$employee) {
    session_destroy();
    header("Location: Login_page.php");
    exit;
}
?>


<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Employee My Profile View - CultureAmp</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&amp;display=swap"
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
                        "primary": "#642754",
                        "background-light": "#f6f6f8",
                        "background-dark": "#141121",
                    },
                    fontFamily: {
                        "display": ["Manrope", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.5rem",
                        "lg": "1rem",
                        "xl": "1.5rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style type="text/tailwindcss">
        body {
            font-family: 'Manrope', sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .profile-field-container {
            @apply flex flex-col gap-2;
        }
        .profile-field-label {
            @apply text-slate-700 dark:text-slate-300 text-sm font-bold;
        }
        .profile-field-label span.required {
            @apply text-rose-500 ml-1;
        }
        .profile-field-value {
            @apply flex items-center h-[52px] px-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 font-medium w-full shadow-sm;
        }
        .profile-field-value.readonly {
            @apply bg-slate-50/50 cursor-default;
        }
    </style>
</head>

<body class="bg-[#f9fafb] dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen">
    <div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-x-hidden">
        <div class="layout-container flex h-full grow flex-col">
            <?php $currentPage = 'my_profile'; $pageTitle = 'My Profile'; include __DIR__ . '/employee_navbar.php'; ?>
            <main class="flex flex-1 justify-center py-12 px-6 sm:px-10 lg:px-40">
                <div class="layout-content-container flex flex-col max-w-[1024px] flex-1 gap-8">
                    <section class="flex flex-col mb-4">
                        <div class="flex w-full flex-col gap-6 md:flex-row md:items-center justify-between">
                            <div class="flex gap-6 items-center">
                                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-2xl size-32 shadow-xl ring-4 ring-white dark:ring-slate-800"
                                    style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCMRSlLdE0e_rHPEasJlGH9ZpGYog3ZCs3Tue-xXNQdiMa8k8KYDjfsuYlIy-LrK6xLq8UAMZq8gTfMEyWtdmTiB95DM5qGnqTXDV27WVDrumKIl1xfkruhfBcyWDOJdzkItmgMeV_N7T6CR7Bur1slJqJS_foEmxkBVGhU4pHg5tc5edQ8uOJaCSrN9Pi4Vq4Eh_hrlw8FnC5j-pp7TYpdf-ra1PtuawXC73-ZnGNzfI0IDzc-N3ypNv2FYRdF73NrGkCzNRcLXW8E");'>
                                </div>
                                <div class="flex flex-col gap-1">
                                  <h1 class="text-slate-900 dark:text-white text-3xl font-extrabold leading-tight tracking-tight font-display">
    <?= htmlspecialchars($employee['full_name']) ?>
</h1>
                                   <p class="text-primary font-semibold text-lg">
    <?= htmlspecialchars($employee['job_title']) ?>

    <div class="mt-2">
    <span class="px-3 py-1 text-xs font-bold rounded-full <?= $statusClasses ?>">
        <?= htmlspecialchars($employee['status']) ?>
    </span>
</div>
</p>
                                    <div
                                        class="flex items-center gap-3 text-slate-500 dark:text-slate-400 text-sm mt-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-lg">groups</span>
                                         <span><?= htmlspecialchars($employee['department_name']) ?></span>
                                        </div>
                                       
                                       
                                    </div>
                                </div>
                            </div>

                        </div>
                    </section>
                    <div class="flex flex-col gap-8">
                        <div
                            class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-10 shadow-sm">
                            <div class="flex items-center gap-4 mb-10">
                                <div class="size-11 rounded-xl bg-[#eb652c]/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[#eb652c] text-2xl">person</span>
                                </div>
                                <h3 class="text-2xl font-bold text-[#141121] dark:text-white font-display">Personal
                                    Information</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
                                <div class="profile-field-container">
                                    <span class="profile-field-label">Full Name<span class="required">*</span></span>
                                    <div class="profile-field-value readonly"><?= htmlspecialchars($employee['full_name']) ?></div>
                                </div>
                                <div class="profile-field-container">
                                    <span class="profile-field-label">Email<span class="required">*</span></span>
                                    <div class="profile-field-value readonly"><?= htmlspecialchars($employee['email']) ?></div>
                                </div>
                                <div class="profile-field-container">
                                    <span class="profile-field-label">Phone Number</span>
                                    <div class="profile-field-value readonly"><?= htmlspecialchars($employee['phone']) ?></div>
                                </div>
                                <div class="profile-field-container">
                                    <span class="profile-field-label">Marital Status</span>
                                    <div class="profile-field-value readonly justify-between">
                                       <span><?= htmlspecialchars($employee['marital_status']) ?></span>
                                        <span class="material-symbols-outlined text-slate-400"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-10 shadow-sm">
                            <div class="flex items-center gap-4 mb-10">
                                <div class="size-11 rounded-xl bg-[#eb652c]/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[#eb652c] text-2xl">work</span>
                                </div>
                                <h3 class="text-2xl font-bold text-[#141121] dark:text-white font-display">Professional
                                    Details</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
                                <div class="profile-field-container">
                                    <span class="profile-field-label">Job Title<span class="required"></span></span>
                                    <div class="profile-field-value readonly"> <?= htmlspecialchars($employee['job_title']) ?></div>
                                </div>
                                <div class="profile-field-container">
                                    <span class="profile-field-label">Position Level</span>
                                    <div class="profile-field-value readonly justify-between">
                                       <span><?= htmlspecialchars($employee['position_level']) ?></span>
                                        <span class="material-symbols-outlined text-slate-400"></span>
                                    </div>
                                </div>
                                <div class="profile-field-container">
                                    <span class="profile-field-label">Joining Date<span class="required">*</span></span>
                                    <div class="profile-field-value readonly justify-between">
                                        <span>
    <?= date("d/m/Y", strtotime($employee['joining_date'])) ?>
</span>
                                        <span
                                            class="material-symbols-outlined text-slate-400 text-xl">calendar_today</span>
                                    </div>
                                </div>
                                <div class="profile-field-container">
                                    <span class="profile-field-label">Employment Type</span>
                                    <div class="profile-field-value readonly justify-between">
                                        <span><?= htmlspecialchars($employee['employment_type']) ?></span>
                                        <span class="material-symbols-outlined text-slate-400"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-10 shadow-sm">
                            <div class="flex items-center gap-4 mb-10">
                                <div class="size-11 rounded-xl bg-[#eb652c]/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[#eb652c] text-2xl">account_tree</span>
                                </div>
                                <h3 class="text-2xl font-bold text-[#141121] dark:text-white font-display">Reporting
                                    Structure</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-10">
                                <div class="profile-field-container">
                                    <span class="profile-field-label">Manager<span class="required">*</span></span>
                                    <div class="profile-field-value readonly gap-3">
                                        <span class="material-symbols-outlined text-slate-400 text-xl">search</span>
                                        <div class="size-6 rounded-full bg-slate-200 bg-cover bg-center"
                                            style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAVY1GBIuF0gkd-bDU6BarhwnZdPMVHO9zcRdP-iyJJTI6CY_He4cfbsG0kCpZiJZ7RwJx5Yc8eBGvp4X0RIN2sqLBSuqIU0Aqqnx-4UPvEaZaRoU8z-ZcGtpVVC4dMPeqGtFlmrxO6coUbcLDnyArv1GQHK_g1FA4TO1RMB9RknSSLPmacmM22vStDH9jLgqbce7CafQ4sT3TnhmlS5z1ukNj6x7WBjJyVjvQfPsf9xGVuXJi2Bjs9krxhRDCOQlPAiLJIU49zcWUH");'>
                                        </div>
                                        <span>
    <?= htmlspecialchars($employee['manager_name'] ?? 'No Manager Assigned') ?>
</span>
                                    </div>
                                </div>
                                <div class="profile-field-container">
                                    <span class="profile-field-label">Department<span class="required">*</span></span>
                                    <div class="profile-field-value readonly justify-between">
                                      <span><?= htmlspecialchars($employee['department_name']) ?></span>
                                        <span class="material-symbols-outlined text-slate-400"></span>
                                    </div>
                                </div>
                                <div class="col-span-full profile-field-container">
                                    <span class="profile-field-label">Work Location</span>
                                   <?php
$location = strtolower(trim($employee['location']));
?>

<div class="flex items-center gap-10 mt-2">

<label class="flex items-center gap-3 cursor-default">
    <div class="size-5 rounded-full border 
        <?= $location == 'remote' ? 'border-4 border-primary ring-2 ring-primary/10' : 'border-slate-300' ?> 
        bg-white">
    </div>
    <span class="text-sm <?= $location == 'remote' ? 'font-bold text-slate-900 dark:text-white' : 'text-slate-600' ?>">
        Remote
    </span>
</label>

<label class="flex items-center gap-3 cursor-default">
    <div class="size-5 rounded-full border 
        <?= $location == 'hybrid' ? 'border-4 border-primary ring-2 ring-primary/10' : 'border-slate-300' ?> 
        bg-white">
    </div>
    <span class="text-sm <?= $location == 'hybrid' ? 'font-bold text-slate-900 dark:text-white' : 'text-slate-600' ?>">
        Hybrid
    </span>
</label>

<label class="flex items-center gap-3 cursor-default">
    <div class="size-5 rounded-full border 
        <?= $location == 'onsite' ? 'border-4 border-primary ring-2 ring-primary/10' : 'border-slate-300' ?> 
        bg-white">
    </div>
    <span class="text-sm <?= $location == 'onsite' ? 'font-bold text-slate-900 dark:text-white' : 'text-slate-600' ?>">
        On-site
    </span>
</label>

</div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="bg-[#642754]/5 dark:bg-[#642754]/10 rounded-2xl p-8 border border-[#642754]/20 flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex flex-col gap-1.5">
                                <h4 class="text-slate-900 dark:text-white font-bold text-lg">Need to update info?</h4>
                                <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed max-w-xl">Personal
                                    information can only be edited through HR approval to maintain data integrity and
                                    regulatory compliance.</p>
                            </div>
                            <a href="employee_request_change.php"
   class="whitespace-nowrap px-8 py-3 bg-white dark:bg-slate-800 border-2 border-[#642754] rounded-xl text-[#642754] text-sm font-bold hover:bg-[#642754]/5 transition-colors shadow-sm inline-block text-center">
   Open Request
</a>
                        </div>
                    </div>
                </div>
            </main>
            
        </div>
    </div>
    

</body>

</html>