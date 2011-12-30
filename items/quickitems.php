<?php

	$page_security = 'SA_CUSTOMER';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$_SESSION['App']->selected_application = 'Items';
	if (AJAX_REFERRER) {
		if (isset($_GET['term'])) {
			$data = Item::search($_GET['term']);
		}
		elseif (isset($_POST['id'])) {
			if (isset($_POST['name'])) {
				$item = new Item($_POST);
				$item->save($_POST);
			}
			else {
				$id = Item::getStockId($_POST['id']);
				$item = new Item($id);
			}
			$data['item'] = $item;
			$data['stockLevels'] = $item->getStockLevels();
		}
		if (isset($_GET['page'])) {
			$data['page'] = $_GET['page'];
		}
		JS::renderJSON($data, JSON_NUMERIC_CHECK);
	}
	JS::footerFile("js/quickitems.js");
	Page::start(_($help_context = "Items"), isset($_GET['frame']));
	$stock_cats = Item_Category::select('category_id');
	$units = Item_Unit::select('uom');
	$tax_itemtype = Tax_ItemType::select('tax_type_id');
	$stock_type = Item_UI::type('mb_flag');
	$sales_account = GL_UI::all('sales_account');
	$inventory_account = GL_UI::all('inventory_account');
	$cogs_account = GL_UI::all('cogs_account');
	$adjustment_account = GL_UI::all('adjustment_account');
	$assembly_account = GL_UI::all('assembly_account');
	if (!isset($_GET['stock_id'])) {
		HTML::div('itemSearch', array('class' => 'bold pad10 center'));
		Item::addSearchBox('stock_id', array(
																				'selectjs' => '$("#stock_id").val(value.stock_id);Items.fetch(value.stock_id);return false;'
																	 ));
		HTML::div();
		$id = 0;
	}
	else {
		$id = Item::getStockId($_GET['stock_id']);
	}
	$data['item'] = $item = new Item($id);
	$data['stockLevels'] = $item->getStockLevels();
	$data = json_encode($data, JSON_NUMERIC_CHECK);
	$js = <<<JS
	Items.onload($data);
JS;
	JS::onload($js);
	$menu = new MenuUI();
	if (isset($_GET['page'])) {
		$menu->firstPage = $_GET['page'];
	}
	$menu->startTab("Items", "Items");
	echo <<<HTML
<div id="Items" class="center">
<input type="hidden" value="\${id}" id="id">
<table class="tablestyle2 marginauto width80">
<tr><td><label for="stock_id">Code:</label></td><td><input id="stock_id" type="text" value="\${stock_id}" maxlength="10"></td></tr>
<tr><td ><label for="name">Name:</label><br></td><td><input id="name" type="text" value="\${name}" maxlength="10"></td></tr>
<tr><td ><label for="long_description">Description:</label></td><td><textarea id="long_description" rows="6" cols="36">\${long_description} </textarea></td></tr>
<tr><td ><label for="category_id">Category:</label></td><td>$stock_cats</td></tr>
<tr><td ><label for="uom">Units of Mesasure:</label><br></td><td>$units</tr>
</table></div><div class="center">
<table id="stockLevels" class="marginauto grid" style="width:80%">
<thead><th>Location</th><th>QOH</th><th>Shelf Primary</th><th>Shelf Secondary</th><th>Reorder Level</th><th>On SalesOrder</th><th>Available</th><th>On PurchaseOrder</th></thead><tbody>
<script id="stockRow" type="text/x-jquery-tmpl"><tr><td>\${location_name}</td><td>\${qty}</td><td>\${shelf_primary}</td><td>\${shelf_secondary}</td><td>\${reorder_level}</td><td>\${demand}</td><td>\${available}</td><td>\${onorder}</td></tr></script></tbody>
</table>
</div>
HTML;
	$menu->endTab();
	$menu->startTab("Selling", "Sales Prices");
	echo "<iframe id='sellFrame' src='" . PATH_TO_ROOT . "/inventory/prices.php?frame=1&stock_id=" . $item->stock_id . "' style='width:95%' height='500' frameborder='0'></iframe> ";
	$menu->endTab();
	$menu->startTab("Purchasing", "Purchasing Prices");
	echo "<iframe id='buyFrame' src='" . PATH_TO_ROOT . "/inventory/purchasing_data.php?frame=1&stock_id=" . $item->stock_id . "' style='width:100%' height='500' frameborder='0'></iframe> ";
	$menu->endTab();
	$menu->startTab("Locations", "Stock Locations");
	echo "<iframe id='locationFrame' src='" . PATH_TO_ROOT . "/inventory/reorder_level.php?frame=1&stock_id=" . $item->stock_id . "' style='width:100%' height='500' frameborder='0'></iframe> ";
	$menu->endTab();
	$menu->startTab("Accounts", "Accounts");
	echo <<<HTML
	<div id="Accounts" class="center">
	<table class="tablestyle2 marginauto width80" >
	<tr><td ><label for="tax_type_id">Units of Mesasure:</label><br></td><td>$tax_itemtype</tr>
		<tr><td ><label for="mb_flag">Units of Mesasure:</label><br></td><td>$stock_type</tr>

		<tr><td ><label for="sales_account">Units of Mesasure:</label><br></td><td>$sales_account</tr>

		<tr><td ><label for="inventory_account">Units of Mesasure:</label><br></td><td>$inventory_account</tr>
	<tr><td ><label for="cogs_account">Units of Mesasure:</label><br></td><td>$cogs_account</tr>
	<tr><td ><label for="adjustment_account">Units of Mesasure:</label><br></td><td>$adjustment_account</tr>
	<tr><td ><label for="assembly_account">Units of Mesasure:</label><br></td><td>$assembly_account</tr>
	</table></div>
HTML;
	$menu->endTab();
	$menu->startTab("Website", "Website page for product");
	echo "<iframe id='webFrame' src='" . Config::get('store_product_url') . $item->stock_id . Config::get('store_url_extension') . "' style='width:100%' height='500' frameborder='0'></iframe> ";
	$menu->endTab();
	$menu->render();
	UI::button('btnCancel', 'Cancel', array("style" => "display:none"));
	Page::end(isset($_GET['frame']));
