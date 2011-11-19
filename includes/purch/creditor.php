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
	class Purch_Creditor {

		public static function get_to_trans($supplier_id, $to = null) {


		if ($to == null)
			$todate = date("Y-m-d");
		else
			$todate = Dates::date2sql($to);
		$past1 = DB_Company::get_pref('past_due_days');
		$past2 = 2 * $past1;
		// removed - supp_trans.alloc from all summations

		$value = "(supp_trans.ov_amount + supp_trans.ov_gst + supp_trans.ov_discount)";
		$due = "IF (supp_trans.type=" . ST_SUPPINVOICE . " OR supp_trans.type=" . ST_SUPPCREDIT . ",supp_trans.due_date,supp_trans.tran_date)";
		$sql = "SELECT suppliers.supp_name, suppliers.curr_code, payment_terms.terms,

		Sum($value) AS Balance,

		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0)) AS Due,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past1,$value,0)) AS Overdue1,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past2,$value,0)) AS Overdue2

		FROM suppliers,
			 payment_terms,
			 supp_trans

		WHERE
			 suppliers.payment_terms = payment_terms.terms_indicator
			 AND suppliers.supplier_id = $supplier_id
			 AND supp_trans.tran_date <= '$todate'
			 AND suppliers.supplier_id = supp_trans.supplier_id

		GROUP BY
			  suppliers.supp_name,
			  payment_terms.terms,
			  payment_terms.days_before_due,
			  payment_terms.day_in_following_month";

		$result = DB::query($sql, "The customer details could not be retrieved");

		if (DB::num_rows($result) == 0) {

			/*Because there is no balance - so just retrieve the header information about the customer - the choice is do one query to get the balance and transactions for those customers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */

			$nil_balance = true;

			$sql = "SELECT suppliers.supp_name, suppliers.curr_code, suppliers.supplier_id,  payment_terms.terms
			FROM suppliers,
				 payment_terms
			WHERE
				 suppliers.payment_terms = payment_terms.terms_indicator
				 AND suppliers.supplier_id = " . DB::escape($supplier_id);

			$result = DB::query($sql, "The customer details could not be retrieved");
		} else {
			$nil_balance = false;
		}

		$supp = DB::fetch($result);

		if ($nil_balance == true) {
			$supp["Balance"] = 0;
			$supp["Due"] = 0;
			$supp["Overdue1"] = 0;
			$supp["Overdue2"] = 0;
		}
		return $supp;
	}

	/**
	 *	 Get how much we owe the supplier for the period
	 */
	public static function get_oweing($supplier_id, $date_from, $date_to) {
		$date_from = Dates::date2sql($date_from);
		$date_to = Dates::date2sql($date_to);
		// Sherifoz 22.06.03 Also get the description
		$sql = "SELECT


    	SUM((trans.ov_amount + trans.ov_gst  + trans.ov_discount)) AS Total


    	FROM supp_trans as trans
     	WHERE trans.ov_amount != 0
		AND trans . tran_date >= '$date_from'
		AND trans . tran_date <= '$date_to'
		AND trans.supplier_id = " . DB::escape($supplier_id) . "
		AND trans.type = " . ST_SUPPINVOICE;
		$result = DB::query($sql);
		$results = DB::fetch($result);
		return $results['Total'];
	}

	public static function get($supplier_id) {
		$sql = "SELECT * FROM suppliers WHERE supplier_id=" . DB::escape($supplier_id);

		$result = DB::query($sql, "could not get supplier");

		return DB::fetch($result);
	}

	public static function get_name($supplier_id) {
		$sql = "SELECT supp_name AS name FROM suppliers WHERE supplier_id=" . DB::escape($supplier_id);

		$result = DB::query($sql, "could not get supplier");

		$row = DB::fetch_row($result);

		return $row[0];
	}

	public static function get_accounts_name($supplier_id) {
		$sql = "SELECT payable_account,purchase_account,payment_discount_account FROM suppliers WHERE supplier_id=" . DB::escape($supplier_id);

		$result = DB::query($sql, "could not get supplier");

		return DB::fetch($result);
	}

	}
