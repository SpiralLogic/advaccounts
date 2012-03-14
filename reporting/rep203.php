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
Page::set_security(SA_SUPPPAYMREP);

	print_payment_report();
	function get_transactions($supplier, $date)
	{
		$date = Dates::date2sql($date);
		$dec = User::price_dec();
		$sql
		 = "SELECT creditor_trans.supp_reference,
			creditor_trans.tran_date,
			creditor_trans.due_date,
			creditor_trans.trans_no,
			creditor_trans.type,
			creditor_trans.rate,
			(ABS(creditor_trans.ov_amount) + ABS(creditor_trans.ov_gst) - creditor_trans.alloc) AS Balance,
			(ABS(creditor_trans.ov_amount) + ABS(creditor_trans.ov_gst) ) AS TranTotal
		FROM creditor_trans
		WHERE creditor_trans.supplier_id = '" . $supplier . "'
		AND ROUND(ABS(creditor_trans.ov_amount),$dec) + ROUND(ABS(creditor_trans.ov_gst),$dec) -
		ROUND(creditor_trans.alloc,$dec) != 0
		AND creditor_trans.tran_date <='" . $date . "'
		ORDER BY creditor_trans.type,
			creditor_trans.trans_no";
		return DB::query($sql, "No transactions were returned");
	}


	function print_payment_report()
	{
		global $systypes_array;
		$to = $_POST['PARAM_0'];
		$fromsupp = $_POST['PARAM_1'];
		$currency = $_POST['PARAM_2'];
		$no_zeros = $_POST['PARAM_3'];
		$comments = $_POST['PARAM_4'];
		$destination = $_POST['PARAM_5'];
		if ($destination) {
			include_once(APPPATH . "reports/excel.php");
		}
		else
		{
			include_once(APPPATH . "reports/pdf.php");
		}
		if ($fromsupp == ALL_NUMERIC) {
			$from = _('All');
		}
		else
		{
			$from = Creditor::get_name($fromsupp);
		}
		$dec = User::price_dec();
		if ($currency == ALL_TEXT) {
			$convert = true;
			$currency = _('Balances in Home Currency');
		}
		else
		{
			$convert = false;
		}
		if ($no_zeros) {
			$nozeros = _('Yes');
		}
		else {
			$nozeros = _('No');
		}
		$cols = array(0, 100, 130, 190, 250, 320, 385, 450, 515);
		$headers = array(_('Trans Type'), _('#'), _('Due Date'), '', '',
			'', _('Total'), _('Balance')
		);
		$aligns = array('left', 'left', 'left', 'left', 'right', 'right', 'right', 'right');
		$params = array(0 => $comments,
			1 => array('text' => _('End Date'), 'from' => $to, 'to' => ''),
			2 => array('text' => _('Supplier'), 'from' => $from, 'to' => ''),
			3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
			4 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => '')
		);
		$rep = new ADVReport(_('Payment Report'), "PaymentReport", User::pagesize());
		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header();
		$total = array();
		$grandtotal = array(0, 0);
		$sql
		 = "SELECT supplier_id, supp_name AS name, curr_code, payment_terms.terms FROM suppliers, payment_terms
		WHERE ";
		if ($fromsupp != ALL_NUMERIC) {
			$sql .= "supplier_id=" . DB::escape($fromsupp) . " AND ";
		}
		$sql
		 .= "suppliers.payment_terms = payment_terms.terms_indicator
		ORDER BY supp_name";
		$result = DB::query($sql, "The customers could not be retrieved");
		while ($myrow = DB::fetch($result))
		{
			if (!$convert && $currency != $myrow['curr_code']) {
				continue;
			}
			$res = get_transactions($myrow['supplier_id'], $to);
			if ($no_zeros && DB::num_rows($res) == 0) {
				continue;
			}
			$rep->fontSize += 2;
			$rep->TextCol(0, 6, $myrow['name'] . " - " . $myrow['terms']);
			if ($convert) {
				$rep->TextCol(6, 7, $myrow['curr_code']);
			}
			$rep->fontSize -= 2;
			$rep->NewLine(1, 2);
			if (DB::num_rows($res) == 0) {
				continue;
			}
			$rep->Line($rep->row + 4);
			$total[0] = $total[1] = 0.0;
			while ($trans = DB::fetch($res))
			{
				if ($no_zeros && $trans['TranTotal'] == 0 && $trans['Balance'] == 0) {
					continue;
				}
				if ($convert) {
					$rate = $trans['rate'];
				}
				else {
					$rate = 1.0;
				}
				$rep->NewLine(1, 2);
				$rep->TextCol(0, 1, $systypes_array[$trans['type']]);
				$rep->TextCol(1, 2, $trans['supp_reference']);
				if ($trans['type'] == ST_SUPPINVOICE) {
					$rep->DateCol(2, 3, $trans['due_date'], true);
				}
				else
				{
					$rep->DateCol(2, 3, $trans['tran_date'], true);
				}
				if ($trans['type'] != ST_SUPPINVOICE) {
					$trans['TranTotal'] = -$trans['TranTotal'];
					$trans['Balance'] = -$trans['Balance'];
				}
				$item[0] = $trans['TranTotal'] * $rate;
				$rep->AmountCol(6, 7, $item[0], $dec);
				$item[1] = $trans['Balance'] * $rate;
				$rep->AmountCol(7, 8, $item[1], $dec);
				for ($i = 0; $i < 2; $i++)
				{
					$total[$i] += $item[$i];
					$grandtotal[$i] += $item[$i];
				}
			}
			$rep->Line($rep->row - 8);
			$rep->NewLine(2);
			$rep->TextCol(0, 3, _('Total'));
			for ($i = 0; $i < 2; $i++)
			{
				$rep->AmountCol($i + 6, $i + 7, $total[$i], $dec);
				$total[$i] = 0.0;
			}
			$rep->Line($rep->row - 4);
			$rep->NewLine(2);
		}
		$rep->fontSize += 2;
		$rep->TextCol(0, 3, _('Grand Total'));
		$rep->fontSize -= 2;
		for ($i = 0; $i < 2; $i++)
		{
			$rep->AmountCol($i + 6, $i + 7, $grandtotal[$i], $dec);
		}
		$rep->Line($rep->row - 4);
		$rep->NewLine();
		$rep->End();
	}

?>
