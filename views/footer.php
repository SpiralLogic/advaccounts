<!-- end wrapper div-->
</div>
<? if ($footer): ?>
<div id='footer'>
  <? if ($user): ?>
  <span class='power'><a target='_blank' href='<?= POWERED_URL ?>'><?= POWERED_BY ?></a></span>
  <span class='date'><?= $today ?> | <?= $now ?></span>
  <span> </span>| <span>mem/peak: <?=  $mem ?> </span><span>|</span><span> load time: <?= $load_time ?></span>
  <? endif; ?>
  <!-- end footer div-->
</div>
<? endif; ?>
<!-- end content div-->
</div>
  <?= $sidemenu ?>
  <?= $messages ?>
<? if (!AJAX_REFERRER): ?>

<? endif; ?>
<script><?=$beforescripts?>
</script>  <?= $js ?>

<? if (!AJAX_REFERRER): ?>
</body>
</html>
<? endif; ?>
