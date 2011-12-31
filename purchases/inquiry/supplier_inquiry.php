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
	$page_security = 'SA_SUPPTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 500);
	Page::start(_($help_context = "Supplier Inquiry"));
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
		$_POST['supplier_id'] = Session::i()->supplier_id;
	}
	start_table('tablestyle_noborder');
	start_row();
	Creditor::cells(_("Select a supplier:"), 'supplier_id', null, true);
	date_cells(_("From:"), 'TransAfterDate', '', null, -90);
	date_cells(_("To:"), 'TransToDate');
	Purch_Allocation::row("filterType", null);
	submit_cells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
	end_row();
	end_table();
	Session::i()->supplier_id = $_POST['supplier_id'];
	function display_supplier_summary($supplier_record) {
		$past1 = DB_Company::get_pref('past_due_days');
		$past2 = 2 * $past1;
		$nowdue = "1-" . $past1 . " " . _('Days');
		$pastdue1 = $past1 + 1 . "-" . $past2 . " " . _('Days');
		$pastdue2 = _('Over') . " " . $past2 . " " . _('Days');
		start_table('tablestyle width90');
		$th = array(
			_("Currency"), _("Terms"), _("Current"), $nowdue, $pastdue1, $pastdue2, _("Total Balance"), _("Total For Search Period")
		);
		table_header($th);
		start_row();
		label_cell($supplier_record["curr_code"]);
		label_cell($supplier_record["terms"]);
		amount_cell($supplier_record["Balance"] - $supplier_record["Due"]);
		amount_cell($supplier_record["Due"] - $supplier_record["Overdue1"]);
		amount_cell($supplier_record["Overdue1"] - $supplier_record["Overdue2"]);
		amount_cell($supplier_record["Overdue2"]);
		amount_cell($supplier_record["Balance"]);
		amount_cell(Creditor::get_oweing($_POST['supplier_id'], $_POST['TransAfterDate'], $_POST['TransToDate']));
		end_row();
		end_table(1);
	}

	Display::div_start('totals_tbl');
	if (($_POST['supplier_id'] != "") && ($_POST['supplier_id'] != ALL_TEXT)) {
		$supplier_record = Creditor::get_to_trans($_POST['supplier_id']);
		display_supplier_summary($supplier_record);
	}
	Display::div_end();
	if (get_post('RefreshInquiry')) {
		Ajax::i()->activate('totals_tbl');
	}
	function systype_name($dummy, $type) {
		global $systypes_array;
		return $systypes_array[$type];
	}

	function trans_view($trans) {
		return GL_UI::trans_view($trans["type"], $trans["trans_no"]);
	}

	function due_date($row) {
		return ($row["type"] == ST_SUPPINVOICE) || ($row["type"] == ST_SUPPCREDIT) ? $row["due_date"] : '';
	}

	function gl_view($row) {
		return GL_UI::view($row["type"], $row["trans_no"]);
	}

	function credit_link($row) {
		return $row['type'] == ST_SUPPINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
		 DB_Pager::link(_("Credit This"), "/purchases/supplier_credit.php?New=1&invoice_no=" . $row['trans_no'], ICON_CREDIT) : '';
	}

	function fmt_debit($row) {
		$value = $row["TotalAmount"];
		return $value >= 0 ? Num::price_format($value) : '';
	}

	function fmt_credit($row) {
		$value = -$row["TotalAmount"];
		return $value > 0 ? Num::price_format($value) : '';
	}

	function prt_link($row) {
		if ($row['type'] == ST_SUPPAYMENT || $row['type'] == ST_BANKPAYMENT || $row['type'] == ST_SUPPCREDIT) {
			return Reporting::print_doc_link($row['trans_no'] . "-" . $row['type'], _("Print Remittance"), true, ST_SUPPAYMENT, ICON_PRINT);
		}
	}

	function check_overdue($row) {
		return $row['OverDue'] == 1 && (abs($row["TotalAmount"]) - $row["Allocated"] != 0);
	}

	if (AJAX_REFERRER && !empty($_POST['ajaxsearch'])) {
		$searchArray = explode(' ', $_POST['ajaxsearch']);
		unset($_POST['supplier_id']);
	}
	$date_after = Dates::date2sql($_POST['TransAfterDate']);
	$date_to = Dates::date2sql($_POST['TransToDate']);
	// Sherifoz 22.06.03 Also get the description
	$sql = "SELECT trans.type,
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
		((trans.type = " . ST_SUPPINVOICE . " OR trans.type = " . ST_SUPPCREDIT . ") AND trans.due_date < '" . Dates::date2sql(Dates::Today()) . "') AS OverDue,
 	(ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount - trans.alloc) <= 0.005) AS Settled
 	FROM supp_trans as trans, suppliers as supplier
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
		$sql .= " AND trans . tran_date >= '$date_after'
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
			$today = Dates::date2sql(Dates::Today());
			$sql .= " AND trans.due_date < '$today' ";
		}
	}
	$cols = array(
		_("Type") => array('fun' => 'systype_name', 'ord' => ''),
		_("#") => array('fun' => 'trans_view', 'ord' => ''),
		_("Reference"),
		_("Supplier") => array('type' => 'id'),
		_("Supplier ID") => 'skip',
		_("Supplier's Reference"),
		_("Date") => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'),
		_("Due Date") => array(
			'type' => 'date', 'fun' => 'due_date'
		),
		_("Currency") => array('align' => 'center'),
		_("Debit") => array(
			'align' => 'right', 'fun' => 'fmt_debit'
		),
		_("Credit") => array(
			'align' => 'right', 'insert' => true, 'fun' => 'fmt_credit'
		),
		array(
			'insert' => true, 'fun' => 'gl_view'
		),
		array(
			'insert' => true, 'fun' => 'credit_link'
		),
		array(
			'insert' => true, 'fun' => 'prt_link'
		)
	);
	if (Input::post('supplier_id')) {
		$cols[_("Supplier")] = 'skip';
		$cols[_("Currency")] = 'skip';
	}
	/*show a table of the transactions returned by the sql */
	$table =& db_pager::new_db_pager('trans_tbl', $sql, $cols);
	$table->set_marker('check_overdue', _("Marked items are overdue."));
	$table->width = "80";
	DB_Pager::display($table);
	Creditor::addInfoDialog('.pagerclick');
	end_form();
	Page::end();

?>
