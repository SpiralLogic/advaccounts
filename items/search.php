<?php

	$page_security = 'SA_ITEM';

	include_once("includes/items.php");
	if (AJAX_REFERRER) {

		if (isset($_GET['term'])) {
			$data = Items_Item::searchOrder($_GET['term'], $_GET['id']);
		} elseif (isset($_POST['id'])) {
			if (isset($_POST['name'])) {
				$item = new Items_Item($_POST);
				$item->save($_POST);
			} else {
				$item = new Items_Item($_POST['id']);
			}
			$data['item'] = $item;
		}
		if (isset($_GET['page'])) {
			$data['page'] = $_GET['page'];
		}

		echo json_encode($data, JSON_NUMERIC_CHECK);
		exit();
	}

