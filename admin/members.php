<?php
require '../inc/functions.php'; require '../inc/auth.php';
require_perm('members.view');

if($_SERVER['REQUEST_METHOD']==='POST' && post('action')==='delete'){
  require_perm('members.delete'); check_csrf();
  $pdo->prepare('DELETE FROM members WHERE id=?')->execute([(int)post('id')]);
  header('Location: members.php'); exit;
}
list($sql,$args,$F)=member_filters($_GET);
$per=50; $page=max(1,(int)get('page'));
$cnt=$pdo->prepare("SELECT COUNT(*) FROM members$sql"); $cnt->execute($args);
$count=(int)$cnt->fetchColumn(); $pages=max(1,(int)ceil($count/$per)); if($page>$pages) $page=$pages;
$st=$pdo->prepare("SELECT * FROM members$sql ORDER BY id DESC LIMIT $per OFFSET ".(($page-1)*$per));
$st->execute($args); $rows=$st->fetchAll();
$qs=function($extra=[]) use ($F){ return http_build_query(array_filter(array_merge($F,$extra),function($x){ return $x!=='' && $x!==null; })); };
$title='المسجلون'; include '_head.php';
?>
<div class="card"><h2>المسجلون (<?=$count?>)</h2>
<form method="get">
<input name="q" placeholder="اسم / هاتف / رقم تسجيل" value="<?=e($F['q'])?>"><br><br>
<select name="type"><option value="">داخل وخارج السودان</option><option value="inside"<?=$F['type']==='inside'?' selected':''?>>داخل السودان</option><option value="outside"<?=$F['type']==='outside'?' selected':''?>>خارج السودان</option></select><br><br>
<select name="country"><option value="">كل الدول</option><option<?=$F['country']==='السودان'?' selected':''?>>السودان</option>
<?php foreach($COUNTRIES as $c) echo '<option'.($F['country']===$c?' selected':'').'>'.e($c).'</option>'; ?></select><br><br>
<select name="state"><option value="">كل الولايات</option>
<?php foreach(array_keys($STATES) as $s) echo '<option'.($F['state']===$s?' selected':'').'>'.e($s).'</option>'; ?></select><br><br>
<input name="city" placeholder="المدينة" value="<?=e($F['city'])?>"><br><br>
<button>بحث</button>
<?php if(admin_can('members.full')): ?><a class="btn gray" href="directory.php?<?=e($qs())?>">كل البيانات وتصدير Excel</a><?php endif; ?>
</form><br>
<div class="tbl"><table><tr><th>الرقم</th><th>الاسم</th><th>الهاتف</th><th>الدولة</th><th>الولاية</th><th>المدينة</th><th>التاريخ</th><th></th></tr>
<?php foreach($rows as $r): ?>
<tr><td><?=e($r['reg_no'])?></td><td><?=e($r['full_name'])?></td><td dir="ltr"><?=e($r['phone'])?></td><td><?=e($r['country'])?></td><td><?=e($r['state'])?></td><td><?=e($r['city'])?></td><td><?=e(substr((string)$r['created_at'],0,10))?></td>
<td>
<?php if(admin_can('members.edit')): ?><a class="btn sm" href="member_form.php?id=<?=(int)$r['id']?>">تعديل</a><?php endif; ?>
<?php if(admin_can('members.delete')): ?>
<form method="post" style="display:inline" onsubmit="return confirm('حذف السجل نهائياً؟')">
<input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$r['id']?>">
<button class="red sm">حذف</button></form>
<?php endif; ?>
</td></tr>
<?php endforeach; ?></table></div>
<div class="pages" style="margin-top:12px">
<?php for($p=1;$p<=$pages;$p++): ?><a href="?<?=e($qs(['page'=>$p]))?>"<?=$p===$page?' style="background:#1b5e3b;color:#fff"':''?>><?=$p?></a><?php endfor; ?>
</div></div>
<?php include '_foot.php'; ?>
