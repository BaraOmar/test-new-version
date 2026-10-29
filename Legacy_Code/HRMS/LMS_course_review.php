<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/validation.php";
require_once __DIR__ . "/config/auth.php";

requireLogin();

$viewContent = null;
$contentId = validate_id($_GET['id'] ?? 0);

if ($contentId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM lms_content WHERE id = ?");
    $stmt->execute([$contentId]);
    $viewContent = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all content for library view (when not viewing single item)
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
$sql .= match ($sort) {
    'oldest' => " ORDER BY created_at ASC",
    'az'     => " ORDER BY title ASC",
    default  => " ORDER BY created_at DESC"
};

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contents = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCount = count($contents);

$countStmt = $pdo->query("SELECT COUNT(*) FROM lms_content");
$totalAssets = (int)$countStmt->fetchColumn();

function formatFileSize($bytes) {
    if (!$bytes) return '';
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / (1024 * 1024), 1) . ' MB';
}

function cardStyles($type) {
    return match ($type) {
        'video' => ['bg' => 'bg-[#0f172a]', 'icon' => 'play_circle', 'badge' => 'bg-[#eb652c]', 'label' => 'VIDEO'],
        'pdf'   => ['bg' => 'bg-[#fff1f2]', 'icon' => 'picture_as_pdf', 'badge' => 'bg-rose-500', 'label' => 'PDF'],
        'pptx'  => ['bg' => 'bg-[#fffbeb]', 'icon' => 'present_to_all', 'badge' => 'bg-orange-500', 'label' => 'PPTX'],
        'docx'  => ['bg' => 'bg-[#eff6ff]', 'icon' => 'description', 'badge' => 'bg-blue-500', 'label' => 'DOCX'],
        'image' => ['bg' => 'bg-[#ecfdf5]', 'icon' => 'image', 'badge' => 'bg-emerald-500', 'label' => 'IMAGE'],
        default => ['bg' => 'bg-slate-100', 'icon' => 'description', 'badge' => 'bg-slate-500', 'label' => htmlspecialchars(strtoupper((string)$type), ENT_QUOTES, 'UTF-8')]
    };
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>HR LMS Content Library | CultureHR</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
        rel="stylesheet" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#642754",
                        accent: "#eb652c",
                        "background-light": "#f8f4f8",
                        "card-bg": "#ffffff",
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"],
                        body: ["Inter", "sans-serif"],
                    },
                },
            },
        };
    </script>
    <style type="text/tailwindcss">
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .filled-icon {
            font-variation-settings: 'FILL' 1;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
    </style>
</head>

<body class="font-body bg-[#f8f4f8] text-slate-900 antialiased min-h-screen flex flex-col">
    <?php $currentPage = 'lms'; $pageTitle = 'LMS'; include __DIR__ . '/employee_navbar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-auto shrink-0 bg-white/80 backdrop-blur-sm px-8 py-4 flex flex-wrap items-center justify-between gap-4 z-10 border-b border-slate-200 shadow-sm">
            <a href="LMS_course_review.php" class="flex items-center gap-2 text-slate-600 hover:text-primary transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
                <span class="text-sm font-bold">Back to Library</span>
            </a>
            <div class="flex items-center gap-3 flex-wrap">
                <a href="?type=&search=<?= urlencode($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold transition-colors <?= $filterType === '' ? 'bg-[#eb652c] text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' ?>">
                    <span class="material-symbols-outlined text-[18px]">grid_view</span>
                    All Content
                </a>
                <a href="?type=video&search=<?= urlencode($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium transition-colors <?= $filterType === 'video' ? 'bg-[#eb652c] text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' ?>">
                    <span class="material-symbols-outlined text-[18px]">play_circle</span>
                    Videos
                </a>
                <a href="?type=pdf&search=<?= urlencode($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium transition-colors <?= $filterType === 'pdf' ? 'bg-[#eb652c] text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' ?>">
                    <span class="material-symbols-outlined text-[18px]">description</span>
                    PDFs
                </a>
                <a href="?type=pptx&search=<?= urlencode($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium transition-colors <?= $filterType === 'pptx' ? 'bg-[#eb652c] text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' ?>">
                    <span class="material-symbols-outlined text-[18px]">present_to_all</span>
                    Presentations
                </a>
                <a href="?type=docx&search=<?= urlencode($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium transition-colors <?= $filterType === 'docx' ? 'bg-[#eb652c] text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' ?>">
                    <span class="material-symbols-outlined text-[18px]">description</span>
                    Word Docs
                </a>
                <a href="?type=image&search=<?= urlencode($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium transition-colors <?= $filterType === 'image' ? 'bg-[#eb652c] text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' ?>">
                    <span class="material-symbols-outlined text-[18px]">image</span>
                    Images
                </a>
            </div>
            <form method="get" class="flex items-center gap-2">
                <input type="hidden" name="type" value="<?= htmlspecialchars($filterType) ?>" />
                <input type="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search..."
                    class="px-4 py-2 border border-slate-200 rounded-xl text-sm" />
                <select name="sort" onchange="this.form.submit()"
                    class="px-4 py-2 border border-slate-200 rounded-xl text-sm font-medium">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="az" <?= $sort === 'az' ? 'selected' : '' ?>>A-Z</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-[#eb652c] hover:bg-[#d85a24] text-white rounded-xl text-sm font-bold transition-all">Search</button>
            </form>
        </header>
        <main class="flex-1 overflow-y-auto custom-scrollbar p-8">
            <?php if ($viewContent): ?>
            <!-- Single content viewer -->
            <div class="max-w-4xl mx-auto">
                <div class="mb-6">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"><?= htmlspecialchars(strtoupper((string)($viewContent['file_type'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                    <h1 class="text-2xl font-black text-slate-900 mt-1"><?= htmlspecialchars($viewContent['title']) ?></h1>
                    <?php if ($viewContent['description']): ?>
                    <p class="text-slate-600 mt-2"><?= nl2br(htmlspecialchars($viewContent['description'])) ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-slate-400 mt-2">Added <?= date('M j, Y', strtotime($viewContent['created_at'])) ?></p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <?php if ($viewContent['file_type'] === 'video'): ?>
                    <video controls class="w-full aspect-video bg-black">
                        <source src="<?= htmlspecialchars($viewContent['file_path']) ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <?php elseif ($viewContent['file_type'] === 'pdf'): ?>
                    <iframe src="<?= htmlspecialchars($viewContent['file_path']) ?>#toolbar=1" class="w-full h-[70vh] border-0" title="PDF Viewer"></iframe>
                    <?php elseif ($viewContent['file_type'] === 'image'): ?>
                    <div class="p-6 flex flex-col items-center gap-4">
                        <img src="<?= htmlspecialchars($viewContent['file_path']) ?>" alt="<?= htmlspecialchars($viewContent['title']) ?>" class="max-w-full max-h-[70vh] object-contain rounded-lg" />
                        <a href="<?= htmlspecialchars($viewContent['file_path']) ?>" download
                            class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-xl font-bold hover:bg-primary/90 transition-colors">
                            <span class="material-symbols-outlined">download</span>
                            Download Image
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="p-12 text-center">
                        <span class="material-symbols-outlined text-6xl text-slate-300 mb-4"><?= $viewContent['file_type'] === 'pptx' ? 'present_to_all' : 'description' ?></span>
                        <p class="text-slate-600 mb-4">Preview not available. Download to view.</p>
                        <a href="<?= htmlspecialchars($viewContent['file_path']) ?>" download
                            class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-xl font-bold hover:bg-primary/90 transition-colors">
                            <span class="material-symbols-outlined">download</span>
                            Download <?= htmlspecialchars(strtoupper((string)($viewContent['file_type'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <!-- Content grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-6">
                <?php foreach ($contents as $c):
                    $styles = cardStyles($c['file_type']);
                    $sizeStr = formatFileSize($c['file_size'] ?? 0);
                    $dateStr = date('M j, Y', strtotime($c['created_at']));
                ?>
                <a href="?id=<?= (int)$c['id'] ?>"
                    class="bg-white rounded-[24px] border border-slate-100 shadow-sm overflow-hidden flex flex-col group hover:shadow-md transition-shadow">
                    <div class="relative h-48 <?= $c['file_type'] === 'image' ? 'bg-slate-100' : $styles['bg'] ?> flex items-center justify-center overflow-hidden">
                        <?php if ($c['file_type'] === 'image'): ?>
                        <img src="<?= htmlspecialchars($c['file_path']) ?>" alt="<?= htmlspecialchars($c['title']) ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        <?php endif; ?>
                        <div class="absolute top-3 left-3 flex gap-1.5 z-10">
                            <span class="<?= $styles['badge'] ?> px-2 py-0.5 rounded text-[10px] font-black text-white"><?= $styles['label'] ?></span>
                            <?php if ($sizeStr): ?>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= in_array($c['file_type'], ['video', 'image']) ? 'bg-black/40 text-white' : 'bg-black/10 text-slate-700' ?>"><?= $sizeStr ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($c['file_type'] !== 'image'): ?>
                        <span class="material-symbols-outlined text-6xl <?= $c['file_type'] === 'video' ? 'text-white/80' : 'text-slate-300' ?> group-hover:scale-110 transition-transform relative z-10">
                            <?= $styles['icon'] === 'play_circle' ? 'play_arrow' : $styles['icon'] ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="p-6 flex-1 flex flex-col">
                        <h3 class="font-bold text-slate-900 mb-2 leading-tight group-hover:text-primary transition-colors"><?= htmlspecialchars($c['title']) ?></h3>
                        <p class="text-xs text-slate-500 mb-4 line-clamp-2"><?= $c['description'] ? htmlspecialchars($c['description']) : 'No description.' ?></p>
                        <div class="mt-auto pt-4 border-t border-slate-50 flex items-center justify-between">
                            <span class="text-[9px] uppercase font-bold text-slate-400">Added <?= $dateStr ?></span>
                            <span class="flex items-center gap-1 text-[10px] font-bold text-emerald-500 uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                View
                            </span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if (empty($contents)): ?>
            <div class="text-center py-16 text-slate-500">
                <span class="material-symbols-outlined text-6xl mb-4 opacity-50">folder_off</span>
                <p class="font-semibold">No content found.</p>
                <p class="text-sm mt-1">Upload content from <a href="LMS_upload.php" class="text-primary font-bold hover:underline">Content Management</a>.</p>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </main>
        <footer class="h-16 shrink-0 border-t border-slate-200 bg-white px-8 flex items-center justify-between z-10">
            <span class="text-xs font-bold text-slate-500">Showing <?= $totalCount ?> item<?= $totalCount !== 1 ? 's' : '' ?> (<?= $totalAssets ?> total)</span>
        </footer>
    </div>
</body>

</html>
