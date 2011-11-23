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
	$page_security = 'SA_TAXREP';
	// ----------------------------------------------------------------
	// $ Revision:	2.0 $
	// Creator:	Joe Hunt
	// date_:	2005-05-19
	// Title:	Tax Report
	// ----------------------------------------------------------------
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	//------------------------------------------------------------------
	print_tax_report();
	function getTaxTransactions($from, $to)
	{
		$fromdate = Dates::date2sql($from);
		$todate = Dates::date2sql($to);
		$sql
		 = "SELECT taxrec.*, taxrec.amount*ex_rate AS amount,
	            taxrec.net_amount*ex_rate AS net_amount,
				IF(ISNULL(supp.supp_name), debt.name, supp.supp_name) as name,
				branch.br_name
		FROM trans_tax_details taxrec
		LEFT JOIN supp_trans strans
			ON taxrec.trans_no=strans.trans_no AND taxrec.trans_type=strans.type
		LEFT JOIN suppliers as supp ON strans.supplier_id=supp.supplier_id
		LEFT JOIN debtor_trans dtrans
			ON taxrec.trans_no=dtrans.trans_no AND taxrec.trans_type=dtrans.type
		LEFT JOIN debtors_master as debt ON dtrans.debtor_no=debt.debtor_no
		LEFT JOIN cust_branch as branch ON dtrans.branch_code=branch.branch_code
		WHERE (taxrec.amount <> 0 OR taxrec.net_amount <> 0)
			AND taxrec.trans_type <> " . ST_CUSTDELIVERY . "
			AND taxrec.tran_date >= '$fromdate'
			AND taxrec.tran_date <= '$todate'
		ORDER BY taxrec.tran_date";
		//Errors::error($sql);
		return DB::query($sql, "No transactions were returned");
	}

	function getTaxTypes()
	{
		$sql = "SELECT * FROM tax_types ORDER BY id";
		return DB::query($sql, "No transactions were returned");
	}

	function getTaxInfo($id)
	{
		$sql = "SELECT * FROM tax_types WHERE id=$id";
		$result = DB::query($sql, "No transactions were returned");
		return DB::fetch($result);
	}

	//----------------------------------------------------------------------------------------------------
	function print_tax_report()
	{
		global $trans_dir, $Hooks, $systypes_array;
		$from = $_POST['PARAM_0'];
		$to = $_POST['PARAM_1'];
		$summaryOnly = $_POST['PARAM_2'];
		$comments = $_POST['PARAM_3'];
		$destination = $_POST['PARAM_4'];
		if ($destination) {
			include_once(APP_PATH . "includes/reports/excel.php");
		}
		else
		{
			include_once(APP_PATH . "includes/reports/pdf.php");
		}
		$dec = User::price_dec();
		$rep = new FrontReport(_('Tax Report'), "TaxReport", User::pagesize());
		if ($summaryOnly == 1) {
			$summary = _('Summary Only');
		}
		else
		{
			$summary = _('Detailed Report');
		}
		$res = getTaxTypes();
		$taxes = array();
		while ($tax = DB::fetch($res))
		{
			$taxes[$tax['id']] = array('in' => 0, 'out' => 0, 'taxin' => 0, 'taxout' => 0);
		}
		$params = array(0 => $comments,
			1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
			2 => array('text' => _('Type'), 'from' => $summary, 'to' => '')
		);
		$cols = array(0, 100, 130, 180, 290, 370, 420, 470, 520);
		$headers = array(_('Trans Type'), _('Ref'), _('Date'), _('Name'), _('Branch Name'),
			_('Net'), _('Rate'), _('Tax')
		);
		$aligns = array('left', 'left', 'left', 'left', 'left', 'right', 'right', 'right');
		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);
		if (!$summaryOnly) {
			$rep->Header();
		}
		$totalnet = 0.0;
		$totaltax = 0.0;
		$transactions = getTaxTransactions($from, $to);
		while ($trans = DB::fetch($transactions))
		{
			if (in_array($trans['trans_type'], array(ST_CUSTCREDIT, ST_SUPPINVOICE))) {
				$trans['net_amount'] *= -1;
				$trans['amount'] *= -1;
			}
			if (!$summaryOnly) {
				$rep->TextCol(0, 1, $systypes_array[$trans['trans_type']]);
				if ($trans['memo'] == '') {
					$trans['memo'] = Refs::get_reference($trans['trans_type'], $trans['trans_no']);
				}
				$rep->TextCol(1, 2, $trans['memo']);
				$rep->DateCol(2, 3, $trans['tran_date'], true);
				$rep->TextCol(3, 4, $trans['name']);
				$rep->TextCol(4, 5, $trans['br_name']);
				$rep->AmountCol(5, 6, $trans['net_amount'], $dec);
				$rep->AmountCol(6, 7, $trans['rate'], $dec);
				$rep->AmountCol(7, 8, $trans['amount'], $dec);
				$rep->NewLine();
				if ($rep->row < $rep->bottomMargin + $rep->lineHeight) {
					$rep->Line($rep->row - 2);
					$rep->Header();
				}
			}
			if ($trans['trans_type'] == ST_JOURNAL && $trans['amount'] < 0) {
				$taxes[$trans['tax_type_id']]['taxout'] -= $trans['amount'];
				$taxes[$trans['tax_type_id']]['out'] -= $trans['net_amount'];
			}
			elseif (in_array($trans['trans_type'], array(ST_BANKDEPOSIT, ST_SALESINVOICE, ST_CUSTCREDIT))) {
				$taxes[$trans['tax_type_id']]['taxout'] += $trans['amount'];
				$taxes[$trans['tax_type_id']]['out'] += $trans['net_amount'];
			} else {
				$taxes[$trans['tax_type_id']]['taxin'] += $trans['amount'];
				$taxes[$trans['tax_type_id']]['in'] += $trans['net_amount'];
			}
			$totalnet += $trans['net_amount'];
			$totaltax += $trans['amount'];
		}
		// Summary
		$cols2 = array(0, 100, 180, 260, 340, 420, 500);
		$headers2 = array(_('Tax Rate'), _('Outputs'), _('Output Tax'), _('Inputs'), _('Input Tax'), _('Net Tax'));
		$aligns2 = array('left', 'right', 'right', 'right', 'right', 'right', 'right');
		$rep->Info($params, $cols2, $headers2, $aligns2);
		//for ($i = 0; $i < count($cols2); $i++)
		//	$rep->cols[$i] = $rep->leftMargin + $cols2[$i];
		//$rep->numcols = count($headers2);
		//$rep->headers = $headers2;
		//$rep->aligns = $aligns2;
		$rep->Header();
		$taxtotal = 0;
		foreach ($taxes as $id => $sum)
		{
			$tx = getTaxInfo($id);
			$rep->TextCol(0, 1, $tx['name'] . " " . Num::format($tx['rate'], $dec) . "%");
			$rep->AmountCol(1, 2, $sum['out'], $dec);
			$rep->AmountCol(2, 3, $sum['taxout'], $dec);
			$rep->AmountCol(3, 4, $sum['in'], $dec);
			$rep->AmountCol(4, 5, $sum['taxin'], $dec);
			$rep->AmountCol(5, 6, $sum['taxout'] + $sum['taxin'], $dec);
			$taxtotal += $sum['taxout'] + $sum['taxin'];
			$rep->NewLine();
		}
		$rep->Font('bold');
		$rep->NewLine();
		$rep->Line($rep->row + $rep->lineHeight);
		$rep->TextCol(3, 5, _("Total payable or refund"));
		$rep->AmountCol(5, 6, $taxtotal, $dec);
		$rep->Line($rep->row - 5);
		$rep->Font();
		$rep->NewLine();
		if (method_exists($Hooks, 'TaxFunction')) {
			$Hooks->TaxFunction();
		}
		$rep->End();
	}

?>