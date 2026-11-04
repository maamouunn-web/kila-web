<?php
function save_image(array $f): array {
  if(!isset($f['error']) || $f['error']===UPLOAD_ERR_NO_FILE) return [null,''];
  if($f['error']!==UPLOAD_ERR_OK) return [null,'تعذر رفع الصورة (ربما حجمها أكبر من المسموح في الاستضافة).'];
  if($f['size']>2*1024*1024) return [null,'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.'];
  if(!is_uploaded_file($f['tmp_name'])) return [null,'ملف غير صالح.'];
  $fi=new finfo(FILEINFO_MIME_TYPE);
  $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  $ext=$types[$fi->file($f['tmp_name'])] ?? null;
  if(!$ext) return [null,'الصيغ المسموحة: JPG وPNG وWEBP فقط.'];
  $info=@getimagesize($f['tmp_name']);
  if(!$info || $info[0]>6000 || $info[1]>6000) return [null,'الصورة غير صالحة أو كبيرة جداً.'];
  $name=bin2hex(random_bytes(12)).'.'.$ext;
  $dest=__DIR__.'/../uploads/'.$name;
  $ok=false;
  // إعادة ترميز الصورة تزيل أي محتوى مخفي داخلها
  if(function_exists('imagecreatefromstring')){
    $img=@imagecreatefromstring((string)file_get_contents($f['tmp_name']));
    if($img){
      if($ext==='jpg') $ok=@imagejpeg($img,$dest,85);
      elseif($ext==='png'){ imagesavealpha($img,true); $ok=@imagepng($img,$dest,6); }
      elseif(function_exists('imagewebp')) $ok=@imagewebp($img,$dest,85);
      imagedestroy($img);
    }
  }
  if(!$ok) $ok=move_uploaded_file($f['tmp_name'],$dest);
  return $ok ? [$name,''] : [null,'تعذر حفظ الصورة. تأكد من صلاحيات الكتابة على مجلد uploads.'];
}
function delete_image($n){
  if($n && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/',(string)$n)) @unlink(__DIR__.'/../uploads/'.$n);
}
