<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/validation.php";
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/upload.php";

requireHRAdmin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ensure lms_content table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS lms_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type ENUM('video', 'pdf', 'pptx', 'docx', 'image') NOT NULL,
    file_size INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_file_type (file_type),
    INDEX idx_created_at (created_at),
    INDEX idx_title (title)
)");

$uploadDir = __DIR__ . "/uploads/lms/";
$uploadMsg = '';
$uploadError = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_content'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        require_once __DIR__ . "/config/security_log.php";
        security_log('csrf_failure', ['context' => 'LMS_upload_delete']);
        http_response_code(403);
        die('Invalid request');
    }
    $deleteId = (int) ($_POST['delete_content'] ?? 0);
    $redirectParams = [
        'type' => validate_whitelist($_GET['type'] ?? '', ['video', 'pdf', 'pptx', 'docx', 'image'], ''),
        'search' => validate_search($_GET['search'] ?? ''),
        'sort' => validate_whitelist($_GET['sort'] ?? '', ['newest', 'oldest', 'az'], 'newest')
    ];
    $redirectQuery = http_build_query(array_filter($redirectParams));
    $redirectUrl = 'LMS_upload.php' . ($redirectQuery ? '?' . $redirectQuery : '');
    if ($deleteId > 0) {
        $stmt = $pdo->prepare("SELECT file_path FROM lms_content WHERE id = ?");
        $stmt->execute([$deleteId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $filePath = __DIR__ . '/' . preg_replace('/^\/+/', '', $row['file_path']);
            $absPath = realpath($filePath);
            if ($absPath !== false && isPathWithin($uploadDir, $absPath)) {
                @unlink($absPath);
            }
            $pdo->prepare("DELETE FROM lms_content WHERE id = ?")->execute([$deleteId]);
            require_once __DIR__ . "/config/security_log.php";
            security_log('admin_action', ['action' => 'lms_content_delete', 'content_id' => (string)$deleteId]);
            header('Location: ' . $redirectUrl . (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'deleted=1');
            exit;
        } else {
            $uploadError = 'Content not found.';
        }
    }
}

// Detect PHP post_max_size / upload_max_filesize exceeded (request rejected before script runs)
function phpSizeToBytes($value) {
    $value = trim((string) $value);
    $unit = strtolower(substr($value, -1));
    $num = (int) $value;
    return match ($unit) {
        'g' => $num * 1024 * 1024 * 1024,
        'm' => $num * 1024 * 1024,
        'k' => $num * 1024,
        default => (int) $value
    };
}
$postMaxBytes = phpSizeToBytes(ini_get('post_max_size'));
$uploadMaxBytes = phpSizeToBytes(ini_get('upload_max_filesize'));
$phpLimitBytes = min($postMaxBytes, $uploadMaxBytes);
$phpLimitDisplay = ini_get('upload_max_filesize');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $gotPostData = isset($_POST['lms_upload']);
    if ($contentLength > 0 && !$gotPostData && $contentLength >= $postMaxBytes) {
        $uploadError = "File size exceeds the server limit ({$phpLimitDisplay}). Your file is " . round($contentLength / 1024 / 1024, 1) . " MB. Please choose a smaller file or ask your administrator to increase the limit.";
    }
}

// Add 'image' to file_type enum if table already exists
try {
    $pdo->exec("ALTER TABLE lms_content MODIFY COLUMN file_type ENUM('video', 'pdf', 'pptx', 'docx', 'image') NOT NULL");
} catch (PDOException $e) {
    // Ignore if column already has image or table structure differs
}

