<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 9/12/11
	 * Time: 3:29 PM
	 * To change this template use File | Settings | File Templates.
	 */
	include ('bootstrap.php');
	$items = DB::select('stock_id', 'description')->from('stock_master')->limit(0, 200)->fetch()->all();
	foreach ($items as $item) {
		$data = Barcode::create(array('code' => $item['stock_id'] . "\n" . $item['description']));
		$image = base64_encode($data);
		echo '<div style="display:inline-block;text-align:center;clear:left;width:25%;"><IMG SRC="data:image/gif;base64,' . $image . '">' . '<br>' . $item['stock_id'] . '<br>' . $item['description'] . '</div>';
		echo '<div style="width:25%; text-align:center;float:left"><IMG SRC="data:image/gif;base64,' . $image . '">' . '<br>' . $item['stock_id'] . '<br>' . $item['description'] . '</div>';
		echo '<div style="width:25%; text-align:center;float:left"><IMG SRC="data:image/gif;base64,' . $image . '">' . '<br>' . $item['stock_id'] . '<br>' . $item['description'] . '</div>';
		echo '<div style="width:25%; text-align:center;float:left"><IMG SRC="data:image/gif;base64,' . $image . '">' . '<br>' . $item['stock_id'] . '<br>' . $item['description'] . '</div>';
	}

?>