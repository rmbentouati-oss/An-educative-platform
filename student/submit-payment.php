<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if (is_admin()) {
    redirect('admin/dashboard.php');
}

$studentId = (int) current_user()['id'];

$courseId = (int) (
    $_POST['course_id'] ??
    $_GET['course_id'] ??
    0
);

if ($courseId <= 0) {
    http_response_code(400);
    exit('Invalid course ID.');
}

// Get paid and published course
$stmt = $pdo->prepare("
    SELECT *
    FROM courses
    WHERE id = ?
      AND status = 'published'
      AND is_free = 0
    LIMIT 1
");

$stmt->execute([$courseId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    http_response_code(404);
    exit('Paid course not found.');
}

// Check existing enrollment
$stmt = $pdo->prepare("
    SELECT *
    FROM enrollments
    WHERE user_id = ?
      AND course_id = ?
    LIMIT 1
");

$stmt->execute([$studentId, $courseId]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    // Already approved
    if ($existing && $existing['status'] === 'approved') {

        flash('success', 'You are already enrolled in this course.');

        redirect('student/dashboard.php');
    }

    try {

        // Upload new receipt
        $path = upload_file(
            $_FILES['receipt'] ?? [],
            'receipt'
        );

        if ($existing) {

            // Update existing enrollment
            $stmt = $pdo->prepare("
                UPDATE enrollments
                SET
                    receipt_path = ?,
                    status = 'pending',
                    submitted_at = NOW(),
                    reviewed_at = NULL
                WHERE id = ?
                  AND user_id = ?
            ");

            $stmt->execute([
                $path,
                $existing['id'],
                $studentId
            ]);

            // Delete old receipt only after successful update
            if (!empty($existing['receipt_path'])) {

                $oldPath = dirname(__DIR__) . '/' . $existing['receipt_path'];

                if (is_file($oldPath)) {
                    unlink($oldPath);
                }
            }

        } else {

            // Create new enrollment
            $stmt = $pdo->prepare("
                INSERT INTO enrollments
                    (user_id, course_id, status, receipt_path)
                VALUES
                    (?, ?, 'pending', ?)
            ");

            $stmt->execute([
                $studentId,
                $courseId,
                $path
            ]);
        }

        flash(
            'success',
            'Receipt submitted successfully. Access will be granted after administrator approval.'
        );

        redirect('student/dashboard.php');

    } catch (Throwable $e) {

        error_log('Payment submission error: ' . $e->getMessage());

        $error = 'Unable to submit your receipt. Please check the file and try again.';
    }
}

page_header('Submit Payment Receipt');

?>

<style>

.payment-page {
    max-width: 850px;
    margin: 45px auto 65px;
    padding: 0 20px;
}

.payment-card {
    overflow: hidden;
    background: #fff;
    border: 1px solid #eeeaf6;
    border-radius: 22px;
    box-shadow: 0 15px 40px rgba(49,36,87,.06);
}

.payment-header {
    padding: 30px;
    background: linear-gradient(110deg,#6549BD,#9278E5);
    color: white;
}

.payment-header span {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
    color: #e1d8ff;
}

.payment-header h1 {
    margin: 12px 0 7px;
    color: white;
    font-size: 26px;
}

.payment-header p {
    margin: 0;
    color: #eee8ff;
    font-size: 12px;
}

.payment-content {
    padding: 30px;
}

.payment-course {
    padding: 20px;
    margin-bottom: 25px;
    border: 1px solid #eeeaf6;
    border-radius: 15px;
    background: #fbfaff;
}

.payment-course small {
    color: #9993a8;
    font-size: 10px;
}

.payment-course h3 {
    margin: 8px 0;
    color: #302a45;
    font-size: 17px;
}

.payment-price {
    color: #7657d5;
    font-size: 20px;
    font-weight: 800;
}

.payment-info {
    padding: 15px;
    margin-bottom: 22px;
    border-radius: 12px;
    background: #f5f1ff;
    color: #7056b5;
    font-size: 12px;
    line-height: 1.8;
}

.payment-form label {
    display: block;
    margin-bottom: 10px;
    color: #302a45;
    font-size: 12px;
    font-weight: 700;
}

.payment-form input[type="file"] {
    width: 100%;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px dashed #c9b9f2;
    border-radius: 12px;
    background: #fbfaff;
    font-size: 12px;
}

.payment-form button {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 11px;
    background: #7657d5;
    color: white;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    transition: .2s;
}

.payment-form button:hover {
    background: #5b3db8;
}

.payment-error {
    padding: 13px;
    margin-bottom: 18px;
    border-radius: 10px;
    background: #fff0f0;
    color: #bc5555;
    font-size: 12px;
}

.payment-back {
    display: inline-block;
    margin-top: 20px;
    color: #7657d5;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}

@media(max-width:550px) {
    .payment-content {
        padding: 20px;
    }

    .payment-header {
        padding: 25px 20px;
    }

    .payment-header h1 {
        font-size: 22px;
    }
}

</style>


<div class="payment-page">

    <div class="payment-card">

        <div class="payment-header">

            <span>LEARNHUB / ENROLLMENT</span>

            <h1>Submit Payment Receipt</h1>

            <p>
                Complete your enrollment by uploading your payment proof.
            </p>

        </div>


        <div class="payment-content">

            <div class="payment-course">

                <small>SELECTED COURSE</small>

                <h3><?= e($course['title']) ?></h3>

                <div class="payment-price">
                    <?= number_format((float)$course['price'], 2) ?> DZD
                </div>

            </div>


            <div class="payment-info">
                Your receipt will be reviewed by the administrator.
                Once your payment is approved, you will get access to the course.
                <br>
                Accepted formats: JPG, PNG, WEBP and PDF. Maximum size: 8 MB.
            </div>


            <?php if ($error): ?>

                <div class="payment-error">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>


            <form
                class="payment-form"
                method="POST"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <input
                    type="hidden"
                    name="course_id"
                    value="<?= $courseId ?>"
                >

                <label for="receipt">
                    Upload Payment Receipt
                </label>

                <input
                    type="file"
                    id="receipt"
                    name="receipt"
                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                    required
                >

                <button type="submit">
                    Submit Receipt →
                </button>

            </form>


            <a
                href="<?= e(BASE_URL) ?>/student/dashboard.php"
                class="payment-back"
            >
                ← Back to Dashboard
            </a>

        </div>

    </div>

</div>


<?php page_footer(); ?>