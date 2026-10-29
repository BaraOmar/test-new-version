<?php
session_start();

/* ======================
   AUTH: Recruiter only
====================== */
if (!isset($_SESSION["user_id"]) || $_SESSION["role_id"] != 1) {
    header("Location: Recruiter_login_Page.php");
    exit;
}

/* ======================
   DB Connection
====================== */
$conn = new mysqli("localhost", "root", "", "recruitment_management");
if ($conn->connect_error) {
    die("Database connection failed");
}

$recruiter_id = (int)$_SESSION["user_id"];

/* ======================
   HANDLE ACTIONS
====================== */
if (isset($_GET["action"], $_GET["job_id"])) {
    $job_id = (int)$_GET["job_id"];
    $action = $_GET["action"];

    $check = $conn->prepare("SELECT job_id FROM jobs WHERE job_id=? AND recruiter_id=?");
    $check->bind_param("ii", $job_id, $recruiter_id);
    $check->execute();

    if ($check->get_result()->num_rows === 1) {

        if ($action === "delete") {
            $stmt = $conn->prepare("DELETE FROM jobs WHERE job_id=?");
            $stmt->bind_param("i", $job_id);
            $stmt->execute();
        }

        if ($action === "close") {
            $stmt = $conn->prepare("UPDATE jobs SET status='closed' WHERE job_id=?");
            $stmt->bind_param("i", $job_id);
            $stmt->execute();
        }
    }
    header("Location: Job_Management_Page.php");
    exit;
}

/* ======================
   FILTERS
====================== */
$search = trim($_GET["search"] ?? "");
$statusFilter = $_GET["status"] ?? "";
$departmentFilter = $_GET["department"] ?? "";

$where = "WHERE recruiter_id=?";
$params = [$recruiter_id];
$types = "i";

if ($search !== "") {
    $where .= " AND job_title LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if ($departmentFilter !== "") {
    $where .= " AND department=?";
    $params[] = $departmentFilter;
    $types .= "s";
}

if (in_array($statusFilter, ["published","draft","closed"])) {
    $where .= " AND status=?";
    $params[] = $statusFilter;
    $types .= "s";
}

/* ======================
   FETCH JOBS
====================== */
$sql = "SELECT * FROM jobs $where ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$jobs = $stmt->get_result();

/* ======================
   FETCH DEPARTMENTS
====================== */
$departments = [];
$deptRes = $conn->query(
    "SELECT DISTINCT department FROM jobs WHERE recruiter_id=$recruiter_id"
);
while ($row = $deptRes->fetch_assoc()) {
    $departments[] = $row["department"];
}

/* ======================
   STATS
====================== */
$stats = ["total"=>0,"published"=>0,"draft"=>0,"closed"=>0];

