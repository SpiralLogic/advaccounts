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
  Page::start(_($help_context = "Supplier Inquiry"), SA_SUPPTRANSVIEW);
  $creditor_id = Input::getPost('creditor_id', INPUT::NUMERIC, -1);
  if (isset($_GET['FromDate'])) {
    $_POST['TransAfterDate'] = $_GET['FromDate'];
  }
  if (isset($_GET['ToDate'])) {
    $_POST['TransToDate'] = $_GET['ToDate'];
  }
  Forms::start();
  if (!$creditor_id) {
    $_POST['creditor_id'] = $creditor_id = Session::getGlobal('creditor_id', -1);
  }
  Table::start('tablestyle_noborder');
  Row::start();
  Creditor::cells(_(''), 'creditor_id', null, true);
  Forms::dateCells(_("From:"), 'TransAfterDate', '', null, -90);
  Forms::dateCells(_("To:"), 'TransToDate');
  Purch_Allocation::row("filterType", null);
  Forms::submitCells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
  Row::end();
  Table::end();
  Display::div_start('totals_tbl');
  if ($creditor_id > 0) {
    $supplier_record = Creditor::get_to_trans($creditor_id);
    displaySupplierSummary($supplier_record);
    Session::setGlobal('creditor_id', $creditor_id);
  }
  Display::div_end();
  if (Input::post('RefreshInquiry')) {
    Ajax::activate('totals_tbl');
  }
  if (AJAX_REFERRER && !empty($_POST['q'])) {
    $searchArray = explode(' ', $_POST['q']);
    unset($_POST['creditor_id']);
  }
  $date_after = Dates::dateToSql($_POST['TransAfterDate']);
  $date_to    = Dates::dateToSql($_POST['TransToDate']);
  // Sherifoz 22.06.03 Also get the description
  $sql
    = "SELECT trans.type,
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
		((trans.type = " . ST_SUPPINVOICE . " OR trans.type = " . ST_SUPPCREDIT . ") AND trans.due_date < '" . Dates::today(true) . "') AS OverDue,
 	(ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount - trans.alloc) <= 0.005) AS Settled
 	FROM creditor_trans as trans, suppliers as supplier
 	WHERE supplier.creditor_id = trans.creditor_id
 	AND trans.ov_amount != 0"; // exclude voided transactions
  if (AJAX_REFERRER && !empty($_POST['q'])) {
    foreach ($searchArray as $quicksearch) {
      if (empty($quicksearch)) {
        continue;
      }
      $quicksearch = "%" . $quicksearch . "%";
      $sql .= " AND (";
      $sql .= " supplier.name LIKE " . DB::quote($quicksearch) . " OR trans.trans_no LIKE " . DB::quote($quicksearch) . " OR trans.reference LIKE " . DB::quote($quicksearch) . " OR trans.supplier_reference LIKE " . DB::quote($quicksearch) . ")";
    }
  } else {
    $sql
      .= " AND trans . tran_date >= '$date_after'
	 AND trans . tran_date <= '$date_to'";
  }
  if ($creditor_id > 0) {
    $sql .= " AND trans.creditor_id = " . DB::quote($creditor_id);
  }
  if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
    if (($_POST['filterType'] == '1')) {
      $sql .= " AND (trans.type = " . ST_SUPPINVOICE . " OR trans.type = " . ST_BANKDEPOSIT . ")";
    } elseif (($_POST['filterType'] == '2')) {
      $sql .= " AND trans.type = " . ST_SUPPINVOICE . " ";
    } elseif (($_POST['filterType'] == '6')) {
      $sql .= " AND trans.type = " . ST_SUPPINVOICE . " ";
    } elseif ($_POST['filterType'] == '3') {
      $sql .= " AND (trans.type = " . ST_SUPPAYMENT . " OR trans.type = " . ST_BANKPAYMENT . ") ";
    } elseif (($_POST['filterType'] == '4') || ($_POST['filterType'] == '5')) {
      $sql .= " AND trans.type = " . ST_SUPPCREDIT . " ";
    }
    if (($_POST['filterType'] == '2') || ($_POST['filterType'] == '5')) {
      $today = Dates::today(true);
      $sql .= " AND trans.due_date < '$today' ";
    }
  }
  $cols = array(
    _("Type")        => array('fun' => 'sysTypeName', 'ord' => ''),
    _("#")           => array('fun' => 'viewTrans', 'ord' => ''),
    _("Reference"),
    _("Supplier")    => array('type' => 'id'),
    _("Supplier ID") => 'skip',
    _("Supplier #"),
    _("Date")        => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
    _("Due Date")    => array(
      'type' => 'date', 'fun' => 'due_date'
    ),
    _("Currency")    => array('align' => 'center'),
    _("Debit")       => array(
      'align' => 'right', 'fun' => 'formatDebit'
    ),
    _("Credit")      => array(
      'align' => 'right', 'insert' => true, 'fun' => 'formatCredit'
    ),
    array(
      'insert' => true, 'fun' => 'viewGl'
    ),
    array(
      'insert' => true, 'fun' => 'creditLink'
    ),
    array(
      'insert' => true, 'fun' => 'printLink'
    )
  );
  if ($creditor_id > 0) {
    $cols[_("Supplier")] = 'skip';
    $cols[_("Currency")] = 'skip';
  }
  /*show a table of the transactions returned by the sql */
  $table = DB_Pager::new_DB_Pager('trans_tbl', $sql, $cols);
  $table->setMarker('checkOverdue', _("Marked items are overdue."));
  $table->width = "90";
  $table->display($table);
  Creditor::addInfoDialog('.pagerclick');
  Forms::end();
  Page::end();
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
  function viewTrans($trans) {
    return GL_UI::viewTrans($trans["type"], $trans["trans_no"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function due_date($row) {
    return ($row["type"] == ST_SUPPINVOICE) || ($row["type"] == ST_SUPPCREDIT) ? $row["due_date"] : '';
  }

  /**
   * @param $row
   *
   * @return string
   */
  function viewGl($row) {
    return GL_UI::view($row["type"], $row["trans_no"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function creditLink($row) {
    return $row['type'] == ST_SUPPINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
      DB_Pager::link(_("Credit"), "/purchases/supplier_credit.php?New=1&invoice_no=" . $row['trans_no'], ICON_CREDIT) : '';
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function formatDebit($row) {
    $value = $row["TotalAmount"];
    return $value >= 0 ? Num::priceFormat($value) : '';
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function formatCredit($row) {
    $value = -$row["TotalAmount"];
    return $value > 0 ? Num::priceFormat($value) : '';
  }

  /**
   * @param $row
   *
   * @return string
   */
  function printLink($row) {
    if ($row['type'] == ST_SUPPAYMENT || $row['type'] == ST_BANKPAYMENT || $row['type'] == ST_SUPPCREDIT) {
      return Reporting::print_doc_link($row['trans_no'] . "-" . $row['type'], _("Remittance"), true, ST_SUPPAYMENT, ICON_PRINT);
    }
  }

  /**
   * @param $row
   *
   * @return bool
   */
  function checkOverdue($row) {
    return $row['OverDue'] == 1 && (abs($row["TotalAmount"]) - $row["Allocated"] != 0);
  }

  /**
   * @param $supplier_record
   */
  function displaySupplierSummary($supplier_record) {
    $past_due1     = DB_Company::get_pref('past_due_days');
    $past_due2     = 2 * $past_due1;
    $txt_now_due   = "1-" . $past_due1 . " " . _('Days');
    $txt_past_due1 = $past_due1 + 1 . "-" . $past_due2 . " " . _('Days');
    $txt_past_due2 = _('Over') . " " . $past_due2 . " " . _('Days');
    Table::start('tablestyle width90');
    $th = array(
      _("Currency"),
      _("Terms"),
      _("Current"),
      $txt_now_due,
      $txt_past_due1,
      $txt_past_due2,
      _("Total Balance"),
      _("Total For Search Period")
    );
    Table::header($th);
    Row::start();
    Cell::label($supplier_record["curr_code"]);
    Cell::label($supplier_record["terms"]);
    Cell::amount($supplier_record["Balance"] - $supplier_record["Due"]);
    Cell::amount($supplier_record["Due"] - $supplier_record["Overdue1"]);
    Cell::amount($supplier_record["Overdue1"] - $supplier_record["Overdue2"]);
    Cell::amount($supplier_record["Overdue2"]);
    Cell::amount($supplier_record["Balance"]);
    Cell::amount(Creditor::get_oweing($_POST['creditor_id'], $_POST['TransAfterDate'], $_POST['TransToDate']));
    Row::end();
    Table::end(1);
  }
