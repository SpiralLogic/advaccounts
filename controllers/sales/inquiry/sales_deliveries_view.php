<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::openWindow(950, 600);
  if (isset($_GET['OutstandingOnly']) && ($_GET['OutstandingOnly'] == true)) {
    $_POST['OutstandingOnly'] = true;
    Page::start(_($help_context = "Search Not Invoiced Deliveries"), SA_SALESINVOICE);
  } else {
    $_POST['OutstandingOnly'] = false;
    Page::start(_($help_context = "Search All Deliveries"), SA_SALESINVOICE);
  }
  $selected_customer = Input::getPost('debtor_id', Input::NUMERIC, -1);
  if (isset($_POST[Orders::BATCH_INVOICE])) {
    // checking batch integrity
    $del_count = 0;
    foreach ($_POST['Sel_'] as $delivery => $branch) {
      $checkbox = 'Sel_' . $delivery;
      if (Forms::hasPost($checkbox)) {
        if (!$del_count) {
          $del_branch = $branch;
        } else {
          if ($del_branch != $branch) {
            $del_count = 0;
            break;
          }
        }
        $selected[] = $delivery;
        $del_count++;
      }
    }
    if (!$del_count) {
      Event::error(_('For batch invoicing you should select at least one delivery. All items must be dispatched to the same customer branch.'));
    } else {
      $_SESSION['DeliveryBatch'] = $selected;
      Display::meta_forward('/sales/customer_invoice.php', 'BatchInvoice=Yes');
    }
  }
  if (Input::post('_DeliveryNumber_changed')) {
    $disable = Input::post('DeliveryNumber') !== '';
    Ajax::addDisable(true, 'DeliveryAfterDate', $disable);
    Ajax::addDisable(true, 'DeliveryToDate', $disable);
    Ajax::addDisable(true, 'StockLocation', $disable);
    Ajax::addDisable(true, '_SelectStockFromList_edit', $disable);
    Ajax::addDisable(true, 'SelectStockFromList', $disable);
    // if search is not empty rewrite table
    if ($disable) {
      Ajax::addFocus(true, 'DeliveryNumber');
    } else {
      Ajax::addFocus(true, 'DeliveryAfterDate');
    }
    Ajax::activate('deliveries_tbl');
  }
  Forms::start(false, $_SERVER['DOCUMENT_URI'] . "?OutstandingOnly=" . $_POST['OutstandingOnly']);
  Table::start('tablestyle_noborder');
  Row::start();
  Debtor::cells(_('Customer:'), 'debtor_id', null, true);
  Forms::refCells(_("#:"), 'DeliveryNumber', '', null, '', true);
  Forms::dateCells(_("from:"), 'DeliveryAfterDate', '', null, -30);
  Forms::dateCells(_("to:"), 'DeliveryToDate', '', null, 1);
  Inv_Location::cells(_("Location:"), 'StockLocation', null, true);
  Item::cells(_("Item:"), 'SelectStockFromList', null, true, false, false, false, false);
  Forms::submitCells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
  Forms::hidden('OutstandingOnly', $_POST['OutstandingOnly']);
  Row::end();
  Table::end();
  if (isset($_POST['SelectStockFromList']) && ($_POST['SelectStockFromList'] != "") && ($_POST['SelectStockFromList'] != ALL_TEXT)
  ) {
    $selected_stock_item = $_POST['SelectStockFromList'];
  } else {
    unset($selected_stock_item);
  }
  $sql
    = "SELECT trans.trans_no,
		debtor.name,
		branch.branch_id,
		sorder.contact_name,
		sorder.deliver_to,
		trans.reference,
		sorder.customer_ref,
		trans.tran_date,
		trans.due_date,
		(ov_amount+ov_gst+ov_freight+ov_freight_tax) AS DeliveryValue,
		debtor.curr_code,
		Sum(line.quantity-line.qty_done) AS Outstanding,
		Sum(line.qty_done) AS Done
	FROM sales_orders as sorder, debtor_trans as trans, debtor_trans_details as line, debtors as debtor, branches as branch
		WHERE
		sorder.order_no = trans.order_ AND
		trans.debtor_id = debtor.debtor_id
			AND trans.type = " . ST_CUSTDELIVERY . "
			AND line.debtor_trans_no = trans.trans_no
			AND line.debtor_trans_type = trans.type
			AND trans.branch_id = branch.branch_id
			AND trans.debtor_id = branch.debtor_id ";
  if ($_POST['OutstandingOnly']) {
    $sql .= " AND line.qty_done < line.quantity ";
  }
  //figure out the sql required from the inputs available
  if (isset($_POST['DeliveryNumber']) && $_POST['DeliveryNumber'] != "") {
    $delivery = "%" . $_POST['DeliveryNumber'];
    $sql .= " AND trans.trans_no LIKE " . DB::quote($delivery);
    $sql .= " GROUP BY trans.trans_no";
  } else {
    $sql .= " AND trans.tran_date >= '" . Dates::dateToSql($_POST['DeliveryAfterDate']) . "'";
    $sql .= " AND trans.tran_date <= '" . Dates::dateToSql($_POST['DeliveryToDate']) . "'";
    if ($selected_customer != -1) {
      $sql .= " AND trans.debtor_id=" . DB::quote($selected_customer) . " ";
    }
    if (isset($selected_stock_item)) {
      $sql .= " AND line.stock_id=" . DB::quote($selected_stock_item) . " ";
    }
    if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) {
      $sql .= " AND sorder.from_stk_loc = " . DB::quote($_POST['StockLocation']);
    }
    $sql .= " GROUP BY trans.trans_no ";
  } //end no delivery number selected
  $cols = array(
    _("Delivery #")                                                                                 => array(
      'fun' => function ($trans, $trans_no) {
        return Debtor::viewTrans(ST_CUSTDELIVERY, $trans['trans_no']);
      }
    ),
    _("Customer"),
    _("branch_id")                                                                                  => 'skip',
    _("Contact"),
    _("Address"),
    _("Reference"),
    _("Cust Ref"),
    _("Delivery Date")                                                                              => array(
      'type' => 'date', 'ord' => ''
    ),
    _("Due By")                                                                                     => array('type' => 'date'),
    _("Delivery Total")                                                                             => array(
      'type' => 'amount', 'ord' => ''
    ),
    _("Currency")                                                                                   => array('align' => 'center'),
    Forms::submit(Orders::BATCH_INVOICE, _("Batch"), false, _("Batch Invoicing"))                   => array(
      'insert'   => true, 'fun' => function ($row) {
        $name = "Sel_" . $row['trans_no'];
        return $row['Done'] ? '' :
          "<input type='checkbox' name='$name' value='1' >" // add also trans_no => branch code for checking after 'Batch' submit
            . "<input name='Sel_[" . $row['trans_no'] . "]' type='hidden' value='" . $row['branch_id'] . "'>\n";
      }, 'align' => 'center'
    ),
    array(
      'insert' => true, 'fun' => function ($row) {
      return $row["Outstanding"] == 0 ? '' :
        DB_Pager::link(_('Edit'), "/sales/customer_delivery.php?ModifyDelivery=" . $row['trans_no'], ICON_EDIT);
    }

    ),
    array(
      'insert' => true, 'fun' => function ($row) {
      return $row["Outstanding"] == 0 ? '' :
        DB_Pager::link(_('Invoice'), "/sales/customer_invoice.php?DeliveryNumber=" . $row['trans_no'], ICON_DOC);
    }

    ),
    array(
      'insert' => true, 'fun' => function ($row) {
      return Reporting::print_doc_link($row['trans_no'], _("Print"), true, ST_CUSTDELIVERY, ICON_PRINT);
    }

    )
  );
  if (isset($_SESSION['Batch'])) {
    foreach ($_SESSION['Batch'] as $trans => $del) {
      unset($_SESSION['Batch'][$trans]);
    }
    unset($_SESSION['Batch']);
  }
  $table = DB_Pager::new_DB_Pager('deliveries_tbl', $sql, $cols);
  $table->setMarker(function ($row) {
    return Dates::isGreaterThan(Dates::today(), Dates::sqlToDate($row["due_date"])) && $row["Outstanding"] != 0;
  }, _("Marked items are overdue."));
  //$table->width = "92%";
  $table->display($table);
  Forms::end();
  Page::end();
