<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 5/08/11
 * Time: 5:53 AM
 *
 */

	$page_security = 'SA_CUSTOMER';
	$path_to_root = "..";
	include_once("includes/contacts.inc");
	include_once(APP_PATH . "reporting/includes/reporting.inc");
	include_once(APP_PATH . "reporting/includes/tcpdf.php");
	if (AJAX_REFERRER) {
		if (Input::has_post('type','id')) {
			if ($_POST['type'] === 'c') {
				$content = Customer::getEmailDialogue($_POST['id']);

				if ($content === false) {
					echo HTML::h3(null, 'No email addresses available.', array('class' => 'center bold top40 font15'), false);
				} else {
					echo $content;
				}

			}
		}

	}
	exit();
