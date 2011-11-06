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
	class ui_view
	{
		//--------------------------------------------------------------------------------------
		static function get_supplier_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '')
		{
			$viewer = "purchases/view/";
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
		static function get_gl_view_str($type, $trans_no, $label = "", $force = false, $class = '', $id = '')
		{
			if (!$force && !User::show_gl_info()) {
				return "";
			}
			$icon = false;
			if ($label == "") {
				$label = _("GL");
				$icon = ICON_GL;
			}
			return viewer_link($label, "gl/view/gl_trans_view.php?type_id=$type&trans_no=$trans_no", $class, $id, $icon);
		}

		//--------------------------------------------------------------------------------------
		static function get_gl_view_str_cell($type, $trans_no, $label = "")
		{
			$str = ui_view::get_gl_view_str($type, $trans_no, $label);
			if ($str != "") {
				return "<td>$str</td>";
			}
		}

		//--------------------------------------------------------------------------------------
		static function get_customer_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '')
		{
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
			$lbl = $label;
			$preview_str = '';
			foreach (
				$trans_no as $trans
			) {
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
		static function get_banking_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '')
		{
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
		static function get_inventory_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '')
		{
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
		static function get_manufacturing_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '')
		{
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
		static function get_dimensions_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '')
		{
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
		static function get_journal_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '')
		{
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
		static function get_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '')
		{
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
		static function stock_status($stock_id, $description = null, $echo = true)
		{
			if ($description) //hyperlink_params_separate( "/inventory/inquiry/stock_status.php", (User::show_codes()?$stock_id . " - ":"") . $description, "stock_id=$stock_id");
			{
				$preview_str = "<a target='_blank' href='/inventory/inquiry/stock_status.php?stock_id=$stock_id' onclick=\"javascript:openWindow(this.href,this.target); return false;\" >" . (User::show_codes()
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

		static function stock_status_cell($stock_id, $description = null)
		{
			echo "<td>";
			ui_view::stock_status($stock_id, $description);
			echo "</td>";
		}

		//--------------------------------------------------------------------------------------
		//
		//	Simple English version of number to words conversion.
		//
		protected static function number_to_words($number)
		{
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
				$res .= static::number_to_words($Bn) . " Billion";
			}
			if ($Gn) {
				$res .= (empty($res) ? "" : " ") . static::number_to_words($Gn) . " Million";
			}
			if ($kn) {
				$res .= (empty($res) ? "" : " ") . static::number_to_words($kn) . " Thousand";
			}
			if ($Hn) {
				$res .= (empty($res) ? "" : " ") . static::number_to_words($Hn) . " Hundred";
			}
			$ones = array(
				"", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen",
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

		static function price_in_words($amount, $document = 0)
		{
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
			$dec = User::price_dec();
			if ($dec > 0) {
				$divisor = pow(10, $dec);
				$frac = Num::round($amount - floor($amount), $dec) * $divisor;
				$frac = sprintf("%0{$dec}d", $frac);
				$and = _("and");
				$frac = " $and $frac/$divisor";
			}
			else {
				$frac = "";
			}
			return static::number_to_words(intval($amount)) . $frac;
		}
	}