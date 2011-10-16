<?php
	//memcached simple test
	require_once('bootstrap.php');

	$item = new Item(Item::getStockID('Brac-1aH'));
	echo '<pre>';
	var_dump($item->getPurchPrices(array('min' => true)));