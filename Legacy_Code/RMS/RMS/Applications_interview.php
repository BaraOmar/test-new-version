<?php
session_start();

/**
 * ---------------------------------------------------
 * Database connection (REQUIRED)
 * ---------------------------------------------------
 */
require_once __DIR__ . "/config/database.php";

if (!isset($pdo) || !$pdo instanceof PDO) {
    die("Database connection failed. Check config/database.php");
}

/**
 * Recruiter ID (dynamic)
 * - If your login system stores recruiter id in session, use it.
 * - Otherwise fallback to 1 (TEMP).
 */
$recruiter_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

/**
 * Helpers
 */
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

function timeAgo($datetime) {
    if (!$datetime) return "";
    $ts = strtotime($datetime);
    if (!$ts) return "";
    $diff = time() - $ts;
    if ($diff < 60) return "just now";
    if ($diff < 3600) return floor($diff / 60) . "m ago";
    if ($diff < 86400) return floor($diff / 3600) . "h ago";
    return floor($diff / 86400) . "d ago";
}

/**
 * INPUTS
 */
$filter_status = $_GET["filter"] ?? "All";
$q = trim($_GET["q"] ?? "");
$selected_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

$flash = "";

/**
 * ---------------------------------------------------
 * POST Actions: update status, schedule interview, save feedback
 * ---------------------------------------------------
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $application_id = isset($_POST["application_id"]) ? (int)$_POST["application_id"] : 0;

    // Safety: ensure this application belongs to this recruiter
    $checkStmt = $pdo->prepare("
        SELECT a.application_id
        FROM applications a
        JOIN jobs j ON a.job_id = j.job_id
        WHERE a.application_id = ? AND j.recruiter_id = ?
        LIMIT 1
    ");
    $checkStmt->execute([$application_id, $recruiter_id]);
    $owned = (bool)$checkStmt->fetchColumn();

    if (!$owned) {
        $flash = "Invalid action (not your application).";
    } else {

        if ($action === "update_status") {
            $status = $_POST["status"] ?? "New";
            $allowed = ["New","Screened","Interviewing","Offered","Rejected"];
            if (!in_array($status, $allowed, true)) $status = "New";

            // IMPORTANT: correct column name is application_status
            $stmt = $pdo->prepare("UPDATE applications SET application_status = ? WHERE application_id = ?");
            $stmt->execute([$status, $application_id]);

            $flash = "Status updated.";
            $selected_id = $application_id;
        }

        if ($action === "schedule_interview") {
            $date = $_POST["interview_date"] ?? "";
            $time = $_POST["interview_time"] ?? "";
            $type = $_POST["interview_type"] ?? "Google Meet";
            $allowedTypes = ["Google Meet","Zoom","In-Person Office"];
            if (!in_array($type, $allowedTypes, true)) $type = "Google Meet";

            if ($date && $time) {
                // upsert interview
                $existsStmt = $pdo->prepare("SELECT interview_id FROM interviews WHERE application_id = ? LIMIT 1");
                $existsStmt->execute([$application_id]);
                $existingId = $existsStmt->fetchColumn();

                if ($existingId) {
                    $stmt = $pdo->prepare("
                        UPDATE interviews
                        SET interview_date = ?, interview_time = ?, interview_type = ?
                        WHERE application_id = ?
                    ");
                    $stmt->execute([$date, $time, $type, $application_id]);
                    $flash = "Interview updated.";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO interviews (application_id, interview_date, interview_time, interview_type)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$application_id, $date, $time, $type]);
                    $flash = "Interview scheduled.";
                }

                // automatically set status to Interviewing
                $pdo->prepare("UPDATE applications SET application_status = 'Interviewing' WHERE application_id = ?")
                    ->execute([$application_id]);

                $selected_id = $application_id;
            } else {
                $flash = "Please select date and time.";
            }
        }

        if ($action === "save_feedback") {
            $notes = trim($_POST["notes"] ?? "");
            $rating = isset($_POST["rating"]) && $_POST["rating"] !== "" ? (int)$_POST["rating"] : null;
            if ($rating !== null && ($rating < 1 || $rating > 5)) $rating = null;

            // upsert feedback
            $existsStmt = $pdo->prepare("SELECT feedback_id FROM interview_feedback WHERE application_id = ? LIMIT 1");
            $existsStmt->execute([$application_id]);
            $existingId = $existsStmt->fetchColumn();

            if ($existingId) {
                $stmt = $pdo->prepare("
                    UPDATE interview_feedback
                    SET rating = ?, notes = ?
                    WHERE application_id = ?
                ");
                $stmt->execute([$rating, $notes, $application_id]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO interview_feedback (application_id, rating, notes)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$application_id, $rating, $notes]);
            }

            $flash = "Feedback saved.";
            $selected_id = $application_id;
        }
    }
}

/**
 * ---------------------------------------------------
 * Load applications list (left sidebar) with filter + search
 * ---------------------------------------------------
 */
