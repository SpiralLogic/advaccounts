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
	//-------------------------------------------------------------------------------------------------------------

	function add_supp_invoice_item($supp_trans_type, $supp_trans_no, $stock_id, $description,
																 $gl_code, $unit_price, $unit_tax, $quantity, $grn_item_id, $po_detail_item_id, $memo_,
																 $err_msg = "", $discount, $exp_price = -1) {
		$unit_price = $unit_price / (1 - $discount / 100);

		$sql = "INSERT INTO supp_invoice_items (supp_trans_type, supp_trans_no, stock_id, description, gl_code, unit_price, unit_tax, quantity,
	  	grn_item_id, po_detail_item_id, memo_, discount, exp_price) ";
		$sql .= "VALUES (" . DB::escape($supp_trans_type) . ", " . DB::escape($supp_trans_no) . ", "
		 . DB::escape($stock_id) .
		 ", " . DB::escape($description) . ", " . DB::escape($gl_code) . ", " . DB::escape($unit_price)
		 . ", " . DB::escape($unit_tax) . ", " . DB::escape($quantity) . ",
		" . DB::escape($grn_item_id) . ", " . DB::escape($po_detail_item_id) . ", " . DB::escape($memo_) . ", " . DB::escape($discount) . "," . DB::escape($exp_price) . ")";

		if ($err_msg == "")
			$err_msg = "Cannot insert a supplier transaction detail record";

		DB::query($sql, $err_msg);

		return DB::insert_id();
	}

	//-------------------------------------------------------------------------------------------------------------

	function add_supp_invoice_gl_item($supp_trans_type, $supp_trans_no, $gl_code, $amount, $memo_, $err_msg = "") {
		return add_supp_invoice_item($supp_trans_type, $supp_trans_no, "", "", $gl_code, $amount,
			0, 0, /*$grn_item_id*/
			0, /*$po_detail_item_id*/
			0, $memo_, $err_msg);
	}

	//----------------------------------------------------------------------------------------

	function get_supp_invoice_items($supp_trans_type, $supp_trans_no) {
		$sql = "SELECT *, unit_price AS FullUnitPrice FROM supp_invoice_items
		WHERE supp_trans_type = " . DB::escape($supp_trans_type) . "
		AND supp_trans_no = " . DB::escape($supp_trans_no) . " ORDER BY id";
		return DB::query($sql, "Cannot retreive supplier transaction detail records");
	}

	//----------------------------------------------------------------------------------------

	function void_supp_invoice_items($type, $type_no) {
		$sql = "UPDATE supp_invoice_items SET quantity=0, unit_price=0
		WHERE supp_trans_type = " . DB::escape($type) . " AND supp_trans_no=" . DB::escape($type_no);
		DB::query($sql, "could not void supptrans details");
	}

?>