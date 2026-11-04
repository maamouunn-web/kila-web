<?php
define('ADMIN_COOKIE','kila_admin');
function admin_can($p){
  global $PERMS;
  $list=$PERMS[$_SESSION['role'] ?? ''] ?? [];
  return in_array('*',$list,true) || in_array($p,$list,true);
}
function admin_issue($id,$tv){
  jwt_cookie(ADMIN_COOKIE,['typ'=>'admin','sub'=>(int)$id,'tv'=>(int)$tv],ADMIN_SESSION_HOURS*3600);
}
function admin_kick(){
  jwt_clear(ADMIN_COOKIE);
  $_SESSION=[];
  header('Location: login.php'); exit;
}
function require_login(){
  global $pdo;
  $c=jwt_decode($_COOKIE[ADMIN_COOKIE] ?? '');
  if(!$c || ($c['typ'] ?? '')!=='admin') admin_kick();
  $st=$pdo->prepare('SELECT id,username,role,active,token_version FROM admins WHERE id=?');
  $st->execute([(int)($c['sub'] ?? 0)]);
  $a=$st->fetch();
  if(!$a || !$a['active'] || (int)$a['token_version']!==(int)($c['tv'] ?? -1)) admin_kick();
  $_SESSION['admin_id']=(int)$a['id']; $_SESSION['admin_name']=$a['username']; $_SESSION['role']=$a['role'];
  // تجديد تلقائي: كلما مرّ نصف مدة الجلسة يُصدر رمز جديد، فلا تنتهي الجلسة أثناء العمل
  if(((int)$c['exp']-time()) < ADMIN_SESSION_HOURS*1800) admin_issue($a['id'],$a['token_version']);
}
function require_perm($p){
  require_login();
  if(!admin_can($p)){ http_response_code(403); die('ليس لديك صلاحية لهذه الصفحة.'); }
}
