<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if (is_admin()) {
    redirect('admin/dashboard.php');
}

$student = current_user();

$st = $pdo->prepare("
    SELECT
        e.status,
        e.submitted_at,
        c.id AS course_id,
        c.title,
        c.price,
        c.is_free,
        c.slug,
        (
            SELECT COUNT(*)
            FROM lessons l
            WHERE l.course_id = c.id
        ) AS lesson_count
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    WHERE e.user_id = ?
    ORDER BY e.submitted_at DESC
");

$st->execute([$student['id']]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$totalCourses = count($rows);
$approvedCourses = 0;
$pendingCourses = 0;

foreach ($rows as $row) {
    if ($row['status'] === 'approved') {
        $approvedCourses++;
    }

    if ($row['status'] === 'pending') {
        $pendingCourses++;
    }
}

page_header('Student Dashboard');
?>

<style>

/* ==================================
   LEARNHUB STUDENT DASHBOARD
================================== */

.student-dashboard {
    max-width: 1250px;
    margin: 35px auto 65px;
    padding: 0 28px;
    color: #29243E;
    font-family: 'Manrope', sans-serif;
}

.student-dashboard * {
    box-sizing: border-box;
}

/* WELCOME */

.student-welcome {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.student-welcome h1 {
    margin: 10px 0;
    font-size: 31px;
    font-weight: 800;
    letter-spacing: -1px;
    color: #29243E;
}

.student-welcome p {
    margin: 0;
    color: #9290A0;
    font-size: 13px;
}

.student-eyebrow {
    color: #8064D7;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
}

.student-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 16px;
    background: white;
    border: 1px solid #EEEAF6;
    border-radius: 15px;
}

.student-avatar {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: linear-gradient(135deg,#7657D5,#B49BEF);
    color: white;
    font-size: 18px;
    font-weight: 800;
}

.student-profile strong,
.student-profile small {
    display: block;
}

.student-profile strong {
    font-size: 12px;
}

.student-profile small {
    margin-top: 4px;
    color: #9993A8;
    font-size: 10px;
}

/* HERO */

.student-hero {
    position: relative;
    overflow: hidden;
    display: flex;
    justify-content: space-between;
    align-items: center;
    min-height: 205px;
    padding: 38px 42px;
    margin-bottom: 30px;
    border-radius: 23px;
    background: linear-gradient(110deg,#6549BD,#9278E5);
    color: white;
    box-shadow: 0 15px 35px rgba(104,75,190,.15);
}

.student-hero-content {
    position: relative;
    z-index: 2;
    max-width: 570px;
}

.student-hero-label {
    color: #E1D8FF;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
}

.student-hero h2 {
    margin: 13px 0 9px;
    color: white;
    font-size: 27px;
    font-weight: 800;
}

.student-hero p {
    margin: 0;
    color: #E9E2FF;
    font-size: 12px;
    line-height: 1.9;
}

.student-hero-decoration {
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
    font-size: 57px;
}

.student-hero::before {
    content: "";
    position: absolute;
    width: 270px;
    height: 270px;
    right: -75px;
    top: -140px;
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 50%;
}

.student-hero::after {
    content: "";
    position: absolute;
    width: 190px;
    height: 190px;
    right: 110px;
    bottom: -135px;
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 50%;
}

/* STATISTICS */

.student-stats {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 18px;
    margin-bottom: 38px;
}

.student-stat {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 23px;
    background: white;
    border: 1px solid #EEEAF6;
    border-radius: 17px;
    transition: .25s ease;
}

.student-stat:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(49,36,87,.06);
}

.student-stat-icon {
    width: 48px;
    height: 48px;
    display: grid;
    flex-shrink: 0;
    place-items: center;
    border-radius: 14px;
    font-size: 21px;
}

.student-stat-purple {
    background: #F0EBFF;
    color: #7657D5;
}

.student-stat-green {
    background: #E5F5EC;
    color: #3B9664;
}

.student-stat-orange {
    background: #FFF3E5;
    color: #D49A50;
}

.student-stat-info strong {
    display: block;
    color: #29243E;
    font-size: 25px;
    font-weight: 800;
}

.student-stat-info span {
    display: block;
    margin-top: 4px;
    color: #9290A0;
    font-size: 11px;
}

/* SECTION TITLE */

.student-section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin: 38px 0 20px;
}

.student-section-heading h2 {
    margin: 0;
    color: #29243E;
    font-size: 20px;
    font-weight: 800;
}

.student-section-heading p {
    margin: 6px 0 0;
    color: #9993A8;
    font-size: 11px;
}

.student-course-count {
    padding: 8px 13px;
    border-radius: 20px;
    background: #F0EBFF;
    color: #7657D5;
    font-size: 10px;
    font-weight: 800;
}

/* COURSE GRID */

.student-course-grid {
    display: grid;
    grid-template-columns: repeat(3,minmax(0,1fr));
    gap: 20px;
}

.student-course-card {
    overflow: hidden;
    background: white;
    border: 1px solid #EEEAF6;
    border-radius: 19px;
    transition: .25s ease;
}

.student-course-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(49,36,87,.08);
    border-color: #DCCFFF;
}

.student-course-cover {
    position: relative;
    min-height: 135px;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg,#EAE2FF,#F7F2FF);
}

.student-course-cover::before {
    content: "";
    position: absolute;
    width: 115px;
    height: 115px;
    border: 1px solid rgba(118,87,213,.15);
    border-radius: 50%;
}

.student-course-symbol {
    position: relative;
    z-index: 1;
    width: 65px;
    height: 65px;
    display: grid;
    place-items: center;
    border-radius: 20px;
    background: rgba(255,255,255,.75);
    color: #7657D5;
    font-size: 29px;
    box-shadow: 0 8px 20px rgba(118,87,213,.10);
}

.student-course-body {
    padding: 20px;
}

.student-course-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 13px;
}

