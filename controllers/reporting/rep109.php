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

	Page::set_security($_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_SALESTRANSVIEW : SA_SALESBULKREP);


	print_sales_orders();
	$print_as_quote = 0;
	function print_sales_orders()
		{
			global $print_as_quote;
			include_once(APPPATH . "reports/pdf.php");
			$from = $_POST['PARAM_0'];
			$to = $_POST['PARAM_1'];
			$currency = $_POST['PARAM_2'];
			$email = $_POST['PARAM_3'];
			$print_as_quote = $_POST['PARAM_4'];
			$comments = $_POST['PARAM_5'];
			if ($from == null) {
				$from = 0;
			}
			if ($to == null) {
				$to = 0;
			}
			$dec = User::price_dec();
			$cols = array(4, 70, 300, 320, 360, 395, 450, 475, 515,475);
			// $headers in doctext.inc
			$aligns = array('left', 'left', 'center', 'left', 'left', 'left', 'left', 'right');
			$params = array('comments' => $comments);
			$cur = DB_Company::get_pref('curr_default');
			if ($email == 0) {
				if ($print_as_quote == 0) {
					$rep = new ADVReport(_("ORDER"), "SalesOrderBulk", User::page_size());
				} elseif ($print_as_quote == 2) {
					$rep = new ADVReport(_("PROFORMA INVOICE"), "QuoteBulk", User::page_size());
				} elseif ($print_as_quote == 3) {
					$rep = new ADVReport(_("PROFORMA INVOICE"), "QuoteBulk", User::page_size());
				} else {
					$rep = new ADVReport(_("QUOTE"), "QuoteBulk", User::page_size());
				}
				$rep->currency = $cur;
				$rep->Font();
				$rep->Info($params, $cols, null, $aligns);
			}
			for (
				$i = $from; $i <= $to; $i++
			) {
				if ($print_as_quote < 3) {
					$myrow = Sales_Order::get_header($i, ST_SALESORDER);
				} else {
					$myrow = Sales_Order::get_header($i, ST_SALESQUOTE);
				}
				$baccount = Bank_Account::get_default($myrow['curr_code']);
				$params['bankaccount'] = $baccount['id'];
				$branch = Sales_Branch::get($myrow["branch_id"]);
				if ($email == 1) {
					$rep = new ADVReport("", "", User::page_size());
					$rep->currency = $cur;
					$rep->Font();
					if ($print_as_quote == 1) {
						$rep->title = _('QUOTE');
						$rep->filename = "Quote" . $i . ".pdf";
					} elseif ($print_as_quote == 2 || $print_as_quote == 3) {
						$rep->title = _('PROFORMA INVOICE');
						$rep->filename = "Proforna" . $i . ".pdf";
					} else {
						$rep->title = _("ORDER");
						$rep->filename = "SalesOrder" . $i . ".pdf";
					}
					$rep->Info($params, $cols, null, $aligns);
				} else {
					$rep->title = ($print_as_quote == 1) ? _("QUOTE") : _("ORDER");
					if ($print_as_quote == 2 || $print_as_quote == 3) {
						$rep->title = _("PROFORMA INVOICE");
					}
				}
				if ($print_as_quote == 3) {
					$rep->Header2($myrow, $branch, $myrow, $baccount, ST_PROFORMAQ);
					$result = Sales_Order::get_details($i, ST_SALESQUOTE);
				} elseif ($print_as_quote == 2) {
					$rep->Header2($myrow, $branch, $myrow, $baccount, ST_PROFORMA);
					$result = Sales_Order::get_details($i, ST_SALESORDER);
				} else {
					$rep->Header2($myrow, $branch, $myrow, $baccount, ST_SALESORDER);
					$result = Sales_Order::get_details($i, ST_SALESORDER);
				}
				$SubTotal = 0;
				$TaxTotal = 0;
				while ($myrow2 = DB::fetch($result)) {
					$Net = Num::round(
						((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] *
						 $myrow2["quantity"]), User::price_dec()
					);
					$SubTotal += $Net;
					# __ADVANCEDEDIT__ BEGIN #
					$TaxType = Tax_ItemType::get_for_item($myrow2['stk_code']);
					$TaxTotal += Tax::for_item($myrow2['stk_code'], $Net, $TaxType);
					# __ADVANCEDEDIT__ END #
					$DisplayPrice = Num::format($myrow2["unit_price"], $dec);
					$DisplayQty = Num::format($myrow2["quantity"], Item::qty_dec($myrow2['stk_code']));
					$DisplayNet = Num::format($Net, $dec);
					if ($myrow2["discount_percent"] == 0) {
						$DisplayDiscount = "";
					} else
					{
						$DisplayDiscount = Num::format($myrow2["discount_percent"] * 100, User::percent_dec()) . "%";
					}
					$rep->TextCol(0, 1, $myrow2['stk_code'], -2);
					$oldrow = $rep->row;
					$rep->TextColLines(1, 2, $myrow2['description'], -2);
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
					$rep->NewLine();
					if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
						if ($print_as_quote < 3) {
							$rep->Header2($myrow, $branch, $myrow, $baccount, ST_SALESORDER);
						} else {
							$rep->Header2($myrow, $branch, $myrow, $baccount, ST_SALESQUOTE);
						}
					}
				}
				if ($myrow['comments'] != "") {
					$rep->NewLine();
					$rep->TextColLines(1, 5, $myrow['comments'], -2);
				}
				if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
					if ($print_as_quote < 3) {
						$rep->Header2($myrow, $branch, $myrow, $baccount, ST_SALESORDER);
					} else {
						$rep->Header2($myrow, $branch, $myrow, $baccount, ST_SALESQUOTE);
					}
				}
				$display_freight = Num::format($myrow["freight_cost"], $dec);
				$SubTotal += $myrow["freight_cost"];
				$TaxTotal += $myrow['freight_cost'] * .1;
				$display_sub_total = Num::format($SubTotal, $dec);
				$DisplayTaxTot = Num::format($TaxTotal, $dec);
				$display_total = Num::format($SubTotal + $TaxTotal, $dec);
				$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
				$linetype = true;
				$doctype = ($print_as_quote < 3) ? ST_SALESORDER : ST_SALESQUOTE;
				if ($rep->currency != $myrow['curr_code']) {
					include(REPORTS_PATH . 'includes'.DS.'doctext2.php');
				} else {
					include(REPORTS_PATH . 'includes'.DS.'doctext.php');
				}
				$rep->TextCol(4, 7, $doc_shipping . ' (ex.GST)', -2);
				$rep->TextCol(7, 8, $display_freight, -2);
				$rep->NewLine();
				$rep->TextCol(4, 7, $doc_sub_total, -2);
				$rep->TextCol(7, 8, $display_sub_total, -2);
				$rep->NewLine();
				$rep->NewLine();
				# __ADVANCEDEDIT__ BEGIN # added tax to invoice
				$rep->TextCol(4, 7, 'Total GST (10%)', -2);
				$rep->TextCol(7, 8, $DisplayTaxTot, -2);
				$rep->NewLine();
				# __ADVANCEDEDIT__ END #
				$rep->Font('bold');
				#	if ($myrow['tax_included'] == 0)
				#	$rep->TextCol(4, 7, $doc_TOTAL_ORDER, - 2);
				#	else
				$rep->TextCol(4, 7, $doc_TOTAL_ORDER2, -2);
				$rep->TextCol(7, 8, $display_total, -2);
				if ($print_as_quote < 3) {
					$words = Item_Price::to_words($myrow["freight_cost"] + $SubTotal, ST_SALESORDER);
				} else {
					$words = Item_Price::to_words($myrow["freight_cost"] + $SubTotal, ST_SALESQUOTE);
				}
				if ($words != "") {
					$rep->NewLine(1);
					$rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, -2);
				}
				$rep->Font();
				if ($email == 1) {
					if ($myrow['contact_email'] == '') {
						$myrow['contact_email'] = $branch['email'];
						if ($myrow['contact_email'] == '') {
							$myrow['contact_email'] = $myrow['master_email'];
						}
						$myrow['DebtorName'] = $branch['br_name'];
					}
					//$myrow['reference'] = $i;
					$rep->End($email, $doc_invoice_no . " " . $i, $myrow);
				}
			}
			if ($email == 0) {
				$rep->End();
			}
		}
