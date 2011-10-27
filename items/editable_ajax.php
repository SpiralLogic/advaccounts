<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 29/08/11
	 * Time: 3:27 PM
	 * To change this template use File | Settings | File Templates.
	 */

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	$item = array('id' => $_POST['row_id']);
	$q = DB::update('stock')->where('id=', $item['id']);
	switch ($_POST['column']) {
		case 0:
			$f = 'id';
			break;
		case 1:
			$f = 'name';
			break;
		case 2:
			$f = 'description';
			break;
		case 3:
			$f = 'price';
			break;
	}
	$item[$f] = $_POST['value'];

	echo	$q->exec($item);
