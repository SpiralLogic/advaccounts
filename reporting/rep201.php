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
Page::set_security(SA_SUPPLIERANALYTIC);

	print_supplier_balances();
	function get_open_balance($supplier_id, $to, $convert)
		{
			$to = Dates::date2sql($to);
			$sql = "SELECT SUM(IF(creditor_trans.type = " . ST_SUPPINVOICE . ", (creditor_trans.ov_amount + creditor_trans.ov_gst +
 	creditor_trans.ov_discount)";
			if ($convert) {
				$sql .= " * rate";
			}
			$sql
			 .= ", 0)) AS charges,
 	SUM(IF(creditor_trans.type <> " . ST_SUPPINVOICE . ", (creditor_trans.ov_amount + creditor_trans.ov_gst +
 	creditor_trans.ov_discount)";
			if ($convert) {
				$sql .= "* rate";
			}
			$sql
			 .= ", 0)) AS credits,
		SUM(creditor_trans.alloc";
			if ($convert) {
				$sql .= " * rate";
			}
			$sql
			 .= ") AS Allocated,
		SUM((creditor_trans.ov_amount + creditor_trans.ov_gst +
 	creditor_trans.ov_discount - creditor_trans.alloc)";
			if ($convert) {
				$sql .= " * rate";
			}
			$sql
			 .= ") AS OutStanding
		FROM creditor_trans
 	WHERE creditor_trans.tran_date < '$to'
		AND creditor_trans.supplier_id = '$supplier_id' GROUP BY supplier_id";
			$result = DB::query($sql, "No transactions were returned");
			return DB::fetch($result);
		}

	function get_transactions($supplier_id, $from, $to)
		{
			$from = Dates::date2sql($from);
			$to = Dates::date2sql($to);
			$sql
			 = "SELECT creditor_trans.*,
				(creditor_trans.ov_amount + creditor_trans.ov_gst + creditor_trans.ov_discount)
				AS TotalAmount, creditor_trans.alloc AS Allocated,
				((creditor_trans.type = " . ST_SUPPINVOICE . ")
					AND creditor_trans.due_date < '$to') AS OverDue
 			FROM creditor_trans
 			WHERE creditor_trans.tran_date >= '$from' AND creditor_trans.tran_date <= '$to'
 			AND creditor_trans.supplier_id = '$supplier_id'
 				ORDER BY creditor_trans.tran_date";
			$trans_rows = DB::query($sql, "No transactions were returned");
			return $trans_rows;
		}


	function print_supplier_balances()
		{
			global $systypes_array;
			$from = $_POST['PARAM_0'];
			$to = $_POST['PARAM_1'];
			$fromsupp = $_POST['PARAM_2'];
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
			if ($fromsupp == ALL_NUMERIC) {
				$supp = _('All');
			}
			else
			{
				$supp = Creditor::get_name($fromsupp);
			}
			$dec = User::price_dec();
			if ($currency == ALL_TEXT) {
				$convert = true;
				$currency = _('Balances in Home currency');
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
			$cols = array(0, 100, 180, 240, 270, 340, 405, 470, 515);
			$headers = array(
				_('Trans Type'), _('# - Invoice #'), _('Date'), _('Due Date'), _('Charges'),
				_('Credits'), _('Allocated'), _('Outstanding')
			);
			$aligns = array('left', 'left', 'left', 'left', 'right', 'right', 'right', 'right');
			$params = array(
				0 => $comments,
				1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
				2 => array('text' => _('Supplier'), 'from' => $supp, 'to' => ''),
				3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
				4 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => '')
			);
			$rep = new ADVReport(_('Supplier Balances'), "SupplierBalances", User::pagesize());
			$rep->Font();
			$rep->fontSize -= 2;
			$rep->Info($params, $cols, $headers, $aligns);
			$rep->Header();
			$total = array();
			$grandtotal = array(0, 0, 0, 0);
			$sql = "SELECT supplier_id, supp_name AS name, curr_code FROM suppliers";
			if ($fromsupp != ALL_NUMERIC) {
				$sql .= " WHERE supplier_id=" . DB::escape($fromsupp);
			}
			$sql .= " ORDER BY supp_name";
			$result = DB::query($sql, "The customers could not be retrieved");
			while ($myrow = DB::fetch($result))
			{
				if (!$convert && $currency != $myrow['curr_code']) {
					continue;
				}
				$bal = get_open_balance($myrow['supplier_id'], $from, $convert);
				$init[0] = $init[1] = 0.0;
				$init[0] = Num::round(abs($bal['charges']), $dec);
				$init[1] = Num::round(Abs($bal['credits']), $dec);
				$init[2] = Num::round($bal['Allocated'], $dec);
				$init[3] = Num::round($bal['OutStanding'], $dec);
				;
				$total = array(0, 0, 0, 0);
				for ($i = 0; $i < 4; $i++)
				{
					$total[$i] += $init[$i];
					$grandtotal[$i] += $init[$i];
				}
				$res = get_transactions($myrow['supplier_id'], $from, $to);
				if ($no_zeros && DB::num_rows($res) == 0) {
					continue;
				}
				$rep->TextCol(0, 2, $myrow['name']);
				if ($convert) {
					$rep->TextCol(2, 3, $myrow['curr_code']);
				}
				$rep->fontSize -= 2;
				$rep->TextCol(3, 4, _("Open Balance"));
				$rep->AmountCol(4, 5, $init[0], $dec);
				$rep->AmountCol(5, 6, $init[1], $dec);
				$rep->AmountCol(6, 7, $init[2], $dec);
				$rep->AmountCol(7, 8, $init[3], $dec);
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
					$rep->NewLine(1, 2);
					$rep->TextCol(0, 1, $systypes_array[$trans['type']]);
					$rep->TextCol(1, 2, $trans['reference'] . ' - ' . $trans['supp_reference']);
					$rep->DateCol(2, 3, $trans['tran_date'], true);
					if ($trans['type'] == ST_SUPPINVOICE) {
						$rep->DateCol(3, 4, $trans['due_date'], true);
					}
					$item[0] = $item[1] = 0.0;
					if ($convert) {
						$rate = $trans['rate'];
					}
					else
					{
						$rate = 1.0;
					}
					if ($trans['TotalAmount'] > 0.0) {
						$item[0] = Num::round(abs($trans['TotalAmount']) * $rate, $dec);
						$rep->AmountCol(4, 5, $item[0], $dec);
					} else {
						$item[1] = Num::round(abs($trans['TotalAmount']) * $rate, $dec);
						$rep->AmountCol(5, 6, $item[1], $dec);
					}
					$item[2] = Num::round($trans['Allocated'] * $rate, $dec);
					$rep->AmountCol(6, 7, $item[2], $dec);
					/*
								 if ($trans['type'] == 20)
									 $item[3] = ($trans['TotalAmount'] - $trans['Allocated']) * $rate;
								 else
									 $item[3] = ($trans['TotalAmount'] + $trans['Allocated']) * $rate;
								 */
					if ($trans['type'] == ST_SUPPINVOICE || $trans['type'] == ST_BANKDEPOSIT) {
						$item[3] = $item[0] + $item[1] - $item[2];
					}
					else
					{
						$item[3] = $item[0] - $item[1] + $item[2];
					}
					$rep->AmountCol(7, 8, $item[3], $dec);
					for ($i = 0; $i < 4; $i++)
					{
						$total[$i] += $item[$i];
						$grandtotal[$i] += $item[$i];
					}
				}
				$rep->Line($rep->row - 8);
				$rep->NewLine(2);
				$rep->TextCol(0, 3, _('Total'));
				for ($i = 0; $i < 4; $i++)
				{
					$rep->AmountCol($i + 4, $i + 5, $total[$i], $dec);
					$total[$i] = 0.0;
				}
				$rep->Line($rep->row - 4);
				$rep->NewLine(2);
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

?>
