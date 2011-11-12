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
	function add_supp_payment($supplier_id, $date_, $bank_account,
														$amount, $discount, $ref, $memo_, $rate = 0, $charge = 0)
	{
		DB::begin_transaction();
		$supplier_currency = Banking::get_supplier_currency($supplier_id);
		$bank_account_currency = Banking::get_bank_account_currency($bank_account);
		$bank_gl_account = GL_BankAccount::get_gl($bank_account);
		if ($rate == 0) {
			$supp_amount = Banking::exchange_from_to($amount, $bank_account_currency, $supplier_currency, $date_);
			$supp_discount = Banking::exchange_from_to($discount, $bank_account_currency, $supplier_currency, $date_);
			$supp_charge = Banking::exchange_from_to($charge, $bank_account_currency, $supplier_currency, $date_);
		} else {
			$supp_amount = round($amount / $rate, User::price_dec());
			$supp_discount = round($discount / $rate, User::price_dec());
			$supp_charge = round($charge / $rate, User::price_dec());
		}
		// it's a supplier payment
		$trans_type = ST_SUPPAYMENT;
		/* Create a supp_trans entry for the supplier payment */
		$payment_id = add_supp_trans($trans_type, $supplier_id, $date_, $date_,
			$ref, "", -$supp_amount, 0, -$supp_discount, "", $rate);
		// Now debit creditors account with payment + discount
		$total = 0;
		$supplier_accounts = get_supplier_accounts($supplier_id);
		$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $supplier_accounts["payable_account"], 0, 0,
		 $supp_amount + $supp_discount, $supplier_id, "", $rate);
		// Now credit discount received account with discounts
		if ($supp_discount != 0) {
			$total += add_gl_trans_supplier($trans_type, $payment_id, $date_,
				$supplier_accounts["payment_discount_account"], 0, 0,
				-$supp_discount, $supplier_id, "", $rate);
		}
		if ($supp_charge != 0) {
			$charge_act = DB_Company::get_pref('bank_charge_act');
			$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $charge_act, 0, 0,
				$supp_charge, $supplier_id, "", $rate);
		}
		if ($supp_amount != 0) {
			$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $bank_gl_account, 0, 0,
				-($supp_amount + $supp_charge), $supplier_id, "", $rate);
		}
		/*Post a balance post if $total != 0 */
		GL_Trans::add_balance($trans_type, $payment_id, $date_, -$total, PT_SUPPLIER, $supplier_id);
		/*now enter the bank_trans entry */
		Bank_Trans::add($trans_type, $payment_id, $bank_account, $ref,
			$date_, -($amount + $supp_charge), PT_SUPPLIER,
			$supplier_id, $bank_account_currency,
			"Could not add the supplier payment bank transaction");
		DB_Comments::add($trans_type, $payment_id, $date_, $memo_);
		Refs::save($trans_type, $payment_id, $ref);
		DB::commit_transaction();
		return $payment_id;
	}

	//------------------------------------------------------------------------------------------------
	function void_supp_payment($type, $type_no)
	{
		DB::begin_transaction();
		Bank_Trans::void($type, $type_no, true);
		GL_Trans::void($type, $type_no, true);
		void_supp_allocations($type, $type_no);
		void_supp_trans($type, $type_no);
		DB::commit_transaction();
	}

?>