$statRes = $conn->query(
    "SELECT status, COUNT(*) c FROM jobs WHERE recruiter_id=$recruiter_id GROUP BY status"
);
while ($row = $statRes->fetch_assoc()) {
    $stats[$row["status"]] = $row["c"];
    $stats["total"] += $row["c"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Job Management</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
<style>body{font-family:Manrope}</style>
</head>

<body class="bg-slate-100">

<!-- NAVBAR -->
<header class="bg-white border-b px-8 py-4 flex justify-between items-center">
    <div class="flex gap-6 items-center">
        <h1 class="text-xl font-extrabold">RMS Recruiter</h1>
        <a href="Recruiter_Dashboard.php" class="text-slate-500 font-semibold">Dashboard</a>
        <a href="Job_Management_Page.php" class="text-primary font-bold">Jobs</a>
        <a href="#" class="text-slate-500 font-semibold">Applicants</a>
        <a href="#" class="text-slate-500 font-semibold">Reports</a>
    </div>
    <div class="font-bold text-slate-700"><?= htmlspecialchars($_SESSION["full_name"]) ?></div>
</header>

<div class="max-w-[1400px] mx-auto p-8">

<h1 class="text-3xl font-extrabold mb-6">Job Management</h1>

<!-- STATS -->
<div class="grid grid-cols-4 gap-4 mb-8">
<?php foreach ([
    ["Total Jobs",$stats["total"]],
    ["Published",$stats["published"]],
    ["Drafts",$stats["draft"]],
    ["Closed",$stats["closed"]],
] as $c): ?>
<div class="bg-white p-4 rounded-xl border">
    <p class="text-xs font-bold text-slate-500"><?= $c[0] ?></p>
    <p class="text-2xl font-black"><?= $c[1] ?></p>
</div>
<?php endforeach; ?>
</div>

<!-- FILTER FORM -->
<form method="GET" class="flex flex-wrap gap-4 items-end mb-6">

    <!-- Search input -->
    <div>
        <label class="block text-xs font-bold text-slate-400 mb-1">Search title</label>
        <input
            name="search"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="Search title"
            class="h-11 w-64 rounded-lg border border-slate-300 bg-white px-4 text-sm
                   focus:border-primary focus:ring-primary">
    </div>

    <!-- Department filter -->
    <div>
        <label class="block text-xs font-bold text-slate-400 mb-1">Department</label>
        <select
            name="department"
            class="h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm
                   focus:border-primary focus:ring-primary">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= htmlspecialchars($dept) ?>"
                    <?= $departmentFilter === $dept ? "selected" : "" ?>>
                    <?= htmlspecialchars($dept) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Status filter -->
    <div>
        <label class="block text-xs font-bold text-slate-400 mb-1">Status</label>
        <select
            name="status"
            class="h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm
                   focus:border-primary focus:ring-primary">
            <option value="">All Status</option>
            <?php foreach (["published","draft","closed"] as $s): ?>
                <option value="<?= $s ?>" <?= $statusFilter === $s ? "selected" : "" ?>>
                    <?= ucfirst($s) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- 🔍 SEARCH BUTTON (FIXED) -->
    <div>
        <label class="block text-xs font-bold text-transparent mb-1">Search</label>
        <button
            type="submit"
            class="h-11 px-6 rounded-lg bg-primary text-white font-bold text-sm
                   flex items-center gap-2
                   hover:bg-primary/90 active:scale-[0.98]
                   transition-all shadow shadow-primary/30">
            <span class="material-symbols-outlined text-lg">search</span>
            Search
        </button>
    </div>

</form>


<!-- TABLE -->
<div class="bg-white rounded-xl border overflow-hidden">
<table class="w-full">
<thead class="bg-slate-50">
<tr>
<th class="p-4">Title</th>
<th>Department</th>
<th>Status</th>
<th>Date</th>
<th class="text-right p-4">Actions</th>
</tr>
</thead>
<tbody>

<?php if ($jobs->num_rows === 0): ?>
<tr><td colspan="5" class="p-8 text-center text-slate-400">No jobs found</td></tr>
<?php endif; ?>

<?php while ($job = $jobs->fetch_assoc()): ?>
<tr class="border-t hover:bg-slate-50">
<td class="p-4 font-bold"><?= htmlspecialchars($job["job_title"]) ?></td>
<td><?= htmlspecialchars($job["department"]) ?></td>
<td>
<span class="px-3 py-1 rounded-full text-xs font-bold
<?= $job["status"]=="published"?"bg-green-100 text-green-700":"" ?>
<?= $job["status"]=="draft"?"bg-slate-100 text-slate-700":"" ?>
<?= $job["status"]=="closed"?"bg-red-100 text-red-700":"" ?>">
<?= ucfirst($job["status"]) ?>
</span>
</td>
<td><?= date("M d, Y", strtotime($job["created_at"])) ?></td>
<td class="p-4 text-right space-x-3">
<a href="create_edit_job.php?job_id=<?= $job["job_id"] ?>" class="text-primary font-bold">Edit</a>
<?php if ($job["status"] !== "closed"): ?>
<a href="?action=close&job_id=<?= $job["job_id"] ?>" class="text-orange-500 font-bold"
onclick="return confirm('Close this job?')">Close</a>
<?php endif; ?>
<a href="?action=delete&job_id=<?= $job["job_id"] ?>" class="text-red-500 font-bold"
onclick="return confirm('Delete permanently?')">Delete</a>
</td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>

</div>
</body>
</html>
