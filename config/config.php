<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define('APP_NAME', 'LearnHub');
define('BASE_URL', '/edu-platform');
define('UPLOAD_ROOT', dirname(__DIR__) . '/uploads');
define('MAX_RECEIPT_SIZE', 8 * 1024 * 1024);
define('MAX_VIDEO_SIZE', 500 * 1024 * 1024);

$dbHost = 'localhost';
$dbName = 'learnhub_platform';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(500);

    exit('Database connection failed. Import database.sql and check config/config.php.');
}