// Allowed file config (SVG excluded - can contain XSS)
$allowedTypes = [
    'video' => ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'],
    'pdf'   => ['pdf'],
    'pptx'  => ['pptx', 'ppt'],
    'docx'  => ['docx', 'doc'],
    'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']
];
$maxSize = 100 * 1024 * 1024; // 100MB (configurable)

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lms_upload'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        require_once __DIR__ . "/config/security_log.php";
        security_log('csrf_failure', ['context' => 'LMS_upload']);
        http_response_code(403);
        die('Invalid request');
    }
    $title = validate_string($_POST['title'] ?? '', 255);
    $description = validate_string($_POST['description'] ?? '', 2000);
    $file = $_FILES['content_file'] ?? null;

    if ($title === '') {
        $uploadError = 'Please enter a content title.';
    } elseif (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $uploadError = 'Please select a file to upload.';
    } elseif ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $uploadError = 'File exceeds size limit.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadError = 'File upload failed. Please try again.';
    } elseif ($file['size'] > $maxSize) {
        $uploadError = 'File size exceeds 100MB limit.';
    } elseif ($file['size'] <= 0) {
        $uploadError = 'Invalid file.';
    } else {
        $validation = validateUploadedFile($file, $allowedTypes);
        if (!$validation['ok']) {
            $uploadError = $validation['error'];
        } else {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newName = secureUploadFilename($validation['ext']);
            $targetPath = $uploadDir . $newName;
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $relativePath = 'uploads/lms/' . $newName;
                $originalName = validate_string(pathinfo($file['name'], PATHINFO_BASENAME), 255) ?: $newName;
                $stmt = $pdo->prepare("INSERT INTO lms_content (title, description, file_path, file_name, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description ?: null, $relativePath, $originalName, $validation['type'], (int)$file['size']]);
                $newId = (int)$pdo->lastInsertId();
                require_once __DIR__ . "/config/security_log.php";
                security_log('admin_action', ['action' => 'lms_content_upload', 'content_id' => (string)$newId, 'file_type' => $validation['type']]);
                $uploadMsg = 'Content uploaded successfully.';
            } else {
                $uploadError = 'Failed to save file.';
            }
        }
    }
}

// Fetch content with filters (whitelisted)
$filterType = validate_whitelist($_GET['type'] ?? '', ['video', 'pdf', 'pptx', 'docx', 'image'], '');
$search = validate_search($_GET['search'] ?? '');
$sort = validate_whitelist($_GET['sort'] ?? '', ['newest', 'oldest', 'az'], 'newest');

$sql = "SELECT * FROM lms_content WHERE 1=1";
$params = [];
if ($filterType && in_array($filterType, ['video', 'pdf', 'pptx', 'docx', 'image'])) {
    $sql .= " AND file_type = ?";
    $params[] = $filterType;
}
if ($search !== '') {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= ($sort === 'oldest' ? " ORDER BY created_at ASC" : ($sort === 'az' ? " ORDER BY title ASC" : " ORDER BY created_at DESC"));

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contents = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCount = count($contents);

// Count by type for stats
$countStmt = $pdo->query("SELECT file_type, COUNT(*) as c FROM lms_content GROUP BY file_type");
$typeCounts = ['video' => 0, 'pdf' => 0, 'pptx' => 0, 'docx' => 0, 'image' => 0];
while ($row = $countStmt->fetch(PDO::FETCH_ASSOC)) {
    $typeCounts[$row['file_type']] = (int)$row['c'];
}
$totalAssets = array_sum($typeCounts);

function formatFileSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / (1024 * 1024), 1) . ' MB';
}

