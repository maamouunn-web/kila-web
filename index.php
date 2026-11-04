<?php
require 'inc/functions.php';
$total=null; $byCountry=[]; $latest=[];
try{
  if(SHOW_PUBLIC_STATS){
    $total=(int)$pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
    $byCountry=$pdo->query('SELECT country,COUNT(*) c FROM members GROUP BY country ORDER BY c DESC LIMIT 8')->fetchAll();
  }
  $latest=$pdo->query('SELECT id,title,category,image,created_at,body FROM news WHERE published=1 ORDER BY created_at DESC LIMIT 3')->fetchAll();
}catch(Exception $ex){}
?><!DOCTYPE html>
<html lang="ar" dir="rtl"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e(SITE_NAME)?></title><link rel="stylesheet" href="style.css"></head><body>
<div class="wrap">
<div class="card home">
<h1><?=e(COMMITTEE)?></h1>
<h2>السجل الإلكتروني الشامل لأبناء كيلا 2026–2028</h2>
<p>«نحو قاعدة بيانات موحدة، وتواصل مستمر، وتكافل يجمع أبناء المنطقة أينما كانوا».</p>
<a class="btn" href="register.php">سجّل بياناتك الآن</a>
<a class="btn gray" href="update.php">حدّث بياناتك</a>
<a class="btn gray" href="news.php">أخبار الجمعية</a>
</div>
<?php if($latest): ?>
<div class="card"><h2>آخر الأخبار</h2>
<?php foreach($latest as $n) include 'inc/news_item.php'; ?>
<a class="btn sm" href="news.php">كل الأخبار</a></div>
<?php endif; ?>
<?php if($total!==null): ?>
<div class="card">
<div class="stats"><div class="stat"><b><?=$total?></b>إجمالي المسجلين</div></div>
<?php if($byCountry): ?><h2 style="margin-top:16px">الانتشار حسب الدولة</h2><?php bars($byCountry,'country'); ?><?php endif; ?>
</div>
<?php endif; ?>
<div class="card"><small>بيانات المسجلين محمية ولا تُعرض للعامة، وتُستخدم لأغراض الحصر والتواصل المجتمعي فقط.</small></div>
</div></body></html>
