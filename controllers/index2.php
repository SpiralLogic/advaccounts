<div class="btn-group  <?php echo $auto; ?>">
  <?php if (isset($menus) && is_array($menus)): foreach ($menus as $_502b40e236e92_name => $_502b40e236e92_val): ?>
  <?php if ($_502b40e236e92_val["split"]): ?>
    <button class="btn btn-mini btn-primary btn-split"><?php echo $_502b40e236e92_val["title"]; ?> </button><button class=\'btn btn-mini btn-primary dropdown-toggle <?php echo $_502b40e236e92_val["auto"]; ?>\' data-toggle="dropdown"><span class="caret"></span></button>
<?php else: ?>
    <button class="btn btn-mini btn-primary dropdown-toggle <?php echo $_502b40e236e92_val["auto"]; ?>" data-toggle="dropdown"><?php echo $_502b40e236e92_val["title"]; ?><span class="caret"></span></button>
    <?php endif; ?>
  <ul class="dropdown-menu">
    <?php if (isset($_502b40e236e92_val["items"]) && is_array($_502b40e236e92_val["items"])): foreach ($_502b40e236e92_val["items"] as $_502b40e236f77_name => $_502b40e236f77_val): ?>
    <?php if ($_502b40e236f77_val["divider"]): ?>
      <li class="divider"></li>
      <?php else: ?>
      <li>
        <a class="<?php echo $_502b40e236f77_val["class"]; ?>"
           href="<?php echo $_502b40e236f77_val["href"] ? : '#'; ?>"
          <?php if (isset($_502b40e236f77_val["data"]) && is_array($_502b40e236f77_val["data"])):
          foreach ($_502b40e236f77_val["data"] as $_502b40e236ffc_name => $_502b40e236ffc_val): ?>
           data-<?php echo $_502b40e236e92_name; ?>="<?php echo $_502b40e236e92_val; ?>"<?php endforeach; endif; ?>><?php echo $_502b40e236f77_val["label"]; ?></a></li>
      <?php endif; ?>
    <?php endforeach; endif; ?>
  </ul>

  <?php endforeach; endif; ?>
</div>
