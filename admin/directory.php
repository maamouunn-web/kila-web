<?php
require '../inc/functions.php'; require '../inc/auth.php';
require_perm('members.full');
list($sql,$args,$F)=member_filters($_GET);
$HEAD=['reg_no'=>'رقم التسجيل','full_name'=>'الاسم','nickname'=>'الشهرة','gender'=>'الجنس','age'=>'العمر','phone'=>'الهاتف','phone2'=>'هاتف إضافي','job'=>'المهنة','education'=>'التعليم','location_type'=>'الإقامة','country'=>'الدولة','state'=>'الولاية','locality'=>'المحلية','city'=>'المدينة','area'=>'الحي/القرية','join_initiatives'=>'المبادرات','join_groups'=>'مجموعات التواصل','created_at'=>'تاريخ التسجيل','updated_at'=>'آخر تحديث'];
$prep=function($r) use ($HEAD){
  $o=[];
  foreach(array_keys($HEAD) as $k){
    $v=$r[$k] ?? null;
    if($k==='location_type') $v=$v==='inside'?'داخل السودان':'خارج السودان';
    elseif($k==='join_initiatives'||$k==='join_groups') $v=$v?'نعم':'لا';
    elseif($k==='age' && $v!==null) $v=(int)$v;
    $o[]=$v;
  }
  return $o;
};
if(isset($_GET['export'])){
  require_perm('members.export');
  $st=$pdo->prepare("SELECT * FROM members$sql ORDER BY id"); $st->execute($args);
  $out=[]; foreach($st->fetchAll() as $r) $out[]=$prep($r);
  xlsx_output('kila_members_'.date('Y-m-d'),array_values($HEAD),$out);
}
$per=100; $page=max(1,(int)get('page'));
$cnt=$pdo->prepare("SELECT COUNT(*) FROM members$sql"); $cnt->execute($args);
$count=(int)$cnt->fetchColumn(); $pages=max(1,(int)ceil($count/$per)); if($page>$pages) $page=$pages;
$st=$pdo->prepare("SELECT * FROM members$sql ORDER BY id DESC LIMIT $per OFFSET ".(($page-1)*$per));
$st->execute($args); $rows=$st->fetchAll();
$qs=function($extra=[]) use ($F){ return http_build_query(array_filter(array_merge($F,$extra),function($x){ return $x!=='' && $x!==null; })); };
$title='كل البيانات'; include '_head.php';
?>
<div class="card"><h2>جميع بيانات المسجلين (<?=$count?>)</h2>
<form method="get">
<input name="q" placeholder="اسم / هاتف / رقم تسجيل" value="<?=e($F['q'])?>"><br><br>
<select name="type"><option value="">داخل وخارج السودان</option><option value="inside"<?=$F['type']==='inside'?' selected':''?>>داخل السودان</option><option value="outside"<?=$F['type']==='outside'?' selected':''?>>خارج السودان</option></select><br><br>
<select name="country"><option value="">كل الدول</option><option<?=$F['country']==='السودان'?' selected':''?>>السودان</option>
<?php foreach($COUNTRIES as $c) echo '<option'.($F['country']===$c?' selected':'').'>'.e($c).'</option>'; ?></select><br><br>
<select name="state"><option value="">كل الولايات</option>
<?php foreach(array_keys($STATES) as $s) echo '<option'.($F['state']===$s?' selected':'').'>'.e($s).'</option>'; ?></select><br><br>
<input name="city" placeholder="المدينة" value="<?=e($F['city'])?>"><br><br>
<button>بحث</button>
<?php if(admin_can('members.export')): ?><a class="btn gray" href="?<?=e($qs(['export'=>1]))?>">تصدير الأسماء والبيانات إلى Excel</a><?php endif; ?>
</form><br>
<div class="tbl"><table><tr><?php foreach($HEAD as $h) echo '<th>'.e($h).'</th>'; ?></tr>
<?php foreach($rows as $r): ?><tr><?php foreach($prep($r) as $i=>$v) echo '<td'.($i===5||$i===6?' dir="ltr"':'').'>'.e($v).'</td>'; ?></tr><?php endforeach; ?>
</table></div>
<div class="pages" style="margin-top:12px">
<?php for($p=1;$p<=$pages;$p++): ?><a href="?<?=e($qs(['page'=>$p]))?>"<?=$p===$page?' style="background:#1b5e3b;color:#fff"':''?>><?=$p?></a><?php endfor; ?>
</div></div>
<?php include '_foot.php'; ?>
