<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 6/11/11
	 * Time: 1:37 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Display
	{
		static function heading($msg)
		{
			echo "<center><span class='headingtext'>$msg</span></center>\n";
		}

		static function note($msg, $br = 0, $br2 = 0, $extra = "")
		{
			for ($i = 0; $i < $br; $i++)
			{
				echo "<br>";
			}
			if ($extra != "") {
				echo "<center><span $extra>$msg</span></center>\n";
			}
			else
			{
				echo "<center><span class='note_msg'>$msg</span></center>\n";
			}
			for ($i = 0; $i < $br2; $i++)
			{
				echo "<br>";
			}
		}

		static function item_heading($stock_id)
		{
			if ($stock_id != "") {
				$result = DBOld::query("SELECT description, units FROM stock_master WHERE stock_id=" . DB::escape($stock_id));
				$myrow = DBOld::fetch_row($result);
				static::heading("$stock_id - $myrow[0]");
				$units = $myrow[1];
				static::heading(_("in units of : ") . $units);
			}
		}

		static function backtrace($cond = true, $msg = '')
		{
			if ($cond) {
				if ($msg) {
					$str = "<center><span class='headingtext'>$msg</span></center>\n";
				}
				else {
					$str = '';
				}
				$str .= '<table border=0>';
				$trace = debug_backtrace();
				foreach (
					$trace as $trn => $tr
				) {
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
					foreach (
						$tr['args'] as $n => $a
					) {
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
				Errors::error($str);
			}
		}

		//--------------------------------------------------------------------------------------
		// Displays currency exchange rate for given date.
		// When there is no exrate for today,
		// gets it form ECB and stores in local database.
		//
		static function exchange_rate($from_currency, $to_currency, $date_, $edit_rate = false)
		{
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
				$rate = Num::format($rate, user_exrate_dec());
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
		static function is_voided($type, $id, $label)
		{
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
		static function comments_row($type, $id)
		{
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
		static function debit_or_credit_cells($value)
		{
			$value = Num::round($value, user_price_dec());
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
		static function customer_trans_tax_details($tax_items, $columns)
		{
			while ($tax_item = DBOld::fetch($tax_items)) {
				$tax = Num::format($tax_item['amount'], user_price_dec());
				if ($tax_item['included_in_price']) {
					label_row(_("Included") . " " . $tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%) " . _("Amount") . ": $tax", "", "colspan=$columns align=right", "align=right");
				}
				else {
					label_row($tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%)", $tax, "colspan=$columns align=right", "align=right");
				}
			}
		}

		//--------------------------------------------------------------------------------------
		static function supp_trans_tax_details($tax_items, $columns, $tax_recorded = 0)
		{
			$tax_total = 0;
			while ($tax_item = DBOld::fetch($tax_items)) {
				$tax = Num::format(abs($tax_item['amount']), user_price_dec());
				if ($tax_item['included_in_price']) {
					label_row(_("Included") . " " . $tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%) " . _("Amount") . ": $tax", "colspan=$columns align=right", "align=right");
				}
				else {
					label_row($tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%)", $tax, "colspan=$columns align=right", "align=right");
				}
				$tax_total += $tax;
			}
			if ($tax_recorded != 0) {
				$tax_correction = Num::format($tax_recorded - $tax_total, user_price_dec());
				label_row("Tax Correction" . " ", $tax_correction, "colspan=$columns align=right", "align=right");
			}
		}

		//--------------------------------------------------------------------------------------
		static function edit_tax_items($taxes, $columns, $tax_included, $leftspan = 0, $tax_correcting = false)
		{
			$total = 0;
			foreach (
				$taxes as $taxitem
			) {
				if ($tax_included) {
					label_row(_("Included") . " " . $taxitem['tax_type_name'] . " (" . $taxitem['rate'] . "%) " . _("Amount:") . " ", Num::format($taxitem['Value'], user_price_dec()), "colspan=$columns align=right", "align=right", $leftspan);
				}
				else {
					$total += Num::round($taxitem['Value'], user_price_dec());
					label_row($taxitem['tax_type_name'] . " (" . $taxitem['rate'] . "%)", Num::format($taxitem['Value'], user_price_dec()), "colspan=$columns align=right", "align=right", $leftspan);
				}
			}
			if ($tax_correcting) {
				label_cell(_("Tax Correction"), "colspan=$columns align=right width='90%'");
				small_amount_cells(null, 'ChgTax', Num::price_format(get_post('ChgTax'), 2));
				end_row();
				$total += get_post('ChgTax');
			}
			return $total;
		}

		//--------------------------------------------------------------------------------------
		static function display_allocations($alloc_result, $total)
		{
			global $systypes_array;
			if (!$alloc_result || DBOld::num_rows($alloc_result) == 0) {
				return;
			}
			Display::heading(_("Allocations"));
			start_table(Config::get('tables_style') . "  width=90%");
			$th = array(_("Type"), _("Number"), _("Date"), _("Total Amount"), _("Left to Allocate"), _("This Allocation"));
			table_header($th);
			$k = $total_allocated = 0;
			while ($alloc_row = DBOld::fetch($alloc_result)) {
				alt_table_row_color($k);
				label_cell($systypes_array[$alloc_row['type']]);
				label_cell(ui_view::get_trans_view_str($alloc_row['type'], $alloc_row['trans_no']));
				label_cell(Dates::sql2date($alloc_row['tran_date']));
				$alloc_row['Total'] = Num::round($alloc_row['Total'], user_price_dec());
				$alloc_row['amt'] = Num::round($alloc_row['amt'], user_price_dec());
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
			$total = Num::round($total, user_price_dec());
			amount_cell($total - $total_allocated);
			end_row();
			end_table(1);
		}

		//--------------------------------------------------------------------------------------
		static function display_allocations_from($person_type, $person_id, $type, $type_no, $total)
		{
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
		static function display_quick_entries(&$cart, $id, $base, $type, $descr = '')
		{
			$bank_amount = 0;
			if (!isset($id) || $id == null || $id == "") {
				Errors::error(_("No Quick Entries are defined."));
				JS::set_focus('totamount');
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
				$result = get_quick_entry_lines($id);
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
				$first = true;
				$taxbase = 0;
				foreach (
					$qe_lines as $qe_line
				) {
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
						$part = Num::round($base * $qe_line['amount'] / 100, user_price_dec());
						break;
					case "%+": // ditto & increase base amount
						$part = Num::round($base * $qe_line['amount'] / 100, user_price_dec());
						$base += $part;
						break;
					case "%-": // ditto & reduce base amount
						$part = Num::round($base * $qe_line['amount'] / 100, user_price_dec());
						$base -= $part;
						break;
					case "t": // post taxes calculated on base amount
					case "t+": // ditto & increase base amount
					case "t-": // ditto & reduce base amount
						if ($first) {
							$taxbase = $base / ($totrate + 100);
							$first = false;
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
							$rates = 0;
							$res = Tax_Groups::get_for_item($cart->tax_group_id);
							while ($row = DBOld::fetch($res)) {
								$rates += $row['rate'];
							}
							if ($rates == 0) {
								continue 2;
							}
						}
						$tax = Num::round($part * $item_tax['rate'], user_price_dec());
						if ($tax == 0) {
							continue 2;
						}
						$gl_code = ($type == QE_DEPOSIT || ($type == QE_JOURNAL && $base < 0)) ? $item_tax['sales_gl_code'] : $item_tax['purchasing_gl_code'];
						if (!Tax_Types::is_tax_gl_unique($gl_code)) {
							Errors::error(_("Cannot post to GL account used by more than one tax type."));
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
	}
