<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/data.php';

date_default_timezone_set(TIMEZONE);
if(!defined('ADMIN_SESSION_HOURS')) define('ADMIN_SESSION_HOURS',8);
if(!defined('MEMBER_SESSION_HOURS')) define('MEMBER_SESSION_HOURS',2);
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (session_status() === PHP_SESSION_NONE) {
  ini_set('session.gc_maxlifetime','28800');
  session_set_cookie_params(['lifetime'=>28800,'httponly'=>true,'samesite'=>'Lax','secure'=>$https]);
  session_name('kila_sid');
  session_start();
}
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");

try {
  $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $ex) {
  http_response_code(500);
  die('تعذر الاتصال بقاعدة البيانات. راجع إعدادات الاتصال في ملف config.php');
}

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf(){ if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function post($k){ $v=$_POST[$k] ?? ''; return is_string($v) ? $v : ''; }
function get($k){ $v=$_GET[$k] ?? ''; return is_string($v) ? $v : ''; }
function check_csrf(){ $t=$_POST['csrf'] ?? ''; if(!is_string($t) || !hash_equals((string)($_SESSION['csrf'] ?? ''), $t)){ http_response_code(400); die('طلب غير صالح، أعد تحميل الصفحة وحاول مجدداً.'); } }
function client_ip(){ return substr($_SERVER['REMOTE_ADDR'] ?? '0', 0, 45); }
function too_many_attempts(){
  global $pdo;
  $st=$pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip=? AND created_at > (NOW() - INTERVAL 15 MINUTE)');
  $st->execute([client_ip()]);
  return (int)$st->fetchColumn() >= 5;
}
function log_fail(){
  global $pdo;
  $pdo->prepare('INSERT INTO login_attempts (ip) VALUES (?)')->execute([client_ip()]);
  $pdo->exec('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)');
}
function normalize_phone($p){
  $p=preg_replace('/[\s\-\(\)\.]/','',(string)$p);
  if(strpos($p,'00')===0) $p='+'.substr($p,2);
  return $p;
}
function new_code(){ return strtoupper(substr(bin2hex(random_bytes(4)),0,8)); }

function normalize_name($n){
  $n=preg_replace('/[\x{064B}-\x{065F}\x{0640}\x{0670}]/u','',(string)$n);
  $n=str_replace(['أ','إ','آ','ٱ','ى','ة'],['ا','ا','ا','ا','ي','ه'],$n);
  $n=preg_replace('/\s+/u',' ',trim($n));
  return mb_strtolower($n,'UTF-8');
}
function validate_member(array $in, bool $needConsent, int $excludeId=0, bool $allowSame=false): array {
  global $pdo,$STATES,$COUNTRIES,$EDU;
  $f=function($k) use ($in){ return trim((string)($in[$k] ?? '')); };
  $type=$f('location_type');
  $name=preg_replace('/\s+/u',' ',$f('full_name'));
  $phone=normalize_phone($f('phone')); $phone2=normalize_phone($f('phone2'));
  $state=$f('state'); $loc=$f('locality'); $age=$f('age');
  $re='/^\+[0-9]{8,15}$/';
  $err='';
  if(count(explode(' ',$name))<3 || mb_strlen($name)<8) $err='يرجى كتابة الاسم الرباعي كاملاً.';
  elseif(!in_array($f('gender'),['ذكر','أنثى'],true)) $err='اختر الجنس.';
  elseif($age!=='' && (!ctype_digit($age) || (int)$age<1 || (int)$age>120)) $err='العمر غير صحيح.';
  elseif(!preg_match($re,$phone)) $err='رقم الهاتف غير صحيح. اكتبه مع مفتاح الدولة ويبدأ بعلامة + مثل +249912345678';
  elseif($phone2!=='' && !preg_match($re,$phone2)) $err='رقم الهاتف الإضافي غير صحيح.';
  elseif($f('education')!=='' && !in_array($f('education'),$EDU,true)) $err='المستوى التعليمي غير صحيح.';
  elseif(!in_array($type,['inside','outside'],true)) $err='حدد مكان الإقامة.';
  elseif($needConsent && empty($in['consent'])) $err='يجب الموافقة على استخدام البيانات.';
  elseif($type==='inside' && (!isset($STATES[$state]) || !in_array($loc,$STATES[$state],true))) $err='اختر الولاية والمحلية من القائمة.';
  elseif($type==='outside' && !in_array($f('country'),$COUNTRIES,true)) $err='اختر الدولة من القائمة.';
  else {
    if($type==='inside'){ $country='السودان'; $city=$f('city_in'); }
    else { $country=$f('country'); $state=''; $loc=''; $city=$f('city_out'); }
    if($city==='') $err='اكتب اسم المدينة أو المنطقة.';
    elseif($phone2!=='' && $phone2===$phone) $err='الرقم الإضافي مطابق للرقم الأساسي.';
    else {
      $nums=[$phone]; if($phone2!=='') $nums[]=$phone2;
      $ph=implode(',',array_fill(0,count($nums),'?'));
      $st=$pdo->prepare("SELECT id FROM members WHERE (phone IN ($ph) OR phone2 IN ($ph)) AND id<>? LIMIT 1");
      $st->execute(array_merge($nums,$nums,[$excludeId]));
      if($st->fetch()) $err='أحد أرقام الهاتف مسجل مسبقاً. إن كان رقمك فادخل صفحة تحديث البيانات.';
      elseif(!$allowSame){
        $st=$pdo->prepare('SELECT id FROM members WHERE name_key=? AND id<>? LIMIT 1');
        $st->execute([normalize_name($name),$excludeId]);
        if($st->fetch()) $err='يوجد سجل بنفس الاسم. إن كان سجلك فاستخدم صفحة تحديث البيانات، وإن كنت شخصاً آخر فتواصل مع الإدارة.';
      }
    }
  }
  if($err!=='') return [null,$err];
  $data=[
    'full_name'=>mb_substr($name,0,150),
    'name_key'=>mb_substr(normalize_name($name),0,190),
    'nickname'=>mb_substr($f('nickname'),0,80) ?: null,
    'gender'=>$f('gender'),
    'age'=>$age!=='' ? (int)$age : null,
    'phone'=>$phone,
    'phone2'=>$phone2 ?: null,
    'job'=>mb_substr($f('job'),0,100) ?: null,
    'education'=>$f('education') ?: null,
    'location_type'=>$type,
    'country'=>$country,
    'state'=>$state ?: null,
    'locality'=>$loc ?: null,
    'city'=>mb_substr($city,0,80),
    'area'=>mb_substr($f('area'),0,120) ?: null,
    'join_initiatives'=>!empty($in['join_initiatives']) ? 1 : 0,
    'join_groups'=>!empty($in['join_groups']) ? 1 : 0,
  ];
  return [$data,''];
}

function create_member(array $d): array {
  global $pdo;
  $code=new_code();
  $cols=array_keys($d);
  $sql='INSERT INTO members ('.implode(',',$cols).',consent,edit_code_hash) VALUES ('.implode(',',array_fill(0,count($cols),'?')).',1,?)';
  $vals=array_values($d); $vals[]=password_hash($code,PASSWORD_DEFAULT);
  $pdo->prepare($sql)->execute($vals);
  $id=(int)$pdo->lastInsertId();
  $reg='KILA-'.date('Y').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT);
  $pdo->prepare('UPDATE members SET reg_no=? WHERE id=?')->execute([$reg,$id]);
  return [$reg,$code];
}
function update_member(int $id, array $d): void {
  global $pdo;
  $sets=implode(',',array_map(function($c){ return $c.'=?'; },array_keys($d)));
  $vals=array_values($d); $vals[]=$id;
  $pdo->prepare('UPDATE members SET '.$sets.',updated_at=NOW() WHERE id=?')->execute($vals);
}
function member_to_form(array $m): array {
  $m['city_in']  = $m['location_type']==='inside'  ? $m['city'] : '';
  $m['city_out'] = $m['location_type']==='outside' ? $m['city'] : '';
  return $m;
}

