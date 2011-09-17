<?php

  /*     * ********************************************************************
		Copyright (C) FrontAccounting, LLC.
		Released under the terms of the GNU General Public License, GPL,
		as published by the Free Software Foundation, either version 3
		of the License, or (at your option) any later version.
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
		See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
		* ********************************************************************* */
  $path_to_root = "../..";
  include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/db_pager.inc");
  include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");
  include_once(APP_PATH . "/sales/includes/sales_ui.inc");
  include_once(APP_PATH . "/reporting/includes/reporting.inc");
  $page_security = 'SA_SALESTRANSVIEW';
  set_page_security(@$_POST['order_view_mode'],
						  array('OutstandingOnly' => 'SA_SALESDELIVERY', 'InvoiceTemplates' => 'SA_SALESINVOICE'),
						  array('OutstandingOnly' => 'SA_SALESDELIVERY', 'InvoiceTemplates' => 'SA_SALESINVOICE'));
  $js = "";
  if (Config::get('ui.windows.popups')) {
	 $js .= get_js_open_window(900, 600);
  }

  if (AJAX_REFERRER && !empty($_POST['ajaxsearch'])) {
	 $searchArray = explode(' ', $_POST['ajaxsearch']);
  }
  if (isset($searchArray) && $searchArray[0] == 'o') {
	 $trans_type = ST_SALESORDER;
  }
  elseif (isset($searchArray) && $searchArray[0] == 'q') {
	 $trans_type = ST_SALESQUOTE;
  }
  elseif (isset($searchArray)) {
	 $trans_type = ST_SALESORDER;
  }
  elseif (get_post('type')) {
	 $trans_type = $_POST['type'];
  }
  elseif (isset($_GET['type']) && ($_GET['type'] == ST_SALESQUOTE)) {
	 $trans_type = ST_SALESQUOTE;
  }
  else {
	 $trans_type = ST_SALESORDER;
  }
  if ($trans_type == ST_SALESORDER) {
	 if (isset($_GET['OutstandingOnly']) && ($_GET['OutstandingOnly'] == true)) {
		$_POST['order_view_mode'] = 'OutstandingOnly';
		$_SESSION['page_title'] = _($help_context = "Search Outstanding Sales Orders");
	 }
	 elseif (isset($_GET['InvoiceTemplates']) && ($_GET['InvoiceTemplates'] == true)) {
		$_POST['order_view_mode'] = 'InvoiceTemplates';
		$_SESSION['page_title'] = _($help_context = "Search Template for Invoicing");
	 }
	 elseif (isset($_GET['DeliveryTemplates']) && ($_GET['DeliveryTemplates'] == true)) {
		$_POST['order_view_mode'] = 'DeliveryTemplates';
		$_SESSION['page_title'] = _($help_context = "Select Template for Delivery");
	 }
	 elseif (!isset($_POST['order_view_mode'])) {
		$_POST['order_view_mode'] = false;
		$_SESSION['page_title'] = _($help_context = "Search All Sales Orders");
	 }
  }
  else {
	 $_POST['order_view_mode'] = "Quotations";
	 $_SESSION['page_title'] = _($help_context = "Search All Sales Quotations");
  }
  page($_SESSION['page_title'], false, false, "", $js);
  if (isset($_GET['selected_customer'])) {
	 $selected_customer = $_GET['selected_customer'];
  }
  elseif (isset($_POST['selected_customer'])) {
	 $selected_customer = $_POST['selected_customer'];
  }
  else {
	 $selected_customer = -1;
  }
  //---------------------------------------------------------------------------------------------
  if (isset($_POST['SelectStockFromList']) && ($_POST['SelectStockFromList'] != "") && (
	$_POST['SelectStockFromList'] != ALL_TEXT)
  ) {
	 $selected_stock_item = $_POST['SelectStockFromList'];
  }
  else {
	 unset($selected_stock_item);
  }
  //---------------------------------------------------------------------------------------------
  //	Query format functions
  //
  function check_overdue($row) {
	 global $trans_type;
	 if ($trans_type == ST_SALESQUOTE) {
		return (date1_greater_date2(Today(), sql2date($row['delivery_date'])));
	 }
	 else {
		return ($row['type'] == 0 && date1_greater_date2(Today(), sql2date($row['ord_date'])) && (
		 $row['TotDelivered'] < $row['TotQuantity']));
	 }
  }

  function view_link($row, $order_no) {
	 global $trans_type;
	 return get_customer_trans_view_str($row['trans_type'], $order_no);
  }

  function prt_link($row) {

	 return print_document_link($row['order_no'], _("Print"), true, $row['trans_type'], ICON_PRINT, 'button');
  }

  function prt_link2($row) {
	 return print_document_link($row['order_no'], _("Proforma"), true, ST_PROFORMA, ICON_PRINT, 'button');
  }

  function edit_link($row) {
	 $modify = ($row['trans_type'] == ST_SALESORDER ? "ModifyOrderNumber" : "ModifyQuotationNumber");
	 return pager_link(_("Edit"), "/sales/sales_order_entry.php?$modify=" . $row['order_no'], ICON_EDIT);
  }

  function email_link($row) {
	 return UI::emailDialogue('c', $row['debtor_no'] . '-' . $row['trans_type'] . '-' . $row['order_no']);
  }

  function dispatch_link($row) {
	 if ($row['trans_type'] == ST_SALESORDER) {
		return pager_link(_("Dispatch"), "/sales/customer_delivery.php?OrderNumber=" . $row['order_no'], ICON_DOC);
	 }
	 else {
		return pager_link(_("Sales Order"), "/sales/sales_order_entry.php?OrderNumber=" . $row['order_no'], ICON_DOC);
	 }
  }

  function invoice_link($row) {
	 if ($row['trans_type'] == ST_SALESORDER) {
		return pager_link(_("Invoice"), "/sales/sales_order_entry.php?NewInvoice=" . $row["order_no"], ICON_DOC);
	 }
	 else {
		return '';
	 }
  }

  function delivery_link($row) {
	 return pager_link(_("Delivery"), "/sales/sales_order_entry.php?NewDelivery=" . $row['order_no'], ICON_DOC);
  }

  function order_link($row) {
	 return pager_link(_("Create Order"), "/sales/sales_order_entry.php?NewQuoteToSalesOrder=" .
													  $row['order_no'], ICON_DOC);
  }

  function tmpl_checkbox($row) {
	 global $trans_type;
	 if ($trans_type == ST_SALESQUOTE) {
		return '';
	 }
	 $name = "chgtpl" . $row['order_no'];
	 $value = $row['type'] ? 1 : 0;
	 // save also in hidden field for testing during 'Update'
	 return checkbox(null, $name, $value, true, _('Set this order as a template for direct deliveries/invoices')) . hidden('last[' .
																																								  $row
																																								  ['order_no'] . ']', $value, false);
  }

  //---------------------------------------------------------------------------------------------
  // Update db record if respective checkbox value has changed.
  //
  function change_tpl_flag($id) {
	 global $Ajax;
	 $sql = "UPDATE sales_orders SET type = !type WHERE order_no=$id";
	 db_query($sql, "Can't change sales order type");
	 $Ajax->activate('orders_tbl');
  }

  $id = find_submit('_chgtpl');
  if ($id != -1) {
	 change_tpl_flag($id);
  }
  if (isset($_POST['Update']) && isset($_POST['last'])) {
	 foreach ($_POST['last'] as $id => $value)
		if ($value != check_value('chgtpl' . $id)) {
		  change_tpl_flag($id);
		}
  }
  //---------------------------------------------------------------------------------------------
  //	Order range form
  //
  if (get_post('_OrderNumber_changed')) { // enable/disable selection controls
	 $disable = get_post('OrderNumber') !== '';
	 if ($_POST['order_view_mode'] != 'DeliveryTemplates' && $_POST['order_view_mode'] != 'InvoiceTemplates') {
		$Ajax->addDisable(true, 'OrdersAfterDate', $disable);
		$Ajax->addDisable(true, 'OrdersToDate', $disable);
	 }
	 $Ajax->addDisable(true, 'StockLocation', $disable);
	 $Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
	 $Ajax->addDisable(true, 'SelectStockFromList', $disable);
	 if ($disable) {
		$Ajax->addFocus(true, 'OrderNumber');
	 }
	 else {
		$Ajax->addFocus(true, 'OrdersAfterDate');
	 }
	 $Ajax->activate('orders_tbl');
  }
  start_form();
  start_table("class='tablestyle_noborder'");
  start_row();
  customer_list_cells(_("Customer: "), 'customer_id', null, true);
  ref_cells(_("#:"), 'OrderNumber', '', null, '', true);
  if ($_POST['order_view_mode'] != 'DeliveryTemplates' && $_POST['order_view_mode'] != 'InvoiceTemplates') {
	 ref_cells(_("Ref"), 'OrderReference', '', null, '', true);
	 date_cells(_("From:"), 'OrdersAfterDate', '', null, -30);
	 date_cells(_("To:"), 'OrdersToDate', '', null, 1);
  }
  locations_list_cells(_("Location:"), 'StockLocation', null, true);
  stock_items_list_cells(_("Item:"), 'SelectStockFromList', null, true);
  if ($trans_type == ST_SALESQUOTE) {
	 check_cells(_("Show All:"), 'show_all');
  }
  submit_cells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
  hidden('order_view_mode', $_POST['order_view_mode']);
  hidden('type', $trans_type);
  end_row();
  end_table(1);
  //---------------------------------------------------------------------------------------------
  //	Orders inquiry table
  //
  $sql = "SELECT
		sorder.order_no,
		sorder.trans_type,
		sorder.reference,
		debtor.name,
		debtor.debtor_no,
		branch.br_name," . ($_POST['order_view_mode'] == 'InvoiceTemplates' ||
								  $_POST['order_view_mode'] == 'DeliveryTemplates' ? "sorder.comments, "
	: "sorder.customer_ref, ") . "sorder.ord_date,
		sorder.delivery_date,
		sorder.deliver_to,
		Sum(line.unit_price*line.quantity*(1-line.discount_percent))+freight_cost AS OrderValue,
		sorder.type,
		debtor.curr_code,
		Sum(line.qty_sent) AS TotDelivered,
		Sum(line.quantity) AS TotQuantity
	FROM sales_orders as sorder, sales_order_details as line, debtors_master as debtor, cust_branch as branch
		WHERE sorder.order_no = line.order_no
		AND sorder.trans_type = line.trans_type";
  if (isset($searchArray) && $searchArray[0] == 'o') {
	 $sql .= " AND sorder.trans_type = 30 ";
  }
  elseif (isset($searchArray) && $searchArray[0] == 'q') {
	 $sql .= " AND sorder.trans_type = 32 ";
  }
  elseif (isset($searchArray)) {
	 $sql .= " AND ( sorder.trans_type = 30 OR sorder.trans_type = 32) ";
  }
  else {
	 $sql .= " AND sorder.trans_type = " . $trans_type;
  }
  $sql .= " AND sorder.debtor_no = debtor.debtor_no
		AND sorder.branch_code = branch.branch_code
		AND debtor.debtor_no = branch.debtor_no";
  if ($_POST['customer_id'] != ALL_TEXT) {
	 $sql .= " AND sorder.debtor_no = " . db_escape($_POST['customer_id']);
  }
  if (isset($_POST['OrderNumber']) && $_POST['OrderNumber'] != "") {
	 // search orders with number like
	 $number_like = "%" . $_POST['OrderNumber'];
	 $sql .= " AND sorder.order_no LIKE " . db_escape($number_like) . " GROUP BY sorder.order_no";
  }
  elseif (isset($_POST['OrderReference']) && $_POST['OrderReference'] != "") {
	 // search orders with reference like
	 $number_like = "%" . $_POST['OrderReference'] . "%";
	 $sql .= " AND sorder.reference LIKE " . db_escape($number_like) . " GROUP BY sorder.order_no";
  }
  elseif (AJAX_REFERRER && !empty($_POST['ajaxsearch'])) {
	 foreach ($searchArray as $ajaxsearch) {
		if (empty($ajaxsearch)) {
		  continue;
		}
		$ajaxsearch = db_escape("%" . trim($ajaxsearch) . "%");
		$sql .= " AND (debtor.name LIKE $ajaxsearch OR sorder.order_no LIKE $ajaxsearch
			OR sorder.reference LIKE $ajaxsearch  OR sorder.contact_name LIKE $ajaxsearch
			OR sorder.customer_ref LIKE $ajaxsearch
			 OR sorder.customer_ref LIKE $ajaxsearch OR branch.br_name LIKE $ajaxsearch)";
	 }
	 $sql .= " GROUP BY sorder.ord_date,
        sorder.order_no,
				sorder.debtor_no,
				sorder.branch_code,
				sorder.customer_ref,
				sorder.deliver_to";
  }
  else { // ... or select inquiry constraints
	 if ($_POST['order_view_mode'] != 'DeliveryTemplates' && $_POST['order_view_mode'] != 'InvoiceTemplates' && !isset(
	 $_POST['ajaxsearch'])
	 ) {
		$date_after = date2sql($_POST['OrdersAfterDate']);
		$date_before = date2sql($_POST['OrdersToDate']);
		$sql .= " AND sorder.ord_date >= '$date_after'" . " AND sorder.ord_date <= '$date_before'";
	 }
	 if ($trans_type == 32 && !check_value('show_all')) {
		$sql .= " AND sorder.delivery_date >= '" . date2sql(Today()) . "'";
	 }
	 if ($selected_customer != -1) {
		$sql .= " AND sorder.debtor_no=" . db_escape($selected_customer);
	 }
	 if (isset($selected_stock_item)) {
		$sql .= " AND line.stk_code=" . db_escape($selected_stock_item);
	 }
	 if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) {
		$sql .= " AND sorder.from_stk_loc = " . db_escape($_POST['StockLocation']);
	 }
	 if ($_POST['order_view_mode'] == 'OutstandingOnly') {
		$sql .= " AND line.qty_sent < line.quantity";
	 }
	 elseif (
		$_POST['order_view_mode'] == 'InvoiceTemplates' || $_POST['order_view_mode'] == 'DeliveryTemplates'
	 )
	 {
		$sql .= " AND sorder.type=1";
	 }
	 $sql .= " GROUP BY sorder.ord_date,
        sorder.order_no,
				sorder.debtor_no,
				sorder.branch_code,
				sorder.customer_ref,
				sorder.deliver_to";
  }
  if ($trans_type == ST_SALESORDER) {
	 $cols = array(_("Order #") => array('fun' => 'view_link', 'ord' => 'desc'),
						array('type' => 'skip'),
						_("Ref") => array('ord' => ''),
						_("Customer") => array('ord' => ''),
						array('type' => 'skip'),
						_("Branch") => array('ord' => ''),
						_("Customer PO#") => array('ord' => ''),
						_("Order Date") => array('type' => 'date', 'ord' => ''),
						_("Required By") => array('type' => 'date', 'ord' => ''),
						_("Delivery To"),
						_("Order Total") => array('type' => 'amount', 'ord' => ''),
						'type' => 'skip',
						_("Currency") => array('align' => 'center'));
  }
  else {
	 $cols = array(
		_("Quote #") => array('fun' => 'view_link', 'ord' => 'desc'),
		array('type' => 'skip'),
		_("Ref") => array('ord' => ''),
		_("Customer") => array('ord' => ''),
		array('type' => 'skip'),
		_("Branch") => array('ord' => ''),
		_("Customer PO#") => array('ord' => ''),
		_("Quote Date") => array('type' => 'date', 'ord' => ''),
		_("Valid until") => array('type' => 'date', 'ord' => ''),
		_("Delivery To"),
		_("Quote Total") => array('type' => 'amount', 'ord' => ''),
		'type' => 'skip',
		_("Currency") => array('align' => 'center'));

  }
  if ($_POST['order_view_mode'] == 'OutstandingOnly') {
	 //array_substitute($cols, 3, 1, _("Cust Order Ref"));
	 array_append($cols, array(array('insert' => true, 'fun' => 'dispatch_link')));
  }
  elseif ($_POST['order_view_mode'] == 'InvoiceTemplates') {
	 array_substitute($cols, 3, 1, _("Description"));
	 array_append($cols, array(array('insert' => true, 'fun' => 'invoice_link')));
  }
  else {
	 if ($_POST['order_view_mode'] == 'DeliveryTemplates') {
		array_substitute($cols, 3, 1, _("Description"));
		array_append($cols, array(array('insert' => true, 'fun' => 'delivery_link')));
	 }
	 elseif ($trans_type == ST_SALESQUOTE) {
		array_append($cols,
						 array(array('insert' => true, 'type' => 'skip'),
								array('insert' => true, 'type' => 'skip'),
								array('insert' => true, 'fun' => 'edit_link'),
								array('insert' => true, 'fun' => 'order_link'),
								array('insert' => true, 'fun' => 'email_link'),
								array('insert' => true, 'fun' => 'prt_link'))
		);
	 }
	 elseif ($trans_type == ST_SALESORDER) {
		array_append($cols,
						 array(
								_("Tmpl") => array('type' => 'skip', 'insert' => true, 'fun' => 'tmpl_checkbox'),
								array('insert' => true, 'fun' => 'edit_link'),
								array('insert' => true, 'fun' => 'email_link'),
								array('insert' => true, 'fun' => 'prt_link2'),
								array('insert' => true, 'fun' => 'prt_link')));
	 }
  }
  ;
  $table = & new_db_pager('orders_tbl', $sql, $cols, null, null, 0, _("Order #"));
  $table->set_marker('check_overdue', _("Marked items are overdue."));
  $table->width = "80%";
  display_db_pager($table);
  submit_center('Update', _("Update"), true, '', null);
  end_form();
  end_page();
?>