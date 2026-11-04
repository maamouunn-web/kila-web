<?php
require '../inc/functions.php'; require '../inc/auth.php';
require_perm('news.manage');
$id=(int)get('id'); if(!$id) $id=(int)post('id');
$n=null;
if($id){ $st=$pdo->prepare('SELECT * FROM news WHERE id=?'); $st->execute([$id]); $n=$st->fetch(); if(!$n) die('غير موجود.'); }
$err=''; $v=$n ?: ['title'=>'','category'=>'news','body'=>'','event_date'=>'','published'=>1];
if($_SERVER['REQUEST_METHOD']==='POST'){
  check_csrf();
  $t=trim(post('title')); $cat=post('category'); $body=trim(post('body')); $date=trim(post('event_date')); $pub=post('published')?1:0;
  $v=['title'=>$t,'category'=>$cat,'body'=>$body,'event_date'=>$date,'published'=>$pub,'image'=>$n['image'] ?? null];
  if(mb_strlen($t)<3 || mb_strlen($t)>200) $err='العنوان من 3 إلى 200 حرف.';
  elseif(!isset($CATS[$cat])) $err='اختر نوع الخبر.';
  elseif(mb_strlen($body)<10 || mb_strlen($body)>10000) $err='النص من 10 إلى 10000 حرف.';
  elseif($date!=='' && (!($dt=DateTime::createFromFormat('Y-m-d',$date)) || $dt->format('Y-m-d')!==$date)) $err='التاريخ غير صحيح.';
  else{
    list($img,$ierr)=save_image($_FILES['image'] ?? []);
    if($ierr!=='') $err=$ierr;
    else{
      $old=$n['image'] ?? null; $keep=$old;
      if($img){ $keep=$img; delete_image($old); }
      elseif(post('remove_image')){ $keep=null; delete_image($old); }
      if($id){
        $pdo->prepare('UPDATE news SET title=?,category=?,body=?,image=?,event_date=?,published=?,updated_at=NOW() WHERE id=?')->execute([$t,$cat,$body,$keep,$date?:null,$pub,$id]);
      } else {
        $pdo->prepare('INSERT INTO news (title,category,body,image,event_date,published,author_id) VALUES (?,?,?,?,?,?,?)')->execute([$t,$cat,$body,$keep,$date?:null,$pub,$_SESSION['admin_id']]);
      }
      header('Location: news.php'); exit;
    }
  }
}
$title=$id?'تعديل خبر':'خبر جديد'; include '_head.php';
?>
<form method="post" enctype="multipart/form-data" class="card"><h2><?=$id?'تعديل الخبر':'كتابة خبر جديد'?></h2>
<?php if($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=$id?>">
<label>العنوان *</label><input name="title" maxlength="200" required value="<?=e($v['title'])?>">
<label>النوع *</label><select name="category"><?php foreach($CATS as $k=>$nm) echo '<option value="'.e($k).'"'.($v['category']===$k?' selected':'').'>'.e($nm).'</option>'; ?></select>
<label>تاريخ الفعالية (اختياري)</label><input type="date" name="event_date" value="<?=e($v['event_date'])?>">
<label>النص *</label><textarea name="body" rows="10" required><?=e($v['body'])?></textarea>
<label>صورة (JPG أو PNG أو WEBP، حتى 2 ميجابايت)</label>
<?php if(!empty($n['image'])): ?><img class="newsimg" alt="" src="../uploads/<?=e($n['image'])?>"><label><input type="checkbox" name="remove_image" value="1">حذف الصورة الحالية</label><?php endif; ?>
<input type="file" name="image" accept="image/jpeg,image/png,image/webp">
<label><input type="checkbox" name="published" value="1"<?=$v['published']?' checked':''?>>نشر الخبر للزوار</label>
<br><button>حفظ</button> <a class="btn gray" href="news.php">رجوع</a>
</form>
<?php include '_foot.php'; ?>