function member_fields(array $v=[], string $base=''): void {
  global $STATES,$COUNTRIES,$EDU;
  $opt=function($list,$k) use ($v){
    $h='';
    foreach($list as $x) $h.='<option'.((($v[$k] ?? '')===$x)?' selected':'').'>'.e($x).'</option>';
    return $h;
  };
  $val=function($k) use ($v){ return e($v[$k] ?? ''); };
  $chk=function($k) use ($v){ return !empty($v[$k]) ? ' checked' : ''; };
  $t=$v['location_type'] ?? '';
  ?>
<h2>البيانات الشخصية</h2>
<label>الاسم الرباعي *</label><input name="full_name" required value="<?=$val('full_name')?>">
<label>اسم الشهرة</label><input name="nickname" value="<?=$val('nickname')?>">
<label>الجنس *</label><select name="gender" required><option value="">اختر</option><?=$opt(['ذكر','أنثى'],'gender')?></select>
<label>العمر</label><input type="number" name="age" min="1" max="120" value="<?=$val('age')?>">
<label>رقم الهاتف مع مفتاح الدولة *</label><input name="phone" id="phone" dir="ltr" placeholder="+249912345678" required value="<?=$val('phone')?>">
<label>رقم هاتف إضافي</label><input name="phone2" dir="ltr" value="<?=$val('phone2')?>">
<label>المهنة</label><input name="job" value="<?=$val('job')?>">
<label>المستوى التعليمي</label><select name="education"><option value="">اختر</option><?=$opt($EDU,'education')?></select>

<h2>مكان الإقامة</h2>
<label>أين تقيم حالياً؟ *</label>
<select name="location_type" id="lt" onchange="toggleLoc()" required>
<option value="">اختر</option>
<option value="inside"<?=$t==='inside'?' selected':''?>>داخل السودان</option>
<option value="outside"<?=$t==='outside'?' selected':''?>>خارج السودان</option></select>
<div id="inBox" class="hide">
<label>الولاية *</label><select name="state" id="state" onchange="fillLoc('')"><option value="">اختر الولاية</option><?=$opt(array_keys($STATES),'state')?></select>
<label>المحلية *</label><select name="locality" id="locality"><option value="">اختر الولاية أولاً</option></select>
<label>المدينة أو المنطقة *</label><input name="city_in" value="<?=$val('city_in')?>">
</div>
<div id="outBox" class="hide">
<label>الدولة *</label><select name="country"><option value="">اختر الدولة</option><?=$opt($COUNTRIES,'country')?></select>
<label>المدينة *</label><input name="city_out" value="<?=$val('city_out')?>">
</div>
<label>الحي أو القرية / مكان الإقامة</label><input name="area" value="<?=$val('area')?>">

<h2>بيانات إضافية</h2>
<label><input type="checkbox" name="join_initiatives" value="1"<?=$chk('join_initiatives')?>>أرغب في المشاركة في المبادرات المجتمعية</label>
<label><input type="checkbox" name="join_groups" value="1"<?=$chk('join_groups')?>>أرغب في الانضمام إلى مجموعات التواصل</label>
<script>window.STATES=<?=json_encode($STATES,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP)?>;window.INIT_LOC=<?=json_encode($v['locality'] ?? '',JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP)?>;</script>
<script src="<?=e($base)?>app.js"></script>
  <?php
}

