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
	//	Check if given account is used by any bank_account.
	//	Returns id of first bank_account using account_code, null otherwise.
	//
	//	Keep in mind that direct posting to bank account is depreciated
	//	because we have no way to select right bank account if
	//	there is more than one using given gl account.
	//
	class Bank
	{

		public static function get_exchange_rate_from_to($from_curr_code, $to_curr_code, $date_) {
			//	echo "converting from $from_curr_code to $to_curr_code <BR>";
			if ($from_curr_code == $to_curr_code) {
				return 1.0000;
			}
			$home_currency = static::for_company();
			if ($to_curr_code == $home_currency) {
				return static::get_exchange_rate_to_home_currency($from_curr_code, $date_);
			}
			if ($from_curr_code == $home_currency) {
				return static::get_exchange_rate_from_home_currency($to_curr_code, $date_);
			}
			// neither from or to are the home currency
			return static::get_exchange_rate_to_home_currency($from_curr_code, $date_) / static::get_exchange_rate_to_home_currency($to_curr_code, $date_);
		}

		public static function exchange_from_to($amount, $from_curr_code, $to_curr_code, $date_) {
			$ex_rate = static::get_exchange_rate_from_to($from_curr_code, $to_curr_code, $date_);
			return $amount / $ex_rate;
		}

		// Exchange Variations Joe Hunt 2008-09-20 ////////////////////////////////////////
		public static function exchange_variation($pyt_type, $pyt_no, $type, $trans_no, $pyt_date, $amount, $person_type, $neg = false) {
			global $systypes_array;
			if ($person_type == PT_CUSTOMER) {
				$trans = Sales_Trans::get($trans_no, $type);
				$pyt_trans = Sales_Trans::get($pyt_no, $pyt_type);
				$ar_ap_act = $trans['receivables_account'];
				$person_id = $trans['debtor_no'];
				$curr = $trans['curr_code'];
				$date = Dates::sql2date($trans['tran_date']);
			} else {
				$trans = Purch_Trans::get($trans_no, $type);
				$pyt_trans = Purch_Trans::get($pyt_no, $pyt_type);
				$supp_accs = Purch_Creditor::get_accounts_name($trans['supplier_id']);
				$ar_ap_act = $supp_accs['payable_account'];
				$person_id = $trans['supplier_id'];
				$curr = $trans['SupplierCurrCode'];
				$date = Dates::sql2date($trans['tran_date']);
			}
			if (Bank_Currency::is_company($curr)) {
				return;
			}
			$inv_amt = Num::round($amount * $trans['rate'], User::price_dec());
			$pay_amt = Num::round($amount * $pyt_trans['rate'], User::price_dec());
			if ($inv_amt != $pay_amt) {
				$diff = $inv_amt - $pay_amt;
				if ($person_type == PT_SUPPLIER) {
					$diff = -$diff;
				}
				if ($neg) {
					$diff = -$diff;
				}
				$exc_var_act = DB_Company::get_pref('exchange_diff_act');
				if (Dates::date1_greater_date2($date, $pyt_date)) {
					$memo = $systypes_array[$pyt_type] . " " . $pyt_no;
					GL_Trans::add($type, $trans_no, $date, $ar_ap_act, 0, 0, $memo, -$diff, null, $person_type, $person_id);
					GL_Trans::add($type, $trans_no, $date, $exc_var_act, 0, 0, $memo, $diff, null, $person_type, $person_id);
				} else {
					$memo = $systypes_array[$type] . " " . $trans_no;
					GL_Trans::add($pyt_type, $pyt_no, $pyt_date, $ar_ap_act, 0, 0, $memo, -$diff, null, $person_type, $person_id);
					GL_Trans::add($pyt_type, $pyt_no, $pyt_date, $exc_var_act, 0, 0, $memo, $diff, null, $person_type, $person_id);
				}
			}
		}

		public function payment_person_has_items($type) {
			switch ($type) {
				case PT_MISC :
					return true;
				case PT_QUICKENTRY :
					return Validation::check(Validation::QUICK_ENTRIES);
				case PT_WORKORDER : // 070305 changed to open workorders JH
					return Validation::check(Validation::OPEN_WORKORDERS);
				case PT_CUSTOMER :
					return Validation::check(Validation::CUSTOMERS);
				case PT_SUPPLIER :
					return Validation::check(Validation::SUPPLIERS);
				default :
					Errors::show_db_error("Invalid type sent to has_items", "");
					return false;
			}
		}

		public function payment_person_currency($type, $person_id) {
			switch ($type) {
				case PT_MISC :
				case PT_QUICKENTRY :
				case PT_WORKORDER :
					return static::for_company();
				case PT_CUSTOMER :
					return static::get_customer_currency($person_id);
				case PT_SUPPLIER :
					return static::get_supplier_currency($person_id);
				default :
					return static::for_company();
			}
		}

		public function payment_person_name($type, $person_id, $full = true, $trans_no = null) {
			global $payment_person_types;
			switch ($type) {
				case PT_MISC :
					return $person_id;
				case PT_QUICKENTRY :
					$qe = GL_QuickEntry::get($person_id);
					$comment = '';
					if (!is_null($trans_no)) {
						$comment = "<br>" . DB_Comments::get_string(ST_BANKPAYMENT, $trans_no);
					}
					return ($full ? $payment_person_types[$type] . " " : "") . $qe["description"] . $comment;
				case PT_WORKORDER :
					global $wo_cost_types;
					return $wo_cost_types[$person_id];
				case PT_CUSTOMER :
					return ($full ? $payment_person_types[$type] . " " : "") . Debtor::get_name($person_id);
				case PT_SUPPLIER :
					return ($full ? $payment_person_types[$type] . " " : "") . Purch_Creditor::get_name($person_id);
				default :
					//DisplayDBerror("Invalid type sent to person_name");
					//return;
					return '';
			}
		}
	}

?>