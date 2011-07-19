<?php

	$page_security = 'SA_CUSTOMER';
	$path_to_root = "..";
	include_once("includes/items.inc");
	if (AJAX_REFERRER) {
		echo json_encode($data);
		exit();
	}
	add_js_ffile("/js/js2/jquery-tmpl.min.js");
	add_js_ffile("includes/js/quickitems.js");

	page(_($help_context = "Customers"), true);

	$stock_cats = stock_categories_list('category_id');
	HTML::div('itemSearch');
	UI::search('item', array('label' => 'Search Item', 'size' => 80, 'url' => 'search.php', 'callback' => 'Items.fetch'));
	HTML::div();
	echo <<<HTML
<div id="Items" class="aligncenter">
<table class="marginauto" style="width:80%">
<tr><td><label for="id">Code:</label></td><td><input id="id" type="text" value="\${id}" maxlength="10"></td></tr>
<tr><td><label for="name">Name:</label><br></td><td><input id="name" type="text" value="\${name}" maxlength="10"></td></tr>
<tr><td><label for="description">Description:</label></td><td><textarea id="description" rows="6" cols="36">\${description} </textarea></td></tr>
<tr><td><label for="category_id">Category:</label></td><td>$stock_cats</td></tr>
<tr><td><label for="uom">Units of Mesasure:</label><br></td><td><input id="uom" type="text" value="\${uom}" maxlength="10"></td></tr>
</table>
<table id="stockLevels" class="marginauto" style="width:80%">
<tr><th>Location</th><th>QOH</th><th>Reorder Level</th><th>On Sales<br>Order</th><th>Available</th><th>On Purchase<br>Order</th></tr>
<script id="stockRow" type="text/x-jquery-tmpl"><tr><td>\${location_name}</td><td>\${quantity}</td><td>\${reorder_level}</td><td>\${demand}</td><td>\${available}</td><td>\${on_order}</td></tr></script>
</table>
</div>
HTML;

	end_page(true, true);