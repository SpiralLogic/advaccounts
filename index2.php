<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  include('bootstrap.php');
  if (!isset($_SESSION['xmlorders'])) {
    $orders = new \Modules\Volusion\Orders();
    $_SESSION['xmlorders'] = $orders->XML;
  }
  if (!isset($_SESSION['xmlorders2'])) {
    $orders = new \Modules\Volusion\Orders();
    $_SESSION['xmlorders2'] = $orders->XML;
  }
  echo '<pre>';
  //var_dump($_SESSION['xmlorders']);
  //var_dump($_SESSION['xmlorders2']);
  $simplexml = new SimpleXMLElement($_SESSION['xmlorders']);
  $simplexml2 = new SimpleXMLElement($_SESSION['xmlorders2']);
  //var_dump($simplexml);
  //var_dump($simplexml2);
  function objectTOarray($object) {
    $object = (array) $object;
    /*if (isset($object['OrderID'])) {
    $object = [$object];
  }
  */
    foreach ($object as $k => &$v) {
      if (is_array($v) || is_object($v)) {
        $v = (array) objectTOarray($v);
      }
    }
    if (count($object) == 0) {
      $v = '';
    }
    if (is_array($object)) {
      $object2 = $object;
      $object = array();
      foreach ($object2 as $k2 => $v2) {
        if (is_array($v2)) {
          if (!is_array(reset($v2))) {
            $object[reset($v2)] = $v2;
          }
          else {
            $object[$k2] = $v2;
          }
        }
        else {
          $object[$k2] = $v2;
        }
      }
    }
    return $object;
  }

  var_dump(objectTOarray($simplexml));
  var_dump(objectTOarray($simplexml2));
