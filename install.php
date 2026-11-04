<?php
require 'inc/functions.php';
run_schema($pdo); // آمن: ينشئ الجداول الناقصة ويرقّي القديمة
$exists=(int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn()>0;
if($exists){
  die('<div dir="rtl" style="font-family:Tahoma;padding:20px">تم تحديث قاعدة البيانات بنجاح. <b>احذف ملف install.php من السيرفر الآن.</b></div>');
}
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  check_csrf();
  $u=trim(post('u')); $p=post('p');
  if(!preg_match('/^[A-Za-z0-9_.-]{3,30}$/',$u)) $err='اسم المستخدم: حروف إنجليزية وأرقام فقط (3 إلى 30).';
  elseif(strlen($p)<10) $err='كلمة المرور يجب ألا تقل عن 10 أحرف.';
  else{
    $pdo->prepare("INSERT INTO admins (username,password_hash,role) VALUES (?,?,'super')")->execute([$u,password_hash($p,PASSWORD_DEFAULT)]);
    die('<div dir="rtl" style="font-family:Tahoma;padding:20px">تم التثبيت بنجاح. <b>احذف ملف install.php الآن</b> ثم ادخل من <a href="admin/login.php">admin/login.php</a></div>');
  }
}
?><!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><link rel="stylesheet" href="style.css"></head><body><div class="wrap">
<form method="post" class="card"><h2>تثبيت المنصة: إنشاء المدير العام</h2>
<p>تم إنشاء جداول قاعدة البيانات.</p>
<?php if($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?=csrf()?>">
<label>اسم المستخدم (إنجليزي)</label><input name="u" dir="ltr" required>
<label>كلمة المرور (10 أحرف فأكثر)</label><input type="password" name="p" required>
<br><button>إنشاء وإنهاء التثبيت</button></form></div></body></html>
