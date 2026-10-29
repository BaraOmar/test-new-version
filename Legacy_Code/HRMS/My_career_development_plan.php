<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/auth.php";
requireLogin();
?>
<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>My Career &amp; Development Path</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200;300;400;500;600;700;800&amp;display=swap"
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
                        "primary": "#642754",
                        "background-light": "#f6f6f8",
                        "background-dark": "#141121",
                        "accent-teal": "#2dd4bf",
                    },
                    fontFamily: {
                        "display": ["Manrope"]
                    },
                    borderRadius: { "DEFAULT": "0.5rem", "lg": "1rem", "xl": "1.5rem", "full": "9999px" },
                },
            },
        }
    </script>
</head>

<body class="bg-[#f8f4f8] font-display text-slate-900">
    <div class="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
        <div class="layout-container flex h-full grow flex-col">
            <?php $currentPage = 'my_development'; $pageTitle = 'My Development'; include __DIR__ . '/employee_navbar.php'; ?>
            <main class="flex flex-col lg:flex-row gap-6 px-10 py-8">
                <aside class="flex flex-col w-full lg:w-72 gap-6">
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-slate-200 dark:border-slate-800">
                        <div class="flex flex-col items-center text-center">
                            <div class="size-20 rounded-full bg-primary/10 flex items-center justify-center mb-4">
                                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-16"
                                    data-alt="User profile portrait thumbnail"
                                    style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBNt70hvblWFAksCC0MoUUmspyh63F-Bg5h6iqi_TcGg_nl6oidaRBB6DKM5-2F5MfFkwsF6iwEh8Y4QkccjR1-kK2S1uG8BGY-OvnmhMA6w57XfDp2N-WOLvSlyhmIa_2kI6pIAzyh3rAkzl9osZ6QQY-ui_LN6EbYAALbFqEtYatcQoWggivGz3voPeFYnda8mRGqZV4ZFdBsZJl_OZ36dInOTT4AS2wAmJY32o2TDknTpRiVdkqa1wUqsp_2hS4w2Ciy_ryzpqHj");'>
                                </div>
                            </div>
                            <h1 class="text-slate-900 dark:text-white text-xl font-bold">Alex Johnson</h1>
                            <p class="text-slate-500 text-sm font-medium mb-4">Senior Product Designer</p>
                            <div class="flex gap-2 w-full">
                                <div
                                    class="flex-1 bg-primary/10 text-primary rounded-lg py-2 text-xs font-bold uppercase tracking-wider">
                                    Level 4</div>
                                <div
                                    class="flex-1 bg-accent-teal/10 text-accent-teal rounded-lg py-2 text-xs font-bold uppercase tracking-wider">
                                    Product</div>
                            </div>
                        </div>
                    </div>
                    <nav
                        class="bg-white dark:bg-slate-900 rounded-xl p-2 shadow-sm border border-slate-200 dark:border-slate-800">
                        <a class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all"
                            href="#">
                            <span class="material-symbols-outlined">grid_view</span>
                            <span class="font-medium">Overview</span>
                        </a>
                        <a class="flex items-center gap-3 px-4 py-3 rounded-lg bg-[#eb652c] text-white shadow-md hover:bg-[#d85a24] transition-all"
                            href="#">
                            <span class="material-symbols-outlined">map</span>
                            <span class="font-bold">Career Path</span>
                        </a>
                        <a class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all"
                            href="#">
                            <span class="material-symbols-outlined">radar</span>
                            <span class="font-medium">Skill Matrix</span>
                        </a>
                        <a class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all"
                            href="#">
                            <span class="material-symbols-outlined">track_changes</span>
                            <span class="font-medium">Growth Plan</span>
                        </a>
                        <a class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all"
                            href="#">
                            <span class="material-symbols-outlined">group</span>
                            <span class="font-medium">Mentorship</span>
                        </a>
                    </nav>
                </aside>
                <div class="flex-1 flex flex-col gap-8">
                    <section
                        class="bg-white dark:bg-slate-900 rounded-xl p-8 shadow-sm border border-slate-200 dark:border-slate-800">
                        <div class="flex justify-between items-center mb-10">
                            <div>
                                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">Career
                                    Timeline</h2>
                                <p class="text-slate-500">Your professional evolution at the company</p>
                            </div>
                            <button
                                class="bg-primary/10 text-primary px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">download</span>
                                Export Path
                            </button>
                        </div>
                        <div class="relative pt-8 pb-4">
                            <div
                                class="absolute top-[48px] left-0 right-0 h-1 bg-slate-100 dark:bg-slate-800 rounded-full">
                            </div>
                            <div class="absolute top-[48px] left-0 w-[66%] h-1 bg-primary rounded-full"></div>
                            <div class="flex justify-between relative z-10">
                                <div class="flex flex-col items-center text-center max-w-[120px]">
                                    <div
                                        class="size-6 rounded-full bg-primary border-4 border-white dark:border-slate-900 ring-4 ring-primary/20 mb-4">
                                    </div>
                                    <p class="font-bold text-slate-900 dark:text-white text-sm">Junior Designer</p>
                                    <p class="text-slate-400 text-xs mt-1">Jan 2021</p>
                                </div>
                                <div class="flex flex-col items-center text-center max-w-[120px]">
                                    <div
                                        class="size-6 rounded-full bg-primary border-4 border-white dark:border-slate-900 ring-4 ring-primary/20 mb-4">
                                    </div>
                                    <p class="font-bold text-slate-900 dark:text-white text-sm">Product Designer</p>
                                    <p class="text-slate-400 text-xs mt-1">Mar 2022</p>
                                </div>
                                <div class="flex flex-col items-center text-center max-w-[120px]">
                                    <div
                                        class="size-8 rounded-full bg-primary border-4 border-white dark:border-slate-900 ring-4 ring-primary/30 mb-4 flex items-center justify-center -mt-1">
                                        <span class="material-symbols-outlined text-white text-xs">person</span>
                                    </div>
                                    <p class="font-bold text-primary text-sm">Senior Designer</p>
                                    <p class="text-primary/60 text-xs font-semibold mt-1">Present</p>
                                </div>
                                <div class="flex flex-col items-center text-center max-w-[120px]">
                                    <div
                                        class="size-6 rounded-full bg-slate-100 dark:bg-slate-800 border-2 border-dashed border-slate-400 mb-4 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-slate-400 text-xs">star</span>
                                    </div>
                                    <p class="font-bold text-slate-400 text-sm">Lead Designer</p>
                                    <p class="text-slate-400 text-xs mt-1">Next Goal</p>
                                </div>
                            </div>
                            <div
                                class="absolute top-[48px] left-[66%] right-[10%] h-1 border-t-2 border-dashed border-primary/40">
                            </div>
                        </div>
                    </section>
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                        <section
                            class="bg-white dark:bg-slate-900 rounded-xl p-8 shadow-sm border border-slate-200 dark:border-slate-800 flex flex-col items-center">
                            <div class="w-full mb-6">
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Skill Matrix</h3>
                                <p class="text-slate-500 text-sm">Current vs Target Proficiency</p>
                            </div>
                            <div class="relative size-64 flex items-center justify-center">
                                <div
                                    class="absolute inset-0 border-[16px] border-slate-100 dark:border-slate-800 rounded-full opacity-20">
                                </div>
                                <div
                                    class="absolute inset-4 border-[1px] border-slate-300 dark:border-slate-700 rounded-full opacity-30">
                                </div>
                                <div
                                    class="absolute inset-10 border-[1px] border-slate-300 dark:border-slate-700 rounded-full opacity-30">
                                </div>
                                <div
                                    class="absolute inset-16 border-[1px] border-slate-300 dark:border-slate-700 rounded-full opacity-30">
                                </div>
                                <svg class="size-64 -rotate-90">
                                    <polygon class="transition-all duration-700" fill="rgba(102, 76, 230, 0.2)"
                                        points="128,40 216,100 190,200 66,200 40,100" stroke="#642754" stroke-width="2">
                                    </polygon>
                                    <polygon class="transition-all duration-700 opacity-60" fill="none"
                                        points="128,10 240,90 200,220 56,220 16,90" stroke="#2dd4bf"
                                        stroke-dasharray="4" stroke-width="2"></polygon>
                                </svg>
                                <div class="absolute -top-6 font-bold text-xs uppercase text-slate-500">Technical</div>
                                <div class="absolute -right-8 top-1/3 font-bold text-xs uppercase text-slate-500">
                                    Leadership</div>
                                <div class="absolute -bottom-6 right-8 font-bold text-xs uppercase text-slate-500">
                                    Strategy</div>
                                <div class="absolute -bottom-6 left-8 font-bold text-xs uppercase text-slate-500">
                                    Product</div>
                                <div class="absolute -left-8 top-1/3 font-bold text-xs uppercase text-slate-500">Design
                                </div>
                            </div>
                            <div class="flex gap-6 mt-12 w-full justify-center">
                                <div class="flex items-center gap-2">
                                    <div class="size-3 rounded-full bg-primary"></div>
                                    <span class="text-sm font-medium">Senior Designer</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="size-3 rounded-full border-2 border-dashed border-accent-teal"></div>
                                    <span class="text-sm font-medium">Lead Designer Target</span>
                                </div>
                            </div>
                        </section>
                        <section
                            class="bg-white dark:bg-slate-900 rounded-xl p-8 shadow-sm border border-slate-200 dark:border-slate-800">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6">Development Plan</h3>
                            <div class="space-y-4">
                                <div
                                    class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="size-10 rounded-lg bg-accent-teal/10 flex items-center justify-center text-accent-teal">
                                                <span class="material-symbols-outlined">psychology</span>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-slate-900 dark:text-white">Leadership
                                                    Fundamentals</h4>
                                                <p class="text-xs text-slate-500">Bridge gap: People Management</p>
                                            </div>
                                        </div>
                                        <span
                                            class="text-xs font-bold text-accent-teal bg-accent-teal/10 px-2 py-1 rounded">75%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full">
                                        <div class="h-full bg-accent-teal rounded-full" style="width: 75%"></div>
                                    </div>
                                </div>
                                <div
                                    class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="size-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                                <span class="material-symbols-outlined">architecture</span>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-slate-900 dark:text-white">Design Systems at
                                                    Scale</h4>
                                                <p class="text-xs text-slate-500">Bridge gap: Technical Infrastructure
                                                </p>
                                            </div>
                                        </div>
                                        <span
                                            class="text-xs font-bold text-primary bg-primary/10 px-2 py-1 rounded">40%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full">
                                        <div class="h-full bg-primary rounded-full" style="width: 40%"></div>
                                    </div>
                                </div>
                                <div class="mt-8">
                                    <h4
                                        class="text-sm font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wider">
                                        Recommended Courses</h4>
                                    <div class="space-y-3">
                                        <div
                                            class="flex items-center justify-between p-3 rounded-xl border border-dashed border-slate-200 dark:border-slate-700">
                                            <div class="flex items-center gap-3">
                                                <span class="material-symbols-outlined text-slate-400">school</span>
                                                <span
                                                    class="text-sm font-medium text-slate-700 dark:text-slate-300">Strategic
                                                    Decision Making</span>
                                            </div>
                                            <button class="text-primary hover:bg-primary/5 p-1 rounded-full">
                                                <span class="material-symbols-outlined">chevron_right</span>
                                            </button>
                                        </div>
                                        <div
                                            class="flex items-center justify-between p-3 rounded-xl border border-dashed border-slate-200 dark:border-slate-700">
                                            <div class="flex items-center gap-3">
                                                <span class="material-symbols-outlined text-slate-400">school</span>
                                                <span
                                                    class="text-sm font-medium text-slate-700 dark:text-slate-300">Advanced
                                                    Figma Prototyping</span>
                                            </div>
                                            <button class="text-primary hover:bg-primary/5 p-1 rounded-full">
                                                <span class="material-symbols-outlined">chevron_right</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                    <section
                        class="bg-[#642754] rounded-xl p-8 shadow-md flex flex-col md:flex-row justify-between items-center text-white">
                        <div class="mb-6 md:mb-0">
                            <h3 class="text-2xl font-bold mb-2">Ready for your next feedback cycle?</h3>
                            <p class="text-white/80 max-w-md">Schedule a growth-focused check-in with your manager to
                                discuss your Skill Matrix and target goals.</p>
                        </div>
                        <button
                            class="bg-white text-[#eb652c] border-2 border-[#eb652c] px-8 py-4 rounded-xl font-bold shadow-xl hover:bg-[#eb652c]/5 transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined">calendar_today</span>
                            Schedule 1:1
                        </button>
                    </section>
                </div>
            </main>
            <footer
                class="mt-auto px-10 py-8 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark text-center">
                <p class="text-slate-500 text-sm">© 2024 GrowthPlatform. Empowing people through continuous development.
                </p>
            </footer>
        </div>
    </div>
</body>

</html>