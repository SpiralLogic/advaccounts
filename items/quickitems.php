<?php

	$page_security = 'SA_CUSTOMER';

	include_once("includes/items.inc");
	if (AJAX_REFERRER) {

		if (isset($_GET['term'])) {
			$data = Item::search($_GET['term']);
		} elseif (isset($_POST['stock_id'])) {
			if (isset($_POST['name'])) {
				$item = new Item($_POST);
				$item->save($_POST);
			} else {
				$id = Item::getStockId($_POST['stock_id']);
				$item = new Item($id);
			}
			$data['item'] = $item;
			$data['stockLevels'] = $item->getStockLevels();
		}
		if (isset($_GET['page'])) {
			$data['page'] = $_GET['page'];
		}

		echo json_encode($data, JSON_NUMERIC_CHECK);
		exit();
	}
	JS::footerFile(array("/js/js2/jquery-tmpl.min.js", "includes/js/quickitems.js"));

	page(_($help_context = "Items"), true);

	$stock_cats = stock_categories_list('category_id');
	if (!isset($_GET['stock_id'])) {
		HTML::div('itemSearch');
		UI::search('item', array('label' => 'Search Item',
			'size' => 80,
			'url' => 'search.php',
			'callback' => 'Items.fetch'
		));
		HTML::div();
		$id = 0;
	} else {
		$id = Item::getStockId($_GET['stock_id']);
	}
	$data['item'] = $item = new Item($id);
	$data['stockLevels'] = $item->getStockLevels();
	$data = json_encode($data, JSON_NUMERIC_CHECK);
	JS::onload(<<<JS
Items.onload($data);
JS
	);

	$menu = new MenuUI();
	$menu->startTab("Items", "Items");

	echo <<<HTML
<div id="Items" class="center">
<hidden value="\${id}" id="id"></hidden>
<table class="tableinfo marginauto" style="width:80%">
<tr><td><label for="stock_id">Code:</label></td><td><input id="stock_id" type="text" value="\${stock_id}" maxlength="10"></td></tr>
<tr><td ><label for="name">Name:</label><br></td><td><input id="name" type="text" value="\${name}" maxlength="10"></td></tr>
<tr><td ><label for="description">Description:</label></td><td><textarea id="description" rows="6" cols="36">\${description} </textarea></td></tr>
<tr><td ><label for="category_id">Category:</label></td><td>$stock_cats</td></tr>
<tr><td ><label for="uom">Units of Mesasure:</label><br></td><td><input id="uom" type="text" value="\${uom}" maxlength="10"></td></tr>
</table></div><div class="center">
<table id="stockLevels" class="marginauto grid" style="width:80%">
<thead><th>Location</th><th>QOH</th><th>Reorder Level</th><th>On SalesOrder</th><th>Available</th><th>On PurchaseOrder</th></thead><tbody>
<script id="stockRow" type="text/x-jquery-tmpl"><tr><td>\${location_name}</td><td>\${qty}</td><td>\${reorder_level}</td><td>\${demand}</td><td>\${available}</td><td>\${onorder}</td></tr></script></tbody>
</table>
</div>
HTML;
	$menu->endTab();
	$menu->startTab("Selling", "Sales Prices");
	echo "<iframe id='sellFrame' src='" . PATH_TO_ROOT . "/inventory/prices.php?frame=1&stock_id=" . $item->stock_id . "' width='90%' height='500' frameborder='0'></iframe> ";

	$menu->endTab();
	$menu->startTab("Purchasing", "Purchasing Prices");
	echo "<iframe id='buyFrame' src='" . PATH_TO_ROOT . "/inventory/purchasing_data.php?frame=1&stock_id=" . $item->stock_id . "' width='90%' height='500'  frameborder='0'></iframe> ";

	$menu->endTab();
	$menu->startTab("Website", "Website page for product");
	echo "<iframe id='webFrame' src='" . STORE_PRODUCT_URL . $item->stock_id . STORE_URL_EXTENSION . "' width='90%' height='500'  frameborder='0'></iframe> ";

	$menu->endTab();

	if (isset($_GET['page'])) {
		$menu->firstPage = $_GET['page'];
	}
	$menu->render();
	UI::button('btnCancel', 'Cancel', array("style" => "display:none"));
	end_page(true, true);