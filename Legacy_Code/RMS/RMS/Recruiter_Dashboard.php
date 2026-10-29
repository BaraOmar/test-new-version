<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Recruiter Dashboard Hub - RMS Admin</title>
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
                        "primary": "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                    },
                    fontFamily: {
                        "display": ["Manrope"]
                    },
                    borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Manrope', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .active-nav {
            background-color: #137fec;
            color: white;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900">

    <main class="flex-1 flex flex-col overflow-hidden">
       
        <div class="flex-1 overflow-y-auto p-8 lg:p-12">
            <div class="max-w-5xl mx-auto space-y-12">
                <div class="text-center lg:text-left">
                    <h2 class="text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">Recruiter
                        Dashboard Hub</h2>
                    <p class="text-slate-500 mt-2 text-lg">Select a section below to manage your recruitment portal.
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-10">
                    <div
                        class="group bg-white dark:bg-slate-900 p-8 rounded-2xl border-2 border-transparent hover:border-primary/20 shadow-sm hover:shadow-xl transition-all flex flex-col items-center text-center justify-between aspect-square">
                        <div
                            class="bg-primary/10 text-primary w-24 h-24 rounded-2xl flex items-center justify-center transition-transform group-hover:scale-110">
                            <span class="material-symbols-outlined text-5xl">add_circle</span>
                        </div>
                        <div class="space-y-3">
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Post New Job</h3>
                            <p class="text-slate-500 text-sm max-w-xs">Create and publish new job openings to
                                attract the best talent.</p>
                        </div>
                        <a class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2"
                            href="create_edit_job.php">
                            Create Posting
                            <span class="material-symbols-outlined text-xl">arrow_forward</span>
                        </a>
                    </div>
                    <div
                        class="group bg-white dark:bg-slate-900 p-8 rounded-2xl border-2 border-transparent hover:border-primary/20 shadow-sm hover:shadow-xl transition-all flex flex-col items-center text-center justify-between aspect-square">
                        <div
                            class="bg-emerald-100 text-emerald-600 w-24 h-24 rounded-2xl flex items-center justify-center transition-transform group-hover:scale-110">
                            <span class="material-symbols-outlined text-5xl">work_history</span>
                        </div>
                        <div class="space-y-3">
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Job Management</h3>
                            <p class="text-slate-500 text-sm max-w-xs">Edit, close, or promote your existing active
                                job listings.</p>
                        </div>
                        <a class="w-full bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold py-3.5 rounded-xl transition-all flex items-center justify-center gap-2"
                            href="Job_Management_Page.php">
                            View Jobs
                            <span class="material-symbols-outlined text-xl">arrow_forward</span>
                        </a>
                    </div>
                    <div
                        class="group bg-white dark:bg-slate-900 p-8 rounded-2xl border-2 border-transparent hover:border-primary/20 shadow-sm hover:shadow-xl transition-all flex flex-col items-center text-center justify-between aspect-square">
                        <div
                            class="bg-purple-100 text-purple-600 w-24 h-24 rounded-2xl flex items-center justify-center transition-transform group-hover:scale-110">
                            <span class="material-symbols-outlined text-5xl">description</span>
                        </div>
                        <div class="space-y-3">
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Applications</h3>
                            <p class="text-slate-500 text-sm max-w-xs">Review candidate profiles, screen resumes,
                                and move stages.</p>
                        </div>
                        <a class="w-full bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold py-3.5 rounded-xl transition-all flex items-center justify-center gap-2"
                            href="Applications_interview.php">
                            Review Applicants
                            <span class="material-symbols-outlined text-xl">arrow_forward</span>
                        </a>
                    </div>
                    <div
                        class="group bg-white dark:bg-slate-900 p-8 rounded-2xl border-2 border-transparent hover:border-primary/20 shadow-sm hover:shadow-xl transition-all flex flex-col items-center text-center justify-between aspect-square">
                        <div
                            class="bg-amber-100 text-amber-600 w-24 h-24 rounded-2xl flex items-center justify-center transition-transform group-hover:scale-110">
                            <span class="material-symbols-outlined text-5xl">bar_chart</span>
                        </div>
                        <div class="space-y-3">
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Reports</h3>
                            <p class="text-slate-500 text-sm max-w-xs">Analyze recruitment data, hiring velocity,
                                and source metrics.</p>
                        </div>
                        <a class="w-full bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold py-3.5 rounded-xl transition-all flex items-center justify-center gap-2"
                            href="report_generating.php">
                            Generate Reports
                            <span class="material-symbols-outlined text-xl">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    </div>

</body>

</html>