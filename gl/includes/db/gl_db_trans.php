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
	//--------------------------------------------------------------------------------

	// Base function for adding a GL transaction
	// $date_ is display date (non-sql)
	// $amount is in $currency currency
	// if $currency is not set, then defaults to no conversion

	function add_gl_trans($type, $trans_id, $date_, $account, $dimension, $dimension2, $memo_,
												$amount, $currency = null, $person_type_id = null, $person_id = null, $err_msg = "", $rate = 0) {

		$date = Dates::date2sql($date_);
		if ($currency != null) {
			if ($rate == 0)
				$amount_in_home_currency = Banking::to_home_currency($amount, $currency, $date_);
			else
				$amount_in_home_currency = round2($amount * $rate, user_price_dec());
		}
		else
			$amount_in_home_currency = round2($amount, user_price_dec());
		if ($dimension == null || $dimension < 0)
			$dimension = 0;
		if ($dimension2 == null || $dimension2 < 0)
			$dimension2 = 0;
		if (Config::get('logs.audits')) {
			if ($memo_ == "" || $memo_ == null)
				$memo_ = $_SESSION["wa_current_user"]->username;
			else
				$memo_ = $_SESSION["wa_current_user"]->username . " - " . $memo_;
		}
		$sql = "INSERT INTO gl_trans ( type, type_no, tran_date,
		account, dimension_id, dimension2_id, memo_, amount";

		if ($person_type_id != null)
			$sql .= ", person_type_id, person_id";

		$sql .= ") ";

		$sql .= "VALUES (" . DBOld::escape($type) . ", " . DBOld::escape($trans_id) . ", '$date',
		" . DBOld::escape($account) . ", " . DBOld::escape($dimension) . ", "
		 . DBOld::escape($dimension2) . ", " . DBOld::escape($memo_) . ", "
		 . DBOld::escape($amount_in_home_currency);

		if ($person_type_id != null)
			$sql .= ", " . DBOld::escape($person_type_id) . ", " . DBOld::escape($person_id);

		$sql .= ") ";

		if ($err_msg == "")
			$err_msg = "The GL transaction could not be inserted";

		DBOld::query($sql, $err_msg);
		return $amount_in_home_currency;
	}

	//--------------------------------------------------------------------------------

	// GL Trans for standard costing, always home currency regardless of person
	// $date_ is display date (non-sql)
	// $amount is in HOME currency

	function add_gl_trans_std_cost($type, $trans_id, $date_, $account, $dimension, $dimension2,
																 $memo_, $amount, $person_type_id = null, $person_id = null, $err_msg = "") {
		if ($amount != 0)
			return add_gl_trans($type, $trans_id, $date_, $account, $dimension, $dimension2, $memo_,
				$amount, null, $person_type_id, $person_id, $err_msg);
		else
			return 0;
	}

	// Function for even out rounding problems
	function add_gl_balance($type, $trans_id, $date_, $amount, $person_type_id = null, $person_id = null) {
		$amount = round2($amount, user_price_dec());
		if ($amount != 0)
			return add_gl_trans($type, $trans_id, $date_, DB_Company::get_pref('exchange_diff_act'), 0, 0, "",
				$amount, null, $person_type_id, $person_id, "The balanced GL transaction could not be inserted");
		else
			return 0;
	}

	//--------------------------------------------------------------------------------

	function get_gl_transactions($from_date, $to_date, $trans_no = 0,
															 $account = null, $dimension = 0, $dimension2 = 0, $filter_type = null,
															 $amount_min = null, $amount_max = null) {
		$from = Dates::date2sql($from_date);
		$to = Dates::date2sql($to_date);

		$sql = "SELECT gl_trans.*, "
		 . "chart_master.account_name FROM gl_trans, "
		 . "chart_master
		WHERE chart_master.account_code=gl_trans.account
		AND tran_date >= '$from'
		AND tran_date <= '$to'";
		if ($trans_no > 0)
			$sql .= " AND gl_trans.type_no LIKE " . DBOld::escape('%' . $trans_no);

		if ($account != null)
			$sql .= " AND gl_trans.account = " . DBOld::escape($account);

		if ($dimension != 0)
			$sql .= " AND gl_trans.dimension_id = " . ($dimension < 0 ? 0 : DBOld::escape($dimension));

		if ($dimension2 != 0)
			$sql .= " AND gl_trans.dimension2_id = " . ($dimension2 < 0 ? 0 : DBOld::escape($dimension2));

		if ($filter_type != null AND is_numeric($filter_type))
			$sql .= " AND gl_trans.type= " . DBOld::escape($filter_type);

		if ($amount_min != null)
			$sql .= " AND ABS(gl_trans.amount) >= ABS(" . DBOld::escape($amount_min) . ")";

		if ($amount_max != null)
			$sql .= " AND ABS(gl_trans.amount) <= ABS(" . DBOld::escape($amount_max) . ")";

		$sql .= " ORDER BY tran_date, counter";

		return DBOld::query($sql, "The transactions for could not be retrieved");
	}

	//--------------------------------------------------------------------------------

	function get_gl_trans($type, $trans_id) {
		$sql = "SELECT gl_trans.*, "
		 . "chart_master.account_name FROM "
		 . "gl_trans, chart_master
		WHERE chart_master.account_code=gl_trans.account
		AND gl_trans.type=" . DBOld::escape($type)
		 . " AND gl_trans.type_no=" . DBOld::escape($trans_id);

		return DBOld::query($sql, "The gl transactions could not be retrieved");
	}

	//--------------------------------------------------------------------------------

	function get_gl_wo_cost_trans($trans_id, $person_id = -1) {
		$sql = "SELECT gl_trans.*, chart_master.account_name FROM "
		 . "gl_trans, chart_master
		WHERE chart_master.account_code=gl_trans.account
		AND gl_trans.type=" . ST_WORKORDER
		 . " AND gl_trans.type_no=" . DBOld::escape($trans_id) . "
		AND gl_trans.person_type_id=" . PT_WORKORDER;
		if ($person_id != -1)
			$sql .= " AND gl_trans.person_id=" . DBOld::escape($person_id);
		$sql .= " AND amount < 0";

		return DBOld::query($sql, "The gl transactions could not be retrieved");
	}

	function get_gl_balance_from_to($from_date, $to_date, $account, $dimension = 0, $dimension2 = 0) {
		$from = Dates::date2sql($from_date);
		$to = Dates::date2sql($to_date);

		$sql = "SELECT SUM(amount) FROM gl_trans
		WHERE account='$account'";
		if ($from_date != "")
			$sql .= "  AND tran_date > '$from'";
		if ($to_date != "")
			$sql .= "  AND tran_date < '$to'";
		if ($dimension != 0)
			$sql .= " AND dimension_id = " . ($dimension < 0 ? 0 : DBOld::escape($dimension));
		if ($dimension2 != 0)
			$sql .= " AND dimension2_id = " . ($dimension2 < 0 ? 0 : DBOld::escape($dimension2));

		$result = DBOld::query($sql, "The starting balance for account $account could not be calculated");

		$row = DBOld::fetch_row($result);
		return $row[0];
	}

	//--------------------------------------------------------------------------------

	function get_gl_trans_from_to($from_date, $to_date, $account, $dimension = 0, $dimension2 = 0) {
		$from = Dates::date2sql($from_date);
		$to = Dates::date2sql($to_date);

		$sql = "SELECT SUM(amount) FROM gl_trans
		WHERE account='$account'";
		if ($from_date != "")
			$sql .= " AND tran_date >= '$from'";
		if ($to_date != "")
			$sql .= " AND tran_date <= '$to'";
		if ($dimension != 0)
			$sql .= " AND dimension_id = " . ($dimension < 0 ? 0 : DBOld::escape($dimension));
		if ($dimension2 != 0)
			$sql .= " AND dimension2_id = " . ($dimension2 < 0 ? 0 : DBOld::escape($dimension2));

		$result = DBOld::query($sql, "Transactions for account $account could not be calculated");

		$row = DBOld::fetch_row($result);
		return $row[0];
	}

	//----------------------------------------------------------------------------------------------------
	function get_balance($account, $dimension, $dimension2, $from, $to, $from_incl = true, $to_incl = true) {
		$sql = "SELECT SUM(IF(amount >= 0, amount, 0)) as debit,
		SUM(IF(amount < 0, -amount, 0)) as credit, SUM(amount) as balance 
		FROM gl_trans,chart_master,"
		 . "chart_types, chart_class
		WHERE gl_trans.account=chart_master.account_code AND "
		 . "chart_master.account_type=chart_types.id
		AND chart_types.class_id=chart_class.cid AND";

		if ($account != null)
			$sql .= " account=" . DBOld::escape($account) . " AND";
		if ($dimension != 0)
			$sql .= " dimension_id = " . ($dimension < 0 ? 0 : DBOld::escape($dimension)) . " AND";
		if ($dimension2 != 0)
			$sql .= " dimension2_id = " . ($dimension2 < 0 ? 0 : DBOld::escape($dimension2)) . " AND";
		$from_date = Dates::date2sql($from);
		if ($from_incl)
			$sql .= " tran_date >= '$from_date'  AND";
		else
			$sql .= " tran_date > IF(ctype>0 AND ctype<" . CL_INCOME . ", '0000-00-00', '$from_date') AND";
		$to_date = Dates::date2sql($to);
		if ($to_incl)
			$sql .= " tran_date <= '$to_date' ";
		else
			$sql .= " tran_date < '$to_date' ";

		$result = DBOld::query($sql, "No general ledger accounts were returned");

		return DBOld::fetch($result);
	}

	//--------------------------------------------------------------------------------

	function get_budget_trans_from_to($from_date, $to_date, $account, $dimension = 0, $dimension2 = 0) {

		$from = Dates::date2sql($from_date);
		$to = Dates::date2sql($to_date);

		$sql = "SELECT SUM(amount) FROM budget_trans
		WHERE account=" . DBOld::escape($account);
		if ($from_date != "")
			$sql .= " AND tran_date >= '$from' ";
		if ($to_date != "")
			$sql .= " AND tran_date <= '$to' ";
		if ($dimension != 0)
			$sql .= " AND dimension_id = " . ($dimension < 0 ? 0 : DBOld::escape($dimension));
		if ($dimension2 != 0)
			$sql .= " AND dimension2_id = " . ($dimension2 < 0 ? 0 : DBOld::escape($dimension2));
		$result = DBOld::query($sql, "No budget accounts were returned");

		$row = DBOld::fetch_row($result);
		return $row[0];
	}

	//--------------------------------------------------------------------------------
	//	Stores journal/bank transaction tax details if applicable
	//
	function add_gl_tax_details($gl_code, $trans_type, $trans_no, $amount, $ex_rate, $date, $memo) {
		$tax_type = Taxes::is_tax_account($gl_code);
		if (!$tax_type) return; // $gl_code is not tax account

		$tax = Tax_Types::get($tax_type);
		if ($gl_code == $tax['sales_gl_code'])
			$amount = -$amount;
		// we have to restore net amount as we cannot know the base amount
		if ($tax['rate'] == 0) {
			//		ui_msgs::display_warning(_("You should not post gl transactions
			//			to tax account with	zero tax rate."));
			$net_amount = 0;
		} else {
			// calculate net amount
			$net_amount = $amount / $tax['rate'] * 100;
		}

		add_trans_tax_details($trans_type, $trans_no, $tax['id'], $tax['rate'], 0,
			$amount, $net_amount, $ex_rate, $date, $memo);
	}

	//--------------------------------------------------------------------------------
	//
	//	Store transaction tax details for fiscal purposes with 'freezed'
	//	actual tax type rate.
	//
	function add_trans_tax_details($trans_type, $trans_no, $tax_id, $rate, $included,
																 $amount, $net_amount, $ex_rate, $tran_date, $memo) {

		$sql = "INSERT INTO trans_tax_details
		(trans_type, trans_no, tran_date, tax_type_id, rate, ex_rate,
			included_in_price, net_amount, amount, memo)
		VALUES (" . DBOld::escape($trans_type) . "," . DBOld::escape($trans_no) . ",'"
		 . Dates::date2sql($tran_date) . "'," . DBOld::escape($tax_id) . ","
		 . DBOld::escape($rate) . "," . DBOld::escape($ex_rate) . "," . ($included ? 1 : 0) . ","
		 . DBOld::escape($net_amount) . ","
		 . DBOld::escape($amount) . "," . DBOld::escape($memo) . ")";

		DBOld::query($sql, "Cannot save trans tax details");
	}

	//----------------------------------------------------------------------------------------

	function get_trans_tax_details($trans_type, $trans_no) {
		$sql = "SELECT trans_tax_details.*, "
		 . "tax_types.name AS tax_type_name
		FROM trans_tax_details,tax_types
		WHERE trans_type = " . DBOld::escape($trans_type) . "
		AND trans_no = " . DBOld::escape($trans_no) . "
		AND (net_amount != 0 OR amount != 0)
		AND tax_types.id = trans_tax_details.tax_type_id";

		return DBOld::query($sql, "The transaction tax details could not be retrieved");
	}

	//----------------------------------------------------------------------------------------

	function void_trans_tax_details($type, $type_no) {
		$sql = "UPDATE trans_tax_details SET amount=0, net_amount=0
		WHERE trans_no=" . DBOld::escape($type_no)
		 . " AND trans_type=" . DBOld::escape($type);

		DBOld::query($sql, "The transaction tax details could not be voided");
	}

	function get_tax_summary($from, $to) {
		$fromdate = Dates::date2sql($from);
		$todate = Dates::date2sql($to);

		$sql = "SELECT
				SUM(IF(trans_type=" . ST_CUSTCREDIT . " || trans_type=" . ST_SUPPINVOICE
		 . " || trans_type=" . ST_JOURNAL . ",-1,1)*
				IF(trans_type=" . ST_BANKDEPOSIT . " || trans_type=" . ST_SALESINVOICE
		 . " || (trans_type=" . ST_JOURNAL . " AND amount<0)"
		 . " || trans_type=" . ST_CUSTCREDIT . ", net_amount*ex_rate,0)) net_output,

				SUM(IF(trans_type=" . ST_CUSTCREDIT . " || trans_type=" . ST_SUPPINVOICE
		 . " || trans_type=" . ST_JOURNAL . ",-1,1)*
				IF(trans_type=" . ST_BANKDEPOSIT . " || trans_type=" . ST_SALESINVOICE
		 . " || (trans_type=" . ST_JOURNAL . " AND amount<0)"
		 . " || trans_type=" . ST_CUSTCREDIT . ", amount*ex_rate,0)) payable,

				SUM(IF(trans_type=" . ST_CUSTCREDIT . " || trans_type=" . ST_SUPPINVOICE . ",-1,1)*
				IF(trans_type=" . ST_BANKDEPOSIT . " || trans_type=" . ST_SALESINVOICE
		 . " || (trans_type=" . ST_JOURNAL . " AND amount<0)"
		 . " || trans_type=" . ST_CUSTCREDIT . ", 0, net_amount*ex_rate)) net_input,

				SUM(IF(trans_type=" . ST_CUSTCREDIT . " || trans_type=" . ST_SUPPINVOICE . ",-1,1)*
				IF(trans_type=" . ST_BANKDEPOSIT . " || trans_type=" . ST_SALESINVOICE
		 . " || (trans_type=" . ST_JOURNAL . " AND amount<0)"
		 . " || trans_type=" . ST_CUSTCREDIT . ", 0, amount*ex_rate)) collectible,
				taxrec.rate,
				ttype.id,
				ttype.name
		FROM tax_types ttype,
			 trans_tax_details taxrec
		WHERE taxrec.tax_type_id=ttype.id
			AND taxrec.trans_type != " . ST_CUSTDELIVERY . "
			AND taxrec.tran_date >= '$fromdate'
			AND taxrec.tran_date <= '$todate'
		GROUP BY ttype.id";
		//ui_msgs::display_error($sql);
		return DBOld::query($sql, "Cannot retrieve tax summary");
	}

	//--------------------------------------------------------------------------------
	// Write/update journal entries.
	//
	function write_journal_entries(&$cart, $reverse, $use_transaction = true) {

		$date_ = $cart->tran_date;
		$ref = $cart->reference;
		$memo_ = $cart->memo_;
		$trans_type = $cart->trans_type;
		$new = $cart->order_id == 0;

		if ($new)
			$cart->order_id = SysTypes::get_next_trans_no($trans_type);

		$trans_id = $cart->order_id;

		if ($use_transaction)
			DBOld::begin_transaction();

		if (!$new)
			void_journal_trans($trans_type, $trans_id, false);

		foreach ($cart->gl_items as $journal_item)
		{
			// post to first found bank account using given gl acount code.
			$is_bank_to = Banking::is_bank_account($journal_item->code_id);

			add_gl_trans($trans_type, $trans_id, $date_, $journal_item->code_id,
				$journal_item->dimension_id, $journal_item->dimension2_id,
				$journal_item->reference, $journal_item->amount);
			if ($is_bank_to) {
				add_bank_trans($trans_type, $trans_id, $is_bank_to, $ref,
					$date_, $journal_item->amount, 0, "", Banking::get_company_currency(),
					"Cannot insert a destination bank transaction");
			}
			// store tax details if the gl account is a tax account
			add_gl_tax_details($journal_item->code_id,
				ST_JOURNAL, $trans_id, $journal_item->amount, 1, $date_, $memo_);
		}

		if ($new) {
			DB_Comments::add($trans_type, $trans_id, $date_, $memo_);
			Refs::save($trans_type, $trans_id, $ref);
		} else {
			DB_Comments::update($trans_type, $trans_id, null, $memo_);
			Refs::update($trans_type, $trans_id, $ref);
		}

		DB_AuditTrail::add($trans_type, $trans_id, $date_);

		if ($reverse) {
			//$reversingDate = date(user_date_display(),
			//	Mktime(0,0,0,get_month($date_)+1,1,get_year($date_)));
			$reversingDate = Dates::begin_month(Dates::add_months($date_, 1));

			$trans_id_reverse = SysTypes::get_next_trans_no($trans_type);

			foreach ($cart->gl_items as $journal_item)
			{
				$is_bank_to = Banking::is_bank_account($journal_item->code_id);

				add_gl_trans($trans_type, $trans_id_reverse, $reversingDate,
					$journal_item->code_id, $journal_item->dimension_id, $journal_item->dimension2_id,
					$journal_item->reference, -$journal_item->amount);
				if ($is_bank_to) {
					add_bank_trans($trans_type, $trans_id_reverse, $is_bank_to, $ref,
						$reversingDate, -$journal_item->amount,
						0, "", Banking::get_company_currency(),
						"Cannot insert a destination bank transaction");
				}
				// store tax details if the gl account is a tax account
				add_gl_tax_details($journal_item->code_id,
					ST_JOURNAL, $trans_id, $journal_item->amount, 1, $reversingDate, $memo_);
			}

			DB_Comments::add($trans_type, $trans_id_reverse, $reversingDate, $memo_);

			Refs::save($trans_type, $trans_id_reverse, $ref);
			DB_AuditTrail::add($trans_type, $trans_id_reverse, $reversingDate);
		}

		if ($use_transaction)
			DBOld::commit_transaction();

		return $trans_id;
	}

	//--------------------------------------------------------------------------------------------------

	function exists_gl_trans($type, $trans_id) {
		$sql = "SELECT type_no FROM gl_trans WHERE type=" . DBOld::escape($type)
		 . " AND type_no=" . DBOld::escape($trans_id);
		$result = DBOld::query($sql, "Cannot retreive a gl transaction");

		return (DBOld::num_rows($result) > 0);
	}

	//--------------------------------------------------------------------------------------------------

	function void_gl_trans($type, $trans_id, $nested = false) {
		if (!$nested)
			DBOld::begin_transaction();

		$sql = "UPDATE gl_trans SET amount=0 WHERE type=" . DBOld::escape($type)
		 . " AND type_no=" . DBOld::escape($trans_id);

		DBOld::query($sql, "could not void gl transactions for type=$type and trans_no=$trans_id");

		if (!$nested)
			DBOld::commit_transaction();
	}

	//----------------------------------------------------------------------------------------

	function void_journal_trans($type, $type_no, $use_transaction = true) {
		if ($use_transaction)
			DBOld::begin_transaction();

		void_bank_trans($type, $type_no, true);
		//	void_gl_trans($type, $type_no, true);	 // this is done above
		//	void_trans_tax_details($type, $type_no); // ditto

		if ($use_transaction)
			DBOld::commit_transaction();
	}

?>