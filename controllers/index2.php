<?php
  use ADV\App\DB\Collection;
  use ADV\App\Debtor\Debtor;
  use ADV\App\Contact\Contact;

  ini_set('xdebug.var_display_max_depth', 6);
  $test = new Debtor(5618);
  var_dump($test);

