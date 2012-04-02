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
  $_POST['customer_id'] = 4;
  var_dump(Input::get_post('customer_id', INPUT::NUMERIC, -1));
