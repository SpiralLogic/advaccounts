<?php

  ini_set('display_errors',1);
   //include 'phar:///usr/share/php/firephp.phar/FirePHP/Init.php';
$phar = new Phar('firephp.phar');
   echo $phar->count();
$phar->extractTo('/var/www/advaccounts/web/tmp/');