<?php

	$page_security = 'SA_OPEN';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Session::i()->App->selected_application = 'messages';
	if (AJAX_REFERRER) {
		$data['post'] = $_POST;
		if (isset($_POST['user_id'])) {
			$data['result'] = Messages::set($_POST['user_id'], $_POST['subject'], $_POST['message']);
		}
		JS::renderJSON($data);
	}
	JS::footerFile("js/messages.js");
	Page::start(_($help_context = "Messages"), Input::request('frame'));
	HTML::div(array('style' => 'margin:0 auto;text-align:center'));
	Users::row(_("User:"), 'user_id');
	HTML::br(false)->label(array(
															'content' => "Subject: ", 'for' => 'subject'
												 ))->br->input('subject', array('size' => 50))->label;
	HTML::br(false)->label(array(
															'content' => "Message: ", 'for' => 'message'
												 ))->br->textarea('message', array(
																													'cols' => 35, 'rows' => 5, 'title' => 'Message to send:'
																										 ))->textarea->label->br;
	UI::button('btnSend', 'Send Message');
	HTML::_div();
	Page::end(true, true);
?>