<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/dashboard.php');
}

verify_csrf();

$id = (int)($_POST['enrollment_id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($id <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
    flash('error', 'Invalid enrollment request.');
    redirect('admin/dashboard.php');
}

$stmt = $pdo->prepare("
    UPDATE enrollments
    SET status = ?, reviewed_at = NOW()
    WHERE id = ? AND status = 'pending'
");

$stmt->execute([$status, $id]);

if ($stmt->rowCount() > 0) {
    flash('success', 'Enrollment ' . $status . ' successfully.');
} else {
    flash('error', 'Enrollment was not updated.');
}

redirect('admin/dashboard.php');