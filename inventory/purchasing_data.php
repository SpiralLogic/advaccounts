<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
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

	page(_($help_context = "Supplier Purchasing Data"), Input::request('frame'));

	check_db_has_purchasable_items(_("There are no purchasable inventory items defined in the system."));
	check_db_has_suppliers(_("There are no suppliers defined in the system."));

	//----------------------------------------------------------------------------------------
	simple_page_mode(true);

	//--------------------------------------------------------------------------------------------------

	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		if (Input::request('frame')) {
			$_POST['stock_id'] = ui_globals::get_global_stock_item();
		}
		$input_error = 0;
		if ($_POST['stock_id'] == "" || !isset($_POST['stock_id'])) {
			$input_error = 1;
			ui_msgs::display_error(_("There is no item selected."));
			ui_view::set_focus('stock_id');
		}
		elseif (!check_num('price', 0))
		{
			$input_error = 1;
			ui_msgs::display_error(_("The price entered was not numeric."));
			ui_view::set_focus('price');
		}
		elseif (!check_num('conversion_factor'))
		{
			$input_error = 1;
			ui_msgs::display_error(_("The conversion factor entered was not numeric. The conversion factor is the number by which the price must be divided by to get the unit price in our unit of measure."));
			ui_view::set_focus('conversion_factor');
		}

		if ($input_error == 0) {
			if ($Mode == 'ADD_ITEM') {

				$sql = "INSERT INTO purch_data (supplier_id, stock_id, price, suppliers_uom,
    			conversion_factor, supplier_description) VALUES (";
				$sql .= DBOld::escape($_POST['supplier_id']) . ", " . DBOld::escape($_POST['stock_id']) . ", "
				 . input_num('price', 0) . ", " . DBOld::escape($_POST['suppliers_uom']) . ", "
				 . input_num('conversion_factor') . ", "
				 . DBOld::escape($_POST['supplier_description']) . ")";

				DBOld::query($sql, "The supplier purchasing details could not be added");
				ui_msgs::display_notification(_("This supplier purchasing data has been added."));
			} else
			{
				$sql = "UPDATE purch_data SET price=" . input_num('price', 0) . ",
				suppliers_uom=" . DBOld::escape($_POST['suppliers_uom']) . ",
				conversion_factor=" . input_num('conversion_factor') . ",
				supplier_description=" . DBOld::escape($_POST['supplier_description']) . "
				WHERE stock_id=" . DBOld::escape($_POST['stock_id']) . " AND
				supplier_id=" . DBOld::escape($selected_id);
				DBOld::query($sql, "The supplier purchasing details could not be updated");
				ui_msgs::display_notification(_("Supplier purchasing data has been updated."));
			}
			$Mode = 'RESET';
		}
	}
	//--------------------------------------------------------------------------------------------------
	if ($Mode == 'Delete') {
		if (!Input::post('stock_id')) $_POST['stock_id'] = ui_globals::get_global_stock_item();
		$sql = "DELETE FROM purch_data WHERE supplier_id=" . DBOld::escape($selected_id) . "
		AND stock_id=" . DBOld::escape($_POST['stock_id']);
		DBOld::query($sql, "could not delete purchasing data");
		ui_msgs::display_notification(_("The purchasing data item has been sucessfully deleted."));
		$Mode = 'RESET';
	}
	if ($Mode == 'RESET') {
		$selected_id = -1;
	}
	if (isset($_POST['_selected_id_update'])) {
		$selected_id = $_POST['selected_id'];
		$Ajax->activate('_page_body');
	}
	if (list_updated('stock_id'))
		$Ajax->activate('price_table');
	//--------------------------------------------------------------------------------------------------
	if (Input::request('frame')) {
		start_form(false, false, $_SERVER['PHP_SELF'] . '?frame=1');
	} else {
		start_form();
	}
	if (!isset($_POST['stock_id']))
		$_POST['stock_id'] = ui_globals::get_global_stock_item();
	if (!Input::request('frame')) {
		echo "<center>" . _("Item:") . "&nbsp;";
		echo stock_purchasable_items_list('stock_id', $_POST['stock_id'], false, true, false, false);
		echo "<hr></center>";
	}
	ui_globals::set_global_stock_item($_POST['stock_id']);
	$mb_flag = Manufacturing::get_mb_flag($_POST['stock_id']);

	if ($mb_flag == -1) {
		ui_msgs::display_error(_("Entered item is not defined. Please re-enter."));
		ui_view::set_focus('stock_id');
	}
	else
	{

		$sql = "SELECT purch_data.*,suppliers.supp_name,"
		 . "suppliers.curr_code
		FROM purch_data INNER JOIN suppliers
		ON purch_data.supplier_id=suppliers.supplier_id
		WHERE stock_id = " . DBOld::escape($_POST['stock_id']);

		$result = DBOld::query($sql, "The supplier purchasing details for the selected part could not be retrieved");
		div_start('price_table');
		if (DBOld::num_rows($result) == 0) {
			ui_msgs::display_note(_("There is no supplier prices set up for the product selected"));
		}
		else
		{
			if (Input::request('frame')) {
				start_table(Config::get('tables.style') . "  width=90%");
			} else {
				start_table(Config::get('tables.style') . "  width=65%");
			}
			$th = array(_("Updated"), _("Supplier"), _("Price"), _("Currency"),
				_("Unit"), _("Conversion Factor"), _("Supplier's Code"), "", ""
			);

			table_header($th);

			$k = $j = 0; //row colour counter

			while ($myrow = DBOld::fetch($result))
			{
				alt_table_row_color($k);
				label_cell(Dates::sql2date($myrow['last_update']), "style='white-space:nowrap;'");

				label_cell($myrow["supp_name"]);
				amount_decimal_cell($myrow["price"]);
				label_cell($myrow["curr_code"]);
				label_cell($myrow["suppliers_uom"]);
				qty_cell($myrow['conversion_factor'], false, user_exrate_dec());
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

		$sql = "SELECT purch_data.*,suppliers.supp_name FROM purch_data
		INNER JOIN suppliers ON purch_data.supplier_id=suppliers.supplier_id
		WHERE purch_data.supplier_id=" . DBOld::escape($selected_id) . "
		AND purch_data.stock_id=" . DBOld::escape($_POST['stock_id']);
		$result = DBOld::query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");
		$myrow = DBOld::fetch($result);
		$supp_name = $myrow["supp_name"];
		$_POST['price'] = price_decimal_format($myrow["price"], $dec2);
		$_POST['suppliers_uom'] = $myrow["suppliers_uom"];
		$_POST['supplier_description'] = $myrow["supplier_description"];
		$_POST['conversion_factor'] = exrate_format($myrow["conversion_factor"]);
	}
	br();
	hidden('selected_id', $selected_id);
	start_table('class="tableinfo"');

	if ($Mode == 'Edit') {
		hidden('supplier_id');
		label_row(_("Supplier:"), $supp_name);
	}
	else
	{
		supplier_list_row(_("Supplier:"), 'supplier_id', null, false, true);
		$_POST['price'] = $_POST['suppliers_uom'] = $_POST['conversion_factor'] = $_POST['supplier_description'] = "";
	}
	amount_row(_("Price:"), 'price', null, '', Banking::get_supplier_currency($selected_id), $dec2);
	text_row(_("Suppliers Unit of Measure:"), 'suppliers_uom', null, false, 51);

	if (!isset($_POST['conversion_factor']) || $_POST['conversion_factor'] == "") {
		$_POST['conversion_factor'] = exrate_format(1);
	}
	amount_row(_("Conversion Factor (to our UOM):"), 'conversion_factor',
		exrate_format($_POST['conversion_factor']), null, null, user_exrate_dec());
	text_row(_("Supplier's Product Code:"), 'supplier_description', null, 50, 51);

	end_table(1);

	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	if (Input::request('frame')) {
		end_page(true, true, true);
	} else {
		end_page();
	}
