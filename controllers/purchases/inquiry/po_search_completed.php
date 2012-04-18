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
  if (isset($_GET['order_number'])) {
    $order_number = $_GET['order_number'];
  }
  $supplier_id = Input::post_get('supplier_id', Input::NUMERIC, -1);
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
  if (!Input::request('frame')) {
    start_table('tablestyle_noborder');
    start_row();
    Creditor::cells(_("Supplier: "), 'supplier_id', NULL, TRUE);
    ref_cells(_("#:"), 'order_number', '', NULL, '', TRUE);
    date_cells(_("From:"), 'OrdersAfterDate', '', NULL, -30);
    date_cells(_("To:"), 'OrdersToDate');
    Inv_Location::cells(_("Location:"), 'StockLocation', NULL, TRUE);
    Item::cells(_("Item:"), 'SelectStockFromList', NULL, TRUE);
    submit_cells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
    end_row();
    end_table();
  }
  if (isset($_POST['order_number'])) {
    $order_number = $_POST['order_number'];
  }
  if (isset($_POST['SelectStockFromList']) && ($_POST['SelectStockFromList'] != "") && ($_POST['SelectStockFromList'] != ALL_TEXT)
  ) {
    $selected_stock_item = $_POST['SelectStockFromList'];
  }
  else {
    unset($selected_stock_item);
  }
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
    foreach ($searchArray as $ajaxsearch) {
      if (empty($ajaxsearch)) {
        continue;
      }
      $ajaxsearch = DB::quote("%" . $ajaxsearch . "%");
      $sql
        .= " AND (supplier.supp_name LIKE $ajaxsearch OR porder.order_no LIKE $ajaxsearch
		 OR porder.reference LIKE $ajaxsearch
		 OR porder.requisition_no LIKE $ajaxsearch
		 OR location.location_name LIKE $ajaxsearch)";
    }
  }
  elseif (isset($order_number) && $order_number != "") {
    $sql .= "AND porder.reference LIKE " . DB::quote('%' . $order_number . '%');
  }
  else {

    if ($supplier_id > -1) {
      $sql .= " AND porder.supplier_id = " . DB::quote($supplier_id);
    }
    if ((isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) || isset($_GET[LOC_NOT_FAXED_YET])) {
      $sql .= " AND porder.into_stock_location = ";
      $sql .= (Input::get(LOC_NOT_FAXED_YET) == 1) ? "'" . LOC_NOT_FAXED_YET . "'" : DB::quote($_POST['StockLocation']);
    }
    else {
      $data_after = Dates::date2sql($_POST['OrdersAfterDate']);
      $date_before = Dates::date2sql($_POST['OrdersToDate']);
      $sql .= " AND porder.ord_date >= '$data_after'";
      $sql .= " AND porder.ord_date <= '$date_before'";
    }
    if (isset($selected_stock_item)) {
      $sql .= " AND line.item_code=" . DB::quote($selected_stock_item);
    }
  } //end not order number selected
  $sql .= " GROUP BY porder.order_no";
  $cols = array(
    // Transaction link
    _("#") => array('ord' => '', 'fun' => function ($trans) { return GL_UI::trans_view(ST_PURCHORDER, $trans["order_no"]); }), //
    _("Reference"), //
    _("Supplier") => array('ord' => '', 'type' => 'id'), //
    _("Supplier ID") => 'skip', //
    _("Location") => '', //
    _("Invoice #") => '', //
    _("Order Date") => array('name' => 'ord_date', 'type' => 'date', 'ord' => 'desc'), //
    _("Currency") => array('align' => 'center'), //
    _("Order Total") => 'amount', //
    // Edit link
    array('insert' => TRUE, 'fun' => function ($row) { return DB_Pager::link(_("Edit"), "/purchases/po_entry_items.php?" . SID . Orders::MODIFY_ORDER . "=" . $row["order_no"], ICON_EDIT); }) //
  );
  if (get_post('StockLocation') != ALL_TEXT) {
    $cols[_("Location")] = 'skip';
  }
  if ((Input::get(LOC_NOT_FAXED_YET) == 1)) {
    $cols[_("Invoice #")] = 'skip';
  }
  else {
    Arr::append($cols, array(
      // Email button
      array('insert' => TRUE, 'fun' => function ($row) { return Reporting::emailDialogue($row['id'], ST_PURCHORDER, $row['order_no']); }), //
      // Print button
      array('insert' => TRUE, 'fun' => function ($row) { return Reporting::print_doc_link($row['order_no'], _("Print"), TRUE, 18, ICON_PRINT, 'button printlink'); }), //
      // Recieve/Invoice button
      array(
        'insert' => TRUE, 'fun' => function ($row) {
        if ($row['Received'] > 0) {
          return DB_Pager::link(_("Receive"), "/purchases/po_receive_items.php?PONumber=" . $row["order_no"], ICON_RECEIVE);
        }
        elseif ($row['Invoiced'] > 0) {
          return DB_Pager::link(_("Invoice"), "/purchases/supplier_invoice.php?New=1&supplier_id=" . $row['supplier_id'] . "&PONumber=" . $row["order_no"], ICON_RECEIVE);
        }
        return '';
      }
      ) //
    )//
    );
  }
  $table =& db_pager::new_db_pager('orders_tbl', $sql, $cols);
  $table->width = (Input::request('frame')) ? '100' : "90";
  DB_Pager::display($table);
  Creditor::addInfoDialog('.pagerclick');
  UI::emailDialogue(CT_SUPPLIER);
  end_form();
  Page::end();
