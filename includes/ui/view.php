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

	class ui_view {
		//--------------------------------------------------------------------------------------
		static function get_supplier_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			$viewer = "purchasing/view/";
			if ($type == ST_PURCHORDER) {
				$viewer .= "view_po.php";
			}
			elseif ($type == ST_SUPPINVOICE) {
				$viewer .= "view_supp_invoice.php";
			}
			elseif ($type == ST_SUPPCREDIT) {
				$viewer .= "view_supp_credit.php";
			}
			elseif ($type == ST_SUPPAYMENT) {
				$viewer .= "view_supp_payment.php";
			}
			elseif ($type == ST_SUPPRECEIVE) {
				$viewer .= "view_grn.php";
			}
			else {
				return null;
			}
			$viewer .= "?trans_no=$trans_no";
			if ($label == "") {
				$label = $trans_no;
			}
			return viewer_link($label, $viewer, $class, $id, $icon);
		}

		//--------------------------------------------------------------------------------------
		static function get_gl_view_str($type, $trans_no, $label = "", $force = false, $class = '', $id = '') {
			if (!$force && !user_show_gl_info()) {
				return "";
			}
			$icon = false;
			if ($label == "") {
				$label = _("GL");
				$icon  = ICON_GL;
			}
			return viewer_link($label, "gl/view/gl_trans_view.php?type_id=$type&trans_no=$trans_no", $class, $id, $icon);
		}

		//--------------------------------------------------------------------------------------
		static function get_gl_view_str_cell($type, $trans_no, $label = "") {
			$str = ui_view::get_gl_view_str($type, $trans_no, $label);
			if ($str != "") {
				return "<td>$str</td>";
			}
		}

		//--------------------------------------------------------------------------------------
		static function get_customer_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			$viewer = "sales/view/";
			if ($type == ST_SALESINVOICE) {
				$viewer .= "view_invoice.php";
			}
			elseif ($type == ST_CUSTCREDIT) {
				$viewer .= "view_credit.php";
			}
			elseif ($type == ST_CUSTPAYMENT) {
				$viewer .= "view_receipt.php";
			}
			elseif ($type == ST_CUSTREFUND) {
				$viewer .= "view_receipt.php";
			}
			elseif ($type == ST_CUSTDELIVERY) {
				$viewer .= "view_dispatch.php";
			}
			elseif ($type == ST_SALESORDER || $type == ST_SALESQUOTE) {
				$viewer .= "view_sales_order.php";
			}
			else {
				return null;
			}
			if (!is_array($trans_no)) {
				$trans_no = array($trans_no);
			}
			$lbl         = $label;
			$preview_str = '';
			foreach ($trans_no as $trans) {
				if ($label == "") {
					$lbl = $trans;
				}
				if ($preview_str != '') {
					$preview_str .= ',';
				}
				$preview_str .= viewer_link($lbl, $viewer . "?trans_no=$trans&trans_type=$type", $class, $id, $icon);
			}
			return $preview_str;
		}

		//--------------------------------------------------------------------------------------
		static function get_banking_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			if ($label == "") {
				$label = $trans_no;
			}
			if ($type == ST_BANKTRANSFER) {
				$viewer = "bank_transfer_view.php";
			}
			elseif ($type == ST_BANKPAYMENT) {
				$viewer = "gl_payment_view.php";
			}
			elseif ($type == ST_BANKDEPOSIT) {
				$viewer = "gl_deposit_view.php";
			}
			else {
				return null;
			}
			return viewer_link($label, "gl/view/$viewer?trans_no=$trans_no", $class, $id, $icon);
		}

		//--------------------------------------------------------------------------------------
		static function get_inventory_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			$viewer = "inventory/view/";
			if ($type == ST_INVADJUST) {
				$viewer .= "view_adjustment.php";
			}
			elseif ($type == ST_LOCTRANSFER) {
				$viewer .= "view_transfer.php";
			}
			else {
				return null;
			}
			$viewer .= "?trans_no=$trans_no";
			if ($label == "") {
				$label = $trans_no;
			}
			return viewer_link($label, $viewer, $class, $id, $icon);
		}

		//--------------------------------------------------------------------------------------
		static function get_manufacturing_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			$viewer = "manufacturing/view/";
			if ($type == ST_MANUISSUE) {
				$viewer .= "wo_issue_view.php";
			}
			elseif ($type == ST_MANURECEIVE) {
				$viewer .= "wo_production_view.php";
			}
			elseif ($type == ST_WORKORDER) {
				$viewer .= "work_order_view.php";
			}
			else {
				return null;
			}
			$viewer .= "?trans_no=$trans_no";
			if ($label == "") {
				$label = $trans_no;
			}
			return viewer_link($label, $viewer, $class, $id, $icon);
		}

		//--------------------------------------------------------------------------------------
		static function get_dimensions_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			if ($type == ST_DIMENSION) {
				$viewer = "dimensions/view/view_dimension.php?trans_no=$trans_no";
			}
			else {
				return null;
			}
			if ($label == "") {
				$label = $trans_no;
			}
			return viewer_link($label, $viewer, $class, $id, $icon);
		}

		/*
				 Journal entry or cost update postings link
		 */
		static function get_journal_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			if ($type == ST_JOURNAL || $type == ST_COSTUPDATE) {
				$viewer = "gl/view/gl_trans_view.php?type_id=$type&trans_no=$trans_no";
			}
			else {
				return null;
			}
			if ($label == "") {
				$label = $trans_no;
			}
			return viewer_link($label, $viewer, $class, $id, $icon);
		}

		//--------------------------------------------------------------------------------------
		static function get_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			$view_str = ui_view::get_customer_trans_view_str($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			$view_str = ui_view::get_supplier_trans_view_str($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			$view_str = ui_view::get_banking_trans_view_str($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			$view_str = ui_view::get_inventory_trans_view_str($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			$view_str = ui_view::get_manufacturing_trans_view_str($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			$view_str = ui_view::get_dimensions_trans_view_str($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			$view_str = ui_view::get_journal_trans_view_str($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			return null;
		}

		//--------------------------------------------------------------------------------------
		// Displays currency exchange rate for given date.
		// When there is no exrate for today,
		// gets it form ECB and stores in local database.
		//
		static function exchange_rate_display($from_currency, $to_currency, $date_, $edit_rate = false) {
			$Ajax = Ajax::instance();
			if ($from_currency != $to_currency) {
				$comp_currency = Banking::get_company_currency();
				if ($from_currency == $comp_currency) {
					$currency = $to_currency;
				}
				else {
					$currency = $from_currency;
				}
				$rate = 0;
				if ($date_ == Dates::Today()) {
					$rate = get_date_exchange_rate($currency, $date_);
					if (!$rate) {
						$row = get_currency($currency);
						if ($row['auto_update']) {
							$rate = retrieve_exrate($currency, $date_);
							if ($rate) {
								add_exchange_rate($currency, $date_, $rate, $rate);
							}
						}
					}
				}
				if (!$rate) {
					$rate = Banking::get_exchange_rate_from_home_currency($currency, $date_);
				}
				if ($from_currency != $comp_currency) {
					$rate = 1 / ($rate / Banking::get_exchange_rate_from_home_currency($to_currency, $date_));
				}
				$rate = number_format2($rate, user_exrate_dec());
				if ($edit_rate) {
					text_row(_("Exchange Rate:"), '_ex_rate', $rate, 8, 8, null, "", " $from_currency = 1 $to_currency");
				}
				else {
					label_row(_("Exchange Rate:"), "<span style='vertical-align:top;' id='_ex_rate'>$rate</span> $from_currency = 1 $to_currency");
				}
				$Ajax->addUpdate('_ex_rate', '_ex_rate', $rate);
			}
		}

		//--------------------------------------------------------------------------------------
		static function is_voided_display($type, $id, $label) {
			$void_entry = Voiding::get($type, $id);
			if ($void_entry == null) {
				return false;
			}
			start_table("width=50%  " . Config::get('tables_style'));
			echo "<tr><td align=center><font color=red>$label</font><br>";
			echo "<font color=red>" . _("Date Voided:") . " " . Dates::sql2date($void_entry["date_"]) . "</font><br>";
			if (strlen($void_entry["memo_"]) > 0) {
				echo "<center><font color=red>" . _("Memo:") . " " . $void_entry["memo_"] . "</font></center><br>";
			}
			echo "</td></tr>";
			end_table(1);
			return true;
		}

		//--------------------------------------------------------------------------------------
		static function comments_display_row($type, $id) {
			$comments = DB_Comments::get($type, $id);
			if ($comments and DBOld::num_rows($comments)) {
				echo "<tr><td colspan=15>";
				while ($comment = DBOld::fetch($comments)) {
					echo $comment["memo_"] . "<br>";
				}
				echo "</td></tr>";
			}
		}

		//--------------------------------------------------------------------------------------
		static function get_comments_string($type, $type_no) {
			$str_return = "";
			$result     = DB_Comments::get($type, $type_no);
			while ($comment = DBOld::fetch($result)) {
				if (strlen($str_return)) {
					$str_return = $str_return . " \n";
				}
				$str_return = $str_return . $comment["memo_"];
			}
			return $str_return;
		}

		//--------------------------------------------------------------------------------------
		static function view_stock_status($stock_id, $description = null, $echo = true) {

			if ($description) //hyperlink_params_separate( "/inventory/inquiry/stock_status.php", (user_show_codes()?$stock_id . " - ":"") . $description, "stock_id=$stock_id");
			{
				$preview_str = "<a target='_blank' href='/inventory/inquiry/stock_status.php?stock_id=$stock_id' onclick=\"javascript:openWindow(this.href,this.target); return false;\" >" . (user_show_codes()
				 ? $stock_id . " - " : "") . $description . "</a>";
			}
			else //hyperlink_params_separate( "/inventory/inquiry/stock_status.php", $stock_id, "stock_id=$stock_id");
			{
				$preview_str = "<a target='_blank' href='/inventory/inquiry/stock_status.php?stock_id=$stock_id' onclick=\"javascript:openWindow(this.href,this.target); return false;\" >$stock_id</a>";
			}
			if ($echo) {
				echo $preview_str;
			}
			return $preview_str;
		}

		static function view_stock_status_cell($stock_id, $description = null) {
			echo "<td>";
			ui_view::view_stock_status($stock_id, $description);
			echo "</td>";
		}

		//--------------------------------------------------------------------------------------
		static function display_debit_or_credit_cells($value) {
			$value = round2($value, user_price_dec());
			if ($value >= 0) {
				amount_cell($value);
				label_cell("");
			}
			elseif ($value < 0) {
				label_cell("");
				amount_cell(abs($value));
			}
		}

		//--------------------------------------------------------------------------------------
		static function display_customer_trans_tax_details($tax_items, $columns) {
			while ($tax_item = DBOld::fetch($tax_items)) {
				$tax = number_format2($tax_item['amount'], user_price_dec());
				if ($tax_item['included_in_price']) {
					label_row(_("Included") . " " . $tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%) " . _("Amount") . ": $tax", "", "colspan=$columns align=right", "align=right");
				}
				else {
					label_row($tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%)", $tax, "colspan=$columns align=right", "align=right");
				}
			}
		}

		//--------------------------------------------------------------------------------------
		static function display_supp_trans_tax_details($tax_items, $columns, $tax_recorded = 0) {
			$tax_total = 0;
			while ($tax_item = DBOld::fetch($tax_items)) {
				$tax = number_format2(abs($tax_item['amount']), user_price_dec());
				if ($tax_item['included_in_price']) {
					label_row(_("Included") . " " . $tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%) " . _("Amount") . ": $tax", "colspan=$columns align=right", "align=right");
				}
				else {
					label_row($tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%)", $tax, "colspan=$columns align=right", "align=right");
				}
				$tax_total += $tax;
			}
			if ($tax_recorded != 0) {
				$tax_correction = number_format2($tax_recorded - $tax_total, user_price_dec());
				label_row("Tax Correction" . " ", $tax_correction, "colspan=$columns align=right", "align=right");
			}
		}

		//--------------------------------------------------------------------------------------
		static function display_edit_tax_items($taxes, $columns, $tax_included, $leftspan = 0, $tax_correcting = false) {
			$total = 0;
			foreach ($taxes as $taxitem) {
				if ($tax_included) {
					label_row(_("Included") . " " . $taxitem['tax_type_name'] . " (" . $taxitem['rate'] . "%) " . _("Amount:") . " ", number_format2($taxitem['Value'], user_price_dec()), "colspan=$columns align=right", "align=right", $leftspan);
				}
				else {
					$total += round2($taxitem['Value'], user_price_dec());
					label_row($taxitem['tax_type_name'] . " (" . $taxitem['rate'] . "%)", number_format2($taxitem['Value'], user_price_dec()), "colspan=$columns align=right", "align=right", $leftspan);
				}
			}
			if ($tax_correcting) {
				label_cell(_("Tax Correction"), "colspan=$columns align=right width='90%'");
				small_amount_cells(null, 'ChgTax', price_format(get_post('ChgTax'), 2));
				end_row();
				$total += get_post('ChgTax');
			}
			return $total;
		}

		//--------------------------------------------------------------------------------------
		static function display_footer_exit() {
			br(2);
			end_page(false, false, true);
			exit;
		}

		//--------------------------------------------------------------------------------------
		static function display_allocations($alloc_result, $total) {
			global $systypes_array;
			if (!$alloc_result || DBOld::num_rows($alloc_result) == 0) {
				return;
			}
			ui_msgs::display_heading2(_("Allocations"));
			start_table(Config::get('tables_style') . "  width=90%");
			$th = array(_("Type"), _("Number"), _("Date"), _("Total Amount"), _("Left to Allocate"), _("This Allocation"));
			table_header($th);
			$k = $total_allocated = 0;
			while ($alloc_row = DBOld::fetch($alloc_result)) {
				alt_table_row_color($k);
				label_cell($systypes_array[$alloc_row['type']]);
				label_cell(ui_view::get_trans_view_str($alloc_row['type'], $alloc_row['trans_no']));
				label_cell(Dates::sql2date($alloc_row['tran_date']));
				$alloc_row['Total'] = round2($alloc_row['Total'], user_price_dec());
				$alloc_row['amt']   = round2($alloc_row['amt'], user_price_dec());
				amount_cell($alloc_row['Total']);
				//amount_cell($alloc_row['Total'] - $alloc_row['PrevAllocs'] - $alloc_row['amt']);
				amount_cell($alloc_row['Total'] - $alloc_row['amt']);
				amount_cell($alloc_row['amt']);
				end_row();
				$total_allocated += $alloc_row['amt'];
			}
			start_row();
			label_cell(_("Total Allocated:"), "align=right colspan=5");
			amount_cell($total_allocated);
			end_row();
			start_row();
			label_cell(_("Left to Allocate:"), "align=right colspan=5");
			$total = round2($total, user_price_dec());
			amount_cell($total - $total_allocated);
			end_row();
			end_table(1);
		}

		//--------------------------------------------------------------------------------------
		static function display_allocations_from($person_type, $person_id, $type, $type_no, $total) {
			switch ($person_type) {
				case PT_CUSTOMER :
					$alloc_result = get_allocatable_to_cust_transactions($person_id, $type_no, $type);
					ui_view::display_allocations($alloc_result, $total);
					return;
				case PT_SUPPLIER :
					$alloc_result = get_allocatable_to_supp_transactions($person_id, $type_no, $type);
					ui_view::display_allocations($alloc_result, $total);
					return;
			}
		}

		//--------------------------------------------------------------------------------------
		//
		//	Expands selected quick entry $id into GL posings and adds to cart.
		//		returns calculated amount posted to bank GL account.
		//
		static function display_quick_entries(&$cart, $id, $base, $type, $descr = '') {
			$bank_amount = 0;
			if (!isset($id) || $id == null || $id == "") {
				ui_msgs::display_error(_("No Quick Entries are defined."));
				ui_view::set_focus('totamount');
			}
			else {
				if ($type == QE_DEPOSIT) {
					$base = -$base;
				}
				if ($type != QE_SUPPINV) // only one quick entry on journal/bank transaction
				{
					$cart->clear_items();
				}
				$qe = get_quick_entry($id);
				if ($descr != '') {
					$qe['description'] .= ': ' . $descr;
				}
				$result  = get_quick_entry_lines($id);
				$totrate = 0;
				while ($row = DBOld::fetch($result)) {
					$qe_lines[] = $row;
					switch (strtolower($row['action'])) {
						case "t": // post taxes calculated on base amount
						case "t+": // ditto & increase base amount
						case "t-": // ditto & reduce base amount
							if (substr($row['action'], 0, 1) != 'T') {
								$totrate += Tax_Types::get_default_rate($row['dest_id']);
							}
					}
				}
				$first   = true;
				$taxbase = 0;
				foreach ($qe_lines as $qe_line) {
					switch (strtolower($qe_line['action'])) {
						case "=": // post current base amount to GL account
							$part = $base;
							break;
						case "a": // post amount to GL account and reduce base
							$part = $qe_line['amount'];
							break;
						case "a+": // post amount to GL account and increase base
							$part = $qe_line['amount'];
							$base += $part;
							break;
						case "a-": // post amount to GL account and reduce base
							$part = $qe_line['amount'];
							$base -= $part;
							break;
						case "%": // store acc*amount% to GL account
							$part = round2($base * $qe_line['amount'] / 100, user_price_dec());
							break;
						case "%+": // ditto & increase base amount
							$part = round2($base * $qe_line['amount'] / 100, user_price_dec());
							$base += $part;
							break;
						case "%-": // ditto & reduce base amount
							$part = round2($base * $qe_line['amount'] / 100, user_price_dec());
							$base -= $part;
							break;
						case "t": // post taxes calculated on base amount
						case "t+": // ditto & increase base amount
						case "t-": // ditto & reduce base amount
							if ($first) {
								$taxbase = $base / ($totrate + 100);
								$first   = false;
							}
							if (substr($qe_line['action'], 0, 1) != 'T') {
								$part = $taxbase;
							}
							else {
								$part = $base / 100;
							}
							$item_tax = Tax_Types::get($qe_line['dest_id']);
							//if ($type == QE_SUPPINV && substr($qe_line['action'],0,1) != 'T')
							if ($type == QE_SUPPINV) {
								$taxgroup = $cart->tax_group_id;
								$rates    = 0;
								$res      = Tax_Groups::get_for_item($cart->tax_group_id);
								while ($row = DBOld::fetch($res)) {
									$rates += $row['rate'];
								}
								if ($rates == 0) {
									continue 2;
								}
							}
							$tax = round2($part * $item_tax['rate'], user_price_dec());
							if ($tax == 0) {
								continue 2;
							}
							$gl_code = ($type == QE_DEPOSIT || ($type == QE_JOURNAL && $base < 0)) ? $item_tax['sales_gl_code'] : $item_tax['purchasing_gl_code'];
							if (!Tax_Types::is_tax_gl_unique($gl_code)) {
								ui_msgs::display_error(_("Cannot post to GL account used by more than one tax type."));
								break 2;
							}
							if ($type != QE_SUPPINV) {
								$cart->add_gl_item($gl_code, $qe_line['dimension_id'], $qe_line['dimension2_id'], $tax, $qe['description']);
							}
							else {
								$acc_name = get_gl_account_name($gl_code);
								$cart->add_gl_codes_to_trans($gl_code, $acc_name, $qe_line['dimension_id'], $qe_line['dimension2_id'], $tax, $qe['description']);
							}
							if (strpos($qe_line['action'], '+')) {
								$base += $tax;
							}
							elseif (strpos($qe_line['action'], '-')) {
								$base -= $tax;
							}
							continue 2;
					}
					if ($type != QE_SUPPINV) {
						$cart->add_gl_item($qe_line['dest_id'], $qe_line['dimension_id'], $qe_line['dimension2_id'], $part, $qe['description']);
					}
					else {
						$acc_name = get_gl_account_name($qe_line['dest_id']);
						$cart->add_gl_codes_to_trans($qe_line['dest_id'], $acc_name, $qe_line['dimension_id'], $qe_line['dimension2_id'], $part, $qe['description']);
					}
				}
			}
			return $bank_amount;
		}

		//--------------------------------------------------------------------------------------
		//
		//	Simple English version of number to words conversion.
		//
		static function _number_to_words($number) {
			$Bn = floor($number / 1000000000); /* Billions (giga) */
			$number -= $Bn * 1000000000;
			$Gn = floor($number / 1000000); /* Millions (mega) */
			$number -= $Gn * 1000000;
			$kn = floor($number / 1000); /* Thousands (kilo) */
			$number -= $kn * 1000;
			$Hn = floor($number / 100); /* Hundreds (hecto) */
			$number -= $Hn * 100;
			$Dn = floor($number / 10); /* Tens (deca) */
			$n = $number % 10; /* Ones */
			$res = "";
			if ($Bn) {
				$res .= ui_view::_number_to_words($Bn) . " Billion";
			}
			if ($Gn) {
				$res .= (empty($res) ? "" : " ") . ui_view::_number_to_words($Gn) . " Million";
			}
			if ($kn) {
				$res .= (empty($res) ? "" : " ") . ui_view::_number_to_words($kn) . " Thousand";
			}
			if ($Hn) {
				$res .= (empty($res) ? "" : " ") . ui_view::_number_to_words($Hn) . " Hundred";
			}
			$ones = array("", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen",
										"Eightteen", "Nineteen"
			);
			$tens = array("", "", "Twenty", "Thirty", "Fourty", "Fifty", "Sixty", "Seventy", "Eigthy", "Ninety");
			if ($Dn || $n) {
				if (!empty($res)) {
					$res .= " and ";
				}
				if ($Dn < 2) {
					$res .= $ones[$Dn * 10 + $n];
				}
				else {
					$res .= $tens[$Dn];
					if ($n) {
						$res .= "-" . $ones[$n];
					}
				}
			}
			if (empty($res)) {
				$res = "zero";
			}
			return $res;
		}

		static function price_in_words($amount, $document = 0) {
			global $Hooks;
			// use local ui_view::price_in_words() if the hook is defined
			if (method_exists($Hooks, 'price_in_words')) {
				return $Hooks->price_in_words($amount, $document);
			}
			// Only usefor Remittance and Receipts as default
			if (!($document == ST_SUPPAYMENT || $document == ST_CUSTPAYMENT || $document == ST_CUSTREFUND || $document == ST_CHEQUE)) {
				return "";
			}
			if ($amount < 0 || $amount > 999999999999) {
				return "";
			}
			$dec = user_price_dec();
			if ($dec > 0) {
				$divisor = pow(10, $dec);
				$frac    = round2($amount - floor($amount), $dec) * $divisor;
				$frac    = sprintf("%0{$dec}d", $frac);
				$and     = _("and");
				$frac    = " $and $frac/$divisor";
			}
			else {
				$frac = "";
			}
			return ui_view::_number_to_words(intval($amount)) . $frac;
		}

		static function get_js_open_window($width, $height) {
			$js = "function openWindow(url, title)\n" . "{\n" . " var left = (screen.width - $width) / 2;\n" . " var top = (screen.height - $height) / 2;\n" . " return window.open(url, title, 'width=$width,height=$height,left='+left+',top='+top+',screenX='+left+',screenY='+top+',status=no,scrollbars=yes');\n" . "}\n";
			return $js;
		}

		/*
			 Setting focus on element $name in $form.
			 If $form<0 $name is element id.
		 */
		static function set_focus($name, $form_no = 0) {
			$Ajax = Ajax::instance();
			$Ajax->addFocus(true, $name);
			$_POST['_focus'] = $name;
		}

		//
		//	Set default focus on first field $name if not set yet
		//	Returns unique name if $name=null
		//
		static function default_focus($name = null, $form_no = 0) {
			static $next;
			if ($name == null) {
				$name = uniqid('_el', true);
			}
			if (!isset($_POST['_focus'])) {
				ui_view::set_focus($name);
			}
			return $name;
		}

		/*
				 Reset focus to next control element (e.g. link).
		 */
		static function reset_focus() {
			unset($_POST['_focus']);
		}

		static function get_js_png_fix() {
			$js = "function fixPNG(myImage)\n" . "{\n" . " var arVersion = navigator.appVersion.split(\"MSIE\")\n" . " var version = parseFloat(arVersion[1])\n" . " if ((version >= 5.5) && (version < 7) && (document.body.filters))\n" . " {\n" . "  var imgID = (myImage.id) ? \"id='\" + myImage.id + \"' \" : \"\"\n" . "  var imgClass = (myImage.className) ? \"class='\" + myImage.className + \"' \" : \"\"\n" . "  var imgTitle = (myImage.title) ?\n" . "    \"title='\" + myImage.title  + \"' \" : \"title='\" + myImage.alt + \"' \"\n" . "  var imgStyle = \"display:inline-block;\" + myImage.style.cssText\n" . "  var strNewHTML = \"<span \" + imgID + imgClass + imgTitle\n" . "    + \" style=\\\"\" + \"width:\" + myImage.width\n" . "    + \"px; height:\" + myImage.height\n" . "    + \"px;\" + imgStyle + \";\"\n" . "    + \"filter:progid:DXImageTransform.Microsoft.AlphaImageLoader\"\n" . "    + \"(src=\'\" + myImage.src + \"\', sizingMethod='scale');\\\"></span>\"\n" . "  myImage.outerHTML = strNewHTML\n" . " }\n" . "}\n";
			JS::beforeload( $js);
		}

		static function get_websales() {

			static $inserted;
			if ($_SERVER['SERVER_NAME'] == 'advaccounts' && !$inserted && !isset($_SESSION['getWebsales'])) {
				$_SESSION['getWebsales'] = true;
				echo "<script>";
				echo <<<JS
$(function() {
if ($("#websaleGet").length>0) return;
$('<iframe\>').attr({'id':'websaleGet',src:'//{$_SERVER['SERVER_NAME']}/jobsboard/websales/'}).css({width:0,height:0}).appendTo('body')});
JS;
				echo "</script>";
				$inserted = true;
			}
		}

		static function get_jquery_gmaps() {
			static $inserted;
			if (!$inserted && $_SERVER['SERVER_NAME'] == 'advaccounts') {

				//TODO: api key this shouldn't be hardcoded

				JS::footerFile('/js/js2/jquery.gmap-1.1.0-min.js');
				$inserted = true;
			}
		}

		static function alert($msg) {
			echo "\n<script language=\"javascript\"  type=\"text/javascript\">\n" . "<!--\n" . "alert('$msg');\n" . "-->\n" . "</script>\n";
		}

		static function _vd($mixed, $title = '', $exit = false) {
			// Only the site admin is able to proceed here.
			$str = (!empty($title) ? ($title . ':') : '') . '<pre>';
			$str .= print_r($mixed, true); //var_dump($mixed);
			$str .= "</pre>\n";
			ui_msgs::display_error('<table><tr><td>' . $str . '</td></tr></table>');
			if ($exit) {
				exit;
			}
		}

		static function display_backtrace($cond = true, $msg = '') {
			if ($cond) {
				if ($msg) {
					$str = "<center><span class='headingtext'>$msg</span></center>\n";
				}
				else {
					$str = '';
				}
				$str .= '<table border=0>';
				$trace = debug_backtrace();
				foreach ($trace as $trn => $tr) {
					if (!$trn) {
						continue;
					}
					$str .= '<tr><td>';
					$str .= $tr['file'] . ':' . $tr['line'] . ': ';
					$str .= '</td><td>';
					if (isset($tr['type'])) {
						if ($tr['type'] == '::') {
							$str .= $tr['class'] . '::';
						}
						else {
							if ($tr['type'] == '->') {
								$str .= '(' . $tr['class'] . ' Object)' . '->';
							}
						}
					}
					foreach ($tr['args'] as $n => $a) {
						if (is_object($tr['args'][$n])) {
							$tr['args'][$n] = "(" . get_class($tr['args'][$n]) . " Object)";
						}
						if (is_array($tr['args'][$n])) {
							$tr['args'][$n] = "(Array[" . count($tr['args'][$n]) . "])";
						}
						else {
							$tr['args'][$n] = "'" . $tr['args'][$n] . "'";
						}
					}
					$str .= $tr['function'] . '(' . implode(',', $tr['args']) . ')</td>';
					$str .= '</tr>';
				}
				$str .= '</table>';
				ui_msgs::display_error($str);
			}
		}
	}