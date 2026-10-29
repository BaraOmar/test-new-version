<?php
session_start();

/* ======================
   AUTH: Applicant only
====================== */
if (!isset($_SESSION["user_id"]) || ($_SESSION["role_id"] ?? null) != 2) {
    header("Location: Applicant_login_Page.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

/* ======================
   DB CONNECTION (PDO)
   - Adjust path if needed
====================== */
require_once __DIR__ . "/config/database.php"; // uses $pdo from your database.php

/* ======================
   UPLOAD SETTINGS
====================== */
$UPLOAD_DIR = __DIR__ . "/uploads/resumes/";
$MAX_SIZE = 5 * 1024 * 1024; // 5MB
$ALLOWED_EXT = ["pdf", "doc", "docx"];
$ALLOWED_MIME = [
    "application/pdf",
    "application/msword",
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
];

if (!is_dir($UPLOAD_DIR)) {
    @mkdir($UPLOAD_DIR, 0775, true);
}

/* ======================
   HANDLE SAVE PROFILE
====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_profile"])) {
    $full_name = trim($_POST["full_name"] ?? "");
    $phone     = trim($_POST["phone"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $headline  = trim($_POST["headline"] ?? "");
    $bio       = trim($_POST["bio"] ?? "");

    $stmt = $pdo->prepare("
        UPDATE users
        SET full_name=?, phone=?, email=?, headline=?, bio=?
        WHERE user_id=?
    ");
    $stmt->execute([$full_name, $phone, $email, $headline, $bio, $user_id]);

    header("Location: applicant_profile.php?saved=1");
    exit;
}

/* ======================
   HANDLE RESUME UPLOAD
====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["upload_resume"])) {
    if (!isset($_FILES["resume_file"]) || $_FILES["resume_file"]["error"] !== UPLOAD_ERR_OK) {
        header("Location: applicant_profile.php?upload=0");
        exit;
    }

    $f = $_FILES["resume_file"];
    if ($f["size"] > $MAX_SIZE) {
        header("Location: applicant_profile.php?upload=oversize");
        exit;
    }

    $originalName = $f["name"];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $ALLOWED_EXT, true)) {
        header("Location: applicant_profile.php?upload=badext");
        exit;
    }

    $mime = mime_content_type($f["tmp_name"]) ?: "";
    if (!in_array($mime, $ALLOWED_MIME, true)) {
        header("Location: applicant_profile.php?upload=badmime");
        exit;
    }

    // Build safe stored name
    $storedName = "resume_u{$user_id}_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4)) . "." . $ext;
    $storedPath = $UPLOAD_DIR . $storedName;

    if (!move_uploaded_file($f["tmp_name"], $storedPath)) {
        header("Location: applicant_profile.php?upload=fail");
        exit;
    }

    // Remove old resume file if exists
    $old = $pdo->prepare("SELECT resume_path FROM users WHERE user_id=?");
    $old->execute([$user_id]);
    $oldPath = (string)($old->fetchColumn() ?: "");
    if ($oldPath && file_exists(__DIR__ . "/" . $oldPath)) {
        @unlink(__DIR__ . "/" . $oldPath);
    }

    // Save new path in DB (relative path)
    $relative = "uploads/resumes/" . $storedName;
    $stmt = $pdo->prepare("UPDATE users SET resume_path=?, resume_original_name=?, resume_uploaded_at=NOW() WHERE user_id=?");
    $stmt->execute([$relative, $originalName, $user_id]);

    header("Location: applicant_profile.php?upload=1");
    exit;
}

/* ======================
   HANDLE RESUME DOWNLOAD
====================== */
if (isset($_GET["download_resume"])) {
    $stmt = $pdo->prepare("SELECT resume_path, resume_original_name FROM users WHERE user_id=?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $resumePath = (string)($row["resume_path"] ?? "");
    $original   = (string)($row["resume_original_name"] ?? "resume");

    if (!$resumePath || !file_exists(__DIR__ . "/" . $resumePath)) {
        http_response_code(404);
        echo "Resume not found.";
        exit;
    }

    $fullPath = __DIR__ . "/" . $resumePath;
    $mime = mime_content_type($fullPath) ?: "application/octet-stream";

    header("Content-Type: $mime");
    header('Content-Disposition: attachment; filename="' . basename($original) . '"');
    header("Content-Length: " . filesize($fullPath));
    readfile($fullPath);
    exit;
}

/* ======================
   HANDLE RESUME DELETE
====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_resume"])) {
    $stmt = $pdo->prepare("SELECT resume_path FROM users WHERE user_id=?");
    $stmt->execute([$user_id]);
    $resumePath = (string)($stmt->fetchColumn() ?: "");

    if ($resumePath && file_exists(__DIR__ . "/" . $resumePath)) {
        @unlink(__DIR__ . "/" . $resumePath);
    }

    $stmt = $pdo->prepare("UPDATE users SET resume_path=NULL, resume_original_name=NULL, resume_uploaded_at=NULL WHERE user_id=?");
    $stmt->execute([$user_id]);

    header("Location: applicant_profile.php?deleted=1");
    exit;
}

/* ======================
   FETCH USER DATA
====================== */
$stmt = $pdo->prepare("SELECT full_name, phone, email, headline, bio, resume_path, resume_original_name, resume_uploaded_at FROM users WHERE user_id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$full_name = $user["full_name"] ?? "";
$phone     = $user["phone"] ?? "";
$email     = $user["email"] ?? "";
$headline  = $user["headline"] ?? "";
$bio       = $user["bio"] ?? "";

$resume_path = $user["resume_path"] ?? null;
$resume_name = $user["resume_original_name"] ?? null;
$resume_date = $user["resume_uploaded_at"] ?? null;

// small UI flags
$saved_ok = isset($_GET["saved"]);
$upload_q = $_GET["upload"] ?? null;
$deleted_ok = isset($_GET["deleted"]);
?>
<!DOCTYPE html>

<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Applicant Profile &amp; Resume Management</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200;300;400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
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
              borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
            },
          },
        }
    </script>
<style>
        body { font-family: 'Manrope', sans-serif; }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark min-h-screen">
<!-- Main Container -->
<div class="layout-container flex h-full grow flex-col">
<!-- Header / TopNavBar -->
<header class="bg-white border-b border-slate-200">
    <div class="max-w-[1400px] mx-auto px-10 py-4 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <h1 class="text-lg font-extrabold text-slate-900">
                RMS Project
            </h1>

            <!-- NAV BAR (YOUR CODE, ALIGNED) -->
            <nav class="hidden md:flex items-center gap-6 ml-4">
                 <a class="text-primary border-b-2 border-primary text-sm font-bold py-1" href="Applicant_dashboard.php">Dashboard</a>
                <a class="text-primary border-b-2 border-primary text-sm font-bold py-1" href="Application_status.php">Application status</a>
            </nav>
        </div>

        <div class="flex items-center gap-3"></div>
    </div>
</header>

<!-- Main Content Section -->
<main class="flex-1 px-6 lg:px-40 py-8">
<div class="max-w-[1200px] mx-auto">

<?php if ($saved_ok): ?>
<div class="mb-6 p-4 rounded-lg border border-green-200 bg-green-50 text-green-800 text-sm font-semibold">
Profile updated successfully.
</div>
<?php endif; ?>

<?php if ($deleted_ok): ?>
<div class="mb-6 p-4 rounded-lg border border-green-200 bg-green-50 text-green-800 text-sm font-semibold">
Resume deleted successfully.
</div>
<?php endif; ?>

<?php if ($upload_q !== null): ?>
<?php
    $msg = "Upload failed.";
    $cls = "border-red-200 bg-red-50 text-red-800";
    if ($upload_q === "1") { $msg = "Resume uploaded successfully."; $cls = "border-green-200 bg-green-50 text-green-800"; }
    if ($upload_q === "oversize") { $msg = "File too large. Max 5MB."; }
    if ($upload_q === "badext") { $msg = "Invalid file type. Allowed: PDF, DOC, DOCX."; }
    if ($upload_q === "badmime") { $msg = "Invalid file content type."; }
    if ($upload_q === "fail") { $msg = "Could not move uploaded file."; }
?>
<div class="mb-6 p-4 rounded-lg border <?= $cls ?> text-sm font-semibold">
<?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- Profile Grid -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
<!-- Left Column: Personal Details -->
<section class="lg:col-span-7 flex flex-col gap-6">
<div class="bg-white dark:bg-background-dark/50 border border-primary/10 rounded-xl p-8 shadow-sm">
<div class="flex items-center gap-6 mb-8">
<div class="relative group">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-xl size-24 border-4 border-white dark:border-background-dark shadow-md" data-alt="User profile picture" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBJ4prewqrWgWe0zIlARx2Gqr10WeGtNUx1EvNzf6A5pV7xc9e-QUbkJ2qlohBBaXp9Op-mWYWrVgUnLCQQIcJtl6wS_Esx0_DzFekoghXHn0RnKqAbIgPefRXowloq8HC1xnFTqsdIZ7LaO7Rs8ZfqDFs0jcjPTerx9BRQfNsIzCxoD4CCqVvyiI2WwQk1ESIc_7M5VH1V1QUhZ34RURmbSTUTviAmcnB_s2JRPZbQfOwjwo0dRpeAIOcuQc5Vx42XXuN7ffMl3Qft");'></div>
<button type="button" class="absolute -bottom-2 -right-2 bg-primary text-white p-2 rounded-lg shadow-lg hover:bg-primary/90 transition-all">
<span class="material-symbols-outlined text-sm">edit</span>
</button>
</div>
<div class="flex flex-col">
<h3 class="text-[#111418] dark:text-white text-xl font-bold">Personal Information</h3>
<p class="text-primary/60 dark:text-white/40 text-sm">Update your public profile data</p>
</div>
</div>

