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
	$page_security = 'SA_INVENTORYADJUSTMENT';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	include_once(APP_PATH . "inventory/includes/item_adjustments_ui.php");
	JS::open_window(800, 500);
	Page::start(_($help_context = "Item Stocktake Note"));
	//-----------------------------------------------------------------------------------------------
	Validation::check(Validation::COST_ITEMS, _("There are no inventory items defined in the system which can be adjusted (Purchased or Manufactured)."), STOCK_SERVICE);
	Validation::check(Validation::MOVEMENT_TYPES, _("There are no inventory movement types defined in the system. Please define at least one inventory adjustment type."));
	//-----------------------------------------------------------------------------------------------
	if (isset($_GET['AddedID'])) {
		$trans_no = $_GET['AddedID'];
		$trans_type = ST_INVADJUST;
		Errors::notice(_("Items adjustment has been processed"));
		Display::note(ui_view::get_trans_view_str($trans_type, $trans_no, _("&View this adjustment")));
		Display::note(ui_view::get_gl_view_str($trans_type, $trans_no, _("View the GL &Postings for this Adjustment")), 1, 0);
		hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another Adjustment"));
		Page::footer_exit();
	}
	//--------------------------------------------------------------------------------------------------
	function line_start_focus()
	{
		$Ajax = Ajax::instance();
		$Ajax->activate('items_table');
		JS::set_focus('_stock_id_edit');
	}

	//-----------------------------------------------------------------------------------------------
	function handle_new_order()
	{
		if (isset($_SESSION['adj_items'])) {
			$_SESSION['adj_items']->clear_items();
			unset ($_SESSION['adj_items']);
		}
		//session_register("adj_items");
		$_SESSION['adj_items'] = new Item_Cart(ST_INVADJUST);
		$_POST['AdjDate'] = Dates::new_doc_date();
		if (!Dates::is_date_in_fiscalyear($_POST['AdjDate'])) {
			$_POST['AdjDate'] = Dates::end_fiscalyear();
		}
		$_SESSION['adj_items']->tran_date = $_POST['AdjDate'];
	}

	//-----------------------------------------------------------------------------------------------
	function can_process()
	{
		$adj = &$_SESSION['adj_items'];
		if (count($adj->line_items) == 0) {
			Errors::error(_("You must enter at least one non empty item line."));
			JS::set_focus('stock_id');
			return false;
		}
		if (!Refs::is_valid($_POST['ref'])) {
			Errors::error(_("You must enter a reference."));
			JS::set_focus('ref');
			return false;
		}
		if (!is_new_reference($_POST['ref'], ST_INVADJUST)) {
			Errors::error(_("The entered reference is already in use."));
			JS::set_focus('ref');
			return false;
		}
		if (!Dates::is_date($_POST['AdjDate'])) {
			Errors::error(_("The entered date for the adjustment is invalid."));
			JS::set_focus('AdjDate');
			return false;
		} elseif (!Dates::is_date_in_fiscalyear($_POST['AdjDate'])) {
			Errors::error(_("The entered date is not in fiscal year."));
			JS::set_focus('AdjDate');
			return false;
		}
		return true;
	}

	//-------------------------------------------------------------------------------
	if (isset($_POST['Process']) && can_process()) {
		foreach ($_SESSION['adj_items']->line_items as $line) {
			$item = new Item($line->stock_id);
			$current_stock = $item->getStockLevels($_POST['StockLocation']);
			$line->quantity -= $current_stock['qty'];
		}
		$trans_no = Inv_Adjustment::add($_SESSION['adj_items']->line_items, $_POST['StockLocation'], $_POST['AdjDate'], $_POST['type'], $_POST['Increase'], $_POST['ref'], $_POST['memo_']);
		Dates::new_doc_date($_POST['AdjDate']);
		$_SESSION['adj_items']->clear_items();
		unset($_SESSION['adj_items']);
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
	} /*end of process credit note */
	//-----------------------------------------------------------------------------------------------
	function check_item_data()
	{
		if (!Validation::is_num('qty', 0)) {
			Errors::error(_("The quantity entered is negative or invalid."));
			JS::set_focus('qty');
			return false;
		}
		if (!Validation::is_num('std_cost', 0)) {
			Errors::error(_("The entered standard cost is negative or invalid."));
			JS::set_focus('std_cost');
			return false;
		}
		return true;
	}

	//-----------------------------------------------------------------------------------------------
	function handle_update_item()
	{
		if ($_POST['UpdateItem'] != "" && check_item_data()) {
			$id = $_POST['LineNo'];
			$_SESSION['adj_items']->update_cart_item($id, input_num('qty'), input_num('std_cost'));
		}
		line_start_focus();
	}

	//-----------------------------------------------------------------------------------------------
	function handle_delete_item($id)
	{
		$_SESSION['adj_items']->remove_from_cart($id);
		line_start_focus();
	}

	//-----------------------------------------------------------------------------------------------
	function handle_new_item()
	{
		if (!check_item_data()) {
			return;
		}
		Item_Cart::add_line($_SESSION['adj_items'], $_POST['stock_id'], input_num('qty'), input_num('std_cost'));
		line_start_focus();
	}

	//-----------------------------------------------------------------------------------------------
	$id = find_submit('Delete');
	if ($id != -1) {
		handle_delete_item($id);
	}
	if (isset($_POST['AddItem'])) {
		handle_new_item();
	}
	if (isset($_POST['UpdateItem'])) {
		handle_update_item();
	}
	if (isset($_POST['CancelItemChanges'])) {
		line_start_focus();
	}
	//-----------------------------------------------------------------------------------------------
	if (isset($_GET['NewAdjustment']) || !isset($_SESSION['adj_items'])) {
		handle_new_order();
	}
	//-----------------------------------------------------------------------------------------------
	start_form();
	display_order_header($_SESSION['adj_items']);
	start_outer_table(Config::get('tables_style') . "  width=80%", 10);
	display_adjustment_items(_("Adjustment Items"), $_SESSION['adj_items']);
	adjustment_options_controls();
	end_outer_table(1, false);
	submit_center_first('Update', _("Update"), '', null);
	submit_center_last('Process', _("Process Adjustment"), '', 'default');
	end_form();
	end_page();

?>
