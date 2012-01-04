<?php

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$page_security = SA_ITEM;

	Session::i()->App->selected_application = 'Items';
	if (AJAX_REFERRER) {
		if (isset($_GET['term'])) {
			$data = Item::searchOrder($_GET['term'], $_GET['UniqueID']);
		} elseif (isset($_POST['id'])) {
			if (isset($_POST['name'])) {
				$item = new Item($_POST);
				$item->save($_POST);
			} else {
				$item = new Item($_POST['id']);
			}
			$data['item'] = $item;
		}
		if (isset($_GET['page'])) {
			$data['page'] = $_GET['page'];
		}
		 JS::renderJSON($data, JSON_NUMERIC_CHECK);
	}