$params = [$recruiter_id];
$where = "WHERE j.recruiter_id = ?";

if ($filter_status !== "All") {
    $allowed = ["New","Screened","Interviewing","Offered","Rejected"];
    if (in_array($filter_status, $allowed, true)) {
        $where .= " AND a.application_status = ?";
        $params[] = $filter_status;
    }
}

if ($q !== "") {
    $where .= " AND (u.full_name LIKE ? OR ap.headline LIKE ? OR ap.bio LIKE ?)";
    $like = "%".$q."%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$listStmt = $pdo->prepare("
    SELECT
        a.application_id,
        a.application_status AS status,
        a.applied_at,
        u.user_id,
        u.full_name,
        u.email,
        ap.headline,
        ap.bio,
        ap.location
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    JOIN users u ON a.applicant_id = u.user_id
    LEFT JOIN applicant_profiles ap ON ap.applicant_id = u.user_id
    $where
    ORDER BY a.applied_at DESC
    LIMIT 200
");
$listStmt->execute($params);
$applications = $listStmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Auto select first application if none selected
 */
if ($selected_id === 0 && count($applications) > 0) {
    $selected_id = (int)$applications[0]["application_id"];
}

/**
 * ---------------------------------------------------
 * Load selected applicant details
 * ---------------------------------------------------
 */
$selected = null;
$selectedInterview = null;
$selectedFeedback = null;

if ($selected_id) {
    $detailStmt = $pdo->prepare("
        SELECT
            a.application_id,
            a.application_status AS status,
            a.applied_at,
            u.user_id AS applicant_user_id,
            u.full_name,
            u.email,
            ap.headline,
            ap.bio,
            ap.location
        FROM applications a
        JOIN jobs j ON a.job_id = j.job_id
        JOIN users u ON a.applicant_id = u.user_id
        LEFT JOIN applicant_profiles ap ON ap.applicant_id = u.user_id
        WHERE a.application_id = ? AND j.recruiter_id = ?
        LIMIT 1
    ");
    $detailStmt->execute([$selected_id, $recruiter_id]);
    $selected = $detailStmt->fetch(PDO::FETCH_ASSOC);

    if ($selected) {
        $intStmt = $pdo->prepare("SELECT * FROM interviews WHERE application_id = ? LIMIT 1");
        $intStmt->execute([$selected_id]);
        $selectedInterview = $intStmt->fetch(PDO::FETCH_ASSOC);

        $fbStmt = $pdo->prepare("SELECT * FROM interview_feedback WHERE application_id = ? LIMIT 1");
        $fbStmt->execute([$selected_id]);
        $selectedFeedback = $fbStmt->fetch(PDO::FETCH_ASSOC);
    }
}

/**
 * ---------------------------------------------------
 * Counts
 * ---------------------------------------------------
 */
$newCountStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    WHERE j.recruiter_id = ? AND a.application_status = 'New'
");
$newCountStmt->execute([$recruiter_id]);
$newCount = (int)$newCountStmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title>Applications &amp; Interviews | RMS Project</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            primary: "#137fec",
            "background-light": "#f6f7f8",
            "background-dark": "#101922",
          },
          fontFamily: {
            display: ["Manrope", "sans-serif"]
          },
          borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
        },
      },
    }
  </script>
  <style>
    body { font-family: 'Manrope', sans-serif; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
  </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-[#111418]">
<div class="relative flex min-h-screen w-full flex-col overflow-x-hidden">

  <!-- Top Navigation -->
  <header class="bg-white border-b px-8 py-4 flex justify-between items-center">
    <div class="flex gap-6 items-center">
      <h1 class="text-xl font-extrabold">RMS Recruiter</h1>
      <a href="Recruiter_Dashboard.php" class="text-slate-500 font-semibold">Dashboard</a>
      <a href="Job_Management_Page.php" class="text-slate-500 font-semibold">Jobs</a>
      <a href="Applications_interview.php" class="text-primary font-bold">Applicants</a>
      <a href="report_generating.php" class="text-slate-500 font-semibold">Reports</a>
    </div>
    <div class="font-bold text-slate-700"><?= h($_SESSION["full_name"] ?? "Recruiter") ?></div>
  </header>

  <?php if ($flash): ?>
    <div class="px-8 pt-4">
      <div class="bg-primary/10 text-primary font-bold text-sm px-4 py-3 rounded-lg border border-primary/20">
        <?= h($flash) ?>
      </div>
    </div>
  <?php endif; ?>

  <main class="flex flex-1 overflow-hidden h-[calc(100vh-65px)]">

    <!-- Left Sidebar -->
    <aside class="w-full max-w-[380px] bg-white border-r border-primary/10 flex flex-col h-full">
      <div class="p-4 border-b border-primary/5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-lg">Applicants</h3>
          <span class="bg-primary/10 text-primary text-xs font-bold px-2 py-1 rounded-full"><?= (int)$newCount ?> New</span>
        </div>

        <div class="flex gap-2 mb-2">
          <a href="?filter=All&q=<?= urlencode($q) ?>" class="flex-1 text-xs font-bold py-2 px-3 rounded-lg <?= $filter_status==="All" ? "bg-primary text-white":"bg-[#f0f2f4] text-[#617589] hover:bg-primary/5" ?>">All</a>
          <a href="?filter=Screened&q=<?= urlencode($q) ?>" class="flex-1 text-xs font-bold py-2 px-3 rounded-lg <?= $filter_status==="Screened" ? "bg-primary text-white":"bg-[#f0f2f4] text-[#617589] hover:bg-primary/5" ?>">Screened</a>
          <a href="?filter=Rejected&q=<?= urlencode($q) ?>" class="flex-1 text-xs font-bold py-2 px-3 rounded-lg <?= $filter_status==="Rejected" ? "bg-primary text-white":"bg-[#f0f2f4] text-[#617589] hover:bg-primary/5" ?>">Rejected</a>
        </div>

        <form method="get" class="relative">
          <input type="hidden" name="filter" value="<?= h($filter_status) ?>">
          <span class="material-symbols-outlined absolute left-3 top-2.5 text-[#617589] text-lg">search</span>
          <input name="q" value="<?= h($q) ?>"
            class="w-full pl-10 pr-4 py-2 bg-[#f0f2f4] border-none rounded-lg text-sm focus:ring-1 focus:ring-primary/30"
            placeholder="Filter by name or headline" type="text" />
        </form>
      </div>

      <div class="flex-1 overflow-y-auto no-scrollbar">
        <?php if (count($applications) === 0): ?>
          <div class="p-6 text-sm text-slate-500">
            No applications found yet.
          </div>
        <?php endif; ?>

        <?php foreach ($applications as $app): ?>
          <?php
            $active = ((int)$app["application_id"] === (int)$selected_id);
            $status = $app["status"] ?? "New";
            $badgeClass = "bg-primary/20 text-primary";
            if ($status === "Screened") $badgeClass = "bg-orange-100 text-orange-600";
            if ($status === "Rejected") $badgeClass = "bg-red-100 text-red-600";
            if ($status === "Offered") $badgeClass = "bg-emerald-100 text-emerald-600";
          ?>
          <a href="?id=<?= (int)$app["application_id"] ?>&filter=<?= urlencode($filter_status) ?>&q=<?= urlencode($q) ?>"
             class="block p-4 border-b border-primary/5 cursor-pointer hover:bg-primary/5 transition-colors <?= $active ? "border-l-4 border-primary bg-primary/5":"border-l-4 border-transparent" ?>">
            <div class="flex gap-3">
              <div class="size-12 rounded-full bg-primary/10 shrink-0 flex items-center justify-center font-extrabold text-primary">
                <?= strtoupper(substr($app["full_name"] ?? "U", 0, 1)) ?>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start">
                  <h4 class="font-bold text-sm text-[#111418] truncate"><?= h($app["full_name"] ?? "Unknown") ?></h4>
                  <span class="text-[10px] text-[#617589]"><?= h(timeAgo($app["applied_at"] ?? "")) ?></span>
                </div>
                <p class="text-xs text-[#617589] mb-2 truncate"><?= h($app["headline"] ?? "No headline") ?></p>
                <div class="flex gap-2">
                  <span class="text-[10px] font-bold px-1.5 py-0.5 rounded <?= $badgeClass ?>"><?= h($status) ?></span>
                </div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </aside>

    <!-- Right Content -->
    <section class="flex-1 bg-white overflow-y-auto no-scrollbar flex flex-col">
      <?php if (!$selected): ?>
        <div class="p-10 text-slate-600">Select an applicant from the left.</div>
      <?php else: ?>

      <!-- Detail Header -->
      <div class="p-8 border-b border-primary/5 sticky top-0 bg-white z-10">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
          <div class="flex gap-6 items-center">
            <div class="size-24 rounded-2xl bg-primary/10 flex items-center justify-center text-primary font-extrabold text-3xl border-4 border-[#f0f2f4]">
              <?= strtoupper(substr($selected["full_name"], 0, 1)) ?>
            </div>
            <div>
              <h1 class="text-3xl font-extrabold text-[#111418] tracking-tight"><?= h($selected["full_name"]) ?></h1>
              <p class="text-primary font-bold text-lg mb-2">
                <?= h($selected["headline"] ?? "No headline") ?>
                <?php if (!empty($selected["location"])): ?>
                  <span class="text-[#617589] font-normal">• <?= h($selected["location"]) ?></span>
                <?php endif; ?>
              </p>
              <div class="flex gap-4 flex-wrap">
                <span class="flex items-center gap-1.5 text-xs font-bold text-[#617589]">
                  <span class="material-symbols-outlined text-sm">mail</span>
                  <?= h($selected["email"] ?? "") ?>
                </span>
              </div>
            </div>
          </div>

          <!-- Update Status -->
          <div class="flex flex-wrap gap-3">
            <form method="post" class="flex flex-col gap-1 min-w-[200px]">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="application_id" value="<?= (int)$selected["application_id"] ?>">
              <label class="text-[10px] uppercase tracking-wider font-bold text-[#617589]">Update Status</label>
              <select name="status" class="w-full bg-[#f0f2f4] border-none rounded-lg text-sm font-bold py-2.5 focus:ring-1 focus:ring-primary/30">
                <?php foreach (["New","Screened","Interviewing","Offered","Rejected"] as $st): ?>
                  <option value="<?= h($st) ?>" <?= ($selected["status"]===$st ? "selected":"") ?>><?= h($st) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="mt-2 h-10 px-4 bg-primary text-white rounded-lg font-bold text-sm hover:shadow-lg hover:shadow-primary/20 transition-all">
                Save Status
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Main Details -->
      <div class="p-8 grid grid-cols-1 xl:grid-cols-3 gap-8">

        <!-- Left -->
        <div class="xl:col-span-2 space-y-10">
          <div>
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
              <span class="material-symbols-outlined text-primary">person</span>
              Professional Summary
            </h3>
            <p class="text-[#617589] leading-relaxed">
              <?= nl2br(h($selected["bio"] ?? "No bio yet.")) ?>
            </p>
          </div>

          <!-- Feedback -->
          <div class="bg-primary/5 p-6 rounded-xl border border-primary/10">
            <div class="flex justify-between items-center mb-4">
              <h3 class="font-bold text-[#111418] flex items-center gap-2">
                <span class="material-symbols-outlined">rate_review</span>
                Interview Feedback
              </h3>
            </div>

            <form method="post" class="space-y-3">
              <input type="hidden" name="action" value="save_feedback">
              <input type="hidden" name="application_id" value="<?= (int)$selected["application_id"] ?>">

              <label class="text-[10px] uppercase tracking-wider font-bold text-[#617589]">Rating (1-5)</label>
              <input name="rating" type="number" min="1" max="5"
                value="<?= h($selectedFeedback["rating"] ?? "") ?>"
                class="w-24 bg-white border border-primary/20 rounded-lg px-3 py-2 text-sm font-bold focus:ring-1 focus:ring-primary">

              <textarea name="notes"
                class="w-full bg-white border border-primary/20 rounded-lg p-4 text-sm focus:ring-1 focus:ring-primary min-h-[120px]"
                placeholder="Add your notes about the candidate..."><?= h($selectedFeedback["notes"] ?? "") ?></textarea>

              <div class="flex justify-end">
                <button class="px-4 py-2 bg-white border border-primary/20 rounded-lg text-primary text-xs font-bold hover:bg-primary/5 transition-colors">
                  Save Feedback
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Right -->
        <div class="space-y-8">

          <!-- Schedule Interview -->
          <div class="bg-white border border-primary/10 rounded-xl overflow-hidden">
            <div class="bg-primary/5 px-4 py-3 border-b border-primary/10">
              <h4 class="text-xs font-bold uppercase tracking-wider text-primary">Schedule Interview</h4>
            </div>

            <form method="post" class="p-4 space-y-4">
              <input type="hidden" name="action" value="schedule_interview">
              <input type="hidden" name="application_id" value="<?= (int)$selected["application_id"] ?>">

              <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-[#617589] uppercase">Date</label>
                <div class="flex items-center gap-2 bg-[#f0f2f4] rounded-lg px-3 py-2 border border-transparent focus-within:border-primary/30">
                  <span class="material-symbols-outlined text-sm text-[#617589]">event</span>
                  <input name="interview_date"
                    class="bg-transparent border-none text-xs font-bold w-full focus:ring-0 p-0"
                    type="date"
                    value="<?= h($selectedInterview["interview_date"] ?? "") ?>"
                    required />
                </div>
              </div>

              <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-[#617589] uppercase">Time</label>
                <div class="flex items-center gap-2 bg-[#f0f2f4] rounded-lg px-3 py-2 border border-transparent focus-within:border-primary/30">
                  <span class="material-symbols-outlined text-sm text-[#617589]">schedule</span>
                  <input name="interview_time"
                    class="bg-transparent border-none text-xs font-bold w-full focus:ring-0 p-0"
                    type="time"
                    value="<?= h($selectedInterview["interview_time"] ?? "") ?>"
                    required />
                </div>
              </div>

              <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-[#617589] uppercase">Interview Type</label>
                <select name="interview_type"
                  class="bg-[#f0f2f4] rounded-lg px-3 py-2 border-none text-xs font-bold w-full focus:ring-0">
                  <?php foreach (["Google Meet","Zoom","In-Person Office"] as $t): ?>
                    <option value="<?= h($t) ?>" <?= (($selectedInterview["interview_type"] ?? "") === $t ? "selected":"") ?>><?= h($t) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <button class="w-full py-2.5 bg-primary/10 text-primary rounded-lg text-xs font-bold hover:bg-primary hover:text-white transition-all">
                Confirm Schedule
              </button>
            </form>
          </div>

          <!-- Attachments -->
          <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-[#617589] mb-4">Attachments</h4>
            <div class="text-sm text-slate-500">
              Resume is not available because your database table <b>applicant_profiles</b> currently has no resume columns.
              If you tell me where the resume is stored, I will connect it.
            </div>
          </div>

        </div>
      </div>

      <?php endif; ?>
    </section>
  </main>
</div>
</body>
</html>
