<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  //require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  JS::open_window(900, 500);
  if (isset($_GET['id'])) {
    $_GET['customer_id'] = $_GET['id'];
  }
  Page::start(_($help_context = "Customer Transactions"), SA_SALESTRANSVIEW, isset($_GET['customer_id']));
  if (isset($_GET['customer_id'])) {
    $_POST['customer_id'] = $_GET['customer_id'];
  }
  start_form();
  if (!isset($_POST['customer_id'])) {
    $_POST['customer_id'] = Session::i()->global_customer;
  }
  start_table('tablestyle_noborder');
  start_row();
  ref_cells(_("Ref"), 'reference', '', NULL, '', TRUE);
  Debtor::cells(_("Select a customer: "), 'customer_id', NULL, TRUE);
  date_cells(_("From:"), 'TransAfterDate', '', NULL, -30);
  date_cells(_("To:"), 'TransToDate', '', NULL, 1);
  if (!isset($_POST['filterType'])) {
    $_POST['filterType'] = 0;
  }
  Debtor_Payment::allocations_select(NULL, 'filterType', $_POST['filterType'], TRUE);
  submit_cells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
  end_row();
  end_table();
  Session::i()->global_customer = $_POST['customer_id'];
  Display::div_start('totals_tbl');
  if ($_POST['customer_id'] != "" && $_POST['customer_id'] != ALL_TEXT && !isset($_POST['ajaxsearch'])) {
    $customer_record = Debtor::get_details($_POST['customer_id'], $_POST['TransToDate']);
    Debtor::display_summary($customer_record);
    echo "<br>";
  }
  Display::div_end();
  if (get_post('RefreshInquiry')) {
    Ajax::i()->activate('totals_tbl');
  }
  $date_after = Dates::date2sql($_POST['TransAfterDate']);
  $date_to = Dates::date2sql($_POST['TransToDate']);
  if (AJAX_REFERRER && isset($_POST['ajaxsearch'])) {
    $searchArray = trim($_POST['ajaxsearch']);
    $searchArray = explode(' ', $searchArray);
    unset($_POST['customer_id'], $_POST['filterType']);
    if ($searchArray[0] == 'd') {
      $filter = " AND type = " . ST_CUSTDELIVERY . " ";
    }
    elseif ($searchArray[0] == 'i') {
      $filter = " AND (type = " . ST_SALESINVOICE . " OR type = " . ST_BANKPAYMENT . ") ";
    }
    elseif ($searchArray[0] == 'p') {
      $filter = " AND (type = " . ST_CUSTPAYMENT . " OR type = " . ST_CUSTREFUND . " OR type = " . ST_BANKDEPOSIT . ") ";
    }
  }
  $sql
    = "SELECT
 		trans.type,
		trans.trans_no,
		trans.order_,
		trans.reference,
		trans.tran_date,
		trans.due_date,
		debtor.name,
		debtor.debtor_no,
		branch.br_name,
		debtor.curr_code,
		(trans.ov_amount + trans.ov_gst + trans.ov_freight
			+ trans.ov_freight_tax + trans.ov_discount)	AS TotalAmount, ";
  if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
    $sql .= "@bal := @bal+(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount), ";
  }
  $sql
    .= "trans.alloc AS Allocated,
		((trans.type = " . ST_SALESINVOICE . ")
			AND trans.due_date < '" . Dates::date2sql(Dates::today()) . "') AS OverDue, SUM(details.quantity - qty_done) as
			still_to_deliver
		FROM debtors as debtor, branches as branch,debtor_trans as trans
		LEFT JOIN debtor_trans_details as details ON (trans.trans_no = details.debtor_trans_no AND trans.type = details.debtor_trans_type) WHERE debtor.debtor_no =
		trans.debtor_no AND trans.branch_id = branch.branch_id";
  if (AJAX_REFERRER && !empty($_POST['ajaxsearch'])) {
    $sql = "SELECT * FROM debtor_trans_view WHERE ";
    foreach ($searchArray as $ajaxsearch) {
      if (empty($ajaxsearch)) {
        continue;
      }
      $sql .= ($ajaxsearch == $searchArray[0]) ? " (" : " AND (";
      if ($ajaxsearch[0] == "$") {
        if (substr($ajaxsearch, -1) == 0 && substr($ajaxsearch, -3, 1) == '.') {
          $ajaxsearch = (substr($ajaxsearch, 0, -1));
        }
        $sql .= "TotalAmount LIKE " . DB::quote('%' . substr($ajaxsearch, 1) . '%') . ") ";
        continue;
      }
      if (stripos($ajaxsearch, '/') > 0) {
        $sql .= " tran_date LIKE '%" . Dates::date2sql($ajaxsearch, FALSE) . "%' OR";
        continue;
      }
      if (is_numeric($ajaxsearch)) {
        $sql .= " debtor_no = $ajaxsearch OR ";
      }
      $ajaxsearch = DB::quote("%" . $ajaxsearch . "%");
      $sql .= " name LIKE $ajaxsearch ";
      if (is_numeric($ajaxsearch)) {
        $sql .= " OR trans_no LIKE $ajaxsearch OR order_ LIKE $ajaxsearch ";
      }
      $sql .= " OR reference LIKE $ajaxsearch OR br_name LIKE $ajaxsearch) ";
    }
    if (isset($filter) && $filter) {
      $sql .= $filter;
    }
  }
  else {
    $sql
      .= " AND trans.tran_date >= '$date_after'
			AND trans.tran_date <= '$date_to'";
  }
  if ($_POST['reference'] != ALL_TEXT) {
    $number_like = "%" . $_POST['reference'] . "%";
    $sql .= " AND trans.reference LIKE " . DB::quote($number_like);
  }
  if (isset($_POST['customer_id']) && $_POST['customer_id'] != ALL_TEXT) {
    $sql .= " AND trans.debtor_no = " . DB::quote($_POST['customer_id']);
  }
  if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
    if ($_POST['filterType'] == '1') {
      $sql .= " AND (trans.type = " . ST_SALESINVOICE . " OR trans.type = " . ST_BANKPAYMENT . ") ";
    }
    elseif ($_POST['filterType'] == '2') {
      $sql .= " AND (trans.type = " . ST_SALESINVOICE . ") ";
    }
    elseif ($_POST['filterType'] == '3') {
      $sql .= " AND (trans.type = " . ST_CUSTPAYMENT . " OR trans.type = " . ST_CUSTREFUND . " OR trans.type = " . ST_BANKDEPOSIT . " OR trans.type = " . ST_BANKDEPOSIT . ") ";
    }
    elseif ($_POST['filterType'] == '4') {
      $sql .= " AND trans.type = " . ST_CUSTCREDIT . " ";
    }
    elseif ($_POST['filterType'] == '5') {
      $sql .= " AND trans.type = " . ST_CUSTDELIVERY . " ";
    }
    elseif ($_POST['filterType'] == '6') {
      $sql .= " AND trans.type = " . ST_SALESINVOICE . " ";
    }
    if ($_POST['filterType'] == '2') {
      $today = Dates::date2sql(Dates::today());
      $sql
        .= " AND trans.due_date < '$today'
				AND (trans.ov_amount + trans.ov_gst + trans.ov_freight_tax +
				trans.ov_freight + trans.ov_discount - trans.alloc > 0)";
    }
  }
  if (!AJAX_REFERRER) {
    $sql .= " GROUP BY trans.trans_no, trans.type";
  }
  DB::query("set @bal:=0");
  $cols = array(
    _("Type") => array(
      'fun' => function ($dummy, $type) {
        global $systypes_array;
        return $systypes_array[$type];
      }
    , 'ord' => ''
    ),
    _("#") => array(
      'fun' => function ($trans) {
        return GL_UI::trans_view($trans["type"], $trans["trans_no"]);
      }
    , 'ord' => ''
    ),
    _("Order") => array(
      'fun' => function ($row) {
        return $row['order_'] > 0 ? Debtor::trans_view(ST_SALESORDER, $row['order_']) : "";
      }
    ),
    _("Reference") => array('ord' => ''),
    _("Date") => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
    _("Due Date") => array(
      'type' => 'date', 'fun' => function ($row) {
        return $row["type"] == ST_SALESINVOICE ? $row["due_date"] : '';
      }
    ),
    _("Customer") => array('ord' => 'asc'),
    array('type' => 'skip'),
    _("Branch") => array('ord' => ''),
    _("Currency") => array('align' => 'center'),
    _("Debit") => array(
      'align' => 'right', 'fun' => function ($row) {
        $value = $row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_BANKDEPOSIT ?
          -$row["TotalAmount"] : $row["TotalAmount"];
        return $value >= 0 ? Num::price_format($value) : '';
      }
    ),
    _("Credit") => array(
      'align' => 'right', 'insert' => TRUE, 'fun' => function ($row) {
        $value = !($row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_BANKDEPOSIT) ?
          -$row["TotalAmount"] : $row["TotalAmount"];
        return $value > 0 ? Num::price_format($value) : '';
      }
    ),
    array('type' => 'skip'),
    _("RB") => array('align' => 'right', 'type' => 'amount'),
    array(
      'insert' => TRUE, 'fun' => function ($row) {
      return GL_UI::view($row["type"], $row["trans_no"]);
    }
    ),
    array(
      'insert' => TRUE, 'align' => 'center', 'fun' => function ($row) {
      return $row['type'] == ST_SALESINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
        DB_Pager::link(_("Credit"), "/sales/customer_credit_invoice.php?InvoiceNumber=" . $row['trans_no'], ICON_CREDIT) : '';
    }
    ),
    array(
      'insert' => TRUE, 'align' => 'center', 'fun' => function ($row) {
      $str = '';
      switch ($row['type']) {
        case ST_SALESINVOICE:
          if (Voiding::get(ST_SALESINVOICE, $row["trans_no"]) === FALSE || AJAX_REFERRER) {
            if ($row['Allocated'] == 0) {
              $str = "/sales/customer_invoice.php?ModifyInvoice=" . $row['trans_no'];
            }
            else {
              $str = "/sales/customer_invoice.php?ViewInvoice=" . $row['trans_no'];
            }
          }
          break;
        case ST_CUSTCREDIT:
          if (Voiding::get(ST_CUSTCREDIT, $row["trans_no"]) === FALSE && $row['Allocated'] == 0) {
            if ($row['order_'] == 0) {
              $str = "/sales/credit_note_entry.php?ModifyCredit=" . $row['trans_no'];
            }
            else {
              $str = "/sales/customer_credit_invoice.php?ModifyCredit=" . $row['trans_no'];
            }
          }
          break;
        case ST_CUSTDELIVERY:
          if ($row['still_to_deliver'] == 0) {
            continue;
          }
          if (Voiding::get(ST_CUSTDELIVERY, $row["trans_no"]) === FALSE) {
            $str = "/sales/customer_delivery.php?ModifyDelivery=" . $row['trans_no'];
          }
          break;
      }
      if ($str != "" && (!DB_AuditTrail::is_closed_trans($row['type'], $row["trans_no"]) || $row['type'] == ST_SALESINVOICE)) {
        return DB_Pager::link(_('Edit'), $str, ICON_EDIT);
      }
      return '';
    }
    ),
    array(
      'insert' => TRUE, 'align' => 'center', 'fun' => function ($row) {
      if ($row['type'] != ST_SALESINVOICE) {
        return;
      }
      HTML::setReturn(TRUE);
      UI::button(FALSE, 'Email', array(
        'class' => 'button email-button',
        'data-emailid' => $row['debtor_no'] . '-' . $row['type'] . '-' . $row['trans_no']
      ));
      return HTML::setReturn(FALSE);
    }
    ),
    array(
      'insert' => TRUE, 'align' => 'center', 'fun' => function ($row) {
      if ($row['type'] != ST_CUSTPAYMENT && $row['type'] != ST_CUSTREFUND && $row['type'] != ST_BANKDEPOSIT
      ) // customer payment or bank deposit printout not defined yet.
      {
        return Reporting::print_doc_link($row['trans_no'] . "-" . $row['type'], _("Print"), TRUE, $row['type'], ICON_PRINT, 'button printlink');
      }
      else {
        return Reporting::print_doc_link($row['trans_no'] . "-" . $row['type'], _("Receipt"), TRUE, $row['type'], ICON_PRINT, 'button printlink');
      }
    }
    )
  );
  if (isset($_POST['customer_id']) && $_POST['customer_id'] != ALL_TEXT) {
    $cols[_("Customer")] = 'skip';
    $cols[_("Currency")] = 'skip';
  }
  if (isset($_POST['filterType']) && $_POST['filterType'] == ALL_TEXT || !empty($_POST['ajaxsearch'])) {
    $cols[_("RB")] = 'skip';
  }
  $table =& db_pager::new_db_pager('trans_tbl', $sql, $cols);
  $table->set_marker(function ($row) {

      return (isset($row['OverDue']) && $row['OverDue'] == 1) && (abs($row["TotalAmount"]) - $row["Allocated"] != 0);
    }
    , _("Marked items are overdue."));
  $table->width = "80%";
  DB_Pager::display($table);
  UI::emailDialogue(CT_CUSTOMER);
  end_form();
  Page::end();
