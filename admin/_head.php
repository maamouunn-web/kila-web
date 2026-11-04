<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?=e($title ?? 'الإدارة')?> - <?=e(SITE_NAME)?></title><link rel="stylesheet" href="../style.css"></head><body>
<div class="wrap wide">
<div class="card nav"><b><?=e(SITE_NAME)?></b>
<?php if(admin_can('stats.view')): ?><a href="index.php">الإحصائيات</a><?php endif; ?>
<?php if(admin_can('members.view')): ?><a href="members.php">المسجلون</a><?php endif; ?>
<?php if(admin_can('members.full')): ?><a href="directory.php">كل البيانات</a><?php endif; ?>
<?php if(admin_can('members.add')): ?><a href="member_form.php">إضافة شخص</a><?php endif; ?>
<?php if(admin_can('news.manage')): ?><a href="news.php">الأخبار</a><?php endif; ?>
<?php if(admin_can('users.manage')): ?><a href="users.php">المسؤولون</a><?php endif; ?>
<a href="password.php">كلمة المرور</a>
<a href="logout.php">خروج (<?=e($_SESSION['admin_name'] ?? '')?>)</a>
</div>
