<?php

	$path_to_root = ".";
	if (!file_exists($path_to_root . '/config.php'))
		header("Location: " . "/install/index.php");

	$page_security = 'SA_OPEN';


	include_once("includes/session.inc");
$item = new Item('test');
$stock = $item->getStockLevels('MEL');
	echo "<pre>";
print_r($stock);