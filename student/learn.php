<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if (is_admin()) {
    redirect('admin/dashboard.php');
}

$user = current_user();

$id = (int)($_GET['course_id'] ?? 0);


// =====================================
// CHECK APPROVED ENROLLMENT
// =====================================

$st = $pdo->prepare("
    SELECT c.*
    FROM courses c
    JOIN enrollments e ON e.course_id = c.id
    WHERE c.id = ?
      AND e.user_id = ?
      AND e.status = 'approved'
    LIMIT 1
");

$st->execute([$id, $user['id']]);

$course = $st->fetch();

if (!$course) {
    http_response_code(403);
    exit('You must have an approved enrollment to access this course.');
}


// =====================================
// GET COURSE LESSONS
// =====================================

$q = $pdo->prepare("
    SELECT *
    FROM lessons
    WHERE course_id = ?
    ORDER BY sort_order ASC, id ASC
");

$q->execute([$id]);

$lessons = $q->fetchAll();

$totalLessons = count($lessons);


// =====================================
// MARK LESSON AS COMPLETED
// =====================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['complete_lesson'])
) {

    verify_csrf();

    $completedLessonId = (int)($_POST['lesson_id'] ?? 0);

    // Make sure the lesson belongs to this course
    $check = $pdo->prepare("
        SELECT id
        FROM lessons
        WHERE id = ?
          AND course_id = ?
        LIMIT 1
    ");

    $check->execute([$completedLessonId, $id]);

    if ($check->fetch()) {

        $insert = $pdo->prepare("
            INSERT IGNORE INTO lesson_progress (
                user_id,
                lesson_id
            )
            VALUES (?, ?)
        ");

        $insert->execute([
            $user['id'],
            $completedLessonId
        ]);

        flash('success', 'Lesson marked as completed!');
    }

    redirect(
        'student/learn.php?course_id=' . $id .
        '&lesson_id=' . $completedLessonId
    );
}


// =====================================
// CALCULATE COURSE PROGRESS
// =====================================

$progressQuery = $pdo->prepare("
    SELECT COUNT(*)
    FROM lesson_progress lp
    INNER JOIN lessons l ON l.id = lp.lesson_id
    WHERE lp.user_id = ?
      AND l.course_id = ?
");

$progressQuery->execute([
    $user['id'],
    $id
]);

$completedLessons = (int)$progressQuery->fetchColumn();

$progressPercentage = $totalLessons > 0
    ? (int)round(($completedLessons / $totalLessons) * 100)
    : 0;


// =====================================
// GET CURRENT LESSON
// =====================================

$lessonId = (int)(
    $_GET['lesson_id'] ??
    ($lessons[0]['id'] ?? 0)
);

$current = null;
$currentIndex = -1;

foreach ($lessons as $index => $lesson) {

    if ((int)$lesson['id'] === $lessonId) {

        $current = $lesson;
        $currentIndex = $index;

        break;
    }
}


// If lesson ID is invalid, select first lesson
if ($current === null && !empty($lessons)) {

    $current = $lessons[0];
    $currentIndex = 0;
}


// =====================================
// PREVIOUS AND NEXT LESSONS
// =====================================

$previousLesson = $currentIndex > 0
    ? $lessons[$currentIndex - 1]
    : null;

$nextLesson = (
    $currentIndex >= 0 &&
    $currentIndex < $totalLessons - 1
)
    ? $lessons[$currentIndex + 1]
    : null;


// =====================================
// CHECK CURRENT LESSON COMPLETION
// =====================================

$lessonCompleted = false;

if ($current) {

    $completedCheck = $pdo->prepare("
        SELECT id
        FROM lesson_progress
        WHERE user_id = ?
          AND lesson_id = ?
        LIMIT 1
    ");

    $completedCheck->execute([
        $user['id'],
        $current['id']
    ]);

    $lessonCompleted = (bool)$completedCheck->fetch();
}


// =====================================
// PAGE
// =====================================

page_header('Learning: ' . $course['title']);

?>


<style>
@import url("<?= e(BASE_URL) ?>/assets/css/learn.css");
</style>


<div class="learning-page">


    <!-- TOP BAR -->

    <div class="learning-topbar">

        <a
            href="<?= e(BASE_URL) ?>/student/dashboard.php"
            class="back-dashboard"
        >
            <span>←</span> My Dashboard
        </a>

        <div class="learning-brand">
            <span class="brand-icon">✦</span>
            LearnHub
        </div>

    </div>


    <!-- COURSE HEADER -->

    <section class="learning-course-header">

        <div class="course-header-content">

            <span class="learning-label">
                YOUR LEARNING SPACE
            </span>

            <h1><?= e($course['title']) ?></h1>

            <p>
                Continue learning and take another step towards your goals.
            </p>


            <div class="learning-course-info">

                <span>
                    ◷
                    <?= number_format((float)($course['duration_hours'] ?? 0), 2) ?>
                    Hours
                </span>

                <span>
                    ▤
                    <?= $totalLessons ?>
                    <?= $totalLessons === 1 ? 'Lesson' : 'Lessons' ?>
                </span>

                <span>
                    ✦
                    <?= e(ucfirst($course['level'])) ?>
                </span>

            </div>


            <!-- PROGRESS BAR -->

            <div class="course-progress">

                <div class="progress-heading">

                    <strong>Your Progress</strong>

                    <span>
                        <?= $progressPercentage ?>% Completed
                    </span>

                </div>

                <div class="progress-track">

                    <div
                        class="progress-fill"
                        style="width: <?= $progressPercentage ?>%"
                    ></div>

                </div>

                <small>
                    <?= $completedLessons ?>
                    of
                    <?= $totalLessons ?>
                    lessons completed
                </small>

            </div>

        </div>


        <div class="course-header-decoration">
            ✦
        </div>

    </section>


    <!-- LEARNING CONTENT -->

    <div class="learning-layout">


        <!-- VIDEO SECTION -->

        <main class="learning-main">

            <section class="video-card">


                <?php if ($current): ?>


                    <!-- VIDEO HEADING -->

                    <div class="video-heading">

                        <div>

                            <span class="video-section-label">
                                NOW LEARNING
                            </span>

                            <h2>
                                <?= e($current['title']) ?>
                            </h2>

                        </div>

                        <span class="lesson-number">

                            Lesson <?= $currentIndex + 1 ?>
                            /
                            <?= $totalLessons ?>

                        </span>

                    </div>


                    <!-- VIDEO PLAYER -->

                    <div class="video-container">

                        <?php if (!empty($current['video_path'])): ?>

                            <video
                                class="learning-video"
                                controls
                                controlsList="nodownload"
                                preload="metadata"
                            >

                                <source
                                    src="<?= e(BASE_URL . '/' . $current['video_path']) ?>"
                                >

                                Your browser does not support video playback.

                            </video>

                        <?php else: ?>

                            <div class="video-empty">

                                <div class="video-empty-icon">
                                    ▶
                                </div>

                                <h3>Video not available</h3>

                                <p>
                                    The instructor has not uploaded a video
                                    for this lesson yet.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- LESSON DESCRIPTION -->

                    <?php if (!empty($current['description'])): ?>

                        <div class="lesson-description">

                            <h3>About this lesson</h3>

                            <p>
                                <?= nl2br(e($current['description'])) ?>
                            </p>

                        </div>

                    <?php endif; ?>


                    <!-- MARK AS COMPLETED -->

                    <form method="POST" class="complete-lesson-form">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrf_token()) ?>"
                        >

                        <input
                            type="hidden"
                            name="lesson_id"
                            value="<?= (int)$current['id'] ?>"
                        >


                        <?php if ($lessonCompleted): ?>

                            <button
                                type="button"
                                class="completed-button"
                                disabled
                            >
                                ✓ Lesson Completed
                            </button>

                        <?php else: ?>

                            <button
                                type="submit"
                                name="complete_lesson"
                                value="1"
                                class="complete-button"
                            >
                                ✓ Mark as Completed
                            </button>

                        <?php endif; ?>

                    </form>


                    <!-- LESSON NAVIGATION -->

                    <div class="lesson-navigation">


                        <?php if ($previousLesson): ?>

                            <a
                                class="lesson-nav-btn previous-btn"
                                href="<?= e(BASE_URL) ?>/student/learn.php?course_id=<?= $id ?>&lesson_id=<?= (int)$previousLesson['id'] ?>"
                            >

                                <span>←</span>

                                <div>

                                    <small>Previous lesson</small>

                                    <strong>
                                        <?= e($previousLesson['title']) ?>
                                    </strong>

                                </div>

                            </a>

                        <?php else: ?>

                            <div></div>

                        <?php endif; ?>


                        <?php if ($nextLesson): ?>

                            <a
                                class="lesson-nav-btn next-btn"
                                href="<?= e(BASE_URL) ?>/student/learn.php?course_id=<?= $id ?>&lesson_id=<?= (int)$nextLesson['id'] ?>"
                            >

                                <div>

                                    <small>Next lesson</small>

                                    <strong>
                                        <?= e($nextLesson['title']) ?>
                                    </strong>

                                </div>

                                <span>→</span>

                            </a>

                        <?php else: ?>

                            <div class="course-completed-message">
                                ✦ You reached the last lesson!
                            </div>

                        <?php endif; ?>


                    </div>


                <?php else: ?>


                    <div class="no-lessons">

                        <div class="no-lessons-icon">
                            ▤
                        </div>

                        <h2>No lessons available yet</h2>

                        <p>
                            The instructor has not added lessons to this course.
                            Please check again later.
                        </p>

                        <a href="<?= e(BASE_URL) ?>/student/dashboard.php">
                            Back to Dashboard
                        </a>

                    </div>


                <?php endif; ?>


            </section>

        </main>



        <!-- LESSON SIDEBAR -->

        <aside class="learning-sidebar">


            <div class="sidebar-heading">

                <div>

                    <span class="sidebar-label">
                        YOUR CONTENT
                    </span>

                    <h3>Course Lessons</h3>

                </div>

                <span class="lessons-total">
                    <?= $totalLessons ?>
                </span>

            </div>



            <div class="lesson-list">


                <?php if (empty($lessons)): ?>

                    <div class="sidebar-empty">
                        No lessons added yet.
                    </div>


                <?php else: ?>


                    <?php foreach ($lessons as $index => $lesson): ?>


                        <?php

                        $isActive = $current &&
                            (int)$current['id'] === (int)$lesson['id'];

                        $checkCompleted = $pdo->prepare("
                            SELECT id
                            FROM lesson_progress
                            WHERE user_id = ?
                              AND lesson_id = ?
                            LIMIT 1
                        ");

                        $checkCompleted->execute([
                            $user['id'],
                            $lesson['id']
                        ]);

                        $isCompleted = (bool)$checkCompleted->fetch();

                        ?>


                        <a
                            href="<?= e(BASE_URL) ?>/student/learn.php?course_id=<?= $id ?>&lesson_id=<?= (int)$lesson['id'] ?>"
                            class="lesson-item <?= $isActive ? 'active' : '' ?>"
                        >

                            <span class="lesson-index">

                                <?= str_pad(
                                    (string)($index + 1),
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>

                            </span>


                            <div class="lesson-item-info">

                                <strong>
                                    <?= e($lesson['title']) ?>
                                </strong>

                                <small>

                                    <?php if ($isCompleted): ?>

                                        Completed

                                    <?php elseif ($isActive): ?>

                                        Currently watching

                                    <?php else: ?>

                                        Watch lesson

                                    <?php endif; ?>

                                </small>

                            </div>


                            <span class="lesson-play-icon">

                                <?= $isCompleted ? '✓' : ($isActive ? '▶' : '›') ?>

                            </span>

                        </a>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>



            <!-- SIDEBAR FOOTER -->

            <div class="sidebar-footer">

                <span class="sidebar-footer-icon">
                    ✦
                </span>

                <div>

                    <strong>Keep going!</strong>

                    <p>
                        Every lesson brings you closer to your goals.
                    </p>

                </div>

            </div>


        </aside>


    </div>



    <!-- FOOTER -->

    <footer class="learning-footer">

        <span>
            © <?= date('Y') ?> LearnHub
        </span>

        <span>
            Learn something new, every day. ✦
        </span>

    </footer>


</div>


<?php page_footer(); ?>