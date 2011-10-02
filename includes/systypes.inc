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
	//-----------------------------------------------------------------------------------------
	//	Returns next transaction number.
	//	Used only for transactions stored in tables without autoincremented key.
	//
	function get_next_trans_no($trans_type) {
		$st = get_systype_db_info($trans_type);
		if (!($st && $st[0] && $st[2])) {
			// this is in fact internal error condition.
			display_error('Internal error: invalid type passed to get_next_trans_no()');
			return 0;
		}
		$sql = "SELECT MAX(`$st[2]`) FROM $st[0]";
		if ($st[1] != null) $sql .= " WHERE `$st[1]`=$trans_type";
		$unique = false;
		$result = db_query($sql, "The next transaction number for $trans_type could not be retrieved");
		$myrow = db_fetch_row($result);
		$ref = $myrow[0];
		while (!$unique) {
			$ref++;
			$sql = "SELECT id FROM refs WHERE `id`=" . $ref . " AND `type`=" . $trans_type;
			$result = db_query($sql);
			$unique = (db_num_rows($result) > 0) ? false : true;
		}
		return $ref;
	}

	//-----------------------------------------------------------------------------
	function get_systype_db_info($type) {
		switch ($type) {
			case	 ST_JOURNAL		:
				return array("gl_trans", "type", "type_no", null, "tran_date");
			case	 ST_BANKPAYMENT	:
				return array("bank_trans", "type", "trans_no", "ref", "trans_date");
			case	 ST_BANKDEPOSIT	:
				return array("bank_trans", "type", "trans_no", "ref", "trans_date");
			case	 3				 :
				return null;
			case	 ST_BANKTRANSFER :
				return array("bank_trans", "type", "trans_no", "ref", "trans_date");
			case	 ST_SALESINVOICE :
				return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
			case	 ST_CUSTCREDIT	 :
				return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
			case	 ST_CUSTPAYMENT	:
				return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
			case	 ST_CUSTREFUND	:
				return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
			case	 ST_CUSTDELIVERY :
				return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
			case	 ST_LOCTRANSFER	:
				return array("stock_moves", "type", "trans_no", "reference", "tran_date");
			case	 ST_INVADJUST	:
				return array("stock_moves", "type", "trans_no", "reference", "tran_date");
			case	 ST_PURCHORDER	 :
				return array("purch_orders", null, "order_no", "reference", "tran_date");
			case	 ST_SUPPINVOICE	:
				return array("supp_trans", "type", "trans_no", "reference", "tran_date");
			case	 ST_SUPPCREDIT	 :
				return array("supp_trans", "type", "trans_no", "reference", "tran_date");
			case	 ST_SUPPAYMENT	 :
				return array("supp_trans", "type", "trans_no", "reference", "tran_date");
			case	 ST_SUPPRECEIVE	:
				return array("grn_batch", null, "id", "reference", "delivery_date");
			case	 ST_WORKORDER	:
				return array("workorders", null, "id", "wo_ref", "released_date");
			case	 ST_MANUISSUE	:
				return array("wo_issues", null, "issue_no", "reference", "issue_date");
			case	 ST_MANURECEIVE	:
				return array("wo_manufacture", null, "id", "reference", "date_");
			case	 ST_SALESORDER	 :
				return array("sales_orders", "trans_type", "order_no", "reference", "ord_date");
			case	 31				:
				return array("service_orders", null, "order_no", "cust_ref", "date");
			case	 ST_SALESQUOTE	 :
				return array("sales_orders", "trans_type", "order_no", "reference", "ord_date");
			case	 ST_DIMENSION	:
				return array("dimensions", null, "id", "reference", "date_");
			case	 ST_COSTUPDATE	 :
				return array("gl_trans", "type", "type_no", null, "tran_date");
		}
		display_db_error("invalid type ($type) sent to get_systype_db_info", "", true);
	}

	function get_systypes() {
		$sql = "SELECT * FROM sys_types";
		$result = db_query($sql, "could not query systypes table");
		return $result;
	}

?>
