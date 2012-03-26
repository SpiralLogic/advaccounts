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
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  JS::open_window(900, 600);
  if (isset($_GET['OutstandingOnly']) && ($_GET['OutstandingOnly'] == TRUE)) {
    $_POST['OutstandingOnly'] = TRUE;
    Page::start(_($help_context = "Search Not Invoiced Deliveries"), SA_SALESINVOICE);
  }
  else {
    $_POST['OutstandingOnly'] = FALSE;
    Page::start(_($help_context = "Search All Deliveries"), SA_SALESINVOICE);
  }
  if (isset($_GET['selected_customer'])) {
    $selected_customer = $_GET['selected_customer'];
  }
  elseif (isset($_POST['selected_customer'])) {
    $selected_customer = $_POST['selected_customer'];
  }
  else {
    $selected_customer = -1;
  }
  if (isset($_POST[Orders::BATCH_INVOICE])) {
    // checking batch integrity
    $del_count = 0;
    foreach ($_POST['Sel_'] as $delivery => $branch) {
      $checkbox = 'Sel_' . $delivery;
      if (check_value($checkbox)) {
        if (!$del_count) {
          $del_branch = $branch;
        }
        else {
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
    }
    else {
      $_SESSION['DeliveryBatch'] = $selected;
      Display::meta_forward('/sales/customer_invoice.php', 'BatchInvoice=Yes');
    }
  }
  if (get_post('_DeliveryNumber_changed')) {
    $disable = get_post('DeliveryNumber') !== '';
    Ajax::i()->addDisable(TRUE, 'DeliveryAfterDate', $disable);
    Ajax::i()->addDisable(TRUE, 'DeliveryToDate', $disable);
    Ajax::i()->addDisable(TRUE, 'StockLocation', $disable);
    Ajax::i()->addDisable(TRUE, '_SelectStockFromList_edit', $disable);
    Ajax::i()->addDisable(TRUE, 'SelectStockFromList', $disable);
    // if search is not empty rewrite table
    if ($disable) {
      Ajax::i()->addFocus(TRUE, 'DeliveryNumber');
    }
    else {
      Ajax::i()->addFocus(TRUE, 'DeliveryAfterDate');
    }
    Ajax::i()->activate('deliveries_tbl');
  }
  start_form(FALSE, $_SERVER['PHP_SELF'] . "?OutstandingOnly=" . $_POST['OutstandingOnly']);
  start_table('tablestyle_noborder');
  start_row();
  Debtor::cells(_('Customer:'), 'selected_customer', $_POST['selected_customer'], TRUE);
  ref_cells(_("#:"), 'DeliveryNumber', '', NULL, '', TRUE);
  date_cells(_("from:"), 'DeliveryAfterDate', '', NULL, -30);
  date_cells(_("to:"), 'DeliveryToDate', '', NULL, 1);
  Inv_Location::cells(_("Location:"), 'StockLocation', NULL, TRUE);
  Item::cells(_("Item:"), 'SelectStockFromList', NULL, TRUE, FALSE, FALSE, FALSE, FALSE);
  submit_cells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
  hidden('OutstandingOnly', $_POST['OutstandingOnly']);
  end_row();
  end_table();
  if (isset($_POST['SelectStockFromList']) && ($_POST['SelectStockFromList'] != "") && ($_POST['SelectStockFromList'] != ALL_TEXT)
  ) {
    $selected_stock_item = $_POST['SelectStockFromList'];
  }
  else {
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
		trans.debtor_no = debtor.debtor_no
			AND trans.type = " . ST_CUSTDELIVERY . "
			AND line.debtor_trans_no = trans.trans_no
			AND line.debtor_trans_type = trans.type
			AND trans.branch_id = branch.branch_id
			AND trans.debtor_no = branch.debtor_no ";
  if ($_POST['OutstandingOnly'] == TRUE) {
    $sql .= " AND line.qty_done < line.quantity ";
  }
  //figure out the sql required from the inputs available
  if (isset($_POST['DeliveryNumber']) && $_POST['DeliveryNumber'] != "") {
    $delivery = "%" . $_POST['DeliveryNumber'];
    $sql .= " AND trans.trans_no LIKE " . DB::quote($delivery);
    $sql .= " GROUP BY trans.trans_no";
  }
  else {
    $sql .= " AND trans.tran_date >= '" . Dates::date2sql($_POST['DeliveryAfterDate']) . "'";
    $sql .= " AND trans.tran_date <= '" . Dates::date2sql($_POST['DeliveryToDate']) . "'";
    if ($selected_customer != -1) {
      $sql .= " AND trans.debtor_no=" . DB::quote($_POST['customer_id']) . " ";
    }
    if (isset($selected_stock_item)) {
      $sql .= " AND line.stock_id=" . DB::quote($selected_stock_item) . " ";
    }
    if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) {
      $sql .= " AND sorder.from_stk_loc = " . DB::quote($_POST['StockLocation']) . " ";
    }
    $sql .= " GROUP BY trans.trans_no ";
  } //end no delivery number selected
  $cols = array(
    _("Delivery #") => array('fun' => 'trans_view'), _("Customer"), _("branch_id") => 'skip', _("Contact"), _("Address"),
    _("Reference"), _("Cust Ref"), _("Delivery Date") => array(
      'type' => 'date', 'ord' => ''
    ), _("Due By") => array('type' => 'date'), _("Delivery Total") => array(
      'type' => 'amount', 'ord' => ''
    ), _("Currency") => array('align' => 'center'),
    submit(Orders::BATCH_INVOICE, _("Batch"), FALSE, _("Batch Invoicing")) => array(
      'insert' => TRUE, 'fun' => 'batch_checkbox', 'align' => 'center'
    ), array(
      'insert' => TRUE, 'fun' => 'edit_link'
    ), array(
      'insert' => TRUE, 'fun' => 'invoice_link'
    ), array(
      'insert' => TRUE, 'fun' => 'prt_link'
    )
  );
  if (isset($_SESSION['Batch'])) {
    foreach ($_SESSION['Batch'] as $trans => $del) {
      unset($_SESSION['Batch'][$trans]);
    }
    unset($_SESSION['Batch']);
  }
  Errors::log($sql);
  $table =& db_pager::new_db_pager('deliveries_tbl', $sql, $cols);
  $table->set_marker('check_overdue', _("Marked items are overdue."));
  //$table->width = "92%";
  DB_Pager::display($table);
  end_form();
  Page::end();
  function trans_view($trans, $trans_no) {
    return Debtor::trans_view(ST_CUSTDELIVERY, $trans['trans_no']);
  }

  function batch_checkbox($row) {
    $name = "Sel_" . $row['trans_no'];
    return $row['Done'] ? '' :
      "<input type='checkbox' name='$name' value='1' >" // add also trans_no => branch code for checking after 'Batch' submit
        . "<input name='Sel_[" . $row['trans_no'] . "]' type='hidden' value='" . $row['branch_id'] . "'>\n";
  }

  function edit_link($row) {
    return $row["Outstanding"] == 0 ? '' :
      DB_Pager::link(_('Edit'), "/sales/customer_delivery.php?ModifyDelivery=" . $row['trans_no'], ICON_EDIT);
  }

  function prt_link($row) {
    return Reporting::print_doc_link($row['trans_no'], _("Print"), TRUE, ST_CUSTDELIVERY, ICON_PRINT);
  }

  function invoice_link($row) {
    return $row["Outstanding"] == 0 ? '' :
      DB_Pager::link(_('Invoice'), "/sales/customer_invoice.php?DeliveryNumber=" . $row['trans_no'], ICON_DOC);
  }

  function check_overdue($row) {
    return Dates::date1_greater_date2(Dates::today(), Dates::sql2date($row["due_date"])) && $row["Outstanding"] != 0;
  }

?>

