<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 5/08/11
	 * Time: 5:53 AM
	 *
	 */
	$page_security = 'SA_CUSTOMER';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Session::get()->App->selected_application = 'contacts';
	if (AJAX_REFERRER) {
		if (Input::has_post('type', 'id')) {
			if ($_POST['type'] === 'c') {
				$content = Contacts_Customer::getEmailDialogue($_POST['id']);
				if ($content === false) {
					echo HTML::h3(null, 'No email addresses available.', array('class' => 'center bold top40 font15'), false);
				} else {
					echo $content;
				}
			}
		}
	}
	JS::render();