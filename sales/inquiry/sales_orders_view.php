<?php

  /* * ********************************************************************
          Copyright (C) Advanced Group PTY LTD
          Released under the terms of the GNU General Public License, GPL,
          as published by the Free Software Foundation, either version 3
          of the License, or (at your option) any later version.
          This program is distributed in the hope that it will be useful,
          but WITHOUT ANY WARRANTY; without even the implied warranty of
          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
          See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
          * ********************************************************************* */
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

  Security::set_page(Input::post('order_view_mode'), array(
    'OutstandingOnly' => SA_SALESDELIVERY, 'InvoiceTemplates' => SA_SALESINVOICE
  ), array(
    'OutstandingOnly' => SA_SALESDELIVERY, 'InvoiceTemplates' => SA_SALESINVOICE
  ));
  JS::open_window(900, 600);
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
    if (Input::get('OutstandingOnly')) {
      $_POST['order_view_mode'] = 'OutstandingOnly';
      Session::i()->page_title = _($help_context = "Search Outstanding Sales Orders");
    }
    elseif (isset($_GET['InvoiceTemplates']) && ($_GET['InvoiceTemplates'] == TRUE)) {
      $_POST['order_view_mode'] = 'InvoiceTemplates';
      Session::i()->page_title = _($help_context = "Search Template for Invoicing");
    }
    elseif (isset($_GET['DeliveryTemplates']) && ($_GET['DeliveryTemplates'] == TRUE)) {
      $_POST['order_view_mode'] = 'DeliveryTemplates';
      Session::i()->page_title = _($help_context = "Select Template for Delivery");
    }
    elseif (!isset($_POST['order_view_mode'])) {
      $_POST['order_view_mode'] = FALSE;
      Session::i()->page_title = _($help_context = "Search All Sales Orders");
    }
  }
  else {
    $_POST['order_view_mode'] = "Quotations";
    Session::i()->page_title = _($help_context = "Search All Sales Quotations");
  }
  Page::start(Session::i()->page_title);
  if (isset($_GET['selected_customer'])) {
    $selected_customer = $_GET['selected_customer'];
  }
  elseif (isset($_POST['selected_customer'])) {
    $selected_customer = $_POST['selected_customer'];
  }
  else {
    $selected_customer = -1;
  }
  if (isset($_POST['SelectStockFromList']) && ($_POST['SelectStockFromList'] != "") && ($_POST['SelectStockFromList'] != ALL_TEXT)
  ) {
    $selected_stock_item = $_POST['SelectStockFromList'];
  }
  else {
    unset($selected_stock_item);
  }

  $id = find_submit('_chgtpl');
  if ($id != -1) {
    change_tpl_flag($id);
  }
  if (isset($_POST['Update']) && isset($_POST['last'])) {
    foreach ($_POST['last'] as $id => $value) {
      if ($value != check_value('chgtpl' . $id)) {
        change_tpl_flag($id);
      }
    }
  }
  //	Order range form
  //
  if (get_post('_OrderNumber_changed')) { // enable/disable selection controls
    $disable = get_post('OrderNumber') !== '';
    if ($_POST['order_view_mode'] != 'DeliveryTemplates' && $_POST['order_view_mode'] != 'InvoiceTemplates') {
      Ajax::i()->addDisable(TRUE, 'OrdersAfterDate', $disable);
      Ajax::i()->addDisable(TRUE, 'OrdersToDate', $disable);
    }
    Ajax::i()->addDisable(TRUE, 'StockLocation', $disable);
    Ajax::i()->addDisable(TRUE, '_SelectStockFromList_edit', $disable);
    Ajax::i()->addDisable(TRUE, 'SelectStockFromList', $disable);
    if ($disable) {
      Ajax::i()->addFocus(TRUE, 'OrderNumber');
    }
    else {
      Ajax::i()->addFocus(TRUE, 'OrdersAfterDate');
    }
    Ajax::i()->activate('orders_tbl');
  }
  start_form();
  start_table('tablestyle_noborder');
  start_row();
  Debtor::cells(_("Customer: "), 'customer_id', NULL, TRUE);
  ref_cells(_("#:"), 'OrderNumber', '', NULL, '', TRUE);
  if ($_POST['order_view_mode'] != 'DeliveryTemplates' && $_POST['order_view_mode'] != 'InvoiceTemplates') {
    ref_cells(_("Ref"), 'OrderReference', '', NULL, '', TRUE);
    date_cells(_("From:"), 'OrdersAfterDate', '', NULL, -30);
    date_cells(_("To:"), 'OrdersToDate', '', NULL, 1);
  }
  Inv_Location::cells(_("Location:"), 'StockLocation', NULL, TRUE);
  Item::cells(_("Item:"), 'SelectStockFromList', NULL, TRUE);
  if ($trans_type == ST_SALESQUOTE) {
    check_cells(_("Show All:"), 'show_all');
  }
  submit_cells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
  end_row();
  end_table(1);

  hidden('order_view_mode', $_POST['order_view_mode']);
  hidden('type', $trans_type);
  //	Orders inquiry table
  //
  $sql = "SELECT
		sorder.trans_type,
		sorder.order_no,
		sorder.reference," . ($_POST['order_view_mode'] == 'InvoiceTemplates' || $_POST['order_view_mode'] == 'DeliveryTemplates' ?
    "sorder.comments, " : "sorder.customer_ref, ") . "
		sorder.ord_date,
		sorder.delivery_date,
		debtor.name,
		debtor.debtor_no,
		branch.br_name,
		sorder.deliver_to,
		Sum(line.unit_price*line.quantity*(1-line.discount_percent))+freight_cost AS OrderValue,
		sorder.type,
		debtor.curr_code,
		Sum(line.qty_sent) AS TotDelivered,
		Sum(line.quantity) AS TotQuantity
	FROM sales_orders as sorder, sales_order_details as line, debtors as debtor, branches as branch
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
		AND sorder.branch_id = branch.branch_id
		AND debtor.debtor_no = branch.debtor_no";
  if ($_POST['customer_id'] != ALL_TEXT) {
    $sql .= " AND sorder.debtor_no = " . DB::quote($_POST['customer_id']);
  }
  if (isset($_POST['OrderNumber']) && $_POST['OrderNumber'] != "") {
    // search orders with number like
    $number_like = "%" . $_POST['OrderNumber'];
    $sql .= " AND sorder.order_no LIKE " . DB::quote($number_like) . " GROUP BY sorder.order_no";
  }
  elseif (isset($_POST['OrderReference']) && $_POST['OrderReference'] != "") {
    // search orders with reference like
    $number_like = "%" . $_POST['OrderReference'] . "%";
    $sql .= " AND sorder.reference LIKE " . DB::quote($number_like) . " GROUP BY sorder.order_no";
  }
  elseif (AJAX_REFERRER && !empty($_POST['ajaxsearch'])) {
    foreach ($searchArray as $ajaxsearch) {
      if (empty($ajaxsearch)) {
        continue;
      }
      $ajaxsearch = DB::quote("%" . trim($ajaxsearch) . "%");
      $sql .= " AND ( debtor.debtor_no = $ajaxsearch OR debtor.name LIKE $ajaxsearch OR sorder.order_no LIKE $ajaxsearch
			OR sorder.reference LIKE $ajaxsearch OR sorder.contact_name LIKE $ajaxsearch
			OR sorder.customer_ref LIKE $ajaxsearch
			 OR sorder.customer_ref LIKE $ajaxsearch OR branch.br_name LIKE $ajaxsearch)";
    }
    $sql .= " GROUP BY sorder.ord_date,
				 sorder.order_no,
				sorder.debtor_no,
				sorder.branch_id,
				sorder.customer_ref,
				sorder.deliver_to";
  }
  else { // ... or select inquiry constraints
    if ($_POST['order_view_mode'] != 'DeliveryTemplates' && $_POST['order_view_mode'] != 'InvoiceTemplates' && !isset($_POST['ajaxsearch'])
    ) {
      $date_after = Dates::date2sql($_POST['OrdersAfterDate']);
      $date_before = Dates::date2sql($_POST['OrdersToDate']);
      $sql .= " AND sorder.ord_date >= '$date_after' AND sorder.ord_date <= '$date_before'";
    }
    if ($trans_type == 32 && !check_value('show_all')) {
      $sql .= " AND sorder.delivery_date >= '" . Dates::date2sql(Dates::today()) . "'";
    }
    if ($selected_customer != -1) {
      $sql .= " AND sorder.debtor_no=" . DB::quote($selected_customer);
    }
    if (isset($selected_stock_item)) {
      $sql .= " AND line.stk_code=" . DB::quote($selected_stock_item);
    }
    if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) {
      $sql .= " AND sorder.from_stk_loc = " . DB::quote($_POST['StockLocation']);
    }
    if ($_POST['order_view_mode'] == 'OutstandingOnly') {
      $sql .= " AND line.qty_sent < line.quantity";
    }
    elseif ($_POST['order_view_mode'] == 'InvoiceTemplates' || $_POST['order_view_mode'] == 'DeliveryTemplates'
    ) {
      $sql .= " AND sorder.type=1";
    }
    $sql .= " GROUP BY sorder.ord_date,
 sorder.order_no,
				sorder.debtor_no,
				sorder.branch_id,
				sorder.customer_ref,
				sorder.deliver_to";
  }
  $ord = NULL;
  if ($trans_type == ST_SALESORDER) {
    $ord = "Order #";
    $cols = array(
      array('type' => 'skip'),
      _("Order #") => array('fun' => 'view_link', 'ord' => ''),
      _("Ref") => array('ord' => ''),
      _("PO#") => array('ord' => ''),
      _("Date") => array('type' => 'date', 'ord' => 'desc'),
      _("Required By") => array('type' => 'date', 'ord' => ''),
      _("Customer") => array('ord' => 'asc'),
      array('type' => 'skip'),
      _("Branch") => array('ord' => ''),
      _("Delivery To"),
      _("Currency") => array('align' => 'center'),
      array('type' => 'skip'),
      _("Total") => array('type' => 'amount', 'ord' => ''),
    );
  }
  else {
    $ord = "Quote #";
    $cols = array(
      array('type' => 'skip'),
      _("Quote #") => array('fun' => 'view_link', 'ord' => ''),
      _("Ref") => array('ord' => ''),
      _("PO#") => array('type' => 'skip'),
      _("Date") => array('type' => 'date', 'ord' => 'desc'),
      _("Valid until") => array('type' => 'date', 'ord' => ''),
      _("Customer") => array('ord' => 'asc'),
      array('type' => 'skip'),
      _("Branch") => array('ord' => ''),
      _("Delivery To"),
      _("Currency") => array('align' => 'center'),
      array('type' => 'skip'),
      _("Total") => array('type' => 'amount', 'ord' => ''),
    );
  }
  if ($trans_type == ST_CUSTDELIVERY) {
  }
  if ($_POST['order_view_mode'] == 'OutstandingOnly') {
    Arr::append($cols, array(
      array('type' => 'skip'), array('fun' => 'dispatch_link')
    ));
  }
  elseif ($_POST['order_view_mode'] == 'InvoiceTemplates') {
    Arr::substitute($cols, 3, 1, _("Description"));
    Arr::append($cols, array(array('insert' => TRUE, 'fun' => 'invoice_link')));
  }
  else {
    if ($_POST['order_view_mode'] == 'DeliveryTemplates') {
      Arr::substitute($cols, 3, 1, _("Description"));
      Arr::append($cols, array(array('insert' => TRUE, 'fun' => 'delivery_link')));
    }
    elseif ($trans_type == ST_SALESQUOTE) {

      Arr::append($cols, array(
        array('insert' => TRUE, 'fun' => 'edit_link'),
        array('insert' => TRUE, 'fun' => 'order_link'),
        array('insert' => TRUE, 'fun' => 'email_link'),
        array('insert' => TRUE, 'fun' => 'prt_link2'),
        array('insert' => TRUE, 'fun' => 'prt_link')
      ));
    }
    elseif ($trans_type == ST_SALESORDER) {
      Arr::append($cols, array(
        _("Tmpl") => array(
          'type' => 'skip', 'insert' => TRUE, 'fun' => 'tmpl_checkbox'
        ), array(
          'insert' => TRUE, 'fun' => 'edit_link'
        ), array(
          'insert' => TRUE, 'fun' => 'email_link'
        ), array(
          'insert' => TRUE, 'fun' => 'prt_link2'
        ), array(
          'insert' => TRUE, 'fun' => 'prt_link'
        )
      ));
    }
  }
  $table = & db_pager::new_db_pager('orders_tbl', $sql, $cols, NULL, NULL, 0, NULL);
  $table->set_marker('check_overdue', _("Marked items are overdue."));
  $table->width = "80%";
  DB_Pager::display($table);
  submit_center('Update', _("Update"), TRUE, '', NULL);
  end_form();
  UI::emailDialogue(CT_CUSTOMER);

  Page::end();
  //	Query format functions
  //
  function check_overdue($row) {
    global $trans_type;
    if ($trans_type == ST_SALESQUOTE) {
      return (Dates::date1_greater_date2(Dates::today(), Dates::sql2date($row['delivery_date'])));
    }
    else {
      return ($row['type'] == 0 && Dates::date1_greater_date2(Dates::today(), Dates::sql2date($row['delivery_date'])) && ($row['TotDelivered'] < $row['TotQuantity']));
    }
  }

  function view_link($row, $order_no) {
    return Debtor::trans_view($row['trans_type'], $order_no);
  }

  function prt_link($row) {
    return Reporting::print_doc_link($row['order_no'], _("Print"), TRUE, $row['trans_type'], ICON_PRINT, 'button printlink');
  }

  function prt_link2($row) {
    return Reporting::print_doc_link($row['order_no'], _("Proforma"), TRUE, ST_PROFORMA, ICON_PRINT, 'button printlink');
  }

  function edit_link($row) {
    return DB_Pager::link(_("Edit"), "/sales/sales_order_entry.php?update=" . $row['order_no'] . "&type=" . $row['trans_type'], ICON_EDIT);
  }

  function email_link($row) {
    HTML::setReturn(TRUE);
    UI::button(FALSE, 'Email', array(
      'class' => 'button email-button',
      'data-emailid' => $row['debtor_no'] . '-' . $row['trans_type'] . '-' . $row['order_no']
    ));
    return HTML::setReturn(FALSE);
  }

  function dispatch_link($row) {
    if ($row['trans_type'] == ST_SALESORDER) {
      return DB_Pager::link(_("Dispatch"), "/sales/customer_delivery.php?OrderNumber=" . $row['order_no'], ICON_DOC);
    }
    else {
      return DB_Pager::link(_("Sales Order"), "/sales/sales_order_entry.php?OrderNumber=" . $row['order_no'], ICON_DOC);
    }
  }

  function invoice_link($row) {
    if ($row['trans_type'] == ST_SALESORDER) {
      return DB_Pager::link(_("Invoice"), "/sales/sales_order_entry.php?NewInvoice=" . $row["order_no"], ICON_DOC);
    }
    else {
      return '';
    }
  }

  function delivery_link($row) {
    return DB_Pager::link(_("Delivery"), "/sales/sales_order_entry.php?NewDelivery=" . $row['order_no'], ICON_DOC);
  }

  function order_link($row) {
    return DB_Pager::link(_("Create Order"), "/sales/sales_order_entry.php?QuoteToOrder=" . $row['order_no'], ICON_DOC);
  }

  function tmpl_checkbox($row) {
    global $trans_type;
    if ($trans_type == ST_SALESQUOTE) {
      return '';
    }
    $name = "chgtpl" . $row['order_no'];
    $value = $row['type'] ? 1 : 0;
    // save also in hidden field for testing during 'Update'
    return checkbox(NULL, $name, $value, TRUE, _('Set this order as a template for direct deliveries/invoices')) . hidden('last[' . $row
    ['order_no'] . ']', $value, FALSE);
  }

  // Update db record if respective checkbox value has changed.
  //
  function change_tpl_flag($id) {
    $sql = "UPDATE sales_orders SET type = !type WHERE order_no=$id";
    DB::query($sql, "Can't change sales order type");
    Ajax::i()->activate('orders_tbl');
  }

?>
