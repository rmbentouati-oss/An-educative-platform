<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$admin = current_user();

$stats = [
    'students' => 0,
    'courses' => 0,
    'published' => 0,
    'lessons' => 0,
    'pending' => 0,
    'enrollments' => 0
];

$recentCourses = [];
$recentEnrollments = [];
$dbError = false;

try {

    // Students
    $stats['students'] = (int)$pdo->query("
        SELECT COUNT(*) FROM users WHERE role = 'student'
    ")->fetchColumn();

    // Courses
    $stats['courses'] = (int)$pdo->query("
        SELECT COUNT(*) FROM courses
    ")->fetchColumn();

    // Published courses
    $stats['published'] = (int)$pdo->query("
        SELECT COUNT(*) FROM courses WHERE status = 'published'
    ")->fetchColumn();

    // Lessons
    $stats['lessons'] = (int)$pdo->query("
        SELECT COUNT(*) FROM lessons
    ")->fetchColumn();

    // Pending enrollments
    $stats['pending'] = (int)$pdo->query("
        SELECT COUNT(*) FROM enrollments WHERE status = 'pending'
    ")->fetchColumn();

    // Total enrollments
    $stats['enrollments'] = (int)$pdo->query("
        SELECT COUNT(*) FROM enrollments
    ")->fetchColumn();

    // Recent courses
    $stmt = $pdo->query("
        SELECT
            c.id,
            c.title,
            c.status,
            c.price,
            c.is_free,
            (
                SELECT COUNT(*)
                FROM lessons l
                WHERE l.course_id = c.id
            ) AS lesson_count
        FROM courses c
        ORDER BY c.id DESC
        LIMIT 5
    ");

    $recentCourses = $stmt->fetchAll();

    // Recent enrollments
    $stmt = $pdo->query("
        SELECT
            e.id,
            e.status,
            e.submitted_at,
            e.receipt_path,
            u.full_name,
            c.title AS course_title
        FROM enrollments e
        LEFT JOIN users u ON u.id = e.user_id
        LEFT JOIN courses c ON c.id = e.course_id
        ORDER BY e.id DESC
        LIMIT 5
    ");

    $recentEnrollments = $stmt->fetchAll();

} catch (PDOException $e) {

    error_log('Admin Dashboard: ' . $e->getMessage());
    $dbError = true;
}

page_header('Admin Dashboard');
?>

<style>

.admin-dashboard {
    max-width: 1400px;
    margin: 35px auto 60px;
    padding: 0 25px;
    color: #29243E;
    font-family: 'Manrope', sans-serif;
}

.admin-dashboard * {
    box-sizing: border-box;
}

/* HEADER */

.dash-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.dash-eyebrow {
    color: #8064D7;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
}

.dash-header h1 {
    margin: 9px 0;
    font-size: 29px;
    font-weight: 800;
}

.dash-header p {
    margin: 0;
    color: #9290A0;
    font-size: 13px;
}

.dash-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 17px;
    background: white;
    border: 1px solid #EEEAF6;
    border-radius: 15px;
}

.dash-avatar {
    width: 43px;
    height: 43px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: linear-gradient(135deg,#7657D5,#B49BEF);
    color: white;
    font-weight: 800;
    font-size: 17px;
}

.dash-profile strong,
.dash-profile small {
    display: block;
}

.dash-profile strong {
    font-size: 12px;
}

.dash-profile small {
    color: #9993A8;
    font-size: 10px;
    margin-top: 4px;
}

/* HERO */

.dash-hero {
    position: relative;
    overflow: hidden;
    padding: 38px 42px;
    margin-bottom: 32px;
    border-radius: 23px;
    color: white;
    background: linear-gradient(110deg,#6246B8,#987DE9);
    box-shadow: 0 15px 35px rgba(104,75,190,.15);
}

.dash-hero-content {
    position: relative;
    z-index: 2;
    max-width: 580px;
}

.dash-hero small {
    color: #E1D8FF;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
}

.dash-hero h2 {
    margin: 14px 0 9px;
    font-size: 29px;
    font-weight: 800;
}

.dash-hero p {
    margin: 0;
    color: #E9E2FF;
    font-size: 13px;
    line-height: 1.9;
}

.dash-hero-decoration {
    position: absolute;
    right: 80px;
    top: 50%;
    transform: translateY(-50%) rotate(10deg);
    width: 120px;
    height: 120px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(255,255,255,.3);
    border-radius: 30px;
    background: rgba(255,255,255,.13);
    font-size: 55px;
}

/* SECTION */

.dash-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin: 35px 0 18px;
}

.dash-section h2 {
    margin: 0;
    font-size: 19px;
    font-weight: 800;
}

.dash-section span {
    color: #9993A8;
    font-size: 11px;
}

/* STATS */

.dash-stats {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 17px;
}

.dash-stat {
    padding: 23px;
    background: white;
    border: 1px solid #EEEAF6;
    border-radius: 18px;
    transition: .25s;
}

.dash-stat:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(49,36,87,.07);
}

.dash-stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dash-icon {
    width: 45px;
    height: 45px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    font-size: 20px;
}

.purple .dash-icon {
    background: #F0EBFF;
    color: #7657D5;
}

.blue .dash-icon {
    background: #EAF1FF;
    color: #6487D5;
}

.pink .dash-icon {
    background: #FCEDEE;
    color: #D77E94;
}

.orange .dash-icon {
    background: #FFF3E5;
    color: #D49A50;
}

.green .dash-icon {
    background: #E5F5EC;
    color: #3B9664;
}

.dash-number {
    margin: 18px 0 5px;
    font-size: 30px;
    font-weight: 800;
}

.dash-label {
    color: #898499;
    font-size: 12px;
    font-weight: 600;
}

.dash-stat a {
    display: inline-block;
    margin-top: 14px;
    color: #7657D5;
    font-size: 10px;
    font-weight: 700;
    text-decoration: none;
}

/* QUICK ACTIONS */

.dash-actions {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 15px;
}

.dash-action {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 19px;
    background: white;
    border: 1px solid #EEEAF6;
    border-radius: 16px;
    color: #29243E;
    text-decoration: none;
    transition: .25s;
}

.dash-action:hover {
    transform: translateY(-4px);
    border-color: #CFC0FF;
    box-shadow: 0 10px 25px rgba(49,36,87,.06);
}

.action-icon {
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: #F0EBFF;
    color: #7657D5;
    font-size: 20px;
}

.dash-action strong,
.dash-action small {
    display: block;
}

.dash-action strong {
    font-size: 12px;
}

.dash-action small {
    margin-top: 5px;
    color: #9993A8;
    font-size: 10px;
}

/* CONTENT */

.dash-content {
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    gap: 20px;
    margin-top: 28px;
}

.dash-box {
    min-width: 0;
    padding: 23px;
    background: white;
    border: 1px solid #EEEAF6;
    border-radius: 18px;
}

.dash-box-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.dash-box-header h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
}

.dash-box-header a {
    color: #7657D5;
    font-size: 10px;
    font-weight: 700;
    text-decoration: none;
}

/* TABLE */

.dash-table-wrap {
    overflow-x: auto;
}

.dash-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 280px;
}

.dash-table th {
    padding: 12px 10px;
    background: #F8F6FC;
    color: #9993A8;
    font-size: 9px;
    text-align: left;
}

.dash-table td {
    padding: 15px 10px;
    color: #5F5A70;
    font-size: 11px;
    border-bottom: 1px solid #F1EEF6;
}

.dash-table tr:last-child td {
    border-bottom: none;
}

.dash-table td strong {
    color: #302A45;
}

.dash-table td a {
    color: #7657D5;
    text-decoration: none;
    font-weight: 700;
}

/* BADGES */

.dash-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    background: #F0EBFF;
    color: #7657D5;
    font-size: 9px;
    font-weight: 800;
    text-transform: capitalize;
}

.dash-badge.pending,
.dash-badge.draft {
    background: #FFF3DD;
    color: #B47B28;
}

.dash-badge.approved,
.dash-badge.published {
    background: #E5F5EC;
    color: #3B9664;
}

.dash-badge.rejected {
    background: #FCE9E9;
    color: #C35D65;
}

/* ENROLLMENTS */

.dash-enrollment {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid #F1EEF6;
}

.dash-enrollment:last-child {
    border-bottom: none;
}

.enrollment-avatar {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    background: #F0EBFF;
    color: #7657D5;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 800;
}

.enrollment-info {
    flex: 1;
    min-width: 0;
}

.enrollment-info strong,
.enrollment-info small {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.enrollment-info strong {
    color: #302A45;
    font-size: 11px;
}

.enrollment-info small {
    margin-top: 4px;
    color: #9993A8;
    font-size: 10px;
}

.enrollment-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 7px;
    flex-shrink: 0;
}

.receipt-link {
    color: #7657D5;
    font-size: 10px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
}

.receipt-link:hover {
    text-decoration: underline;
}

/* APPROVE / REJECT BUTTONS */

.enrollment-review {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 6px;
    margin-top: 4px;
}

.enrollment-review form {
    margin: 0;
}

.review-btn {
    border: none;
    border-radius: 7px;
    padding: 7px 10px;
    font-size: 9px;
    font-weight: 800;
    cursor: pointer;
    transition: .2s;
    white-space: nowrap;
}

.approve-btn {
    background: #E5F5EC;
    color: #329260;
}

.reject-btn {
    background: #FCE9E9;
    color: #C35D65;
}

.approve-btn:hover {
    background: #CDEEDB;
}

.reject-btn:hover {
    background: #F7D4D4;
}

.dash-empty {
    padding: 30px 10px;
    text-align: center;
    color: #9993A8;
    font-size: 12px;
}

.dash-error {
    padding: 14px;
    margin-bottom: 20px;
    border-radius: 12px;
    background: #FFF0F0;
    color: #BC5555;
    font-size: 12px;
}

/* FOOTER */

.dash-footer {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 35px;
    padding-top: 20px;
    border-top: 1px solid #EEEAF6;
    color: #A29DAF;
    font-size: 10px;
}

/* RESPONSIVE */

@media(max-width:950px) {
    .dash-content {
        grid-template-columns: 1fr;
    }
}

@media(max-width:750px) {
    .admin-dashboard {
        padding: 0 17px;
    }

    .dash-stats {
        grid-template-columns: repeat(2,1fr);
    }

    .dash-actions {
        grid-template-columns: repeat(2,1fr);
    }

    .dash-hero-decoration {
        right: 25px;
        opacity: .4;
    }
}

@media(max-width:480px) {
    .dash-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .dash-header h1 {
        font-size: 23px;
    }

    .dash-stats,
    .dash-actions {
        grid-template-columns: 1fr;
    }

    .dash-hero {
        padding: 27px;
    }

    .dash-hero h2 {
        font-size: 22px;
    }

    .dash-hero-decoration {
        display: none;
    }

    .dash-footer {
        flex-direction: column;
    }

    .dash-enrollment {
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .enrollment-actions {
        width: 100%;
        align-items: flex-start;
        padding-left: 52px;
    }

    .enrollment-review {
        justify-content: flex-start;
    }
}

</style>


<div class="admin-dashboard">

    <!-- HEADER -->

    <header class="dash-header">

        <div>
            <span class="dash-eyebrow">
                LEARNHUB / ADMINISTRATION
            </span>

            <h1>
                Welcome back,
                <?= e($admin['full_name'] ?? 'Administrator') ?>!
            </h1>

            <p>
                Manage your courses, students and learning activities.
            </p>
        </div>

        <div class="dash-profile">

            <div class="dash-avatar">
                <?= e(strtoupper(substr($admin['full_name'] ?? 'A', 0, 1))) ?>
            </div>

            <div>
                <strong>
                    <?= e($admin['full_name'] ?? 'Administrator') ?>
                </strong>

                <small>Platform Administrator</small>
            </div>

        </div>

    </header>


    <?php if ($dbError): ?>

        <div class="dash-error">
            Some dashboard information could not be loaded.
            Please check your database structure.
        </div>

    <?php endif; ?>


    <!-- HERO -->

    <section class="dash-hero">

        <div class="dash-hero-content">

            <small>YOUR LEARNING PLATFORM</small>

            <h2>Manage. Monitor. Inspire.</h2>

            <p>
                Everything you need to manage LearnHub in one place.
                Keep your courses organized and your students moving forward.
            </p>

        </div>

        <div class="dash-hero-decoration">✦</div>

    </section>


    <!-- STATISTICS -->

    <div class="dash-section">

        <h2>Platform Overview</h2>

        <span>Live database statistics</span>

    </div>


    <div class="dash-stats">

        <div class="dash-stat purple">

            <div class="dash-stat-top">
                <div class="dash-icon">♙</div>
            </div>

            <div class="dash-number">
                <?= number_format($stats['students']) ?>
            </div>

            <div class="dash-label">Total Students</div>

        </div>


        <div class="dash-stat blue">

            <div class="dash-stat-top">
                <div class="dash-icon">▤</div>
            </div>

            <div class="dash-number">
                <?= number_format($stats['courses']) ?>
            </div>

            <div class="dash-label">Total Courses</div>

        </div>


        <div class="dash-stat pink">

            <div class="dash-stat-top">
                <div class="dash-icon">▣</div>
            </div>

            <div class="dash-number">
                <?= number_format($stats['lessons']) ?>
            </div>

            <div class="dash-label">Total Lessons</div>

        </div>


        <div class="dash-stat green">

            <div class="dash-stat-top">
                <div class="dash-icon">✓</div>
            </div>

            <div class="dash-number">
                <?= number_format($stats['published']) ?>
            </div>

            <div class="dash-label">Published Courses</div>

        </div>


        <div class="dash-stat orange">

            <div class="dash-stat-top">
                <div class="dash-icon">◷</div>
            </div>

            <div class="dash-number">
                <?= number_format($stats['pending']) ?>
            </div>

            <div class="dash-label">Pending Requests</div>

            <a href="#recent-enrollments">
                Review payment requests →
            </a>

        </div>


        <div class="dash-stat purple">

            <div class="dash-stat-top">
                <div class="dash-icon">◎</div>
            </div>

            <div class="dash-number">
                <?= number_format($stats['enrollments']) ?>
            </div>

            <div class="dash-label">Total Enrollments</div>

        </div>

    </div>


    <!-- QUICK ACTIONS -->

    <div class="dash-section">

        <h2>Quick Actions</h2>

        <span>Available platform pages</span>

    </div>


    <div class="dash-actions">

        <a
            href="<?= e(BASE_URL) ?>/admin/add-course.php"
            class="dash-action"
        >

            <div class="action-icon">＋</div>

            <div>
                <strong>Add a Course</strong>
                <small>Create a new learning course</small>
            </div>

        </a>


        <a href="#recent-enrollments" class="dash-action">

            <div class="action-icon">▤</div>

            <div>
                <strong>Payment Receipts</strong>
                <small>Review student payment requests</small>
            </div>

        </a>


        <a
            href="<?= e(BASE_URL) ?>/index.php"
            class="dash-action"
        >

            <div class="action-icon">⌂</div>

            <div>
                <strong>View Website</strong>
                <small>Visit the public LearnHub page</small>
            </div>

        </a>

    </div>


    <!-- RECENT ACTIVITY -->

    <div class="dash-content">


        <!-- RECENT COURSES -->

        <section class="dash-box" id="recent-courses">

            <div class="dash-box-header">

                <h3>Recently Added Courses</h3>

                <a href="<?= e(BASE_URL) ?>/admin/add-course.php">
                    Add course +
                </a>

            </div>


            <?php if (empty($recentCourses)): ?>

                <div class="dash-empty">
                    No courses have been added yet.
                </div>

            <?php else: ?>

                <div class="dash-table-wrap">

                    <table class="dash-table">

                        <thead>
                            <tr>
                                <th>COURSE</th>
                                <th>LESSONS</th>
                                <th>STATUS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($recentCourses as $course): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?= e($course['title']) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= (int)$course['lesson_count'] ?>
                                    </td>

                                    <td>
                                        <span class="dash-badge <?= e($course['status']) ?>">
                                            <?= e(ucfirst($course['status'])) ?>
                                        </span>
                                    </td>

                                    <td>
    <div class="course-actions">

        <a href="<?= e(BASE_URL) ?>/admin/course.php?id=<?= (int)$course['id'] ?>">
            Manage →
        </a>

        <form
            method="POST"
            action="<?= e(BASE_URL) ?>/admin/delete-course.php"
            class="delete-course-form"
            onsubmit="return confirm('Are you sure you want to delete this course? All its lessons and enrollments will also be deleted.');"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <input
                type="hidden"
                name="course_id"
                value="<?= (int)$course['id'] ?>"
            >

            <button type="submit" class="delete-course-btn">
                Delete
            </button>

        </form>

    </div>
</td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>


        <!-- RECENT ENROLLMENTS -->

        <section class="dash-box" id="recent-enrollments">

            <div class="dash-box-header">

                <h3>Recent Enrollments</h3>

                <a href="#recent-enrollments">
                    View recent requests →
                </a>

            </div>


            <?php if (empty($recentEnrollments)): ?>

                <div class="dash-empty">
                    No enrollment requests yet.
                </div>

            <?php else: ?>

                <?php foreach ($recentEnrollments as $enrollment): ?>

                    <div class="dash-enrollment">

                        <div class="enrollment-avatar">
                            <?= e(strtoupper(substr($enrollment['full_name'] ?? 'S', 0, 1))) ?>
                        </div>


                        <div class="enrollment-info">

                            <strong>
                                <?= e($enrollment['full_name'] ?? 'Unknown student') ?>
                            </strong>

                            <small>
                                <?= e($enrollment['course_title'] ?? 'Unknown course') ?>
                            </small>

                        </div>


                        <div class="enrollment-actions">

                            <span class="dash-badge <?= e($enrollment['status']) ?>">
                                <?= e(ucfirst($enrollment['status'])) ?>
                            </span>


                            <?php if (!empty($enrollment['receipt_path'])): ?>

                                <a
                                    class="receipt-link"
                                    href="<?= e(BASE_URL) ?>/admin/receipt.php?id=<?= (int)$enrollment['id'] ?>"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    View receipt →
                                </a>

                            <?php else: ?>

                                <small style="color:#9993A8; font-size:10px;">
                                    No receipt
                                </small>

                            <?php endif; ?>


                            <?php if (
                                $enrollment['status'] === 'pending'
                                && !empty($enrollment['receipt_path'])
                            ): ?>

                                <div class="enrollment-review">

                                    <form
                                        method="POST"
                                        action="<?= e(BASE_URL) ?>/admin/update-enrollment.php"
                                        onsubmit="return confirm('Are you sure you want to approve this enrollment?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrf_token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="enrollment_id"
                                            value="<?= (int)$enrollment['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="status"
                                            value="approved"
                                        >

                                        <button type="submit" class="review-btn approve-btn">
                                            ✓ Approve
                                        </button>

                                    </form>


                                    <form
                                        method="POST"
                                        action="<?= e(BASE_URL) ?>/admin/update-enrollment.php"
                                        onsubmit="return confirm('Are you sure you want to reject this enrollment?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrf_token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="enrollment_id"
                                            value="<?= (int)$enrollment['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="status"
                                            value="rejected"
                                        >

                                        <button type="submit" class="review-btn reject-btn">
                                            ✕ Reject
                                        </button>

                                    </form>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </div>


    <!-- FOOTER -->

    <footer class="dash-footer">

        <span>
            © <?= date('Y') ?> LearnHub — Administration Panel
        </span>

        <span>
            Made for better learning experiences ✦
        </span>

    </footer>

</div>


<?php page_footer(); ?>
