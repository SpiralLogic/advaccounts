<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 9/12/11
	 * Time: 3:29 PM
	 * To change this template use File | Settings | File Templates.
	 */
	include ('bootstrap.php');
	$_GET['start'] = $_GET['start'] ? : 0;
	$result = DB::select('stock_id', 'description')->from('stock_master')->where("concat('',stock_id * 1) <> stock_id")->limit($_GET['start'], 500)->fetch();
	foreach ($result as $item) {
		$data = Barcode::create(array('code' => $item['stock_id'] . "\n" . $item['description']));
		$image = base64_encode($data);
		echo '<div style="width:25%; text-align:center;float:left"><IMG SRC="data:image/gif;base64,' . $image . '">' . '<br>' . $item['stock_id'] . '<br>' . $item['description'] . '</div>';
		$result->next();
		$item = $result->current();
		$data = Barcode::create(array('code' => $item['stock_id'] . "\n" . $item['description']));
		$image = base64_encode($data);
		echo '<div style="width:25%; text-align:center;float:left"><IMG SRC="data:image/gif;base64,' . $image . '">' . '<br>' . $item['stock_id'] . '<br>' . $item['description'] . '</div>';
		$result->next();
		$item = $result->current();
		$data = Barcode::create(array('code' => $item['stock_id'] . "\n" . $item['description']));
		$image = base64_encode($data);
		echo '<div style="width:25%; text-align:center;float:left"><IMG SRC="data:image/gif;base64,' . $image . '">' . '<br>' . $item['stock_id'] . '<br>' . $item['description'] . '</div>';
		$result->next();
		$item = $result->current();
		$data = Barcode::create(array('code' => $item['stock_id'] . "\n" . $item['description']));
		$image = base64_encode($data);
		echo '<div style="display:inline-block;text-align:center;clear:left;width:25%;"><IMG SRC="data:image/gif;base64,' . $image . '">' . '<br>' . $item['stock_id'] . '<br>' . $item['description'] . '</div>';
	}

?>