function bars(array $rows, string $k): void {
  $cnt=array_map('intval',array_column($rows,'c'));
  $max=$cnt ? max($cnt) : 1; if($max<1) $max=1;
  foreach($rows as $r){
    $w=round(((int)$r['c'])/$max*100);
    echo '<div class="bar"><span>'.e($r[$k]).'</span><i style="width:'.$w.'%"></i><b>'.(int)$r['c'].'</b></div>';
  }
}

function member_filters(array $src): array {
  $g=function($k) use ($src){ $v=$src[$k] ?? ''; return is_string($v) ? trim($v) : ''; };
  $F=['q'=>$g('q'),'country'=>$g('country'),'state'=>$g('state'),'city'=>$g('city'),'type'=>$g('type')];
  $where=[]; $args=[];
  if($F['q']!==''){ $where[]='(full_name LIKE ? OR nickname LIKE ? OR phone LIKE ? OR phone2 LIKE ? OR reg_no LIKE ?)'; for($i=0;$i<5;$i++) $args[]='%'.$F['q'].'%'; }
  if($F['country']!==''){ $where[]='country=?'; $args[]=$F['country']; }
  if($F['state']!==''){ $where[]='state=?'; $args[]=$F['state']; }
  if($F['city']!==''){ $where[]='city LIKE ?'; $args[]='%'.$F['city'].'%'; }
  if(in_array($F['type'],['inside','outside'],true)){ $where[]='location_type=?'; $args[]=$F['type']; }
  return [$where ? ' WHERE '.implode(' AND ',$where) : '', $args, $F];
}

require_once __DIR__.'/jwt.php';
require_once __DIR__.'/upload.php';
require_once __DIR__.'/xlsx.php';
require_once __DIR__.'/schema.php';
