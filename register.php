<?php
require 'inc/functions.php';
$err=''; $done=null; $v=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
  check_csrf();
  if(trim(post('website'))!=='') die('ok');
  list($d,$err)=validate_member($_POST,true,0);
  if($err===''){
    try{ $done=create_member($d); }
    catch(PDOException $ex){ $err='تعذر الحفظ، ربما الرقم مسجل مسبقاً.'; }
  }
  if($err!=='') $v=$_POST;
}
?><!DOCTYPE html>
<html lang="ar" dir="rtl"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>التسجيل - <?=e(SITE_NAME)?></title><link rel="stylesheet" href="style.css"></head><body>
<div class="wrap">
<div class="card"><h1><?=e(COMMITTEE)?></h1><p>السجل الإلكتروني الشامل لأبناء كيلا 2026–2028</p></div>
<?php if($done): ?>
<div class="card"><div class="msg ok">
<b>تم تسجيلك بنجاح.</b><br>
رقم التسجيل: <span class="code"><?=e($done[0])?></span><br>
رمز التحديث: <span class="code"><?=e($done[1])?></span><br><br>
احتفظ بالرقم والرمز (صوّر الشاشة)، فستحتاجهما لتحديث بياناتك عند تغيير الهاتف أو الإقامة. لن يظهر الرمز مرة أخرى.
</div><a class="btn gray" href="index.php">الصفحة الرئيسية</a></div>
<?php else: ?>
<form method="post" class="card" onsubmit="return checkForm()">
<?php if($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?=csrf()?>">
<input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
<?php member_fields($v,''); ?>
<label><input type="checkbox" name="consent" value="1" required>أوافق على استخدام بياناتي لأغراض الحصر والتواصل المجتمعي فقط، وأعلم أنها لن تُعرض للعامة. *</label>
<br><button>تسجيل</button>
</form>
<?php endif; ?>
</div></body></html>
