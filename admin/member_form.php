<?php
require '../inc/functions.php'; require '../inc/auth.php';
require_login();
$id=(int)($_GET['id'] ?? $_POST['id'] ?? 0);
require_perm($id ? 'members.edit' : 'members.add');
$m=null;
if($id){
  $st=$pdo->prepare('SELECT * FROM members WHERE id=?'); $st->execute([$id]); $m=$st->fetch();
  if(!$m) die('السجل غير موجود.');
}
$err=''; $msg=''; $v=$m ? member_to_form($m) : [];
if($_SERVER['REQUEST_METHOD']==='POST'){
  check_csrf();
  if((post('action'))==='newcode' && $id){
    $code=new_code();
    $pdo->prepare('UPDATE members SET edit_code_hash=? WHERE id=?')->execute([password_hash($code,PASSWORD_DEFAULT),$id]);
    $msg='رمز التحديث الجديد: <span class="code">'.e($code).'</span> (سلّمه للشخص، لن يظهر مرة أخرى)';
  } else {
    if(!$id && empty($_POST['confirm'])) $err='أكّد أن الشخص وافق على تسجيل بياناته.';
    else list($d,$err)=validate_member($_POST,false,$id,!empty($_POST['allow_same']));
    if($err===''){
      try{
        if($id){ update_member($id,$d); header('Location: members.php'); exit; }
        list($reg,$code)=create_member($d);
        $msg='تمت الإضافة. رقم التسجيل: <span class="code">'.e($reg).'</span> رمز التحديث: <span class="code">'.e($code).'</span>';
        $v=[];
      }catch(PDOException $ex){ $err='تعذر الحفظ، ربما الرقم مسجل مسبقاً.'; }
    }
    if($err!=='') $v=$_POST;
  }
}
$title=$id?'تعديل سجل':'إضافة شخص'; include '_head.php';
?>
<form method="post" class="card" onsubmit="return checkForm()">
<h2><?=$id?'تعديل: '.e($m['reg_no']):'إضافة شخص يدوياً'?></h2>
<?php if($msg): ?><div class="msg ok"><?=$msg?></div><?php endif; ?>
<?php if($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=$id?>">
<?php member_fields($v,'../'); ?>
<label><input type="checkbox" name="allow_same" value="1">السماح بنفس الاسم (شخص آخر فعلاً يحمل الاسم نفسه)</label>
<?php if(!$id): ?><label><input type="checkbox" name="confirm" value="1">أؤكد أن الشخص وافق على تسجيل بياناته لأغراض الحصر والتواصل المجتمعي</label><?php endif; ?>
<br><button>حفظ</button> <a class="btn gray" href="members.php">رجوع</a>
</form>
<?php if($id): ?>
<form method="post" class="card" onsubmit="return confirm('إصدار رمز جديد يلغي القديم. متابعة؟')">
<input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="newcode">
<p>إذا نسي الشخص رمز التحديث يمكنك إصدار رمز جديد له.</p><button class="gray">إصدار رمز تحديث جديد</button>
</form>
<?php endif; ?>
<?php include '_foot.php'; ?>
