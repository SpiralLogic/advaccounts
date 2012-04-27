<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

  if (!AJAX_REFERRER) {
    header("Location: /");
    exit();
  }
  $content = FALSE;
  if (Input::has_post('type', 'id')) {
    if ($_POST['type'] == CT_CUSTOMER) {
      $content = Debtor::getEmailDialogue($_POST['id']);
    }
    elseif ($_POST['type'] == CT_SUPPLIER) {
      $content = Creditor::getEmailDialogue($_POST['id']);
    }
    if ($content == FALSE) {
      HTML::h3(NULL, 'No email addresses available.', array('class' => 'center bold top40 font15'), FALSE);
    }
    else {
      echo $content;
    }
  }

  JS::render();
