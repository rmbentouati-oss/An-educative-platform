<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Admin protection
if (!current_user() || !is_admin()) {
    redirect('auth/login.php');
}

$admin = current_user();

// Dashboard statistics
$stats = [
    'students' => 0,
    'courses' => 0,
    'lessons' => 0,
    'pending' => 0,
    'enrollments' => 0,
];

$recentCourses = [];
$recentEnrollments = [];
$dbError = false;

try {

    // Total students
    $stats['students'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'student'"
    )->fetchColumn();

    // Total courses
    $stats['courses'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM courses"
    )->fetchColumn();

    // Total lessons
    $stats['lessons'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM lessons"
    )->fetchColumn();

    // Pending enrollments
    $stats['pending'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM enrollments WHERE status = 'pending'"
    )->fetchColumn();

    // Total enrollments
    $stats['enrollments'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM enrollments"
    )->fetchColumn();

    // Recent courses
    $stmt = $pdo->query("
        SELECT id, title, status
        FROM courses
        ORDER BY id DESC
        LIMIT 5
    ");

    $recentCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent enrollments
    $stmt = $pdo->query("
        SELECT
            e.id,
            e.status,
            e.submitted_at,
            u.full_name,
            c.title AS course_title
        FROM enrollments e
        LEFT JOIN users u ON u.id = e.user_id
        LEFT JOIN courses c ON c.id = e.course_id
        ORDER BY e.id DESC
        LIMIT 5
    ");

    $recentEnrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log('Admin Dashboard Error: ' . $e->getMessage());
    $dbError = true;
}

page_header('Admin Dashboard');
?>

<style>

/* =====================================
   LEARNHUB ADMIN DASHBOARD
===================================== */

.admin-dashboard {
    max-width: 1450px;
    margin: 35px auto 60px;
    padding: 0 30px;
    color: #29243E;
    font-family: 'Manrope', sans-serif;
}

.admin-dashboard * {
    box-sizing: border-box;
}

/* HEADER */

.admin-welcome {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.admin-eyebrow {
    color: #8064D7;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
}

.admin-welcome h1 {
    margin: 9px 0;
    font-size: 30px;
    font-weight: 800;
    letter-spacing: -1px;
}

.admin-welcome p {
    margin: 0;
    color: #9290A0;
    font-size: 13px;
}

.admin-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    background: white;
    border: 1px solid #EEEAF6;
    border-radius: 15px;
}

.admin-avatar {
    width: 43px;
    height: 43px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: linear-gradient(135deg,#7657D5,#B49BEF);
    color: white;
    font-size: 17px;
    font-weight: 800;
}

.admin-profile-info strong,
.admin-profile-info small {
    display: block;
}

.admin-profile-info strong {
    font-size: 12px;
}

.admin-profile-info small {
    margin-top: 4px;
    color: #9993A8;
    font-size: 10px;
}

/* BANNER */

.admin-banner {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 190px;
    padding: 35px 42px;
    margin-bottom: 35px;
    border-radius: 23px;
    background: linear-gradient(110deg,#6549BD,#9278E5);
    color: white;
    box-shadow: 0 15px 35px rgba(104,75,190,.15);
}

.admin-banner-content {
    position: relative;
    z-index: 2;
    max-width: 570px;
}

.admin-banner-label {
    color: #E1D8FF;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
}

.admin-banner h2 {
    margin: 13px 0 8px;
    color: white;
    font-size: 28px;
    font-weight: 800;
}

.admin-banner p {
    margin: 0;
    color: #E9E2FF;
    font-size: 12px;
    line-height: 1.9;
}

.admin-banner-art {
    position: absolute;
    right: 75px;
    top: 50%;
    transform: translateY(-50%) rotate(8deg);
    width: 125px;
    height: 125px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 35px;
    background: rgba(255,255,255,.12);
    font-size: 60px;
}

.admin-banner::before {
    content: "";
    position: absolute;
    width: 270px;
    height: 270px;
    right: -75px;
    top: -140px;
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 50%;
}

.admin-banner::after {
    content: "";
    position: absolute;
    width: 190px;
    height: 190px;
    right: 110px;
    bottom: -135px;
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 50%;
}

/* SECTION HEADINGS */

.admin-section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 35px 0 18px;
}

.admin-section-title h2 {
    margin: 0;
    font-size: 19px;
    font-weight: 800;
}

.admin-section-title span {
    color: #9993A8;
    font-size: 11px;
}

/* STATISTICS */

.admin-stats {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 18px;
}

.admin-stat {
    padding: 23px;
    background: white;
    border: 1px solid #EEEAF6;
    border-radius: 18px;
    transition: .25s ease;
}

.admin-stat:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 28px rgba(49,36,87,.07);
    border-color: #DCCFFF;
}

.admin-stat-top {
    display: flex;
    justify-content: space-between;
}

.admin-stat-icon {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    font-size: 20px;
}

.stat-purple .admin-stat-icon {
    background: #F0EBFF;
    color: #7657D5;
}

.stat-blue .admin-stat-icon {
    background: #EAF1FF;
    color: #6487D5;
}

.stat-pink .admin-stat-icon {
    background: #FCEDEE;
    color: #D77E94;
}

.stat-orange .admin-stat-icon {
    background: #FFF3E5;
    color: #D49A50;
}

.admin-stat-number {
    margin: 19px 0 5px;
    font-size: 30px;
    font-weight: 800;
    letter-spacing: -1px;
}

.admin-stat-label {
    color: #898499;
    font-size: 12px;
    font-weight: 600;
}

.admin-stat-link {
    display: inline-block;
    margin-top: 15px;
    color: #7657D5;
    font-size: 10px;
    font-weight: 700;
    text-decoration: none;
}

/* QUICK ACTIONS */

.admin-quick-grid {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 15px;
}

.admin-quick-card {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 18px;
    background: white;
    border: 1px solid #EEEAF6;
    border-radius: 16px;
    color: #29243E;
    text-decoration: none;
    transition: .25s;
}

.admin-quick-card:hover {
    transform: translateY(-4px);
    border-color: #CFC0FF;
    box-shadow: 0 10px 25px rgba(49,36,87,.06);
}

.quick-icon {
    width: 43px;
    height: 43px;
    display: grid;
    flex-shrink: 0;
    place-items: center;
    border-radius: 12px;
    background: #F0EBFF;
    color: #7657D5;
    font-size: 20px;
}

.admin-quick-card strong,
.admin-quick-card small {
    display: block;
}

.admin-quick-card strong {
    font-size: 12px;
}

.admin-quick-card small {
    margin-top: 5px;
    color: #9993A8;
    font-size: 10px;
}

/* CONTENT GRID */

.admin-content-grid {
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    gap: 20px;
    margin-top: 25px;
}

.admin-box {
    min-width: 0;
    padding: 23px;
    background: white;
    border: 1px solid #EEEAF6;
    border-radius: 18px;
}

.admin-box-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.admin-box-heading h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
}

.admin-box-heading a {
    color: #7657D5;
    font-size: 10px;
    font-weight: 700;
    text-decoration: none;
}

/* TABLE */

.admin-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    min-width: 300px;
    border-collapse: collapse;
}

