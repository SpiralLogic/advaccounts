<?php

	require 'bootstrap.php';
	echo '<pre>';
	use Modules\Volusion\Orders as Orders;

	$orders = new Orders();
	/** @var	Orders $order	*/
	foreach ($orders as $order) {
		//var_dump($order);
		echo $orders->exists();
		foreach ($orders->details as $detail) {
			//var_dump($detail);
			if ($orders->details->options) {
				foreach ($orders->details->options as $option) {
				//	var_dump($option);
				}
			}
		}
	}
	;

