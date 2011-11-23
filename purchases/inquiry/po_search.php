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
	$page_security = 'SA_SUPPTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 500);
	Page::start(_($help_context = "Search Outstanding Purchase Orders"));
	if (isset($_GET['order_number'])) {
		$_POST['order_number'] = $_GET['order_number'];
	}
	//-----------------------------------------------------------------------------------
	// Ajax updates
	//
	if (get_post('SearchOrders')) {
		$Ajax->activate('orders_tbl');
	} elseif (get_post('_order_number_changed')) {
		$disable = get_post('order_number') !== '';
		$Ajax->addDisable(true, 'OrdersAfterDate', $disable);
		$Ajax->addDisable(true, 'OrdersToDate', $disable);
		$Ajax->addDisable(true, 'StockLocation', $disable);
		$Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
		$Ajax->addDisable(true, 'SelectStockFromList', $disable);
		if ($disable) {
			$Ajax->addFocus(true, 'order_number');
		} else {
			$Ajax->addFocus(true, 'OrdersAfterDate');
		}
		$Ajax->activate('orders_tbl');
	}
	//---------------------------------------------------------------------------------------------
	start_form();
	start_table("class='tablestyle_noborder'");
	start_row();
	supplier_list_cells(_("Select a supplier: "), 'supplier_id', Input::post('supplier_id'), true);
	ref_cells(_("#:"), 'order_number', '', null, '', true);
	date_cells(_("from:"), 'OrdersAfterDate', '', null, -30);
	date_cells(_("to:"), 'OrdersToDate');
	locations_list_cells(_("Location:"), 'StockLocation', null, true);
	//stock_items_list_cells(_("Item:"), 'SelectStockFromList', null, true,false,false,false,true);
	submit_cells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
	end_row();
	end_table();
	//---------------------------------------------------------------------------------------------
	function trans_view($trans)
		{
			return ui_view::get_trans_view_str(ST_PURCHORDER, $trans["order_no"]);
		}

	function edit_link($row)
		{
			return pager_link(_("Edit"), "/purchases/po_entry_items.php?ModifyOrderNumber=" . $row["order_no"], ICON_EDIT);
		}

	function prt_link($row)
		{
			return Reporting::print_doc_link($row['order_no'], _("Print"), true, 18, ICON_PRINT, 'button printlink');
		}

	function receive_link($row)
		{
			return pager_link(_("Receive"), "/purchases/po_receive_items.php?PONumber=" . $row["order_no"], ICON_RECEIVE);
		}

	function check_overdue($row)
		{
			return $row['OverDue'] == 1;
		}

	//---------------------------------------------------------------------------------------------
	if (isset($_POST['order_number']) && ($_POST['order_number'] != "")) {
		$order_number = $_POST['order_number'];
	}
	if (isset($_POST['SelectStockFromList']) && ($_POST['SelectStockFromList'] != "") && ($_POST['SelectStockFromList'] != ALL_TEXT)
	) {
		$selected_stock_item = $_POST['SelectStockFromList'];
	} else {
		unset($selected_stock_item);
	}
	//figure out the sql required from the inputs available
	$sql = "SELECT
	porder.order_no, 
	porder.reference,
	supplier.supp_name,
	 supplier.supplier_id as id,
	location.location_name,
	porder.requisition_no, 
	porder.ord_date,
	supplier.curr_code,
	Sum(line.unit_price*line.quantity_ordered) AS OrderValue,
	Sum(line.delivery_date < '" . Dates::date2sql(Dates::Today()) . "'
	AND (line.quantity_ordered > line.quantity_received)) As OverDue
	FROM purch_orders as porder, purch_order_details as line, suppliers as supplier, locations as location
	WHERE porder.order_no = line.order_no
	AND porder.supplier_id = supplier.supplier_id
	AND location.loc_code = porder.into_stock_location
	AND (line.quantity_ordered > line.quantity_received) ";
	if ($_POST['supplier_id'] != ALL_TEXT) {
		$sql .= " AND supplier.supplier_id = " . DB::escape($_POST['supplier_id'], false, false);
	}
	if (isset($order_number) && $order_number != "") {
		$sql .= "AND porder.reference LIKE " . DB::escape('%' . $order_number . '%', false, false);
	} else {
		$data_after = Dates::date2sql($_POST['OrdersAfterDate']);
		$data_before = Dates::date2sql($_POST['OrdersToDate']);
		$sql .= "  AND porder.ord_date >= '$data_after'";
		$sql .= "  AND porder.ord_date <= '$data_before'";
		if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) {
			$sql .= " AND porder.into_stock_location = " . DB::escape($_POST['StockLocation'], false, false);
		}
		if (isset($selected_stock_item)) {
			$sql .= " AND line.item_code=" . DB::escape($selected_stock_item, false, false);
		}
	} //end not order number selected
	$sql .= " GROUP BY porder.order_no";
	$result = DB::query($sql, "No orders were returned");
	/*show a table of the orders returned by the sql */
	$cols = array(
		_("#") => array(
			'fun' => 'trans_view', 'ord' => ''), _("Reference"), _("Supplier") => array(
			'ord' => '', 'type' => 'id'), _("Supplier ID") => array('skip'), _("Location"), _("Supplier's Reference"), _("Order Date") => array(
			'name' => 'ord_date', 'type' => 'date', 'ord' => 'desc'), _("Currency") => array('align' => 'center'), _("Order Total") => 'amount', array(
			'insert' => true, 'fun' => 'edit_link'), array(
			'insert' => true, 'fun' => 'prt_link'), array(
			'insert' => true, 'fun' => 'receive_link'));
	if (get_post('StockLocation') != ALL_TEXT) {
		$cols[_("Location")] = 'skip';
	}
	$table =& db_pager::new_db_pager('orders_tbl', $sql, $cols);
	$table->set_marker('check_overdue', _("Marked orders have overdue items."));
	$table->width = "80%";
	display_db_pager($table);
	Contacts_Supplier::addInfoDialog('.pagerclick');
	end_form();
	end_page();
?>
