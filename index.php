<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  file_exists('config/config.php') ? : header("Location: /install/index.php");
  require_once "bootstrap.php";
  if ($_GET['q'] && file_exists(DOCROOT . 'app' . DS . $_GET['q'])) {
    include(DOCROOT . 'app' . DS . $_GET['q']);
  }
  else {
    Session::i()->App->display();
  }
