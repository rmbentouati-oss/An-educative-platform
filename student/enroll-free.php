<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

// Only students can enroll
if (is_admin()) {
    redirect('admin/dashboard.php');
}

// Only POST requests are allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

// Verify CSRF token
verify_csrf();

// Get course ID
$courseId = (int) ($_POST['course_id'] ?? 0);

if ($courseId <= 0) {
    flash('error', 'Invalid course.');
    redirect('index.php');
}

try {

    // Check if the course exists, is published and free
    $stmt = $pdo->prepare("
        SELECT id
        FROM courses
        WHERE id = ?
          AND status = 'published'
          AND is_free = 1
        LIMIT 1
    ");

    $stmt->execute([$courseId]);

    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        flash('error', 'This free course is not available.');
        redirect('index.php');
    }

    // Enroll student
    $stmt = $pdo->prepare("
        INSERT INTO enrollments (user_id, course_id, status)
        VALUES (?, ?, 'approved')
        ON DUPLICATE KEY UPDATE
            status = 'approved',
            reviewed_at = NOW()
    ");

    $stmt->execute([
        current_user()['id'],
        $courseId
    ]);

    flash('success', 'You are now enrolled in this free course.');

    redirect('student/dashboard.php');

} catch (PDOException $e) {

    error_log('Free enrollment error: ' . $e->getMessage());

    flash('error', 'Something went wrong. Please try again.');

    redirect('index.php');
}