<?php
require '../inc/functions.php'; require '../inc/auth.php';
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  check_csrf();
  if(too_many_attempts()){ $err='محاولات كثيرة. انتظر 15 دقيقة ثم حاول مجدداً.'; }
  else{
    $st=$pdo->prepare('SELECT * FROM admins WHERE username=? AND active=1');
    $st->execute([trim(post('u'))]);
    $a=$st->fetch();
    if($a && password_verify(post('p'),$a['password_hash'])){
      session_regenerate_id(true);
      admin_issue($a['id'],$a['token_version']);
      header('Location: index.php'); exit;
    } else { log_fail(); $err='بيانات الدخول غير صحيحة.'; }
  }
}
?><!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>دخول الإدارة</title><link rel="stylesheet" href="../style.css"></head><body><div class="wrap">
<form method="post" class="card"><h2>دخول الإدارة</h2>
<?php if($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?=csrf()?>">
<label>اسم المستخدم</label><input name="u" dir="ltr" required autocomplete="username">
<label>كلمة المرور</label><input type="password" name="p" required autocomplete="current-password">
<br><button>دخول</button></form></div></body></html>
