<?php
require '../inc/functions.php'; require '../inc/auth.php';
require_perm('news.manage');
if($_SERVER['REQUEST_METHOD']==='POST' && post('action')==='delete'){
  check_csrf();
  $id=(int)post('id');
  $st=$pdo->prepare('SELECT image FROM news WHERE id=?'); $st->execute([$id]); delete_image($st->fetchColumn());
  $pdo->prepare('DELETE FROM news WHERE id=?')->execute([$id]);
  header('Location: news.php'); exit;
}
$list=$pdo->query('SELECT id,title,category,published,created_at FROM news ORDER BY created_at DESC LIMIT 200')->fetchAll();
$title='الأخبار'; include '_head.php';
?>
<div class="card"><h2>الأخبار والفعاليات</h2><a class="btn" href="news_form.php">كتابة خبر جديد</a><br><br>
<div class="tbl"><table><tr><th>العنوان</th><th>النوع</th><th>الحالة</th><th>التاريخ</th><th></th></tr>
<?php foreach($list as $n): ?><tr><td><?=e($n['title'])?></td><td><?=e($CATS[$n['category']] ?? '')?></td><td><?=$n['published']?'منشور':'مسودة'?></td><td><?=e(substr((string)$n['created_at'],0,10))?></td>
<td><a class="btn sm" href="news_form.php?id=<?=(int)$n['id']?>">تعديل</a>
<form method="post" style="display:inline" onsubmit="return confirm('حذف الخبر؟')"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$n['id']?>"><button class="red sm">حذف</button></form></td></tr>
<?php endforeach; ?></table></div></div>
<?php include '_foot.php'; ?>
