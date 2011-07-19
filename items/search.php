<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 9:50 PM
 * To change this template use File | Settings | File Templates.
 */
$path_to_root = "..";
include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");
include_once("includes/items.inc");
    if (isset($_GET['term'])) {
        $data = Item::search($_GET['term']);
    }
    if (isset($_POST['id'])) {
        $data['item'] = $item = new Item($_POST['id']);
	    $data['stockLevels'] = $item->getStockLevels();
    }
echo json_encode($data);
