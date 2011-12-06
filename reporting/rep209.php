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
	$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	 'SA_SUPPTRANSVIEW' : 'SA_SUPPBULKREP';
	// ----------------------------------------------------------------
	// $ Revision:	2.0 $
	// Creator:	Joe Hunt
	// date_:	2005-05-19
	// Title:	Purchase Orders
	// ----------------------------------------------------------------
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	print_po();

	function get_po($order_no)
		{
			# __ADVANCEDEDIT__ BEGIN # include suppliers phone and fax number
			$sql
			 = "SELECT purch_orders.*, suppliers.supp_name, suppliers.supp_account_no,
 		suppliers.curr_code, suppliers.payment_terms, suppliers.phone, suppliers.fax, locations.location_name,
 		suppliers.email, suppliers.address, suppliers.contact
		FROM purch_orders, suppliers, locations
		WHERE purch_orders.supplier_id = suppliers.supplier_id
		AND locations.loc_code = into_stock_location
		AND purch_orders.order_no = " . DB::escape($order_no);
			$result = DB::query($sql, "The order cannot be retrieved");
			return DB::fetch($result);
		}

	function get_po_details($order_no)
		{
			$sql
			 = "SELECT purch_order_details.*, units
		FROM purch_order_details
		LEFT JOIN stock_master
		ON purch_order_details.item_code=stock_master.stock_id
		WHERE order_no =" . DB::escape($order_no) . " ";
			$sql .= " ORDER BY po_detail_item";
			return DB::query($sql, "Retreive order Line Items");
		}

	function print_po()
		{
			include_once(APPPATH . "reports/pdf.php");
			$from = $_POST['PARAM_0'];
			$to = $_POST['PARAM_1'];
			$currency = $_POST['PARAM_2'];
			$email = $_POST['PARAM_3'];
			$comments = $_POST['PARAM_4'];
			if ($from == null) {
				$from = 0;
			}
			if ($to == null) {
				$to = 0;
			}
			$dec = User::price_dec();
			$cols = array(4, 80, 329, 330, 370, 405, 450, 515);
			// $headers in doctext.inc
			$aligns = array('left', 'left', 'left', 'center', 'right', 'right', 'right');
			$params = array('comments' => $comments);
			$cur = DB_Company::get_pref('curr_default');
			if ($email == 0) {
				$rep = new ADVReport(_('PURCHASE ORDER'), "PurchaseOrderBulk", User::pagesize());
				$rep->currency = $cur;
				$rep->Font();
				$rep->Info($params, $cols, null, $aligns);
			}
			for ($i = $from; $i <= $to; $i++)
			{
				$myrow = get_po($i);
				$baccount = Bank_Account::get_default($myrow['curr_code']);
				$params['bankaccount'] = $baccount['id'];
				if ($email == 1) {
					$rep = new ADVReport("", "", User::pagesize());
					$rep->currency = $cur;
					$rep->Font();
					$rep->title = _('PURCHASE ORDER');
					$rep->filename = "PurchaseOrder" . $i . ".pdf";
					$rep->Info($params, $cols, null, $aligns);
				}
				else
				{
					$rep->title = _('PURCHASE ORDER');
				}
				$rep->Header2($myrow, null, $myrow, $baccount, ST_PURCHORDER);
				$result = get_po_details($i);
				$SubTotal = 0;
				while ($myrow2 = DB::fetch($result))
				{
					if ($myrow2['item_code'] != 'freight' || $myrow['freight'] != $myrow2['unit_price']) {
						$data = Purch_Order::get_data($myrow['supplier_id'], $myrow2['item_code']);
						if ($data !== false) {
							if ($data['supplier_description'] != "") {
								$myrow2['item_code'] = $data['supplier_description'];
							}
							if ($data['suppliers_uom'] != "") {
								$myrow2['units'] = $data['suppliers_uom'];
							}
							if ($data['conversion_factor'] > 1) {
								$myrow2['unit_price'] = Num::round(
									$myrow2['unit_price'] * $data['conversion_factor'], User::price_dec());
								$myrow2['quantity_ordered'] = Num::round(
									$myrow2['quantity_ordered'] / $data['conversion_factor'], User::qty_dec());
							}
						}
						$Net = Num::round(($myrow2["unit_price"] * $myrow2["quantity_ordered"]), User::price_dec());
						$SubTotal += $Net;
						$dec2 = 0;
						$DisplayPrice = Num::price_decimal($myrow2["unit_price"], $dec2);
						$DisplayQty = Num::format($myrow2["quantity_ordered"], Item::qty_dec($myrow2['item_code']));
						$DisplayNet = Num::format($Net, $dec);
						$rep->TextCol(0, 1, $myrow2['item_code'], -2);
						$oldrow = $rep->row;
						$rep->TextColLines(1, 2, $myrow2['description'], -2);
						$newrow = $rep->row;
						$rep->row = $oldrow;
						//$rep->TextCol(2, 3,	Dates::sql2date($myrow2['delivery_date']), -2);
						$rep->TextCol(2, 3, '', -2);
						$rep->TextCol(3, 4, $DisplayQty, -2);
						$rep->TextCol(4, 5, $myrow2['units'], -2);
						$rep->TextCol(5, 6, $DisplayPrice, -2);
						$rep->TextCol(6, 7, $DisplayNet, -2);
						$rep->row = $newrow;
						if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
							$rep->Header2($myrow, $branch, $myrow, $baccount, ST_PURCHORDER);
						}
					}
				}
				if ($myrow['comments'] != "") {
					$rep->NewLine();
					$rep->TextColLines(1, 5, $myrow['comments'], -2);
				}
				$DisplaySubTot = Num::format($SubTotal, $dec);
				$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
				$linetype = true;
				$doctype = ST_PURCHORDER;
				if ($rep->currency != $myrow['curr_code']) {
					include(DOCROOT . "reporting/includes/doctext2.php");
				} else {
					include(DOCROOT . "reporting/includes/doctext.php");
				}
				$rep->TextCol(3, 6, $doc_Sub_total, -2);
				$rep->TextCol(6, 7, $DisplaySubTot, -2);
				$rep->NewLine();
				$rep->TextCol(3, 6, 'Freight:', -2);
				$rep->TextCol(6, 7, Num::format($myrow['freight'], $dec), -2);
				$rep->NewLine();
				$DisplayTotal = Num::format($SubTotal + $myrow['freight'], $dec);
				$rep->Font('bold');
				$rep->TextCol(3, 6, $doc_TOTAL_PO, -2);
				$rep->TextCol(6, 7, $DisplayTotal, -2);
				$words = Item_Price::to_words($SubTotal, ST_PURCHORDER);
				if ($words != "") {
					$rep->NewLine(1);
					$rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, -2);
				}
				$rep->Font();
				if ($email == 1) {
					$myrow['contact_email'] = $myrow['email'];
					$myrow['DebtorName'] = $myrow['supp_name'];
					if ($myrow['contact'] != '') {
						$myrow['DebtorName'] = $myrow['contact'];
					}
					if ($myrow['reference'] == "") {
						$myrow['reference'] = $myrow['order_no'];
					}
					$rep->End($email, $doc_Order_no . " " . $myrow['reference'], $myrow);
				}
			}
			if ($email == 0) {
				$rep->End();
			}
		}

?>