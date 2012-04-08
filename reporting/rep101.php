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
	Page::set_security(SA_CUSTPAYMREP);

	// trial_inquiry_controls();
	print_customer_balances();
	function get_open_balance($debtorno, $to, $convert)
		{
			$to = Dates::date2sql($to);
			$sql = "SELECT SUM(IF(" . '' . "debtor_trans.type = " . ST_SALESINVOICE . ", (" . '' . "debtor_trans.ov_amount + " . '' . "debtor_trans.ov_gst +
 	" . '' . "debtor_trans.ov_freight + " . '' . "debtor_trans.ov_freight_tax + " . '' . "debtor_trans.ov_discount)";
			if ($convert) {
				$sql .= " * rate";
			}
			$sql
			 .= ", 0)) AS charges,
 	SUM(IF(" . '' . "debtor_trans.type <> " . ST_SALESINVOICE . ", (" . '' . "debtor_trans.ov_amount + " . '' . "debtor_trans.ov_gst +
 	" . '' . "debtor_trans.ov_freight + " . '' . "debtor_trans.ov_freight_tax + " . '' . "debtor_trans.ov_discount)";
			if ($convert) {
				$sql .= " * rate";
			}
			$sql
			 .= " * -1, 0)) AS credits,
		SUM(" . '' . "debtor_trans.alloc";
			if ($convert) {
				$sql .= " * rate";
			}
			$sql
			 .= ") AS Allocated,
		SUM(IF(" . '' . "debtor_trans.type = " . ST_SALESINVOICE . ", (" . '' . "debtor_trans.ov_amount + " . '' . "debtor_trans.ov_gst +
 	" . '' . "debtor_trans.ov_freight + " . '' . "debtor_trans.ov_freight_tax + " . '' . "debtor_trans.ov_discount - " . '' . "debtor_trans.alloc)";
			if ($convert) {
				$sql .= " * rate";
			}
			$sql
			 .= ",
 	((" . '' . "debtor_trans.ov_amount + " . '' . "debtor_trans.ov_gst + " . '' . "debtor_trans.ov_freight +
 	" . '' . "debtor_trans.ov_freight_tax + " . '' . "debtor_trans.ov_discount) * -1 + " . '' . "debtor_trans.alloc)";
			if ($convert) {
				$sql .= " * rate";
			}
			$sql
			 .= ")) AS OutStanding
		FROM " . '' . "debtor_trans
 	WHERE " . '' . "debtor_trans.tran_date < '$to'
		AND " . '' . "debtor_trans.debtor_no = " . DB::escape($debtorno) . "
		AND " . '' . "debtor_trans.type <> " . ST_CUSTDELIVERY . " GROUP BY debtor_no";
			$result = DB::query($sql, "No transactions were returned");
			return DB::fetch($result);
		}

	function get_transactions($debtorno, $from, $to)
		{
			$from = Dates::date2sql($from);
			$to = Dates::date2sql($to);
			$sql = "SELECT " . '' . "debtor_trans.*,
		(" . '' . "debtor_trans.ov_amount + " . '' . "debtor_trans.ov_gst + " . '' . "debtor_trans.ov_freight +
		" . '' . "debtor_trans.ov_freight_tax + " . '' . "debtor_trans.ov_discount)
		AS TotalAmount, " . '' . "debtor_trans.alloc AS Allocated,
		((" . '' . "debtor_trans.type = " . ST_SALESINVOICE . ")
		AND " . '' . "debtor_trans.due_date < '$to') AS OverDue
 	FROM " . '' . "debtor_trans
 	WHERE " . '' . "debtor_trans.tran_date >= '$from'
		AND " . '' . "debtor_trans.tran_date <= '$to'
		AND " . '' . "debtor_trans.debtor_no = " . DB::escape($debtorno) . "
		AND " . '' . "debtor_trans.type <> " . ST_CUSTDELIVERY . "
 	ORDER BY " . '' . "debtor_trans.tran_date";
			return DB::query($sql, "No transactions were returned");
		}


	function print_customer_balances()
		{
			global $systypes_array;
			$from = $_POST['PARAM_0'];
			$to = $_POST['PARAM_1'];
			$fromcust = $_POST['PARAM_2'];
			$currency = $_POST['PARAM_3'];
			$no_zeros = $_POST['PARAM_4'];
			$comments = $_POST['PARAM_5'];
			$destination = $_POST['PARAM_6'];
			if ($destination) {
				include_once(APPPATH . "reports/excel.php");
			}
			else
			{
				include_once(APPPATH . "reports/pdf.php");
			}
			if ($fromcust == ALL_NUMERIC) {
				$cust = _('All');
			}
			else
			{
				$cust = Debtor::get_name($fromcust);
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
			$headers = array(
				_('Trans Type'), _('#'), _('Date'), _('Due Date'), _('Charges'), _('Credits'),
				_('Allocated'), _('Outstanding')
			);
			$aligns = array('left', 'left', 'left', 'left', 'right', 'right', 'right', 'right');
			$params = array(
				0 => $comments,
				1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
				2 => array('text' => _('Customer'), 'from' => $cust, 'to' => ''),
				3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
				4 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => '')
			);
			$rep = new ADVReport(_('Customer Balances'), "CustomerBalances", User::pagesize());
			$rep->Font();
			$rep->Info($params, $cols, $headers, $aligns);
			$rep->Header();
			$grandtotal = array(0, 0, 0, 0);
			$sql = "SELECT debtor_no, name, curr_code FROM " . '' . "debtors ";
			if ($fromcust != ALL_NUMERIC) {
				$sql .= "WHERE debtor_no=" . DB::escape($fromcust);
			}
			$sql .= " ORDER BY name";
			$result = DB::query($sql, "The customers could not be retrieved");
			$num_lines = 0;
			while ($myrow = DB::fetch($result))
			{
				if (!$convert && $currency != $myrow['curr_code']) {
					continue;
				}
				$bal = get_open_balance($myrow['debtor_no'], $from, $convert);
				$init[0] = $init[1] = 0.0;
				$init[0] = Num::round(abs($bal['charges']), $dec);
				$init[1] = Num::round(Abs($bal['credits']), $dec);
				$init[2] = Num::round($bal['Allocated'], $dec);
				$init[3] = Num::round($bal['OutStanding'], $dec);
				;
				$res = get_transactions($myrow['debtor_no'], $from, $to);
				if ($no_zeros && DB::num_rows($res) == 0) {
					continue;
				}
				$num_lines++;
				$rep->fontSize += 2;
				$rep->TextCol(0, 2, $myrow['name']);
				if ($convert) {
					$rep->TextCol(2, 3, $myrow['curr_code']);
				}
				$rep->fontSize -= 2;
				//$rep->TextCol(3, 4,	_("Open Balance"));
				//$rep->AmountCol(4, 5, $init[0], $dec);
				//$rep->AmountCol(5, 6, $init[1], $dec);
				//$rep->AmountCol(6, 7, $init[2], $dec);
				//$rep->AmountCol(7, 8, $init[3], $dec);
				$total = array(0, 0, 0, 0);
				for ($i = 0; $i < 4; $i++)
				{
					$total[$i] += $init[$i];
					$grandtotal[$i] += $init[$i];
				}
				$rep->NewLine(1, 2);
				if (DB::num_rows($res) == 0) {
					continue;
				}
				$rep->Line($rep->row + 4);
				while ($trans = DB::fetch($res))
				{
					if ($no_zeros && $trans['TotalAmount'] == 0 && $trans['Allocated'] == 0) {
						continue;
					}
					//$rep->NewLine(1, 2);
					//$rep->TextCol(0, 1, $systypes_array[$trans['type']]);
					//$rep->TextCol(1, 2,	$trans['reference']);
					//$rep->DateCol(2, 3,	$trans['tran_date'], true);
					if ($trans['type'] == ST_SALESINVOICE
					) //	$rep->DateCol(3, 4,	$trans['due_date'], true);
					{
						$item[0] = $item[1] = 0.0;
					}
					if ($convert) {
						$rate = $trans['rate'];
					}
					else
					{
						$rate = 1.0;
					}
					if ($trans['type'] == ST_CUSTCREDIT || $trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_BANKDEPOSIT) {
						$trans['TotalAmount'] *= -1;
					}
					if ($trans['TotalAmount'] > 0.0) {
						$item[0] = Num::round(abs($trans['TotalAmount']) * $rate, $dec);
						//		$rep->AmountCol(4, 5, $item[0], $dec);
					} else {
						$item[1] = Num::round(Abs($trans['TotalAmount']) * $rate, $dec);
						//		$rep->AmountCol(5, 6, $item[1], $dec);
					}
					$item[2] = Num::round($trans['Allocated'] * $rate, $dec);
					//	$rep->AmountCol(6, 7, $item[2], $dec);
					/*
								 if ($trans['type'] == 10)
									 $item[3] = ($trans['TotalAmount'] - $trans['Allocated']) * $rate;
								 else
									 $item[3] = ($trans['TotalAmount'] + $trans['Allocated']) * $rate;
								 */
					if ($trans['type'] == ST_SALESINVOICE || $trans['type'] == ST_BANKPAYMENT) {
						$item[3] = $item[0] + $item[1] - $item[2];
					}
					else
					{
						$item[3] = $item[0] - $item[1] + $item[2];
					}
					//	$rep->AmountCol(7, 8, $item[3], $dec);
					for ($i = 0; $i < 4; $i++)
					{
						$total[$i] += $item[$i];
						$grandtotal[$i] += $item[$i];
					}
				}
				//$rep->Line($rep->row - 8);
				//$rep->NewLine(2);
				//$rep->TextCol(0, 3, _('Total'));
				//		for ($i = 0; $i < 4; $i++)
				//			$rep->AmountCol($i + 4, $i + 5, $total[$i], $dec);
				// 		$rep->Line($rep->row - 4);
				//		$rep->NewLine(2);
			}
			$rep->fontSize += 2;
			$rep->TextCol(0, 3, _('Grand Total'));
			$rep->fontSize -= 2;
			for ($i = 0; $i < 4; $i++)
			{
				$rep->AmountCol($i + 4, $i + 5, $grandtotal[$i], $dec);
			}
			$rep->Line($rep->row - 4);
			$rep->NewLine();
			$rep->End();
		}


