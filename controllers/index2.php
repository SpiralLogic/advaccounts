<?php


$test = \ADV\Core\Session::_getFlash('test');
  var_dump($test);
\ADV\Core\Session::_setFlash('test','testing');
  var_dump($test);
