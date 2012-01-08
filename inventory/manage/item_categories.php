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
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

Page::start(_($help_context = "Item Categories"), SA_ITEMCATEGORY);
	list($Mode,$selected_id) = Page::simple_mode(true);
	if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
		//initialise no input errors assumed initially before we test
		$input_error = 0;
		if (strlen($_POST['description']) == 0) {
			$input_error = 1;
			Errors::error(_("The item category description cannot be empty."));
			JS::set_focus('description');
		}
		if ($input_error != 1) {
			if ($selected_id != -1) {
				Item_Category::update($selected_id, $_POST['description'], $_POST['tax_type_id'], $_POST['sales_account'], $_POST['cogs_account'], $_POST['inventory_account'], $_POST['adjustment_account'], $_POST['assembly_account'], $_POST['units'], $_POST['mb_flag'], $_POST['dim1'], $_POST['dim2'], check_value('no_sale'));
				Errors::notice(_('Selected item category has been updated'));
			}
			else {
				Item_Category::add($_POST['description'], $_POST['tax_type_id'], $_POST['sales_account'], $_POST['cogs_account'], $_POST['inventory_account'], $_POST['adjustment_account'], $_POST['assembly_account'], $_POST['units'], $_POST['mb_flag'], $_POST['dim1'], $_POST['dim2'], check_value('no_sale'));
				Errors::notice(_('New item category has been added'));
			}
			$Mode = MODE_RESET;
		}
	}
	function edit_link($row) {
		return button("Edit" . $row["category_id"], _("Edit"));
	}

	function delete_link($row) {
		return button("Delete" . $row["category_id"], _("Delete"));
	}

	if ($Mode == MODE_DELETE) {
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'stock_master'
		$sql = "SELECT COUNT(*) FROM stock_master WHERE category_id=" . DB::escape($selected_id);
		$result = DB::query($sql, "could not query stock master");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Errors::error(_("Cannot delete this item category because items have been created using this item category."));
		}
		else {
			Item_Category::delete($selected_id);
			Errors::notice(_('Selected item category has been deleted'));
		}
		$Mode = MODE_RESET;
	}
	if ($Mode == MODE_RESET) {
		$selected_id = -1;
		$sav = get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $sav;
	}
	if (list_updated('mb_flag')) {
		Ajax::i()->activate('details');
	}
	$sql = "SELECT c.*, t.name as tax_name FROM stock_category c, item_tax_types t WHERE c.dflt_tax_type=t.id";
	if (!check_value('show_inactive')) {
		$sql .= " AND !c.inactive";
	}
	/*$result = DB::query($sql, "could not get stock categories");
	start_form();
	start_table('tablestyle width90');*/
	$th = array(
		array('type' => 'skip'),
		_("Name"),
		array('type' => 'skip'),
		_("Tax type"),
		_("Units"),
		_("Type"),
		_("Sales Act"),
		_("Inventory Account"),
		_("COGS Account"),
		_("Adjustment Account"),
		_("Assembly Account"),
		array(
			'fun' => 'edit_link'
		),
		array(
			'insert' => true, 'fun' => 'delete_link'
		)
	);
	/*	inactive_control_column($th);
	table_header($th);
	$k = 0; //row colour counter
	while ($myrow = DB::fetch($result))
	{
		alt_table_row_color($k);
		label_cell($myrow["description"]);
		label_cell($myrow["tax_name"]);
		label_cell($myrow["dflt_units"], "class=center");
		label_cell($stock_types[$myrow["dflt_mb_flag"]]);
		label_cell($myrow["dflt_sales_act"], "class=center");
		label_cell($myrow["dflt_inventory_act"], "class=center");
		label_cell($myrow["dflt_cogs_act"], "class=center");
		label_cell($myrow["dflt_adjustment_act"], "class=center");
		label_cell($myrow["dflt_assembly_act"], "class=center");
		inactive_control_cell($myrow["category_id"], $myrow["inactive"], 'stock_category', 'category_id');
		edit_button_cell("Edit" . $myrow["category_id"], _("Edit"));
		delete_button_cell("Delete" . $myrow["category_id"], _("Delete"));
		end_row();
	}
	inactive_control_row($th);*/
	$table =& db_pager::new_db_pager('cat_tbl', $sql, $th);
	//$table->width = "92%";
	DB_Pager::display($table);
	echo '<br>';
	Display::div_start('details');
	start_table('tablestyle2');
	if ($selected_id != -1) {
		if ($Mode == MODE_EDIT) {
			//editing an existing item category
			$myrow = Item_Category::get($selected_id);
			$_POST['category_id'] = $myrow["category_id"];
			$_POST['description'] = $myrow["description"];
			$_POST['tax_type_id'] = $myrow["dflt_tax_type"];
			$_POST['sales_account'] = $myrow["dflt_sales_act"];
			$_POST['cogs_account'] = $myrow["dflt_cogs_act"];
			$_POST['inventory_account'] = $myrow["dflt_inventory_act"];
			$_POST['adjustment_account'] = $myrow["dflt_adjustment_act"];
			$_POST['assembly_account'] = $myrow["dflt_assembly_act"];
			$_POST['units'] = $myrow["dflt_units"];
			$_POST['mb_flag'] = $myrow["dflt_mb_flag"];
			$_POST['dim1'] = $myrow["dflt_dim1"];
			$_POST['dim2'] = $myrow["dflt_dim2"];
			$_POST['no_sale'] = $myrow["dflt_no_sale"];
		}
		hidden('selected_id', $selected_id);
		hidden('category_id');
	}
	else {
		if ($Mode != MODE_CLONE) {
			$_POST['long_description'] = '';
			$_POST['description'] = '';
			$_POST['no_sale'] = 0;
			$company_record = DB_Company::get_prefs();
			if (get_post('inventory_account') == "") {
				$_POST['inventory_account'] = $company_record["default_inventory_act"];
			}
			if (get_post('cogs_account') == "") {
				$_POST['cogs_account'] = $company_record["default_cogs_act"];
			}
			if (get_post('sales_account') == "") {
				$_POST['sales_account'] = $company_record["default_inv_sales_act"];
			}
			if (get_post('adjustment_account') == "") {
				$_POST['adjustment_account'] = $company_record["default_adj_act"];
			}
			if (get_post('assembly_account') == "") {
				$_POST['assembly_account'] = $company_record["default_assembly_act"];
			}
		}
	}
	text_row(_("Category Name:"), 'description', null, 30, 30);
	table_section_title(_("Default values for new items"));
	Tax_ItemType::row(_("Item Tax Type:"), 'tax_type_id', null);
	Item_UI::type_row(_("Item Type:"), 'mb_flag', null, true);
	Item_Unit::row(_("Units of Measure:"), 'units', null);
	check_row(_("Exclude from sales:"), 'no_sale');
	GL_UI::all_row(_("Sales Account:"), 'sales_account', $_POST['sales_account']);
	if (Input::post('mb_flag') == STOCK_SERVICE) {
		GL_UI::all_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
		hidden('inventory_account', $_POST['inventory_account']);
		hidden('adjustment_account', $_POST['adjustment_account']);
	}
	else {
		GL_UI::all_row(_("Inventory Account:"), 'inventory_account', $_POST['inventory_account']);
		GL_UI::all_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
		GL_UI::all_row(_("Inventory Adjustments Account:"), 'adjustment_account', $_POST['adjustment_account']);
	}
	if (STOCK_MANUFACTURE == $_POST['mb_flag']) {
		GL_UI::all_row(_("Item Assembly Costs Account:"), 'assembly_account', $_POST['assembly_account']);
	}
	else {
		hidden('assembly_account', $_POST['assembly_account']);
	}
	$dim = DB_Company::get_pref('use_dimension');
	if ($dim >= 1) {
		Dimensions::select_row(_("Dimension") . " 1", 'dim1', null, true, " ", false, 1);
		if ($dim > 1) {
			Dimensions::select_row(_("Dimension") . " 2", 'dim2', null, true, " ", false, 2);
		}
	}
	if ($dim < 1) {
		hidden('dim1', 0);
	}
	if ($dim < 2) {
		hidden('dim2', 0);
	}
	end_table(1);
	Display::div_end();
	submit_add_or_update_center($selected_id == -1, '', 'both', true);
	end_form();
	Page::end();

?>
