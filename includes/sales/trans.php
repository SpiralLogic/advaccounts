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
	class Sales_Trans
	{
		//------------------------------------------------------------------------------
		//	Retreive parent document number(s) for given transaction
		//
		public static function get_parent($trans_type, $trans_no)
		{
			$sql = 'SELECT trans_link FROM ' . 'debtor_trans WHERE (trans_no=' . DB::escape($trans_no) . ' AND type=' . DB::escape($trans_type) . ' AND trans_link!=0)';
			$result = DB::query($sql, 'Parent document numbers cannot be retrieved');
			if (DB::num_rows($result)) {
				$link = DB::fetch($result);
				return array($link['trans_link']);
			}
			if ($trans_type != ST_SALESINVOICE) {
				return 0;
			} // this is credit note with no parent invoice
			// invoice: find batch invoice parent trans.
			$sql = 'SELECT trans_no FROM ' . 'debtor_trans WHERE (trans_link=' . DB::escape($trans_no) . ' AND type=' . get_parent_type($trans_type) . ')';
			$result = DB::query($sql, 'Delivery links cannot be retrieved');
			$delivery = array();
			if (DB::num_rows($result) > 0) {
				while ($link = DB::fetch($result)) {
					$delivery[] = $link['trans_no'];
				}
			}
			return count($delivery) ? $delivery : 0;
		}

		//----------------------------------------------------------------------------------------
		// Mark changes in debtor_trans_details
		//
		public static function update_version($type, $versions)
		{
			$sql
			 = 'UPDATE debtor_trans SET version=version+1
			WHERE type=' . DB::escape($type) . ' AND (';
			foreach ($versions as $trans_no => $version)
			{
				$where[] = '(trans_no=' . DB::escape($trans_no) . ' AND version=' . $version . ')';
			}
			$sql .= implode(' OR ', $where) . ')';
			return DB::query($sql, 'Concurrent editing conflict');
		}

		//----------------------------------------------------------------------------------------
		// Gets document header versions for transaction set of type $type
		// $trans_no = array(num1, num2,...);
		// returns array(num1=>ver1, num2=>ver2...)
		//
		public static function get_version($type, $trans_no)
		{
			if (!is_array($trans_no)) {
				$trans_no = array($trans_no);
			}
			$sql = 'SELECT trans_no, version FROM ' . 'debtor_trans
			WHERE type=' . DB::escape($type) . ' AND (';
			foreach ($trans_no as $key => $trans)
			{
				$trans_no[$key] = 'trans_no=' . $trans_no[$key];
			}
			$sql .= implode(' OR ', $trans_no) . ')';
			$res = DB::query($sql, 'document version retreival');
			$vers = array();
			while ($mysql = DB::fetch($res)) {
				$vers[$mysql['trans_no']] = $mysql['version'];
			}
			return $vers;
		}

		//----------------------------------------------------------------------------------------
		// $Total, $Tax, $Freight, $discount all in customer's currency
		// date_ is display date (non-sql)
		public static function write($trans_type, $trans_no, $debtor_no, $BranchNo,
																 $date_, $reference, $Total, $discount = 0, $Tax = 0, $Freight = 0, $FreightTax = 0,
																 $sales_type = 0, $order_no = 0, $trans_link = 0, $ship_via = 0, $due_date = "",
																 $AllocAmt = 0, $rate = 0, $dimension_id = 0, $dimension2_id = 0)
		{
			$new = $trans_no == 0;
			$curr = Banking::get_customer_currency($debtor_no);
			if ($rate == 0) {
				$rate = Banking::get_exchange_rate_from_home_currency($curr, $date_);
			}
			$SQLDate = Dates::date2sql($date_);
			if ($due_date == "") {
				$SQLDueDate = "0000-00-00";
			} else {
				$SQLDueDate = Dates::date2sql($due_date);
			}
			if ($trans_type == ST_BANKPAYMENT) {
				$Total = -$Total;
			}
			if ($trans_type == ST_CUSTPAYMENT) {
				$AllocAmt = abs($AllocAmt);
			}
			if ($new) {
				$trans_no = SysTypes::get_next_trans_no($trans_type);
				$sql
				 = "INSERT INTO debtor_trans (
		trans_no, type,
		debtor_no, branch_code,
		tran_date, due_date,
		reference, tpe,
		order_, ov_amount, ov_discount,
		ov_gst, ov_freight, ov_freight_tax,
		rate, ship_via, alloc, trans_link,
		dimension_id, dimension2_id
		) VALUES ($trans_no, " . DB::escape($trans_type) . ",
		" . DB::escape($debtor_no) . ", " . DB::escape($BranchNo) . ",
		'$SQLDate', '$SQLDueDate', " . DB::escape($reference) . ",
		" . DB::escape($sales_type) . ", " . DB::escape($order_no) . ", $Total, " . DB::escape($discount) . ", $Tax,
		" . DB::escape($Freight) . ",
		$FreightTax, $rate, " . DB::escape($ship_via) . ", $AllocAmt, " . DB::escape($trans_link) . ",
		" . DB::escape($dimension_id) . ", " . DB::escape($dimension2_id) . ")";
			}
			else { // may be optional argument should stay unchanged ?
				$sql
				 = "UPDATE debtor_trans SET
		debtor_no=" . DB::escape($debtor_no) . " , branch_code=" . DB::escape($BranchNo) . ",
		tran_date='$SQLDate', due_date='$SQLDueDate',
		reference=" . DB::escape($reference) . ", tpe=" . DB::escape($sales_type) . ", order_=" . DB::escape($order_no) . ",
		ov_amount=$Total, ov_discount=" . DB::escape($discount) . ", ov_gst=$Tax,
		ov_freight=" . DB::escape($Freight) . ", ov_freight_tax=$FreightTax, rate=$rate,
		ship_via=" . DB::escape($ship_via) . ", alloc=$AllocAmt, trans_link=$trans_link,
		dimension_id=" . DB::escape($dimension_id) . ", dimension2_id=" . DB::escape($dimension2_id) . "
		WHERE trans_no=$trans_no AND type=" . DB::escape($trans_type);
			}
			DB::query($sql, "The debtor transaction record could not be inserted");
			DB_AuditTrail::add($trans_type, $trans_no, $date_, $new ? '' : _("Updated."));
			return $trans_no;
		}

		//----------------------------------------------------------------------------------------
		public static function get($trans_id, $trans_type)
		{
			$sql
			 = "SELECT debtor_trans.*,
		ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount AS Total,
		debtors_master.name AS DebtorName, debtors_master.address, debtors_master.email AS email2,
		debtors_master.curr_code, debtors_master.tax_id, debtors_master.payment_terms ";
			if ($trans_type == ST_CUSTPAYMENT) {
				// it's a payment/refund so also get the bank account
				$sql
				 .= ", bank_accounts.bank_name, bank_accounts.bank_account_name,
			bank_accounts.account_type AS BankTransType ";
			}
			if ($trans_type == ST_SALESINVOICE || $trans_type == ST_CUSTCREDIT || $trans_type == ST_CUSTDELIVERY) {
				// it's an invoice so also get the shipper and salestype
				$sql .= ", shippers.shipper_name, "
				 . "sales_types.sales_type, "
				 . "sales_types.tax_included, "
				 . "cust_branch.*, "
				 . "debtors_master.discount, "
				 . "tax_groups.name AS tax_group_name, "
				 . "tax_groups.id AS tax_group_id ";
			}
			$sql .= " FROM debtor_trans, debtors_master ";
			if ($trans_type == ST_CUSTPAYMENT) {
				// it's a payment so also get the bank account
				$sql .= ", bank_trans, bank_accounts";
			}
			if ($trans_type == ST_SALESINVOICE || $trans_type == ST_CUSTCREDIT || $trans_type == ST_CUSTDELIVERY) {
				// it's an invoice so also get the shipper, salestypes
				$sql .= ", shippers, sales_types, cust_branch, tax_groups ";
			}
			$sql .= " WHERE debtor_trans.trans_no=" . DB::escape($trans_id) . "
		AND debtor_trans.type=" . DB::escape($trans_type) . "
		AND debtor_trans.debtor_no=debtors_master.debtor_no";
			if ($trans_type == ST_CUSTPAYMENT) {
				// it's a payment so also get the bank account
				$sql
				 .= " AND bank_trans.trans_no =$trans_id
			AND bank_trans.type=$trans_type
			AND bank_accounts.id=bank_trans.bank_act ";
			}
			if ($trans_type == ST_SALESINVOICE || $trans_type == ST_CUSTCREDIT || $trans_type == ST_CUSTDELIVERY) {
				// it's an invoice so also get the shipper
				$sql
				 .= " AND shippers.shipper_id=debtor_trans.ship_via
			AND sales_types.id = debtor_trans.tpe
			AND cust_branch.branch_code = debtor_trans.branch_code
			AND cust_branch.tax_group_id = tax_groups.id ";
			}
			$result = DB::query($sql, "Cannot retreive a debtor transaction");
			if (DB::num_rows($result) == 0) {
				// can't return nothing
				Errors::show_db_error("no debtor trans found for given params", $sql, true);
				exit;
			}
			if (DB::num_rows($result) > 1) {
				// can't return multiple
				Errors::show_db_error("duplicate debtor transactions found for given params", $sql, true);
				exit;
			}
			//return DB::fetch($result);
			$row = DB::fetch($result);
			$row['email'] = $row['email2'];
			return $row;
		}

		//----------------------------------------------------------------------------------------
		public static function exists($type, $type_no)
		{
			$sql = "SELECT trans_no FROM debtor_trans WHERE type=" . DB::escape($type) . "
		AND trans_no=" . DB::escape($type_no);
			$result = DB::query($sql, "Cannot retreive a debtor transaction");
			return (DB::num_rows($result) > 0);
		}

		//----------------------------------------------------------------------------------------
		// retreives the related sales order for a given trans
		public static function get_order($type, $type_no)
		{
			$sql = "SELECT order_ FROM debtor_trans WHERE type=" . DB::escape($type) . " AND trans_no=" . DB::escape($type_no);
			$result = DB::query($sql, "The debtor transaction could not be queried");
			$row = DB::fetch_row($result);
			return $row[0];
		}

		//----------------------------------------------------------------------------------------
		public static function get_details($type, $type_no)
		{
			$sql
			 = "SELECT debtors_master.name, debtors_master.curr_code, cust_branch.br_name
		FROM debtors_master,cust_branch,debtor_trans
		WHERE debtor_trans.type=" . DB::escape($type) . " AND debtor_trans.trans_no=" . DB::escape($type_no) . "
		AND debtors_master.debtor_no = debtor_trans.debtor_no
		AND	cust_branch.branch_code = debtor_trans.branch_code";
			$result = DB::query($sql, "could not get customer details from trans");
			return DB::fetch($result);
		}

		//----------------------------------------------------------------------------------------
		public static function void($type, $type_no)
		{
			// clear all values and mark as void
			$sql
			 = "UPDATE debtor_trans SET ov_amount=0, ov_discount=0, ov_gst=0, ov_freight=0,
		ov_freight_tax=0, alloc=0, version=version+1 WHERE type=" . DB::escape($type) . " AND trans_no=" . DB::escape($type_no);
			DB::query($sql, "could not void debtor transactions for type=$type and trans_no=$type_no");
		}

		//----------------------------------------------------------------------------------------
		public static function post_void($type, $type_no)
		{
			switch ($type) {
			case ST_SALESINVOICE :
			case ST_CUSTCREDIT	:
				void_sales_invoice($type, $type_no);
				break;
			case ST_CUSTDELIVERY :
				void_sales_delivery($type, $type_no);
				break;
			case ST_CUSTPAYMENT :
				void_customer_payment($type, $type_no);
				break;
			}
		}

		//----------------------------------------------------------------------------------------
		public static function get_link($type, $type_no)
		{
			$row = DB::query("SELECT trans_link from debtor_trans
		WHERE type=" . DB::escape($type) . " AND trans_no=" . DB::escape($type_no),
				"could not get transaction link for type=$type and trans_no=$type_no");
			return $row[0];
		}
	}