function cardStyles($type) {
    return match ($type) {
        'video' => ['bg' => 'bg-slate-900', 'icon' => 'play_circle', 'badge' => 'bg-[#eb652c]', 'label' => 'VIDEO'],
        'pdf'   => ['bg' => 'bg-red-50 dark:bg-red-950/20', 'icon' => 'picture_as_pdf', 'badge' => 'bg-red-500', 'label' => 'PDF'],
        'pptx'  => ['bg' => 'bg-orange-50 dark:bg-orange-950/20', 'icon' => 'present_to_all', 'badge' => 'bg-orange-500', 'label' => 'PPTX'],
        'docx'  => ['bg' => 'bg-blue-50 dark:bg-blue-950/20', 'icon' => 'description', 'badge' => 'bg-blue-500', 'label' => 'DOCX'],
        'image' => ['bg' => 'bg-emerald-50 dark:bg-emerald-950/20', 'icon' => 'image', 'badge' => 'bg-emerald-500', 'label' => 'IMAGE'],
        default => ['bg' => 'bg-slate-100', 'icon' => 'description', 'badge' => 'bg-slate-500', 'label' => htmlspecialchars(strtoupper((string)$type), ENT_QUOTES, 'UTF-8')]
    };
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LMS Content Management | SurveyCenter</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="assets/hrms-theme.css" rel="stylesheet" />
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
                    fontFamily: { display: ["Inter", "sans-serif"] }
                }
            }
        };
    </script>
    <style>body{font-family:'Inter',sans-serif} .material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24} .filled-icon{font-variation-settings:'FILL' 1}</style>
</head>

