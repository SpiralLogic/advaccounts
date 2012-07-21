<?php
$test= new \ADV\Core\Input2\Input($_GET);


echo $test['test'];
 echo $test->get('test',null,'wawa');
  echo $test['test'];
  echo $test->get('test',null,'wawa');
var_dump($test['wawa']);
  $test['wawa']='eee';
var_dump($test['wawa']);
