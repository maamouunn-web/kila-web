<?php
require '../inc/functions.php'; require '../inc/auth.php';
require_login();
$err=''; $msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  check_csrf();
  $st=$pdo->prepare('SELECT password_hash FROM admins WHERE id=?'); $st->execute([$_SESSION['admin_id']]); $h=$st->fetchColumn();
  if(!password_verify(post('old'),(string)$h)) $err='كلمة المرور الحالية غير صحيحة.';
  elseif(strlen(post('new'))<10) $err='كلمة المرور الجديدة 10 أحرف على الأقل.';
  elseif(post('new')!==post('new2')) $err='كلمتا المرور غير متطابقتين.';
  else{
    $pdo->prepare('UPDATE admins SET password_hash=?, token_version=token_version+1 WHERE id=?')->execute([password_hash(post('new'),PASSWORD_DEFAULT),$_SESSION['admin_id']]);
    $tv=$pdo->prepare('SELECT token_version FROM admins WHERE id=?'); $tv->execute([$_SESSION['admin_id']]);
    admin_issue($_SESSION['admin_id'],$tv->fetchColumn()); // يُنهي الجلسات الأخرى ويُبقي هذه
    $msg='تم تغيير كلمة المرور، وأُنهيت جلساتك الأخرى.';
  }
}
$title='كلمة المرور'; include '_head.php';
?>
<form method="post" class="card"><h2>تغيير كلمة المرور</h2>
<?php if($msg): ?><div class="msg ok"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?=csrf()?>">
<label>الحالية</label><input type="password" name="old" required>
<label>الجديدة (10 أحرف فأكثر)</label><input type="password" name="new" required>
<label>تأكيد الجديدة</label><input type="password" name="new2" required>
<br><button>تغيير</button></form>
<?php include '_foot.php'; ?>
