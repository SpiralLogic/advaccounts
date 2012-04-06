<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 23/12/10
   * Time: 3:09 PM
   * To change this template use File | Settings | File Templates.
   */
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  if (!AJAX_REFERRER) {
    header("Location: /");
    exit();
  }
  if (Input::has_post('contact_id', 'message', 'type')) {
    $message_id = Contact_Log::add($_POST['contact_id'], $_POST['contact_name'], $_POST['type'], $_POST['message']);
  }
  if (Input::has_post('contact_id', 'type')) {
    $contact_log = Contact_Log::read($_POST['contact_id'], $_POST['type']);
    JS::renderJSON($contact_log);
  }
