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
  Page::start(_($help_context = "Supplier Allocation Inquiry"), SA_SUPPLIERALLOC);
  if (isset($_GET['supplier_id']) || isset($_GET['id'])) {
    $_POST['supplier_id'] = isset($_GET['id']) ? $_GET['id'] : $_GET['supplier_id'];
  }
  if (isset($_GET['supplier_id'])) {
    $_POST['supplier_id'] = $_GET['supplier_id'];
  }
  if (isset($_GET['FromDate'])) {
    $_POST['TransAfterDate'] = $_GET['FromDate'];
  }
  if (isset($_GET['ToDate'])) {
    $_POST['TransToDate'] = $_GET['ToDate'];
  }
  if (isset($_GET['frame'])) {
    foreach ($_GET as $k => $v) {
      $_POST[$k] = $v;
    }
  }
  start_form(FALSE, '', 'invoiceForm');
  if (!isset($_POST['supplier_id'])) {
    $_POST['supplier_id'] = Session::i()->getGlobal('creditor');
  }
  if (!isset($_POST['TransAfterDate']) && Session::i()->getGlobal('TransAfterDate')) {
    $_POST['TransAfterDate'] = Session::i()->getGlobal('TransAfterDate');
  }
  elseif (isset($_POST['TransAfterDate'])) {
    Session::i()->setGlobal('TransAfterDate',$_POST['TransAfterDate']);
  }
  if (!isset($_POST['TransToDate']) && Session::i()->getGlobal('TransToDate')) {
    $_POST['TransToDate'] = Session::i()->getGlobal('TransToDate');
  }
  elseif (isset($_POST['TransToDate'])) {
    Session::i()->setGlobal('TransToDate',$_POST['TransToDate']);
  }
  start_table('tablestyle_noborder');
  start_row();
  if (!Input::get('frame')) {

    Creditor::cells(_("Supplier: "), 'supplier_id', NULL, TRUE);
  }
  date_cells(_("From:"), 'TransAfterDate', '', NULL, -90);
  date_cells(_("To:"), 'TransToDate', '', NULL, 1);
  Purch_Allocation::row("filterType", NULL);
  check_cells(_("Show settled:"), 'showSettled', NULL);
  submit_cells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
  Session::i()->setGlobal('creditor',$_POST['supplier_id']);
  end_row();
  end_table();
  /**
   * @param $row
   *
   * @return bool
   */
  function check_overdue($row) {
    return ($row['TotalAmount'] > $row['Allocated']) && $row['OverDue'] == 1;
  }

  /**
   * @param $dummy
   * @param $type
   *
   * @return mixed
   */
  function systype_name($dummy, $type) {
    global $systypes_array;
    return $systypes_array[$type];
  }

  /**
   * @param $trans
   *
   * @return null|string
   */
  function view_link($trans) {
    return GL_UI::trans_view($trans["type"], $trans["trans_no"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function due_date($row) {
    return (($row["type"] == ST_SUPPINVOICE) || ($row["type"] == ST_SUPPCREDIT)) ? $row["due_date"] : "";
  }

  /**
   * @param $row
   *
   * @return mixed
   */
  function fmt_balance($row) {
    $value = ($row["type"] == ST_BANKPAYMENT || $row["type"] == ST_SUPPCREDIT || $row["type"] == ST_SUPPAYMENT) ?
      -$row["TotalAmount"] - $row["Allocated"] :
      $row["TotalAmount"] - $row["Allocated"];
    return $value;
  }

  /**
   * @param $row
   *
   * @return string
   */
  function alloc_link($row) {
    $link = DB_Pager::link(_("Allocations"), "/purchases/allocations/supplier_allocate.php?trans_no=" . $row["trans_no"] . "&trans_type=" . $row["type"], ICON_MONEY);
    return (($row["type"] == ST_BANKPAYMENT || $row["type"] == ST_SUPPCREDIT || $row["type"] == ST_SUPPAYMENT) && (-$row["TotalAmount"] - $row["Allocated"]) > 0) ?
      $link : '';
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function fmt_debit($row) {
    $value = -$row["TotalAmount"];
    return $value >= 0 ? Num::price_format($value) : '';
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function fmt_credit($row) {
    $value = $row["TotalAmount"];
    return $value > 0 ? Num::price_format($value) : '';
  }

  $date_after = Dates::date2sql($_POST['TransAfterDate']);
  $date_to = Dates::date2sql($_POST['TransToDate']);
  // Sherifoz 22.06.03 Also get the description
  $sql
    = "SELECT
		trans.type, 
		trans.trans_no,
		trans.reference, 
		supplier.supp_name,
		supplier.supplier_id as id,
		trans.supp_reference,
 		trans.tran_date,
		trans.due_date,
		supplier.curr_code, 
 		(trans.ov_amount + trans.ov_gst + trans.ov_discount) AS TotalAmount,
		trans.alloc AS Allocated,
		((trans.type = " . ST_SUPPINVOICE . " OR trans.type = " . ST_SUPPCREDIT . ") AND trans.due_date < '" . Dates::date2sql(Dates::today()) . "') AS OverDue
 	FROM creditor_trans as trans, suppliers as supplier
 	WHERE supplier.supplier_id = trans.supplier_id
 	AND trans.tran_date >= '$date_after'
 	AND trans.tran_date <= '$date_to'";
  if ($_POST['supplier_id'] != ALL_TEXT) {
    $sql .= " AND trans.supplier_id = " . DB::quote($_POST['supplier_id']);
  }
  if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
    if (($_POST['filterType'] == '1') || ($_POST['filterType'] == '2')) {
      $sql .= " AND trans.type = " . ST_SUPPINVOICE . " ";
    }
    elseif ($_POST['filterType'] == '3') {
      $sql .= " AND trans.type = " . ST_SUPPAYMENT . " ";
    }
    elseif (($_POST['filterType'] == '4') || ($_POST['filterType'] == '5')) {
      $sql .= " AND trans.type = " . ST_SUPPCREDIT . " ";
    }
    if (($_POST['filterType'] == '2') || ($_POST['filterType'] == '5')) {
      $today = Dates::date2sql(Dates::today());
      $sql .= " AND trans.due_date < '$today' ";
    }
  }
  if (!check_value('showSettled')) {
    $sql .= " AND (round(abs(ov_amount + ov_gst + ov_discount) - alloc,6) != 0) ";
  }
  $cols = array(
    _("Type") => array('fun' => 'systype_name'),
    _("#") => array('fun' => 'view_link', 'ord' => ''),
    _("Reference"),
    _("Supplier") => array('ord' => '', 'type' => 'id'),
    _("Supplier ID") => array('skip'),
    _("Supp Reference"),
    _("Date") => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
    _("Due Date") => array('fun' => 'due_date'),
    _("Currency") => array('align' => 'center'),
    _("Debit") => array('align' => 'right', 'fun' => 'fmt_debit'),
    _("Credit") => array(
      'align' => 'right', 'insert' => TRUE, 'fun' => 'fmt_credit'
    ),
    _("Allocated") => 'amount',
    _("Balance") => array(
      'type' => 'amount', 'insert' => TRUE, 'fun' => 'fmt_balance'
    ),
    array(
      'insert' => TRUE, 'fun' => 'alloc_link'
    )
  );
  if ($_POST['supplier_id'] != ALL_TEXT) {
    $cols[_("Supplier ID")] = 'skip';
    $cols[_("Supplier")] = 'skip';
    $cols[_("Currency")] = 'skip';
  }
  $table =& db_pager::new_db_pager('doc_tbl', $sql, $cols);
  $table->set_marker('check_overdue', _("Marked items are overdue."));
  $table->width = "90";
  DB_Pager::display($table);
  Creditor::addInfoDialog('.pagerclick');
  end_form();
  Page::end();

