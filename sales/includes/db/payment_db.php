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
	/*
			 Write/update customer payment.
		 */
	function write_customer_payment($trans_no, $customer_id, $branch_id, $bank_account,
																	$date_, $ref, $amount, $discount, $memo_, $rate = 0, $charge = 0, $tax = 0)
	{
		DB::begin_transaction();
		$company_record = DB_Company::get_prefs();
		$payment_no = Sales_Trans::write(ST_CUSTPAYMENT, $trans_no, $customer_id, $branch_id,
			$date_, $ref, $amount, $discount, $tax, 0, 0, 0, 0, 0, 0, $date_, 0, $rate);
		$bank_gl_account = get_bank_gl_account($bank_account);
		if ($trans_no != 0) {
			DB_Comments::delete(ST_CUSTPAYMENT, $trans_no);
			Bank_Trans::void(ST_CUSTPAYMENT, $trans_no, true);
			void_gl_trans(ST_CUSTPAYMENT, $trans_no, true);
			void_cust_allocations(ST_CUSTPAYMENT, $trans_no, $date_);
		}
		$total = 0;
		/* Bank account entry first */
		$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
			$bank_gl_account, 0, 0, $amount - $charge, $customer_id,
			"Cannot insert a GL transaction for the bank account debit", $rate);
		if ($branch_id != ANY_NUMERIC) {
			$branch_data = get_branch_accounts($branch_id);
			$debtors_account = $branch_data["receivables_account"];
			$discount_account = $branch_data["payment_discount_account"];
			$tax_group = Tax_Groups::get_tax_group($branch_data["payment_discount_account"]);
		}
		else {
			$debtors_account = $company_record["debtors_act"];
			$discount_account = $company_record["default_prompt_payment_act"];
		}
		if (($discount + $amount) != 0) {
			/* Now Credit Debtors account with receipts + discounts */
			$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
				$debtors_account, 0, 0, -($discount + $amount), $customer_id,
				"Cannot insert a GL transaction for the debtors account credit", $rate);
		}
		if ($discount != 0) {
			/* Now Debit discount account with discounts allowed*/
			$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
				$discount_account, 0, 0, $discount, $customer_id,
				"Cannot insert a GL transaction for the payment discount debit", $rate);
		}
		if ($charge != 0) {
			/* Now Debit bank charge account with charges */
			$charge_act = DB_Company::get_pref('bank_charge_act');
			$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
				$charge_act, 0, 0, $charge, $customer_id,
				"Cannot insert a GL transaction for the payment bank charge debit", $rate);
		}
		if ($tax != 0) {
			$taxes = Tax_Groups::get_for_item($tax_group);
		}
		/*Post a balance post if $total != 0 */
		add_gl_balance(ST_CUSTPAYMENT, $payment_no, $date_, -$total, PT_CUSTOMER, $customer_id);
		/*now enter the bank_trans entry */
		Bank_Trans::add(ST_CUSTPAYMENT, $payment_no, $bank_account, $ref,
			$date_, $amount - $charge, PT_CUSTOMER, $customer_id,
			Banking::get_customer_currency($customer_id), "", $rate);
		DB_Comments::add(ST_CUSTPAYMENT, $payment_no, $date_, $memo_);
		Refs::save(ST_CUSTPAYMENT, $payment_no, $ref);
		DB::commit_transaction();
		return $payment_no;
	}

	//-------------------------------------------------------------------------------------------------
	function void_customer_payment($type, $type_no)
	{
		DB::begin_transaction();
		Bank_Trans::void($type, $type_no, true);
		void_gl_trans($type, $type_no, true);
		void_cust_allocations($type, $type_no);
		Sales_Trans::void($type, $type_no);
		DB::commit_transaction();
	}

?>