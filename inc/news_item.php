<?php /* يتوقع المتغير $n */ global $CATS; ?>
<div class="newsitem">
<?php if(!empty($n['image'])): ?><a href="news_view.php?id=<?=(int)$n['id']?>"><img class="newsimg" loading="lazy" alt="" src="uploads/<?=e($n['image'])?>"></a><?php endif; ?>
<div>
<span class="tag"><?=e($CATS[$n['category']] ?? 'خبر')?></span> <small><?=e(substr((string)$n['created_at'],0,10))?></small>
<h3><a href="news_view.php?id=<?=(int)$n['id']?>"><?=e($n['title'])?></a></h3>
<p><?=e(mb_substr(preg_replace('/\s+/u',' ',(string)$n['body']),0,180))?>…</p>
</div></div>
