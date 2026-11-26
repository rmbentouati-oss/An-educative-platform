<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$error = '';

$title = '';
$description = '';
$level = 'beginner';
$isFree = 1;
$priceInput = '0';
$status = 'draft';
$durationHours = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $level = $_POST['level'] ?? 'beginner';

    $isFree = isset($_POST['is_free']) ? 1 : 0;
    $priceInput = $_POST['price'] ?? '0';

    $status = $_POST['status'] ?? 'draft';
    $durationHours = trim($_POST['duration_hours'] ?? '');

    $allowedLevels = ['beginner', 'intermediate', 'advanced'];
    $allowedStatuses = ['draft', 'published'];

    if (
        $title === '' ||
        mb_strlen($title) > 200 ||
        $description === ''
    ) {

        $error = 'Please enter a valid title and description.';

    } elseif (
        !is_numeric($durationHours) ||
        (float)$durationHours <= 0
    ) {

        $error = 'Please enter a valid course duration.';

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

        $uploadedImagePath = null;

        try {

            $price = $isFree ? 0 : (float)$priceInput;
            $duration = (float)$durationHours;

            $slug = make_slug($title, $pdo);

            // Upload course image (optional)
            if (
                isset($_FILES['course_image']) &&
                $_FILES['course_image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                $file = $_FILES['course_image'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Image upload failed.');
                }

                if (
                    $file['size'] <= 0 ||
                    $file['size'] > 5 * 1024 * 1024
                ) {
                    throw new RuntimeException(
                        'Image must be smaller than 5 MB.'
                    );
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);

                $allowedImages = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                if (
                    !isset($allowedImages[$mime]) ||
                    !getimagesize($file['tmp_name'])
                ) {
                    throw new RuntimeException(
                        'Only JPG, PNG, and WEBP images are allowed.'
                    );
                }

                $folder = UPLOAD_ROOT . '/courses';

                if (!is_dir($folder)) {

                    if (
                        !mkdir($folder, 0755, true) &&
                        !is_dir($folder)
                    ) {
                        throw new RuntimeException(
                            'Could not create course image folder.'
                        );
                    }
                }

                $filename = bin2hex(random_bytes(18))
                    . '.' . $allowedImages[$mime];

                if (
                    !move_uploaded_file(
                        $file['tmp_name'],
                        $folder . '/' . $filename
                    )
                ) {
                    throw new RuntimeException(
                        'Could not save course image.'
                    );
                }

                $uploadedImagePath = 'uploads/courses/' . $filename;
            }

            // Insert course
            $stmt = $pdo->prepare("
                INSERT INTO courses (
                    title,
                    slug,
                    description,
                    level,
                    price,
                    is_free,
                    status,
                    image,
                    duration_hours
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $title,
                $slug,
                $description,
                $level,
                $price,
                $isFree,
                $status,
                $uploadedImagePath,
                $duration
            ]);

            flash('success', 'Course created successfully.');

            redirect('admin/dashboard.php');

        } catch (Throwable $e) {

            // Delete uploaded image if course creation failed
            if ($uploadedImagePath !== null) {

                $fullPath = dirname(__DIR__) . '/' . $uploadedImagePath;

                if (is_file($fullPath)) {
                    unlink($fullPath);
                }
            }

            error_log('Add Course Error: ' . $e->getMessage());

            $error = $e instanceof PDOException
                ? 'Database error. Please check the course information and try again.'
                : $e->getMessage();
        }
    }
}

page_header('Add a Course');

?>

<style>

.add-course-container {
    max-width: 850px;
    margin: 35px auto;
    padding: 0 20px 40px;
}

.add-course-heading {
    margin-bottom: 25px;
}

.add-course-heading h1 {
    font-size: 28px;
    color: #29233d;
    margin-bottom: 8px;
}

.add-course-heading p {
    color: #898398;
    font-size: 14px;
}

.add-course-card {
    background: #fff;
    border: 1px solid #eeeaf5;
    border-radius: 18px;
    padding: 30px;
    box-shadow: 0 8px 30px rgba(40, 25, 80, 0.05);
}

.add-course-card h2 {
    font-size: 19px;
    color: #302747;
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 22px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #514967;
    margin-bottom: 9px;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 13px 15px;
    border: 1px solid #e7e2ef;
    border-radius: 10px;
    background: #fcfbfe;
    color: #393149;
    font-size: 14px;
    outline: none;
    transition: 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #8b70dc;
    box-shadow: 0 0 0 3px rgba(139, 112, 220, 0.1);
}

.form-group textarea {
    min-height: 140px;
    resize: vertical;
}

