<div class="btn-group <?php  echo $auto; ?>">
  <?php if (isset($menus) && (is_array($menus) || $menus instanceof \Traversable)): foreach ($menus as $_50766e3c43810_name => $_50766e3c43810_val): ?>
  <?php if (count($_50766e3c43810_val["items"]) == 1): ?>
    <?php if (isset($_50766e3c43810_val["items"]) && (is_array(
      $_50766e3c43810_val["items"]
    ) || $_50766e3c43810_val["items"] instanceof \Traversable)
    ): foreach ($_50766e3c43810_val["items"] as $_50766e3c4391a_name => $_50766e3c4391a_val): ?><a class="btn btn-mini btn-primary <?php  echo $_50766e3c4391a_val["class"]; ?>" href="<?php  echo $_50766e3c4391a_val["href"] ? :
      '#'; ?>" <?php if (isset($_50766e3c4391a_val["attr"]) && (is_array(
      $_50766e3c4391a_val["attr"]
    ) || $_50766e3c4391a_val["attr"] instanceof \Traversable)
    ): foreach ($_50766e3c4391a_val["attr"] as $_50766e3c43982_name => $_50766e3c43982_val): ?> <?php echo $_50766e3c43982_name; ?>="<?php echo $_50766e3c43982_val; ?>
            "<?php endforeach; endif; ?> <?php if (isset($_50766e3c4391a_val["data"]) && (is_array(
      $_50766e3c4391a_val["data"]
    ) || $_50766e3c4391a_val["data"] instanceof \Traversable)
    ): foreach ($_50766e3c4391a_val["data"] as $_50766e3c43a16_name => $_50766e3c43a16_val): ?> data-<?php echo $_50766e3c43a16_name; ?>="<?php echo $_50766e3c43a16_val; ?>
            "<?php endforeach; endif; ?>
            ><?php  echo $_50766e3c4391a_val["label"]; ?></a><?php endforeach; endif; ?>
    <?php else: ?>
    <?php if ($_50766e3c43810_val["split"]): ?>
            <button class="btn btn-mini btn-primary btn-split"><?php  echo $_50766e3c43810_val["title"]; ?></button>
            <button class='btn btn-mini btn-primary dropdown-toggle <?php  echo $_50766e3c43810_val["auto"]; ?>' data-toggle="dropdown"><span class="icon-caret-down"></span>
            </button>
      <?php else: ?>
            <button class="btn btn-mini btn-primary dropdown-toggle <?php  echo $_50766e3c43810_val["auto"]; ?>" data-toggle="dropdown"><?php  echo $_50766e3c43810_val["title"]; ?>
                &nbsp;<span class="icon-caret-down"></span></button>
      <?php endif; ?>
        <ul class="dropdown-menu">
          <?php if (isset($_50766e3c43810_val["items"]) && (is_array($_50766e3c43810_val["items"]) || $_50766e3c43810_val["items"] instanceof \Traversable)):
          foreach ($_50766e3c43810_val["items"] as $_50766e3c43ac3_name => $_50766e3c43ac3_val): ?>
            <?php if ($_50766e3c43ac3_val["divider"]): ?>
                  <li class="divider"></li>
              <?php else: ?>
                  <li><a class="<?php  echo $_50766e3c43ac3_val["class"];
                    ?>" href="<?php  echo $_50766e3c43ac3_val["href"] ? : '#';
                    ?>"
                    <?php if (isset($_50766e3c43ac3_val["attr"]) && (is_array(
                      $_50766e3c43ac3_val["attr"]
                    ) || $_50766e3c43ac3_val["attr"] instanceof \Traversable)
                    ): foreach ($_50766e3c43ac3_val["attr"] as $_50766e3c43b2a_name => $_50766e3c43b2a_val): ?>
                      <?php echo $_50766e3c43b2a_name; ?>="
                      <?php echo $_50766e3c43b2a_val; ?>"<?php
                    endforeach; endif; ?> <?php
                    if (isset($_50766e3c43ac3_val["data"]) && (is_array($_50766e3c43ac3_val["data"]) || $_50766e3c43ac3_val["data"] instanceof \Traversable)):
                      foreach ($_50766e3c43ac3_val["data"] as $_50766e3c43b95_name => $_50766e3c43b95_val):
                        ?> data-<?php  echo $_50766e3c43b95_name;
                        ?>="<?php echo $_50766e3c43b95_val; ?>"<?php
                      endforeach;
                    endif;
                    ?>><?php  echo $_50766e3c43ac3_val["label"];
                      ?></a></li>
              <?php endif; ?>
            <?php endforeach;
        endif; ?> </ul>
    <?php endif; ?>
  <?php endforeach;
endif; ?>
</div>
