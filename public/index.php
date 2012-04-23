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
  require_once "../bootstrap.php";
  if ($_SESSION['current_user']->username != 'admin' && strpos($_SERVER['SERVER_NAME'], 'dev')) {
    throw new ErrorException("Dev no working.");
  }
  elseif ($_SERVER['DOCUMENT_URI'] == '/assets.php') {
    new Assets();
  }
  elseif (isset($_SERVER['DOCUMENT_URI']) && file_exists(DOCROOT . 'controllers' . DS . ltrim($_SERVER['DOCUMENT_URI'], '/'))) {
    include(DOCROOT . 'controllers' . DS . ltrim($_SERVER['DOCUMENT_URI'], '/'));
  }
  elseif ($_SERVER['DOCUMENT_URI'] != $_SERVER['SCRIPT_NAME']) {
    header('HTTP/1.0 404 Not Found');
    Event::error('Error 404 Not Found:' . $_SERVER['DOCUMENT_URI']);
  }
  else {
    Session::i()->App->display();
  }
