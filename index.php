<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$st = $pdo->query("
    SELECT
        c.*,
        (
            SELECT COUNT(*)
            FROM lessons l
            WHERE l.course_id = c.id
        ) AS lesson_count
    FROM courses c
    WHERE c.status = 'published'
    ORDER BY c.created_at DESC
");

$courses = $st->fetchAll();

page_header('Home');
?>

<!-- Hero Section -->
<section class="hero hero-modern">

    <div class="hero-content">

        <span class="hero-tag">
            ✦ YOUR LEARNING JOURNEY STARTS HERE
        </span>

        <h1>
            Learn new skills.<br>
            <span>Build your future.</span>
        </h1>

        <p>
            Discover practical courses, develop your skills,
            and take the next step towards your goals.
        </p>

        <div class="hero-actions">
            <a class="btn hero-btn" href="#courses">
                Explore courses <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="hero-note">
            <span class="hero-dot"></span>
            Learn at your own pace, anytime and anywhere.
        </div>

    </div>

    <div class="hero-visual">

        <div class="visual-circle circle-one"></div>
        <div class="visual-circle circle-two"></div>

        <div class="hero-photo-frame"></div>

        <img
            class="hero-photo"
            src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1100&q=85"
            alt="Students learning together around a laptop"
            loading="eager"
        >

        <div class="floating-card floating-card-top">
            <span class="floating-icon">✦</span>
            <div>
                <strong>Keep learning</strong>
                <small>Your future starts today</small>
            </div>
        </div>

        <div class="floating-card floating-card-bottom">
            <span class="floating-icon">✓</span>
            <div>
                <strong>Learn something new</strong>
                <small>One step at a time</small>
            </div>
        </div>

    </div>

</section>


<!-- Courses Section -->
<section class="courses-section" id="courses">

    <div class="section-heading">

        <div>
            <span class="section-label">EXPLORE & DISCOVER</span>

            <h2>Explore our courses</h2>

            <p>
                Find the right course to develop your knowledge and skills.
            </p>
        </div>

        <div class="course-count">
            <?= count($courses) ?> Courses Available
        </div>

    </div>


    <div class="courses-grid">

        <?php if (empty($courses)): ?>

            <p class="empty-courses">
                No courses available yet.
            </p>

        <?php else: ?>

            <?php foreach ($courses as $course): ?>

                <article class="course-card">

                    <!-- Course Image -->
                    <div class="course-card-image">

                        <?php if (!empty($course['image'])): ?>

                            <img
                                src="<?= e(BASE_URL . '/' . $course['image']) ?>"
                                alt="<?= e($course['title']) ?>"
                                loading="lazy"
                            >

                        <?php else: ?>

                            <div class="course-image-placeholder">
                                <span>LearnHub</span>
                                <small>Start learning today</small>
                            </div>

                        <?php endif; ?>

                        <span class="image-level">
                            <?= e(ucfirst($course['level'])) ?>
                        </span>

                    </div>


                    <!-- Course Information -->
                    <div class="course-card-content">

                        <h3>
                            <?= e($course['title']) ?>
                        </h3>

                        <p>
                            <?= e($course['description']) ?>
                        </p>


                        <!-- Course Details -->
                        <div class="course-meta">

                            <span>
                                <span class="meta-icon">◷</span>

                                <?= e(
                                    rtrim(
                                        rtrim(
                                            number_format((float)$course['duration_hours'], 2, '.', ''),
                                            '0'
                                        ),
                                        '.'
                                    )
                                ) ?> Hours
                            </span>

                            <span>
                                <span class="meta-icon">▤</span>

                                <?= (int)$course['lesson_count'] ?>
                                <?= (int)$course['lesson_count'] === 1 ? 'Lesson' : 'Lessons' ?>
                            </span>

                        </div>


                        <!-- Price and Button -->
                        <div class="course-card-footer">

                            <strong>
                                <?= !empty($course['is_free'])
                                    ? 'Free'
                                    : number_format((float)$course['price']) . ' DZD' ?>
                            </strong>

                            <a href="<?= e(BASE_URL) ?>/course.php?id=<?= (int)$course['id'] ?>">
                                View Course →
                            </a>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>


    <!-- Platform Credit -->
    <div class="platform-credit">
        <p>This platform was created by Ms. BENTOUATI</p>
    </div>

</section>


<style>

/* Course Details */
.course-meta {
    display: flex;
    align-items: center;
    gap: 20px;
    margin: 18px 0;
    color: #817991;
    font-size: 12px;
    font-weight: 600;
    flex-wrap: wrap;
}

.course-meta span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.course-meta .meta-icon {
    color: #8063d5;
    font-size: 16px;
}

/* Responsive */
@media (max-width: 600px) {

    .course-meta {
        gap: 14px;
    }

}

</style>


<?php page_footer(); ?>