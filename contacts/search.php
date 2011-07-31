<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 9:50 PM
 * To change this template use File | Settings | File Templates.
 */
	$page_security = 'SA_CUSTOMER';
	$path_to_root = "..";
	include_once("includes/contacts.inc");

	if (AJAX_REFERRER) {
		if (isset($_GET['term'])) {
			$data = Customer::searchOrder($_GET['term']);
		} elseif (isset($_POST['id'])) {
			if (isset($_POST['name'])) {
				$customer = new Customer($_POST);
				$customer->save($_POST);
			} else {
				$customer = new Customer($_POST['id']);
			}
			$data['customer'] = $customer;
		}
		if (isset($_GET['page'])) {
			$data['page'] = $_GET['page'];
		}

		echo json_encode($data, JSON_NUMERIC_CHECK);
		exit();
	}


	page(_($help_context = "Items"), @$_REQUEST['popup']);
	Customer::addSearchBox('customer_id', array('cell' => false, 'description' => ''));

	end_page();
