<?php
//$groupid = Bank_Undeposited::createGroup(7,'01/08/2012');
  Bank_Undeposited::addToGroup(10572, 7, 10960);
  $results = Bank_Trans::getGroupDeposit(7, 10960);
  foreach ($results as $result) {
    var_dump($result);
  }
