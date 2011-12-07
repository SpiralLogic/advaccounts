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
class GL_Bank {
	protected static function add_exchange_variation($trans_type, $trans_no, $date_, $acc_id, $account,
																	$currency, $person_type_id = null, $person_id = "")
	{
		if (Bank_Currency::is_company($currency)) {
			return;
		}
		if ($date_ == null) {
			$date_ = Dates::Today();
		}
		$for_amount = 0;
		// We have to calculate all the currency accounts belonging to the GL account
		// upto $date_ and calculate with the exchange rates. And then compare with the GL account balance.
		// 2010-02-23 Joe Hunt with help of Ary Wibowo
		$sql
		 = "SELECT SUM(bt.amount) AS for_amount, ba.bank_curr_code
		FROM bank_trans bt, bank_accounts ba
		WHERE ba.id = bt.bank_act AND ba.account_code = " . DB::escape($account) . " AND bt.trans_date<='" . Dates::date2sql($date_) . "'
		GROUP BY ba.bank_curr_code";
		$result = DB::query($sql, "Transactions for bank account $acc_id could not be calculated");
		while ($row = DB::fetch($result))
		{
			if ($row['for_amount'] == 0) {
				continue;
			}
			$rate = Bank_Currency::exchange_rate_from_home($row['bank_curr_code'], $date_);
			$for_amount += Num::round($row['for_amount'] * $rate, User::price_dec());
		}
		$amount = GL_Trans::get_from_to("", $date_, $account);
		$diff = $amount - $for_amount;
		if ($diff != 0) {
			if ($trans_type == null) {
				$trans_type = ST_JOURNAL;
			}
			if ($trans_no == null) {
				$trans_no = SysTypes::get_next_trans_no($trans_type);
			}
			if ($person_type_id == null) {
				$person_type_id = PT_MISC;
			}
			GL_Trans::add($trans_type, $trans_no, $date_, $account, 0, 0, _("Exchange Variance"),
				-$diff, null, $person_type_id, $person_id);
			GL_Trans::add($trans_type, $trans_no, $date_, DB_Company::get_pref('exchange_diff_act'), 0, 0,
				_("Exchange Variance"), $diff, null, $person_type_id, $person_id);
		}
	}



	//	Add bank tranfer to database.
	//
	//	$from_account - source bank account id
	//	$to_account -	target bank account id
	//
public static	function add_bank_transfer($from_account, $to_account, $date_,
														 $amount, $ref, $memo_, $charge = 0)
	{
		DB::begin_transaction();
		$trans_type = ST_BANKTRANSFER;
		$currency = Bank_Currency::for_company($from_account);
		$trans_no = SysTypes::get_next_trans_no($trans_type);
		$from_gl_account = Bank_Account::get_gl($from_account);
		$to_gl_account = Bank_Account::get_gl($to_account);
		$total = 0;
		// do the source account postings
		$total += GL_Trans::add($trans_type, $trans_no, $date_, $from_gl_account, 0, 0, "",
			-($amount + $charge), $currency);
		Bank_Trans::add($trans_type, $trans_no, $from_account, $ref,
			$date_, -($amount + $charge),
			PT_MISC, "", $currency,
			"Cannot insert a source bank transaction");
		static::add_exchange_variation($trans_type, $trans_no, $date_, $from_account, $from_gl_account,
			$currency, PT_MISC, "");
		if ($charge != 0) {
			/* Now Debit bank charge account with charges */
			$charge_act = DB_Company::get_pref('bank_charge_act');
			$total += GL_Trans::add($trans_type, $trans_no, $date_,
				$charge_act, 0, 0, "", $charge, $currency);
		}
		// do the destination account postings
		$total += GL_Trans::add($trans_type, $trans_no, $date_, $to_gl_account, 0, 0, "",
			$amount, $currency);
		/*Post a balance post if $total != 0 */
		GL_Trans::add_balance($trans_type, $trans_no, $date_, -$total);
		Bank_Trans::add($trans_type, $trans_no, $to_account, $ref,
			$date_, $amount, PT_MISC, "",
			$currency, "Cannot insert a destination bank transaction");
		$currency = Bank_Currency::for_company($to_account);
		static::add_exchange_variation($trans_type, $trans_no, $date_, $to_account, $to_gl_account,
			$currency, PT_MISC, "");
		DB_Comments::add($trans_type, $trans_no, $date_, $memo_);
		Ref::save($trans_type,  $ref);
		DB_AuditTrail::add($trans_type, $trans_no, $date_);
		DB::commit_transaction();
		return $trans_no;
	}


