<?php
require 'inc/functions.php';
$cat=get('cat'); $page=max(1,(int)get('page'));
$where='WHERE published=1'; $args=[];
if(isset($CATS[$cat])){ $where.=' AND category=?'; $args[]=$cat; } else $cat='';
$per=10;
$c=$pdo->prepare("SELECT COUNT(*) FROM news $where"); $c->execute($args);
$pages=max(1,(int)ceil((int)$c->fetchColumn()/$per)); if($page>$pages) $page=$pages;
$st=$pdo->prepare("SELECT id,title,category,image,created_at,body FROM news $where ORDER BY created_at DESC LIMIT $per OFFSET ".(($page-1)*$per));
$st->execute($args); $list=$st->fetchAll();
?><!DOCTYPE html>
<html lang="ar" dir="rtl"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>أخبار الجمعية - <?=e(SITE_NAME)?></title><link rel="stylesheet" href="style.css"></head><body>
<div class="wrap">
<div class="card"><h1>أخبار وفعاليات الجمعية</h1>
<div class="nav"><a href="news.php">الكل</a>
<?php foreach($CATS as $k=>$n) echo '<a href="news.php?cat='.e($k).'">'.e($n).'</a>'; ?>
<a href="index.php">الرئيسية</a></div></div>
<div class="card">
<?php if(!$list) echo '<p>لا توجد أخبار حالياً.</p>'; ?>
<?php foreach($list as $n) include 'inc/news_item.php'; ?>
<div class="pages">
<?php for($p=1;$p<=$pages;$p++) echo '<a href="news.php?'.e(http_build_query(array_filter(['cat'=>$cat,'page'=>$p]))).'">'.$p.'</a>'; ?>
</div></div>
</div></body></html>
