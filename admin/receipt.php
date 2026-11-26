<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

/*
|--------------------------------------------------------------------------
| 1. Validate enrollment ID
|--------------------------------------------------------------------------
*/

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    exit('Invalid receipt ID.');
}

/*
|--------------------------------------------------------------------------
| 2. Get receipt path from database
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT receipt_path
    FROM enrollments
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row || empty($row['receipt_path'])) {
    http_response_code(404);
    exit('Receipt not found.');
}

/*
|--------------------------------------------------------------------------
| 3. Resolve and validate file paths
|--------------------------------------------------------------------------
*/

$receiptDirectory = realpath(UPLOAD_ROOT . '/receipts');

$file = realpath(
    dirname(__DIR__) . '/' . $row['receipt_path']
);

if (!$receiptDirectory || !$file || !is_file($file)) {
    http_response_code(404);
    exit('Receipt file not found.');
}

// Make sure the file is inside the receipts directory
$basePath = rtrim($receiptDirectory, DIRECTORY_SEPARATOR)
    . DIRECTORY_SEPARATOR;

$checkFile = $file;
$checkBase = $basePath;

// Windows paths are case-insensitive
if (DIRECTORY_SEPARATOR === '\\') {
    $checkFile = strtolower($checkFile);
    $checkBase = strtolower($checkBase);
}

if (strncmp($checkFile, $checkBase, strlen($checkBase)) !== 0) {
    http_response_code(403);
    exit('Access denied.');
}

/*
|--------------------------------------------------------------------------
| 4. Validate MIME type
|--------------------------------------------------------------------------
*/

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file);

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf'
];

if (!isset($allowedTypes[$mime])) {
    http_response_code(415);
    exit('Unsupported receipt file.');
}

/*
|--------------------------------------------------------------------------
| 5. Display receipt
|--------------------------------------------------------------------------
*/

$filename = 'payment-receipt.' . $allowedTypes[$mime];

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('Content-Disposition: inline; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, no-cache, must-revalidate');

readfile($file);
exit;