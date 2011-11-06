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
	function clear_dflt_curr_account($curr_code) {
		$sql = "UPDATE bank_accounts SET dflt_curr_act=0 WHERE bank_curr_code="
		 . DB::escape($curr_code);
		DB::query($sql, "could not update default currency account");
	}

	function add_bank_account($account_code, $account_type, $bank_account_name,
														$bank_name, $bank_account_number, $bank_address, $bank_curr_code,
														$dflt_curr_act) {
		if ($dflt_curr_act) // only one default account for any currency
			clear_dflt_curr_account($bank_curr_code);

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

	function update_bank_account($id, $account_code, $account_type, $bank_account_name,
															 $bank_name, $bank_account_number, $bank_address, $bank_curr_code, $dflt_curr_act) {
		if ($dflt_curr_act) // only one default account for any currency
			clear_dflt_curr_account($bank_curr_code);

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

	function delete_bank_account($id) {
		$sql = "DELETE FROM bank_accounts WHERE id=" . DB::escape($id);

		DB::query($sql, "could not delete bank account for $id");
	}

	//---------------------------------------------------------------------------------------------

	function get_bank_account($id) {
		$sql = "SELECT * FROM bank_accounts WHERE id=" . DB::escape($id);

		$result = DB::query($sql, "could not retreive bank account for $id");

		return DB::fetch($result);
	}

	//---------------------------------------------------------------------------------------------
	function get_bank_gl_account($id) {
		$sql = "SELECT account_code FROM bank_accounts WHERE id=" . DB::escape($id);

		$result = DB::query($sql, "could not retreive bank account for $id");

		$bank_account = DB::fetch($result);

		return $bank_account['account_code'];
	}

	//---------------------------------------------------------------------------------------------

	function add_quick_entry($description, $type, $base_amount, $base_desc) {
		$sql = "INSERT INTO quick_entries (description, type, base_amount, base_desc)
	VALUES (" . DB::escape($description) . ", " . DB::escape($type) . ", "
		 . DB::escape($base_amount) . ", " . DB::escape($base_desc) . ")";

		DB::query($sql, "could not insert quick entry for $description");
	}

	//---------------------------------------------------------------------------------------------

	function update_quick_entry($selected_id, $description, $type, $base_amount, $base_desc) {
		$sql = "UPDATE quick_entries	SET description = " . DB::escape($description) . ",
		type=" . DB::escape($type) . ", base_amount=" . DB::escape($base_amount)
		 . ", base_desc=" . DB::escape($base_desc) . "
		WHERE id = " . DB::escape($selected_id);

		DB::query($sql, "could not update quick entry for $selected_id");
	}

	//---------------------------------------------------------------------------------------------

	function delete_quick_entry($selected_id) {
		$sql = "DELETE FROM quick_entries WHERE id=" . DB::escape($selected_id);

		DB::query($sql, "could not delete quick entry $selected_id");
	}

	//---------------------------------------------------------------------------------------------

	function add_quick_entry_line($qid, $action, $dest_id, $amount, $dim, $dim2) {
		$sql = "INSERT INTO quick_entry_lines
		(qid, action, dest_id, amount, dimension_id, dimension2_id) 
	VALUES 
		($qid, " . DB::escape($action) . "," . DB::escape($dest_id) . ",
			" . DB::escape($amount) . ", " . DB::escape($dim) . ", " . DB::escape($dim2) . ")";

		DB::query($sql, "could not insert quick entry line for $qid");
	}

	//---------------------------------------------------------------------------------------------

	function update_quick_entry_line($selected_id, $qid, $action, $dest_id, $amount, $dim, $dim2) {
		$sql = "UPDATE quick_entry_lines SET qid = " . DB::escape($qid)
		 . ", action=" . DB::escape($action) . ",
		dest_id=" . DB::escape($dest_id) . ", amount=" . DB::escape($amount)
		 . ", dimension_id=" . DB::escape($dim) . ", dimension2_id=" . DB::escape($dim2) . "
		WHERE id = " . DB::escape($selected_id);

		DB::query($sql, "could not update quick entry line for $selected_id");
	}

	//---------------------------------------------------------------------------------------------

	function delete_quick_entry_line($selected_id) {
		$sql = "DELETE FROM quick_entry_lines WHERE id=" . DB::escape($selected_id);

		DB::query($sql, "could not delete quick entry line $selected_id");
	}

	//---------------------------------------------------------------------------------------------

	function has_quick_entries($type = null) {
		$sql = "SELECT id FROM quick_entries";
		if ($type != null)
			$sql .= " WHERE type=" . DB::escape($type);

		$result = DB::query($sql, "could not retreive quick entries");
		return DB::num_rows($result) > 0;
	}

	function get_quick_entries($type = null) {
		$sql = "SELECT * FROM quick_entries";
		if ($type != null)
			$sql .= " WHERE type=" . DB::escape($type);
		$sql .= " ORDER BY description";

		return DB::query($sql, "could not retreive quick entries");
	}

	function get_quick_entry($selected_id) {
		$sql = "SELECT * FROM quick_entries WHERE id=" . DB::escape($selected_id);

		$result = DB::query($sql, "could not retreive quick entry $selected_id");

		return DB::fetch($result);
	}

	function get_quick_entry_lines($qid) {
		$sql = "SELECT quick_entry_lines.*, chart_master.account_name,
			tax_types.name as tax_name
		FROM quick_entry_lines
		LEFT JOIN chart_master ON
			quick_entry_lines.dest_id = chart_master.account_code
		LEFT JOIN tax_types ON
			quick_entry_lines.dest_id = tax_types.id
		WHERE 
			qid=" . DB::escape($qid) . " ORDER by id";

		return DB::query($sql, "could not retreive quick entries");
	}

	function has_quick_entry_lines($qid) {
		$sql = "SELECT id FROM quick_entry_lines WHERE qid=" . DB::escape($qid);

		$result = DB::query($sql, "could not retreive quick entries");
		return DB::num_rows($result) > 0;
	}

	//---------------------------------------------------------------------------------------------

	function get_quick_entry_line($selected_id) {
		$sql = "SELECT * FROM quick_entry_lines WHERE id=" . DB::escape($selected_id);

		$result = DB::query($sql, "could not retreive quick entry for $selected_id");

		return DB::fetch($result);
	}

	//---------------------------------------------------------------------------------------------

	function get_default_bank_account($curr) {
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

	function get_default_customer_bank_account($cust_id) {
		$sql = "SELECT curr_code FROM debtors_master WHERE debtor_no=" . DB::escape($cust_id);
		$result = DB::query($sql, "could not retreive default customer currency code");
		$row = DB::fetch_row($result);
		$ba = get_default_bank_account($row[0]);
		return $ba['id'];
	}

?>