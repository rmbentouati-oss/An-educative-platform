<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/dashboard.php');
}

verify_csrf();

$courseId = (int)($_POST['course_id'] ?? 0);

if ($courseId <= 0) {
    flash('error', 'Invalid course ID.');
    redirect('admin/dashboard.php');
}

try {

    // Check if course exists
    $stmt = $pdo->prepare("
        SELECT id, title, image
        FROM courses
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$courseId]);
    $course = $stmt->fetch();

    if (!$course) {
        flash('error', 'Course not found.');
        redirect('admin/dashboard.php');
    }

    // Get lesson video paths
    $stmt = $pdo->prepare("
        SELECT video_path
        FROM lessons
        WHERE course_id = ?
    ");

    $stmt->execute([$courseId]);
    $lessons = $stmt->fetchAll();

    // Get enrollment receipt paths
    $stmt = $pdo->prepare("
        SELECT receipt_path
        FROM enrollments
        WHERE course_id = ?
    ");

    $stmt->execute([$courseId]);
    $enrollments = $stmt->fetchAll();

    // Start database transaction
    $pdo->beginTransaction();

    // Delete enrollments
    $stmt = $pdo->prepare("
        DELETE FROM enrollments
        WHERE course_id = ?
    ");

    $stmt->execute([$courseId]);

    // Delete lessons
    $stmt = $pdo->prepare("
        DELETE FROM lessons
        WHERE course_id = ?
    ");

    $stmt->execute([$courseId]);

    // Delete course
    $stmt = $pdo->prepare("
        DELETE FROM courses
        WHERE id = ?
    ");

    $stmt->execute([$courseId]);

    $pdo->commit();

    // Safely remove uploaded files belonging to this course
    $filesToDelete = [$course['image']];

    foreach ($lessons as $lesson) {
        $filesToDelete[] = $lesson['video_path'];
    }

    foreach ($enrollments as $enrollment) {
        $filesToDelete[] = $enrollment['receipt_path'];
    }

    $uploadDirectory = realpath(UPLOAD_ROOT);

    if ($uploadDirectory !== false) {

        foreach ($filesToDelete as $relativePath) {

            if (
                !is_string($relativePath) ||
                $relativePath === '' ||
                !str_starts_with(str_replace('\\', '/', $relativePath), 'uploads/')
            ) {
                continue;
            }

            $fullPath = realpath(dirname(__DIR__) . '/' . $relativePath);

            if (
                $fullPath !== false &&
                str_starts_with(
                    $fullPath,
                    $uploadDirectory . DIRECTORY_SEPARATOR
                ) &&
                is_file($fullPath)
            ) {
                unlink($fullPath);
            }
        }
    }

    flash('success', 'Course deleted successfully.');

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Delete Course Error: ' . $e->getMessage());

    flash('error', 'Could not delete the course. Please try again.');
}

redirect('admin/dashboard.php');