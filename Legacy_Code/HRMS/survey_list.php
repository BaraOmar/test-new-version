<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/validation.php";
require_once __DIR__ . "/config/auth.php";

requireHRAdmin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

    $search            = validate_search($_GET['search'] ?? '');
    $date_from         = validate_date($_GET['date_from'] ?? '');
    $date_to           = validate_date($_GET['date_to'] ?? '');
    $anonymous_filter  = validate_whitelist($_GET['anonymous'] ?? '', ['anonymous', 'named'], '');
    $department_filter = validate_id($_GET['department_id'] ?? 0);

    // Departments for filter dropdown
    $deptStmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "
   SELECT 
       s.id,
       s.title,
       s.created_at,
       s.status,
       s.is_anonymous,
       CASE 
           WHEN EXISTS (SELECT 1 FROM survey_employee_targets se_a WHERE se_a.survey_id = s.id) THEN 'employee'
           WHEN EXISTS (SELECT 1 FROM survey_targets st WHERE st.survey_id = s.id) THEN 'department'
           ELSE 'organization'
       END AS audience_type,
       (
           SELECT COUNT(*) 
           FROM survey_responses sr2 
           WHERE sr2.survey_id = s.id
       ) AS responses,
       CASE 
           WHEN EXISTS (SELECT 1 FROM survey_employee_targets se2 WHERE se2.survey_id = s.id) THEN (
               SELECT COUNT(*) FROM survey_employee_targets se3 WHERE se3.survey_id = s.id
           )
           WHEN EXISTS (SELECT 1 FROM survey_targets st2 WHERE st2.survey_id = s.id) THEN (
               SELECT COUNT(*) 
               FROM employees emp
               WHERE emp.department_id IN (
                   SELECT st3.department_id 
                   FROM survey_targets st3 
                   WHERE st3.survey_id = s.id
               )
           )
           ELSE (SELECT COUNT(*) FROM employees emp_all)
       END AS target_count
   FROM surveys s
    ";

    $params = [];
    $conditions = [];
    if ($anonymous_filter !== '') {

    if ($anonymous_filter === 'anonymous') {
        $conditions[] = "s.is_anonymous = 1";
    }

    if ($anonymous_filter === 'named') {
        $conditions[] = "s.is_anonymous = 0";
    }

}

    if ($search !== '') {
        $conditions[] = "s.title LIKE ?";
        $params[] = "%$search%";
    }

    if ($date_from !== '') {
        $conditions[] = "DATE(s.created_at) >= ?";
        $params[] = $date_from;
    }

    if ($date_to !== '') {
        $conditions[] = "DATE(s.created_at) <= ?";
        $params[] = $date_to;
    }

    // Department filter: only when a specific department is selected (not "All")
    if ($department_filter > 0) {
        $conditions[] = "EXISTS (
            SELECT 1 
            FROM survey_targets stf 
            WHERE stf.survey_id = s.id
              AND stf.department_id = ?
        )";
        $params[] = $department_filter;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY s.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>

    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <title>Published Surveys</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <link href="assets/hrms-theme.css" rel="stylesheet">
    <script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    primary: "#642754",
                    "primary-dark": "#58264a",
                    accent: "#eb652c",
                    "accent-dark": "#d85a24",
                    "background-light": "#f8f4f8"
                },
                fontFamily: { display: ["Inter","sans-serif"] }
            }
        }
    };
    </script>
    <style>body{font-family:'Inter',sans-serif} .material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}</style>

    </head>


    <body class="bg-[#f8f4f8] text-slate-900 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <?php $currentPage = 'surveys'; include __DIR__ . '/navbar.php'; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php $pageTitle = 'Surveys'; include __DIR__ . '/admin_header.php'; ?>
    <main class="flex-1 p-8 overflow-y-auto">

    <!-- CONTENT HEADER -->

    <div class="flex items-center justify-between mb-8">

    <div>
   <form method="GET" class="mb-6 flex items-center gap-3 flex-wrap">

<!-- SEARCH -->
<div class="relative w-[420px]">

<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
search
</span>

<input
type="text"
name="search"
value="<?php echo htmlspecialchars($search); ?>"
placeholder="Search for published surveys..."
class="w-full h-11 pl-12 pr-4 rounded-xl border border-slate-200 focus:border-[#eb652c] focus:ring-2 focus:ring-[#eb652c]/20 outline-none bg-white shadow-sm"
/>

</div>

<!-- FROM DATE -->
<input
type="date"
name="date_from"
value="<?php echo htmlspecialchars($date_from); ?>"
class="h-11 px-3 rounded-lg border border-slate-200 shadow-sm min-w-[150px]"
/>

<!-- TO DATE -->
<input
type="date"
name="date_to"
value="<?php echo htmlspecialchars($date_to); ?>"
class="h-11 px-3 rounded-lg border border-slate-200 shadow-sm min-w-[150px]"
/>

<!-- DEPARTMENT -->
<select
name="department_id"
class="h-11 px-3 rounded-lg border border-slate-200 shadow-sm bg-white min-w-[190px]">
    <option value="">All Departments</option>
    <?php foreach ($departments as $dept): ?>
        <option value="<?php echo (int)$dept['id']; ?>"
            <?php if ((string)$department_filter === (string)$dept['id']) echo 'selected'; ?>>
            <?php echo htmlspecialchars($dept['name']); ?>
        </option>
    <?php endforeach; ?>
</select>

<!-- ANONYMITY -->
<select
name="anonymous"
class="h-11 px-3 rounded-lg border border-slate-200 shadow-sm bg-white min-w-[150px]">

<option value="">All Surveys</option>

<option value="anonymous"
<?php if($anonymous_filter==='anonymous') echo 'selected'; ?>>
Anonymous Only
</option>

