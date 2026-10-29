<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/auth.php";
requireHRAdmin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Career Path &amp; Skills Module</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&amp;display=swap"
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
                        "sidebar-bg": "#ffffff",
                        "sidebar-active": "#f3e8ff",
                        "background-light": "#f7f6f8",
                        "background-dark": "#191022",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
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

        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
    </style>
</head>

<body class="bg-[#f8f4f8] text-slate-900 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <?php $currentPage = 'career_paths'; include __DIR__ . '/navbar.php'; ?>
        <main class="flex-1 flex flex-col overflow-hidden">
            <?php $pageTitle = 'Career Paths'; include __DIR__ . '/admin_header.php'; ?>
            <div class="max-w-7xl mx-auto px-4 md:px-8 py-8 w-full flex-1 overflow-y-auto">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
                    <div class="max-w-2xl">
                        <div class="flex items-center gap-2 text-primary text-sm font-semibold mb-1">
                            <span class="material-symbols-outlined text-sm">auto_graph</span>
                            Career &amp; Development
                        </div>
                        <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white">Career Path &amp; Skills
                            Matrix</h1>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">Map competencies, define growth tracks, and
                            track technical skill evolution across your engineering and management teams.</p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            class="flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-semibold rounded-lg hover:bg-slate-50 transition-all shadow-sm">
                            <span class="material-symbols-outlined text-lg">download</span>
                            Export Report
                        </button>
                        <button
                            class="flex items-center gap-2 px-4 py-2.5 bg-[#eb652c] text-white font-semibold rounded-lg hover:bg-[#d85a24] transition-all shadow-md">
                            <span class="material-symbols-outlined text-lg">grid_view</span>
                            Visual Skill Matrix
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div
                        class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Total Employees</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-2xl font-bold">1,248</span>
                            <span class="text-emerald-500 text-xs font-medium flex items-center">+12%</span>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Advancing Levels</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-2xl font-bold">342</span>
                            <span class="text-primary text-xs font-medium uppercase tracking-tighter">Current
                                Quarter</span>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Avg Skill Density</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-2xl font-bold">Inter.</span>
                            <span class="text-slate-400 text-xs font-medium">Level 2.4</span>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Skill Gap Alert</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-2xl font-bold">14</span>
                            <span class="text-amber-500 text-xs font-medium">Needs Review</span>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
                    <div
                        class="flex flex-col lg:flex-row items-center justify-between border-b border-slate-100 dark:border-slate-800 px-6 py-4 gap-4">
                        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg self-start">
                            <button
                                class="px-4 py-1.5 text-sm font-semibold rounded-md bg-white dark:bg-slate-700 shadow-sm text-primary">All
                                Tracks</button>
                            <button
                                class="px-4 py-1.5 text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400">Technical</button>
                            <button
                                class="px-4 py-1.5 text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400">Management</button>
                        </div>
                        <div class="flex items-center gap-3 w-full lg:w-auto">
                            <div
                                class="flex items-center gap-2 text-sm text-slate-500 px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors">
                                <span class="material-symbols-outlined text-lg">filter_alt</span>
                                <span>Department</span>
                            </div>
                            <div
                                class="flex items-center gap-2 text-sm text-slate-500 px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors">
                                <span class="material-symbols-outlined text-lg">sort</span>
                                <span>Level</span>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr
                                    class="bg-slate-50/50 dark:bg-slate-800/50 text-slate-500 text-[11px] font-bold uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">
                                    <th class="px-6 py-4">Employee</th>
                                    <th class="px-6 py-4">Current Track</th>
                                    <th class="px-6 py-4">Skill Level</th>
                                    <th class="px-6 py-4">Progress</th>
                                    <th class="px-6 py-4">Last Update</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="size-9 rounded-full bg-indigo-100 dark:bg-indigo-900/30 overflow-hidden border border-indigo-200/50">
                                                <div
                                                    class="w-full h-full flex items-center justify-center text-indigo-600 font-bold text-xs">
                                                    SM</div>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-900 dark:text-white">Sarah Miller
                                                </p>
                                                <p class="text-xs text-slate-500">Senior Engineer</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2.5 py-1 text-[11px] font-bold bg-[#eb652c]/10 text-[#eb652c] rounded-full uppercase">Technical
                                            IC</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="text-sm font-medium text-slate-700 dark:text-slate-300">Advanced</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1 w-32">
                                            <div class="flex gap-1 h-2">
                                                <div class="flex-1 bg-[#eb652c] rounded-full"></div>
                                                <div class="flex-1 bg-[#eb652c] rounded-full"></div>
                                                <div class="flex-1 bg-[#eb652c] rounded-full"></div>
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-medium">92% Core
                                                Competency</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-500">Oct 12, 2023</td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-sm font-bold text-primary hover:underline">Update
                                            Progress</button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="size-9 rounded-full bg-emerald-100 dark:bg-emerald-900/30 overflow-hidden border border-emerald-200/50">
                                                <div
                                                    class="w-full h-full flex items-center justify-center text-emerald-600 font-bold text-xs">
                                                    AJ</div>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-900 dark:text-white">Alex Johnson
                                                </p>
                                                <p class="text-xs text-slate-500">Product Manager</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2.5 py-1 text-[11px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full uppercase">People
                                            Mgmt</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="text-sm font-medium text-slate-700 dark:text-slate-300">Intermediate</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1 w-32">
                                            <div class="flex gap-1 h-2">
                                                <div class="flex-1 bg-[#eb652c] rounded-full"></div>
                                                <div class="flex-1 bg-[#eb652c] rounded-full"></div>
                                                <div class="flex-1 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-medium">Level 4/6
                                                Achieved</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-500">Nov 04, 2023</td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-sm font-bold text-primary hover:underline">Update
                                            Progress</button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="size-9 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden border border-slate-200/50">
                                                <div
                                                    class="w-full h-full flex items-center justify-center text-slate-600 font-bold text-xs">
                                                    KW</div>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-900 dark:text-white">Kevin White
                                                </p>
                                                <p class="text-xs text-slate-500">Junior Developer</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2.5 py-1 text-[11px] font-bold bg-[#eb652c]/10 text-[#eb652c] rounded-full uppercase">Technical
                                            IC</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="text-sm font-medium text-slate-700 dark:text-slate-300">Beginner</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1 w-32">
                                            <div class="flex gap-1 h-2">
                                                <div class="flex-1 bg-[#eb652c] rounded-full"></div>
                                                <div class="flex-1 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                                                <div class="flex-1 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-medium">Foundational
                                                Skills</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-500">Dec 01, 2023</td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-sm font-bold text-primary hover:underline">Update
                                            Progress</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div
                        class="flex items-center justify-between px-6 py-4 bg-slate-50/30 dark:bg-slate-800/20 border-t border-slate-100 dark:border-slate-800">
                        <p class="text-sm text-slate-500">Showing <span
                                class="font-medium text-slate-900 dark:text-white">1-3</span> of <span
                                class="font-medium text-slate-900 dark:text-white">1,248</span> employees</p>
                        <div class="flex gap-2">
                            <button
                                class="p-2 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-white transition-colors disabled:opacity-50"
                                disabled="">
                                <span class="material-symbols-outlined text-lg">chevron_left</span>
                            </button>
                            <button
                                class="p-2 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-white transition-colors">
                                <span class="material-symbols-outlined text-lg">chevron_right</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mt-12 grid grid-cols-1 lg:grid-cols-3 gap-8 pb-12">
                    <div class="lg:col-span-2">
                        <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">analytics</span>
                            Skill Distribution Matrix
                        </h3>
                        <div
                            class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-8 h-64 relative flex items-center justify-center overflow-hidden">
                            <div class="absolute inset-0 opacity-10 flex flex-wrap">
                                <div class="w-1/4 h-1/4 border border-[#eb652c]/20"></div>
                                <div class="w-1/4 h-1/4 border border-[#eb652c]/20 bg-[#eb652c]/20"></div>
                                <div class="w-1/4 h-1/4 border border-[#eb652c]/20"></div>
                                <div class="w-1/4 h-1/4 border border-[#eb652c]/20"></div>
                                <div class="w-1/4 h-1/4 border border-[#eb652c]/20 bg-[#eb652c]/40"></div>
                                <div class="w-1/4 h-1/4 border border-[#eb652c]/20"></div>
                                <div class="w-1/4 h-1/4 border border-[#eb652c]/20 bg-[#eb652c]/20"></div>
                                <div class="w-1/4 h-1/4 border border-[#eb652c]/20"></div>
                            </div>
                            <div class="relative z-10 text-center">
                                <p class="text-slate-500 font-medium max-w-sm">
                                    Open the <span class="text-primary font-bold">Visual Skill Matrix</span> to see
                                    detailed competency mapping across departments.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">history</span>
                            Recent Activity
                        </h3>
                        <div class="space-y-4">
                            <div
                                class="flex gap-4 p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                                <div
                                    class="size-8 rounded bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                                    <span class="material-symbols-outlined text-sm">trending_up</span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">Sarah Miller promoted</p>
                                    <p class="text-xs text-slate-500 italic">Advanced Level Achieved in Backend
                                        Architecture</p>
                                </div>
                            </div>
                            <div
                                class="flex gap-4 p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                                <div class="size-8 rounded bg-[#eb652c]/10 flex items-center justify-center text-[#eb652c]">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">Kevin White track updated</p>
                                    <p class="text-xs text-slate-500 italic">Assigned to Technical IC Career Path</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>

</html>