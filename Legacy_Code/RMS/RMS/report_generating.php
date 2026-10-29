<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Report Generation Page - RMS Academic</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&amp;display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&amp;display=swap"
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
                        "display": ["Manrope", "sans-serif"]
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
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-display min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <aside
            class="w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col shrink-0">
            <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white">
                        <span class="material-symbols-outlined">school</span>
                    </div>
                    <div>
                        <h1 class="text-sm font-bold tracking-tight">RMS Academic</h1>
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">Recruiter Portal
                        </p>
                    </div>
                </div>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <a class="flex items-center gap-3 px-3 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition-colors"
                    href="#">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition-colors"
                    href="#">
                    <span class="material-symbols-outlined text-[20px]">work</span>
                    <span class="text-sm font-medium">Jobs</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition-colors"
                    href="#">
                    <span class="material-symbols-outlined text-[20px]">group</span>
                    <span class="text-sm font-medium">Applicants</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2 bg-primary/10 text-primary rounded-lg transition-colors"
                    href="#">
                    <span class="material-symbols-outlined text-[20px]">bar_chart</span>
                    <span class="text-sm font-medium">Reports</span>
                </a>
                <div class="pt-4 pb-2 text-[10px] font-bold text-slate-400 uppercase px-3 tracking-widest">System</div>
                <a class="flex items-center gap-3 px-3 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition-colors"
                    href="#">
                    <span class="material-symbols-outlined text-[20px]">settings</span>
                    <span class="text-sm font-medium">Settings</span>
                </a>
            </nav>
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3 px-3 py-2">
                    <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                        <img class="w-full h-full object-cover" data-alt="Profile photo of the recruiter"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuDODlC6wu--182RTKrMM4Hu0_RnA_5LvDl8gvSFXXf9IyQ7lOLKejhcnU6AV1rLedWkvrjXzjOM3YyMtEdImwhjHBJ9wgxlQG0yPlEMS7PWk1VqnVuNIKE-fwVK86eSrkPK-sg5XwVEyJh5N7sYoma7P80g1XyiN2zizesW0D1nJDdMc6TLyad8xEQSycBcHCcs5iUIZ22AfCzvnGgrttX0i2Lz-F2rzzDg0yBr0sQdO3byn89FfqBNVzie0uqRWBu_VpIzff1b6E-M" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate">Dr. Aris Thorne</p>
                        <p class="text-[10px] text-slate-500 truncate">Senior Recruiter</p>
                    </div>
                    <span class="material-symbols-outlined text-slate-400 text-[18px]">logout</span>
                </div>
            </div>
        </aside>
        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-y-auto bg-background-light dark:bg-background-dark">
            <!-- Header Section -->
            <header class="p-8 pb-4">
                <div class="flex items-center gap-2 text-slate-400 text-sm mb-4">
                    <span>Home</span>
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                    <span class="text-primary font-medium">Reports</span>
                </div>
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Recruitment
                            Analytics &amp; Reports</h2>
                        <p class="text-slate-500 dark:text-slate-400 mt-1 max-w-xl">Monitor your talent pipeline,
                            analyze application trends, and export detailed candidate data for your departments.</p>
                    </div>
                    <button
                        class="bg-primary hover:bg-primary/90 text-white px-5 py-2.5 rounded-lg flex items-center gap-2 font-semibold text-sm transition-all shadow-sm shadow-primary/20">
                        <span class="material-symbols-outlined text-[20px]">download</span>
                        Export as CSV
                    </button>
                </div>
            </header>
            <!-- Selection and Stats -->
            <section class="px-8 py-4 space-y-6">
                <!-- Filter Bar -->
                <div
                    class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[300px]">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Select Job
                            Listing</label>
                        <div class="relative">
                            <select
                                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg py-3 pl-4 pr-10 text-sm font-medium appearance-none focus:ring-primary focus:border-primary">
                                <option>Assistant Professor of Computer Science - Fall 2024</option>
                                <option>Research Fellow: Quantum Computing Lab</option>
                                <option>Administrative Director - Student Affairs</option>
                                <option>Post-Doctoral Researcher (Biology)</option>
                            </select>
                            <span
                                class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">unfold_more</span>
                        </div>
                    </div>
                    <div class="w-full md:w-auto">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Date
                            Range</label>
                        <button
                            class="w-full md:w-48 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg py-3 px-4 text-sm font-medium text-left flex items-center justify-between">
                            Last 30 Days
                            <span class="material-symbols-outlined text-slate-400 text-[18px]">calendar_today</span>
                        </button>
                    </div>
                </div>
                <!-- Metric Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-slate-500 text-sm font-medium">Total Applicants</span>
                            <span
                                class="flex items-center text-emerald-600 text-xs font-bold bg-emerald-50 px-2 py-0.5 rounded-full">
                                <span class="material-symbols-outlined text-xs mr-1">trending_up</span>
                                +12%
                            </span>
                        </div>
                        <h3 class="text-3xl font-bold text-slate-900 dark:text-white">1,284</h3>
                        <p class="text-xs text-slate-400 mt-2">Unique applications received</p>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-slate-500 text-sm font-medium">Pending Reviews</span>
                            <span
                                class="flex items-center text-rose-600 text-xs font-bold bg-rose-50 px-2 py-0.5 rounded-full">
                                <span class="material-symbols-outlined text-xs mr-1">trending_down</span>
                                -5%
                            </span>
                        </div>
                        <h3 class="text-3xl font-bold text-slate-900 dark:text-white">142</h3>
                        <p class="text-xs text-slate-400 mt-2">Awaiting recruiter action</p>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-slate-500 text-sm font-medium">Success Hired</span>
                            <span
                                class="flex items-center text-emerald-600 text-xs font-bold bg-emerald-50 px-2 py-0.5 rounded-full">
                                <span class="material-symbols-outlined text-xs mr-1">trending_up</span>
                                +8%
                            </span>
                        </div>
                        <h3 class="text-3xl font-bold text-slate-900 dark:text-white">48</h3>
                        <p class="text-xs text-slate-400 mt-2">Positions filled this semester</p>
                    </div>
                </div>
                <!-- Visualization and Detailed Table -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Chart Section -->
                    <div
                        class="lg:col-span-2 bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-lg font-bold">Application Trends</h4>
                            <div class="flex items-center gap-4 text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-full bg-primary"></span>
                                    <span class="text-slate-500">Current Period</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-full bg-slate-200"></span>
                                    <span class="text-slate-500">Previous Period</span>
                                </div>
                            </div>
                        </div>
                        <!-- Mock Chart Representation -->
                        <div class="relative h-64 w-full flex items-end justify-between gap-1 mt-4">
                            <!-- SVG for line chart background -->
                            <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                                <div class="border-t border-slate-100 dark:border-slate-800 w-full h-0"></div>
                                <div class="border-t border-slate-100 dark:border-slate-800 w-full h-0"></div>
                                <div class="border-t border-slate-100 dark:border-slate-800 w-full h-0"></div>
                                <div class="border-t border-slate-100 dark:border-slate-800 w-full h-0"></div>
                                <div class="border-t border-slate-100 dark:border-slate-800 w-full h-0"></div>
                            </div>
                            <!-- Bar placeholders to simulate graph -->
                            <div
                                class="relative flex-1 bg-primary/10 rounded-t-sm group hover:bg-primary/20 transition-all h-[40%]">
                                <div
                                    class="absolute -top-8 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[10px] py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                                    12</div>
                            </div>
                            <div
                                class="relative flex-1 bg-primary/10 rounded-t-sm group hover:bg-primary/20 transition-all h-[55%]">
                            </div>
                            <div
                                class="relative flex-1 bg-primary/10 rounded-t-sm group hover:bg-primary/20 transition-all h-[30%]">
                            </div>
                            <div
                                class="relative flex-1 bg-primary/10 rounded-t-sm group hover:bg-primary/20 transition-all h-[70%]">
                            </div>
                            <div
                                class="relative flex-1 bg-primary/30 rounded-t-sm group hover:bg-primary/40 transition-all h-[90%]">
                            </div>
                            <div
                                class="relative flex-1 bg-primary/10 rounded-t-sm group hover:bg-primary/20 transition-all h-[45%]">
                            </div>
                            <div
                                class="relative flex-1 bg-primary/10 rounded-t-sm group hover:bg-primary/20 transition-all h-[60%]">
                            </div>
                            <div
                                class="relative flex-1 bg-primary/10 rounded-t-sm group hover:bg-primary/20 transition-all h-[85%]">
                            </div>
                            <div
                                class="relative flex-1 bg-primary/30 rounded-t-sm group hover:bg-primary/40 transition-all h-[50%]">
                            </div>
                            <div
                                class="relative flex-1 bg-primary/10 rounded-t-sm group hover:bg-primary/20 transition-all h-[35%]">
                            </div>
                            <div
                                class="relative flex-1 bg-primary/10 rounded-t-sm group hover:bg-primary/20 transition-all h-[25%]">
                            </div>
                            <div
                                class="relative flex-1 bg-primary/60 rounded-t-sm group hover:bg-primary/70 transition-all h-[75%]">
                            </div>
                        </div>
                        <div
                            class="flex justify-between mt-4 text-[10px] text-slate-400 font-bold px-2 uppercase tracking-tighter">
                            <span>Week 1</span>
                            <span>Week 2</span>
                            <span>Week 3</span>
                            <span>Week 4</span>
                        </div>
                    </div>
                    <!-- Distribution Section -->
                    <div
                        class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <h4 class="text-lg font-bold mb-6">Status Distribution</h4>
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-slate-600">Screening</span>
                                    <span class="font-bold">42%</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                    <div class="bg-amber-400 h-full w-[42%]"></div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-slate-600">Interview</span>
                                    <span class="font-bold">28%</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                    <div class="bg-primary h-full w-[28%]"></div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-slate-600">Offered</span>
                                    <span class="font-bold">15%</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                    <div class="bg-emerald-500 h-full w-[15%]"></div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-slate-600">Rejected</span>
                                    <span class="font-bold">15%</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                    <div class="bg-rose-500 h-full w-[15%]"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-10 p-4 rounded-lg bg-primary/5 border border-primary/10">
                            <p class="text-xs text-primary font-semibold flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">info</span>
                                Candidate Insight
                            </p>
                            <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1">Interview stages are
                                progressing 20% faster than last month for this department.</p>
                        </div>
                    </div>
                </div>
                <!-- Applicant Table -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div
                        class="p-6 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h4 class="text-lg font-bold">Recent Applicants</h4>
                            <p class="text-xs text-slate-400">Detailed list of applicants for the selected job listing
                            </p>
                        </div>
                        <div class="relative max-w-xs w-full">
                            <span
                                class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                            <input
                                class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-primary focus:border-primary"
                                placeholder="Search by name..." type="text" />
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50">
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                        Applicant Name</th>
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                        Applied Date</th>
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                        Department</th>
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                        Status</th>
                                    <th
                                        class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                                                AS</div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Alice
                                                    Sterling</p>
                                                <p class="text-[11px] text-slate-400">alice.s@university.edu</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Oct 24, 2023</td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Computer Science
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-amber-50 text-amber-600 border border-amber-100 uppercase">Screening</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-primary hover:text-primary/80 text-sm font-bold">View
                                            Profile</button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                                MR</div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Marcus
                                                    Revelli</p>
                                                <p class="text-[11px] text-slate-400">m.revelli@globaltech.com</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Oct 22, 2023</td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Computer Science
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-primary/10 text-primary border border-primary/10 uppercase">Interviewing</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-primary hover:text-primary/80 text-sm font-bold">View
                                            Profile</button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-xs">
                                                JP</div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Jane
                                                    Pearson</p>
                                                <p class="text-[11px] text-slate-400">jane.p@research.org</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Oct 20, 2023</td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Computer Science
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 uppercase">Offered</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-primary hover:text-primary/80 text-sm font-bold">View
                                            Profile</button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs">
                                                DL</div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900 dark:text-white">David
                                                    Lang</p>
                                                <p class="text-[11px] text-slate-400">dlang@university.edu</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Oct 18, 2023</td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Computer Science
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-slate-50 text-slate-500 border border-slate-200 uppercase">Withdrawn</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-primary hover:text-primary/80 text-sm font-bold">View
                                            Profile</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-6 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <p class="text-xs text-slate-500">Showing 4 of 1,284 results</p>
                        <div class="flex items-center gap-2">
                            <button
                                class="p-2 rounded border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50"
                                disabled="">
                                <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                            </button>
                            <button
                                class="p-2 rounded border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800">
                                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Footer -->
            <footer class="p-8 mt-auto border-t border-slate-200 dark:border-slate-800">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-xs text-slate-400">© 2024 RMS Academic. All rights reserved for project purposes.</p>
                    <div class="flex items-center gap-6">
                        <a class="text-xs font-medium text-slate-500 hover:text-primary transition-colors"
                            href="#">Privacy Policy</a>
                        <a class="text-xs font-medium text-slate-500 hover:text-primary transition-colors"
                            href="#">Terms of Service</a>
                        <a class="text-xs font-medium text-slate-500 hover:text-primary transition-colors" href="#">Help
                            Center</a>
                    </div>
                </div>
            </footer>
        </main>
    </div>
</body>

</html>