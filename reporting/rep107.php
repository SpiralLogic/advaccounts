<?php

	/*     * ********************************************************************
				Copyright (C) FrontAccounting, LLC.
				Released under the terms of the GNU General Public License, GPL,
				as published by the Free Software Foundation, either version 3
				of the License, or (at your option) any later version.
				This program is distributed in the hope that it will be useful,
				but WITHOUT ANY WARRANTY; without even the implied warranty of
				MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
				See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
			 * ********************************************************************* */
	$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	 'SA_SALESTRANSVIEW' : 'SA_SALESBULKREP';
	// ----------------------------------------------------------------
	// $ Revision:	2.0 $
	// Creator:	Joe Hunt
	// date_:	2005-05-19
	// Title:	Print Invoices
	// ----------------------------------------------------------------
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	//----------------------------------------------------------------------------------------------------
	print_invoices();
	//----------------------------------------------------------------------------------------------------
	function print_invoices() {
		include_once(APP_PATH . "reporting/includes/pdf_report.php");
		$from = $_POST['PARAM_0'];
		$to = $_POST['PARAM_1'];
		$currency = $_POST['PARAM_2'];
		$email = $_POST['PARAM_3'];
		$paylink = $_POST['PARAM_4'];
		$comments = $_POST['PARAM_5'];
		if ($from == null)
			$from = 0;
		if ($to == null)
			$to = 0;
		$dec = user_price_dec();
		$fno = explode("-", $from);
		$tno = explode("-", $to);
		$cols = array(4, 60, 330, 355, 380, 410, 450, 470, 495);
		// $headers in doctext.inc
		$aligns = array('left', 'left', 'center', 'left', 'right', 'right', 'center', 'right', 'right');
		$params = array('comments' => $comments);
		$cur = DB_Company::get_pref('curr_default');
		if ($email == 0) {
			$rep = new FrontReport(_('TAX INVOICE'), "InvoiceBulk", user_pagesize());
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);
		}
		for ($i = $fno[0]; $i <= $tno[0]; $i++) {
			for ($j = ST_SALESINVOICE; $j <= ST_CUSTCREDIT; $j++) {
				if (isset($_POST['PARAM_6']) && $_POST['PARAM_6'] != $j)
					continue;
				if (!exists_customer_trans($j, $i))
					continue;
				$sign = $j == ST_SALESINVOICE ? 1 : -1;
				$myrow = get_customer_trans($i, $j);
				$baccount = get_default_bank_account($myrow['curr_code']);
				$params['bankaccount'] = $baccount['id'];
				$branch = get_branch($myrow["branch_code"]);
				$branch['disable_branch'] = $paylink; // helper
				if ($j == ST_SALESINVOICE)
					$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);
				else $sales_order = null;
				if ($email == 1) {
					$rep = new FrontReport("", "", user_pagesize());
					$rep->currency = $cur;
					$rep->Font();
					if ($j == ST_SALESINVOICE) {
						$rep->title = _('TAX INVOICE');
						$rep->filename = "Invoice" . $myrow['reference'] . ".pdf";
					}
					else {
						$rep->title = _('CREDIT NOTE');
						$rep->filename = "CreditNote" . $myrow['reference'] . ".pdf";
					}
					$rep->Info($params, $cols, null, $aligns);
				}
				else $rep->title = ($j == ST_SALESINVOICE) ? _('TAX INVOICE') : _('CREDIT NOTE');
				$rep->Header2($myrow, $branch, $sales_order, $baccount, $j);
				$result = get_customer_trans_details($j, $i);
				$SubTotal = 0;
				while ($myrow2 = DBOld::fetch($result)) {
					if ($myrow2["quantity"] == 0)
						continue;
					$Net = round2($sign * ((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
						user_price_dec());
					$SubTotal += $Net;
					$TaxType = get_item_tax_type_for_item($myrow2['stock_id']);
					$DisplayPrice = number_format2($myrow2["unit_price"], $dec);
					$DisplayQty = number_format2($sign * $myrow2["quantity"], get_qty_dec($myrow2['stock_id']));
					$DisplayNet = number_format2($Net, $dec);
					if ($myrow2["discount_percent"] == 0)
						$DisplayDiscount = "";
					else
						$DisplayDiscount = number_format2($myrow2["discount_percent"] * 100, user_percent_dec()) . "%";
					$rep->TextCol(0, 1, $myrow2['stock_id'], -2);
					$oldrow = $rep->row;
					$rep->TextColLines(1, 2, $myrow2['StockDescription'], -2);
					$newrow = $rep->row;
					$rep->row = $oldrow;
					$rep->TextCol(2, 3, $DisplayQty, -2);
					$rep->TextCol(3, 4, $myrow2['units'], -2);
					$rep->TextCol(4, 5, $DisplayPrice, -2);
					$rep->TextCol(5, 6, $DisplayDiscount, -2);
					$rep->TextCol(6, 7, $TaxType[1], -2);

					$rep->TextCol(7, 8, $DisplayNet, -2);
					$rep->row = $newrow;
					//$rep->NewLine(1);
					if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
						$rep->Header2($myrow, $branch, $sales_order, $baccount, $j);
				}
				$comments = DB_Comments::get($j, $i);
				if ($comments && DBOld::num_rows($comments)) {
					$rep->NewLine();
					while ($comment = DBOld::fetch($comments))
					{
						$rep->TextColLines(0, 6, $comment['memo_'], -2);
					}
				}
				$DisplaySubTot = number_format2($SubTotal, $dec);
				$DisplayFreight = number_format2($sign * $myrow["ov_freight"], $dec);
				$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
				$linetype = true;
				$doctype = $j;
				if ($rep->currency != $myrow['curr_code']) {
					include(APP_PATH . "/reporting/includes/doctext2.php");
				}
				else {
					include(APP_PATH . "/reporting/includes/doctext.php");
				}
				$rep->TextCol(3, 7, $doc_Sub_total, -2);
				$rep->TextCol(7, 8, $DisplaySubTot, -2);
				$rep->NewLine();
				$rep->TextCol(3, 7, $doc_Shipping, -2);
				$rep->TextCol(7, 8, $DisplayFreight, -2);
				$rep->NewLine();
				$tax_items = get_trans_tax_details($j, $i);
				while ($tax_item = DBOld::fetch($tax_items)) {
					$DisplayTax = number_format2($sign * $tax_item['amount'], $dec);
					if ($tax_item['included_in_price']) {
						$rep->TextCol(3, 7, $doc_Included . " " . $tax_item['tax_type_name'] .
						 " (" . $tax_item['rate'] . "%) " . $doc_Amount . ": " . $DisplayTax, -2);
					}
					else {
						$rep->TextCol(3, 7, $tax_item['tax_type_name'] . " (" .
						 $tax_item['rate'] . "%)", -2);
						$rep->TextCol(7, 8, $DisplayTax, -2);
					}
				}
				$rep->NewLine();
				$DisplayTotal = number_format2($sign * ($myrow["ov_freight"] + $myrow["ov_gst"] +
					 $myrow["ov_amount"] + $myrow["ov_freight_tax"]), $dec);
				$rep->Font('bold');
				$rep->TextCol(3, 7, $doc_TOTAL_INVOICE, -2);
				$rep->TextCol(7, 8, $DisplayTotal, -2);
				$words = ui_view::price_in_words($myrow['Total'], $j);
				$rep->NewLine();
				$rep->NewLine();
				$invBalance = get_DebtorTrans_allocation_balance($myrow['type'], $myrow['trans_no']);
				$rep->TextCol(3, 7, 'Total Received', -2);
				$rep->AmountCol(7, 8, $myrow['Total'] - $invBalance, $dec, -2);
				$rep->NewLine();
				$rep->TextCol(3, 7, 'Outstanding Balance', -2);
				$rep->AmountCol(7, 8, $invBalance, $dec, -2);
				$rep->NewLine();
				if ($words != "") {
					$rep->NewLine(1);
					$rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, -2);
				}
				$rep->Font();
				if ($email == 1) {
					$myrow['dimension_id'] = $paylink; // helper for pmt link
					if ($myrow['email'] == '') {
						$myrow['email'] = $branch['email'];
						$myrow['DebtorName'] = $branch['br_name'];
					}
					$rep->End($email, $doc_Invoice_no . " " . $myrow['reference'], $myrow, $j);
				}
			}
		}
		if ($email == 0)
			$rep->End();
	}

