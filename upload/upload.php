<?php
$page_security = 'SA_SALESMAN';
	$path_to_root = "..";
	include($path_to_root . "/includes/session.inc");
	//$upload_dir = APP_PATH.'sales/upload/';
	if (isset($_SESSION['order_no'])) {
		$upload_handler = new UploadHandler($_SESSION['order_no']);
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
	;