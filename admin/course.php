<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(404);
    exit('Invalid course ID.');
}

// Get course
$st = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
$st->execute([$id]);
$course = $st->fetch();

if (!$course) {
    http_response_code(404);
    exit('Course not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $action = $_POST['action'] ?? '';

    try {

        // ADD LESSON
        if ($action === 'add_lesson') {

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $order = (int)($_POST['sort_order'] ?? 0);

            if ($title === '') {
                throw new RuntimeException('Lesson title is required.');
            }

            if (empty($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Please select a valid video file.');
            }

            // Upload video
            $path = upload_file($_FILES['video'], 'video');

            try {
                $q = $pdo->prepare("
                    INSERT INTO lessons
                    (course_id, title, description, video_path, sort_order)
                    VALUES (?, ?, ?, ?, ?)
                ");

                $q->execute([
                    $id,
                    $title,
                    $description,
                    $path,
                    $order
                ]);

            } catch (Throwable $e) {

                // Remove uploaded file if database insertion fails
                $file = dirname(__DIR__) . '/' . $path;

                if (is_file($file)) {
                    unlink($file);
                }

                throw $e;
            }

            flash('success', 'Video lesson uploaded successfully.');
            redirect('admin/course.php?id=' . $id);
        }


        // UPDATE COURSE STATUS
        elseif ($action === 'update_status') {

            $status = $_POST['status'] ?? '';

            if (!in_array($status, ['draft', 'published'], true)) {
                throw new RuntimeException('Invalid course status.');
            }

            $q = $pdo->prepare(
                'UPDATE courses SET status = ? WHERE id = ?'
            );

            $q->execute([$status, $id]);

            flash('success', 'Course publication status updated.');
            redirect('admin/course.php?id=' . $id);
        }


        // DELETE LESSON
        elseif ($action === 'delete_lesson') {

            $lessonId = (int)($_POST['lesson_id'] ?? 0);

            if ($lessonId <= 0) {
                throw new RuntimeException('Invalid lesson ID.');
            }

            $q = $pdo->prepare("
                SELECT video_path
                FROM lessons
                WHERE id = ? AND course_id = ?
            ");

            $q->execute([$lessonId, $id]);
            $lesson = $q->fetch();

            if (!$lesson) {
                throw new RuntimeException('Lesson not found.');
            }

            $delete = $pdo->prepare("
                DELETE FROM lessons
                WHERE id = ? AND course_id = ?
            ");

            $delete->execute([$lessonId, $id]);

            // Delete video file
            if (!empty($lesson['video_path'])) {

                $file = dirname(__DIR__) . '/' . $lesson['video_path'];

                if (is_file($file)) {
                    unlink($file);
                }
            }

            flash('success', 'Lesson deleted successfully.');
            redirect('admin/course.php?id=' . $id);
        }

        else {
            throw new RuntimeException('Invalid action.');
        }

    } catch (Throwable $e) {

        error_log($e->getMessage());

        $error = 'An error occurred: ' . $e->getMessage();
    }
}


// Get lessons
$q = $pdo->prepare("
    SELECT *
    FROM lessons
    WHERE course_id = ?
    ORDER BY sort_order ASC, id ASC
");

$q->execute([$id]);
$lessons = $q->fetchAll();

page_header('Manage course videos');
?>


<div class="course-management">

    <p>
        <a href="<?= e(BASE_URL) ?>/admin/dashboard.php">
            ← Back to dashboard
        </a>
    </p>

    <h1><?= e($course['title']) ?></h1>

    <?php if ($error): ?>
        <div class="alert error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>


    <!-- COURSE STATUS -->
    <section class="panel">

        <h2>Course visibility</h2>

        <form method="post">

            <input type="hidden" name="csrf_token"
                   value="<?= e(csrf_token()) ?>">

            <input type="hidden" name="action"
                   value="update_status">

            <select name="status">

                <option value="draft"
                    <?= $course['status'] === 'draft' ? 'selected' : '' ?>>
                    Draft
                </option>

                <option value="published"
                    <?= $course['status'] === 'published' ? 'selected' : '' ?>>
                    Published
                </option>

            </select>

            <button type="submit">Save status</button>

        </form>

    </section>


    <!-- UPLOAD LESSON -->
    <section class="panel">

        <h2>Upload a lesson video</h2>

        <p class="muted">
            Accepted formats: MP4, WebM, OGG.
            Maximum size depends on your PHP upload configuration.
        </p>

        <form class="form" method="post" enctype="multipart/form-data">

            <input type="hidden" name="csrf_token"
                   value="<?= e(csrf_token()) ?>">

            <input type="hidden" name="action"
                   value="add_lesson">

            <label>Lesson title</label>
            <input
                type="text"
                name="title"
                maxlength="200"
                required
            >

            <label>Lesson description (optional)</label>
            <textarea name="description"></textarea>

            <label>Video file</label>
            <input
                type="file"
                name="video"
                accept=".mp4,.webm,.ogg,video/mp4,video/webm,video/ogg"
                required
            >

            <label>Lesson order</label>
            <input
                type="number"
                name="sort_order"
                value="0"
                min="0"
            >

            <button type="submit">
                Upload video lesson
            </button>

        </form>

    </section>


    <!-- LESSONS LIST -->
    <section class="panel">

        <h2>Uploaded lessons (<?= count($lessons) ?>)</h2>

        <?php foreach ($lessons as $lesson): ?>

            <div class="card">

                <h3><?= e($lesson['title']) ?></h3>

                <?php if (!empty($lesson['description'])): ?>
                    <p><?= e($lesson['description']) ?></p>
                <?php endif; ?>

                <?php if (!empty($lesson['video_path'])): ?>

                    <video
                        class="video"
                        controls
                        preload="metadata"
                        style="width:100%; max-width:750px;"
                    >
                        <source src="<?= e(BASE_URL . '/' . $lesson['video_path']) ?>">
                        Your browser does not support video playback.
                    </video>

                <?php else: ?>

                    <p class="muted">No video file available.</p>

                <?php endif; ?>


                <form
                    method="post"
                    onsubmit="return confirm('Delete this lesson and its video?')"
                >

                    <input type="hidden" name="csrf_token"
                           value="<?= e(csrf_token()) ?>">

                    <input type="hidden" name="action"
                           value="delete_lesson">

                    <input type="hidden" name="lesson_id"
                           value="<?= (int)$lesson['id'] ?>">

                    <button type="submit" class="btn danger">
                        Delete lesson
                    </button>

                </form>

            </div>

        <?php endforeach; ?>


        <?php if (empty($lessons)): ?>

            <p class="muted">
                No video lessons have been uploaded yet.
            </p>

        <?php endif; ?>

    </section>

</div>

<?php page_footer(); ?>