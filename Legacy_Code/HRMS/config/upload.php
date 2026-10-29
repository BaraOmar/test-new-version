<?php
/**
 * Secure file upload helpers - OWASP best practices
 * - Extension whitelist
 * - MIME type validation (content-based)
 * - Secure random filenames
 * - Path traversal prevention
 */

/**
 * Extension -> allowed MIME types (content must match)
 */
$GLOBALS['UPLOAD_ALLOWED_MIMES'] = [
    'mp4'   => ['video/mp4'],
    'webm'  => ['video/webm'],
    'ogg'   => ['video/ogg', 'application/ogg'],
    'mov'   => ['video/quicktime'],
    'avi'   => ['video/x-msvideo'],
    'mkv'   => ['video/x-matroska'],
    'pdf'   => ['application/pdf'],
    'pptx'  => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
    'ppt'   => ['application/vnd.ms-powerpoint'],
    'docx'  => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'doc'   => ['application/msword'],
    'jpg'   => ['image/jpeg'],
    'jpeg'  => ['image/jpeg'],
    'png'   => ['image/png'],
    'gif'   => ['image/gif'],
    'webp'  => ['image/webp'],
    'bmp'   => ['image/bmp'],
    // SVG removed - can contain JavaScript (XSS)
];

/**
 * Validate upload: extension whitelist + MIME match. Returns ['ok' => true, 'ext' => 'pdf', 'type' => 'pdf'] or ['ok' => false, 'error' => '...']
 */
function validateUploadedFile(array $file, array $allowedExtMap): array {
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Invalid upload.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext === '' || preg_match('/[^a-z0-9]/', $ext)) {
        return ['ok' => false, 'error' => 'Invalid file extension.'];
    }
    if (!isset($GLOBALS['UPLOAD_ALLOWED_MIMES'][$ext])) {
        return ['ok' => false, 'error' => 'File type not allowed.'];
    }
    $allowedMimes = $GLOBALS['UPLOAD_ALLOWED_MIMES'][$ext];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($detectedMime, $allowedMimes, true)) {
        return ['ok' => false, 'error' => 'File content does not match extension. Possible invalid or malicious file.'];
    }
    $detectedType = null;
    foreach ($allowedExtMap as $type => $exts) {
        if (in_array($ext, $exts, true)) {
            $detectedType = $type;
            break;
        }
    }
    if (!$detectedType) {
        return ['ok' => false, 'error' => 'File type not allowed.'];
    }
    return ['ok' => true, 'ext' => $ext, 'type' => $detectedType];
}

/**
 * Generate secure random filename: randomhex.ext (no user input)
 */
function secureUploadFilename(string $ext): string {
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

/**
 * Ensure path is within baseDir (prevent path traversal)
 */
function isPathWithin(string $baseDir, string $path): bool {
    $realBase = realpath($baseDir);
    $realPath = realpath($path);
    if ($realBase === false || $realPath === false) {
        return false;
    }
    return strpos($realPath, $realBase . DIRECTORY_SEPARATOR) === 0 || $realPath === $realBase;
}
