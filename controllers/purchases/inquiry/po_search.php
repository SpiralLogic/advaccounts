<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::openWindow(900, 500);
  Page::start(_($help_context = "Search Outstanding Purchase Orders"), SA_SUPPTRANSVIEW);
  $order_number = $_POST['order_number'] = Input::getPost('order_number', Input::NUMERIC);
  // Ajax updates
  //
  if (Input::post('SearchOrders')) {
    Ajax::activate('orders_tbl');
  }
  if ($order_number) {
    Ajax::addFocus(true, 'order_number');
  } else {
    Ajax::addFocus(true, 'OrdersAfterDate');
  }
  Ajax::activate('orders_tbl');
  Forms::start();
  Table::start('tablestyle_noborder');
  Row::start();
  Creditor::cells(_(""), 'supplier_id', Input::post('supplier_id'), true);
  Forms::refCells(_("#:"), 'order_number');
  Forms::dateCells(_("From:"), 'OrdersAfterDate', '', null, -30);
  Forms::dateCells(_("To:"), 'OrdersToDate');
  Inv_Location::cells(_("Location:"), 'StockLocation', null, true);
  //Item::cells(_("Item:"), 'SelectStockFromList', null, true,false,false,false,true);
  Forms::submitCells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
  Row::end();
  Table::end();
  //figure out the sql required from the inputs available
  $sql
               = "SELECT
    porder.order_no,
    porder.reference,
    supplier.name,
     supplier.supplier_id as id,
    location.location_name,
    porder.requisition_no,
    porder.ord_date,
    supplier.curr_code,
    Sum(line.unit_price*line.quantity_ordered) AS OrderValue,
    Sum(line.delivery_date < '" . Dates::dateToSql(Dates::today()) . "'
    AND (line.quantity_ordered > line.quantity_received)) As OverDue
    FROM purch_orders as porder, purch_order_details as line, suppliers as supplier, locations as location
    WHERE porder.order_no = line.order_no
    AND porder.supplier_id = supplier.supplier_id
    AND location.loc_code = porder.into_stock_location
    AND (line.quantity_ordered > line.quantity_received) ";
  $supplier_id = Input::getPost('supplier_id', Input::NUMERIC, 0);
  if ($supplier_id) {
    $sql .= " AND supplier.supplier_id = " . DB::quote($supplier_id);
  }
  if ($order_number) {
    $sql .= " AND (porder.order_no LIKE " . DB::quote('%' . $order_number . '%');
    $sql .= " OR porder.reference LIKE " . DB::quote('%' . $order_number . '%') . ') ';
  } else {
    $data_after  = Dates::dateToSql($_POST['OrdersAfterDate']);
    $data_before = Dates::dateToSql($_POST['OrdersToDate']);
    $sql .= " AND porder.ord_date >= '$data_after'";
    $sql .= " AND porder.ord_date <= '$data_before'";
    $stock_location = Input::getPost('StockLocation', Input::STRING, '');
    if ($stock_location) {
      $sql .= " AND porder.into_stock_location = " . DB::quote($stock_location);
    }
    $selected_stock_item = Input::getPost('SelectStockFromList', Input::STRING, '');
    if ($selected_stock_item) {
      $sql .= " AND line.item_code=" . DB::quote($selected_stock_item);
    }
  } //end not order number selected
  $sql .= " GROUP BY porder.order_no";
  $result = DB::query($sql, "No orders were returned");
  /*show a table of the orders returned by the sql */
  $cols = array(
    _("#")                                                                                                          => array(
      'fun'                                                                      => function ($trans) {
        return GL_UI::viewTrans(ST_PURCHORDER, $trans["order_no"]);
      }, 'ord'                                                                   => ''
    ),
    _("Reference"),
    _("Supplier")                                                                                                   => array(
      'ord'  => '', 'type' => 'id'
    ),
    _("Supplier ID")                                                                                                => 'skip',
    _("Location"),
    _("Supplier's Reference"),
    _("Order Date")                                                                                                 => array(
      'name' => 'ord_date', 'type' => 'date', 'ord'  => 'desc'
    ),
    _("Currency")                                                                                                   => array('align' => 'center'),
    _("Order Total")                                                                                                => 'amount',
    array(
      'insert' => true, 'fun'    => function ($row) {
      return DB_Pager::link(_("Edit"), "/purchases/po_entry_items.php?ModifyOrder=" . $row["order_no"], ICON_EDIT);
    }
    ),
    array(
      'insert' => true, 'fun'    => function ($row) {
      return Reporting::print_doc_link($row['order_no'], _("Print"), true, ST_PURCHORDER, ICON_PRINT, 'button printlink');
    }
    ),
    array(
      'insert' => true, 'fun'    => function ($row) {
      return DB_Pager::link(_("Receive"), "/purchases/po_receive_items.php?PONumber=" . $row["order_no"], ICON_RECEIVE);
    }
    )
  );
  if (!$stock_location) {
    $cols[_("Location")] = 'skip';
  }
  $table = db_pager::new_db_pager('orders_tbl', $sql, $cols);
  $table->setMarker(function ($row) {
    return $row['OverDue'] == 1;
  }, _("Marked orders have overdue items."));
  $table->width = "80%";
  $table->display($table);
  Creditor::addInfoDialog('.pagerclick');
  Forms::end();
  Page::end();
