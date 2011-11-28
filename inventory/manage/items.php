<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	$page_security = 'SA_ITEM';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Items"), Input::request('popup'));
	$user_comp = '';
	$new_item = get_post('stock_id') == '' || get_post('cancel') || get_post('clone');
	//------------------------------------------------------------------------------------
	if (isset($_GET['stock_id'])) {
		$_POST['stock_id'] = $stock_id = $_GET['stock_id'];
	} elseif (isset($_POST['stock_id'])) {
		$stock_id = $_POST['stock_id'];
	}
	if (list_updated('stock_id')) {
		$_POST['NewStockID'] = get_post('stock_id');
		clear_data();
		$Ajax->activate('details');
		$Ajax->activate('controls');
	}
	if (get_post('cancel')) {
		$_POST['NewStockID'] = $_POST['stock_id'] = '';
		clear_data();
		JS::set_focus('stock_id');
		$Ajax->activate('_page_body');
	}
	if (list_updated('category_id') || list_updated('mb_flag')) {
		$Ajax->activate('details');
	}
	$upload_file = "";
	if (isset($_FILES['pic']) && $_FILES['pic']['name'] != '') {
		$stock_id = $_POST['NewStockID'];
		$result = $_FILES['pic']['error'];
		$upload_file = 'Yes'; //Assume all is well to start off with
		$filename = COMPANY_PATH . "/$user_comp/images";
		if (!file_exists($filename)) {
			mkdir($filename);
		}
		$filename .= "/" . Item::img_name($stock_id) . ".jpg";
		//But check for the worst
		if (strtoupper(substr(trim($_FILES['pic']['name']), strlen($_FILES['pic']['name']) - 3)) != 'JPG') {
			Errors::warning(_('Only jpg files are supported - a file extension of .jpg is expected'));
			$upload_file = 'No';
		}
		elseif ($_FILES['pic']['size'] > (Config::get('item_images_max_size') * 1024))
		{ //File Size Check
			Errors::warning(_('The file size is over the maximum allowed. The maximum size allowed in KB is') . ' ' . Config::get('item_images_max_size'));
			$upload_file = 'No';
		}
		elseif ($_FILES['pic']['type'] == "text/plain")
		{ //File type Check
			Errors::warning(_('Only graphics files can be uploaded'));
			$upload_file = 'No';
		}
		elseif (file_exists($filename))
		{
			$result = unlink($filename);
			if (!$result) {
				Errors::error(_('The existing image could not be removed'));
				$upload_file = 'No';
			}
		}
		if ($upload_file == 'Yes') {
			$result = move_uploaded_file($_FILES['pic']['tmp_name'], $filename);
		}
		$Ajax->activate('details');
		/* EOF Add Image upload for New Item  - by Ori */
	}
	Validation::check(Validation::STOCK_CATEGORIES, _("There are no item categories defined in the system. At least one item category is required to add a item."));
	Validation::check(Validation::ITEM_TAX_TYPES, _("There are no item tax types defined in the system. At least one item tax type is required to add a item."));
	function clear_data()
	{
		unset($_POST['long_description']);
		unset($_POST['description']);
		unset($_POST['category_id']);
		unset($_POST['tax_type_id']);
		unset($_POST['units']);
		unset($_POST['mb_flag']);
		unset($_POST['NewStockID']);
		unset($_POST['dimension_id']);
		unset($_POST['dimension2_id']);
		unset($_POST['no_sale']);
	}

	//------------------------------------------------------------------------------------
	if (isset($_POST['addupdate']) || isset($_POST['addupdatenew'])) {
		$input_error = 0;
		if ($upload_file == 'No') {
			$input_error = 1;
		}
		if (strlen($_POST['description']) == 0) {
			$input_error = 1;
			Errors::error(_('The item name must be entered.'));
			JS::set_focus('description');
		} elseif (empty($_POST['NewStockID'])) {
			$input_error = 1;
			Errors::error(_('The item code cannot be empty'));
			JS::set_focus('NewStockID');
		} elseif (strstr($_POST['NewStockID'], " ") || strstr($_POST['NewStockID'], "'") || strstr($_POST['NewStockID'], "+")
							|| strstr($_POST['NewStockID'], "\"")
							|| strstr($_POST['NewStockID'], "&")
							|| strstr($_POST['NewStockID'], "\t")
		) {
			$input_error = 1;
			Errors::error(_('The item code cannot contain any of the following characters -  & + OR a space OR quotes'));
			JS::set_focus('NewStockID');
		} elseif ($new_item && DB::num_rows(Item_Code::get_kit($_POST['NewStockID']))) {
			$input_error = 1;
			Errors::error(_("This item code is already assigned to stock item or sale kit."));
			JS::set_focus('NewStockID');
		}
		if ($input_error != 1) {
			if (check_value('del_image')) {
				$filename = COMPANY_PATH . "/$user_comp/images/" . Item::img_name($_POST['NewStockID']) . ".jpg";
				if (file_exists($filename)) {
					unlink($filename);
				}
			}
			if (!$new_item) { /*so its an existing one */
				Item::update(
					$_POST['NewStockID'], $_POST['description'],
					$_POST['long_description'], $_POST['category_id'],
					$_POST['tax_type_id'], get_post('units'),
					get_post('mb_flag'), $_POST['sales_account'],
					$_POST['inventory_account'], $_POST['cogs_account'],
					$_POST['adjustment_account'], $_POST['assembly_account'],
					$_POST['dimension_id'], $_POST['dimension2_id'],
					check_value('no_sale'), check_value('editable')
				);
				DB::update_record_status($_POST['NewStockID'], $_POST['inactive'], 'stock_master', 'stock_id');
				DB::update_record_status($_POST['NewStockID'], $_POST['inactive'], 'item_codes', 'item_code');
				$Ajax->activate('stock_id'); // in case of status change
				Errors::notice(_("Item has been updated."));
			} else { //it is a NEW part
				Item::add(
					$_POST['NewStockID'], $_POST['description'],
					$_POST['long_description'], $_POST['category_id'], $_POST['tax_type_id'],
					$_POST['units'], $_POST['mb_flag'], $_POST['sales_account'],
					$_POST['inventory_account'], $_POST['cogs_account'],
					$_POST['adjustment_account'], $_POST['assembly_account'],
					$_POST['dimension_id'], $_POST['dimension2_id'],
					check_value('no_sale'), check_value('editable')
				);
				Errors::notice(_("A new item has been added."));
				JS::set_focus('NewStockID');
			}
			if (isset($_POST['addupdatenew'])) {
				$_POST['NewStockID'] = $_POST['stock_id'] = '';
				clear_data();
				$new_item = true;
				meta_forward($_SERVER['PHP_SELF']);
			} else {
				Session::i()->global_stock_id = $_POST['NewStockID'];
				$_POST['stock_id'] = $_POST['NewStockID'];
			}
			$Ajax->activate('_page_body');
		}
	}
	if (get_post('clone')) {
		unset($_POST['stock_id']);
		unset($_POST['inactive']);
		JS::set_focus('NewStockID');
		$Ajax->activate('_page_body');
	}
	//------------------------------------------------------------------------------------
	function check_usage($stock_id, $dispmsg = true)
	{
		$sqls = array(
			"SELECT COUNT(*) FROM stock_moves WHERE stock_id="          => _('Cannot delete this item because there are stock movements that refer to this item.'),
			"SELECT COUNT(*) FROM bom WHERE component=" => _('Cannot delete this item record because there are bills of material that require this part as a component.'),
			"SELECT COUNT(*) FROM sales_order_details WHERE stk_code="  => _('Cannot delete this item because there are existing purchase order items for it.'),
			"SELECT COUNT(*) FROM purch_order_details WHERE item_code="  => _('Cannot delete this item because there are existing purchase order items for it.')
		);
		$msg = '';
		foreach (
			$sqls as $sql => $err
		) {

			$result = DB::query($sql .  DB::escape($stock_id), "could not query stock usage");
			$myrow = DB::fetch_row($result);
			if ($myrow[0] > 0) {
				$msg = $err;
				break;
			}
		}
		if ($msg == '') {
			$kits = Item_Code::get_where_used($stock_id);
			$num_kits = DB::num_rows($kits);
			if ($num_kits) {
				$msg = _("This item cannot be deleted because some code aliases or foreign codes was entered for it, or there are kits defined using this item as component") . ':<br>';
				while ($num_kits--) {
					$kit = DB::fetch($kits);
					$msg .= "'" . $kit[0] . "'";
					if ($num_kits) {
						$msg .= ',';
					}
				}
			}
		}
		if ($msg != '') {
			if ($dispmsg) {
				Errors::error($msg);
			}
			return false;
		}
		return true;
	}

	//------------------------------------------------------------------------------------
	if (isset($_POST['delete']) && strlen($_POST['delete']) > 1) {
		if (check_usage($_POST['NewStockID'])) {
			$stock_id = $_POST['NewStockID'];
			Item::del($stock_id);
			$filename = COMPANY_PATH . "/$user_comp/images/" . Item::img_name($stock_id) . ".jpg";
			if (file_exists($filename)) {
				unlink($filename);
			}
			Errors::notice(_("Selected item has been deleted."));
			$_POST['stock_id'] = '';
			clear_data();
			$new_item = true;
			$Ajax->activate('_page_body');
		}
	}
	//--------------------------------------------------------------------------------------------
	start_form(true);
	if (Validation::check(Validation::STOCK_ITEMS)) {
		start_table("class='tablestyle_noborder'");
		start_row();
		if ($new_item) {
			stock_items_list_cells(_("Select an item:"), 'stock_id', null, _('New item'), true, check_value('show_inactive'), false);
			check_cells(_("Show inactive:"), 'show_inactive', null, true);
		} else {
			hidden('stock_id', $_POST['stock_id']);
		}
		$new_item = get_post('stock_id') == '';
		end_row();
		end_table();
		if (get_post('_show_inactive_update')) {
			$_SESSION['options']['stock_id']['inactive'] = check_value('show_inactive');
			$Ajax->activate('stock_id');
		}
	}
	div_start('details');
	start_outer_table(Config::get('tables_style2'), 5);
	table_section(1);
	table_section_title(_("Item"));
	//------------------------------------------------------------------------------------
	if ($new_item) {
		text_row(_("Item Code:"), 'NewStockID', null, 21, 20);
		$_POST['inactive'] = 0;
	} else { // Must be modifying an existing item
		if (get_post('NewStockID') != get_post('stock_id') || get_post('addupdate')) { // first item display
			$_POST['NewStockID'] = $_POST['stock_id'];
			$myrow = Item::get($_POST['NewStockID']);
			$_POST['long_description'] = $myrow["long_description"];
			$_POST['description'] = $myrow["description"];
			$_POST['category_id'] = $myrow["category_id"];
			$_POST['tax_type_id'] = $myrow["tax_type_id"];
			$_POST['units'] = $myrow["units"];
			$_POST['mb_flag'] = $myrow["mb_flag"];
			$_POST['sales_account'] = $myrow['sales_account'];
			$_POST['inventory_account'] = $myrow['inventory_account'];
			$_POST['cogs_account'] = $myrow['cogs_account'];
			$_POST['adjustment_account'] = $myrow['adjustment_account'];
			$_POST['assembly_account'] = $myrow['assembly_account'];
			$_POST['dimension_id'] = $myrow['dimension_id'];
			$_POST['dimension2_id'] = $myrow['dimension2_id'];
			$_POST['no_sale'] = $myrow['no_sale'];
			$_POST['del_image'] = 0;
			$_POST['inactive'] = $myrow["inactive"];
			$_POST['editable'] = $myrow["editable"];
		}
		label_row(_("Item Code:"), $_POST['NewStockID']);
		hidden('NewStockID', $_POST['NewStockID']);
		JS::set_focus('description');
	}
	text_row(_("Name:"), 'description', null, 52, 200);
	textarea_row(_('Description:'), 'long_description', null, 42, 3);
	stock_categories_list_row(_("Category:"), 'category_id', null, false, $new_item);
	if ($new_item && (list_updated('category_id') || !isset($_POST['units']))) {
		$category_record = Item_Category::get($_POST['category_id']);
		$_POST['tax_type_id'] = $category_record["dflt_tax_type"];
		$_POST['units'] = $category_record["dflt_units"];
		$_POST['mb_flag'] = $category_record["dflt_mb_flag"];
		$_POST['inventory_account'] = $category_record["dflt_inventory_act"];
		$_POST['cogs_account'] = $category_record["dflt_cogs_act"];
		$_POST['sales_account'] = $category_record["dflt_sales_act"];
		$_POST['adjustment_account'] = $category_record["dflt_adjustment_act"];
		$_POST['assembly_account'] = $category_record["dflt_assembly_act"];
		$_POST['dimension_id'] = $category_record["dflt_dim1"];
		$_POST['dimension2_id'] = $category_record["dflt_dim2"];
		$_POST['no_sale'] = $category_record["dflt_no_sale"];
		$_POST['editable'] = 0;
	}
	$fresh_item = !isset($_POST['NewStockID']) || $new_item || check_usage($_POST['stock_id'], false);
	item_tax_types_list_row(_("Item Tax Type:"), 'tax_type_id', null);
	stock_item_types_list_row(_("Item Type:"), 'mb_flag', null, $fresh_item);
	stock_units_list_row(_('Units of Measure:'), 'units', null, $fresh_item);
	check_row(_("Editable description:"), 'editable');
	check_row(_("Exclude from sales:"), 'no_sale');
	table_section(2);
	$dim = DB_Company::get_pref('use_dimension');
	if ($dim >= 1) {
		table_section_title(_("Dimensions"));
		dimensions_list_row(_("Dimension") . " 1", 'dimension_id', null, true, " ", false, 1);
		if ($dim > 1) {
			dimensions_list_row(_("Dimension") . " 2", 'dimension2_id', null, true, " ", false, 2);
		}
	}
	if ($dim < 1) {
		hidden('dimension_id', 0);
	}
	if ($dim < 2) {
		hidden('dimension2_id', 0);
	}
	table_section(2);
	table_section_title(_("GL Accounts"));
	gl_all_accounts_list_row(_("Sales Account:"), 'sales_account', $_POST['sales_account']);
	if (!$_POST['mb_flag'] == STOCK_SERVICE) {
		gl_all_accounts_list_row(_("Inventory Account:"), 'inventory_account', $_POST['inventory_account']);
		gl_all_accounts_list_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
		gl_all_accounts_list_row(_("Inventory Adjustments Account:"), 'adjustment_account', $_POST['adjustment_account']);
	} else {
		gl_all_accounts_list_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
		hidden('inventory_account', $_POST['inventory_account']);
		hidden('adjustment_account', $_POST['adjustment_account']);
	}
	if (STOCK_MANUFACTURE == $_POST['mb_flag']) {
		gl_all_accounts_list_row(_("Item Assembly Costs Account:"), 'assembly_account', $_POST['assembly_account']);
	} else {
		hidden('assembly_account', $_POST['assembly_account']);
	}
	table_section_title(_("Other"));
	// Add image    for New Item  - by Joe
	file_row(_("Image File (.jpg)") . ":", 'pic', 'pic');
	// Add Image upload for New Item  - by Joe
	$stock_img_link = "";
	$check_remove_image = false;
	if (isset($_POST['NewStockID']) && file_exists(COMPANY_PATH . "/$user_comp/images/" . Item::img_name($_POST['NewStockID']) . ".jpg")) {
		// 31/08/08 - rand() call is necessary here to avoid caching problems. Thanks to Peter D.
		$stock_img_link .= "<img id='item_img' alt = '[" . $_POST['NewStockID'] . ".jpg]' src='" . COMPANY_PATH . "/$user_comp/images/"
											 . Item::img_name($_POST['NewStockID']) . ".jpg?nocache=" . rand() . "' height='" . Config::get('item_images_height') . "' border='0'>";
		$check_remove_image = true;
	} else {
		$stock_img_link .= _("No image");
	}
	label_row("&nbsp;", $stock_img_link);
	if ($check_remove_image) {
		check_row(_("Delete Image:"), 'del_image');
	}
	check_row(_("Exclude from sales:"), 'no_sale');
	record_status_list_row(_("Item status:"), 'inactive');
	end_outer_table(1);
	div_end();
	div_start('controls');
	if (!isset($_POST['NewStockID']) || $new_item) {
		submit_center('addupdate', _("Insert New Item"), true, '', 'default');
	} else {
		submit_center_first('addupdate', _("Update Item"), '', Input::request('popup') ? true : 'default');
		submit_return('select', get_post('stock_id'), _("Select this items and return to document entry."), 'default');
		submit('clone', _("Clone This Item"), true, '', true);
		submit('delete', _("Delete This Item"), true, '', true);
		submit('addupdatenew', _("Save & New"), true, '', true);
		submit_center_last('cancel', _("Cancel"), _("Cancel Edition"), 'cancel');
	}
	if (get_post('stock_id')) {
		Session::i()->global_stock_id = get_post('stock_id');
		echo "<iframe src='/inventory/purchasing_data.php?frame=1' width='48%' height='450' style='overflow-x: hidden; overflow-y: scroll; ' frameborder='0'></iframe> ";
	}
	if (get_post('stock_id')) {
		Session::i()->global_stock_id = get_post('stock_id');
		echo "<iframe style='float:right;' src='/inventory/prices.php?frame=1' width='48%' height='450' style='overflow-x: hidden; overflow-y: scroll; ' frameborder='0'></iframe> ";
	}
	div_end();
	hidden('popup', Input::request('popup'));
	end_form();
	//------------------------------------------------------------------------------------
	end_page();
?>
