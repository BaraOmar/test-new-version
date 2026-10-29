<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Applicant Application Status Timeline</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200;300;400;500;600;700;800&amp;display=swap"
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
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .step-active-ring {
            box-shadow: 0 0 0 4px rgba(19, 127, 236, 0.2);
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-slate-900">
    <div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-x-hidden">
        <div class="layout-container flex h-full grow flex-col">
            <!-- Navigation Header -->
            <header
                class="flex items-center justify-between whitespace-nowrap border-b border-solid border-slate-200 bg-white px-6 md:px-20 py-3">
                <div class="flex items-center gap-8">
                    <div class="flex items-center gap-4 text-primary">
                        <div class="size-6">
                            <svg fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"></path>
                            </svg>
                        </div>
                        <h2 class="text-slate-900 text-lg font-bold leading-tight tracking-[-0.015em]">RMS Project</h2>
                    </div>
                    <nav class="hidden md:flex items-center gap-9">
                        <a class="text-slate-600 hover:text-primary text-sm font-medium leading-normal"
                            href="#">Dashboard</a>
                        <a class="text-slate-600 hover:text-primary text-sm font-medium leading-normal"
                            href="#">Jobs</a>
                        <a class="text-primary text-sm font-bold leading-normal border-b-2 border-primary py-1"
                            href="#">Applications</a>
                        <a class="text-slate-600 hover:text-primary text-sm font-medium leading-normal"
                            href="#">Profile</a>
                    </nav>
                </div>
                <div class="flex flex-1 justify-end gap-6 items-center">
                    <label class="hidden lg:flex flex-col min-w-40 h-10 max-w-64">
                        <div class="flex w-full flex-1 items-stretch rounded-lg h-full">
                            <div
                                class="text-slate-400 flex border-none bg-slate-100 items-center justify-center pl-4 rounded-l-lg">
                                <span class="material-symbols-outlined text-[20px]">search</span>
                            </div>
                            <input
                                class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 focus:outline-0 focus:ring-0 border-none bg-slate-100 focus:border-none h-full placeholder:text-slate-400 px-4 rounded-l-none pl-2 text-sm font-normal"
                                placeholder="Search applications..." value="" />
                        </div>
                    </label>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-slate-500 cursor-pointer">notifications</span>
                        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 border-2 border-slate-200"
                            data-alt="User profile avatar circle"
                            style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDv550HlYjS2FvkYQqnIz5BIKDFugTlgn0qkkvFWXRNGtotlxH_7BN4kC3D6dbPpVuVV-oPgZjUcQUmUq1lkG_Pa8rhvhjQTQap-hbP8-NKGDfamZCd-jNgIl8EC5of2B_6pu9X3yBro-ZgdT7wJCmpjOS-M63WGwYTEVv5aN6fXeWfTenTgtRUMmLlR9kxBx2Vnhy372vUnOLwgRMGQS-vYroY5G1V3wowqZPnMPXGeRuc8g-ffU-8ZzyKEj6LnwGMg1B2dIDjc8By");'>
                        </div>
                    </div>
                </div>
            </header>
            <main class="flex-1 flex flex-col max-w-[1200px] mx-auto w-full px-6 py-8">
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 mb-6">
                    <a class="text-slate-500 hover:text-primary text-sm font-medium transition-colors"
                        href="#">Dashboard</a>
                    <span class="material-symbols-outlined text-slate-400 text-sm">chevron_right</span>
                    <span class="text-slate-900 text-sm font-semibold">My Applications</span>
                </nav>
                <!-- Page Title -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
                    <div class="flex flex-col gap-1">
                        <h1 class="text-slate-900 text-3xl font-extrabold tracking-tight">Application Status</h1>
                        <p class="text-slate-500 text-base">Track your real-time recruitment progress across all applied
                            positions.</p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <span class="material-symbols-outlined text-[18px]">filter_list</span>
                            Filter
                        </button>
                        <button
                            class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg text-sm font-bold hover:bg-primary/90 shadow-sm shadow-primary/20 transition-all">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            New Application
                        </button>
                    </div>
                </div>
                <!-- Application List -->
                <div class="flex flex-col gap-6">
                    <!-- Card 1: Active Application -->
                    <div
                        class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8">
                                <div class="flex gap-4">
                                    <div
                                        class="size-14 rounded-lg bg-slate-100 flex items-center justify-center shrink-0 border border-slate-200">
                                        <span class="material-symbols-outlined text-primary text-3xl">school</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <h3
                                            class="text-slate-900 text-lg font-bold leading-tight group-hover:text-primary transition-colors">
                                            Senior Research Assistant</h3>
                                        <p class="text-slate-500 text-sm font-medium">Academic Center of Excellence •
                                            Full-time</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span
                                                class="material-symbols-outlined text-slate-400 text-[14px]">calendar_today</span>
                                            <span class="text-slate-400 text-xs">Applied on Oct 12, 2023</span>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-2 bg-primary/10 text-primary px-3 py-1.5 rounded-full self-start lg:self-center">
                                    <span class="size-2 bg-primary rounded-full animate-pulse"></span>
                                    <span class="text-xs font-bold uppercase tracking-wider">Interview Scheduled</span>
                                </div>
                            </div>
                            <!-- Horizontal Timeline Stepper -->
                            <div class="relative mb-8">
                                <!-- Background line -->
                                <div class="absolute top-5 left-0 w-full h-[2px] bg-slate-100 z-0"></div>
                                <div class="relative z-10 flex justify-between items-start">
                                    <!-- Step 1: Completed -->
                                    <div class="flex flex-col items-center gap-2 group/step cursor-pointer w-1/5">
                                        <div
                                            class="size-10 rounded-full bg-primary text-white flex items-center justify-center transition-transform hover:scale-110">
                                            <span class="material-symbols-outlined text-[20px]">check</span>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-slate-900 text-xs font-bold">Applied</p>
                                            <p class="text-slate-400 text-[10px]">Oct 12</p>
                                        </div>
                                    </div>
                                    <!-- Step 2: Completed -->
                                    <div class="flex flex-col items-center gap-2 group/step cursor-pointer w-1/5">
                                        <div
                                            class="size-10 rounded-full bg-primary text-white flex items-center justify-center transition-transform hover:scale-110">
                                            <span class="material-symbols-outlined text-[20px]">check</span>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-slate-900 text-xs font-bold">Under Review</p>
                                            <p class="text-slate-400 text-[10px]">Oct 15</p>
                                        </div>
                                    </div>
                                    <!-- Step 3: Current -->
                                    <div class="flex flex-col items-center gap-2 group/step cursor-pointer w-1/5">
                                        <div
                                            class="size-10 rounded-full bg-white border-2 border-primary text-primary flex items-center justify-center step-active-ring transition-transform hover:scale-110">
                                            <span class="material-symbols-outlined text-[20px]">event_available</span>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-primary text-xs font-bold">Interview</p>
                                            <p class="text-primary text-[10px] font-medium">Scheduled</p>
                                        </div>
                                    </div>
                                    <!-- Step 4: Future -->
                                    <div
                                        class="flex flex-col items-center gap-2 group/step cursor-not-allowed w-1/5 opacity-50">
                                        <div
                                            class="size-10 rounded-full bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[20px]">task_alt</span>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-slate-500 text-xs font-medium">Completed</p>
                                            <p class="text-slate-400 text-[10px]">TBD</p>
                                        </div>
                                    </div>
                                    <!-- Step 5: Future -->
                                    <div
                                        class="flex flex-col items-center gap-2 group/step cursor-not-allowed w-1/5 opacity-50">
                                        <div
                                            class="size-10 rounded-full bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[20px]">stars</span>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-slate-500 text-xs font-medium">Decision</p>
                                            <p class="text-slate-400 text-[10px]">TBD</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Detail Box -->
                            <div class="bg-slate-50 rounded-lg p-5 border border-slate-100">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="material-symbols-outlined text-primary text-[20px]">info</span>
                                    <p class="text-slate-900 text-sm font-bold tracking-tight">Interview Details</p>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <p class="text-slate-500 text-[11px] font-bold uppercase tracking-wider">Date
                                            &amp; Time</p>
                                        <p class="text-slate-800 text-sm">October 20, 2023 - 10:00 AM EST</p>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <p class="text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                                            Location</p>
                                        <p class="text-slate-800 text-sm">Main Campus Building B, Room 402</p>
                                    </div>
                                    <div class="md:col-span-2 flex flex-col gap-1 mt-1">
                                        <p class="text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                                            Recruiter Feedback</p>
                                        <p class="text-slate-700 text-sm italic leading-relaxed">"The candidate's
                                            profile matches our research requirements perfectly. We're looking forward
                                            to discussing their previous work in AI and data analysis."</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Card 2: Initial Phase -->
                    <div
                        class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8">
                                <div class="flex gap-4">
                                    <div
                                        class="size-14 rounded-lg bg-slate-100 flex items-center justify-center shrink-0 border border-slate-200">
                                        <span class="material-symbols-outlined text-primary text-3xl">biotech</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <h3
                                            class="text-slate-900 text-lg font-bold leading-tight group-hover:text-primary transition-colors">
                                            Lab Technician</h3>
                                        <p class="text-slate-500 text-sm font-medium">Genetics Lab Division • Part-time
                                        </p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span
                                                class="material-symbols-outlined text-slate-400 text-[14px]">calendar_today</span>
                                            <span class="text-slate-400 text-xs">Applied on Oct 14, 2023</span>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-2 bg-slate-100 text-slate-600 px-3 py-1.5 rounded-full self-start lg:self-center">
                                    <span class="size-2 bg-slate-400 rounded-full"></span>
                                    <span class="text-xs font-bold uppercase tracking-wider">Under Review</span>
                                </div>
                            </div>
                            <!-- Horizontal Timeline Stepper -->
                            <div class="relative">
                                <div class="absolute top-5 left-0 w-full h-[2px] bg-slate-100 z-0"></div>
                                <div class="relative z-10 flex justify-between items-start">
                                    <!-- Step 1: Completed -->
                                    <div class="flex flex-col items-center gap-2 w-1/5">
                                        <div
                                            class="size-10 rounded-full bg-primary text-white flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[20px]">check</span>
                                        </div>
                                        <p class="text-slate-900 text-xs font-bold text-center">Applied</p>
                                    </div>
                                    <!-- Step 2: Active -->
                                    <div class="flex flex-col items-center gap-2 w-1/5">
                                        <div
                                            class="size-10 rounded-full bg-white border-2 border-primary text-primary flex items-center justify-center step-active-ring">
                                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                                        </div>
                                        <p class="text-primary text-xs font-bold text-center">Under Review</p>
                                    </div>
                                    <!-- Rest are future -->
                                    <div class="flex flex-col items-center gap-2 w-1/5 opacity-40">
                                        <div
                                            class="size-10 rounded-full bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                                        </div>
                                        <p class="text-slate-500 text-xs font-medium text-center">Interview</p>
                                    </div>
                                    <div class="flex flex-col items-center gap-2 w-1/5 opacity-40">
                                        <div
                                            class="size-10 rounded-full bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[20px]">task_alt</span>
                                        </div>
                                        <p class="text-slate-500 text-xs font-medium text-center">Completed</p>
                                    </div>
                                    <div class="flex flex-col items-center gap-2 w-1/5 opacity-40">
                                        <div
                                            class="size-10 rounded-full bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[20px]">stars</span>
                                        </div>
                                        <p class="text-slate-500 text-xs font-medium text-center">Decision</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Empty State Illustration (Hidden by default, shown if list is empty) -->
                <div class="hidden flex flex-col items-center justify-center py-20 px-4">
                    <div class="w-full max-w-sm mb-6 flex justify-center">
                        <div
                            class="size-48 rounded-full bg-primary/5 flex items-center justify-center border-2 border-dashed border-primary/20">
                            <span class="material-symbols-outlined text-primary/40 text-[96px]">work_history</span>
                        </div>
                    </div>
                    <h2 class="text-slate-900 text-2xl font-bold mb-2">No applications found</h2>
                    <p class="text-slate-500 text-center max-w-md mb-8">You haven't submitted any job applications yet.
                        Browse our current job openings to start your professional journey.</p>
                    <button
                        class="bg-primary text-white font-bold px-8 py-3 rounded-lg hover:bg-primary/90 shadow-lg shadow-primary/20 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined">explore</span>
                        Browse All Jobs
                    </button>
                </div>
                <!-- Footer Stats / Summary -->
                <div class="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-4 border-t border-slate-200 pt-8">
                    <div class="bg-white p-4 rounded-lg border border-slate-100 shadow-sm flex items-center gap-4">
                        <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary">send</span>
                        </div>
                        <div>
                            <p class="text-slate-500 text-xs font-medium">Total Applied</p>
                            <p class="text-slate-900 text-xl font-bold leading-none">02</p>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-slate-100 shadow-sm flex items-center gap-4">
                        <div class="size-10 rounded-full bg-amber-100 flex items-center justify-center">
                            <span class="material-symbols-outlined text-amber-600">hourglass_empty</span>
                        </div>
                        <div>
                            <p class="text-slate-500 text-xs font-medium">In Progress</p>
                            <p class="text-slate-900 text-xl font-bold leading-none">02</p>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-slate-100 shadow-sm flex items-center gap-4">
                        <div class="size-10 rounded-full bg-emerald-100 flex items-center justify-center">
                            <span class="material-symbols-outlined text-emerald-600">verified</span>
                        </div>
                        <div>
                            <p class="text-slate-500 text-xs font-medium">Offers Received</p>
                            <p class="text-slate-900 text-xl font-bold leading-none">00</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>