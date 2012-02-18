<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 15/11/10
	 * Time: 9:50 PM
	 * To change this template use File | Settings | File Templates.
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
	Session::i()->App->selected_application = 'contacts';
	if (AJAX_REFERRER) {
		if (isset($_POST['branch_id'])) {
			if ($_POST['branch_id'] > 0) {
				$data['branch'] = new Debtor_Branch(array('branch_id' => $_POST['branch_id']));
			}
			elseif ($_POST['id'] > 0) {
				$data['branch'] = new Debtor_Branch(array('debtor_no' => $_POST['id']));
			}
		}
		JS::renderJSON($data);
	}
	Page::start(_($help_context = "Items"), SA_CUSTOMER, Input::request('frame'));
	Debtor::addSearchBox('customer_id', array('cell' => false, 'description' => ''));
	Page::end();
