<?php




 include('includes/session.inc');
 ini_set('display_errors', 'On');
 error_reporting(E_ALL);
 echo "<pre>";
 $result = Price::getPrices(26382,Price::PURCHASE,Price::SORT_C);
 print_r($result);
