<!-- end wrapper div-->
</div>
<? if ($footer): ?>
  <div id='footer'>
  <? if ($user): ?>
    <span class='power'><a target='_blank' href='<?= POWERED_URL ?>'><?= POWERED_BY ?></a></span>
    <span class='date'><?= $today ?> | <?= $now ?></span>
    <span> </span>| <span>mem/peak: <?=  $mem ?> </span><span>|</span><span> load time: <?= $load_time ?></span>
    <? endif; ?>
  <? endif; ?>
  <?= $debug ?>
  <!-- end content div-->
  </div>
  <?= $sidemenu ?>
<!-- end footer div-->
</div>
  <?= $messages ?>
<? if (!AJAX_REFERRER): ?>
</body>
<? endif; ?>
<script><?=$beforescripts?>
</script>  <?= $js ?>

<? if (!AJAX_REFERRER): ?>
</html>
<? endif; ?>
