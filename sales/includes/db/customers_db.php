<?php

	/*     * ********************************************************************
						 Copyright (C) FrontAccounting, LLC.
						 Released under the terms of the GNU General Public License, GPL,
						 as published by the Free Software Foundation, either version 3
						 of the License, or (at your option) any later version.
						 This program is distributed in the hope that it will be useful,
						 but WITHOUT ANY WARRANTY; without even the implied warranty of
						 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
						 See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
						* ********************************************************************* */
	function get_customer_details($customer_id, $to = null)
	{
		if ($to == null) {
			$todate = date("Y-m-d");
		}
		else {
			$todate = Dates::date2sql($to);
		}
		$past1 = DB_Company::get_pref('past_due_days');
		$past2 = 2 * $past1;
		// removed - debtor_trans.alloc from all summations
		$value
				 = "IF(debtor_trans.type=11 OR debtor_trans.type=1 OR debtor_trans.type=12 OR debtor_trans.type=2,
	-1, 1) *" .
		 "(debtor_trans.ov_amount + debtor_trans.ov_gst + "
		 . "debtor_trans.ov_freight + debtor_trans.ov_freight_tax + "
		 . "debtor_trans.ov_discount)";
		$due = "IF (debtor_trans.type=10,debtor_trans.due_date,debtor_trans.tran_date)";
		$sql
						= "SELECT debtors_master.name, debtors_master.curr_code, payment_terms.terms,
		debtors_master.credit_limit, credit_status.dissallow_invoices, credit_status.reason_description,

		Sum(" . $value . ") AS Balance,

		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0)) AS Due,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past1,$value,0)) AS Overdue1,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past2,$value,0)) AS Overdue2

		FROM debtors_master,
			 payment_terms,
			 credit_status,
			 debtor_trans

		WHERE
			 debtors_master.payment_terms = payment_terms.terms_indicator
			 AND debtors_master.credit_status = credit_status.id
			 AND debtors_master.debtor_no = " . DB::escape($customer_id) . "
			 AND debtor_trans.tran_date <= '$todate'
			 AND debtor_trans.type <> 13
			 AND debtors_master.debtor_no = debtor_trans.debtor_no

		GROUP BY
			  debtors_master.name,
			  payment_terms.terms,
			  payment_terms.days_before_due,
			  payment_terms.day_in_following_month,
			  debtors_master.credit_limit,
			  credit_status.dissallow_invoices,
			  credit_status.reason_description";
		$result = DBOld::query($sql, "The customer details could not be retrieved");
		if (DBOld::num_rows($result) == 0) {
			/* Because there is no balance - so just retrieve the header information about the customer - the choice is do one query to get the balance and transactions for those customers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */
			$nil_balance = true;
			$sql
			 = "SELECT debtors_master.name, debtors_master.curr_code, debtors_master.debtor_no,  payment_terms.terms,
    		debtors_master.credit_limit, credit_status.dissallow_invoices, credit_status.reason_description
    		FROM debtors_master,
    		     payment_terms,
    		     credit_status

    		WHERE
    		     debtors_master.payment_terms = payment_terms.terms_indicator
    		     AND debtors_master.credit_status = credit_status.id
    		     AND debtors_master.debtor_no = " . DB::escape($customer_id);
			$result = DBOld::query($sql, "The customer details could not be retrieved");
		}
		else {
			$nil_balance = false;
		}
		$customer_record = DBOld::fetch($result);
		if ($nil_balance == true) {
			$customer_record["Balance"]  = 0;
			$customer_record["Due"]      = 0;
			$customer_record["Overdue1"] = 0;
			$customer_record["Overdue2"] = 0;
		}
		return $customer_record;
	}

	function get_customer($customer_id)
	{
		$sql = "SELECT * FROM debtors_master WHERE debtor_no=" . DB::escape($customer_id);
		$result = DBOld::query($sql, "could not get customer");
		return DBOld::fetch($result);
	}

	function get_customer_name($customer_id)
	{
		$sql = "SELECT name FROM debtors_master WHERE debtor_no=" . DB::escape($customer_id);
		$result = DBOld::query($sql, "could not get customer");
		$row = DBOld::fetch_row($result);
		return $row[0];
	}

	function get_customer_habit($customer_id)
	{
		$sql
		 = "SELECT  debtors_master.pymt_discount,
			 credit_status.dissallow_invoices
			FROM  debtors_master,  credit_status
			WHERE  debtors_master.credit_status =  credit_status.id
				AND  debtors_master.debtor_no = " . DB::escape($customer_id);
		$result = DBOld::query($sql, "could not query customers");
		return DBOld::fetch($result);
	}

	function get_area_name($id)
	{
		$sql = "SELECT description FROM areas WHERE area_code=" . DB::escape($id);
		$result = DBOld::query($sql, "could not get sales type");
		$row = DBOld::fetch_row($result);
		return $row[0];
	}

	function get_salesman_name($id)
	{
		$sql = "SELECT salesman_name FROM salesman WHERE salesman_code=" . DB::escape($id);
		$result = DBOld::query($sql, "could not get sales type");
		$row = DBOld::fetch_row($result);
		return $row[0];
	}

	function get_current_cust_credit($customer_id)
	{
		$custdet = get_customer_details($customer_id);
		return ($customer_id > 0) ? $custdet['credit_limit'] - $custdet['Balance'] : 0;
	}

	function is_new_customer($id)
	{
		$tables = array('cust_branch', 'debtor_trans', 'recurrent_invoices', 'sales_orders');
		return !DB_Company::key_in_foreign_table($id, $tables, 'debtor_no');
	}