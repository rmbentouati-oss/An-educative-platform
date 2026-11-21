<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT receipt_path FROM enrollments WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row || !$row['receipt_path']) { http_response_code(404); exit('Receipt not found.'); }
$base = realpath(UPLOAD_ROOT . '/receipts');
$file = realpath(dirname(__DIR__) . '/' . $row['receipt_path']);
if (!$base || !$file || !str_starts_with($file, $base . DIRECTORY_SEPARATOR) || !is_file($file)) { http_response_code(404); exit('Receipt file not found.'); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
$allowed = ['image/jpeg','image/png','image/webp','application/pdf'];
if (!in_array($mime, $allowed, true)) { http_response_code(415); exit('Unsupported receipt file.'); }
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="payment-receipt"');
readfile($file);
exit;
