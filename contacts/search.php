<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 15/11/10
	 * Time: 9:50 PM
	 * To change this template use File | Settings | File Templates.
	 */
	$page_security = 'SA_CUSTOMER';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Session::i()->App->selected_application = 'contacts';
	if (AJAX_REFERRER) {
		if (isset($_POST['branch_code'])) {
			if ($_POST['branch_code'] > 0) {
				$data['branch']= new Debtor_Branch(array('branch_code' => $_POST['branch_code']));
			} elseif ($_POST['id'] > 0) {
				$data['branch'] = new Debtor_Branch(array('debtor_no' => $_POST['id']));
			}
		}
		 JS::renderJSON($data);
	}
	Page::start(_($help_context = "Items"), Input::request('frame'));
	Debtor::addSearchBox('customer_id', array('cell' => false, 'description' => ''));
	Renderer::end_page();
