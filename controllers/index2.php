<?php
  $_POST = json_decode(json_encode(new Creditor(2762)));echo '<pre >';
$_POST = [$_POST];
  array_walk_recursive($_POST, function( &$v,$k) { $v= (array) $v; });
  $test = new Creditor();
  $test->save($_POST);
