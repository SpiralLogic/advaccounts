<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.core.db
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  /*
     User authentication page popped up after login timeout during ajax call.
   */
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  require(DOCROOT . '/access/login.php');
  if (get_post('SubmitUser') && User::i()->logged_in()) {
    // After successfull login repeat last ajax call.
    // Login form consists all post variables from last ajax call.
    echo "<script language=\"javascript\" type=\"text/javascript\">var o = opener;if (o) {o.JsHttpRequest.request(document.getElementsByName('SubmitUser')[0], o.document.forms[0]);close();}</script>";
  }

