<?php
require_once __DIR__ . '/config/config.php'; require_once __DIR__ . '/includes/functions.php';
$id=(int)($_GET['id']??0); $st=$pdo->prepare("SELECT * FROM courses WHERE id=? AND status='published'");$st->execute([$id]);$course=$st->fetch();if(!$course){http_response_code(404);exit('Course not found.');}
$enrollment=null;if(current_user() && !is_admin()){$q=$pdo->prepare('SELECT * FROM enrollments WHERE user_id=? AND course_id=?');$q->execute([current_user()['id'],$id]);$enrollment=$q->fetch();}
$lessons=$pdo->prepare('SELECT id,title,description,sort_order FROM lessons WHERE course_id=? ORDER BY sort_order,id');$lessons->execute([$id]);$lessonRows=$lessons->fetchAll();
$hasAccess = is_admin() || ($enrollment && $enrollment['status'] === 'approved');
page_header($course['title']);?><div class="panel"><h1><?=e($course['title'])?></h1><p><?=nl2br(e($course['description']))?></p><p><?=e(ucfirst($course['level']))?> · <strong><?= (int)$course['is_free']?'Free':number_format((float)$course['price'],2).' DZD' ?></strong></p>
<?php if(!current_user()):?><a class="btn" href="<?=e(BASE_URL)?>/auth/login.php">Log in to enroll</a>
<?php elseif(is_admin()):?><a class="btn" href="<?=e(BASE_URL)?>/admin/course.php?id=<?=$id?>">Manage course</a>
<?php elseif((int)$course['is_free']===1):?><form method="post" action="<?=e(BASE_URL)?>/student/enroll-free.php"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="course_id" value="<?=$id?>"><button>Enroll for free</button></form>
<?php elseif(!$enrollment || $enrollment['status']==='rejected'):?><a class="btn" href="<?=e(BASE_URL)?>/student/submit-payment.php?course_id=<?=$id?>">Submit payment receipt</a>
<?php else:?><p class="badge <?=e($enrollment['status'])?>">Enrollment: <?=e(ucfirst($enrollment['status']))?></p><?php if($enrollment['status']==='pending'):?><p>Your payment is waiting for administrator review.</p><?php endif;?><?php endif;?></div>
<div class="panel"><h2>Course curriculum</h2><?php if(!$lessonRows):?><p>Lessons will be available when the administrator adds them.</p><?php elseif($hasAccess):?><ol><?php foreach($lessonRows as $l):?><li><a href="<?=e(BASE_URL)?>/student/learn.php?course_id=<?=$id?>&lesson_id=<?=(int)$l['id']?>"><?=e($l['title'])?></a></li><?php endforeach;?></ol><?php else:?><p><?=count($lessonRows)?> video lesson(s) included. Enroll to access the videos.</p><?php endif;?></div><?php page_footer();?>
