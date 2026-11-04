<?php
// JWT (HS256) بدون مكتبات خارجية
function b64u($d){ return rtrim(strtr(base64_encode($d),'+/','-_'),'='); }
function b64u_dec($s){ return base64_decode(strtr($s,'-_','+/'),true); }

function jwt_secret(){
  global $pdo; static $s=null;
  if($s) return $s;
  $v=$pdo->query("SELECT svalue FROM settings WHERE skey='jwt_secret'")->fetchColumn();
  if(!$v){
    $pdo->prepare("INSERT IGNORE INTO settings (skey,svalue) VALUES ('jwt_secret',?)")->execute([bin2hex(random_bytes(32))]);
    $v=$pdo->query("SELECT svalue FROM settings WHERE skey='jwt_secret'")->fetchColumn();
  }
  return $s=(string)$v;
}
function jwt_encode(array $claims){
  $h=b64u(json_encode(['alg'=>'HS256','typ'=>'JWT']));
  $p=b64u(json_encode($claims));
  return $h.'.'.$p.'.'.b64u(hash_hmac('sha256',$h.'.'.$p,jwt_secret(),true));
}
function jwt_decode($t){
  if(!is_string($t) || substr_count($t,'.')!==2) return null;
  list($h,$p,$sig)=explode('.',$t);
  $hd=json_decode((string)b64u_dec($h),true);
  if(!is_array($hd) || ($hd['alg'] ?? '')!=='HS256') return null;
  $calc=b64u(hash_hmac('sha256',$h.'.'.$p,jwt_secret(),true));
  if(!hash_equals($calc,$sig)) return null;
  $c=json_decode((string)b64u_dec($p),true);
  if(!is_array($c) || ($c['iss'] ?? '')!=='kila' || (int)($c['exp'] ?? 0) < time()) return null;
  return $c;
}
function jwt_cookie($name,array $claims,$ttl){
  global $https;
  $now=time();
  $claims+=['iss'=>'kila','iat'=>$now,'exp'=>$now+$ttl,'jti'=>bin2hex(random_bytes(8))];
  setcookie($name,jwt_encode($claims),['expires'=>$now+$ttl,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);
}
function jwt_clear($name){
  global $https;
  setcookie($name,'',['expires'=>time()-3600,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);
}
function member_id_from_cookie(){
  $c=jwt_decode($_COOKIE['kila_member'] ?? '');
  return ($c && ($c['typ'] ?? '')==='member') ? (int)($c['sub'] ?? 0) : 0;
}
