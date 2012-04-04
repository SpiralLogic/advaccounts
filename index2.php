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
  $_POST['customer'] = 'test22';
  $_POST['customer_id'] = '8480';
  var_dump(Input::get('customer_id', Input::NUMERIC));
