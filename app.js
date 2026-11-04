function toggleLoc(){
  var lt=document.getElementById('lt'); if(!lt) return;
  var v=lt.value;
  document.getElementById('inBox').classList.toggle('hide',v!=='inside');
  document.getElementById('outBox').classList.toggle('hide',v!=='outside');
}
function fillLoc(selected){
  var s=document.getElementById('state'), l=document.getElementById('locality');
  if(!s||!l) return;
  l.innerHTML='<option value="">اختر المحلية</option>';
  (window.STATES[s.value]||[]).forEach(function(x){
    var o=document.createElement('option'); o.textContent=x; if(x===selected) o.selected=true; l.appendChild(o);
  });
}
function checkForm(){
  var p=document.getElementById('phone').value.replace(/[\s\-().]/g,'');
  if(p.indexOf('00')===0) p='+'+p.slice(2);
  if(!/^\+[0-9]{8,15}$/.test(p)){ alert('رقم الهاتف غير صحيح. اكتبه مع مفتاح الدولة ويبدأ بعلامة +'); return false; }
  return true;
}
document.addEventListener('DOMContentLoaded',function(){ toggleLoc(); fillLoc(window.INIT_LOC||''); });
