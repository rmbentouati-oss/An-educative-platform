<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$st = $pdo->query("
    SELECT c.*,
    (SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) lesson_count
    FROM courses c
    WHERE c.status='published'
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
            <p>Find the right course to develop your knowledge and skills.</p>
        </div>
        <br>
        <center>
        <p>This platform was created by Ms.BENTOUATI</p>
        </center>

        <div class="course-count">

