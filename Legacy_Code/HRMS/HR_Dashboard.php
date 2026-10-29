<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

/* ================= TOTAL EMPLOYEES ================= */
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();

/* ================= SURVEY RESPONSE RATE ================= */
$totalSurveys = $pdo->query("SELECT COUNT(*) FROM surveys")->fetchColumn();
$totalSurveyResponses = $pdo->query("SELECT COUNT(*) FROM survey_responses")->fetchColumn();

$surveyRate = 0;
if ($totalEmployees > 0 && $totalSurveys > 0) {
    $surveyRate = round(($totalSurveyResponses / ($totalEmployees * $totalSurveys)) * 100);
}

/* ================= EVALUATION RESPONSE RATE ================= */
$totalEvaluations = $pdo->query("SELECT COUNT(*) FROM evaluations")->fetchColumn();
$totalEvalResponses = $pdo->query("SELECT COUNT(*) FROM evaluation_responses")->fetchColumn();

$evalRate = 0;
if ($totalEmployees > 0 && $totalEvaluations > 0) {
    $evalRate = round(($totalEvalResponses / ($totalEmployees * $totalEvaluations)) * 100);
}
?>

<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>HR Management Hub - CultureHR</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <link href="assets/hrms-theme.css" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#642754",
                        "primary-light": "#884f79",
                        "accent": "#eb652c",
                        "accent-dark": "#d85a24",
                        "background-light": "#f8f4f8",
                        "background-dark": "#2d1f2d",
                    },
                    fontFamily: { "display": ["Inter", "sans-serif"] },
                    borderRadius: { "xl": "12px", "2xl": "16px" },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

    </style>
</head>

<body class="bg-[#f8f4f8] text-slate-900 font-display">
    <div class="flex h-screen overflow-hidden">
        <?php $currentPage = 'dashboard'; include __DIR__ . '/navbar.php'; ?>
        <main class="flex-1 flex flex-col overflow-y-auto">
            <?php $pageTitle = 'Dashboard'; include __DIR__ . '/admin_header.php'; ?>
            <div class="p-8 max-w-7xl mx-auto w-full flex flex-col h-full bg-[#f8f4f8]">
                <div class="mb-10 text-center md:text-left">
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">HR Management Hub</h2>
                    <p class="text-slate-500 dark:text-slate-400 mt-2 text-lg">Select a section below to manage your
                        organization.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 w-full max-w-5xl mx-auto">
                    <div
                        class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p
                                    class="text-sm font-medium text-[#757575] dark:text-slate-400 uppercase tracking-wide">
                                    Total Employees</p>
                                <h4 class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?= $totalEmployees ?></h4>
                            </div>
                            <div
                                class="w-12 h-12 bg-[#eb652c]/10 rounded-xl flex items-center justify-center text-[#eb652c]">
                                <span class="material-symbols-outlined">badge</span>
                            </div>
                        </div>
                        <div class="flex items-center text-sm">
                            <span
                                class="text-emerald-600 dark:text-emerald-400 font-bold bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded mr-2 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">trending_up</span>
                                2%
                            </span>
                            <span class="text-slate-400">vs last month</span>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p
                                    class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                    Survey Response Rate</p>
                                <h4 class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?= $surveyRate ?>%</h4>
                            </div>
                            <div
                                class="w-12 h-12 bg-[#3cd458]/10 rounded-xl flex items-center justify-center text-[#3cd458]">
                                <span class="material-symbols-outlined">fact_check</span>
                            </div>
                        </div>
                        <div class="flex items-center text-sm">
                            <span
                                class="text-[#3cd458] font-bold bg-[#3cd458]/10 px-2 py-0.5 rounded-lg mr-2">On
                                Track</span>
                            <span class="text-slate-400">Target: 80%</span>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p
                                    class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                    Evaluation Response Rate</p>
                                <h4 class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?= $evalRate ?>%</h4>
                            </div>
                            <div
                                class="w-12 h-12 bg-[#3cd458]/10 rounded-xl flex items-center justify-center text-[#3cd458]">
                                <span class="material-symbols-outlined">fact_check</span>
                            </div>
                        </div>
                        <div class="flex items-center text-sm">
                            <span
                                class="text-[#3cd458] font-bold bg-[#3cd458]/10 px-2 py-0.5 rounded-lg mr-2">On
                                Track</span>
                            <span class="text-slate-400">Target: 80%</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-5xl mx-auto items-stretch">
                    <div
                        class="bg-white dark:bg-slate-900 p-10 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col items-center text-center group">
                        <div
                            class="w-24 h-24 bg-[#eb652c]/10 rounded-2xl flex items-center justify-center text-[#eb652c] mb-8 group-hover:scale-110 transition-transform duration-300">
                            <span class="material-symbols-outlined text-[40px]">person_add</span>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">Employee Management</h3>
                        <p class="text-slate-500 dark:text-slate-400 mb-10 max-w-xs leading-relaxed">Add new team
                            members, update profiles, and organize departmental structures.</p>
                       <a href="Employee_directory.php"
   class="mt-auto w-full bg-[#eb652c] hover:bg-[#d85a24] text-white font-bold py-4 px-6 rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
    View Employees
    <span class="material-symbols-outlined text-sm">arrow_forward</span>
