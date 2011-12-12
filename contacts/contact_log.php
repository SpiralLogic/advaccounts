<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 23/12/10
	 * Time: 3:09 PM
	 * To change this template use File | Settings | File Templates.
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	if (!AJAX_REFERRER) {
		die();
	}
	if (Input::has_post('contact_id', 'message', 'type')) {
		$message_id = Contacts_Log::add($_POST['contact_id'], $_POST['contact_name'], $_POST['type'], $_POST['message']);
	}
	if (Input::has_post('contact_id', 'type')) {
		$contact_log = Contacts_Log::read($_POST['contact_id'], $_POST['type']);
		echo JS::renderJSON($contact_log);
	}
