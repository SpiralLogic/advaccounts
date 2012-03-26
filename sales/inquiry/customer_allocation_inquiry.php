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
  JS::open_window(900, 500);
  Page::start(_($help_context = "Customer Allocation Inquiry"), SA_SALESALLOC);
  if (isset($_GET['customer_id']) || isset($_GET['id'])) {
    $_POST['customer_id'] = isset($_GET['id']) ? $_GET['id'] : $_GET['customer_id'];
  }
  if (!isset($_POST['customer_id'])) {
    $_POST['customer_id'] = Session::i()->global_customer;
  }
  if (isset($_GET['frame'])) {
    foreach ($_GET as $k => $v) {
      $_POST[$k] = $v;
    }
  }
  if (list_updated('customer_id')) {
    Ajax::i()->activate('customer_id');
  }
  start_form(FALSE, '', 'invoiceForm');
  start_table('tablestyle_noborder');
  start_row();
  if (!Input::get('frame')) {
    Debtor::cells(_("Select a customer: "), 'customer_id', NULL, TRUE);
  }
  Session::i()->global_customer = $_POST['customer_id'];
  if (!isset($_POST['TransAfterDate']) && isset($_SESSION['global_TransAfterDate'])) {
    $_POST['TransAfterDate'] = $_SESSION['global_TransAfterDate'];
  }
  elseif (isset($_POST['TransAfterDate'])) {
    $_SESSION['global_TransAfterDate'] = $_POST['TransAfterDate'];
  }
  if (!isset($_POST['TransToDate']) && isset($_SESSION['global_TransToDate'])) {
    $_POST['TransToDate'] = $_SESSION['global_TransToDate'];
  }
  elseif (isset($_POST['TransToDate'])) {
    $_SESSION['global_TransToDate'] = $_POST['TransToDate'];
  }
  date_cells(_("from:"), 'TransAfterDate', '', NULL, -31, -12);
  date_cells(_("to:"), 'TransToDate', '', NULL, 1);
  Debtor_Payment::allocations_select(_("Type:"), 'filterType', NULL);
  check_cells(" " . _("show settled:"), 'showSettled', NULL);
  submit_cells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
  end_row();
  end_table();
  $data_after = Dates::date2sql($_POST['TransAfterDate']);
  $date_to = Dates::date2sql($_POST['TransToDate']);
  $sql = "SELECT ";
  if (Input::get('frame')) {
    $sql .= " IF(trans.type=" . ST_SALESINVOICE . ",0,1), ";
  }
  $sql
    .= " trans.type,
		trans.trans_no,
		trans.reference,
		trans.order_,
		trans.tran_date,
		trans.due_date,
		debtor.name,
		debtor.curr_code,
 	(trans.ov_amount + trans.ov_gst + trans.ov_freight			+ trans.ov_freight_tax + trans.ov_discount)	AS TotalAmount,
	trans.alloc AS credit,
	trans.alloc AS Allocated,
		((trans.type = " . ST_SALESINVOICE . ") AND trans.due_date < '" . Dates::date2sql(Dates::today()) . "') AS OverDue
 	FROM debtor_trans as trans, debtors as debtor
 	WHERE debtor.debtor_no = trans.debtor_no
			AND round(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount,2) != 0
 		AND trans.tran_date >= '$data_after'
 		AND trans.tran_date <= '$date_to'";
  if ($_POST['customer_id'] != ALL_TEXT) {
    $sql .= " AND trans.debtor_no = " . DB::quote($_POST['customer_id']);
  }
  if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
    if ($_POST['filterType'] == '1' || $_POST['filterType'] == '2') {
      $sql .= " AND trans.type = " . ST_SALESINVOICE . " ";
    }
    elseif ($_POST['filterType'] == '3') {
      $sql .= " AND (trans.type = " . ST_CUSTPAYMENT . " OR trans.type = " . ST_CUSTREFUND . ")";
    }
    elseif ($_POST['filterType'] == '4') {
      $sql .= " AND trans.type = " . ST_CUSTCREDIT . " ";
    }
    if ($_POST['filterType'] == '2') {
      $today = Dates::date2sql(Dates::today());
      $sql
        .= " AND trans.due_date < '$today'
				AND (round(abs(trans.ov_amount + " . "trans.ov_gst + trans.ov_freight + " . "trans.ov_freight_tax + trans.ov_discount) - trans.alloc,2) > 0) ";
    }
  }
  else {
    $sql .= " AND trans.type <> " . ST_CUSTDELIVERY . " ";
  }
  if (!check_value('showSettled')) {
    $sql .= " AND (round(abs(trans.ov_amount + trans.ov_gst + " . "trans.ov_freight + trans.ov_freight_tax + " . "trans.ov_discount) - trans.alloc,2) > 0) ";
  }
  $cols = array(
    "<button id='emailInvoices'>Email</button> " => array('fun' => 'email_chk', 'align' => 'center'),
    _("Type") => array('fun' => 'systype_name'),
    _("#") => array('fun' => 'view_link'),
    _("Reference"),
    _("Order") => array('fun' => 'order_link'),
    _("Date") => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'asc'),
    _("Due Date") => array('type' => 'date', 'fun' => 'due_date'),
    _("Customer") => array(),
    _("Currency") => array('align' => 'center'),
    _("Debit") => array('align' => 'right', 'fun' => 'fmt_debit'),
    _("Credit") => array('align' => 'right', 'fun' => 'fmt_credit'),
    _("Allocated") => 'amount', _("overdue") => array('type' => 'skip'),
    _("Balance") => array('type' => 'amount', 'insert' => TRUE, 'fun' => 'fmt_balance'),
    array('insert' => TRUE, 'fun' => 'alloc_link')
  );
  if (Input::post('customer_id')) {
    $cols[_("Customer")] = 'skip';
  }
  if (!Input::get('frame')) {
    array_shift($cols);
  }
  $table =& db_pager::new_db_pager('doc_tbl', $sql, $cols);
  $table->set_marker('check_overdue', _("Marked items are overdue."));
  $table->width = "80%";
  DB_Pager::display($table);
  end_form();
  $action
    = <<<JS

