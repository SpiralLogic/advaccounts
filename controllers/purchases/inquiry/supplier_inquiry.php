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
  Page::start(_($help_context = "Supplier Inquiry"), SA_SUPPTRANSVIEW);
  if (isset($_GET['supplier_id'])) {
    $_POST['supplier_id'] = $_GET['supplier_id'];
  }
  if (isset($_GET['FromDate'])) {
    $_POST['TransAfterDate'] = $_GET['FromDate'];
  }
  if (isset($_GET['ToDate'])) {
    $_POST['TransToDate'] = $_GET['ToDate'];
  }
  start_form();
  if (!isset($_POST['supplier_id'])) {
    $_POST['supplier_id'] = Session::i()->getGlobal('creditor');
  }
  Table::start('tablestyle_noborder');
  Row::start();
  Creditor::cells(_("Supplier:"), 'supplier_id', NULL, TRUE);
  date_cells(_("From:"), 'TransAfterDate', '', NULL, -90);
  date_cells(_("To:"), 'TransToDate');
  Purch_Allocation::row("filterType", NULL);
  submit_cells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
  Row::end();
  Table::end();
  Session::i()->setGlobal('creditor',$_POST['supplier_id']);
  Display::div_start('totals_tbl');
  if (($_POST['supplier_id'] != "") && ($_POST['supplier_id'] != ALL_TEXT)) {
    $supplier_record = Creditor::get_to_trans($_POST['supplier_id']);
    display_supplier_summary($supplier_record);
  }
  Display::div_end();
  if (get_post('RefreshInquiry')) {
    Ajax::i()->activate('totals_tbl');
  }
  if (AJAX_REFERRER && !empty($_POST['ajaxsearch'])) {
    $searchArray = explode(' ', $_POST['ajaxsearch']);
    unset($_POST['supplier_id']);
  }
  $date_after = Dates::date2sql($_POST['TransAfterDate']);
  $date_to = Dates::date2sql($_POST['TransToDate']);
  // Sherifoz 22.06.03 Also get the description
  $sql
    = "SELECT trans.type,
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
		((trans.type = " . ST_SUPPINVOICE . " OR trans.type = " . ST_SUPPCREDIT . ") AND trans.due_date < '" . Dates::date2sql(Dates::today()) . "') AS OverDue,
 	(ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount - trans.alloc) <= 0.005) AS Settled
 	FROM creditor_trans as trans, suppliers as supplier
 	WHERE supplier.supplier_id = trans.supplier_id
 	AND trans.ov_amount != 0"; // exclude voided transactions
  if (AJAX_REFERRER && !empty($_POST['ajaxsearch'])) {
    foreach ($searchArray as $ajaxsearch) {
      if (empty($ajaxsearch)) {
        continue;
      }
      $ajaxsearch = "%" . $ajaxsearch . "%";
      $sql .= " AND (";
      $sql .= " supplier.supp_name LIKE " . DB::quote($ajaxsearch) . " OR trans.trans_no LIKE " . DB::quote($ajaxsearch) . " OR trans.reference LIKE " . DB::quote($ajaxsearch) . " OR trans.supp_reference LIKE " . DB::quote($ajaxsearch) . ")";
    }
  }
  else {
    $sql
      .= " AND trans . tran_date >= '$date_after'
	 AND trans . tran_date <= '$date_to'";
  }
  if (Input::post('supplier_id')) {
    $sql .= " AND trans.supplier_id = " . DB::quote($_POST['supplier_id']);
  }
  if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
    if (($_POST['filterType'] == '1')) {
      $sql .= " AND (trans.type = " . ST_SUPPINVOICE . " OR trans.type = " . ST_BANKDEPOSIT . ")";
    }
    elseif (($_POST['filterType'] == '2')) {
      $sql .= " AND trans.type = " . ST_SUPPINVOICE . " ";
    }
    elseif (($_POST['filterType'] == '6')) {
      $sql .= " AND trans.type = " . ST_SUPPINVOICE . " ";
    }
    elseif ($_POST['filterType'] == '3') {
      $sql .= " AND (trans.type = " . ST_SUPPAYMENT . " OR trans.type = " . ST_BANKPAYMENT . ") ";
    }
    elseif (($_POST['filterType'] == '4') || ($_POST['filterType'] == '5')) {
      $sql .= " AND trans.type = " . ST_SUPPCREDIT . " ";
    }
    if (($_POST['filterType'] == '2') || ($_POST['filterType'] == '5')) {
      $today = Dates::date2sql(Dates::today());
      $sql .= " AND trans.due_date < '$today' ";
    }
  }
  $cols = array(
    _("Type") => array('fun' => 'systype_name', 'ord' => ''),
    _("#") => array('fun' => 'trans_view', 'ord' => ''),
    _("Reference"),
    _("Supplier") => array('type' => 'id'),
    _("Supplier ID") => 'skip',
    _("Supplier #"),
    _("Date") => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
    _("Due Date") => array(
      'type' => 'date', 'fun' => 'due_date'
    ),
    _("Currency") => array('align' => 'center'),
    _("Debit") => array(
      'align' => 'right', 'fun' => 'fmt_debit'
    ),
    _("Credit") => array(
      'align' => 'right', 'insert' => TRUE, 'fun' => 'fmt_credit'
    ),
    array(
      'insert' => TRUE, 'fun' => 'gl_view'
    ),
    array(
      'insert' => TRUE, 'fun' => 'credit_link'
    ),
    array(
      'insert' => TRUE, 'fun' => 'prt_link'
    )
  );
  if (Input::post('supplier_id')) {
    $cols[_("Supplier")] = 'skip';
    $cols[_("Currency")] = 'skip';
  }
  /*show a table of the transactions returned by the sql */
  $table =& db_pager::new_db_pager('trans_tbl', $sql, $cols);
  $table->set_marker('check_overdue', _("Marked items are overdue."));
  $table->width = "90";
  DB_Pager::display($table);
  Creditor::addInfoDialog('.pagerclick');
  end_form();
  Page::end();
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
  function trans_view($trans) {
    return GL_UI::trans_view($trans["type"], $trans["trans_no"]);
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
  function gl_view($row) {
    return GL_UI::view($row["type"], $row["trans_no"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function credit_link($row) {
    return $row['type'] == ST_SUPPINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
      DB_Pager::link(_("Credit"), "/purchases/supplier_credit.php?New=1&invoice_no=" . $row['trans_no'], ICON_CREDIT) : '';
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function fmt_debit($row) {
    $value = $row["TotalAmount"];
    return $value >= 0 ? Num::price_format($value) : '';
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function fmt_credit($row) {
    $value = -$row["TotalAmount"];
    return $value > 0 ? Num::price_format($value) : '';
  }

  /**
   * @param $row
   *
   * @return string
   */
  function prt_link($row) {
    if ($row['type'] == ST_SUPPAYMENT || $row['type'] == ST_BANKPAYMENT || $row['type'] == ST_SUPPCREDIT) {
      return Reporting::print_doc_link($row['trans_no'] . "-" . $row['type'], _("Remittance"), TRUE, ST_SUPPAYMENT, ICON_PRINT);
    }
  }

  /**
   * @param $row
   *
   * @return bool
   */
  function check_overdue($row) {
    return $row['OverDue'] == 1 && (abs($row["TotalAmount"]) - $row["Allocated"] != 0);
  }

  /**
   * @param $supplier_record
   */
  function display_supplier_summary($supplier_record) {
    $past_due1 = DB_Company::get_pref('past_due_days');
    $past_due2 = 2 * $past_due1;
    $txt_now_due = "1-" . $past_due1 . " " . _('Days');
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
    Cell::amount(Creditor::get_oweing($_POST['supplier_id'], $_POST['TransAfterDate'], $_POST['TransToDate']));
    Row::end();
    Table::end(1);
  }
