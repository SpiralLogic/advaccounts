<?php

	$page_security = 'SA_OPEN';

	include_once("includes/messages.inc");
	if (AJAX_REFERRER) {
		$data['post'] = $_POST;
		if (isset($_POST['user_id'])) {
			$data['result'] = Messages::setNewMessage($_POST['user_id'], $_POST['subject'], $_POST['message']);
		}
		echo json_encode($data);
		exit();
	}
	JS::footerFile("includes/js/messages.js");
	page(_($help_context = "Messages"), Input::request('popup'));
	HTML::div(array('style' => 'margin:0 auto;text-align:center'));
	user_list_row(_("User:"), 'user_id');
	HTML::br(false)->label(array('content' => "Subject: ", 'for' => 'subject'))->br->input('subject', array('size' => 50))->label;
	HTML::br(false)->label(array('content' => "Message: ", 'for' => 'message'))->br->textarea('message', array('cols' => 35, 'rows' => 5, 'title' => 'Message to send:'))->textarea->label->br;
	UI::button('btnSend', 'Send Message');
	HTML::_div();
	end_page(true, true);
?>