$('#invoiceForm').find(':checkbox').each(function(){\$this =\$(this);\$this.prop('checked',!\$this.prop('checked'))});
return false;
JS;
  JS::addLiveEvent('#emailInvoices', 'dblclick', $action, 'wrapper', TRUE);
  JS::addLiveEvent('#emailInvoices', 'click', 'return false;', 'wrapper', TRUE);
  Page::end();
  function check_overdue($row) {
    return ($row['OverDue'] == 1 && Num::price_format(abs($row["TotalAmount"]) - $row["Allocated"]) != 0);
  }

  function order_link($row) {
    return $row['order_'] > 0 ? Debtor::trans_view(ST_SALESORDER, $row['order_']) : "";
  }

  function systype_name($dummy, $type) {
    global $systypes_array;
    return $systypes_array[$type];
  }

  function view_link($trans) {
    return GL_UI::trans_view($trans["type"], $trans["trans_no"]);
  }

  function due_date($row) {
    return $row["type"] == 10 ? $row["due_date"] : '';
  }

  function fmt_balance($row) {
    return $row["TotalAmount"] - $row["Allocated"];
  }

  function alloc_link($row) {
    $link = DB_Pager::link(_("Allocation"), "/sales/allocations/customer_allocate.php?trans_no=" . $row["trans_no"] . "&trans_type=" . $row["type"], ICON_MONEY);
    if ($row["type"] == ST_CUSTCREDIT && Num::price_format($row['TotalAmount'] - $row['Allocated']) > 0) {
      /*its a credit note which could have an allocation */
      return $link;
    }
    elseif (($row["type"] == ST_CUSTPAYMENT || $row["type"] == ST_CUSTREFUND || $row["type"] == ST_BANKDEPOSIT) && Num::price_format($row['TotalAmount'] - $row['Allocated']) > 0
    ) {
      /*its a receipt which could have an allocation*/
      return $link;
    }
    elseif ($row["type"] == ST_CUSTPAYMENT || $row["type"] == ST_CUSTREFUND && Num::price_format($row['TotalAmount']) < 0) {
      /*its a negative receipt */
      return '';
    }
  }

  function fmt_debit($row) {
    $value = $row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_BANKDEPOSIT ?
      -$row["TotalAmount"] : $row["TotalAmount"];
    return $value >= 0 ? Num::price_format($value) : '';
  }

  function fmt_credit($row) {
    $value = !($row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_BANKDEPOSIT) ?
      -$row["TotalAmount"] : $row["TotalAmount"];
    return $value > 0 ? Num::price_format($value) : '';
  }

  function email_chk($row) {
    return ($row['type'] == ST_SALESINVOICE) ? checkbox(NULL, 'emailChk') : '';
  }

?>
