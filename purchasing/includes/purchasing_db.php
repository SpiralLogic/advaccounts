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

	include_once(APP_PATH . "purchasing/includes/db/supp_trans_db.php");
	include_once(APP_PATH . "purchasing/includes/db/po_db.php");
	include_once(APP_PATH . "purchasing/includes/db/grn_db.php");
	include_once(APP_PATH . "purchasing/includes/db/invoice_db.php");
	include_once(APP_PATH . "purchasing/includes/db/suppalloc_db.php");
	include_once(APP_PATH . "purchasing/includes/db/supp_payment_db.php");
	include_once(APP_PATH . "purchasing/includes/db/suppliers_db.php");

	//-------------------------------------------------------------------------------------------------------------

	// add a supplier-related gl transaction
	// $date_ is display date (non-sql)
	// $amount is in SUPPLIERS'S currency

	function add_gl_trans_supplier($type, $type_no, $date_, $account, $dimension, $dimension2,
																 $amount, $supplier_id, $err_msg = "", $rate = 0, $memo = "") {
		if ($err_msg == "")
			$err_msg = "The supplier GL transaction could not be inserted";

		return add_gl_trans($type, $type_no, $date_, $account, $dimension, $dimension2, $memo,
			$amount, Banking::get_supplier_currency($supplier_id),
			PT_SUPPLIER, $supplier_id, $err_msg, $rate);
	}

	//----------------------------------------------------------------------------------------

	function get_purchase_price($supplier_id, $stock_id) {
		$sql = "SELECT price, conversion_factor FROM purch_data
		WHERE supplier_id = " . DBOld::escape($supplier_id) . "
		AND stock_id = " . DBOld::escape($stock_id);
		$result = DBOld::query($sql, "The supplier pricing details for " . $stock_id . " could not be retrieved");

		if (DBOld::num_rows($result) == 1) {
			$myrow = DBOld::fetch($result);
			return $myrow["price"] / $myrow['conversion_factor'];
		} else {
			return 0;
		}
	}

	function get_purchase_conversion_factor($supplier_id, $stock_id) {
		$sql = "SELECT conversion_factor FROM purch_data
		WHERE supplier_id = " . DBOld::escape($supplier_id) . "
		AND stock_id = " . DBOld::escape($stock_id);
		$result = DBOld::query($sql, "The supplier pricing details for " . $stock_id . " could not be retrieved");

		if (DBOld::num_rows($result) == 1) {
			$myrow = DBOld::fetch($result);
			return $myrow['conversion_factor'];
		} else {
			return 1;
		}
	}

	//----------------------------------------------------------------------------------------

	function get_purchase_data($supplier_id, $stock_id) {
		$sql = "SELECT * FROM purch_data
		WHERE supplier_id = " . DBOld::escape($supplier_id) . "
		AND stock_id = " . DBOld::escape($stock_id);
		$result = DBOld::query($sql, "The supplier pricing details for " . $stock_id . " could not be retrieved");

		return DBOld::fetch($result);
	}

	function add_or_update_purchase_data($supplier_id, $stock_id, $price, $supplier_code = "", $uom = "") {
		$data = get_purchase_data($supplier_id, $stock_id);
		if ($data === false) {
			$supplier_code = $stock_id;
			$sql = "INSERT INTO purch_data (supplier_id, stock_id, price, suppliers_uom,
			conversion_factor, supplier_description) VALUES (" . DBOld::escape($supplier_id)
			 . ", " . DBOld::escape($stock_id) . ", " . DBOld::escape($price) . ", "
			 . DBOld::escape($uom) . ", 1, " . DBOld::escape($supplier_code) . ")";
			DBOld::query($sql, "The supplier purchasing details could not be added");
			return;
		}
		$price = round($price * $data['conversion_factor'], user_price_dec());
		$sql = "UPDATE purch_data SET price=" . DBOld::escape($price);
		if ($uom != "")
			$sql .= ",suppliers_uom=" . DBOld::escape($uom);
		if ($supplier_code != "")
			$sql .= ",supplier_description=" . DBOld::escape($supplier_code);
		$sql .= " WHERE stock_id=" . DBOld::escape($stock_id) . " AND supplier_id=" . DBOld::escape($supplier_id);
		DBOld::query($sql, "The supplier purchasing details could not be updated");
		return true;
	}

?>