</a>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-900 p-10 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col items-center text-center group">
                        <div
                            class="w-24 h-24 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl flex items-center justify-center text-emerald-600 mb-8 group-hover:scale-110 transition-transform duration-300">
                            <span class="material-symbols-outlined text-emerald-500 text-5xl">content_paste</span>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">Survey Management</h3>
                        <p class="text-slate-500 dark:text-slate-400 mb-10 max-w-xs leading-relaxed">Create and
                            distribute engagement surveys to gather valuable feedback.</p>
                       <a href="survey_list.php"
   class="mt-auto w-full bg-[#eb652c] hover:bg-[#d85a24] text-white font-bold py-4 px-6 rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
    View Survey
    <span class="material-symbols-outlined text-sm">arrow_forward</span>
</a>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-900 p-10 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col items-center text-center group">
                        <div
                            class="w-24 h-24 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl flex items-center justify-center text-emerald-600 mb-8 group-hover:scale-110 transition-transform duration-300">
                            <span class="material-symbols-outlined text-[40px]">bar_chart</span>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">Evaluation Management</h3>
                        <p class="text-slate-500 dark:text-slate-400 mb-10 max-w-xs leading-relaxed">Create and
                            distribute Evaluations to gather valuable feedback.</p>
                        <a href="evaluation_list.php"
   class="mt-auto w-full bg-[#eb652c] hover:bg-[#d85a24] text-white font-bold py-4 px-6 rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
    View Evaluation
    <span class="material-symbols-outlined text-sm">arrow_forward</span>
</a>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-900 p-10 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col items-center text-center group">
                        <div
                            class="w-24 h-24 bg-[#eb652c]/10 rounded-2xl flex items-center justify-center text-[#eb652c] mb-8 group-hover:scale-110 transition-transform duration-300">
                            <span
                                class="material-symbols-outlined text-[40px]">assignment_turned_in</span>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">Career Path & Development
                        </h3>
                        <p class="text-slate-500 dark:text-slate-400 mb-10 max-w-xs leading-relaxed">Track employee
                            information, current position, and development status across the organization.</p>
                        <a href="career_path_Skill_module.php"
   class="mt-auto w-full bg-[#eb652c] hover:bg-[#d85a24] text-white font-bold py-4 px-6 rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
    View Career Path & Development
    <span class="material-symbols-outlined text-sm">arrow_forward</span>
</a>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-900 p-10 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col items-center text-center group md:col-span-2 md:max-w-md md:mx-auto">
                        <div
                            class="w-24 h-24 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl flex items-center justify-center text-emerald-600 mb-8 group-hover:scale-110 transition-transform duration-300">
                            <span class="material-symbols-outlined text-[#eb652c] text-[40px]">school</span>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">LMS</h3>
                        <p class="text-slate-500 dark:text-slate-400 mb-10 max-w-xs leading-relaxed">Improve employee
                            learning and development through our integrated Learning Management System.</p>
                       <a href="LMS_upload.php"
   class="mt-auto w-full bg-[#eb652c] hover:bg-[#d85a24] text-white font-bold py-4 px-6 rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
    View LMS
    <span class="material-symbols-outlined text-sm">arrow_forward</span>
</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>