	//	Add bank payment or deposit to database.
	//
	//	$from_account - bank account id
	// $item - transaction cart (line item's amounts in bank account's currency)
	// $person_type_id - defines type of $person_id identifiers
	// $person_id	- supplier/customer/other id
	// $person_detail_id - customer branch id or not used
	//
	// returns an array of (inserted trans type, trans no)
	public static	function add_bank_transaction($trans_type, $from_account, $items, $date_,
																$person_type_id, $person_id, $person_detail_id, $ref, $memo_, $use_transaction = true)
	{
		// we can only handle type 1 (payment)and type 2 (deposit)
		if ($trans_type != ST_BANKPAYMENT && $trans_type != ST_BANKDEPOSIT) {
			Errors::show_db_error("Invalid type ($trans_type) sent to add_bank_transaction");
		}
		$do_exchange_variance = false;
		if ($use_transaction) {
			DB::begin_transaction();
		}
		$currency = Bank_Currency::for_company($from_account);
		$bank_gl_account = Bank_Account::get_gl($from_account);
		// the gl items are already inversed/negated for type 2 (deposit)
		$total_amount = $items->gl_items_total();
		if ($person_type_id == PT_CUSTOMER) {
			// we need to add a customer transaction record
			// convert to customer currency
			$cust_amount = Bank::exchange_from_to($total_amount, $currency, Bank_Currency::for_debtor($person_id), $date_);
			// we need to negate it too
			$cust_amount = -$cust_amount;
			$trans_no = Sales_Trans::write($trans_type, 0, $person_id, $person_detail_id, $date_,
				$ref, $cust_amount);
		}
		elseif ($person_type_id == PT_SUPPLIER)
		{
			// we need to add a supplier transaction record
			// convert to supp currency
			$supp_amount = Bank::exchange_from_to($total_amount, $currency, Bank_Currency::for_creditor($person_id), $date_);
			// we need to negate it too
			$supp_amount = -$supp_amount;
			$trans_no = Purch_Trans::add($trans_type, $person_id, $date_, '',
				$ref, "", $supp_amount, 0, 0);
		} else {
			$trans_no = SysTypes::get_next_trans_no($trans_type);
			$do_exchange_variance = true;
		}
		// do the source account postings
		Bank_Trans::add($trans_type, $trans_no, $from_account, $ref,
			$date_, -$total_amount,
			$person_type_id, $person_id,
			$currency,
			"Cannot insert a source bank transaction");
		$total = 0;
		foreach ($items->gl_items as $gl_item)
		{
			$is_bank_to = Bank_Account::is($gl_item->code_id);
			if ($trans_type == ST_BANKPAYMENT AND $is_bank_to) {
				// we don't allow payments to go to a bank account. use transfer for this !
				Errors::show_db_error("invalid payment entered. Cannot pay to another bank account", "");
			}
			// do the destination account postings
			$total += GL_Trans::add($trans_type, $trans_no, $date_, $gl_item->code_id,
				$gl_item->dimension_id, $gl_item->dimension2_id, $gl_item->reference,
				$gl_item->amount, $currency, $person_type_id, $person_id);
			if ($is_bank_to) {
				Bank_Trans::add($trans_type, $trans_no, $is_bank_to, $ref,
					$date_, $gl_item->amount,
					$person_type_id, $person_id, $currency,
					"Cannot insert a destination bank transaction");
				if ($do_exchange_variance) {
					static::add_exchange_variation($trans_type, $trans_no, $date_, $is_bank_to, $gl_item->code_id,
						$currency, $person_type_id, $person_id);
				}
			}
			// store tax details if the gl account is a tax account
			$amount = $gl_item->amount;
			$ex_rate = Bank_Currency::exchange_rate_from_home($currency, $date_);
			GL_Trans::add_gl_tax_details($gl_item->code_id, $trans_type, $trans_no, -$amount,
				$ex_rate, $date_, $memo_);
		}
		// do the source account postings
		GL_Trans::add($trans_type, $trans_no, $date_, $bank_gl_account, 0, 0, $memo_,
			-$total, null, $person_type_id, $person_id);
		if ($do_exchange_variance) {
			static::add_exchange_variation($trans_type, $trans_no, $date_, $from_account, $bank_gl_account,
				$currency, $person_type_id, $person_id);
		}
		DB_Comments::add($trans_type, $trans_no, $date_, $memo_);
		Ref::save($trans_type,  $ref);
		DB_AuditTrail::add($trans_type, $trans_no, $date_);
		if ($use_transaction) {
			DB::commit_transaction();
		}
		return array($trans_type, $trans_no);
	}


}