.admin-table th {
    padding: 12px 10px;
    background: #F8F6FC;
    color: #9993A8;
    font-size: 9px;
    font-weight: 800;
    text-align: left;
}

.admin-table td {
    padding: 15px 10px;
    color: #5F5A70;
    font-size: 11px;
    border-bottom: 1px solid #F1EEF6;
}

.admin-table tr:last-child td {
    border-bottom: none;
}

.admin-table td strong {
    color: #302A45;
    font-size: 11px;
}

/* STATUS */

.admin-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    background: #F0EBFF;
    color: #7657D5;
    font-size: 9px;
    font-weight: 800;
    text-transform: capitalize;
}

.admin-status.pending {
    background: #FFF3DD;
    color: #B47B28;
}

.admin-status.approved,
.admin-status.published {
    background: #E5F5EC;
    color: #3B9664;
}

.admin-status.rejected {
    background: #FCE9E9;
    color: #C35D65;
}

/* ENROLLMENTS */

.admin-enrollment {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid #F1EEF6;
}

.admin-enrollment:last-child {
    border-bottom: none;
}

.enrollment-avatar {
    width: 40px;
    height: 40px;
    display: grid;
    flex-shrink: 0;
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

/* EMPTY & ERROR */

.admin-empty {
    padding: 30px 10px;
    text-align: center;
    color: #9993A8;
    font-size: 12px;
}

.admin-error {
    margin-bottom: 20px;
    padding: 13px 16px;
    border-radius: 12px;
    background: #FFF0F0;
    color: #BC5555;
    font-size: 12px;
}

/* FOOTER */

.admin-dashboard-footer {
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

@media(max-width:1050px) {
    .admin-stats {
        grid-template-columns: repeat(2,1fr);
    }

    .admin-quick-grid {
        grid-template-columns: repeat(2,1fr);
    }
}

@media(max-width:750px) {
    .admin-dashboard {
        padding: 0 17px;
    }

    .admin-content-grid {
        grid-template-columns: 1fr;
    }

    .admin-banner {
        padding: 28px;
    }

    .admin-banner-art {
        right: 25px;
        opacity: .4;
    }
}

@media(max-width:480px) {
    .admin-welcome {
        align-items: flex-start;
        flex-direction: column;
    }

    .admin-welcome h1 {
        font-size: 24px;
    }

    .admin-stats {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .admin-stat {
        padding: 15px;
    }

    .admin-stat-number {
        font-size: 24px;
    }

    .admin-quick-grid {
        grid-template-columns: 1fr;
    }

    .admin-banner-art {
        display: none;
    }

    .admin-banner h2 {
        font-size: 22px;
    }

    .admin-dashboard-footer {
        flex-direction: column;
    }
}

</style>


<div class="admin-dashboard">

    <!-- HEADER -->

    <div class="admin-welcome">

        <div>
            <span class="admin-eyebrow">
                ADMINISTRATION / OVERVIEW
            </span>

            <h1>
                Welcome back,
                <?= e($admin['full_name'] ?? 'Administrator') ?>!
            </h1>

            <p>
                Here is what's happening with your learning platform today.
            </p>
        </div>

        <div class="admin-profile">

            <div class="admin-avatar">
                <?= e(strtoupper(substr($admin['full_name'] ?? 'A', 0, 1))) ?>
            </div>

            <div class="admin-profile-info">
                <strong>
                    <?= e($admin['full_name'] ?? 'Administrator') ?>
                </strong>

                <small>Platform Administrator</small>
            </div>

        </div>

    </div>


    <?php if ($dbError): ?>
        <div class="admin-error">
            Some dashboard information could not be loaded.
            Please check the database structure.
        </div>
    <?php endif; ?>


    <!-- HERO BANNER -->

    <section class="admin-banner">

        <div class="admin-banner-content">

            <span class="admin-banner-label">
                LEARNHUB CONTROL CENTER
            </span>

            <h2>Manage. Monitor. Inspire.</h2>

            <p>
                Your platform is growing. Manage courses, monitor students,
                and keep everything running smoothly from one place.
            </p>

        </div>

        <div class="admin-banner-art">✦</div>

    </section>


    <!-- STATISTICS -->

    <div class="admin-section-title">
        <h2>Platform Overview</h2>
        <span>Live database statistics</span>
    </div>


    <div class="admin-stats">

        <!-- STUDENTS -->

        <div class="admin-stat stat-purple">

            <div class="admin-stat-top">
                <div class="admin-stat-icon">♙</div>
            </div>

            <div class="admin-stat-number">
                <?= number_format($stats['students']) ?>
            </div>

            <div class="admin-stat-label">
                Total Students
            </div>

            <a href="students.php" class="admin-stat-link">
                Manage students →
            </a>

        </div>


        <!-- COURSES -->

        <div class="admin-stat stat-blue">

            <div class="admin-stat-top">
                <div class="admin-stat-icon">▤</div>
            </div>

            <div class="admin-stat-number">
                <?= number_format($stats['courses']) ?>
            </div>

            <div class="admin-stat-label">
                Total Courses
            </div>

            <a href="courses.php" class="admin-stat-link">
                Manage courses →
            </a>

        </div>


        <!-- LESSONS -->

        <div class="admin-stat stat-pink">

            <div class="admin-stat-top">
                <div class="admin-stat-icon">▣</div>
            </div>

            <div class="admin-stat-number">
                <?= number_format($stats['lessons']) ?>
            </div>

            <div class="admin-stat-label">
                Total Lessons
            </div>

            <a href="courses.php" class="admin-stat-link">
                View courses →
            </a>

        </div>


        <!-- PENDING -->

        <div class="admin-stat stat-orange">

            <div class="admin-stat-top">
                <div class="admin-stat-icon">◷</div>
            </div>

            <div class="admin-stat-number">
                <?= number_format($stats['pending']) ?>
            </div>

            <div class="admin-stat-label">
                Pending Enrollments
            </div>

            <a href="enrollments.php" class="admin-stat-link">
                Review requests →
            </a>

        </div>

    </div>


    <!-- MORE INSIGHTS -->

    <div class="admin-section-title">
        <h2>More Insights</h2>
        <span>Additional statistics</span>
    </div>


    <div class="admin-stats">

        <div class="admin-stat stat-blue">

            <div class="admin-stat-icon">◎</div>

            <div class="admin-stat-number">
                <?= number_format($stats['enrollments']) ?>
            </div>

            <div class="admin-stat-label">
                Total Enrollments
            </div>

            <a href="enrollments.php" class="admin-stat-link">
                View enrollments →
            </a>

        </div>

    </div>


    <!-- QUICK ACTIONS -->

    <div class="admin-section-title">
        <h2>Quick Actions</h2>
        <span>Manage your platform</span>
    </div>


    <div class="admin-quick-grid">

        <a href="courses.php" class="admin-quick-card">

            <div class="quick-icon">＋</div>

            <div>
                <strong>Manage Courses</strong>
                <small>Create and review courses</small>
            </div>

        </a>


        <a href="lessons.php" class="admin-quick-card">

            <div class="quick-icon">▤</div>

            <div>
                <strong>Manage Lessons</strong>
                <small>Organize learning content</small>
            </div>

        </a>


        <a href="enrollments.php" class="admin-quick-card">

            <div class="quick-icon">♧</div>

            <div>
                <strong>Enrollments</strong>
                <small>Review student requests</small>
            </div>

        </a>


        <a href="students.php" class="admin-quick-card">

            <div class="quick-icon">♙</div>

            <div>
                <strong>Students</strong>
                <small>View registered students</small>
            </div>

        </a>

    </div>


    <!-- RECENT ACTIVITY -->

    <div class="admin-content-grid">

        <!-- RECENT COURSES -->

        <section class="admin-box">

            <div class="admin-box-heading">

                <h3>Recently Added Courses</h3>

                <a href="courses.php">
                    View all →
                </a>

            </div>


            <?php if (empty($recentCourses)): ?>

                <div class="admin-empty">
                    No courses have been added yet.
                </div>

            <?php else: ?>

                <div class="admin-table-wrap">

                    <table class="admin-table">

                        <thead>
                            <tr>
                                <th>COURSE</th>
                                <th>STATUS</th>
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
                                        <span class="admin-status <?= e($course['status']) ?>">
                                            <?= e(ucfirst($course['status'])) ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>


        <!-- RECENT ENROLLMENTS -->

        <section class="admin-box">

            <div class="admin-box-heading">

                <h3>Recent Enrollments</h3>

                <a href="enrollments.php">
                    View all →
                </a>

            </div>


            <?php if (empty($recentEnrollments)): ?>

                <div class="admin-empty">
                    No enrollment requests yet.
                </div>

            <?php else: ?>

                <?php foreach ($recentEnrollments as $enrollment): ?>

                    <div class="admin-enrollment">

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

                        <span class="admin-status <?= e($enrollment['status']) ?>">
                            <?= e(ucfirst($enrollment['status'])) ?>
                        </span>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </div>


    <!-- FOOTER -->

    <div class="admin-dashboard-footer">

        <span>
            © <?= date('Y') ?> LearnHub — Administration Panel
        </span>

        <span>
            Made for better learning experiences ✦
        </span>

    </div>

</div>


<?php page_footer(); ?>