.form-group small {
    display: block;
    color: #898398;
    font-size: 12px;
    margin-top: 8px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.free-option {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 10px 0 20px;
    color: #514967;
    font-size: 14px;
    font-weight: 600;
}

.free-option input {
    width: 17px;
    height: 17px;
    accent-color: #8063d5;
}

.form-error {
    background: #fff0f0;
    border: 1px solid #ffd5d5;
    color: #c03939;
    padding: 13px 16px;
    border-radius: 10px;
    margin-bottom: 22px;
    font-size: 13px;
    overflow-wrap: anywhere;
}

.image-upload-box {
    border: 2px dashed #ded5f2;
    background: #fcfaff;
    border-radius: 12px;
    padding: 22px;
    text-align: center;
}

.image-upload-box input {
    background: white;
}

.image-upload-box small {
    display: block;
    color: #898398;
    margin-top: 10px;
    font-size: 12px;
}

.form-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.btn-create {
    border: none;
    background: #8063d5;
    color: white;
    padding: 13px 25px;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    font-size: 13px;
    transition: 0.2s;
}

.btn-create:hover {
    background: #6d50c0;
    transform: translateY(-2px);
}

.btn-cancel {
    background: #f3f0f8;
    color: #665b7b;
    padding: 13px 22px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
}

@media (max-width: 600px) {

    .add-course-card {
        padding: 20px;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}

</style>


<div class="add-course-container">

    <div class="add-course-heading">

        <h1>Add a New Course</h1>

        <p>Create a new learning experience for your students.</p>

    </div>


    <div class="add-course-card">

        <h2>Course Information</h2>


        <?php if ($error !== ''): ?>

            <div class="form-error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <form method="POST" enctype="multipart/form-data">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >


            <!-- Course Title -->
            <div class="form-group">

                <label for="title">Course Title *</label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="200"
                    placeholder="Enter course title"
                    value="<?= e($title) ?>"
                    required
                >

            </div>


            <!-- Course Description -->
            <div class="form-group">

                <label for="description">Course Description *</label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="Describe what students will learn..."
                    required
                ><?= e($description) ?></textarea>

            </div>


            <!-- Course Duration -->
            <div class="form-group">

                <label for="duration_hours">
                    Course Duration (Hours) *
                </label>

                <input
                    type="number"
                    id="duration_hours"
                    name="duration_hours"
                    min="0.5"
                    step="0.5"
                    placeholder="Example: 6"
                    value="<?= e($durationHours) ?>"
                    required
                >

                <small>
                    Enter the estimated total learning time in hours.
                </small>

            </div>


            <!-- Course Image -->
            <div class="form-group">

                <label for="course_image">Course Image</label>

                <div class="image-upload-box">

                    <input
                        type="file"
                        id="course_image"
                        name="course_image"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >

                    <small>
                        Choose a JPG, PNG, or WEBP image.
                        Maximum size: 5 MB.
                    </small>

                </div>

            </div>


            <!-- Level and Status -->
            <div class="form-row">

                <div class="form-group">

                    <label for="level">Course Level</label>

                    <select id="level" name="level">

                        <option
                            value="beginner"
                            <?= $level === 'beginner' ? 'selected' : '' ?>
                        >
                            Beginner
                        </option>

                        <option
                            value="intermediate"
                            <?= $level === 'intermediate' ? 'selected' : '' ?>
                        >
                            Intermediate
                        </option>

                        <option
                            value="advanced"
                            <?= $level === 'advanced' ? 'selected' : '' ?>
                        >
                            Advanced
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="status">Publication Status</label>

                    <select id="status" name="status">

                        <option
                            value="draft"
                            <?= $status === 'draft' ? 'selected' : '' ?>
                        >
                            Draft
                        </option>

                        <option
                            value="published"
                            <?= $status === 'published' ? 'selected' : '' ?>
                        >
                            Published
                        </option>

                    </select>

                </div>

            </div>


            <!-- Free Course -->
            <label class="free-option">

                <input
                    type="checkbox"
                    name="is_free"
                    id="is_free"
                    value="1"
                    <?= $isFree ? 'checked' : '' ?>
                >

                This is a free course

            </label>


            <!-- Price -->
            <div class="form-group">

                <label for="price">Course Price (DZD)</label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    min="0"
                    step="0.01"
                    value="<?= e($priceInput) ?>"
                    <?= $isFree ? 'disabled' : '' ?>
                >

            </div>


            <!-- Actions -->
            <div class="form-actions">

                <button type="submit" class="btn-create">
                    Create Course →
                </button>

                <a
                    href="<?= e(BASE_URL) ?>/admin/dashboard.php"
                    class="btn-cancel"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>


<script>

const freeCheckbox = document.getElementById('is_free');
const priceInput = document.getElementById('price');

freeCheckbox.addEventListener('change', function () {

    priceInput.disabled = this.checked;

    if (this.checked) {

        priceInput.value = '0';

    } else {

        priceInput.value = '';

    }

});

</script>


<?php page_footer(); ?>