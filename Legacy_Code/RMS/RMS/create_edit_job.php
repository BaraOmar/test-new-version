<?php
session_start();

/* =========================
   AUTH: Recruiter only
========================= */
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 1) {
    header("Location: Recruiter_login_Page.php");
    exit;
}

/* =========================
   DB Connection
========================= */
$conn = new mysqli("localhost", "root", "", "recruitment_management");
if ($conn->connect_error) {
    die("Database connection failed");
}

$recruiter_id = (int)$_SESSION["user_id"];

$job_id = isset($_GET["job_id"]) ? (int)$_GET["job_id"] : 0;

$errors = [];
$success = "";

/* =========================
   Default values
========================= */
$job_title = "";
$department = "";
$location = "";
$description = "";
$status = "draft";

/* =========================
   Load job for edit (must belong to recruiter)
========================= */
if ($job_id > 0) {
    $stmt = $conn->prepare(
        "SELECT job_title, department, location, description, status
         FROM jobs
         WHERE job_id = ? AND recruiter_id = ?"
    );
    $stmt->bind_param("ii", $job_id, $recruiter_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows !== 1) {
        // job not found OR not owned by this recruiter
        header("Location: Job_Management_Page.php");
        exit;
    }

    $job = $res->fetch_assoc();
    $job_title = $job["job_title"];
    $department = $job["department"] ?? "";
    $location = $job["location"] ?? "";
    $description = $job["description"];
    $status = $job["status"];
}

