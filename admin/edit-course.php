```php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM courses WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$course = $stmt->fetch();

if (!$course) {
    http_response_code(404);
    exit('Course not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $level = $_POST['level'] ?? 'beginner';

    $isFree = isset($_POST['is_free']) ? 1 : 0;
    $priceInput = $_POST['price'] ?? '0';

    $status = $_POST['status'] ?? 'draft';

    $allowedLevels = ['beginner', 'intermediate', 'advanced'];
    $allowedStatuses = ['draft', 'published'];

    if ($title === '' || mb_strlen($title) > 200 || $description === '') {
        $error = 'Please enter a valid title and description.';

    } elseif (!in_array($level, $allowedLevels, true)) {
        $error = 'Please select a valid course level.';

    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = 'Please select a valid publication status.';

    } elseif (
        !$isFree &&
        (!is_numeric($priceInput) || (float)$priceInput <= 0)
    ) {
        $error = 'Please enter a price greater than zero, or select Free course.';

    } else {
        try {
            $price = $isFree ? 0 : (float)$priceInput;

            $slug = make_slug($title, $pdo, $id);

            $update = $pdo->prepare("
                UPDATE courses
                SET
                    title = ?,
                    slug = ?,
                    description = ?,
                    level = ?,
                    price = ?,
                    is_free = ?,
                    status = ?
                WHERE id = ?
            ");

            $update->execute([
                $title,
                $slug,
                $description,
                $level,
                $price,
                $isFree,
                $status,
                $id
            ]);

            flash('success', 'Course updated successfully.');
            redirect('admin/dashboard.php');

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Could not update this course. Please try again.';
        }
    }
}

page_header('Edit course');

$selectedLevel = $_POST['level'] ?? $course['level'];

$selectedFree = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? isset($_POST['is_free'])
    : (bool)$course['is_free'];

$selectedStatus = $_POST['status'] ?? $course['status'];
?>

<p>
    <a href="<?= e(BASE_URL) ?>/admin/dashboard.php">
        &larr; Back to dashboard
    </a>
</p>

<h1>Edit course</h1>

<?php if ($error): ?>
    <div class="alert error">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<section class="panel">

    <form class="form" method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <label>Course title</label>

        <input
            type="text"
            name="title"
            maxlength="200"
            required
            value="<?= e($_POST['title'] ?? $course['title']) ?>"
        >

        <label>Description</label>

        <textarea name="description" required><?= e($_POST['description'] ?? $course['description']) ?></textarea>

        <label>Level</label>

        <select name="level">

            <option value="beginner" <?= $selectedLevel === 'beginner' ? 'selected' : '' ?>>
                Beginner
            </option>

            <option value="intermediate" <?= $selectedLevel === 'intermediate' ? 'selected' : '' ?>>
                Intermediate
            </option>

            <option value="advanced" <?= $selectedLevel === 'advanced' ? 'selected' : '' ?>>
                Advanced
            </option>

        </select>

        <label>
            <input
                type="checkbox"
                name="is_free"
                id="freeCourse"
                value="1"
                <?= $selectedFree ? 'checked' : '' ?>
                style="width:auto"
            >
            Free course
        </label>

        <label>Price (DZD)</label>

        <input
            type="number"
            name="price"
            id="coursePrice"
            min="0"
            step="0.01"
            value="<?= e($_POST['price'] ?? $course['price']) ?>"
        >

        <label>Publication status</label>

        <select name="status">

            <option value="draft" <?= $selectedStatus === 'draft' ? 'selected' : '' ?>>
                Draft
            </option>

            <option value="published" <?= $selectedStatus === 'published' ? 'selected' : '' ?>>
                Published
            </option>

        </select>

        <button type="submit">
            Save changes
        </button>

        <a
            class="btn secondary"
            href="<?= e(BASE_URL) ?>/admin/dashboard.php"
        >
            Cancel
        </a>

    </form>

</section>

<script>
const freeCheckbox = document.getElementById('freeCourse');
const priceInput = document.getElementById('coursePrice');

// Keep the price field editable at all times.
freeCheckbox.addEventListener('change', function () {

    if (this.checked) {
        priceInput.value = '0';
    } else if (Number(priceInput.value) <= 0) {
        priceInput.value = '';
    }

});
</script>

<?php page_footer(); ?>
```