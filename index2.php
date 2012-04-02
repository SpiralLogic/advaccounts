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
  include('bootstrap.php');
  var_dump(class_exists('\\Modules\\Jobsboard'));
  var_dump(is_callable('\\Modules\\Jobsboard::tasks'));
