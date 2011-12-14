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

	class Sales_Allocation {
	public static function add($amount, $trans_type_from, $trans_no_from,
															 $trans_type_to, $trans_no_to) {
		$sql
		 = "INSERT INTO cust_allocations (
		amt, date_alloc,
		trans_type_from, trans_no_from, trans_no_to, trans_type_to)
		VALUES ($amount, Now() ," . DB::escape($trans_type_from) . ", " . DB::escape($trans_no_from) . ", " . DB::escape($trans_no_to)
		 . ", " . DB::escape($trans_type_to) . ")";
		DB::query($sql, "A customer allocation could not be added to the database");
	}


	public static function delete($trans_id) {
		$sql = "DELETE FROM cust_allocations WHERE id = " . DB::escape($trans_id);
		return DB::query($sql, "The existing allocation $trans_id could not be deleted");
	}


	public static function get_balance($trans_type, $trans_no) {
		$sql
		 = "SELECT (ov_amount+ov_gst+ov_freight+ov_freight_tax-ov_discount-alloc) AS BalToAllocate
		FROM debtor_trans WHERE trans_no=" . DB::escape($trans_no) . " AND type=" . DB::escape($trans_type);
		$result = DB::query($sql, "calculate the allocation");
		$myrow = DB::fetch_row($result);
		return $myrow[0];
	}


	public static function update($trans_type, $trans_no, $alloc) {
		$sql
		 = "UPDATE debtor_trans SET alloc = alloc + $alloc
		WHERE type=" . DB::escape($trans_type) . " AND trans_no = " . DB::escape($trans_no);
		DB::query($sql, "The debtor transaction record could not be modified for the allocation against it");
	}


	public static function void($type, $type_no, $date = "") {

		// clear any allocations for this transaction
		$sql
		 = "SELECT * FROM cust_allocations
		WHERE (trans_type_from=" . DB::escape($type) . " AND trans_no_from=" . DB::escape($type_no) . ")
		OR (trans_type_to=" . DB::escape($type) . " AND trans_no_to=" . DB::escape($type_no) . ")";
		$result = DB::query($sql, "could not void debtor transactions for type=$type and trans_no=$type_no");
		while ($row = DB::fetch($result))
		{
			$sql = "UPDATE debtor_trans SET alloc=alloc - " . $row['amt'] . "
			WHERE (type= " . $row['trans_type_from'] . " AND trans_no=" . $row['trans_no_from'] . ")
			OR (type=" . $row['trans_type_to'] . " AND trans_no=" . $row['trans_no_to'] . ")";
			DB::query($sql, "could not clear allocation");
			// 2008-09-20 Joe Hunt
			if ($date != "") {
				Bank::exchange_variation($type, $type_no, $row['trans_type_to'], $row['trans_no_to'], $date,
					$row['amt'], PT_CUSTOMER, true);
			}
			//////////////////////
		}
		// remove any allocations for this transaction
		$sql
		 = "DELETE FROM cust_allocations
		WHERE (trans_type_from=" . DB::escape($type) . " AND trans_no_from=" . DB::escape($type_no) . ")
		OR (trans_type_to=" . DB::escape($type) . " AND trans_no_to=" . DB::escape($type_no) . ")";
		DB::query($sql, "could not void debtor transactions for type=$type and trans_no=$type_no");
	}


	public static function get_sql($extra_fields = null, $extra_conditions = null, $extra_tables = null) {
		$sql
		 = "SELECT
		trans.type,
		trans.trans_no,
		trans.reference,
		trans.tran_date,
		debtor.name AS DebtorName, 
		debtor.curr_code, 
		ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount AS Total,
		trans.alloc,
		trans.due_date,
		debtor.address,
		trans.version ";
		if ($extra_fields) {
			$sql .= ", $extra_fields ";
		}
		$sql .= " FROM debtor_trans as trans, "
		 . "debtors_master as debtor";
		if ($extra_tables) {
			$sql .= ",$extra_tables ";
		}
		$sql .= " WHERE trans.debtor_no=debtor.debtor_no";
		if ($extra_conditions) {
			$sql .= " AND $extra_conditions ";
		}
		return $sql;
	}


	public static function get_allocatable_sql($customer_id, $settled) {
		$settled_sql = "";
		if (!$settled) {
			$settled_sql = " AND (round(ov_amount+ov_gst+ov_freight+ov_freight_tax-ov_discount-alloc,6) > 0)";
		}
		$cust_sql = "";
		if ($customer_id != null) {
			$cust_sql = " AND trans.debtor_no = " . DB::quote($customer_id);
		}
		$sql = Sales_Allocation::get_sql("round(ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount-alloc,6) <= 0 AS settled",
		 "(type=" . ST_CUSTPAYMENT . " OR type=" . ST_CUSTREFUND . " OR type=" . ST_CUSTCREDIT . " OR type=" . ST_BANKDEPOSIT . ") AND (trans.ov_amount > 0) " . $settled_sql . $cust_sql);
		return $sql;
	}


	public static function get_to_trans($customer_id, $trans_no = null, $type = null) {
		if ($trans_no != null and $type != null) {
			$sql = Sales_Allocation::get_sql("amt", "trans.trans_no = alloc.trans_no_to
			AND trans.type = alloc.trans_type_to
			AND alloc.trans_no_from=$trans_no
			AND alloc.trans_type_from=$type
			AND trans.debtor_no=" . DB::escape($customer_id),
				"cust_allocations as alloc");
		} else {
			$sql = Sales_Allocation::get_sql(null, "round(ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount-alloc,6) > 0
			AND trans.type <> " . ST_CUSTPAYMENT . "
			AND trans.type <> " . ST_CUSTREFUND . "
			AND trans.type <> " . ST_BANKDEPOSIT . "
			AND trans.type <> " . ST_CUSTCREDIT . "
			AND trans.type <> " . ST_CUSTDELIVERY . "
			AND trans.debtor_no=" . DB::escape($customer_id));
		}
		return DB::query($sql . " ORDER BY trans_no", "Cannot retreive alloc to transactions");
	}

	}