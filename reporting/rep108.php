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
	function getTransactions($debtorno, $date, $incAllocations = true, $month) {
		$dateend = date('Y-m-d', mktime(0, 0, 0, $month, 0));
		$datestart = date('Y-m-d', mktime(0, 0, 0, $month - 2, 1));
		$sql = "SELECT debtor_trans.*,
				SUM((debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
				debtor_trans.ov_freight_tax + debtor_trans.ov_discount))
				AS TotalAmount, SUM(debtor_trans.alloc) AS Allocated,
				(debtor_trans.type = " . ST_SALESINVOICE . " AND debtor_trans.due_date < '$datestart') AS OverDue
 			FROM debtor_trans
 			WHERE debtor_trans.tran_date <= '$dateend' AND debtor_trans.debtor_no = " . DB::escape($debtorno) . "
 				AND debtor_trans.type <> " . ST_CUSTDELIVERY . "

 				AND (debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
				debtor_trans.ov_freight_tax + debtor_trans.ov_discount) != 0	";
		if ($incAllocations) {
			$sql .= " AND (debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
				debtor_trans.ov_freight_tax + debtor_trans.ov_discount - debtor_trans.alloc) != 0";
		}
		$sql .= " GROUP BY debtor_no, if(debtor_trans.due_date<'$datestart',0,debtor_trans.due_date) ORDER BY debtor_trans.branch_id, debtor_trans.tran_date, debtor_trans.type";
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
		$doc_OpeningBalance = 'Opening Balance';
		$doc_as_of = "as of";
		$customer = $_POST['PARAM_0'];
		$currency = $_POST['PARAM_1'];
		$email = $_POST['PARAM_2'];
		$comments = $_POST['PARAM_3'];
		$incNegatives = $_POST['PARAM_4'];
		$incPayments = $_POST['PARAM_5'];
		$incAllocations = $_POST['PARAM_6'];
		$month = $_POST['PARAM_7'] ? : 0;
		$doctype = ST_STATEMENT;
		$doc_Outstanding = $doc_Over = $doc_Days = $doc_Current = $doc_Total_Balance = null;
		$dec = User::price_dec();
		$cols = array(5, 60, 100, 170, 225, 295, 345, 390, 460, 460);
		$aligns = array('left', 'left', 'left', 'center', 'center', 'left', 'left', 'left', 'right');
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
		$sql = 'SELECT DISTINCT db.*,c.name AS DebtorName,c.tax_id,a.email,c.curr_code, c.payment_terms, CONCAT(a.br_address,CHARACTER(13),a.city," ",a.state," ",a.postcode) as address FROM debtor_balances db, branches a,
		debtors c WHERE db.debtor_no = a.debtor_no AND c.debtor_no=db.debtor_no AND a.branch_ref = "Accounts" AND Balance>0  ';
		if ($customer != ALL_NUMERIC) {
			$sql .= " WHERE debtor_no = " . DB::escape($customer);
		}
		else {
			$sql .= " ORDER by name";
		}
		$result = DB::query($sql, "The customers could not be retrieved");
		while ($myrow = DB::fetch($result)) {
			$date = $myrow['tran_date'] = date('Y-m-1', strtotime("now - $month months"));
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
			$TransResult = getTransactions($myrow['debtor_no'], $date, !$incAllocations && !$incPayments, $month);
			if ((DB::num_rows($TransResult) == 0)) {
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
			$prev_branch = $openingbalance = $balance = 0;
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);
			foreach ($transactions as $i => $trans) {
				if ($trans['OverDue']) {
					$openingbalance = $trans['TotalAmount'] - $trans['Allocated'];
					$balance += $openingbalance;
				}
				else {
					$DisplayTotal = Num::format(abs($trans["TotalAmount"]), $dec);
					$outstanding = abs($trans["TotalAmount"]) - $trans["Allocated"];
					$displayOutstanding = Num::format($outstanding, $dec);
					if (!$incPayments && !$incAllocations && Num::round($outstanding, 2) == 0) {
						continue;
					}
					if (!$incPayments && ($trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_BANKPAYMENT || $trans['type'] == ST_BANKDEPOSIT)) {
						continue;
					}
					if ($incAllocations || $incPayments) {
						$balance += ($trans['type'] == ST_SALESINVOICE) ? $trans["TotalAmount"] : -$trans["TotalAmount"];
					}
					else {
						$balance += ($trans['type'] == ST_SALESINVOICE) ? $outstanding : 0;
					}
				}
				$DisplayBalance = Num::format($balance, $dec);
				if ($prev_branch != $transactions[$i]['branch_id']) {
					$rep->Header2($myrow, Sales_Branch::get($transactions[$i]['branch_id']), null, $baccount, ST_STATEMENT);
					$rep->NewLine();
					if ($rep->currency != $myrow['curr_code']) {
						include(DOCROOT . "reporting/includes/doctext2.php");
					}
					else {
						include(DOCROOT . "reporting/includes/doctext.php");
					}
					$prev_branch = $transactions[$i]['branch_id'];
				}
				if ($openingbalance) {
					$rep->TextCol(0, 8, $doc_OpeningBalance);
					$rep->TextCol(8, 9, Num::format($openingbalance, $dec));
					$rep->NewLine(2);
					$openingbalance = 0;
					continue;
				}
				$rep->TextCol(0, 1, $systypes_array[$trans['type']], -2);
				if ($trans['type'] == ST_SALESINVOICE) {
					$rep->Font('bold');
				}
				$rep->TextCol(1, 2, $trans['reference'], -2);
				if ($trans['type'] == '10') {
					$rep->TextCol(2, 3, getTransactionPO($trans['order_']), -2);
				}
				else {
					$rep->TextCol(2, 3, '', -2);
				}
				$rep->Font();
				$rep->TextCol(3, 4, Dates::sql2date($trans['tran_date']), -2);
				if ($trans['type'] == ST_SALESINVOICE) {
					$rep->TextCol(4, 5, Dates::sql2date($trans['due_date']), -2);
				}
				if ($trans['type'] == ST_SALESINVOICE) {
		if (isset($DisplayTotal))			$rep->TextCol(5, 6, $DisplayTotal, -2);
				}
				else {
					if (isset($DisplayTotal))				$rep->TextCol(6, 7, $DisplayTotal, -2);
				}
				if (isset($displayOutstanding))	$rep->TextCol(7, 8, $displayOutstanding, -2);
				$rep->TextCol(8, 9, $DisplayBalance, -2);
				$rep->NewLine();
				if ($rep->row < $rep->bottomMargin + (10 * $rep->lineHeight)) {
					$rep->Header2($myrow, null, null, $baccount, ST_STATEMENT);
				}
			}
			$rep->Font('bold');
			$doc_Current = _("Current");
			$nowdue = "1-" . $PastDueDays1 . " " . $doc_Days;
			$pastdue1 = $PastDueDays1 + 1 . "-" . $PastDueDays2 . " " . $doc_Days;
			$pastdue2 = $doc_Over . " " . $PastDueDays2 . " " . $doc_Days;
			$str = array($doc_Current, $nowdue, $pastdue1, $pastdue2, $doc_Total_Balance);
			$str2 = array(
				Num::format(($CustomerRecord["Due"] - $CustomerRecord["Overdue1"]), $dec),
				Num::format(($CustomerRecord["Overdue1"] - $CustomerRecord["Overdue2"]), $dec),
				Num::format($CustomerRecord["Overdue2"], $dec),
				Num::format(($CustomerRecord["Balance"] - $CustomerRecord["Due"]), $dec),
				Num::format($CustomerRecord["Balance"], $dec)
			);
			$col = array(
				$rep->cols[0], $rep->cols[0] + 80, $rep->cols[0] + 170, $rep->cols[0] + 270, $rep->cols[0] + 360, $rep->cols[0] + 450
			);
			$rep->row = $rep->bottomMargin + (13 * $rep->lineHeight - 6);
			if ($CustomerRecord["Balance"] > 0 && $CustomerRecord["Due"] - $CustomerRecord["Overdue1"] < $CustomerRecord["Balance"]) {
				$rep->SetTextColor(255, 0, 0);
				$rep->fontSize += 5;
				$rep->Font('bold');
				$rep->TextWrapLines(0, $rep->pageWidth, 'YOUR ACCOUNT IS OVERDUE, IMMEDIATE PAYMENT REQUIRED!', 'C');
				$rep->fontSize -= 5;
				$rep->SetTextColor(0, 0, 0);
			}
			$rep->NewLine();
			for ($i = 0; $i < 5; $i++) {
				$rep->TextWrap($col[$i], $rep->row, $col[$i + 1] - $col[$i], $str[$i], 'center');
			}
			$rep->Font();
			$rep->NewLine();
			for ($i = 0; $i < 5; $i++) {
				$rep->TextWrap($col[$i], $rep->row, $col[$i + 1] - $col[$i], $str2[$i], 'center');
			}
			if ($email == 1) {
				$rep->End($email, $doc_Statement . " " . $doc_as_of . " " . Dates::sql2date($date), $myrow, ST_STATEMENT);
			}
		}
		if ($email == 0) {
			$rep->End();
		}
	}

