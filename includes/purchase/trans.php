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
	/* Definition of the Supplier Transactions class to hold all the information for an accounts payable invoice or credit note
 */
	class Purchase_Trans
	{
		protected static $_instance = null;

		public static function instance($reset_session = false)
		{
			if (!$reset_session && isset($_SESSION["Purchase_Trans"])) {
				static::$_instance = $_SESSION["Purchase_Trans"];
			} elseif (static::$_instance === null) {
				static::$_instance = $_SESSION["Purchase_Trans"] = new static;
			}
			return static::$_instance;
		}

		public static function killInstance()
		{
			unset($_SESSION["Purchase_Trans"]);
		}

		public $grn_items; /*array of objects of class GRNDetails using the GRN No as the pointer */
		public $gl_codes; /*array of objects of class gl_codes using a counter as the pointer */
		public $supplier_id;
		public $supplier_name;
		public $terms_description;
		public $terms;
		public $tax_description;
		public $tax_group_id;
		public $is_invoice;
		public $Comments;
		public $tran_date;
		public $due_date;
		public $supp_reference;
		public $reference;
		public $ov_amount;
		public $ov_discount;
		public $tax_correction = 0;
		public $total_correction = 0;
		public $gl_codes_counter = 0;

		function __construct()
		{
			/*Constructor function initialises a new Supplier Transaction object */
			$this->grn_items = array();
			$this->gl_codes  = array();
		}

		function add_grn_to_trans($grn_item_id, $po_detail_item, $item_code, $description, $qty_recd, $prev_quantity_inv, $this_quantity_inv, $order_price, $chg_price, $Complete, $std_cost_unit, $gl_code, $discount = 0, $exp_price = null)
		{
			$this->grn_items[$grn_item_id] = new Purchase_GLItem($grn_item_id, $po_detail_item, $item_code, $description, $qty_recd, $prev_quantity_inv, $this_quantity_inv, $order_price, $chg_price, $Complete, $std_cost_unit, $gl_code, $discount, $exp_price);
			return 1;
		}

		function add_gl_codes_to_trans($gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_)
		{
			$this->gl_codes[$this->gl_codes_counter] = new Purchase_GLCode($this->gl_codes_counter, $gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_);
			$this->gl_codes_counter++;
			return 1;
		}

		function remove_grn_from_trans($grn_item_id)
		{
			unset($this->grn_items[$grn_item_id]);
		}

		function remove_gl_codes_from_trans(&$gl_code_counter)
		{
			unset($this->gl_codes[$gl_code_counter]);
		}

		function is_valid_trans_to_post()
		{
			return (count($this->grn_items) > 0 || count($this->gl_codes) > 0 || ($this->ov_amount != 0) || ($this->ov_discount > 0));
		}

		function clear_items()
		{
			unset($this->grn_items);
			unset($this->gl_codes);
			$this->ov_amount = $this->ov_discount = $this->supplier_id = $this->tax_correction = $this->total_correction = 0;
			$this->grn_items = array();
			$this->gl_codes  = array();
		}

		function get_taxes($tax_group_id = null, $shipping_cost = 0, $gl_codes = true)
		{
			$items  = array();
			$prices = array();
			if ($tax_group_id == null) {
				$tax_group_id = $this->tax_group_id;
			}
			$tax_group = Tax_Groups::get_tax_group_items_as_array($tax_group_id);
			foreach ($this->grn_items as $ln_itm) {
				$items[]  = $ln_itm->item_code;
				$prices[] = round(($ln_itm->this_quantity_inv * $ln_itm->taxfree_charge_price($tax_group_id, $tax_group)), user_price_dec(), PHP_ROUND_HALF_EVEN);
			}
			if ($tax_group_id == null) {
				$tax_group_id = $this->tax_group_id;
			}
			$taxes = Taxes::get_tax_for_items($items, $prices, $shipping_cost, $tax_group_id);
			///////////////// Joe Hunt 2009.08.18
			if ($gl_codes) {
				foreach ($this->gl_codes as $gl_code) {
					$index = Taxes::is_tax_account($gl_code->gl_code);
					if ($index !== false) {
						$taxes[$index]['Value'] += $gl_code->amount;
					}
				}
			}
			////////////////
			return $taxes;
		}

		function get_total_charged($tax_group_id = null)
		{
			$total = 0;
			// preload the taxgroup !
			if ($tax_group_id != null) {
				$tax_group = Tax_Groups::get_tax_group_items_as_array($tax_group_id);
			} else {
				$tax_group = null;
			}
			foreach ($this->grn_items as $ln_itm) {
				$total += round(($ln_itm->this_quantity_inv * $ln_itm->taxfree_charge_price($tax_group_id, $tax_group)), user_price_dec(), PHP_ROUND_HALF_EVEN);
			}
			foreach ($this->gl_codes as $gl_line) { //////// 2009-08-18 Joe Hunt
				if (!Taxes::is_tax_account($gl_line->gl_code)) {
					$total += $gl_line->amount;
				}
			}
			return $total;
		}
	} /* end of class defintion */
?>
