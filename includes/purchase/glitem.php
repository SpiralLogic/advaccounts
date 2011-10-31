<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 1/11/11
 * Time: 7:05 AM
 * To change this template use File | Settings | File Templates.
 */ 
class Purchase_GLItem {

		/* Contains relavent information from the purch_order_details as well to provide in cached form,
			all the info to do the necessary entries without looking up ie additional queries of the database again */

		var $id;
		var $po_detail_item;
		var $item_code;
		var $description;
		var $qty_recd;
		var $prev_quantity_inv;
		var $this_quantity_inv;
		var $order_price;
		var $chg_price;
		var $exp_price;
		var $discount;
		var $Complete;
		var $std_cost_unit;
		var $gl_code;
		var $freight;


		function __construct($id, $po_detail_item, $item_code, $description, $qty_recd, $prev_quantity_inv, $this_quantity_inv, $order_price, $chg_price, $Complete, $std_cost_unit, $gl_code, $discount = 0, $exp_price = null) {

			$this->id = $id;
			$this->po_detail_item = $po_detail_item;
			$this->item_code = $item_code;
			$this->description = $description;
			$this->qty_recd = $qty_recd;
			$this->prev_quantity_inv = $prev_quantity_inv;
			$this->this_quantity_inv = $this_quantity_inv;
			$this->order_price = $order_price;
			$this->chg_price = $chg_price;
			$this->exp_price = ($exp_price == null) ? $chg_price : $exp_price;
			$this->discount = $discount;
			$this->Complete = $Complete;
			$this->std_cost_unit = $std_cost_unit;
			$this->gl_code = $gl_code;
		}

		function setFreight($freight) {
			$this->freight = $freight;
		}

		function full_charge_price($tax_group_id, $tax_group = null) {
			return Taxes::get_full_price_for_item($this->item_code, $this->chg_price * (1 - $this->discount), $tax_group_id, 0, $tax_group);
		}

		function taxfree_charge_price($tax_group_id, $tax_group = null) {
			//		if ($tax_group_id==null)
			//			return $this->chg_price;
			return Taxes::get_tax_free_price_for_item($this->item_code, $this->chg_price * (1 - $this->discount / 100), $tax_group_id, 0, $tax_group);
		}
	}

