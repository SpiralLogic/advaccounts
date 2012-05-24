<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::open_window(900, 500);
  Page::start(_($help_context = "Search Outstanding Purchase Orders"), SA_SUPPTRANSVIEW);
  $_POST['order_number']        = Input::get_post('order_number', Input::NUMERIC);
  $_POST['StockLocation']       = Input::get_post('StockLocation', Input::STRING, '');
  $_POST['SelectStockFromList'] = Input::get_post('SelectStockFromList', Input::STRING, '');
  $_POST['supplier_id']         = Input::get_post('supplier_id', Input::NUMERIC, 0);
  // Ajax updates
  //
  if (get_post('SearchOrders')) {
    Ajax::i()->activate('orders_tbl');
  }
  elseif (get_post('_order_number_changed')) {
    $disable = get_post('order_number') !== '';
    Ajax::i()->addDisable(TRUE, 'OrdersAfterDate', $disable);
    Ajax::i()->addDisable(TRUE, 'OrdersToDate', $disable);
    Ajax::i()->addDisable(TRUE, 'StockLocation', $disable);
    Ajax::i()->addDisable(TRUE, '_SelectStockFromList_edit', $disable);
    Ajax::i()->addDisable(TRUE, 'SelectStockFromList', $disable);
    if ($disable) {
      Ajax::i()->addFocus(TRUE, 'order_number');
    }
    else {
      Ajax::i()->addFocus(TRUE, 'OrdersAfterDate');
    }
    Ajax::i()->activate('orders_tbl');
  }
  start_form();
  Table::start('tablestyle_noborder');
  Row::start();
  Creditor::cells(_("Supplier: "), 'supplier_id', Input::post('supplier_id'), TRUE);
  ref_cells(_("#:"), 'order_number', '', NULL, '', TRUE);
  date_cells(_("From:"), 'OrdersAfterDate', '', NULL, -30);
  date_cells(_("To:"), 'OrdersToDate');
  Inv_Location::cells(_("Location:"), 'StockLocation', NULL, TRUE);
  //Item::cells(_("Item:"), 'SelectStockFromList', null, true,false,false,false,true);
  submit_cells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
  Row::end();
  Table::end();

  //figure out the sql required from the inputs available
  $sql = "SELECT
	porder.order_no, 
	porder.reference,
	supplier.name,
	 supplier.supplier_id as id,
	location.location_name,
	porder.requisition_no, 
	porder.ord_date,
	supplier.curr_code,
	Sum(line.unit_price*line.quantity_ordered) AS OrderValue,
	Sum(line.delivery_date < '" . Dates::date2sql(Dates::today()) . "'
	AND (line.quantity_ordered > line.quantity_received)) As OverDue
	FROM purch_orders as porder, purch_order_details as line, suppliers as supplier, locations as location
	WHERE porder.order_no = line.order_no
	AND porder.supplier_id = supplier.supplier_id
	AND location.loc_code = porder.into_stock_location
	AND (line.quantity_ordered > line.quantity_received) ";
  if ($_POST['supplier_id']) {
    $sql .= " AND supplier.supplier_id = " . DB::quote($_POST['supplier_id']);
  }
  if ($_POST['order_number']) {
    $sql .= "AND porder.reference LIKE " . DB::quote($_POST['order_number']);
  }
  else {
    $data_after  = Dates::date2sql($_POST['OrdersAfterDate']);
    $data_before = Dates::date2sql($_POST['OrdersToDate']);
    $sql .= " AND porder.ord_date >= '$data_after'";
    $sql .= " AND porder.ord_date <= '$data_before'";
    if ($_POST['StockLocation']) {
      $sql .= " AND porder.into_stock_location = " . DB::quote($_POST['StockLocation']);
    }
    if (isset($selected_stock_item)) {
      $sql .= " AND line.item_code=" . DB::quote($_POST['SelectStockFromList']);
    }
  } //end not order number selected
  $sql .= " GROUP BY porder.order_no";
  $result = DB::query($sql, "No orders were returned");
  /*show a table of the orders returned by the sql */
  $cols = array(
    _("#")           => array('fun' => function ($trans) { return GL_UI::trans_view(ST_PURCHORDER, $trans["order_no"]); }, 'ord' => ''),
    _("Reference"),
    _("Supplier")    => array('ord' => '', 'type' => 'id'),
    _("Supplier ID") => 'skip',
    _("Location"),
    _("Supplier's Reference"),
    _("Order Date")  => array('name' => 'ord_date', 'type' => 'date', 'ord' => 'desc'),
    _("Currency")    => array('align' => 'center'),
    _("Order Total") => 'amount',
    array('insert' => TRUE, 'fun' => function ($row) { return DB_Pager::link(_("Edit"), "/purchases/po_entry_items.php?ModifyOrder=" . $row["order_no"], ICON_EDIT); }),
    array('insert' => TRUE, 'fun' => function ($row) { return Reporting::print_doc_link($row['order_no'], _("Print"), TRUE, ST_PURCHORDER, ICON_PRINT, 'button printlink'); }),
    array('insert' => TRUE, 'fun' => function ($row) { return DB_Pager::link(_("Receive"), "/purchases/po_receive_items.php?PONumber=" . $row["order_no"], ICON_RECEIVE); })
  );
  if (get_post('StockLocation') != ALL_TEXT) {
    $cols[_("Location")] = 'skip';
  }
  $table =& db_pager::new_db_pager('orders_tbl', $sql, $cols);
  $table->set_marker(function ($row) { return $row['OverDue'] == 1; }, _("Marked orders have overdue items."));
  $table->width = "80%";
  DB_Pager::display($table);
  Creditor::addInfoDialog('.pagerclick');
  end_form();
  Page::end();
