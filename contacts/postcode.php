<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 15/11/11
	 * Time: 9:27 PM
	 * To change this template use File | Settings | File Templates.
	 */
	include ($_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php');
	if (AJAX_REFERRER) {
		if (isset($_GET['postcode']) && isset($_GET['term'])) {
			$data = Contacts_Postcode::searchByPostcode($_GET['term']);
		} elseif (isset($_GET['city']) && isset($_GET['term'])) {
			$data = Contacts_Postcode::searchByCity($_GET['term']);
		}
		 JS::renderJSON($data, JSON_NUMERIC_CHECK);

	} else {
		include('../index.php');
	}