<?php
require_once __DIR__ . '/config/config.php'; require_once __DIR__ . '/includes/functions.php';
$st = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) lesson_count FROM courses c WHERE c.status='published' ORDER BY c.created_at DESC"); $courses = $st->fetchAll();
page_header('Home'); ?>
<section class="hero"><h1>Learn new skills. Build your future.</h1><p>Explore practical online courses and learn at your own pace.</p><a class="btn secondary" href="#courses">Explore courses</a></section>
<h2 id="courses">Available courses</h2><div class="grid">
<?php foreach ($courses as $c): ?><article class="card"><h3><?= e($c['title']) ?></h3><p class="muted"><?= e(mb_strimwidth($c['description'],0,145,'…')) ?></p><p><?= e(ucfirst($c['level'])) ?> · <?= (int)$c['lesson_count'] ?> lessons</p><strong><?= (int)$c['is_free'] ? 'Free' : number_format((float)$c['price'],2).' DZD' ?></strong><p><a class="btn" href="<?= e(BASE_URL) ?>/course.php?id=<?= (int)$c['id'] ?>">View course</a></p></article><?php endforeach; ?>
<?php if (!$courses): ?><p>No courses have been published yet.</p><?php endif; ?></div><?php page_footer(); ?>
