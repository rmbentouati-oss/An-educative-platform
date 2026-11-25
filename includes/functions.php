<?php
declare(strict_types=1);

function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }
function require_login(): void {
    if (!current_user()) { header('Location: ' . BASE_URL . '/auth/login.php'); exit; }
}
function require_admin(): void {
    require_login();
    if (!is_admin()) { http_response_code(403); exit('Access denied.'); }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419); exit('Invalid or expired form token. Refresh the page and try again.');
    }
}
function flash(string $type, ?string $message = null): ?string {
    if ($message !== null) { $_SESSION['flash'][$type] = $message; return null; }
    $value = $_SESSION['flash'][$type] ?? null; unset($_SESSION['flash'][$type]); return $value;
}
function redirect(string $path): never { header('Location: ' . BASE_URL . '/' . ltrim($path, '/')); exit; }
function make_slug(string $title, PDO $pdo, ?int $ignoreId = null): string {
    $base = strtolower(trim($title));
    $base = preg_replace('/[^a-z0-9]+/i', '-', $base) ?? '';
    $base = trim($base, '-');
    if ($base === '') $base = 'course';
    $base = substr($base, 0, 190);
    $slug = $base; $i = 1;
    while (true) {
        $sql = 'SELECT id FROM courses WHERE slug = ?';
        $params = [$slug];
        if ($ignoreId !== null) { $sql .= ' AND id <> ?'; $params[] = $ignoreId; }
        $sql .= ' LIMIT 1';
        $st = $pdo->prepare($sql); $st->execute($params);
        if (!$st->fetch()) return $slug;
        $suffix = '-' . $i++;
        $slug = substr($base, 0, 220 - strlen($suffix)) . $suffix;
    }
}
function upload_file(array $file, string $kind): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed. Check the file size and try again.');
    $max = $kind === 'video' ? MAX_VIDEO_SIZE : MAX_RECEIPT_SIZE;
    if (($file['size'] ?? 0) <= 0 || $file['size'] > $max) throw new RuntimeException('File is empty or exceeds the allowed size.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = $kind === 'video'
        ? ['video/mp4'=>'mp4', 'video/webm'=>'webm', 'video/ogg'=>'ogv', 'application/ogg'=>'ogv']
        : ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp', 'application/pdf'=>'pdf'];
    if (!isset($allowed[$mime])) throw new RuntimeException($kind === 'video' ? 'Only MP4, WebM, and OGG videos are allowed.' : 'Only JPG, PNG, WEBP, and PDF receipts are allowed.');
    $folder = UPLOAD_ROOT . ($kind === 'video' ? '/videos' : '/receipts');
    if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) throw new RuntimeException('Upload folder could not be created.');
    $name = bin2hex(random_bytes(18)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $folder . '/' . $name)) throw new RuntimeException('Could not save the uploaded file.');
    return 'uploads/' . ($kind === 'video' ? 'videos/' : 'receipts/') . $name;
}
function page_header(string $title): void { ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<header class="topbar"><a class="brand" href="<?= e(BASE_URL) ?>/index.php">LearnHub</a><nav>
<?php if (current_user()): ?><a href="<?= e(BASE_URL) ?>/<?= is_admin() ? 'admin/dashboard.php' : 'student/dashboard.php' ?>">Dashboard</a><a href="<?= e(BASE_URL) ?>/auth/logout.php">Log out</a><?php else: ?><a href="<?= e(BASE_URL) ?>/auth/login.php">Log in</a><a href="<?= e(BASE_URL) ?>/auth/register.php">Register</a><?php endif; ?>
</nav></header><main class="container">
<?php if ($m = flash('success')): ?><div class="alert success"><?= e($m) ?></div><?php endif; ?><?php if ($m = flash('error')): ?><div class="alert error"><?= e($m) ?></div><?php endif; ?>
<?php }
function page_footer(): void { ?></main><footer>© <?= date('Y') ?> LearnHub — Online Learning Platform</footer></body></html><?php }
