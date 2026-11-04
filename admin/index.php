<?php
require '../inc/functions.php'; require '../inc/auth.php';
require_perm('stats.view');
$one=function($sql) use ($pdo){ return (int)$pdo->query($sql)->fetchColumn(); };
$total=$one('SELECT COUNT(*) FROM members');
$in=$one("SELECT COUNT(*) FROM members WHERE location_type='inside'");
$male=$one("SELECT COUNT(*) FROM members WHERE gender='ذكر'");
$w7=$one('SELECT COUNT(*) FROM members WHERE created_at >= (NOW() - INTERVAL 7 DAY)');
$ini=$one('SELECT COUNT(*) FROM members WHERE join_initiatives=1');
$grp=$one('SELECT COUNT(*) FROM members WHERE join_groups=1');
$byCountry=$pdo->query('SELECT country,COUNT(*) c FROM members GROUP BY country ORDER BY c DESC LIMIT 30')->fetchAll();
$byState=$pdo->query('SELECT state,COUNT(*) c FROM members WHERE state IS NOT NULL GROUP BY state ORDER BY c DESC')->fetchAll();
$byCity=$pdo->query("SELECT CONCAT(city,' - ',country) lbl,COUNT(*) c FROM members GROUP BY city,country ORDER BY c DESC LIMIT 15")->fetchAll();
$title='الإحصائيات'; include '_head.php';
?>
<div class="card"><div class="stats">
<div class="stat"><b><?=$total?></b>إجمالي المسجلين</div>
<div class="stat"><b><?=$in?></b>داخل السودان</div>
<div class="stat"><b><?=$total-$in?></b>خارج السودان</div>
<div class="stat"><b><?=$male?></b>رجال</div>
<div class="stat"><b><?=$total-$male?></b>نساء</div>
<div class="stat"><b><?=$w7?></b>آخر 7 أيام</div>
<div class="stat"><b><?=$ini?></b>مهتمون بالمبادرات</div>
<div class="stat"><b><?=$grp?></b>مجموعات التواصل</div>
</div></div>
<div class="card"><h2>حسب الدولة</h2><?php bars($byCountry,'country'); ?></div>
<div class="card"><h2>حسب الولاية</h2><?php bars($byState,'state'); ?></div>
<div class="card"><h2>أكثر المدن</h2><?php bars($byCity,'lbl'); ?></div>
<?php include '_foot.php'; ?>
