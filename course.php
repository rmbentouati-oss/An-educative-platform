<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(404);
    exit('Invalid course ID.');
}

// Get published course
$st = $pdo->prepare("
    SELECT *
    FROM courses
    WHERE id = ? AND status = 'published'
");

$st->execute([$id]);
$course = $st->fetch();

if (!$course) {
    http_response_code(404);
    exit('Course not found.');
}

// Get student enrollment
$enrollment = null;

if (current_user() && !is_admin()) {

    $q = $pdo->prepare("
        SELECT *
        FROM enrollments
        WHERE user_id = ? AND course_id = ?
        LIMIT 1
    ");

    $q->execute([
        current_user()['id'],
        $id
    ]);

    $enrollment = $q->fetch();
}

// Get course lessons
$lessons = $pdo->prepare("
    SELECT id, title, description, sort_order
    FROM lessons
    WHERE course_id = ?
    ORDER BY sort_order ASC, id ASC
");

$lessons->execute([$id]);
$lessonRows = $lessons->fetchAll();

// Check access
$hasAccess = is_admin() || (
    $enrollment &&
    $enrollment['status'] === 'approved'
);

// Course price
$isFree = (int)$course['is_free'] === 1;

// Page
page_header($course['title']);

?>

<main class="course-details-page">

    <!-- COURSE INFORMATION -->
    <section class="panel course-info">

        <h1><?= e($course['title']) ?></h1>

        <p class="course-description">
            <?= nl2br(e($course['description'])) ?>
        </p>

        <div class="course-meta">

            <span class="course-level">
                <?= e(ucfirst($course['level'])) ?>
            </span>

            <strong class="course-price">
                <?= $isFree
                    ? 'Free'
                    : number_format((float)$course['price'], 2) . ' DZD'
                ?>
            </strong>

        </div>

        <!-- USER NOT LOGGED IN -->
        <?php if (!current_user()): ?>

            <a class="btn" href="<?= e(BASE_URL) ?>/auth/login.php">
                Log in to enroll →
            </a>

        <!-- ADMIN -->
        <?php elseif (is_admin()): ?>

            <a class="btn"
               href="<?= e(BASE_URL) ?>/admin/course.php?id=<?= $id ?>">
                Manage course →
            </a>

        <!-- STUDENT ALREADY APPROVED -->
        <?php elseif ($enrollment && $enrollment['status'] === 'approved'): ?>

            <span class="badge approved">
                Enrolled
            </span>

        <!-- FREE COURSE -->
        <?php elseif ($isFree): ?>

            <form method="post"
                  action="<?= e(BASE_URL) ?>/student/enroll-free.php">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= e(csrf_token()) ?>">

                <input type="hidden"
                       name="course_id"
                       value="<?= $id ?>">

                <button type="submit">
                    Enroll for free →
                </button>

            </form>

        <!-- PAID COURSE: NO ENROLLMENT OR REJECTED -->
        <?php elseif (!$enrollment || $enrollment['status'] === 'rejected'): ?>

            <a class="btn"
               href="<?= e(BASE_URL) ?>/student/submit-payment.php?course_id=<?= $id ?>">
                Submit payment receipt →
            </a>

        <!-- PENDING ENROLLMENT -->
        <?php elseif ($enrollment['status'] === 'pending'): ?>

            <span class="badge pending">
                Pending Review
            </span>

            <p class="enrollment-message">
                Your payment is waiting for administrator review.
            </p>

        <?php endif; ?>

    </section>


    <!-- COURSE CURRICULUM -->
    <section class="panel curriculum-panel">

        <h2>Course curriculum</h2>

        <?php if (empty($lessonRows)): ?>

            <p class="empty-curriculum">
                Lessons will be available when the administrator adds them.
            </p>

        <?php elseif ($hasAccess): ?>

            <ol class="curriculum-list">

                <?php foreach ($lessonRows as $lesson): ?>

                    <li>

                        <a href="<?= e(BASE_URL) ?>/student/learn.php?course_id=<?= $id ?>&lesson_id=<?= (int)$lesson['id'] ?>">
                            <?= e($lesson['title']) ?>
                        </a>

                    </li>

                <?php endforeach; ?>

            </ol>

        <?php else: ?>

            <p class="empty-curriculum">
                <?= count($lessonRows) ?> video lesson(s) included.
                Enroll to access the videos.
            </p>

        <?php endif; ?>

    </section>

</main>

<?php page_footer(); ?>
