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
	//----------------------------------------------------------------------------------------

	function add_supp_allocation($amount, $trans_type_from, $trans_no_from,
															 $trans_type_to, $trans_no_to, $date_) {
		$date = Dates::date2sql($date_);
		$sql = "INSERT INTO supp_allocations (
		amt, date_alloc,
		trans_type_from, trans_no_from, trans_no_to, trans_type_to)
		VALUES (" . DB::escape($amount) . ", '$date', "
		 . DB::escape($trans_type_from) . ", " . DB::escape($trans_no_from) . ", "
		 . DB::escape($trans_no_to) . ", " . DB::escape($trans_type_to) . ")";

		DBOld::query($sql, "A supplier allocation could not be added to the database");
	}

	//----------------------------------------------------------------------------------------

	function delete_supp_allocation($trans_id) {
		$sql = "DELETE FROM supp_allocations WHERE id = " . DB::escape($trans_id);
		DBOld::query($sql, "The existing allocation $trans_id could not be deleted");
	}

	//----------------------------------------------------------------------------------------

	function get_supp_trans_allocation_balance($trans_type, $trans_no) {
		$sql = "SELECT (ov_amount+ov_gst-ov_discount-alloc) AS BalToAllocate
		FROM supp_trans WHERE trans_no="
		 . DB::escape($trans_no) . " AND type=" . DB::escape($trans_type);
		$result = DBOld::query($sql, "calculate the allocation");
		$myrow = DBOld::fetch_row($result);

		return $myrow[0];
	}

	//----------------------------------------------------------------------------------------

	function update_supp_trans_allocation($trans_type, $trans_no, $alloc) {
		$sql = "UPDATE supp_trans SET alloc = alloc + " . DB::escape($alloc) . "
		WHERE type=" . DB::escape($trans_type) . " AND trans_no = " . DB::escape($trans_no);
		DBOld::query($sql, "The supp transaction record could not be modified for the allocation against it");
	}

	//-------------------------------------------------------------------------------------------------------------

	function void_supp_allocations($type, $type_no, $date = "") {
		return clear_supp_alloctions($type, $type_no, $date);
	}

	//-------------------------------------------------------------------------------------------------------------

	function clear_supp_alloctions($type, $type_no, $date = "") {
		// clear any allocations for this transaction
		$sql = "SELECT * FROM supp_allocations
		WHERE (trans_type_from=$type AND trans_no_from=$type_no)
		OR (trans_type_to=" . DB::escape($type) . " AND trans_no_to=" . DB::escape($type_no) . ")";
		$result = DBOld::query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");

		while ($row = DBOld::fetch($result))
		{
			$sql = "UPDATE supp_trans SET alloc=alloc - " . $row['amt'] . "
			WHERE (type= " . $row['trans_type_from'] . " AND trans_no=" . $row['trans_no_from'] . ")
			OR (type=" . $row['trans_type_to'] . " AND trans_no=" . $row['trans_no_to'] . ")";
			//$sql = "UPDATE ".''."supp_trans SET alloc=alloc - " . $row['amt'] . "
			//	WHERE type=" . $row['trans_type_to'] . " AND trans_no=" . $row['trans_no_to'];
			DBOld::query($sql, "could not clear allocation");
			// 2008-09-20 Joe Hunt
			if ($date != "")
				Banking::exchange_variation($type, $type_no, $row['trans_type_to'], $row['trans_no_to'], $date,
					$row['amt'], PT_SUPPLIER, true);
			//////////////////////
		}

		// remove any allocations for this transaction
		$sql = "DELETE FROM supp_allocations
		WHERE (trans_type_from=" . DB::escape($type) . " AND trans_no_from=" . DB::escape($type_no) . ")
		OR (trans_type_to=" . DB::escape($type) . " AND trans_no_to=" . DB::escape($type_no) . ")";

		DBOld::query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");
	}

	//----------------------------------------------------------------------------------------
	function get_alloc_supp_sql($extra_fields = null, $extra_conditions = null, $extra_tables = null) {
		$sql = "SELECT
		trans.type,
		trans.trans_no,
		trans.reference,
		trans.tran_date,
		supplier.supp_name, 
		supplier.curr_code, 
		ov_amount+ov_gst+ov_discount AS Total,
		trans.alloc,
		trans.due_date,
		trans.supplier_id,
		supplier.address";
		/*	$sql = "SELECT trans.*,
				 ov_amount+ov_gst+ov_discount AS Total,
				 supplier.supp_name, supplier.address,
				 supplier.curr_code ";
		 */
		if ($extra_fields)
			$sql .= ", $extra_fields ";

		$sql .= " FROM supp_trans as trans, suppliers as supplier";
		if ($extra_tables)
			$sql .= " ,$extra_tables ";

		$sql .= " WHERE trans.supplier_id=supplier.supplier_id";

		if ($extra_conditions)
			$sql .= " AND $extra_conditions";

		return $sql;
	}

	//-------------------------------------------------------------------------------------------------------------

	function get_allocatable_from_supp_sql($supplier_id, $settled) {
		$settled_sql = "";
		if (!$settled) {
			$settled_sql = "AND round(ABS(ov_amount+ov_gst+ov_discount)-alloc,6) > 0";
		}

		$supp_sql = "";
		if ($supplier_id != null)
			$supp_sql = " AND trans.supplier_id = " . DB::escape($supplier_id);

		$sql = get_alloc_supp_sql("round(ABS(ov_amount+ov_gst+ov_discount)-alloc,6) <= 0 AS settled",
		 "(type=" . ST_SUPPAYMENT . " OR type=" . ST_SUPPCREDIT . " OR type=" . ST_BANKPAYMENT . ") AND (ov_amount < 0) " . $settled_sql . $supp_sql);

		return $sql;
	}

	//-------------------------------------------------------------------------------------------------------------

	function get_allocatable_to_supp_transactions($supplier_id, $trans_no = null, $type = null) {
		if ($trans_no != null && $type != null) {
			$sql = get_alloc_supp_sql("amt, supp_reference", "trans.trans_no = alloc.trans_no_to
			AND trans.type = alloc.trans_type_to
			AND alloc.trans_no_from=" . DB::escape($trans_no) . "
			AND alloc.trans_type_from=" . DB::escape($type) . "
			AND trans.supplier_id=" . DB::escape($supplier_id),
				"supp_allocations as alloc");
		} else {
			$sql = get_alloc_supp_sql(null, "round(ABS(ov_amount+ov_gst+ov_discount)-alloc,6) > 0
			AND trans.type != " . ST_SUPPAYMENT . "
			AND trans.supplier_id=" . DB::escape($supplier_id));
		}

		return DBOld::query($sql . " ORDER BY trans_no", "Cannot retreive alloc to transactions");
	}

?>