<form class="space-y-6" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="save_profile" value="1"/>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="flex flex-col gap-2">
<label class="text-[#111418] dark:text-white/90 text-sm font-semibold">Full Name</label>
<input
name="full_name"
class="rounded-lg border-primary/10 bg-primary/5 dark:bg-white/5 dark:text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none px-4 py-2.5 transition-all"
placeholder="e.g. John Doe"
type="text"
value="<?= htmlspecialchars($full_name) ?>"/>
</div>
<div class="flex flex-col gap-2">
<label class="text-[#111418] dark:text-white/90 text-sm font-semibold">Contact Number</label>
<input
name="phone"
class="rounded-lg border-primary/10 bg-primary/5 dark:bg-white/5 dark:text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none px-4 py-2.5 transition-all"
placeholder="+1 (555) 000-0000"
type="tel"
value="<?= htmlspecialchars($phone) ?>"/>
</div>
</div>

<div class="flex flex-col gap-2">
<label class="text-[#111418] dark:text-white/90 text-sm font-semibold">Email Address</label>
<input
name="email"
class="rounded-lg border-primary/10 bg-primary/5 dark:bg-white/5 dark:text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none px-4 py-2.5 transition-all"
placeholder="email@domain.com"
type="email"
value="<?= htmlspecialchars($email) ?>"/>
</div>

