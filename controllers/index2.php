<?php
  /*  if (!User::i()->can_access(SA_DEBUGGING)) {
    throw new Adv_Exception("Administrator access only");
  }*/
//todo remove
  $ch = curl_init('http://advanced.advancedgroup.com.au:8090/rest/user/login');
  curl_setopt($ch, CURLOPT_POST, 2);
  curl_setopt($ch, CURLOPT_POSTFIELDS, 'login=advadmin&password=1Willenberg!');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  preg_match_all('/^Set-Cookie: (.*?);/m', curl_exec($ch), $m);
  echo '<pre>';
  $cookies = [];
  foreach ($m[1] as $n) {
    $cookie = explode('=', $n);
    setcookie($cookie[0],$cookie[1],time()+Config::get('session_lifetime'),'/','dev.advanced.advancedgroup.com.au');
  }

  var_dump($cookies);
  phpinfo();
