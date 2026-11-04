<?php
require '../inc/functions.php'; require '../inc/auth.php';
require_perm('users.manage');
$err=''; $msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  check_csrf();
  $a=post('action'); $uid=(int)($_POST['uid'] ?? 0); $me=(int)$_SESSION['admin_id'];
  if($a==='add'){
    $u=trim(post('u')); $p=post('p'); $r=post('role');
    if(!preg_match('/^[A-Za-z0-9_.-]{3,30}$/',$u)) $err='اسم المستخدم: حروف إنجليزية وأرقام (3 إلى 30).';
    elseif(strlen($p)<10) $err='كلمة المرور 10 أحرف على الأقل.';
    elseif(!isset($ROLES[$r])) $err='اختر الصلاحية.';
    else{
      try{ $pdo->prepare('INSERT INTO admins (username,password_hash,role) VALUES (?,?,?)')->execute([$u,password_hash($p,PASSWORD_DEFAULT),$r]); $msg='تمت إضافة المسؤول.'; }
      catch(PDOException $ex){ $err='اسم المستخدم موجود مسبقاً.'; }
    }
  } elseif($uid===$me && in_array($a,['toggle','delete'],true)){ $err='لا يمكنك تعطيل أو حذف حسابك.'; }
  elseif($a==='toggle'){ $pdo->prepare('UPDATE admins SET active=1-active, token_version=token_version+1 WHERE id=?')->execute([$uid]); }
  elseif($a==='delete'){ $pdo->prepare('DELETE FROM admins WHERE id=?')->execute([$uid]); }
  elseif($a==='role' && isset($ROLES[post('role')])){ $pdo->prepare('UPDATE admins SET role=?, token_version=token_version+1 WHERE id=?')->execute([$_POST['role'],$uid]); }
  elseif($a==='reset'){
    $p=post('p');
    if(strlen($p)<10) $err='كلمة المرور الجديدة 10 أحرف على الأقل.';
    else { $pdo->prepare('UPDATE admins SET password_hash=?, token_version=token_version+1 WHERE id=?')->execute([password_hash($p,PASSWORD_DEFAULT),$uid]); $msg='تم تغيير كلمة المرور.'; }
  }
}
$admins=$pdo->query('SELECT * FROM admins ORDER BY id')->fetchAll();
$title='المسؤولون'; include '_head.php';
?>
<div class="card"><h2>إضافة مسؤول</h2>
<?php if($msg): ?><div class="msg ok"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="add">
<label>اسم المستخدم</label><input name="u" dir="ltr" required>
<label>كلمة المرور</label><input type="password" name="p" required>
<label>الصلاحية</label><select name="role"><?php foreach($ROLES as $k=>$n) if($k!=='super') echo '<option value="'.e($k).'">'.e($n).'</option>'; ?><option value="super">المدير العام</option></select>
<br><button>إضافة</button></form></div>
<div class="card"><h2>المسؤولون</h2>
<?php foreach($admins as $a): ?>
<div class="card" style="background:rgba(127,127,127,.08)">
<b><?=e($a['username'])?></b> — <?=e($ROLES[$a['role']] ?? $a['role'])?> — <?=$a['active']?'مفعّل':'معطّل'?>
<?php if((int)$a['id']!==(int)$_SESSION['admin_id']): ?>
<form method="post" style="margin-top:8px"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="uid" value="<?=(int)$a['id']?>">
<select name="role" style="width:auto;display:inline-block"><?php foreach($ROLES as $k=>$n) echo '<option value="'.e($k).'"'.($k===$a['role']?' selected':'').'>'.e($n).'</option>'; ?></select>
<button name="action" value="role" class="sm">تغيير الصلاحية</button>
<button name="action" value="toggle" class="sm gray"><?=$a['active']?'تعطيل':'تفعيل'?></button>
<button name="action" value="delete" class="sm red" onclick="return confirm('حذف المسؤول؟')">حذف</button><br>
<input type="password" name="p" placeholder="كلمة مرور جديدة" style="width:auto;display:inline-block">
<button name="action" value="reset" class="sm">تعيين كلمة المرور</button></form>
<?php endif; ?></div>
<?php endforeach; ?></div>
<?php include '_foot.php'; ?>
