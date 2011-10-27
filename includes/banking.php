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

	//----------------------------------------------------------------------------------
	//	Check if given account is used by any bank_account.
	//	Returns id of first bank_account using account_code, null otherwise.
	//
	//	Keep in mind that direct posting to bank account is depreciated
	//	because we have no way to select right bank account if
	//	there is more than one using given gl account.
	//

	class Banking {
		static function is_bank_account($account_code) {
			$sql = "SELECT id FROM bank_accounts WHERE account_code='$account_code'";
			$result = DBOld::query($sql, "checking account is bank account");
			if (DBOld::num_rows($result) > 0) {
				$acct = DBOld::fetch($result);
				return $acct['id'];
			} else
				return false;
		}

		//----------------------------------------------------------------------------------

		static function is_company_currency($currency) {
			return (Banking::get_company_currency() == $currency);
		}

		//----------------------------------------------------------------------------------

		static function get_company_currency() {
			$sql = "SELECT curr_default FROM company";
			$result = DBOld::query($sql, "retreive company currency");

			if (DBOld::num_rows($result) == 0)
				Errors::show_db_error("Could not find the requested currency. Fatal.", $sql);

			$myrow = DBOld::fetch_row($result);
			return $myrow[0];
		}

		//----------------------------------------------------------------------------------

		static function get_bank_account_currency($id) {
			$sql = "SELECT bank_curr_code FROM bank_accounts WHERE id='$id'";
			$result = DBOld::query($sql, "retreive bank account currency");

			$myrow = DBOld::fetch_row($result);
			return $myrow[0];
		}

		//----------------------------------------------------------------------------------

		static function get_customer_currency($customer_id) {
			$sql = "SELECT curr_code FROM debtors_master WHERE debtor_no = '$customer_id'";

			$result = DBOld::query($sql, "Retreive currency of customer $customer_id");

			$myrow = DBOld::fetch_row($result);
			return $myrow[0];
		}

		//----------------------------------------------------------------------------------

		static function get_supplier_currency($supplier_id) {
			$sql = "SELECT curr_code FROM suppliers WHERE supplier_id = '$supplier_id'";

			$result = DBOld::query($sql, "Retreive currency of supplier $supplier_id");

			$myrow = DBOld::fetch_row($result);
			return $myrow[0];
		}

		//----------------------------------------------------------------------------------

		static function get_exchange_rate_from_home_currency($currency_code, $date_) {
			if ($currency_code == Banking::get_company_currency() || $currency_code == null)
				return 1.0000;

			$date = Dates::date2sql($date_);

			$sql = "SELECT rate_buy, max(date_) as date_ FROM exchange_rates WHERE curr_code = '$currency_code'
				AND date_ <= '$date' GROUP BY rate_buy ORDER BY date_ Desc LIMIT 1";

			$result = DBOld::query($sql, "could not query exchange rates");

			if (DBOld::num_rows($result) == 0) {
				// no stored exchange rate, just return 1
				ui_msgs::display_error(
					sprintf(_("Cannot retrieve exchange rate for currency %s as of %s. Please add exchange rate manually on Exchange Rates page."),
						$currency_code, $date_));
				return 1.000;
			}

			$myrow = DBOld::fetch_row($result);
			return $myrow[0];
		}

		//----------------------------------------------------------------------------------

		static function get_exchange_rate_to_home_currency($currency_code, $date_) {
			return 1 / Banking::get_exchange_rate_from_home_currency($currency_code, $date_);
		}

		//----------------------------------------------------------------------------------

		static function to_home_currency($amount, $currency_code, $date_) {
			$ex_rate = Banking::get_exchange_rate_to_home_currency($currency_code, $date_);
			return round2($amount / $ex_rate, user_price_dec());
		}

		//----------------------------------------------------------------------------------

		static function get_exchange_rate_from_to($from_curr_code, $to_curr_code, $date_) {
			//	echo "converting from $from_curr_code to $to_curr_code <BR>";
			if ($from_curr_code == $to_curr_code)
				return 1.0000;

			$home_currency = Banking::get_company_currency();
			if ($to_curr_code == $home_currency) {
				return Banking::get_exchange_rate_to_home_currency($from_curr_code, $date_);
			}

			if ($from_curr_code == $home_currency) {
				return Banking::get_exchange_rate_from_home_currency($to_curr_code, $date_);
			}

			// neither from or to are the home currency
			return Banking::get_exchange_rate_to_home_currency($from_curr_code, $date_) / Banking::get_exchange_rate_to_home_currency($to_curr_code, $date_);
		}

		//--------------------------------------------------------------------------------

		static function exchange_from_to($amount, $from_curr_code, $to_curr_code, $date_) {
			$ex_rate = Banking::get_exchange_rate_from_to($from_curr_code, $to_curr_code, $date_);
			return $amount / $ex_rate;
		}

		//--------------------------------------------------------------------------------
		// Exchange Variations Joe Hunt 2008-09-20 ////////////////////////////////////////

		static function exchange_variation($pyt_type, $pyt_no, $type, $trans_no, $pyt_date, $amount, $person_type, $neg = false) {
			global $systypes_array;

			if ($person_type == PT_CUSTOMER) {
				$trans = get_customer_trans($trans_no, $type);
				$pyt_trans = get_customer_trans($pyt_no, $pyt_type);
				$ar_ap_act = $trans['receivables_account'];
				$person_id = $trans['debtor_no'];
				$curr = $trans['curr_code'];
				$date = Dates::sql2date($trans['tran_date']);
			}
			else
			{
				$trans = get_supp_trans($trans_no, $type);
				$pyt_trans = get_supp_trans($pyt_no, $pyt_type);
				$supp_accs = get_supplier_accounts($trans['supplier_id']);
				$ar_ap_act = $supp_accs['payable_account'];
				$person_id = $trans['supplier_id'];
				$curr = $trans['SupplierCurrCode'];
				$date = Dates::sql2date($trans['tran_date']);
			}
			if (Banking::is_company_currency($curr))
				return;
			$inv_amt = round2($amount * $trans['rate'], user_price_dec());
			$pay_amt = round2($amount * $pyt_trans['rate'], user_price_dec());
			if ($inv_amt != $pay_amt) {
				$diff = $inv_amt - $pay_amt;
				if ($person_type == PT_SUPPLIER)
					$diff = -$diff;
				if ($neg)
					$diff = -$diff;
				$exc_var_act = DB_Company::get_pref('exchange_diff_act');
				if (Dates::date1_greater_date2($date, $pyt_date)) {
					$memo = $systypes_array[$pyt_type] . " " . $pyt_no;
					add_gl_trans($type, $trans_no, $date, $ar_ap_act, 0, 0, $memo, -$diff, null, $person_type, $person_id);
					add_gl_trans($type, $trans_no, $date, $exc_var_act, 0, 0, $memo, $diff, null, $person_type, $person_id);
				}
				else
				{
					$memo = $systypes_array[$type] . " " . $trans_no;
					add_gl_trans($pyt_type, $pyt_no, $pyt_date, $ar_ap_act, 0, 0, $memo, -$diff, null, $person_type, $person_id);
					add_gl_trans($pyt_type, $pyt_no, $pyt_date, $exc_var_act, 0, 0, $memo, $diff, null, $person_type, $person_id);
				}
			}
		}
	}

?>