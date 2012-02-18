<?php

	/* * ********************************************************************
		 Copyright (C) Advanced Group PTY LTD
		 Released under the terms of the GNU General Public License, GPL,
		 as published by the Free Software Foundation, either version 3
		 of the License, or (at your option) any later version.
		 This program is distributed in the hope that it will be useful,
		 but WITHOUT ANY WARRANTY; without even the implied warranty of
		 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
		 See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
		 * ********************************************************************* */

	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
	Page::set_security(SA_CUSTSTATREP);

	print_statements();

	function getTransactions($debtorno, $date, $incAllocations = false) {
		$sql = "SELECT debtor_trans.*,
				(debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
				debtor_trans.ov_freight_tax + debtor_trans.ov_discount)
				AS TotalAmount, debtor_trans.alloc AS Allocated,
				((debtor_trans.type = " . ST_SALESINVOICE . ")
					AND debtor_trans.due_date < '$date') AS OverDue
 			FROM debtor_trans
 			WHERE debtor_trans.tran_date <= '$date' AND debtor_trans.debtor_no = " . DB::escape($debtorno) . "
 				AND debtor_trans.type <> " . ST_CUSTDELIVERY . "

 				AND (debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
				debtor_trans.ov_freight_tax + debtor_trans.ov_discount) != 0	";
		if ($incAllocations) {
			$sql .= " AND (debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
				debtor_trans.ov_freight_tax + debtor_trans.ov_discount - debtor_trans.alloc) != 0";
		}
		$sql .= " ORDER BY debtor_trans.branch_id, debtor_trans.tran_date";
		return DB::query($sql, "No transactions were returned");
	}

	function getTransactionPO($no) {
		$sql = "SELECT customer_ref FROM sales_orders WHERE order_no=$no";
		$result = DB::query($sql, "Could not retrieve any branches");
		$myrow = DB::fetch_assoc($result);
		return $myrow['customer_ref'];
	}

	function print_statements() {
		global $systypes_array;
		include_once(APPPATH . "reports/pdf.php");
		$doc_Statement = "Statement";
		$doc_as_of = "as of";
		$customer = $_POST['PARAM_0'];
		$currency = $_POST['PARAM_1'];
		$email = $_POST['PARAM_2'];
		$comments = $_POST['PARAM_3'];
		$incPayments = $_POST['PARAM_4'];
		$incNegatives = $_POST['PARAM_5'];
		$incAllocations = $_POST['PARAM_6'];
		$doctype = ST_STATEMENT;
		$doc_Outstanding = $doc_Over = $doc_Days = $doc_Current = $doc_Total_Balance = null;
		$dec = User::price_dec();
		$cols = array(4, 80, 120, 180, 230, 280, 320, 385, 450, 515);
		//$headers in doctext.inc
		$aligns = array('left', 'left', 'left', 'left', 'left', 'right', 'right', 'right', 'right');
		$params = array('comments' => $comments);
		$cur = DB_Company::get_pref('curr_default');
		$PastDueDays1 = DB_Company::get_pref('past_due_days');
		$PastDueDays2 = 2 * $PastDueDays1;
		if ($email == 0) {
			$rep = new ADVReport(_('STATEMENT'), "StatementBulk", User::pagesize());
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);
		}
		$sql = "SELECT debtor_no, name AS DebtorName, address, tax_id, email, curr_code, curdate() AS tran_date, payment_terms FROM debtors";
		if ($customer != ALL_NUMERIC) {
			$sql .= " WHERE debtor_no = " . DB::escape($customer);
		} else {
			$sql .= " ORDER by name";
		}
		$result = DB::query($sql, "The customers could not be retrieved");
		while ($myrow = DB::fetch($result)) {
			$date = date('Y-m-d');
			$myrow['order_'] = "";
			$CustomerRecord = Debtor::get_details($myrow['debtor_no']);
			if (round($CustomerRecord["Balance"], 2) == 0) {
				continue;
			}
			if ($CustomerRecord["Balance"] < 0 && !$incNegatives) {
				continue;
			}
			$baccount = Bank_Account::get_default($myrow['curr_code']);
			$params['bankaccount'] = $baccount['id'];
			$TransResult = getTransactions($myrow['debtor_no'], $date, !$incAllocations);
			if ((DB::num_rows($TransResult) == 0)) { //|| ($CustomerRecord['Balance'] == 0)
				continue;
			}
			$transactions = array();
			while ($transaction = DB::fetch_assoc($TransResult)) {
				$transactions[] = $transaction;
			}
			if ($email == 1) {
				$rep = new ADVReport("", "", User::pagesize());
				$rep->currency = $cur;
				$rep->Font();
				$rep->title = _('STATEMENT');
				$rep->filename = "Statement" . $myrow['debtor_no'] . ".pdf";
				$rep->Info($params, $cols, null, $aligns);
			}
			$prev_branch = 0;
			for ($i = 0; $i < count($transactions); $i++) {
				$myrow2 = $transactions[$i];
				$DisplayTotal = Num::format(Abs($myrow2["TotalAmount"]), $dec);
				if ($myrow2["Allocated"] > 0) {
					$DisplayAlloc = Num::format($myrow2["Allocated"], $dec);
					$DisplayNet = Num::format($DisplayTotal - $DisplayAlloc, $dec);
				} else {
					$DisplayAlloc = '0.00';
					$DisplayNet = $DisplayTotal;
				}
				if ($DisplayNet == 0 && !$incAllocations) {
					continue;
				}
				if (($myrow2['type'] == ST_CUSTPAYMENT || $myrow2['type'] == ST_BANKPAYMENT) && !($incPayments || $incAllocations)) {
					continue;
				}
				if ($prev_branch != $transactions[$i]['branch_id']) {
					$rep->Header2($myrow, Sales_Branch::get($transactions[$i]['branch_id']), null, $baccount, ST_STATEMENT);
					$rep->NewLine();
					if ($rep->currency != $myrow['curr_code']) {
						include(DOCROOT . "reporting/includes/doctext2.php");
					} else {
						include(DOCROOT . "reporting/includes/doctext.php");
					}
					$rep->fontSize += 2;
					$rep->TextCol(0, 8, $doc_Outstanding);
					$rep->fontSize -= 2;
					$rep->NewLine(2);
					$prev_branch = $transactions[$i]['branch_id'];
				}
				$rep->TextCol(0, 1, $systypes_array[$myrow2['type']], -2);
				if ($myrow2['type'] == '10') {
					$rep->TextCol(2, 3, getTransactionPO($myrow2['order_']), -2);
				} else {
					$rep->TextCol(2, 3, '', -2);
				}
				$rep->TextCol(1, 2, $myrow2['reference'], -2);
				$rep->TextCol(3, 4, Dates::sql2date($myrow2['tran_date']), -2);
				if ($myrow2['type'] == ST_SALESINVOICE) {
					$rep->TextCol(4, 5, Dates::sql2date($myrow2['due_date']), -2);
				}
				if ($myrow2['type'] == ST_SALESINVOICE) {
					$rep->TextCol(5, 6, $DisplayTotal, -2);
				} else {
					$rep->TextCol(6, 7, $DisplayTotal, -2);
				}
				$rep->TextCol(7, 8, $DisplayAlloc, -2);
				if ($myrow2['type'] == ST_SALESINVOICE || $DisplayNet == 0) {
					$rep->TextCol(8, 9, $DisplayNet, -2);
				} else {
					$rep->TextCol(8, 9, Num::format($DisplayNet * -1, $dec), -2);
				}
				$rep->NewLine();
				if ($rep->row < $rep->bottomMargin + (10 * $rep->lineHeight)) {
					$rep->Header2($myrow, null, null, $baccount, ST_STATEMENT);
				}
			}
			$doc_Current = _("Current");
			$nowdue = "1-" . $PastDueDays1 . " " . $doc_Days;
			$pastdue1 = $PastDueDays1 + 1 . "-" . $PastDueDays2 . " " . $doc_Days;
			$pastdue2 = $doc_Over . " " . $PastDueDays2 . " " . $doc_Days;
			$str = array($doc_Current, $nowdue, $pastdue1, $pastdue2, $doc_Total_Balance);
			$str2 = array(
				Num::format(($CustomerRecord["Balance"] - $CustomerRecord["Due"]), $dec),
				Num::format(($CustomerRecord["Due"] - $CustomerRecord["Overdue1"]), $dec),
				Num::format(($CustomerRecord["Overdue1"] - $CustomerRecord["Overdue2"]), $dec),
				Num::format($CustomerRecord["Overdue2"], $dec),
				Num::format($CustomerRecord["Balance"], $dec));
			$col = array(
				$rep->cols[0], $rep->cols[0] + 110, $rep->cols[0] + 210, $rep->cols[0] + 310, $rep->cols[0] + 410, $rep->cols[0] + 510);
			$rep->row = $rep->bottomMargin + (10 * $rep->lineHeight - 6);
			for ($i = 0; $i < 5; $i++) {
				$rep->TextWrap($col[$i], $rep->row, $col[$i + 1] - $col[$i], $str[$i], 'right');
			}
			$rep->NewLine();
			for ($i = 0; $i < 5; $i++) {
				$rep->TextWrap($col[$i], $rep->row, $col[$i + 1] - $col[$i], $str2[$i], 'right');
			}
			if ($email == 1) {
				$rep->End($email, $doc_Statement . " " . $doc_as_of . " " . Dates::sql2date($date), $myrow, ST_STATEMENT);
			}
		}
		if ($email == 0) {
			$rep->End();
		}
	}

