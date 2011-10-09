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

	function add_supp_trans($type, $supplier_id, $date_, $due_date, $reference, $supp_reference,
													$amount, $amount_tax, $discount, $err_msg = "", $rate = 0) {
		$date = Dates::date2sql($date_);
		if ($due_date == "")
			$due_date = "0000-00-00";
		else
			$due_date = Dates::date2sql($due_date);

		$trans_no = SysTypes::get_next_trans_no($type);

		$curr = Banking::get_supplier_currency($supplier_id);

		if ($rate == 0)
			$rate = Banking::get_exchange_rate_from_home_currency($curr, $date_);

		$sql = "INSERT INTO supp_trans (trans_no, type, supplier_id, tran_date, due_date,
		reference, supp_reference, ov_amount, ov_gst, rate, ov_discount) ";
		$sql .= "VALUES (" . db_escape($trans_no) . ", " . db_escape($type)
		 . ", " . db_escape($supplier_id) . ", '$date', '$due_date',
		" . db_escape($reference) . ", " . db_escape($supp_reference) . ", " . db_escape($amount)
		 . ", " . db_escape($amount_tax) . ", " . db_escape($rate) . ", " . db_escape($discount) . ")";

		if ($err_msg == "")
			$err_msg = "Cannot insert a supplier transaction record";

		db_query($sql, $err_msg);
		add_audit_trail($type, $trans_no, $date_);

		return $trans_no;
	}

	//-------------------------------------------------------------------------------------------------------------

	function get_supp_trans($trans_no, $trans_type = -1) {
		$sql = "SELECT supp_trans.*, (supp_trans.ov_amount+supp_trans.ov_gst+supp_trans.ov_discount) AS Total,
		suppliers.supp_name AS supplier_name, suppliers.curr_code AS SupplierCurrCode ";

		if ($trans_type == ST_SUPPAYMENT) {
			// it's a payment so also get the bank account
			$sql .= ", bank_accounts.bank_name, bank_accounts.bank_account_name, bank_accounts.bank_curr_code,
			bank_accounts.account_type AS BankTransType, bank_trans.amount AS BankAmount,
			bank_trans.ref ";
		}

		$sql .= " FROM supp_trans, suppliers ";

		if ($trans_type == ST_SUPPAYMENT) {
			// it's a payment so also get the bank account
			$sql .= ", bank_trans, bank_accounts";
		}

		$sql .= " WHERE supp_trans.trans_no=" . db_escape($trans_no) . "
		AND supp_trans.supplier_id=suppliers.supplier_id";

		if ($trans_type > 0)
			$sql .= " AND supp_trans.type=" . db_escape($trans_type);

		if ($trans_type == ST_SUPPAYMENT) {
			// it's a payment so also get the bank account
			$sql .= " AND bank_trans.trans_no =" . db_escape($trans_no) . "
			AND bank_trans.type=" . db_escape($trans_type) . "
			AND bank_accounts.id=bank_trans.bank_act ";
		}

		$result = db_query($sql, "Cannot retreive a supplier transaction");

		if (db_num_rows($result) == 0) {
			// can't return nothing
			Errors::show_db_error("no supplier trans found for given params", $sql, true);
			exit;
		}

		if (db_num_rows($result) > 1) {
			// can't return multiple
			Errors::show_db_error("duplicate supplier transactions found for given params", $sql, true);
			exit;
		}

		return db_fetch($result);
	}

	//----------------------------------------------------------------------------------------

	function exists_supp_trans($type, $type_no) {
		if ($type == ST_SUPPRECEIVE)
			return exists_grn($type_no);

		$sql = "SELECT trans_no FROM supp_trans WHERE type=" . db_escape($type) . "
		AND trans_no=" . db_escape($type_no);
		$result = db_query($sql, "Cannot retreive a supplier transaction");

		return (db_num_rows($result) > 0);
	}

	//----------------------------------------------------------------------------------------

	function void_supp_trans($type, $type_no) {
		$sql = "UPDATE supp_trans SET ov_amount=0, ov_discount=0, ov_gst=0,
		alloc=0 WHERE type=" . db_escape($type) . " AND trans_no=" . db_escape($type_no);

		db_query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");
	}

	//----------------------------------------------------------------------------------------

	function post_void_supp_trans($type, $type_no) {
		if ($type == ST_SUPPAYMENT) {
			void_supp_payment($type, $type_no);
			return true;
		}

		if ($type == ST_SUPPINVOICE || $type == ST_SUPPCREDIT) {
			void_supp_invoice($type, $type_no);
			return true;
		}

		if ($type == SUPPRECEIVE) {
			return void_grn($type_no);
		}

		return false;
	}

	//----------------------------------------------------------------------------------------

?>