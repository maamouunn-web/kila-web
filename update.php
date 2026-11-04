<?php
require 'inc/functions.php';
if(isset($_GET['logout'])){ jwt_clear('kila_member'); header('Location: update.php'); exit; }
$err=''; $msg=''; $v=[];
$mid=member_id_from_cookie();

if($_SERVER['REQUEST_METHOD']==='POST'){
  check_csrf();
  $action=post('action');
  if($action==='login'){
    if(too_many_attempts()){ $err='محاولات كثيرة. انتظر 15 دقيقة ثم حاول مجدداً.'; }
    else{
      $st=$pdo->prepare('SELECT id,edit_code_hash FROM members WHERE reg_no=?');
      $st->execute([strtoupper(trim(post('reg_no')))]);
      $m=$st->fetch();
      if($m && $m['edit_code_hash'] && password_verify(strtoupper(trim(post('code'))),$m['edit_code_hash'])){
        $mid=(int)$m['id'];
        jwt_cookie('kila_member',['typ'=>'member','sub'=>$mid],MEMBER_SESSION_HOURS*3600);
      } else { log_fail(); $err='رقم التسجيل أو الرمز غير صحيح.'; }
    }
  } elseif($action==='save' && $mid){
    list($d,$err)=validate_member($_POST,false,$mid);
    if($err===''){
      try{ update_member($mid,$d); $msg='تم تحديث بياناتك بنجاح.'; }
      catch(PDOException $ex){ $err='تعذر الحفظ.'; }
    }
    if($err!=='') $v=$_POST;
  }
}
$m=null;
if($mid){
  $st=$pdo->prepare('SELECT * FROM members WHERE id=?'); $st->execute([$mid]); $m=$st->fetch();
  if(!$m){ jwt_clear('kila_member'); $mid=0; }
  elseif(!$v) $v=member_to_form($m);
}
?><!DOCTYPE html>
<html lang="ar" dir="rtl"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title>تحديث البيانات - <?=e(SITE_NAME)?></title><link rel="stylesheet" href="style.css"></head><body>
<div class="wrap">
<div class="card"><h1>تحديث بياناتك</h1></div>
<?php if(!$mid): ?>
<form method="post" class="card">
<?php if($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="login">
<label>رقم التسجيل</label><input name="reg_no" dir="ltr" placeholder="KILA-2026-00001" required>
<label>رمز التحديث</label><input name="code" dir="ltr" required>
<br><button>دخول</button> <a class="btn gray" href="index.php">رجوع</a>
<p><small>نسيت الرمز؟ تواصل مع الإدارة لإصدار رمز جديد.</small></p>
</form>
<?php else: ?>
<form method="post" class="card" onsubmit="return checkForm()">
<p>رقم التسجيل: <b><?=e($m['reg_no'])?></b></p>
<?php if($msg): ?><div class="msg ok"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="save">
<?php member_fields($v,''); ?>
<br><button>حفظ التحديث</button> <a class="btn gray" href="update.php?logout=1">خروج</a>
</form>
<?php endif; ?>
</div></body></html>