.student-course-card h3 {
    margin: 0 0 13px;
    color: #302A45;
    font-size: 16px;
    font-weight: 800;
    line-height: 1.5;
}

.student-course-meta {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 17px;
    color: #9993A8;
    font-size: 11px;
}

/* STATUS BADGES */

.student-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 9px;
    font-weight: 800;
    text-transform: capitalize;
    white-space: nowrap;
}

.student-badge.approved {
    background: #E5F5EC;
    color: #3B9664;
}

.student-badge.pending {
    background: #FFF3DD;
    color: #B47B28;
}

.student-badge.rejected {
    background: #FCE9E9;
    color: #C35D65;
}

/* COURSE BUTTONS */

.student-course-btn {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    width: 100%;
    min-height: 43px;
    padding: 11px 15px;
    border: none;
    border-radius: 11px;
    background: #7657D5;
    color: white;
    font-size: 11px;
    font-weight: 800;
    text-decoration: none;
    transition: .2s;
}

.student-course-btn:hover {
    background: #5B3DB8;
    color: white;
    transform: translateY(-2px);
}

.student-course-btn.secondary {
    background: #F0EBFF;
    color: #7657D5;
}

.student-course-btn.secondary:hover {
    background: #E4DAFF;
}

.student-course-message {
    margin: 0 0 13px;
    padding: 11px;
    border-radius: 10px;
    background: #FFF8E9;
    color: #A17A36;
    font-size: 10px;
    line-height: 1.7;
}

.student-course-message.rejected {
    background: #FFF0F0;
    color: #BC5555;
}

/* EMPTY STATE */

.student-empty {
    padding: 55px 25px;
    text-align: center;
    background: white;
    border: 1px dashed #DCD3F0;
    border-radius: 20px;
}

.student-empty-icon {
    width: 75px;
    height: 75px;
    display: grid;
    place-items: center;
    margin: 0 auto 18px;
    border-radius: 22px;
    background: #F0EBFF;
    color: #7657D5;
    font-size: 32px;
}

.student-empty h3 {
    margin: 0 0 9px;
    color: #302A45;
    font-size: 18px;
    font-weight: 800;
}

.student-empty p {
    max-width: 390px;
    margin: 0 auto 20px;
    color: #9993A8;
    font-size: 12px;
    line-height: 1.8;
}

.student-empty .student-course-btn {
    max-width: 200px;
    margin: auto;
}

/* FOOTER */

.student-footer {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 45px;
    padding-top: 20px;
    border-top: 1px solid #EEEAF6;
    color: #A29DAF;
    font-size: 10px;
}

/* RESPONSIVE */

@media(max-width:1000px) {
    .student-course-grid {
        grid-template-columns: repeat(2,minmax(0,1fr));
    }
}

@media(max-width:750px) {
    .student-dashboard {
        padding: 0 17px;
    }

    .student-hero {
        padding: 30px;
    }

    .student-hero-decoration {
        right: 25px;
        opacity: .4;
    }

    .student-stats {
        gap: 10px;
    }
}

@media(max-width:550px) {
    .student-welcome {
        align-items: flex-start;
        flex-direction: column;
    }

    .student-welcome h1 {
        font-size: 25px;
    }

    .student-stats {
        grid-template-columns: 1fr;
    }

    .student-course-grid {
        grid-template-columns: 1fr;
    }

    .student-hero-decoration {
        display: none;
    }

    .student-hero h2 {
        font-size: 22px;
    }

    .student-section-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .student-footer {
        flex-direction: column;
    }
}

</style>


