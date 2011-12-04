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
	class Bank_Trans implements IVoidable
	{

		// add a bank transaction
		// $amount is in $currency
		// $date_ is display date (non-sql)
		public static function add($type, $trans_no, $bank_act, $ref, $date_,
															 $amount, $person_type_id, $person_id, $currency = "", $err_msg = "", $rate = 0)
		{
			$sqlDate = Dates::date2sql($date_);
			// convert $amount to the bank's currency
			if ($currency != "") {
				$bank_account_currency = Banking::get_bank_account_currency($bank_act);
				if ($rate == 0) {
					$to_bank_currency = Banking::get_exchange_rate_from_to($currency, $bank_account_currency, $date_);
				}
				else
				{
					$to_bank_currency = 1 / $rate;
				}
				$amount_bank = ($amount / $to_bank_currency);
			}
			else
			{
				$amount_bank = $amount;
			}
			// Also store the rate to the home
			//$BankToHomeCurrencyRate = Banking::get_exchange_rate_to_home_currency($bank_account_currency, $date_);
			$sql
			 = "INSERT INTO bank_trans (type, trans_no, bank_act, ref,
		trans_date, amount, person_type_id, person_id, undeposited) ";
			$undeposited = ($bank_act == 5 && $type == 12) ? 1 : 0;
			$sql .= "VALUES ($type, $trans_no, '$bank_act', " . DB::escape($ref) . ", '$sqlDate',
		" . DB::escape($amount_bank) . ", " . DB::escape($person_type_id)
			 . ", " . DB::escape($person_id) . ", " . DB::escape($undeposited) . ")";
			if ($err_msg == "") {
				$err_msg = "The bank transaction could not be inserted";
			}
			DB::query($sql, $err_msg);
		}


		public static function exists($type, $type_no)
		{
			$sql = "SELECT trans_no FROM bank_trans WHERE type=" . DB::escape($type)
			 . " AND trans_no=" . DB::escape($type_no);
			$result = DB::query($sql, "Cannot retreive a bank transaction");
			return (DB::num_rows($result) > 0);
		}


		public static function get($type, $trans_no = null, $person_type_id = null, $person_id = null)
		{
			$sql
			 = "SELECT *, bank_account_name, account_code, bank_curr_code
		FROM bank_trans, bank_accounts
		WHERE bank_accounts.id=bank_trans.bank_act ";
			if ($type != null) {
				$sql .= " AND type=" . DB::escape($type);
			}
			if ($trans_no != null) {
				$sql .= " AND bank_trans.trans_no = " . DB::escape($trans_no);
			}
			if ($person_type_id != null) {
				$sql .= " AND bank_trans.person_type_id = " . DB::escape($person_type_id);
			}
			if ($person_id != null) {
				$sql .= " AND bank_trans.person_id = " . DB::escape($person_id);
			}
			$sql .= " ORDER BY trans_date, bank_trans.id";
			return DB::query($sql, "query for bank transaction");
		}


		public static function void($type, $type_no, $nested = false)
		{
			if (!$nested) {
				DB::begin_transaction();
			}
			$sql
			 = "UPDATE bank_trans SET amount=0
		WHERE type=" . DB::escape($type) . " AND trans_no=" . DB::escape($type_no);
			DB::query($sql, "could not void bank transactions for type=$type and trans_no=$type_no");
			GL_Trans::void($type, $type_no, true);
			// in case it's a customer trans - probably better to check first
			Sales_Allocation::void($type, $type_no);
			Sales_Trans::void($type, $type_no);
			// in case it's a supplier trans - probably better to check first
			Purch_Allocation::void($type, $type_no);
			Purch_Trans::void($type, $type_no);
			GL_Trans::void_tax_details($type, $type_no);
			if (!$nested) {
				DB::commit_transaction();
			}
		}

	}