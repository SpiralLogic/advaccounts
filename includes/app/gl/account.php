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
class GL_Account {

	function add($account_code, $account_name, $account_type, $account_code2) {
		$sql = "INSERT INTO chart_master (account_code, account_code2, account_name, account_type)
		VALUES (" . DB::escape($account_code) . ", " . DB::escape($account_code2) . ", "
		 . DB::escape($account_name) . ", " . DB::escape($account_type) . ")";

		return DB::query($sql);
	}

	function update($account_code, $account_name, $account_type, $account_code2) {
		$sql = "UPDATE chart_master SET account_name=" . DB::escape($account_name)
		 . ",account_type=" . DB::escape($account_type) . ", account_code2=" . DB::escape($account_code2)
		 . " WHERE account_code = " . DB::escape($account_code);

		return DB::query($sql);
	}



	function update_reconciled_values($reconcile_id, $reconcile_value, $reconcile_date, $end_balance, $bank_account) {
		$sql = "UPDATE bank_trans SET reconciled=$reconcile_value"
		 . " WHERE id=" . DB::escape($reconcile_id);

		DB::query($sql, "Can't change reconciliation status");
		// save last reconcilation status (date, end balance)
		$sql2 = "UPDATE bank_accounts SET last_reconciled_date='"
		 . Dates::date2sql($reconcile_date) . "',
    	    ending_reconcile_balance=$end_balance
			WHERE id=" . DB::escape($bank_account);

		DB::query($sql2, "Error updating reconciliation information");
	}



	function get_max_reconciled($date, $bank_account) {
		$date = Dates::date2sql($date);
		// temporary fix to enable fix of invalid entries made in 2.2RC
		if ($date == 0) $date = '0000-00-00';

		$sql = "SELECT MAX(reconciled) as last_date,
			 SUM(IF(reconciled<='$date', amount, 0)) as end_balance,
			 SUM(IF(reconciled<'$date', amount, 0)) as beg_balance,
			 SUM(amount) as total
		FROM bank_trans trans
		WHERE undeposited=0 AND bank_act=" . DB::escape($bank_account);
		//	." AND trans.reconciled IS NOT NULL";

		return DB::query($sql, "Cannot retrieve reconciliation data");
	}



	function get_ending_reconciled($bank_account, $bank_date) {
		$sql = "SELECT ending_reconcile_balance
		FROM bank_accounts WHERE id=" . DB::escape($bank_account)
		 . " AND last_reconciled_date=" . DB::escape($bank_date);
		$result = DB::query($sql, "Cannot retrieve last reconciliation");
		return DB::fetch($result);
	}



	function get_sql_for_reconcile($bank_account, $date) {
		$sql = "SELECT	type, trans_no, ref, trans_date,
				amount,	person_id, person_type_id, reconciled, id
		FROM bank_trans
		WHERE bank_trans.bank_act = " . DB::escape($bank_account,false,false) . "
			AND undeposited = 0 AND trans_date <= '" . Dates::date2sql($date) . "' AND (reconciled IS NULL OR reconciled='" . Dates::date2sql($date) . "')
		ORDER BY trans_date,bank_trans.id";
		// or	ORDER BY reconciled desc, trans_date,".''."bank_trans.id";
		return $sql;
	}

	function reset_sql_for_reconcile($bank_account, $date) {
		$sql = "UPDATE	reconciled
		FROM bank_trans
		WHERE bank_trans.bank_act = " . DB::escape($bank_account) . "
			AND undeposited = 0 AND reconciled = '" . Dates::date2sql($date) . "'";
		// or	ORDER BY reconciled desc, trans_date,".''."bank_trans.id";
		$result = DB::query($sql);
	}



	function delete($code) {
		$sql = "DELETE FROM chart_master WHERE account_code=" . DB::escape($code);

		DB::query($sql, "could not delete gl account");
	}

	function get_all($from = null, $to = null, $type = null) {
		$sql = "SELECT chart_master.*,chart_types.name AS AccountTypeName
		FROM chart_master,chart_types
		WHERE chart_master.account_type=chart_types.id";
		if ($from != null)
			$sql .= " AND chart_master.account_code >= " . DB::escape($from);
		if ($to != null)
			$sql .= " AND chart_master.account_code <= " . DB::escape($to);
		if ($type != null)
			$sql .= " AND account_type=" . DB::escape($type);
		$sql .= " ORDER BY account_code";

		return DB::query($sql, "could not get gl accounts");
	}

	function get($code) {
		$sql = "SELECT * FROM chart_master WHERE account_code=" . DB::escape($code);

		$result = DB::query($sql, "could not get gl account");
		return DB::fetch($result);
	}

	function is_balancesheet($code) {
		$sql = "SELECT chart_class.ctype FROM chart_class, "
		 . "chart_types, chart_master
		WHERE chart_master.account_type=chart_types.id AND
		chart_types.class_id=chart_class.cid
		AND chart_master.account_code=" . DB::escape($code);

		$result = DB::query($sql, "could not retreive the account class for $code");
		$row = DB::fetch_row($result);
		return $row[0] > 0 && $row[0] < CL_INCOME;
	}

	function get_name($code) {
		$sql = "SELECT account_name from chart_master WHERE account_code=" . DB::escape($code);

		$result = DB::query($sql, "could not retreive the account name for $code");

		if (DB::num_rows($result) == 1) {
			$row = DB::fetch_row($result);
			return $row[0];
		}

		Errors::show_db_error("could not retreive the account name for $code", $sql, true);
	}

}