<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::openWindow(950, 500);
  Page::start(_($help_context = "Supplier Allocation Inquiry"), SA_SUPPLIERALLOC);
  if (isset($_GET['creditor_id']) || isset($_GET['id'])) {
    $_POST['creditor_id'] = isset($_GET['id']) ? $_GET['id'] : $_GET['creditor_id'];
  }
  if (isset($_GET['creditor_id'])) {
    $_POST['creditor_id'] = $_GET['creditor_id'];
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
  Forms::start(false, '', 'invoiceForm');
  if (!isset($_POST['creditor_id'])) {
    $_POST['creditor_id'] = Session::getGlobal('creditor_id');
  }
  if (!isset($_POST['TransAfterDate']) && Session::getGlobal('TransAfterDate')) {
    $_POST['TransAfterDate'] = Session::getGlobal('TransAfterDate');
  } elseif (isset($_POST['TransAfterDate'])) {
    Session::setGlobal('TransAfterDate', $_POST['TransAfterDate']);
  }
  if (!isset($_POST['TransToDate']) && Session::getGlobal('TransToDate')) {
    $_POST['TransToDate'] = Session::getGlobal('TransToDate');
  } elseif (isset($_POST['TransToDate'])) {
    Session::setGlobal('TransToDate', $_POST['TransToDate']);
  }
  Table::start('tablestyle_noborder');
  Row::start();
  if (!Input::get('frame')) {
    Creditor::cells(_("Supplier: "), 'creditor_id', null, true);
  }
  Forms::dateCells(_("From:"), 'TransAfterDate', '', null, -90);
  Forms::dateCells(_("To:"), 'TransToDate', '', null, 1);
  Purch_Allocation::row("filterType", null);
  Forms::checkCells(_("Show settled:"), 'showSettled', null);
  Forms::submitCells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
  Session::setGlobal('creditor_id', $_POST['creditor_id']);
  Row::end();
  Table::end();
  /**
   * @param $row
   *
   * @return bool
   */
  function checkOverdue($row) {
    return ($row['TotalAmount'] > $row['Allocated']) && $row['OverDue'] == 1;
  }

  /**
   * @param $dummy
   * @param $type
   *
   * @return mixed
   */
  function sysTypeName($dummy, $type) {
    global $systypes_array;

    return $systypes_array[$type];
  }

  /**
   * @param $trans
   *
   * @return null|string
   */
  function view_link($trans) {
    return GL_UI::viewTrans($trans["type"], $trans["trans_no"]);
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
      -$row["TotalAmount"] - $row["Allocated"] : $row["TotalAmount"] - $row["Allocated"];

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
  function formatDebit($row) {
    $value = -$row["TotalAmount"];

    return $value >= 0 ? Num::priceFormat($value) : '';
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function formatCredit($row) {
    $value = $row["TotalAmount"];

    return $value > 0 ? Num::priceFormat($value) : '';
  }

  $date_after = Dates::dateToSql($_POST['TransAfterDate']);
  $date_to    = Dates::dateToSql($_POST['TransToDate']);
  // Sherifoz 22.06.03 Also get the description
  $sql
    = "SELECT
        trans.type,
        trans.trans_no,
        trans.reference,
        supplier.name,
        supplier.creditor_id as id,
        trans.supplier_reference,
         trans.tran_date,
        trans.due_date,
        supplier.curr_code,
         (trans.ov_amount + trans.ov_gst + trans.ov_discount) AS TotalAmount,
        trans.alloc AS Allocated,
        ((trans.type = " . ST_SUPPINVOICE . " OR trans.type = " . ST_SUPPCREDIT . ") AND trans.due_date < '" . Dates::today(true) . "') AS OverDue
     FROM creditor_trans as trans, suppliers as supplier
     WHERE supplier.creditor_id = trans.creditor_id
     AND trans.tran_date >= '$date_after'
     AND trans.tran_date <= '$date_to'";
  if ($_POST['creditor_id'] != ALL_TEXT) {
    $sql .= " AND trans.creditor_id = " . DB::quote($_POST['creditor_id']);
  }
  if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
    if (($_POST['filterType'] == '1') || ($_POST['filterType'] == '2')) {
      $sql .= " AND trans.type = " . ST_SUPPINVOICE . " ";
    } elseif ($_POST['filterType'] == '3') {
      $sql .= " AND trans.type = " . ST_SUPPAYMENT . " ";
    } elseif (($_POST['filterType'] == '4') || ($_POST['filterType'] == '5')) {
      $sql .= " AND trans.type = " . ST_SUPPCREDIT . " ";
    }
    if (($_POST['filterType'] == '2') || ($_POST['filterType'] == '5')) {
      $today = Dates::today(true);
      $sql .= " AND trans.due_date < '$today' ";
    }
  }
  if (!Forms::hasPost('showSettled')) {
    $sql .= " AND (round(abs(ov_amount + ov_gst + ov_discount) - alloc,6) != 0) ";
  }
  $cols = array(
    _("Type")        => array('fun' => 'sysTypeName'),
    _("#")           => array('fun' => 'view_link', 'ord' => ''),
    _("Reference"),
    _("Supplier")    => array('ord' => '', 'type' => 'id'),
    _("Supplier ID") => array('type'=> 'skip'),
    _("Supp Reference"),
    _("Date")        => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
    _("Due Date")    => array('fun' => 'due_date', 'type' => 'date'),
    _("Currency")    => array('align' => 'center'),
    _("Debit")       => array('align' => 'right', 'fun' => 'formatDebit'),
    _("Credit")      => array(
      'align' => 'right', 'insert' => true, 'fun' => 'formatCredit'
    ),
    _("Allocated")   => ['type'=> 'amount'],
    _("Balance")     => array(
      'type' => 'amount', 'fun' => 'fmt_balance'
    ),
    array(
      'insert' => true, 'fun' => 'alloc_link'
    )
  );
  if ($_POST['creditor_id'] != ALL_TEXT) {
    $cols[_("Supplier ID")] = 'skip';
    $cols[_("Supplier")]    = 'skip';
    $cols[_("Currency")]    = 'skip';
  }
  $table = DB_Pager::new_db_pager('doc_tbl', $sql, $cols);
  $table->setMarker('checkOverdue', _("Marked items are overdue."));
  $table->width = "90";
  $table->display($table);
  Creditor::addInfoDialog('.pagerclick');
  Forms::end();
  Page::end();