<div class="flex flex-col gap-2">
<label class="text-[#111418] dark:text-white/90 text-sm font-semibold">Professional Headline</label>
<input
name="headline"
class="rounded-lg border-primary/10 bg-primary/5 dark:bg-white/5 dark:text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none px-4 py-2.5 transition-all"
placeholder="e.g. Product Designer"
type="text"
value="<?= htmlspecialchars($headline) ?>"/>
</div>

<div class="pt-4">
<button class="w-full md:w-auto px-8 py-3 bg-primary text-white font-bold rounded-lg hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98] transition-all flex items-center justify-center gap-2" type="submit">
<span class="material-symbols-outlined text-lg">save</span>
Save Changes
</button>
</div>
</form>
</div>

<!-- Secondary card for about -->
<div class="bg-white dark:bg-background-dark/50 border border-primary/10 rounded-xl p-8 shadow-sm">
<h3 class="text-[#111418] dark:text-white text-xl font-bold mb-4">Short Bio</h3>
<textarea
name="bio"
form="" hidden></textarea>

<!-- keep your UI textarea, but we need it inside the POST form -->
<form method="POST" class="space-y-4">
    <input type="hidden" name="save_profile" value="1"/>
    <input type="hidden" name="full_name" value="<?= htmlspecialchars($full_name) ?>">
    <input type="hidden" name="phone" value="<?= htmlspecialchars($phone) ?>">
    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
    <input type="hidden" name="headline" value="<?= htmlspecialchars($headline) ?>">

    <textarea name="bio" class="w-full rounded-lg border-primary/10 bg-primary/5 dark:bg-white/5 dark:text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none px-4 py-2.5 transition-all" placeholder="Tell us a bit about your professional background..." rows="4"><?= htmlspecialchars($bio) ?></textarea>

    <button class="w-full md:w-auto px-8 py-3 bg-primary text-white font-bold rounded-lg hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98] transition-all flex items-center justify-center gap-2" type="submit">
        <span class="material-symbols-outlined text-lg">save</span>
        Save Bio
    </button>
</form>
</div>
</section>

<!-- Right Column: Resume Management -->
<section class="lg:col-span-5 flex flex-col gap-6">
<div class="bg-white dark:bg-background-dark/50 border border-primary/10 rounded-xl p-8 shadow-sm h-full">
<h3 class="text-[#111418] dark:text-white text-xl font-bold mb-2">Resume Management</h3>
<p class="text-primary/60 dark:text-white/40 text-sm mb-6">Upload your latest CV to be visible to recruiters in the system.</p>