/* =========================
   Handle Form Submit
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $job_id_post = isset($_POST["job_id"]) ? (int)$_POST["job_id"] : 0;

    $job_title = trim($_POST["job_title"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $description = trim($_POST["description"] ?? "");

    // Which action?
    // draft | publish | cancel
    $action = $_POST["action"] ?? "draft";

    if ($action === "cancel") {
        header("Location: Job_Management_Page.php");
        exit;
    }

    // Validate
    if ($job_title === "") $errors[] = "Job title is required.";
    if ($description === "") $errors[] = "Job description is required.";

    // Decide status based on action
    $new_status = ($action === "publish") ? "published" : "draft";

    if (empty($errors)) {

        // EDIT existing job
        if ($job_id_post > 0) {

            // Ensure recruiter owns it
            $stmtCheck = $conn->prepare("SELECT job_id FROM jobs WHERE job_id=? AND recruiter_id=?");
            $stmtCheck->bind_param("ii", $job_id_post, $recruiter_id);
            $stmtCheck->execute();
            $checkRes = $stmtCheck->get_result();
            if ($checkRes->num_rows !== 1) {
                $errors[] = "You are not allowed to update this job.";
            } else {
                $stmt = $conn->prepare(
                    "UPDATE jobs
                     SET job_title=?, department=?, location=?, description=?, status=?
                     WHERE job_id=? AND recruiter_id=?"
                );
                $stmt->bind_param(
                    "sssssii",
                    $job_title,
                    $department,
                    $location,
                    $description,
                    $new_status,
                    $job_id_post,
                    $recruiter_id
                );
                if ($stmt->execute()) {
                    $success = ($new_status === "published")
                        ? "Job published successfully."
                        : "Draft saved successfully.";

                    // keep job_id in URL after save
                    header("Location: create_edit_job.php?job_id=" . $job_id_post . "&saved=1");
                    exit;
                } else {
                    $errors[] = "Database error while updating job.";
                }
            }

        } else {
            // CREATE new job
            $stmt = $conn->prepare(
                "INSERT INTO jobs (recruiter_id, job_title, department, location, description, status)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "isssss",
                $recruiter_id,
                $job_title,
                $department,
                $location,
                $description,
                $new_status
            );

            if ($stmt->execute()) {
                $newId = $stmt->insert_id;

                header("Location: create_edit_job.php?job_id=" . $newId . "&created=1");
                exit;
            } else {
                $errors[] = "Database error while creating job.";
            }
        }
    }
}

$pageTitle = ($job_id > 0) ? "Edit Job" : "Create New Job";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Create / Edit Job | RMS Recruiter</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&display=swap" rel="stylesheet" />

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
        body { font-family: 'Manrope', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .rich-text-editor-stub { min-height: 300px; border: 1px solid #dbe0e6; }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100">
<header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-slate-200 bg-white dark:bg-slate-900 dark:border-slate-800 px-10 py-3 sticky top-0 z-50">
    <div class="flex items-center gap-8">
        <div class="flex items-center gap-4 text-primary">
            <div class="size-8 flex items-center justify-center bg-primary/10 rounded-lg">
                <span class="material-symbols-outlined text-primary">school</span>
            </div>
            <h2 class="text-slate-900 dark:text-white text-lg font-bold leading-tight tracking-[-0.015em]">RMS Recruiter</h2>
        </div>
        <div class="flex items-center gap-9">
            <a class="text-slate-600 dark:text-slate-400 text-sm font-medium leading-normal hover:text-primary transition-colors" href="Recruiter_Dashboard.php">Dashboard</a>
            <a class="text-primary text-sm font-bold leading-normal border-b-2 border-primary pb-1" href="Job_Management_Page.php">Jobs</a>
            <a class="text-slate-600 dark:text-slate-400 text-sm font-medium leading-normal hover:text-primary transition-colors" href="Applications_interview.php">Applicants</a>
            <a class="text-slate-600 dark:text-slate-400 text-sm font-medium leading-normal hover:text-primary transition-colors" href="Job_Management_Page.php">Job management</a>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <span class="text-sm text-slate-600 dark:text-slate-300 font-semibold">
            <?= htmlspecialchars($_SESSION["full_name"] ?? "Recruiter") ?>
        </span>
    </div>
</header>

<main class="max-w-[1440px] mx-auto px-10 py-6">

    <!-- Alerts -->
    <?php if (!empty($errors)): ?>
        <div class="mb-6 p-4 rounded-xl border border-red-200 bg-red-50 text-red-700">
            <div class="font-bold mb-1">Fix these issues:</div>
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET["created"])): ?>
        <div class="mb-6 p-4 rounded-xl border border-green-200 bg-green-50 text-green-700 font-bold">
            Job created successfully. (Now you can edit it or publish it.)
        </div>
    <?php endif; ?>

    <?php if (isset($_GET["saved"])): ?>
        <div class="mb-6 p-4 rounded-xl border border-green-200 bg-green-50 text-green-700 font-bold">
            Saved successfully.
        </div>
    <?php endif; ?>

    <div class="mb-8">
        <div class="flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="text-slate-500 mt-1">Define the details of the position to attract top academic and professional talent.</p>
            </div>
        </div>
    </div>

    <form method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <input type="hidden" name="job_id" value="<?= (int)$job_id ?>">

        <!-- Left Column -->
        <div class="lg:col-span-7 space-y-8">

            <!-- Action Buttons -->
            <div class="flex items-center gap-3 justify-end">
              
                <button name="action" value="draft" class="px-5 py-2 rounded-lg font-bold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition-colors">
                    Save as Draft
                </button>
                <button name="action" value="publish" class="px-5 py-2 rounded-lg font-bold text-white bg-primary hover:bg-primary/90 transition-colors shadow-lg shadow-primary/20">
                    Publish Job
                </button>
            </div>

            <!-- Section 1 -->
            <section class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <span class="material-symbols-outlined text-primary p-2 bg-primary/10 rounded-lg">info</span>
                    <h3 class="text-xl font-bold">Basic Information</h3>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Job Title *</label>
                        <input
                            id="job_title"
                            name="job_title"
                            value="<?= htmlspecialchars($job_title) ?>"
                            class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 focus:border-primary focus:ring-primary h-12"
                            placeholder="e.g. Senior Research Assistant in Bio-Tech"
                            type="text"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Department</label>
                        <select id="department" name="department" class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 focus:border-primary focus:ring-primary h-12">
                            <?php
                                $deps = ["School of Life Sciences","Engineering Department","Administration","Human Resources"];
                                foreach ($deps as $d) {
                                    $sel = ($department === $d) ? "selected" : "";
                                    echo "<option $sel>" . htmlspecialchars($d) . "</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Location</label>
                        <select id="location" name="location" class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 focus:border-primary focus:ring-primary h-12">
                            <?php
                                $locs = ["Main Campus (On-site)","Remote - North America","Satellite Lab (Hybrid)"];
                                foreach ($locs as $l) {
                                    $sel = ($location === $l) ? "selected" : "";
                                    echo "<option $sel>" . htmlspecialchars($l) . "</option>";
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Section 2 -->
            <section class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <span class="material-symbols-outlined text-primary p-2 bg-primary/10 rounded-lg">description</span>
                    <h3 class="text-xl font-bold">Job Description *</h3>
                </div>

                <textarea
                    id="description"
                    name="description"
                    class="w-full rich-text-editor-stub rounded-lg bg-white dark:bg-slate-800 p-4 text-slate-700 dark:text-slate-300"
                    placeholder="Write the job description..."
                    required
                ><?= htmlspecialchars($description) ?></textarea>

                <p class="text-xs text-slate-500 mt-2">
                    Tip: You can paste formatted text here; later we can upgrade to a rich editor if needed.
                </p>
            </section>
        </div>

        <!-- Right Column Preview -->
        <aside class="lg:col-span-5 lg:sticky lg:top-[104px] self-start">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden">
                <div class="bg-primary/5 dark:bg-primary/10 px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">visibility</span>
                        <span class="text-sm font-bold text-primary uppercase tracking-wider">Applicant View Preview</span>
                    </div>
                    <div class="flex gap-1">
                        <div class="size-2 rounded-full bg-slate-300"></div>
                        <div class="size-2 rounded-full bg-slate-300"></div>
                        <div class="size-2 rounded-full bg-slate-300"></div>
                    </div>
                </div>

                <div class="p-8 max-h-[700px] overflow-y-auto">
                  

                    <h2 id="pv_title" class="text-2xl font-extrabold text-slate-900 dark:text-white mb-2">
                        <?= htmlspecialchars($job_title ?: "Job Title Preview") ?>
                    </h2>

                    <p id="pv_dept" class="text-slate-500 font-medium mb-6">
                        <?= htmlspecialchars($department ?: "Department Preview") ?>
                    </p>

                    <div class="flex flex-wrap gap-2 mb-8">
                        <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-400">
                            <span class="material-symbols-outlined text-[14px]">location_on</span>
                            <span id="pv_loc"><?= htmlspecialchars($location ?: "Location Preview") ?></span>
                        </span>

                        <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full bg-primary/10 text-xs font-bold text-primary">
                            <span class="material-symbols-outlined text-[14px]">flag</span>
                            <span id="pv_status"><?= htmlspecialchars($status) ?></span>
                        </span>
                    </div>

                    <div class="space-y-6 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        <div>
                            <h4 class="font-bold text-slate-900 dark:text-white mb-2 text-base">Job Description</h4>
                            <div id="pv_desc" class="whitespace-pre-wrap">
                                <?= htmlspecialchars($description ?: "Write a description to see preview...") ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 p-4 rounded-xl border border-primary/20 bg-primary/5 flex items-start gap-3">
                <span class="material-symbols-outlined text-primary">lightbulb</span>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-normal">
                    <strong class="text-primary block mb-1">Recruiter Tip:</strong>
                    Using specific keywords like "PhD", "Molecular" and "Lead" will help candidates understand the role faster.
                </p>
            </div>
        </aside>
    </form>
</main>

<script>
    // Live preview updates
    const titleInput = document.getElementById("job_title");
    const deptInput  = document.getElementById("department");
    const locInput   = document.getElementById("location");
    const descInput  = document.getElementById("description");

    const pvTitle = document.getElementById("pv_title");
    const pvDept  = document.getElementById("pv_dept");
    const pvLoc   = document.getElementById("pv_loc");
    const pvDesc  = document.getElementById("pv_desc");

    function syncPreview() {
        pvTitle.textContent = titleInput.value.trim() || "Job Title Preview";
        pvDept.textContent  = deptInput.value || "Department Preview";
        pvLoc.textContent   = locInput.value || "Location Preview";
        pvDesc.textContent  = descInput.value.trim() || "Write a description to see preview...";
    }

    titleInput.addEventListener("input", syncPreview);
    deptInput.addEventListener("change", syncPreview);
    locInput.addEventListener("change", syncPreview);
    descInput.addEventListener("input", syncPreview);
</script>

</body>
</html>
