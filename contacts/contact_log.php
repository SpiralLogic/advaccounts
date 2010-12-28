<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 23/12/10
 * Time: 3:09 PM
 * To change this template use File | Settings | File Templates.
 */
$path_to_root = "..";
include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");
include_once($_SERVER['DOCUMENT_ROOT'] . "/contacts/includes/classes/contact_log.inc");
if (!isAjaxReferrer()) die();
if (isset($_POST['contact_id']) && isset($_POST['message']) && isset($_POST['type'])) {
    $message_id = contact_log::add($_POST['contact_id'], $_POST['contact_name'], $_POST['type'], $_POST['message']);

};
if (isset($_POST['contact_id']) && isset($_POST['type'])) {
    $contact_log = contact_log::read($_POST['contact_id'], $_POST['type']);
    echo json_encode($contact_log);
}
