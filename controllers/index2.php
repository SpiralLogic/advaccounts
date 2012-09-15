<?php
  use ADV\Core\Dates;

  $start = microtime(true);
  for ($i = 0; $i < 100000; $i++) {
    $num = DB::i();
  }
  var_dump(Dates::getReadableTime(microtime(true) - $start));
