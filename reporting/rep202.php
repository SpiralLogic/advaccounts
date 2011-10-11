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
	$page_security = 'SA_SUPPLIERANALYTIC';
	// ----------------------------------------------------------------
	// $ Revision:	2.0 $
	// Creator:	Joe Hunt
	// date_:	2005-05-19
	// Title:	Ages Supplier Analysis
	// ----------------------------------------------------------------

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	//----------------------------------------------------------------------------------------------------

	print_aged_supplier_analysis();

	//----------------------------------------------------------------------------------------------------

	function get_invoices($supplier_id, $to) {
		$todate = Dates::date2sql($to);
		$PastDueDays1 = get_company_pref('past_due_days');
		$PastDueDays2 = 2 * $PastDueDays1;

		// Revomed allocated from sql
		$value = "(supp_trans.ov_amount + supp_trans.ov_gst + supp_trans.ov_discount)";
		$due = "IF (supp_trans.type=" . ST_SUPPINVOICE . " OR supp_trans.type=" . ST_SUPPCREDIT . ",supp_trans.due_date,supp_trans.tran_date)";
		$sql = "SELECT supp_trans.type,
		supp_trans.reference,
		supp_trans.tran_date,
		$value as Balance,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0) AS Due,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $PastDueDays1,$value,0) AS Overdue1,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $PastDueDays2,$value,0) AS Overdue2

		FROM suppliers,
			payment_terms,
			supp_trans

	   	WHERE suppliers.payment_terms = payment_terms.terms_indicator
			AND suppliers.supplier_id = supp_trans.supplier_id
			AND supp_trans.supplier_id = $supplier_id
			AND supp_trans.tran_date <= '$todate'
			AND ABS(supp_trans.ov_amount + supp_trans.ov_gst + supp_trans.ov_discount) > 0.004
			ORDER BY supp_trans.tran_date";

		return DBOld::query($sql, "The supplier details could not be retrieved");
	}

	//----------------------------------------------------------------------------------------------------

	function print_aged_supplier_analysis() {
		global $systypes_array;

		$to = $_POST['PARAM_0'];
		$fromsupp = $_POST['PARAM_1'];
		$currency = $_POST['PARAM_2'];
		$summaryOnly = $_POST['PARAM_3'];
		$no_zeros = $_POST['PARAM_4'];
		$graphics = $_POST['PARAM_5'];
		$comments = $_POST['PARAM_6'];
		$destination = $_POST['PARAM_7'];
		if ($destination)
			include_once(APP_PATH . "reporting/includes/excel_report.inc");
		else
			include_once(APP_PATH . "reporting/includes/pdf_report.inc");
		if ($graphics) {
			include_once(APP_PATH . "reporting/includes/class.graphic.inc");
			$pg = new graph();
		}

		if ($fromsupp == ALL_NUMERIC)
			$from = _('All');
		else
			$from = get_supplier_name($fromsupp);
		$dec = user_price_dec();

		if ($summaryOnly == 1)
			$summary = _('Summary Only');
		else
			$summary = _('Detailed Report');
		if ($currency == ALL_TEXT) {
			$convert = true;
			$currency = _('Balances in Home Currency');
		}
		else
			$convert = false;

		if ($no_zeros) $nozeros = _('Yes');
		else $nozeros = _('No');

		$PastDueDays1 = get_company_pref('past_due_days');
		$PastDueDays2 = 2 * $PastDueDays1;
		$nowdue = "1-" . $PastDueDays1 . " " . _('Days');
		$pastdue1 = $PastDueDays1 + 1 . "-" . $PastDueDays2 . " " . _('Days');
		$pastdue2 = _('Over') . " " . $PastDueDays2 . " " . _('Days');

		$cols = array(0, 100, 130, 190, 250, 320, 385, 450, 515);

		$headers = array(_('Supplier'), '', '', _('Current'), $nowdue, $pastdue1, $pastdue2,
			_('Total Balance')
		);

		$aligns = array('left', 'left', 'left', 'right', 'right', 'right', 'right', 'right');

		$params = array(0 => $comments,
			1 => array('text' => _('End Date'), 'from' => $to, 'to' => ''),
			2 => array('text' => _('Supplier'), 'from' => $from, 'to' => ''),
			3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
			4 => array('text' => _('Type'), 'from' => $summary, 'to' => ''),
			5 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => '')
		);

		if ($convert)
			$headers[2] = _('currency');
		$rep = new FrontReport(_('Aged Supplier Analysis'), "AgedSupplierAnalysis", user_pagesize());

		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header();

		$total = array();
		$total[0] = $total[1] = $total[2] = $total[3] = $total[4] = 0.0;
		$PastDueDays1 = get_company_pref('past_due_days');
		$PastDueDays2 = 2 * $PastDueDays1;

		$nowdue = "1-" . $PastDueDays1 . " " . _('Days');
		$pastdue1 = $PastDueDays1 + 1 . "-" . $PastDueDays2 . " " . _('Days');
		$pastdue2 = _('Over') . " " . $PastDueDays2 . " " . _('Days');

		$sql = "SELECT supplier_id, supp_name AS name, curr_code FROM suppliers";
		if ($fromsupp != ALL_NUMERIC)
			$sql .= " WHERE supplier_id=" . DBOld::escape($fromsupp);
		$sql .= " ORDER BY supp_name";
		$result = DBOld::query($sql, "The suppliers could not be retrieved");

		while ($myrow = DBOld::fetch($result))
		{
			if (!$convert && $currency != $myrow['curr_code']) continue;

			if ($convert) $rate = Banking::get_exchange_rate_from_home_currency($myrow['curr_code'], $to);
			else $rate = 1.0;

			$supprec = get_supplier_details($myrow['supplier_id'], $to);
			foreach ($supprec as $i => $value)
			{
				$supprec[$i] *= $rate;
			}

			$str = array($supprec["Balance"] - $supprec["Due"],
				$supprec["Due"] - $supprec["Overdue1"],
				$supprec["Overdue1"] - $supprec["Overdue2"],
				$supprec["Overdue2"],
				$supprec["Balance"]
			);

			if ($no_zeros && array_sum($str) == 0) continue;

			$rep->fontSize += 2;
			$rep->TextCol(0, 2, $myrow['name']);
			if ($convert) $rep->TextCol(2, 3, $myrow['curr_code']);
			$rep->fontSize -= 2;
			$total[0] += ($supprec["Balance"] - $supprec["Due"]);
			$total[1] += ($supprec["Due"] - $supprec["Overdue1"]);
			$total[2] += ($supprec["Overdue1"] - $supprec["Overdue2"]);
			$total[3] += $supprec["Overdue2"];
			$total[4] += $supprec["Balance"];
			for ($i = 0; $i < count($str); $i++)
			{
				$rep->AmountCol($i + 3, $i + 4, $str[$i], $dec);
			}
			$rep->NewLine(1, 2);
			if (!$summaryOnly) {
				$res = get_invoices($myrow['supplier_id'], $to);
				if (DBOld::num_rows($res) == 0)
					continue;
				$rep->Line($rep->row + 4);
				while ($trans = DBOld::fetch($res))
				{
					$rep->NewLine(1, 2);
					$rep->TextCol(0, 1, $systypes_array[$trans['type']], -2);
					$rep->TextCol(1, 2, $trans['reference'], -2);
					$rep->TextCol(2, 3, Dates::sql2date($trans['tran_date']), -2);
					foreach ($trans as $i => $value)
					{
						$trans[$i] *= $rate;
					}
					$str = array($trans["Balance"] - $trans["Due"],
						$trans["Due"] - $trans["Overdue1"],
						$trans["Overdue1"] - $trans["Overdue2"],
						$trans["Overdue2"],
						$trans["Balance"]
					);
					for ($i = 0; $i < count($str); $i++)
					{
						$rep->AmountCol($i + 3, $i + 4, $str[$i], $dec);
					}
				}
				$rep->Line($rep->row - 8);
				$rep->NewLine(2);
			}
		}
		if ($summaryOnly) {
			$rep->Line($rep->row + 4);
			$rep->NewLine();
		}
		$rep->fontSize += 2;
		$rep->TextCol(0, 3, _('Grand Total'));
		$rep->fontSize -= 2;
		for ($i = 0; $i < count($total); $i++)
		{
			$rep->AmountCol($i + 3, $i + 4, $total[$i], $dec);
			if ($graphics && $i < count($total) - 1) {
				$pg->y[$i] = abs($total[$i]);
			}
		}
		$rep->Line($rep->row - 8);
		$rep->NewLine();
		if ($graphics) {

			$pg->x = array(_('Current'), $nowdue, $pastdue1, $pastdue2);
			$pg->title = $rep->title;
			$pg->axis_x = _("Days");
			$pg->axis_y = _("Amount");
			$pg->graphic_1 = $to;
			$pg->type = $graphics;
			$pg->skin = Config::get('graphs.skin');
			$pg->built_in = false;
			$pg->fontfile = PATH_TO_ROOT . "/reporting/fonts/Vera.ttf";
			$pg->latin_notation = (Config::get('seperators.decimal', $_SESSION["wa_current_user"]->prefs->dec_sep()) != ".");

			$filename = COMPANY_PATH . "/pdf_files/test.png";
			$pg->display($filename, true);
			$w = $pg->width / 1.5;
			$h = $pg->height / 1.5;
			$x = ($rep->pageWidth - $w) / 2;
			$rep->NewLine(2);
			if ($rep->row - $h < $rep->bottomMargin)
				$rep->Header();
			$rep->AddImage($filename, $x, $rep->row - $h, $w, $h);
		}
		$rep->End();
	}

?>
