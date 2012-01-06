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
	$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_SALESTRANSVIEW : SA_SALESBULKREP;
	// ----------------------------------------------------------------
	// $ Revision:	2.0 $
	// Creator:	Joe Hunt
	// date_:	2005-05-19
	// Title:	Print Invoices
	// ----------------------------------------------------------------
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	print_invoices();
	function print_invoices() {
		include_once(APPPATH . "reports/pdf.php");
		$from = $_POST['PARAM_0'];
		$to = $_POST['PARAM_1'];
		$currency = $_POST['PARAM_2'];
		$email = $_POST['PARAM_3'];
		$paylink = $_POST['PARAM_4'];
		$comments = $_POST['PARAM_5'];
		if ($from == null) {
			$from = 0;
		}
		if ($to == null) {
			$to = 0;
		}
		$dec = User::price_dec();
		$fno = explode("-", $from);
		$tno = explode("-", $to);
		$cols = array(4, 60, 330, 355, 380, 410, 450, 470, 495);
		// $headers in doctext.inc
		$aligns = array('left', 'left', 'center', 'left', 'right', 'right', 'center', 'right', 'right');
		$params = array('comments' => $comments);
		$cur = DB_Company::get_pref('curr_default');
		if ($email == 0) {
			$rep = new ADVReport(_('TAX INVOICE'), "InvoiceBulk", User::pagesize());
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);
		}
		for ($i = $fno[0]; $i <= $tno[0]; $i++) {
			for ($j = ST_SALESINVOICE; $j <= ST_CUSTCREDIT; $j++) {
				if (isset($_POST['PARAM_6']) && $_POST['PARAM_6'] != $j) {
					continue;
				}
				if (!Debtor_Trans::exists($j, $i)) {
					continue;
				}
				$sign = $j == ST_SALESINVOICE ? 1 : -1;
				$myrow = Debtor_Trans::get($i, $j);
				$baccount = Bank_Account::get_default($myrow['curr_code']);
				$params['bankaccount'] = $baccount['id'];
				$branch = Sales_Branch::get($myrow["branch_id"]);
				$branch['disable_branch'] = $paylink; // helper
				if ($j == ST_SALESINVOICE) {
					$sales_order = Sales_Order::get_header($myrow["order_"], ST_SALESORDER);
				}
				else {
					$sales_order = null;
				}
				if ($email == 1) {
					$rep = new ADVReport("", "", User::pagesize());
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
				else {
					$rep->title = ($j == ST_SALESINVOICE) ? _('TAX INVOICE') : _('CREDIT NOTE');
				}
				$rep->Header2($myrow, $branch, $sales_order, $baccount, $j);
				$result = Debtor_TransDetail::get($j, $i);
				$SubTotal = 0;
				while ($myrow2 = DB::fetch($result)) {
					if ($myrow2["quantity"] == 0) {
						continue;
					}
					$Net = Num::round($sign * ((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]), User::price_dec());
					$SubTotal += $Net;
					$TaxType = Tax_ItemType::get_for_item($myrow2['stock_id']);
					$DisplayPrice = Num::format($myrow2["unit_price"], $dec);
					$DisplayQty = Num::format($sign * $myrow2["quantity"], Item::qty_dec($myrow2['stock_id']));
					$DisplayNet = Num::format($Net, $dec);
					if ($myrow2["discount_percent"] == 0) {
						$DisplayDiscount = "";
					}
					else {
						$DisplayDiscount = Num::format($myrow2["discount_percent"] * 100, User::percent_dec()) . "%";
					}
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
					if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
						$rep->Header2($myrow, $branch, $sales_order, $baccount, $j);
					}
				}
				$comments = DB_Comments::get($j, $i);
				if ($comments && DB::num_rows($comments)) {
					$rep->NewLine();
					while ($comment = DB::fetch($comments)) {
						$rep->TextColLines(0, 6, $comment['memo_'], -2);
					}
				}
				$DisplaySubTot = Num::format($SubTotal, $dec);
				$DisplayFreight = Num::format($sign * $myrow["ov_freight"], $dec);
				$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
				$linetype = true;
				$doctype = $j;
				if ($rep->currency != $myrow['curr_code']) {
					include(DOCROOT . "/reporting/includes/doctext2.php");
				}
				else {
					include(DOCROOT . "/reporting/includes/doctext.php");
				}
				$rep->TextCol(3, 7, $doc_Sub_total, -2);
				$rep->TextCol(7, 8, $DisplaySubTot, -2);
				$rep->NewLine();
				$rep->TextCol(3, 7, $doc_Shipping, -2);
				$rep->TextCol(7, 8, $DisplayFreight, -2);
				$rep->NewLine();
				$tax_items = GL_Trans::get_tax_details($j, $i);
				while ($tax_item = DB::fetch($tax_items)) {
					$DisplayTax = Num::format($sign * $tax_item['amount'], $dec);
					if ($tax_item['included_in_price']) {
						$rep->TextCol(3, 7, $doc_Included . " " . $tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%) " . $doc_Amount . ": " . $DisplayTax, -2);
					}
					else {
						$rep->TextCol(3, 7, $tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%)", -2);
						$rep->TextCol(7, 8, $DisplayTax, -2);
					}
				}
				$rep->NewLine();
				$DisplayTotal = Num::format($sign * ($myrow["ov_freight"] + $myrow["ov_gst"] + $myrow["ov_amount"] + $myrow["ov_freight_tax"]), $dec);
				$rep->Font('bold');
				$rep->TextCol(3, 7, $doc_TOTAL_INVOICE, -2);
				$rep->TextCol(7, 8, $DisplayTotal, -2);
				$words = Item_Price::to_words($myrow['Total'], $j);
				$rep->NewLine();
				$rep->NewLine();
				$invBalance = Sales_Allocation::get_balance($myrow['type'], $myrow['trans_no']);
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
					$myrow['email'] = $myrow['email'] ? : Input::get('Email');
					if (!$myrow['email']) {
						$myrow['email'] = $branch['email'];
						$myrow['DebtorName'] = $branch['br_name'];
					}
					$rep->End($email, $doc_Invoice_no . " " . $myrow['reference'], $myrow, $j);
				}
			}
		}
		if ($email == 0) {
			$rep->End();
		}
	}

