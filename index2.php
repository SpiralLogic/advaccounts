<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   *
   * @param $name
   * @param $constants
   **/
  include 'bootstrap.php';
  $customers = new \Modules\Volusion\Customers();
  $customers->process();
echo "<pre >";
  var_dump($customers->status);
