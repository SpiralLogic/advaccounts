<?php


  $config = Config::_get('db.default');
  $start  = microtime(true);
  for ($i = 0; $i < 40; $i++) {
    try {
      $db     = new \ADV\Core\DB\DB($config);
      $result = $db->query('SHOW STATUS LIKE "%onn%"')->fetch();
    } catch (Exception $e) {
      var_dump($e);
      $i = 60;
    }
    $db = null;
  }
  var_dump(microtime(true) - $start);
