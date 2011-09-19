<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 23/12/10
 * Time: 3:09 PM
 * To change this template use File | Settings | File Templates.
 */
include_once($_SERVER['DOCUMENT_ROOT']. "/includes/session.inc");
if (!AJAX_REFERRER) die();
if (isset($_POST['contact_id']) && isset($_POST['message']) && isset($_POST['type'])) {
	$message_id = ContactLog::add($_POST['contact_id'], $_POST['contact_name'], $_POST['type'], $_POST['message']);

}
;
if (isset($_POST['contact_id']) && isset($_POST['type'])) {
	$contact_log = ContactLog::read($_POST['contact_id'], $_POST['type']);
	echo json_encode($contact_log);
}
