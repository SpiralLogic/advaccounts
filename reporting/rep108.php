<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$page_security = 'SA_CUSTSTATREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Print Statements
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

//----------------------------------------------------------------------------------------------------

print_statements();

//----------------------------------------------------------------------------------------------------

function getTransactions($debtorno, $date)
{
    $sql = "SELECT ".TB_PREF."debtor_trans.*,
				(".TB_PREF."debtor_trans.ov_amount + ".TB_PREF."debtor_trans.ov_gst + ".TB_PREF."debtor_trans.ov_freight + 
				".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount)
				AS TotalAmount, ".TB_PREF."debtor_trans.alloc AS Allocated,
				((".TB_PREF."debtor_trans.type = ".ST_SALESINVOICE.")
					AND ".TB_PREF."debtor_trans.due_date < '$date') AS OverDue
    			FROM ".TB_PREF."debtor_trans
    			WHERE ".TB_PREF."debtor_trans.tran_date <= '$date' AND ".TB_PREF."debtor_trans.debtor_no = ".db_escape($debtorno)."
    				AND ".TB_PREF."debtor_trans.type <> ".ST_CUSTDELIVERY."
    				AND (".TB_PREF."debtor_trans.ov_amount + ".TB_PREF."debtor_trans.ov_gst + ".TB_PREF."debtor_trans.ov_freight + 
				".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount) != 0
    				ORDER BY ".TB_PREF."debtor_trans.tran_date";

    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_statements()
{
	global $path_to_root, $systypes_array;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$customer = $_POST['PARAM_0'];
	$currency = $_POST['PARAM_1'];
	$email = $_POST['PARAM_2'];
	$comments = $_POST['PARAM_3'];

	$dec = user_price_dec();

	$cols = array(4, 100, 130, 190,	250, 320, 385, 450, 515);

	//$headers in doctext.inc

	$aligns = array('left',	'left',	'left',	'left',	'right', 'right', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_pref('curr_default');
	$PastDueDays1 = get_company_pref('past_due_days');
	$PastDueDays2 = 2 * $PastDueDays1;

	if ($email == 0)
	{
		$rep = new FrontReport(_('STATEMENT'), "StatementBulk", user_pagesize());
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);
	}

	$sql = "SELECT debtor_no, name AS DebtorName, address, tax_id, email, curr_code, curdate() AS tran_date, payment_terms FROM ".TB_PREF."debtors_master";
	if ($customer != ALL_NUMERIC)
		$sql .= " WHERE debtor_no = ".db_escape($customer);
	else
		$sql .= " ORDER by name";
	$result = db_query($sql, "The customers could not be retrieved");

	while ($myrow=db_fetch($result))
	{
		$date = date('Y-m-d');

		$myrow['order_'] = "";

		$TransResult = getTransactions($myrow['debtor_no'], $date);
		$baccount = get_default_bank_account($myrow['curr_code']);
		$params['bankaccount'] = $baccount['id'];
		if (db_num_rows($TransResult) == 0)
			continue;
		if ($email == 1)
		{
			$rep = new FrontReport("", "", user_pagesize());
			$rep->currency = $cur;
			$rep->Font();
			$rep->title = _('STATEMENT');
			$rep->filename = "Statement" . $myrow['debtor_no'] . ".pdf";
			$rep->Info($params, $cols, null, $aligns);
		}
		$rep->Header2($myrow, null, null, $baccount, ST_STATEMENT);
		$rep->NewLine();
		$linetype = true;
		$doctype = ST_STATEMENT;
		if ($rep->currency != $myrow['curr_code'])
		{
			include($path_to_root . "/reporting/includes/doctext2.inc");
		}
		else
		{
			include($path_to_root . "/reporting/includes/doctext.inc");
		}
		$rep->fontSize += 2;
		$rep->TextCol(0, 8, $doc_Outstanding);
		$rep->fontSize -= 2;
		$rep->NewLine(2);
		while ($myrow2=db_fetch($TransResult))
		{
			$DisplayTotal = number_format2(Abs($myrow2["TotalAmount"]),$dec);
			$DisplayAlloc = number_format2($myrow2["Allocated"],$dec);
			$DisplayNet = number_format2($myrow2["TotalAmount"] - $myrow2["Allocated"],$dec);

			$rep->TextCol(0, 1, $systypes_array[$myrow2['type']], -2);
			$rep->TextCol(1, 2,	$myrow2['reference'], -2);
			$rep->TextCol(2, 3,	sql2date($myrow2['tran_date']), -2);
			if ($myrow2['type'] == ST_SALESINVOICE)
				$rep->TextCol(3, 4,	sql2date($myrow2['due_date']), -2);
			if ($myrow2['type'] == ST_SALESINVOICE)
				$rep->TextCol(4, 5,	$DisplayTotal, -2);
			else
				$rep->TextCol(5, 6,	$DisplayTotal, -2);
			$rep->TextCol(6, 7,	$DisplayAlloc, -2);
			$rep->TextCol(7, 8,	$DisplayNet, -2);
			$rep->NewLine();
			if ($rep->row < $rep->bottomMargin + (10 * $rep->lineHeight))
				$rep->Header2($myrow, null, null, $baccount, ST_STATEMENT);
		}
		$nowdue = "1-" . $PastDueDays1 . " " . $doc_Days;
		$pastdue1 = $PastDueDays1 + 1 . "-" . $PastDueDays2 . " " . $doc_Days;
		$pastdue2 = $doc_Over . " " . $PastDueDays2 . " " . $doc_Days;
		$CustomerRecord = get_customer_details($myrow['debtor_no']);
		$str = array($doc_Current, $nowdue, $pastdue1, $pastdue2, $doc_Total_Balance);
		$str2 = array(number_format2(($CustomerRecord["Balance"] - $CustomerRecord["Due"]),$dec),
			number_format2(($CustomerRecord["Due"]-$CustomerRecord["Overdue1"]),$dec),
			number_format2(($CustomerRecord["Overdue1"]-$CustomerRecord["Overdue2"]) ,$dec),
			number_format2($CustomerRecord["Overdue2"],$dec),
			number_format2($CustomerRecord["Balance"],$dec));
		$col = array($rep->cols[0], $rep->cols[0] + 110, $rep->cols[0] + 210, $rep->cols[0] + 310,
			$rep->cols[0] + 410, $rep->cols[0] + 510);
		$rep->row = $rep->bottomMargin + (10 * $rep->lineHeight - 6);
		for ($i = 0; $i < 5; $i++)
			$rep->TextWrap($col[$i], $rep->row, $col[$i + 1] - $col[$i], $str[$i], 'right');
		$rep->NewLine();
		for ($i = 0; $i < 5; $i++)
			$rep->TextWrap($col[$i], $rep->row, $col[$i + 1] - $col[$i], $str2[$i], 'right');
		if ($email == 1)
			$rep->End($email, $doc_Statement . " " . $doc_as_of . " " . sql2date($date), $myrow, ST_STATEMENT);

	}
	if ($email == 0)
		$rep->End();
}

?>