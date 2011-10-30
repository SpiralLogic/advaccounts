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
	$page_security = 'SA_SUPPTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	include(APP_PATH . "purchasing/includes/purchasing_ui.php");
	JS::get_js_open_window(900, 500);
	Page::start(_($help_context = "Search Purchase Orders"), Input::request('frame'));
	if (isset($_GET['order_number'])) {
		$order_number = $_GET['order_number'];
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
	if (Input::request('frame')) {
		start_table("class='tablestyle_noborder' style='display:none;'");
	} else {
		start_table("class='tablestyle_noborder'");
	}
	start_row();
	ref_cells(_("#:"), 'order_number', '', null, '', true);
	date_cells(_("from:"), 'OrdersAfterDate', '', null, -30);
	date_cells(_("to:"), 'OrdersToDate');
	locations_list_cells(_("into location:"), 'StockLocation', null, true);
	stock_items_list_cells(_("for item:"), 'SelectStockFromList', null, true);
	submit_cells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
	end_row();
	end_table();
	//---------------------------------------------------------------------------------------------
	if (isset($_POST['order_number'])) {
		$order_number = $_POST['order_number'];
	}
	if (isset($_POST['SelectStockFromList']) && ($_POST['SelectStockFromList'] != "")
	 && (
		$_POST['SelectStockFromList'] != ALL_TEXT)
	) {
		$selected_stock_item = $_POST['SelectStockFromList'];
	} else {
		unset($selected_stock_item);
	}
	//---------------------------------------------------------------------------------------------
	function trans_view($trans)
	{
		return ui_view::get_trans_view_str(ST_PURCHORDER, $trans["order_no"]);
	}

	function edit_link($row)
	{
		return pager_link(
			_("Edit"), "/purchasing/po_entry_items.php?" . SID . "ModifyOrderNumber=" .
		 $row["order_no"], ICON_EDIT
		);
	}

	function prt_link($row)
	{
		return Reporting::print_doc_link($row['order_no'], _("Print"), true, 18, ICON_PRINT, 'button');
	}

	function receive_link($row)
	{
		if ($row['Received'] > 0) {
			return pager_link(_("Receive"), "/purchasing/po_receive_items.php?PONumber=" . $row["order_no"], ICON_RECEIVE);
		}
		elseif ($row['Invoiced'] > 0) {
			return pager_link(_("Invoice"), "/purchasing/supplier_invoice.php?New=1&SuppID=" . $row['supplier_id'] . "&PONumber=" . $row["order_no"], ICON_RECEIVE);
		}
		//advaccounts/purchasing/supplier_invoice.php?New=1
	}

	//---------------------------------------------------------------------------------------------
	if (AJAX_REFERRER && !empty($_POST['ajaxsearch'])) {
		$searchArray = explode(' ', $_POST['ajaxsearch']);
		unset($_POST['supplier_id']);
	}
	$sql
	 = "SELECT
	porder.order_no, 
	porder.reference, 
	supplier.supp_name,
	supplier.supplier_id as id,
	location.location_name,
	porder.requisition_no, 
	porder.ord_date, 
	supplier.curr_code, 
	Sum(line.unit_price*line.quantity_ordered)+porder.freight AS OrderValue,
	Sum(line.quantity_ordered - line.quantity_received) AS Received,
	Sum(line.quantity_received - line.qty_invoiced) AS Invoiced,
	porder.into_stock_location, supplier.supplier_id
	FROM purch_orders as porder, purch_order_details as line, suppliers as supplier, locations as location
	WHERE porder.order_no = line.order_no
	AND porder.supplier_id = supplier.supplier_id
	AND location.loc_code = porder.into_stock_location ";
	if (AJAX_REFERRER && !empty($_POST['ajaxsearch'])) {
		foreach (
			$searchArray as $ajaxsearch
		) {
			if (empty($ajaxsearch)) {
				continue;
			}
			$ajaxsearch = DBOld::escape("%" . $ajaxsearch . "%");
			$sql
			 .= " AND (supplier.supp_name LIKE $ajaxsearch OR porder.order_no LIKE $ajaxsearch
		 OR porder.reference LIKE $ajaxsearch
		  OR porder.requisition_no LIKE $ajaxsearch
		   OR location.location_name LIKE $ajaxsearch)";
		}
	} elseif (isset($order_number) && $order_number != "") {
		$sql .= "AND porder.reference LIKE " . DBOld::escape('%' . $order_number . '%');
	} else {
		$data_after  = Dates::date2sql($_POST['OrdersAfterDate']);
		$date_before = Dates::date2sql($_POST['OrdersToDate']);
		$sql .= " AND porder.ord_date >= '$data_after'";
		$sql .= " AND porder.ord_date <= '$date_before'";
		if ((isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) || isset($_GET['NFY'])) {
			$sql .= " AND porder.into_stock_location = ";
			$sql .= (!$_GET['NFY'] == 1) ? DBOld::escape($_POST['StockLocation']) : DBOld::escape('NFY');
		}
		if (isset($selected_stock_item)) {
			$sql .= " AND line.item_code=" . DBOld::escape($selected_stock_item);
		}
	} //end not order number selected
	$sql .= " GROUP BY porder.order_no";
	$cols = array(
		_("#")					 => array(
			'fun' => 'trans_view',
			'ord' => ''
		),
		_("Reference"),
		_("Supplier")		=> array(
			'ord'	=> '',
			'type' => 'id'
		),
		_("Supplier ID") => 'skip',
		_("Location"),
		_("Supplier's Reference"),
		_("Order Date")	=> array(
			'name' => 'ord_date',
			'type' => 'date',
			'ord'	=> 'desc'
		),
		_("Currency")		=> array('align' => 'center'),
		_("Order Total") => 'amount',
		array(
			'insert' => true,
			'fun'		=> 'edit_link'
		),
		array(
			'insert' => true,
			'fun'		=> 'prt_link'
		),
		array(
			'insert' => true,
			'fun'		=> 'receive_link'
		),
	);
	if (get_post('StockLocation') != ALL_TEXT) {
		$cols[_("Location")] = 'skip';
	}
	//---------------------------------------------------------------------------------------------------
	$table =& db_pager::new_db_pager('orders_tbl', $sql, $cols);
	$table->width = "80%";
	display_db_pager($table);
	Supplier::addInfoDialog('.pagerclick');
	end_form();
	end_page();
?>
