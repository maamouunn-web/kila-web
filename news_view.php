<?php
require 'inc/functions.php';
$st=$pdo->prepare('SELECT * FROM news WHERE id=? AND published=1');
$st->execute([(int)get('id')]);
$n=$st->fetch();
if(!$n){ http_response_code(404); die('الخبر غير موجود.'); }
?><!DOCTYPE html>
<html lang="ar" dir="rtl"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($n['title'])?> - <?=e(SITE_NAME)?></title><link rel="stylesheet" href="style.css"></head><body>
<div class="wrap">
<div class="card">
<span class="tag"><?=e($CATS[$n['category']] ?? 'خبر')?></span> <small><?=e(substr((string)$n['created_at'],0,10))?></small>
<h1><?=e($n['title'])?></h1>
<?php if($n['event_date']): ?><p><b>موعد الفعالية:</b> <?=e($n['event_date'])?></p><?php endif; ?>
<?php if($n['image']): ?><img class="newsimg big" alt="" src="uploads/<?=e($n['image'])?>"><?php endif; ?>
<p><?=nl2br(e($n['body']))?></p>
<a class="btn gray" href="news.php">كل الأخبار</a>
</div></div></body></html>
