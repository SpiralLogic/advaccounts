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
	//---------------------------------------------------------------------------------------------
	class GL_BankAccount {
		public static function clear_default_currency($curr_code) {

		$sql = "UPDATE bank_accounts SET dflt_curr_act=0 WHERE bank_curr_code="
		 . DB::escape($curr_code);
		DB::query($sql, "could not update default currency account");
	}

	public static function add($account_code, $account_type, $bank_account_name,
														$bank_name, $bank_account_number, $bank_address, $bank_curr_code,
														$dflt_curr_act) {
		if ($dflt_curr_act) // only one default account for any currency
			static::clear_default_currency($bank_curr_code);

		$sql = "INSERT INTO bank_accounts (account_code, account_type,
		bank_account_name, bank_name, bank_account_number, bank_address, 
		bank_curr_code, dflt_curr_act)
		VALUES (" . DB::escape($account_code) . ", " . DB::escape($account_type) . ", "
		 . DB::escape($bank_account_name) . ", " . DB::escape($bank_name) . ", "
		 . DB::escape($bank_account_number) . "," . DB::escape($bank_address) .
		 ", " . DB::escape($bank_curr_code) . ", " . DB::escape($dflt_curr_act) . ")";

		DB::query($sql, "could not add a bank account for $account_code");
	}

	//---------------------------------------------------------------------------------------------

	public static function update($id, $account_code, $account_type, $bank_account_name,
															 $bank_name, $bank_account_number, $bank_address, $bank_curr_code, $dflt_curr_act) {
		if ($dflt_curr_act) // only one default account for any currency
			static::clear_default_currency($bank_curr_code);

		$sql = "UPDATE bank_accounts	SET account_type = " . DB::escape($account_type) . ",
		account_code=" . DB::escape($account_code) . ",
		bank_account_name=" . DB::escape($bank_account_name) . ", bank_name=" . DB::escape($bank_name) . ",
		bank_account_number=" . DB::escape($bank_account_number) . ", bank_curr_code=" . DB::escape($bank_curr_code) . ",
		bank_address=" . DB::escape($bank_address) . ",
		dflt_curr_act=" . DB::escape($dflt_curr_act)
		 . " WHERE id = " . DB::escape($id);

		DB::query($sql, "could not update bank account for $account_code");
	}

	//---------------------------------------------------------------------------------------------

	public static function delete($id) {
		$sql = "DELETE FROM bank_accounts WHERE id=" . DB::escape($id);

		DB::query($sql, "could not delete bank account for $id");
	}

	//---------------------------------------------------------------------------------------------

	public static function get($id) {
		$sql = "SELECT * FROM bank_accounts WHERE id=" . DB::escape($id);

		$result = DB::query($sql, "could not retreive bank account for $id");

		return DB::fetch($result);
	}

	//---------------------------------------------------------------------------------------------
	public static function get_gl($id) {
		$sql = "SELECT account_code FROM bank_accounts WHERE id=" . DB::escape($id);

		$result = DB::query($sql, "could not retreive bank account for $id");

		$bank_account = DB::fetch($result);

		return $bank_account['account_code'];
	}

	//---------------------------------------------------------------------------------------------

	public static function get_default($curr) {
		/* default bank account is selected as first found account from:
		 . default account in $curr if any
		 . first defined account in $curr if any
		 . default account in home currency
		 . first defined account in home currency
	 */
		$home_curr = DB_Company::get_pref('curr_default');

		$sql = "SELECT b.*, b.bank_curr_code='$home_curr' as fall_back FROM "
		 . "bank_accounts b"
		 . " WHERE b.bank_curr_code=" . DB::escape($curr)
		 . " OR b.bank_curr_code='$home_curr'
		ORDER BY fall_back, dflt_curr_act desc";

		$result = DB::query($sql, "could not retreive default bank account");

		return DB::fetch($result);
	}

	public static function get_customer_default($cust_id) {
		$sql = "SELECT curr_code FROM debtors_master WHERE debtor_no=" . DB::escape($cust_id);
		$result = DB::query($sql, "could not retreive default customer currency code");
		$row = DB::fetch_row($result);
		$ba = static::get_default($row[0]);
		return $ba['id'];
	}

	}