<!-- Upload Form (click zone triggers file picker) -->
<form method="POST" enctype="multipart/form-data" class="space-y-4">
    <input type="hidden" name="upload_resume" value="1"/>

   <form method="POST" enctype="multipart/form-data">

    <input type="hidden" name="upload_resume" value="1">

    <!-- REAL file input -->
    <input
        type="file"
        name="resume_file"
        id="resume_file_input"
        accept=".pdf,.doc,.docx"
        class="hidden"
        onchange="this.form.submit()"
    >

    <!-- Upload box -->
    <label
        for="resume_file_input"
        class="border-2 border-dashed border-primary/30 dark:border-white/20
               rounded-xl p-10 flex flex-col items-center justify-center
               bg-primary/5 dark:bg-white/5 hover:border-primary/60
               transition-all cursor-pointer group mb-8"
    >
        <div class="size-16 bg-primary/10 dark:bg-primary/20 rounded-full
                    flex items-center justify-center text-primary mb-4
                    group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-3xl">upload_file</span>
        </div>

        <p class="text-[#111418] dark:text-white font-bold text-center">
            Click to upload or drag and drop
        </p>
        <p class="text-primary/60 dark:text-white/40 text-xs mt-2 text-center">
            PDF or DOCX (Max 5MB)
        </p>
    </label>

</form>


    <button type="submit" class="w-full px-8 py-3 bg-primary text-white font-bold rounded-lg hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-lg">upload</span>
        Upload Resume
    </button>
</form>

<!-- Current Resume Preview -->
<div class="space-y-4 mt-8">
<h4 class="text-[#111418] dark:text-white/90 text-sm font-bold uppercase tracking-wider">Current Resume</h4>

<?php if ($resume_path && file_exists(__DIR__ . "/" . $resume_path)): ?>
<div class="flex items-center justify-between p-4 border border-primary/10 rounded-lg bg-white dark:bg-white/5">
<div class="flex items-center gap-3">
<div class="size-10 bg-red-100 dark:bg-red-900/30 text-red-600 flex items-center justify-center rounded-lg">
<span class="material-symbols-outlined">description</span>
</div>
<div class="flex flex-col">
<span class="text-sm font-bold text-[#111418] dark:text-white truncate max-w-[150px]"><?= htmlspecialchars($resume_name ?: "Resume") ?></span>
<span class="text-[10px] text-primary/50 dark:text-white/40 uppercase">
Uploaded <?= htmlspecialchars($resume_date ? date("M d, Y", strtotime($resume_date)) : "") ?>
</span>
</div>
</div>
<div class="flex items-center gap-2">
<a href="applicant_profile.php?download_resume=1" class="p-2 hover:bg-primary/10 rounded-full text-primary transition-colors" title="Download">
<span class="material-symbols-outlined text-xl">download</span>
</a>
<form method="POST" onsubmit="return confirm('Delete your resume?');">
    <input type="hidden" name="delete_resume" value="1"/>
    <button class="p-2 hover:bg-red-50 text-red-500 rounded-full transition-colors" title="Delete" type="submit">
        <span class="material-symbols-outlined text-xl">delete</span>
    </button>
</form>
</div>
</div>

<div class="flex items-start gap-3 p-4 bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-900/30 rounded-lg">
<span class="material-symbols-outlined text-green-600 text-sm mt-0.5">check_circle</span>
<div class="flex flex-col">
<span class="text-xs font-bold text-green-800 dark:text-green-400">File ready for applications</span>
<span class="text-[11px] text-green-700 dark:text-green-500/80">Recruiters can now view your resume when you apply for jobs.</span>
</div>
</div>

<?php else: ?>
<div class="p-4 border border-primary/10 rounded-lg bg-white dark:bg-white/5 text-sm text-slate-600 dark:text-slate-300">
No resume uploaded yet.
</div>
<?php endif; ?>
</div>

<div class="mt-8 pt-8 border-t border-primary/10">
<h4 class="text-[#111418] dark:text-white/90 text-sm font-bold mb-4">Application Tips</h4>
<ul class="space-y-3">
<li class="flex items-center gap-3 text-xs text-primary/70 dark:text-white/50">
<span class="material-symbols-outlined text-xs">info</span>
Use a clean, professional PDF format.
</li>
<li class="flex items-center gap-3 text-xs text-primary/70 dark:text-white/50">
<span class="material-symbols-outlined text-xs">info</span>
Ensure your contact info matches your profile.
</li>
<li class="flex items-center gap-3 text-xs text-primary/70 dark:text-white/50">
<span class="material-symbols-outlined text-xs">info</span>
Keywords help recruiters find your profile.
</li>
</ul>
</div>
</div>
</section>
</div>
</div>
</main>

<!-- Footer -->
<footer class="mt-auto px-6 lg:px-40 py-8 border-t border-primary/10 bg-white dark:bg-background-dark text-center">
<p class="text-primary/40 dark:text-white/20 text-xs">© 2024 Recruitment Management System - Academic Project Portal</p>
</footer>
</div>
</body></html>