<option value="named"
<?php if($anonymous_filter==='named') echo 'selected'; ?>>
Non-Anonymous Only
</option>

</select>
<!-- FILTER BUTTON -->
<button
type="submit"
class="h-11 px-5 bg-[#eb652c] hover:bg-[#d85a24] text-white rounded-lg font-semibold transition-all flex items-center gap-1"
>
    <span class="material-symbols-outlined text-sm">tune</span>
    Filter
</button>

<!-- RESET -->
<a
href="survey_list.php"
class="h-11 px-4 flex items-center border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 bg-white"
>
    Reset
</a>

</form>    <h2 class="text-2xl font-bold">
    Published Surveys
    </h2>

    <p class="text-slate-500 text-sm">
    <?php if(!empty($search)): ?>
    Showing <?php echo count($surveys); ?> results for "<?php echo htmlspecialchars($search); ?>"
    <?php else: ?>
    Showing <?php echo count($surveys); ?> surveys
    <?php endif; ?>
    </p>

    </div>


    <div class="flex items-center gap-3">

    <div class="flex border rounded-lg overflow-hidden bg-white">

    <button id="gridViewBtn" class="w-10 h-10 flex items-center justify-center hover:bg-slate-100">

    <span class="material-symbols-outlined">grid_view</span>

    </button>

    <button id="listViewBtn" class="w-10 h-10 flex items-center justify-center border-l hover:bg-slate-100">

    <span class="material-symbols-outlined">view_list</span>

    </button>

    </div>


    <a href="Employee_directory.php"
    class="border-2 border-[#642754] text-[#642754] px-5 h-10 flex items-center gap-2 rounded-lg font-semibold hover:bg-[#642754]/5 transition-colors">

    <span class="material-symbols-outlined">person_add</span>

    Send to Employee

    </a>

    <a href="survey_builder_interface.php"
    class="bg-[#eb652c] hover:bg-[#d85a24] text-white px-5 h-10 flex items-center gap-2 rounded-lg font-semibold shadow-md transition-all">

    <span class="material-symbols-outlined">add</span>

    Create Survey

    </a>

    </div>

    </div>


    <!-- SURVEYS -->

    <div id="surveyContainer" class="grid grid-cols-1 xl:grid-cols-2 2xl:grid-cols-3 gap-6">

    <?php foreach($surveys as $survey): ?>
    
    <?php
    $responses = (int)$survey['responses'];
    $target    = (int)($survey['target_count'] ?? 0);
    $rate      = $target > 0 ? round(($responses / $target) * 100, 1) : 0;
    ?>

    <div class="bg-white p-6 rounded-xl border shadow-sm flex flex-col justify-between">

    <div>

   <div class="flex items-center gap-2">

<span class="text-xs font-bold text-green-600 bg-green-100 px-2 py-1 rounded">
<?php echo htmlspecialchars($survey['status'] ?? 'ACTIVE'); ?>
</span>

<?php if($survey['is_anonymous']): ?>

<span class="text-xs font-bold text-[#eb652c] bg-[#eb652c]/10 px-2 py-1 rounded">
Anonymous
</span>

<?php else: ?>

<span class="text-xs font-bold text-amber-600 bg-amber-100 px-2 py-1 rounded">
Non-Anonymous
</span>

<?php endif; ?>

</div>

    <h3 class="text-lg font-bold mt-3">

    <?php echo htmlspecialchars($survey['title']); ?>

    </h3>

    <div class="text-xs text-slate-500 mt-2 flex gap-4">

    <div class="flex items-center gap-1">

    <span class="material-symbols-outlined text-sm">calendar_today</span>

    <?php echo date("M d, Y",strtotime($survey['created_at'])); ?>

    </div>

    <div class="flex items-center gap-1">
    
    <span class="material-symbols-outlined text-sm">group</span>
    
    <?php if($target > 0): ?>
        <?php echo $responses ?>/<?php echo $target ?> Respondents
    <?php else: ?>
        <?php echo $responses ?> Respondent<?php echo $responses === 1 ? '' : 's'; ?>
    <?php endif; ?>
    
    </div>

    </div>

    </div>


    <div class="mt-6 space-y-3">

    <form method="POST" action="delete_survey.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this survey?');">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="id" value="<?= (int)$survey['id'] ?>">
        <button type="submit" class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-md transition-all w-fit">
            <span class="material-symbols-outlined text-[16px]">delete</span>
            Delete
        </button>
    </form>


    <div>

    <div class="flex justify-between text-xs font-bold">

    Response Rate

    <span class="text-indigo-600"><?php echo $rate ?>%</span>

    </div>

    <div class="h-2 bg-slate-200 rounded-full mt-1">

    <div class="bg-indigo-500 h-2 rounded-full" style="width:<?php echo $rate ?>%"></div>

    </div>

    </div>


    <a
    href="survey_response.php?id=<?= (int)$survey['id'] ?>"
    class="border text-xs px-4 py-2 rounded-lg flex items-center gap-1 justify-center">

    <span class="material-symbols-outlined text-sm">visibility</span>

    View Responses

    </a>

    </div>

    </div>

    <?php endforeach; ?>

    </div>

    </main>

    </div>

    </div>


    <script>

    const container=document.getElementById("surveyContainer");

    const gridBtn=document.getElementById("gridViewBtn");

    const listBtn=document.getElementById("listViewBtn");

    gridBtn.onclick=()=>{
    container.classList.remove("grid-cols-1");
    container.classList.add("xl:grid-cols-2","2xl:grid-cols-3");
    };

    listBtn.onclick=()=>{
    container.classList.remove("xl:grid-cols-2","2xl:grid-cols-3");
    container.classList.add("grid-cols-1");
    };

    </script>


    </body>

    </html>