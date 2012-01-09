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
	ref_cells(_("Ref"), 'reference', '', null, '', true);
	Debtor::cells(_("Select a customer: "), 'customer_id', null, true);
	date_cells(_("From:"), 'TransAfterDate', '', null, -30);
	date_cells(_("To:"), 'TransToDate', '', null, 1);
	if (!isset($_POST['filterType'])) {
		$_POST['filterType'] = 0;
	}
	Debtor_Payment::allocations_select(null, 'filterType', $_POST['filterType'], true);
	submit_cells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
	end_row();
	end_table();
	Session::i()->global_customer = $_POST['customer_id'];
	Display::div_start('totals_tbl');
	if ($_POST['customer_id'] != "" && $_POST['customer_id'] != ALL_TEXT && !isset($_POST['ajaxsearch'])) {
		$customer_record = Debtor::get_details($_POST['customer_id'], $_POST['TransToDate']);
		display_customer_summary($customer_record);
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
		unset($_POST['customer_id']);
		unset($_POST['filterType']);
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
	$sql = "SELECT
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
	$sql .= "trans.alloc AS Allocated,
		((trans.type = " . ST_SALESINVOICE . ")
			AND trans.due_date < '" . Dates::date2sql(Dates::Today()) . "') AS OverDue, SUM(details.quantity - qty_done) as delivered
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
				$sql .= " tran_date LIKE '%" . Dates::date2sql($ajaxsearch, false) . "%' OR";
				continue;
			}
			if (is_numeric($ajaxsearch)) {
				$sql .= " debtor_no = $ajaxsearch OR ";
			}
			$ajaxsearch = DB::quote("%" . $ajaxsearch . "%");
			$sql .= " name LIKE $ajaxsearch ";
			if (is_numeric($ajaxsearch)) $sql .= " OR trans_no LIKE $ajaxsearch OR order_ LIKE $ajaxsearch ";
			$sql .= " OR reference LIKE $ajaxsearch OR br_name LIKE $ajaxsearch) ";
		}
		if (isset($filter) && $filter) {
			$sql .= $filter;
		}
	}
	else {
		$sql .= " AND trans.tran_date >= '$date_after'
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
			$today = Dates::date2sql(Dates::Today());
			$sql .= " AND trans.due_date < '$today'
				AND (trans.ov_amount + trans.ov_gst + trans.ov_freight_tax +
				trans.ov_freight + trans.ov_discount - trans.alloc > 0)";
		}
	}
	if (!AJAX_REFERRER) { 	$sql .= " GROUP BY trans.trans_no,  trans.type";
	}
	DB::query("set @bal:=0");
	$cols = array(
		_("Type") => array('fun' => 'systype_name', 'ord' => ''),
		_("#") => array('fun' => 'trans_view', 'ord' => ''),
		_("Order") => array('fun' => 'order_view'),
		_("Reference") => array('ord' => ''),
		_("Date") => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
		_("Due Date") => array('type' => 'date', 'fun' => 'due_date'),
		_("Customer") => array('ord' => 'asc'),
		_("Branch") => array('ord' => ''),
		_("Currency") => array('align' => 'center'),
		_("Debit") => array('align' => 'right', 'fun' => 'fmt_debit'),
		_("Credit") => array('align' => 'right', 'insert' => true, 'fun' => 'fmt_credit'),
		array('type'=>'skip'),
		_("RB") => array('align' => 'right', 'type' => 'amount'),
		array('insert' => true, 'fun' => 'gl_view'),
		array('insert' => true, 'align' => 'center', 'fun' => 'credit_link'),
		array('insert' => true, 'align' => 'center', 'fun' => 'edit_link'),
		array('insert' => true, 'align' => 'center', 'fun' => 'email_link'),
		array('insert' => true, 'align' => 'center', 'fun' => 'prt_link')
	);
	if (isset($_POST['customer_id']) && $_POST['customer_id'] != ALL_TEXT) {
		$cols[_("Customer")] = 'skip';
		$cols[_("Currency")] = 'skip';
	}
	if (isset($_POST['filterType']) && $_POST['filterType'] == ALL_TEXT || !empty($_POST['ajaxsearch'])) {
		$cols[_("RB")] = 'skip';
	}
	$table =& db_pager::new_db_pager('trans_tbl', $sql, $cols);
	$table->set_marker('check_overdue', _("Marked items are overdue."));
	$table->width = "80%";
	DB_Pager::display($table);
	UI::emailDialogue('c');
	end_form();
	Page::end();
	/**
	 * @param $customer_record
	 */
	function display_customer_summary($customer_record) {
		$past1 = DB_Company::get_pref('past_due_days');
		$past2 = 2 * $past1;
		if (isset($customer_record["dissallow_invoices"]) && $customer_record["dissallow_invoices"] != 0) {
			echo "<div class='center red font4 bold'>" . _("CUSTOMER ACCOUNT IS ON HOLD") . "</div>";
		}
		$nowdue = "1-" . $past1 . " " . _('Days');
		$pastdue1 = $past1 + 1 . "-" . $past2 . " " . _('Days');
		$pastdue2 = _('Over') . " " . $past2 . " " . _('Days');
		start_table('tablestyle width90');
		$th = array(_("Currency"), _("Terms"), _("Current"), $nowdue, $pastdue1, $pastdue2, _("Total Balance"));
		table_header($th);
		start_row();
		label_cell($customer_record["curr_code"]);
		label_cell($customer_record["terms"]);
		amount_cell($customer_record["Balance"] - $customer_record["Due"]);
		amount_cell($customer_record["Due"] - $customer_record["Overdue1"]);
		amount_cell($customer_record["Overdue1"] - $customer_record["Overdue2"]);
		amount_cell($customer_record["Overdue2"]);
		amount_cell($customer_record["Balance"]);
		end_row();
		end_table();
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
	 * @param $row
	 *
	 * @return null|string
	 */
	function order_view($row) {
		return $row['order_'] > 0 ? Debtor::trans_view(ST_SALESORDER, $row['order_']) : "";
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
		return $row["type"] == ST_SALESINVOICE ? $row["due_date"] : '';
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
	 * @return int|string
	 */
	function fmt_debit($row) {
		$value = $row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_BANKDEPOSIT ?
		 -$row["TotalAmount"] : $row["TotalAmount"];
		return $value >= 0 ? Num::price_format($value) : '';
	}

	/**
	 * @param $row
	 *
	 * @return int|string
	 */
	function fmt_credit($row) {
		$value = !($row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_BANKDEPOSIT) ?
		 -$row["TotalAmount"] : $row["TotalAmount"];
		return $value > 0 ? Num::price_format($value) : '';
	}

	/**
	 * @param $row
	 *
	 * @return string
	 */
	function credit_link($row) {
		return $row['type'] == ST_SALESINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
		 DB_Pager::link(_("Credit"), "/sales/customer_credit_invoice.php?InvoiceNumber=" . $row['trans_no'], ICON_CREDIT) : '';
	}

	/**
	 * @param $row
	 *
	 * @return string
	 */
	function edit_link($row) {
		$str = '';
		switch ($row['type']) {
			case ST_SALESINVOICE:
				if (Voiding::get(ST_SALESINVOICE, $row["trans_no"]) === false || AJAX_REFERRER) {
					if ($row['Allocated'] == 0) {
						$str = "/sales/customer_invoice.php?ModifyInvoice=" . $row['trans_no'];
					}
					else {
						$str = "/sales/customer_invoice.php?ViewInvoice=" . $row['trans_no'];
					}
				}
				break;
			case ST_CUSTCREDIT:
				if (Voiding::get(ST_CUSTCREDIT, $row["trans_no"]) === false && $row['Allocated'] == 0) {
					if ($row['order_'] == 0) {
						$str = "/sales/credit_note_entry.php?ModifyCredit=" . $row['trans_no'];
					}
					else {
						$str = "/sales/customer_credit_invoice.php?ModifyCredit=" . $row['trans_no'];
					}
				}
				break;
			case ST_CUSTDELIVERY:
				if ($row['delivered']==0) continue;
				if (Voiding::get(ST_CUSTDELIVERY, $row["trans_no"]) === false) {
					$str = "/sales/customer_delivery.php?ModifyDelivery=" . $row['trans_no'];
				}
				break;
		}
		if ($str != "" && (!DB_AuditTrail::is_closed_trans($row['type'], $row["trans_no"]) || $row['type'] == ST_SALESINVOICE)) {
			return DB_Pager::link(_('Edit'), $str, ICON_EDIT);
		}
		return '';
	}

	/**
	 * @param $row
	 *
	 * @return string
	 */
	function prt_link($row) {
		if ($row['type'] != ST_CUSTPAYMENT && $row['type'] != ST_CUSTREFUND && $row['type'] != ST_BANKDEPOSIT
		) // customer payment or bank deposit printout not defined yet.
		{
			return Reporting::print_doc_link($row['trans_no'] . "-" . $row['type'], _("Print"), true, $row['type'], ICON_PRINT, 'button printlink');
		}
		else {
			return Reporting::print_doc_link($row['trans_no'] . "-" . $row['type'], _("Receipt"), true, $row['type'], ICON_PRINT, 'button printlink');
		}
	}

	/**
	 * @param $row
	 *
	 * @return HTML|string
	 */
	function email_link($row) {
		if ($row['type'] != ST_SALESINVOICE) {
			return;
		}
		HTML::setReturn(true);
		UI::button(false, 'Email', array(
																		'class' => 'button email-button', 'data-emailid' => $row['debtor_no'] . '-' . $row['type'] . '-' . $row['trans_no']
															 ));
		return HTML::setReturn(false);
	}

	/**
	 * @param $row
	 *
	 * @return bool
	 */
	function check_overdue($row) {
		return (isset($row['OverDue']) && $row['OverDue'] == 1) && (abs($row["TotalAmount"]) - $row["Allocated"] != 0);
	}

?>
