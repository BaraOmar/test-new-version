<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/validation.php";

require_once __DIR__ . "/config/auth.php";
requireLogin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$employee_id = (int)$_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Invalid request');
    }

    $urgency   = validate_whitelist($_POST['urgency'] ?? '', ['Normal', 'Urgent'], 'Normal');
    $old_value = validate_string($_POST['current_text'] ?? '', 5000);
    $new_value = validate_string($_POST['new_text'] ?? '', 5000);

    if ($new_value !== '') {

        $stmt = $pdo->prepare("
            INSERT INTO employee_change_requests
            (employee_id, old_value, new_value, urgency, status)
            VALUES (?, ?, ?, ?, 'Pending')
        ");

        $stmt->execute([
            $employee_id,
            $old_value,
            $new_value,
            $urgency
        ]);

        $success = "Request submitted successfully.";
    } else {
        $error = "New content cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Employee Website Change Request Form</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <link href="assets/hrms-theme.css" rel="stylesheet" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#642754",
                        "primary-dark": "#58264a",
                        accent: "#eb652c",
                        "accent-dark": "#d85a24",
                        "background-light": "#f8f4f8",
                    },
                    fontFamily: { sans: ["Inter", "sans-serif"] },
                    borderRadius: { DEFAULT: "0.5rem", xl: "1rem", "2xl": "1.25rem" },
                },
            },
        };
    </script>
</head>

<body class="bg-[#f8f4f8] font-sans antialiased min-h-screen flex flex-col">
    <?php $currentPage = 'my_profile'; $pageTitle = 'Change Request'; include __DIR__ . '/employee_navbar.php'; ?>

    <main class="flex-1 flex items-center justify-center p-6 lg:p-8">
        <div class="max-w-3xl w-full bg-white shadow-xl rounded-2xl overflow-hidden border border-slate-200">
            <div class="bg-[#642754] px-8 py-6">
                <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                    <span class="material-symbols-outlined">edit_document</span>
                    Website Change Request Form
                </h1>
                <p class="text-white/90 mt-2 text-sm">Please fill out the details below to request updates to the
                    employee portal or public website.</p>
            </div>
            <div class="p-8">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="name">
                                Your Name <span class="text-red-500">*</span>
                            </label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-slate-400 text-lg">person</span>
                                </div>
                                <input
                                    class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl leading-5 bg-white text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#eb652c]/30 focus:border-[#eb652c] sm:text-sm transition-colors"
                                    id="name" name="name" placeholder="Name" required type="text" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="email">
                                Your Email
                            </label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-slate-400 text-lg">mail</span>
                                </div>
                                <input
                                    class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl leading-5 bg-white text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#eb652c]/30 focus:border-[#eb652c] sm:text-sm transition-colors"
                                    id="email" name="email" placeholder="Company Email" type="email" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                        <div class="hidden md:block md:col-span-3">
                            <label class="block text-sm font-medium text-slate-700" for="urgency">How Urgent</label>
                        </div>
                        <div class="md:col-span-9 w-full">
                            <label class="md:hidden block text-sm font-medium text-slate-700 mb-1" for="urgency">How Urgent</label>
                            <select
                                class="block w-full pl-3 pr-10 py-2.5 text-base border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#eb652c]/30 focus:border-[#eb652c] sm:text-sm rounded-xl bg-white text-slate-900"
                                id="urgency" name="urgency">
                                <option>Normal</option>
                                <option>Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 pt-4 border-t border-slate-200">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2" for="current-text">
                                CURRENT Text / Content:
                            </label>
                            <textarea
                                class="shadow-sm focus:ring-[#eb652c]/30 focus:border-[#eb652c] block w-full sm:text-sm border border-slate-200 rounded-xl bg-white text-slate-900 placeholder-slate-400 py-2.5 px-3"
                                id="current-text" name="current_text" placeholder="Paste the current content here..."
                                rows="4"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2" for="new-text">
                                NEW Text / Content:
                            </label>
                            <textarea
                                class="shadow-sm focus:ring-[#eb652c]/30 focus:border-[#eb652c] block w-full sm:text-sm border border-slate-200 rounded-xl bg-white text-slate-900 placeholder-slate-400 py-2.5 px-3"
                                id="new-text" name="new_text" placeholder="Enter the new content here..."
                                rows="4"></textarea>
                        </div>
                    </div>

                    <?php if (!empty($success)): ?>
                    <div class="p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200">
                        <?= htmlspecialchars($success) ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                    <div class="p-4 bg-red-50 text-red-700 rounded-xl border border-red-200">
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>

                    <div class="pt-6 border-t border-slate-200 flex justify-end">
                        <button
                            class="inline-flex justify-center items-center py-2.5 px-6 shadow-md text-sm font-bold rounded-xl text-white bg-[#eb652c] hover:bg-[#d85a24] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#eb652c]/50 transition-all"
                            type="submit">
                            <span class="material-symbols-outlined text-sm mr-2">send</span>
                            Send Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

</body>

</html>
