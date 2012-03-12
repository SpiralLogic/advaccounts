<?php
  /**
   * wawaw
   * PHP version 5
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2011
   * @license   GPL <http://www.gnu.org/licenses/gpl-3.0.html>
   * @link      http://www.advancedgroup.com.au
   **/

  file_exists('config/config.php') ? : header("Location: /install/index.php");
  require_once "bootstrap.php";
  Session::i()->App->display();