<div class="student-dashboard">

    <!-- WELCOME HEADER -->

    <div class="student-welcome">

        <div>
            <span class="student-eyebrow">
                STUDENT / MY LEARNING SPACE
            </span>

            <h1>
                Welcome back,
                <?= e($student['full_name']) ?>!
            </h1>

            <p>
                Continue your learning journey and explore new skills.
            </p>
        </div>

        <div class="student-profile">

            <div class="student-avatar">
                <?= e(strtoupper(substr($student['full_name'] ?? 'S', 0, 1))) ?>
            </div>

            <div>
                <strong><?= e($student['full_name']) ?></strong>
                <small>Student Account</small>
            </div>

        </div>

    </div>


    <!-- HERO BANNER -->

    <section class="student-hero">

        <div class="student-hero-content">

            <span class="student-hero-label">
                YOUR LEARNING JOURNEY
            </span>

            <h2>Learn something amazing today.</h2>

            <p>
                Every course is a new opportunity.
                Keep learning, develop your skills, and move closer to your goals.
            </p>

        </div>

        <div class="student-hero-decoration">
            ✦
        </div>

    </section>


    <!-- STATISTICS -->

    <div class="student-stats">

        <div class="student-stat">

            <div class="student-stat-icon student-stat-purple">
                ▤
            </div>

            <div class="student-stat-info">
                <strong><?= $totalCourses ?></strong>
                <span>Enrolled Courses</span>
            </div>

        </div>


        <div class="student-stat">

            <div class="student-stat-icon student-stat-green">
                ✓
            </div>

            <div class="student-stat-info">
                <strong><?= $approvedCourses ?></strong>
                <span>Approved Courses</span>
            </div>

        </div>


        <div class="student-stat">

            <div class="student-stat-icon student-stat-orange">
                ◷
            </div>

            <div class="student-stat-info">
                <strong><?= $pendingCourses ?></strong>
                <span>Pending Requests</span>
            </div>

        </div>

    </div>


    <!-- MY COURSES -->

    <div class="student-section-heading">

        <div>
            <h2>My Learning Space</h2>
            <p>All your courses and enrollment requests in one place.</p>
        </div>

        <span class="student-course-count">
            <?= $totalCourses ?> COURSES
        </span>

    </div>


    <?php if (empty($rows)): ?>

        <!-- EMPTY STATE -->

        <div class="student-empty">

            <div class="student-empty-icon">
                ▤
            </div>

            <h3>Your learning journey starts here!</h3>

            <p>
                You haven't enrolled in any course yet.
                Explore our available courses and discover something new to learn.
            </p>

            <a href="<?= e(BASE_URL) ?>/index.php" class="student-course-btn">
                Explore Courses →
            </a>

        </div>

    <?php else: ?>

        <div class="student-course-grid">

            <?php foreach ($rows as $r): ?>

                <article class="student-course-card">

                    <div class="student-course-cover">

                        <div class="student-course-symbol">
                            ✦
                        </div>

                    </div>


                    <div class="student-course-body">

                        <div class="student-course-top">

                            <span class="student-badge <?= e($r['status']) ?>">

                                <?php if ($r['status'] === 'approved'): ?>
                                    ✓
                                <?php elseif ($r['status'] === 'pending'): ?>
                                    ◷
                                <?php else: ?>
                                    !
                                <?php endif; ?>

                                <?= e(ucfirst($r['status'])) ?>

                            </span>

                        </div>


                        <h3>
                            <?= e($r['title']) ?>
                        </h3>


                        <div class="student-course-meta">
                            ▤
                            <?= (int)$r['lesson_count'] ?> Lessons
                        </div>


                        <?php if ($r['status'] === 'approved'): ?>

                            <a
                                class="student-course-btn"
                                href="<?= e(BASE_URL) ?>/student/learn.php?course_id=<?= (int)$r['course_id'] ?>"
                            >
                                Continue Learning →
                            </a>


                        <?php elseif ($r['status'] === 'pending'): ?>

                            <p class="student-course-message">
                                Your receipt is waiting for review by the administrator.
                            </p>

                            <a
                                class="student-course-btn secondary"
                                href="<?= e(BASE_URL) ?>/index.php"
                            >
                                Browse Courses
                            </a>


                        <?php else: ?>

                            <p class="student-course-message rejected">
                                Your payment was not approved.
                                You can submit a new receipt.
                            </p>

                            <a
                                class="student-course-btn"
                                href="<?= e(BASE_URL) ?>/student/submit-payment.php?course_id=<?= (int)$r['course_id'] ?>"
                            >
                                Resubmit Receipt →
                            </a>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- FOOTER -->

    <div class="student-footer">

        <span>
            © <?= date('Y') ?> LearnHub — Student Portal
        </span>

        <span>
            Keep learning. Keep growing. ✦
        </span>

    </div>

</div>


<?php page_footer(); ?>