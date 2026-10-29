
<?php

session_start();
require_once "config/database.php";

/* ======================
   AUTH: Applicant only
====================== */
if (!isset($_SESSION["user_id"]) || $_SESSION["role_id"] != 2) {
    header("Location: Applicant_login_Page.php");
    exit;
}

$applicant_id = (int)$_SESSION["user_id"];

/* ======================
   DB CONNECTION
====================== */
$conn = new mysqli("localhost", "root", "", "recruitment_management");
if ($conn->connect_error) {
    die("Database connection failed");
}

/* ======================
   HANDLE APPLY
====================== */
if (isset($_POST["apply_job_id"])) {
    $job_id = (int)$_POST["apply_job_id"];

    $stmt = $conn->prepare(
        "INSERT IGNORE INTO applications (job_id, applicant_id)
         VALUES (?, ?)"
    );
    $stmt->bind_param("ii", $job_id, $applicant_id);
    $stmt->execute();

    header("Location: Applicant_Dashboard.php");
    exit;
}

/* ======================
   FILTERS
====================== */
$search = trim($_GET["search"] ?? "");
$department = $_GET["department"] ?? "";

$where = "WHERE j.status='published'";
$params = [];
$types = "";

if ($search !== "") {
    $where .= " AND j.job_title LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if ($department !== "") {
    $where .= " AND j.department = ?";
    $params[] = $department;
    $types .= "s";
}

/* ======================
   FETCH JOBS + APPLIED STATE
====================== */
$sql = "
SELECT j.*,
       a.application_id IS NOT NULL AS applied
FROM jobs j
LEFT JOIN applications a
  ON j.job_id = a.job_id
 AND a.applicant_id = ?
$where
ORDER BY j.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i".$types, $applicant_id, ...$params);
$stmt->execute();
$jobs = $stmt->get_result();

/* ======================
   FETCH DEPARTMENTS
====================== */
$departments = [];
$res = $conn->query("SELECT DISTINCT department FROM jobs WHERE status='published'");
while ($row = $res->fetch_assoc()) {
    $departments[] = $row["department"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Applicant Dashboard</title>

<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300..800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

<style>
body { font-family: Manrope; }
</style>
</head>

<body class="bg-slate-100">

<!-- ================= HEADER + NAV BAR ================= -->
<header class="bg-white border-b border-slate-200">
    <div class="max-w-[1400px] mx-auto px-10 py-4 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <h1 class="text-lg font-extrabold text-slate-900">
                RMS Project
            </h1>

            <!-- NAV BAR (YOUR CODE, ALIGNED) -->
            <nav class="hidden md:flex items-center gap-6 ml-4">
                 <a class="text-primary border-b-2 border-primary text-sm font-bold py-1" href="applicant_profile.php">Applicant Profile</a>
                <a class="text-primary border-b-2 border-primary text-sm font-bold py-1" href="Application_status.php">Application status</a>
                
            </nav>
        </div>

        <div class="flex items-center gap-3">

        </div>
    </div>
</header>

<!-- ================= PAGE CONTENT ================= -->
<div class="max-w-[1400px] mx-auto p-10">

<h2 class="text-3xl font-extrabold text-slate-900 mb-8">
Active Job Openings
</h2>

<!-- FILTER BAR -->
<form method="GET"
class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex flex-wrap gap-4 mb-10">

<div class="relative flex-1 min-w-[250px]">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
search
</span>
<input
name="search"
value="<?= htmlspecialchars($search) ?>"
placeholder="Search job title..."
class="pl-10 h-11 w-full rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500"
/>
</div>

<select name="department"
class="h-11 rounded-lg border border-slate-300 px-4 focus:ring-2 focus:ring-blue-500">
<option value="">All Departments</option>
<?php foreach ($departments as $d): ?>
<option value="<?= $d ?>" <?= $department===$d?"selected":"" ?>>
<?= htmlspecialchars($d) ?>
</option>
<?php endforeach; ?>
</select>

<button type="submit"
class="h-11 px-8 rounded-lg bg-slate-900 text-white font-bold hover:bg-slate-800 transition">
Search
</button>

</form>

<!-- JOB GRID -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

<?php if ($jobs->num_rows === 0): ?>
<p class="text-slate-400 text-lg">No jobs found.</p>
<?php endif; ?>

<?php while ($job = $jobs->fetch_assoc()): ?>

<div
class="bg-white rounded-xl border border-slate-200 p-6 hover:shadow-lg hover:border-blue-500 transition relative">

<?php if ($job["applied"]): ?>
<span
class="absolute top-4 right-4 text-xs font-bold bg-blue-500 text-white px-3 py-1 rounded-full">
Applied
</span>
<?php endif; ?>

<h3 class="text-lg font-bold text-slate-900 mb-1">
<?= htmlspecialchars($job["job_title"]) ?>
</h3>

<p class="text-sm text-slate-500">
<?= htmlspecialchars($job["department"]) ?>
</p>

<p class="text-sm text-slate-400 mt-2 line-clamp-2">
<?= htmlspecialchars($job["description"]) ?>
</p>

<div class="mt-5 flex items-center justify-between border-t pt-4">

<div class="text-xs text-slate-500 flex items-center gap-1">
<span class="material-symbols-outlined text-sm">location_on</span>
<?= htmlspecialchars($job["location"]) ?>
</div>

<?php if (!$job["applied"]): ?>
<form method="POST">
<input type="hidden" name="apply_job_id" value="<?= $job["job_id"] ?>">
<button
class="text-blue-600 font-bold text-sm hover:underline flex items-center gap-1">
Apply Now
<span class="material-symbols-outlined text-base">arrow_forward</span>
</button>
</form>
<?php endif; ?>

</div>
</div>

<?php endwhile; ?>

</div>

</div>
</body>
</html>
