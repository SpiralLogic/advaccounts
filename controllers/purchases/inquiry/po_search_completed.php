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
  Page::start(_($help_context = "Search Purchase Orders"), SA_SUPPTRANSVIEW, Input::request('frame'));
  $order_number = Input::get_post('order_number', Input::STRING);
  $supplier_id  = Input::post_get('supplier_id', Input::NUMERIC, -1);
  if (Input::post('SearchOrders')) {
    Ajax::activate('orders_tbl');
  }
  if ($order_number) {
    Ajax::addFocus(TRUE, 'order_number');
  } else {
    Ajax::addFocus(TRUE, 'OrdersAfterDate');
  }
  Ajax::activate('orders_tbl');
  Forms::start();
  if (!Input::request('frame')) {
    Table::start('tablestyle_noborder');
    Row::start();
    Creditor::cells('', 'supplier_id', NULL, TRUE);
    Forms::refCells(_("#:"), 'order_number');
    Forms::dateCells(_("From:"), 'OrdersAfterDate', '', NULL, -30);
    Forms::dateCells(_("To:"), 'OrdersToDate');
    Inv_Location::cells(_("Location:"), 'StockLocation', NULL, TRUE);
    Item::cells(_("Item:"), 'SelectStockFromList', NULL, TRUE);
    Forms::submitCells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
    Row::end();
    Table::end();
  }
  $searchArray         = [];
  if (AJAX_REFERRER && !empty($_POST['ajaxsearch'])) {
    $searchArray = explode(' ', $_POST['ajaxsearch']);
    unset($_POST['supplier_id']);
  }
  $sql = "SELECT
	porder.order_no, 
	porder.reference, 
	supplier.name,
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
  if (AJAX_REFERRER && $searchArray && !empty($_POST['ajaxsearch'])) {
    foreach ($searchArray as $ajaxsearch) {
      if (empty($ajaxsearch)) {
        continue;
      }
      $ajaxsearch = DB::quote("%" . $ajaxsearch . "%");
      $sql .= " AND (supplier.name LIKE $ajaxsearch OR porder.order_no LIKE $ajaxsearch
		 OR porder.reference LIKE $ajaxsearch
		 OR porder.requisition_no LIKE $ajaxsearch
		 OR location.location_name LIKE $ajaxsearch)";
    }
  } else {
    if ($order_number) {
      $sql .= " AND (porder.order_no LIKE " . DB::quote('%' . $order_number . '%');
      $sql .= " OR porder.reference LIKE " . DB::quote('%' . $order_number . '%') . ') ';
    }
    if ($supplier_id > -1) {
      $sql .= " AND porder.supplier_id = " . DB::quote($supplier_id);
    }
    $stock_location = Input::post('StockLocation', Input::STRING);
    $location       = Input::get(LOC_NOT_FAXED_YET);
    if ($stock_location || $location) {
      $sql .= " AND porder.into_stock_location = ";
      $sql .= ($location == 1) ? "'" . LOC_NOT_FAXED_YET . "'" : DB::quote($stock_location);
    } else {
      $data_after  = Dates::date2sql($_POST['OrdersAfterDate']);
      $date_before = Dates::date2sql($_POST['OrdersToDate']);
      $sql .= " AND porder.ord_date >= '$data_after'";
      $sql .= " AND porder.ord_date <= '$date_before'";
    }
    $selected_stock_item = Input::post('SelectStockFromList');

    if ($selected_stock_item) {
      $sql .= " AND line.item_code=" . DB::quote($selected_stock_item);
    }
  } //end not order number selected
  $sql .= " GROUP BY porder.order_no";
  $cols = array(
    // Transaction link
    _("#")           => array('ord' => '', 'fun' => function ($trans) { return GL_UI::trans_view(ST_PURCHORDER, $trans["order_no"]); }), //
    _("Reference"), //
    _("Supplier")    => array('ord' => '', 'type' => 'id'), //
    _("Supplier ID") => 'skip', //
    _("Location")    => '', //
    _("Invoice #")   => '', //
    _("Order Date")  => array('name' => 'ord_date', 'type' => 'date', 'ord' => 'desc'), //
    _("Currency")    => array('align' => 'center'), //
    _("Order Total") => 'amount', //
    // Edit link
    array('insert' => TRUE, 'fun' => function ($row) { return DB_Pager::link(_("Edit"), "/purchases/po_entry_items.php?" . Orders::MODIFY_ORDER . "=" . $row["order_no"], ICON_EDIT); }) //
  );
  if ($stock_location) {
    $cols[_("Location")] = 'skip';
  }
  if ($location == 1) {
    $cols[_("Invoice #")] = 'skip';
  } else {
    Arr::append($cols, array(
                            // Email button
                            array('insert' => TRUE, 'fun' => function ($row) { return Reporting::emailDialogue($row['id'], ST_PURCHORDER, $row['order_no']); }), //
                            // Print button
                            array('insert' => TRUE, 'fun' => function ($row) { return Reporting::print_doc_link($row['order_no'], _("Print"), TRUE, 18, ICON_PRINT, 'button printlink'); }), //
                            // Recieve/Invoice button
                            array(
                              'insert' => TRUE, 'fun' => function ($row)
                            {
                              if ($row['Received'] > 0) {
                                return DB_Pager::link(_("Receive"), "/purchases/po_receive_items.php?PONumber=" . $row["order_no"], ICON_RECEIVE);
                              } elseif ($row['Invoiced'] > 0) {
                                return DB_Pager::link(_("Invoice"), "/purchases/supplier_invoice.php?New=1&supplier_id=" . $row['supplier_id'] . "&PONumber=" . $row["order_no"], ICON_RECEIVE);
                              }
                              return '';
                            }
                            ) //
                       )//
    );
  }
  $table        =& db_pager::new_db_pager('orders_tbl', $sql, $cols);
  $table->width = (Input::request('frame')) ? '100' : "90";
  DB_Pager::display($table);
  Creditor::addInfoDialog('.pagerclick');
  UI::emailDialogue(CT_SUPPLIER);
  Forms::end();
  Page::end();
