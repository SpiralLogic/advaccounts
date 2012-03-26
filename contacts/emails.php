<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 5/08/11
   * Time: 5:53 AM

   */
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  if (AJAX_REFERRER) {
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
  }
  JS::render();