<body class="bg-[#f8f4f8] text-slate-900 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <?php $currentPage = 'lms'; include __DIR__ . '/navbar.php'; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php $pageTitle = 'LMS'; include __DIR__ . '/admin_header.php'; ?>
            <main class="flex-1 p-8 overflow-y-auto">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-8">
                <div>
                    <nav
                        class="flex items-center gap-2 text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-widest">
                        <a class="hover:text-primary" href="#">Admin</a>
                        <span class="material-symbols-outlined text-[10px]">chevron_right</span>
                        <span>Content Management</span>
                    </nav>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Content Library</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">Centralized management of learning materials and
                        assignments.</p>
                </div>
                <div class="flex gap-4">
                    <div
                        class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm flex items-center gap-4 min-w-[160px]">
                        <div class="bg-[#eb652c]/10 text-[#eb652c] p-2.5 rounded-xl">
                            <span class="material-symbols-outlined">description</span>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Total Assets</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white leading-none mt-1"><?= $totalAssets ?></p>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm flex items-center gap-4 min-w-[160px]">
                        <div class="bg-green-100 dark:bg-green-500/10 text-green-600 p-2.5 rounded-xl">
                            <span class="material-symbols-outlined">assignment_ind</span>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Assigned</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white leading-none mt-1"><?= $totalAssets > 0 ? round($totalAssets / $totalAssets * 100) : 0 ?>%</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-10">
                <div
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-8 shadow-sm">
                    <?php if ($uploadMsg): ?>
                        <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 text-sm font-semibold">
                            <?= htmlspecialchars($uploadMsg) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['deleted'])): ?>
                        <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 text-sm font-semibold">
                            Content deleted successfully.
                        </div>
                    <?php endif; ?>
                    <?php if ($uploadError): ?>
                        <div class="mb-6 rounded-2xl border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-500/10 p-5 flex items-start gap-4 shadow-sm">
                            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-red-100 dark:bg-red-500/20 flex items-center justify-center">
                                <span class="material-symbols-outlined text-red-600 dark:text-red-400 text-2xl filled-icon">error</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-red-800 dark:text-red-300 mb-1">Upload limit exceeded</h4>
                                <p class="text-sm text-red-700 dark:text-red-400 leading-relaxed"><?= htmlspecialchars($uploadError) ?></p>
                            </div>
                            <button type="button" onclick="this.parentElement.remove()" class="flex-shrink-0 p-2 rounded-lg text-red-500 hover:bg-red-100 dark:hover:bg-red-500/20 transition-colors" aria-label="Dismiss">
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data" id="lms-upload-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                        <input type="hidden" name="lms_upload" value="1" />
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                            <div class="group">
                                <label for="content_file" class="block cursor-pointer">
                                    <div
                                        class="relative rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 hover:border-[#eb652c]/50 hover:bg-[#eb652c]/5 transition-all p-12 flex flex-col items-center justify-center text-center h-full">
                                        <div
                                            class="w-16 h-16 rounded-2xl bg-[#eb652c]/10 text-[#eb652c] flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-sm">
                                            <span class="material-symbols-outlined text-3xl">upload_file</span>
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Drag and drop training
                                            content</h3>
                                        <p class="text-slate-500 dark:text-slate-400 max-w-xs mx-auto mb-6 text-sm">
                                            Support for MP4, WebM, PDF, PPTX, DOCX, and Images (JPG, PNG, GIF, WebP) up to 100MB.
                                        </p>
                                        <span
                                            class="inline-block bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 px-6 py-2.5 rounded-xl text-sm font-bold hover:shadow-lg transition-all">
                                            Browse Local Files
                                        </span>
                                        <input type="file" name="content_file" id="content_file" accept=".mp4,.webm,.ogg,.mov,.avi,.mkv,.pdf,.pptx,.ppt,.docx,.doc,.jpg,.jpeg,.png,.gif,.webp,.bmp" class="hidden" data-max-bytes="<?= min($phpLimitBytes, $maxSize) ?>" />
                                        <p id="file-name" class="text-xs text-primary font-semibold mt-2 hidden"></p>
                                        <div id="file-size-error" class="mt-3 hidden rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-500/10 p-4 flex items-start gap-3">
                                            <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 flex-shrink-0">warning</span>
                                            <div>
                                                <p class="font-semibold text-amber-800 dark:text-amber-300 text-sm">File too large</p>
                                                <p id="file-size-error-msg" class="text-xs text-amber-700 dark:text-amber-400 mt-0.5"></p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="flex flex-col gap-6">
                                <h4 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">settings</span>
                                    Content Configuration
                                </h4>
                                <div class="space-y-4">
                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Content
                                            Title</label>
                                        <input name="title" required
                                            class="w-full bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-primary focus:border-primary px-4 py-3"
                                            placeholder="e.g. Introduction to 2024 Safety Protocol" type="text" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Content
                                            Description</label>
                                        <textarea name="description"
                                            class="w-full bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-primary focus:border-primary px-4 py-3"
                                            placeholder="Briefly describe the purpose of this asset..." rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                    </div>
                                    <div class="flex justify-end gap-3 pt-2">
                                        <button type="button" onclick="document.getElementById('lms-upload-form').reset(); document.getElementById('file-name').classList.add('hidden');"
                                            class="px-6 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700">Clear</button>
                                        <button type="submit"
                                            class="px-8 py-2.5 bg-[#eb652c] text-white rounded-xl text-sm font-bold shadow-md hover:bg-[#d85a24] transition-all">Upload
                                            &amp; Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="flex flex-col gap-4 mb-8">
                <form method="get" class="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($filterType) ?>" />
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>" />
                    <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                        <div class="relative flex-1 md:flex-initial min-w-[200px]">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl">search</span>
                            <input type="search" name="search" value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search by title..."
                                class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-primary focus:border-primary outline-none" />
                        </div>
                        <button type="submit" class="px-5 py-2.5 bg-[#eb652c] text-white rounded-xl text-sm font-bold hover:bg-[#d85a24] transition-all">Search</button>
                    </div>
                </form>
                <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div class="flex items-center gap-2 overflow-x-auto pb-2 w-full md:w-auto">
                        <a href="?type=&search=<?= urlencode($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold shrink-0 transition-all <?= $filterType === '' ? 'bg-[#eb652c] text-white shadow-md' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-[#eb652c]' ?>">
                            <span class="material-symbols-outlined text-sm filled-icon">grid_view</span>
                            All Content
                        </a>
                        <a href="?type=video&search=<?= urlencode($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold shrink-0 transition-all <?= $filterType === 'video' ? 'bg-[#eb652c] text-white shadow-md' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-[#eb652c]' ?>">
                            <span class="material-symbols-outlined text-sm">movie</span>
                            Videos
                        </a>
                        <a href="?type=pdf&search=<?= urlencode($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold shrink-0 transition-all <?= $filterType === 'pdf' ? 'bg-[#eb652c] text-white shadow-md' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-[#eb652c]' ?>">
                            <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                            PDFs
                        </a>
                        <a href="?type=pptx&search=<?= urlencode($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold shrink-0 transition-all <?= $filterType === 'pptx' ? 'bg-[#eb652c] text-white shadow-md' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-[#eb652c]' ?>">
                            <span class="material-symbols-outlined text-sm">present_to_all</span>
                            Presentations
                        </a>
                        <a href="?type=docx&search=<?= urlencode($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold shrink-0 transition-all <?= $filterType === 'docx' ? 'bg-[#eb652c] text-white shadow-md' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-[#eb652c]' ?>">
                            <span class="material-symbols-outlined text-sm">description</span>
                            Word Docs
                        </a>
                        <a href="?type=image&search=<?= urlencode($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold shrink-0 transition-all <?= $filterType === 'image' ? 'bg-[#eb652c] text-white shadow-md' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-[#eb652c]' ?>">
                            <span class="material-symbols-outlined text-sm">image</span>
                            Images
                        </a>
                    </div>
                    <form method="get" class="flex items-center gap-3 w-full md:w-auto">
                        <input type="hidden" name="type" value="<?= htmlspecialchars($filterType) ?>" />
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>" />
                        <select name="sort" onchange="this.form.submit()"
                            class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 focus:ring-primary focus:border-primary py-2.5 px-4 outline-none">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="az" <?= $sort === 'az' ? 'selected' : '' ?>>A-Z</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-6" id="content-grid">
                <?php foreach ($contents as $c):
                    $styles = cardStyles($c['file_type']);
                    $sizeStr = $c['file_size'] ? formatFileSize($c['file_size']) : '';
                    $dateStr = date('M j, Y', strtotime($c['created_at']));
                ?>
                <div
                    class="group bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-2xl hover:border-primary/20 transition-all flex flex-col overflow-hidden">
                    <div class="relative aspect-video <?= $c['file_type'] === 'image' ? '' : $styles['bg'] ?> overflow-hidden flex items-center justify-center bg-slate-100 dark:bg-slate-900">
                        <?php if ($c['file_type'] === 'image'): ?>
                        <img src="<?= htmlspecialchars($c['file_path']) ?>" alt="<?= htmlspecialchars($c['title']) ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        <?php elseif ($c['file_type'] === 'video'): ?>
                        <div class="absolute inset-0 opacity-40 bg-gradient-to-t from-black/80 to-transparent"></div>
                        <?php endif; ?>
                        <?php if ($c['file_type'] !== 'image'): ?>
                        <span class="material-symbols-outlined text-5xl <?= $c['file_type'] === 'video' ? 'text-white/80' : 'text-slate-400' ?> group-hover:scale-110 transition-transform relative z-10">
                            <?= $styles['icon'] === 'play_circle' ? 'play_circle' : $styles['icon'] ?>
                        </span>
                        <?php endif; ?>
                        <div class="absolute top-3 left-3 flex gap-2">
                            <span class="<?= $styles['badge'] ?> text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider"><?= $styles['label'] ?></span>
                            <?php if ($sizeStr): ?>
                            <span class="bg-slate-900/60 text-white text-[10px] font-bold px-2 py-0.5 rounded backdrop-blur-sm"><?= $sizeStr ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="absolute top-3 right-3 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                            <a href="LMS_course_review.php?id=<?= (int)$c['id'] ?>"
                                class="p-1.5 rounded-lg <?= in_array($c['file_type'], ['video', 'image']) ? 'bg-black/40 text-white' : 'bg-black/10 dark:bg-white/10 text-slate-600 dark:text-white' ?> hover:bg-[#eb652c] hover:text-white transition-colors"
                                title="View">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                            </a>
                            <a href="<?= htmlspecialchars($c['file_path']) ?>" target="_blank" download
                                class="p-1.5 rounded-lg <?= in_array($c['file_type'], ['video', 'image']) ? 'bg-black/40 text-white' : 'bg-black/10 dark:bg-white/10 text-slate-600 dark:text-white' ?> hover:bg-[#eb652c] hover:text-white transition-colors"
                                title="Download">
                                <span class="material-symbols-outlined text-sm">download</span>
                            </a>
                            <form method="post" class="inline" onsubmit="return confirm('Delete &quot;<?= addslashes(htmlspecialchars($c['title'])) ?>&quot;? This cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                                <input type="hidden" name="delete_content" value="<?= (int)$c['id'] ?>" />
                                <button type="submit" class="p-1.5 rounded-lg <?= in_array($c['file_type'], ['video', 'image']) ? 'bg-black/40 text-white' : 'bg-black/10 dark:bg-white/10 text-slate-600 dark:text-white' ?> hover:bg-red-500 hover:text-white transition-colors" title="Delete">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="p-5 flex-1 flex flex-col">
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-900 dark:text-white leading-snug group-hover:text-primary transition-colors mb-2">
                                <?= htmlspecialchars($c['title']) ?>
                            </h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 line-clamp-2 leading-relaxed">
                                <?= $c['description'] ? htmlspecialchars($c['description']) : 'No description.' ?>
                            </p>
                        </div>
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-700 flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-medium text-slate-400 uppercase tracking-widest">Added <?= $dateStr ?></span>
                                <span class="flex items-center gap-1 text-[10px] font-bold text-green-500 bg-green-50 dark:bg-green-500/10 px-2 py-0.5 rounded">
                                    <span class="material-symbols-outlined text-[12px] filled-icon">check_circle</span>
                                    PUBLISHED
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($contents)): ?>
            <div class="text-center py-16 text-slate-500 dark:text-slate-400">
                <span class="material-symbols-outlined text-6xl mb-4 opacity-50">folder_off</span>
                <p class="font-semibold">No content found.</p>
                <p class="text-sm mt-1">Upload content above or adjust your filters.</p>
            </div>
            <?php endif; ?>
            <div class="mt-12 flex items-center justify-between border-t border-slate-200 dark:border-slate-700 pt-8">
                <p class="text-sm text-slate-500 font-bold">Showing <?= $totalCount ?> item<?= $totalCount !== 1 ? 's' : '' ?></p>
            </div>
            </main>
        </div>
    </div>

    <script>
        (function() {
            const fileInput = document.getElementById('content_file');
            const fileNameSpan = document.getElementById('file-name');
            const sizeErrorDiv = document.getElementById('file-size-error');
            const sizeErrorMsg = document.getElementById('file-size-error-msg');
            const maxBytes = parseInt(fileInput.dataset.maxBytes || '41943040', 10);
            const maxMB = (maxBytes / 1024 / 1024).toFixed(1);

            function formatSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            }

            function validateFile() {
                const file = fileInput.files[0];
                if (!file) {
                    sizeErrorDiv.classList.add('hidden');
                    return true;
                }
                if (file.size > maxBytes) {
                    sizeErrorMsg.textContent = '"' + file.name + '" is ' + formatSize(file.size) + '. Maximum allowed: ' + maxMB + ' MB.';
                    sizeErrorDiv.classList.remove('hidden');
                    return false;
                }
                sizeErrorDiv.classList.add('hidden');
                return true;
            }

            fileInput.addEventListener('change', function() {
                if (this.files.length) {
                    fileNameSpan.textContent = this.files[0].name;
                    fileNameSpan.classList.remove('hidden');
                    validateFile();
                } else {
                    fileNameSpan.classList.add('hidden');
                    sizeErrorDiv.classList.add('hidden');
                }
            });

            document.getElementById('lms-upload-form').addEventListener('submit', function(e) {
                if (!validateFile()) {
                    e.preventDefault();
                    sizeErrorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        })();
    </script>
</body>

</html>
