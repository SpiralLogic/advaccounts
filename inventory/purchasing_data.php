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
	$page_security = 'SA_PURCHASEPRICING';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Supplier Purchasing Data"), Input::request('frame'));
	Validation::check(Validation::PURCHASE_ITEMS, _("There are no purchasable inventory items defined in the system."), STOCK_PURCHASED);
	Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
	//----------------------------------------------------------------------------------------
	Page::simple_mode(true);
	//--------------------------------------------------------------------------------------------------
	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		if (Input::request('frame')) {
			$_POST['stock_id'] = Session::get()->global_stock_id;
		}
		$input_error = 0;
		if ($_POST['stock_id'] == "" || !isset($_POST['stock_id'])) {
			$input_error = 1;
			Errors::error(_("There is no item selected."));
			JS::set_focus('stock_id');
		}
		elseif (!Validation::is_num('price', 0))
		{
			$input_error = 1;
			Errors::error(_("The price entered was not numeric."));
			JS::set_focus('price');
		}
		elseif (!Validation::is_num('conversion_factor'))
		{
			$input_error = 1;
			Errors::error(_("The conversion factor entered was not numeric. The conversion factor is the number by which the price must be divided by to get the unit price in our unit of measure."));
			JS::set_focus('conversion_factor');
		}
		if ($input_error == 0) {
			if ($Mode == 'ADD_ITEM') {
				$sql
				 = "INSERT INTO purch_data (supplier_id, stock_id, price, suppliers_uom,
    			conversion_factor, supplier_description) VALUES (";
				$sql .= DB::escape($_POST['supplier_id']) . ", " . DB::escape($_POST['stock_id']) . ", "
				 . input_num('price', 0) . ", " . DB::escape($_POST['suppliers_uom']) . ", "
				 . input_num('conversion_factor') . ", "
				 . DB::escape($_POST['supplier_description']) . ")";
				DB::query($sql, "The supplier purchasing details could not be added");
				Errors::notice(_("This supplier purchasing data has been added."));
			} else
			{
				$sql = "UPDATE purch_data SET price=" . input_num('price', 0) . ",
				suppliers_uom=" . DB::escape($_POST['suppliers_uom']) . ",
				conversion_factor=" . input_num('conversion_factor') . ",
				supplier_description=" . DB::escape($_POST['supplier_description']) . "
				WHERE stock_id=" . DB::escape($_POST['stock_id']) . " AND
				supplier_id=" . DB::escape($selected_id);
				DB::query($sql, "The supplier purchasing details could not be updated");
				Errors::notice(_("Supplier purchasing data has been updated."));
			}
			$Mode = 'RESET';
		}
	}
	//--------------------------------------------------------------------------------------------------
	if ($Mode == 'Delete') {
		if (!Input::post('stock_id')) {
			$_POST['stock_id'] = Session::get()->global_stock_id;
		}
		$sql = "DELETE FROM purch_data WHERE supplier_id=" . DB::escape($selected_id) . "
		AND stock_id=" . DB::escape($_POST['stock_id']);
		DB::query($sql, "could not delete purchasing data");
		Errors::notice(_("The purchasing data item has been sucessfully deleted."));
		$Mode = 'RESET';
	}
	if ($Mode == 'RESET') {
		$selected_id = -1;
	}
	if (isset($_POST['_selected_id_update'])) {
		$selected_id = $_POST['selected_id'];
		$Ajax->activate('_page_body');
	}
	if (list_updated('stock_id')) {
		$Ajax->activate('price_table');
	}
	//--------------------------------------------------------------------------------------------------
	if (Input::request('frame')) {
		start_form(false, false, $_SERVER['PHP_SELF'] . '?frame=1');
	} else {
		start_form();
	}
	if (!Input::post('stock_id')) {
		$_POST['stock_id'] = Session::get()->global_stock_id;
	}
	if (!Input::request('frame')) {
		echo "<center>" . _("Item:") . "&nbsp;";
		echo stock_purchasable_items_list('stock_id', $_POST['stock_id'], false, true, false, false);
		echo "<hr></center>";
	}
	Session::get()->global_stock_id = $_POST['stock_id'];
	$mb_flag = Manufacturing::get_mb_flag($_POST['stock_id']);
	if ($mb_flag == -1) {
		Errors::error(_("Entered item is not defined. Please re-enter."));
		JS::set_focus('stock_id');
	} else {
		$sql = "SELECT purch_data.*,suppliers.supp_name,"
		 . "suppliers.curr_code
		FROM purch_data INNER JOIN suppliers
		ON purch_data.supplier_id=suppliers.supplier_id
		WHERE stock_id = " . DB::escape($_POST['stock_id']);
		$result = DB::query($sql, "The supplier purchasing details for the selected part could not be retrieved");
		div_start('price_table');
		if (DB::num_rows($result) == 0) {
			Errors::warning(_("There is no supplier prices set up for the product selected"));
		} else {
			if (Input::request('frame')) {
				start_table(Config::get('tables_style') . "  width=90%");
			} else {
				start_table(Config::get('tables_style') . "  width=65%");
			}
			$th = array(
				_("Updated"), _("Supplier"), _("Price"), _("Currency"),
				_("Unit"), _("Conversion Factor"), _("Supplier's Code"), "", ""
			);
			table_header($th);
			$k = $j = 0; //row colour counter
			while ($myrow = DB::fetch($result))
			{
				alt_table_row_color($k);
				label_cell(Dates::sql2date($myrow['last_update']), "style='white-space:nowrap;'");
				label_cell($myrow["supp_name"]);
				amount_decimal_cell($myrow["price"]);
				label_cell($myrow["curr_code"]);
				label_cell($myrow["suppliers_uom"]);
				qty_cell($myrow['conversion_factor'], false, User::exrate_dec());
				label_cell($myrow["supplier_description"]);
				edit_button_cell("Edit" . $myrow['supplier_id'], _("Edit"));
				delete_button_cell("Delete" . $myrow['supplier_id'], _("Delete"));
				end_row();
				$j++;
				If ($j == 12) {
					$j = 1;
					table_header($th);
				} //end of page full new headings
			} //end of while loop
			end_table();
		}
		div_end();
	}
	//-----------------------------------------------------------------------------------------------
	$dec2 = 6;
	if ($Mode == 'Edit') {
		$sql
		 = "SELECT purch_data.*,suppliers.supp_name FROM purch_data
		INNER JOIN suppliers ON purch_data.supplier_id=suppliers.supplier_id
		WHERE purch_data.supplier_id=" . DB::escape($selected_id) . "
		AND purch_data.stock_id=" . DB::escape($_POST['stock_id']);
		$result = DB::query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");
		$myrow = DB::fetch($result);
		$supp_name = $myrow["supp_name"];
		$_POST['price'] = Num::price_decimal($myrow["price"], $dec2);
		$_POST['suppliers_uom'] = $myrow["suppliers_uom"];
		$_POST['supplier_description'] = $myrow["supplier_description"];
		$_POST['conversion_factor'] = Num::exrate_format($myrow["conversion_factor"]);
	}
	br();
	hidden('selected_id', $selected_id);
	start_table('class="tableinfo"');
	if ($Mode == 'Edit') {
		hidden('supplier_id');
		label_row(_("Supplier:"), $supp_name);
	} else {
		supplier_list_row(_("Supplier:"), 'supplier_id', null, false, true);
		$_POST['price'] = $_POST['suppliers_uom'] = $_POST['conversion_factor'] = $_POST['supplier_description'] = "";
	}
	amount_row(_("Price:"), 'price', null, '', Banking::get_supplier_currency($selected_id), $dec2);
	text_row(_("Suppliers Unit of Measure:"), 'suppliers_uom', null, false, 51);
	if (!isset($_POST['conversion_factor']) || $_POST['conversion_factor'] == "") {
		$_POST['conversion_factor'] = Num::exrate_format(1);
	}
	amount_row(
		_("Conversion Factor (to our UOM):"), 'conversion_factor',
		Num::exrate_format($_POST['conversion_factor']), null, null, User::exrate_dec()
	);
	text_row(_("Supplier's Product Code:"), 'supplier_description', null, 50, 51);
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	if (Input::request('frame')) {
		end_page(true, true, true);
	} else {
		end_page();
	}
