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
	$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	 'SA_SALESTRANSVIEW' : 'SA_SALESBULKREP';
	// ----------------------------------------------------------------
	// $ Revision:	2.0 $
	// Creator:	Janusz Dobrwolski
	// date_:	2008-01-14
	// Title:	Print Delivery Notes
	// draft version!
	// ----------------------------------------------------------------

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	$packing_slip = 0;
	//----------------------------------------------------------------------------------------------------

	print_deliveries();

	//----------------------------------------------------------------------------------------------------

	function print_deliveries() {
		global $packing_slip;

		require(APP_PATH. "includes/reports/pdf.php");

		$from = $_POST['PARAM_0'];
		$to = $_POST['PARAM_1'];
		$email = $_POST['PARAM_2'];
		$packing_slip = $_POST['PARAM_3'];
		$comments = $_POST['PARAM_4'];

		if ($from == null)
			$from = 0;
		if ($to == null)
			$to = 0;
		$dec = user_price_dec();

		$fno = explode("-", $from);
		$tno = explode("-", $to);

		$cols = array(4, 60, 225, 450, 515);

		// $headers in doctext.inc
		$aligns = array('left', 'left', 'right', 'right');

		$params = array('comments' => $comments);

		$cur = DB_Company::get_pref('curr_default');

		if ($email == 0) {
			if ($packing_slip == 0)
				$rep = new FrontReport(_('DELIVERY'), "DeliveryNoteBulk", user_pagesize());
			else
				$rep = new FrontReport(_('PACKING SLIP'), "PackingSlipBulk", user_pagesize());
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);
		}

		for ($i = $fno[0]; $i <= $tno[0]; $i++)
		{
			if (!exists_customer_trans(ST_CUSTDELIVERY, $i))
				continue;
			$myrow = get_customer_trans($i, ST_CUSTDELIVERY);
			$branch = get_branch($myrow["branch_code"]);
			$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER); // ?
			if ($email == 1) {
				$rep = new FrontReport("", "", user_pagesize());
				$rep->currency = $cur;
				$rep->Font();
				if ($packing_slip == 0) {
					$rep->title = _('DELIVERY NOTE');
					$rep->filename = "Delivery" . $myrow['reference'] . ".pdf";
} else {
					$rep->title = _('PACKING SLIP');
					$rep->filename = "Packing_slip" . $myrow['reference'] . ".pdf";
				}
				$rep->Info($params, $cols, null, $aligns);
			}
			else
				$rep->title = _('DELIVERY NOTE');
			$rep->Header2($myrow, $branch, $sales_order, '', ST_CUSTDELIVERY);

			$result = get_customer_trans_details(ST_CUSTDELIVERY, $i);
			$SubTotal = 0;
			while ($myrow2 = DBOld::fetch($result))
			{
				if ($myrow2["quantity"] == 0)
					continue;

				$DisplayPrice = number_format2($myrow2["unit_price"], $dec);
				$DisplayQty = number_format2($myrow2["quantity"], get_qty_dec($myrow2['stock_id']));

				$rep->TextCol(0, 1, $myrow2['stock_id'], -2);
				$oldrow = $rep->row;
				$rep->TextColLines(1, 2, $myrow2['StockDescription'], -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				$rep->TextCol(2, 3, $DisplayQty, -2);

				$rep->row = $newrow;
				//$rep->NewLine(1);
				if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
					$rep->Header2($myrow, $branch, $sales_order, '', ST_CUSTDELIVERY);
			}

			$comments = DB_Comments::get(ST_CUSTDELIVERY, $i);
			if ($comments && DBOld::num_rows($comments)) {
				$rep->NewLine();
				while ($comment = DBOld::fetch($comments))
				{
					$rep->TextColLines(0, 6, $comment['memo_'], -2);
				}
			}

			$DisplaySubTot = number_format2($SubTotal, $dec);
			$DisplayFreight = number_format2($myrow["ov_freight"], $dec);

			$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
			$linetype = true;
			$doctype = ST_CUSTDELIVERY;
			if ($rep->currency != $myrow['curr_code']) {
				include(APP_PATH . "reporting/includes/doctext2.php");
} else {
				include(APP_PATH . "reporting/includes/doctext.php");
			}

			if ($email == 1) {
				if ($myrow['email'] == '') {
					$myrow['email'] = $branch['email'];
					$myrow['DebtorName'] = $branch['br_name'];
				}
				$rep->End($email, $doc_Delivery_no . " " . $myrow['reference'], $myrow, ST_CUSTDELIVERY);
			}
		}
		if ($email == 0)
			$rep->End();
	}

?>