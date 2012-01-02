<?php

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$page_security = SA_SALESMAN;
	//$upload_dir = DOCROOT.'sales/upload/';
	$order = (isset($_SESSION['order_no'])) ? $_SESSION['order_no'] : (isset($_GET['order'])) ? $_GET['order'] : false;
	if ($order) {
		$upload_handler = new UploadHandler($order, $o);
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'HEAD':
			case 'GET':
				$upload_handler->get();
				break;
			case 'POST':
				$upload_handler->post();
				break;
			case 'DELETE':
				$upload_handler->delete();
				break;
			default:
				header('HTTP/1.0 405 Method Not Allowed');
		}
	}
